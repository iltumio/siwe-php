<?php

namespace iltumio\SiwePhp\Tests;

use PHPUnit\Framework\TestCase;
use Iltumio\SiwePhp\SiweMessage;

final class MessageVerificationTest extends TestCase
{
    public function verify($data, $suppressExceptions = false)
    {
        $siweMessage = new SiweMessage($data);

        if (isset($data["time"])) {
            $time = $data["time"];
        } else {
            $time = $data["issuedAt"];
        }

        if (isset($data["domainBinding"])) {
            $domain = $data["domainBinding"];
        } else {
            $domain = $data["domain"];
        }

        if (isset($data["matchNonce"])) {
            $nonce = $data["matchNonce"];
        } else {
            $nonce = $data["nonce"];
        }

        $promise = $siweMessage->verify(array(
            "signature" => $data["signature"],
            "time" => $time,
            "domain" => $domain,
            "nonce" => $nonce,
        ), array(
            "suppressExceptions" => $suppressExceptions,
        ));

        return $promise;
    }

    public function testVerificationPositiveNoSuppressExceptions(): void
    {
        $rawFile = file_get_contents(__DIR__ .  "/data/verification_positive.json");
        $verificationPositive = json_decode($rawFile, true);

        foreach ($verificationPositive as $testTitle => $data) {
            $promise = $this->verify($data);

            $result = $promise->wait();

            $success = $result["success"];

            $this->assertSame($success, true, "Failed $testTitle");
        }
    }

    public function testVerificationNegativeNoSuppressExceptions(): void
    {
        $rawFile = file_get_contents(__DIR__ .  "/data/verification_negative.json");
        $verificationNegative = json_decode($rawFile, true);

        foreach ($verificationNegative as $testTitle => $data) {
            $is_error = false;

            try {
                $promise = $this->verify($data);
                $promise->wait();
            } catch (\Exception $e) {
                $is_error = true;
            }

            $this->assertTrue($is_error, "Failed $testTitle");
        }
    }

    public function testVerificationFromJs(): void
    {
        $rawFile = file_get_contents(__DIR__ .  "/data/from_js.json");
        $fromJs = json_decode($rawFile, true);

        $promise = $this->verify($fromJs);

        $result = $promise->wait();

        $this->assertTrue($result["success"], "Failed from_js");
    }
}
