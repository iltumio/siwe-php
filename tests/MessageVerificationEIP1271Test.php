<?php

namespace iltumio\SiwePhp\Tests;

use PHPUnit\Framework\TestCase;
use Iltumio\SiwePhp\SiweMessage;

use const Iltumio\SiwePhp\DEFAULT_PROVIDER_URL;

require __DIR__ . "../../src/siwe-parser/SiweParser.php";

final class MessageVerificationEIP1271Test extends TestCase
{
    public function testVerificationEIP1271(): void
    {
        $rawFile = file_get_contents(__DIR__ .  "/data/eip1271.json");
        $EIP1271 = json_decode($rawFile, true);

        // Use a public node that doesn't require API key
        $publicNode = "https://ethereum.publicnode.com";

        foreach ($EIP1271 as $testTitle => $data) {
            $siweMessage = new SiweMessage($data["message"]);

            $promise = $siweMessage->verify(array(
                "signature" => $data["signature"],
            ), array(
                "suppressExceptions" => false,
                "providerUrl" => $publicNode
            ));

            $result = $promise->wait();
            $success = $result["success"];
            $this->assertSame($success, true, "Failed $testTitle");
        }
    }
}