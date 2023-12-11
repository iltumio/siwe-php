<?php

namespace iltumio\SiwePhp\Tests;

use PHPUnit\Framework\TestCase;
use Iltumio\SiwePhp\SiweMessage;


final class MessageVerificationTest extends TestCase
{
    public function testVerificationPositive(): void
    {
        $rawFile = file_get_contents(__DIR__ .  "/data/verification_positive.json");
        $verificationPositive = json_decode($rawFile, true);

        foreach ($verificationPositive as $testTitle => $data) {
            $siweMessage = new SiweMessage($data);

            if (isset($data["time"])) {
                $time = $data["time"];
            } else {
                $time = $data["issuedAt"];
            }

            $verified = $siweMessage->verify(array(
                "signature" => $data["signature"],
                "time" => $time,
                "domain" => $data["domain"],
                "nonce" => $data["nonce"],
            ), array(
                "suppressExceptions" => true,
            ));

            $result = $verified->wait();

            $success = $result["success"];

            $this->assertSame($success, true, "Failed $testTitle");
        }
    }

    public function testVerificationNegative(): void
    {
        $rawFile = file_get_contents(__DIR__ .  "/data/verification_negative.json");
        $verificationNegative = json_decode($rawFile, true);

        foreach ($verificationNegative as $testTitle => $message) {
            $is_error = false;
            try {
                new SiweMessage($message);
            } catch (\Exception $e) {
                $is_error = true;
            }

            $this->assertTrue($is_error, "Failed $testTitle");
        }
    }
}
