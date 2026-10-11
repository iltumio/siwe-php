<?php

namespace iltumio\SiwePhp\Tests;

use PHPUnit\Framework\TestCase;
use Iltumio\SiwePhp\SiweMessage;




final class MessageVerificationEIP1271Test extends TestCase
{
    public function testVerificationEIP1271(): void
    {
        if (!getenv('SIWE_RPC_URL')) {
            $this->markTestSkipped('Set SIWE_RPC_URL to run the mainnet integration test.');
        }
        $rawFile = file_get_contents(__DIR__ .  "/data/eip1271.json");
        $EIP1271 = json_decode($rawFile, true);


        foreach ($EIP1271 as $testTitle => $data) {
            $siweMessage = new SiweMessage($data["message"]);

            $promise = $siweMessage->verify(array(
                "signature" => $data["signature"],
            ), array(
                "suppressExceptions" => false,
                "providerUrl" => getenv("SIWE_RPC_URL")
            ));

            $result = $promise->wait();

            $success = $result["success"];

            $this->assertSame($success, true, "Failed $testTitle");
        }
    }
}
