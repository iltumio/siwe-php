<?php

namespace Iltumio\SiwePhp\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Iltumio\SiwePhp\SiweMessage;
use PHPUnit\Framework\TestCase;
use function Iltumio\SiwePhp\checkContractWalletSignature;
use function Iltumio\SiwePhp\generateNonce;
use function Iltumio\SiwePhp\hashMessage;
use function Iltumio\SiwePhp\isEIP55Address;
use function Iltumio\SiwePhp\isValidISO8601Date;

final class RegressionTest extends TestCase
{
    private function fields(): array
    {
        return [
            'domain' => 'example.com',
            'address' => '0xC02aaA39b223FE8D0A0e5C4F27eAD9083C756Cc2',
            'uri' => 'https://example.com/login',
            'version' => '1',
            'chainId' => 1,
            'nonce' => 'abcdefgh',
            'issuedAt' => '2026-10-11T12:00:00Z',
        ];
    }

    public function testGeneratedNonceCanBeUsedInAMessage(): void
    {
        $nonce = generateNonce();
        self::assertMatchesRegularExpression('/\A[a-zA-Z0-9]{32}\z/', $nonce);
        self::assertNotSame($nonce, generateNonce());
        $fields = $this->fields();
        unset($fields['nonce']);
        $message = new SiweMessage($fields);
        self::assertSame($message->nonce, (new SiweMessage($message->toMessage()))->nonce);
    }

