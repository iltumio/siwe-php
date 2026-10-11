<?php

namespace Iltumio\SiwePhp;

use DateTime;
use kornrunner\Keccak;
use Elliptic\EC;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

function isEIP55Address($address)
{
    if (!is_string($address) || !preg_match('/\A0x[0-9a-fA-F]{40}\z/', $address)) {
        return false;
    }

    $lowerAddress = strtolower(str_replace('0x', '', $address));
    $hash = Keccak::hash($lowerAddress, 256);

    $ret = "0x";

    for ($i = 0; $i < 40; $i++) {
        $charInt = intval($hash[$i], 16);

        if ($charInt >= 8) {
            $ret .= strtoupper($lowerAddress[$i]);
        } else {
            $ret .= $lowerAddress[$i];
        }
    }

    return $address == $ret;
}

function validateDate($date)
{
    try {
        $dt = new DateTime($date);
    } catch (\Exception $e) {
        return false;
    }

    $errors = DateTime::getLastErrors();
    return $errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0);
}

function isValidISO8601Date($dateString)
{
    return is_string($dateString)
        && preg_match('/\A\d{4}-(?:0[1-9]|1[0-2])-(?:0[1-9]|[12]\d|3[01])[Tt](?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d(?:\.\d+)?(?:[Zz]|[+-](?:[01]\d|2[0-3]):[0-5]\d)\z/', $dateString) === 1
        && validateDate($dateString);
}

function generateNonce()
{
    return bin2hex(random_bytes(16));
}

function toResourcesString($resources = [])
{
    return "Resources:\n" . implode("\n", array_map(static fn ($resource) => "- " . $resource, $resources));
}

function checkInvalidKeys($obj, $keys = [])
{
    $invalidKeys = array_diff(array_keys((array) $obj), $keys);
    return $invalidKeys;
}

function hashMessage($message)
{
    $messageLength = strlen($message);
    $messageHash = Keccak::hash(ETHEREUM_MESSAGE_PREFIX . $messageLength . $message, 256);
    return $messageHash;
}

function verifyMessage($message, $sig)
{
    if (!is_string($sig) || !preg_match('/\A0x[0-9a-fA-F]{130}\z/', $sig)) {
        throw new \InvalidArgumentException("Expected a 65-byte hexadecimal EOA signature.");
    }
    $messageHash = hashMessage($message);
    $signature   = ["r" => substr($sig, 2, 64), "s" => substr($sig, 66, 64)];
    $recoveryId = hexdec(substr($sig, 130, 2));

    // Accept both Ethereum (27/28) and raw (0/1) recovery IDs.
    if ($recoveryId >= 27) {
        $recoveryId -= 27;
    }

    if ($recoveryId !== 0 && $recoveryId !== 1) {
        throw new \InvalidArgumentException("Invalid signature recovery ID.");
    }

    // ECDSA Recovery
    $ec = new EC('secp256k1');
    $recoveredKey = $ec->recoverPubKey($messageHash, $signature, $recoveryId);
    $recoveredAddress =  "0x" . substr(Keccak::hash(substr(hex2bin($recoveredKey->encode("hex")), 1), 256), 24);

    return $recoveredAddress;
}

/** Verify EIP-1271 via eth_call on the provider for the message's chain. */
function checkContractWalletSignature(
    SiweMessage $message,
    string $signature,
    string $providerUrl = "",
    ?ClientInterface $client = null
): bool {
    if (!filter_var($providerUrl, FILTER_VALIDATE_URL)
        || !in_array(parse_url($providerUrl, PHP_URL_SCHEME), ['http', 'https'], true)
        || !preg_match('/\A0x(?:[0-9a-fA-F]{2})*\z/', $signature)) {
        return false;
    }

    // ABI: isValidSignature(bytes32,bytes). The dynamic bytes begin at offset 64.
    $bytes = substr($signature, 2);
    $data = EIP1271_MAGICVALUE . hashMessage($message->prepareMessage())
        . str_pad('40', 64, '0', STR_PAD_LEFT)
        . str_pad(dechex(intdiv(strlen($bytes), 2)), 64, '0', STR_PAD_LEFT)
        . str_pad($bytes, (int) (ceil(strlen($bytes) / 64) * 64), '0');

    try {
        $client ??= new Client();
        $response = $client->request('POST', $providerUrl, [
            'timeout' => 10,
            'connect_timeout' => 5,
            'allow_redirects' => false,
            'json' => [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'eth_call',
                'params' => [['to' => $message->address, 'data' => $data], 'latest'],
            ],
        ]);
        $result = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        // ABI bytes4 is right-padded to one complete 32-byte word.
        return is_array($result) && !isset($result['error'])
            && isset($result['result']) && is_string($result['result'])
            && strtolower($result['result']) === EIP1271_MAGICVALUE . str_repeat('0', 56);
    } catch (\Throwable $e) {
        return false;
    }
}
