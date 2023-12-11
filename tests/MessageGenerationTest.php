<?php

namespace iltumio\SiwePhp\Tests;

use PHPUnit\Framework\TestCase;
use Iltumio\SiwePhp\SiweMessage;


final class MessageGenerationTest extends TestCase
{
    public function testParsePositive(): void
    {

        $rawFile = file_get_contents(__DIR__ .  "/data/parsing_positive.json");
        $parsingPositive = json_decode($rawFile, true);

        foreach ($parsingPositive as $testTitle => $data) {
            $siweMessage = new SiweMessage($data["fields"]);

            $this->assertSame($data["message"], $siweMessage->toMessage(), "Failed $testTitle");
        }
    }

    public function testParseNegative(): void
    {

        $rawFile = file_get_contents(__DIR__ .  "/data/parsing_negative.json");
        $parsingNegative = json_decode($rawFile, true);

        foreach ($parsingNegative as $testTitle => $message) {
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