    public function testAllPositiveFixturesRoundTripThroughTheParser(): void
    {
        $fixtures = json_decode(file_get_contents(__DIR__ . '/data/parsing_positive.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($fixtures as $title => $fixture) {
            $message = new SiweMessage($fixture['message']);
            self::assertSame($fixture['message'], $message->toMessage(), $title);
            self::assertSame($fixture['fields']['resources'] ?? [], $message->resources, $title);
        }
    }

    public function testOptionalFieldsAndFractionalDatesRoundTrip(): void
    {
        $fields = $this->fields() + [
            'expirationTime' => '2026-10-11T14:00:00.123456+02:00',
            'notBefore' => '2026-10-11T11:59:59.123Z',
            'requestId' => '0',
            'resources' => ['ipfs://example/claim', 'https://example.com/claim'],
        ];
        $message = new SiweMessage($fields);
        $parsed = new SiweMessage($message->toMessage());
        self::assertSame($message->toMessage(), $parsed->toMessage());
        self::assertSame($fields['resources'], $parsed->resources);
        self::assertSame('0', $parsed->requestId);
        $message->requestId = '';
        self::assertSame('', (new SiweMessage($message->toMessage()))->requestId);
    }

    public function testEmptyResourcesHeaderIsPreserved(): void
    {
        $raw = (new SiweMessage($this->fields()))->toMessage() . "\nResources:";
        self::assertSame($raw, (new SiweMessage($raw))->toMessage());
    }

    public function testParserRejectsTrailingOrRepeatedFields(): void
    {
        $message = (new SiweMessage($this->fields()))->toMessage();
        foreach (["\nignored", "\nNonce: abcdefgh", "\nIssued At: 2026-10-11T12:00:00Z", "\n"] as $suffix) {
            try {
                new SiweMessage($message . $suffix);
                self::fail('Trailing data must be rejected.');
            } catch (\InvalidArgumentException $e) {
                self::assertSame('Invalid SIWE message format.', $e->getMessage());
            }
        }
    }

    public function testInvalidObjectFieldsAreRejected(): void
    {
        foreach ([
            ['nonce', "abcdefgh\ninjected"], ['nonce', ''],
            ['domain', "example.com\ninjected"], ['domain', 'example.com/path'],
            ['statement', "hello\ninjected"], ['chainId', '1garbage'], ['chainId', 0],
            ['chainId', PHP_INT_MAX . '0'], ['resources', 'not an array'],
            ['resources', ["https://example.com\ninjected"]], ['requestId', "a\nb"],
            ['issuedAt', '2026-02-30T12:00:00Z'], ['issuedAt', '2026-10-11'],
            ['issuedAt', '2026-10-11T12:00:00+99:00'], ['domain', 'example.com:invalid'],
        ] as [$field, $value]) {
            $fields = $this->fields();
            $fields[$field] = $value;
            try {
                new SiweMessage($fields);
            } catch (\Exception $e) {
                self::assertNotSame('', $e->getMessage());
                continue;
            }
            self::fail('Invalid ' . $field . ' was accepted.');
        }
        self::assertFalse(isEIP55Address('0x' . str_repeat('g', 40)));
        self::assertFalse(isValidISO8601Date('not a date'));
    }

    public function testInvalidVerificationInputsRespectSuppression(): void
    {
        $message = new SiweMessage($this->fields());
        foreach ([[], ['signature' => 'invalid'], ['signature' => '0x12', 'time' => 'tomorrow'],
            ['signature' => '0x12', 'nonce' => ''], ['signature' => '0x12', 'domain' => '']] as $params) {
            $result = $message->verify($params, ['suppressExceptions' => true, 'providerUrl' => ''])->wait();
            self::assertFalse($result['success']);
            self::assertInstanceOf(\Throwable::class, $result['error']);
        }
        $this->expectException(\GuzzleHttp\Promise\RejectionException::class);
        $message->verify([])->wait();
    }

    public function testExpirationPreservesSubsecondPrecision(): void
    {
        $fields = $this->fields() + ['expirationTime' => '2026-10-11T12:00:00.500Z'];
        $message = new SiweMessage($fields);
        $before = $message->verify(['signature' => '0x12', 'time' => '2026-10-11T12:00:00.499Z'],
            ['suppressExceptions' => true, 'providerUrl' => ''])->wait();
        self::assertSame('Signature does not match.', $before['error']->getMessage());
        $at = $message->verify(['signature' => '0x12', 'time' => '2026-10-11T12:00:00.500Z'],
            ['suppressExceptions' => true])->wait();
        self::assertSame('Message expired.', $at['error']->getMessage());
    }

    public function testContractSignatureRequestUsesCorrectAbiEncoding(): void
    {
        $history = [];
        $mock = new MockHandler([new Response(200, [], json_encode([
            'jsonrpc' => '2.0', 'id' => 1, 'result' => '0x1626ba7e' . str_repeat('0', 56),
        ]))]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));
        $client = new Client(['handler' => $stack]);
        $message = new SiweMessage($this->fields());
        self::assertTrue(checkContractWalletSignature($message, '0x123456', 'https://rpc.example.com', $client));
        $body = json_decode((string) $history[0]['request']->getBody(), true);
        self::assertSame('eth_call', $body['method']);
        self::assertSame($message->address, $body['params'][0]['to']);
        self::assertSame('latest', $body['params'][1]);
        self::assertSame('0x1626ba7e' . hashMessage($message->toMessage())
            . str_repeat('0', 62) . '40' . str_repeat('0', 63) . '3'
            . '123456' . str_repeat('0', 58), $body['params'][0]['data']);
        self::assertFalse($history[0]['options']['allow_redirects']);
        self::assertSame(10, $history[0]['options']['timeout']);
    }

    public function testContractVerificationFailsClosed(): void
    {
        $message = new SiweMessage($this->fields());
        foreach ([
            '{}', 'not json', '{"result":"0xffffffff"}', '{"result":"0x1626ba7e"}',
            '{"error":{"code":-32000},"result":"0x1626ba7e"}',
        ] as $body) {
            $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200, [], $body)]))]);
            self::assertFalse(checkContractWalletSignature($message, '0x12', 'https://rpc.example.com', $client));
        }
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(500)]))]);
        self::assertFalse(checkContractWalletSignature($message, '0x12', 'https://rpc.example.com', $client));
        self::assertFalse(checkContractWalletSignature($message, '0x12', 'file:///tmp/rpc'));
        self::assertFalse(checkContractWalletSignature($message, '0x1', 'https://rpc.example.com'));
    }
}
