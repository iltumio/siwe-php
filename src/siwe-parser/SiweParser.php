<?php

namespace Iltumio\SiwePhp;

use SiweMessageField;

require __DIR__ . "/parser_utils.php";

class SiweParser
{
    function __construct()
    {
    }

    function parse(string $input)
    {
        $domain = getMessageField($input, SiweMessageField::DOMAIN);
        $address = getMessageField($input, SiweMessageField::ADDRESS);
        $statement = getMessageField($input, SiweMessageField::STATEMENT);
        $uri = getMessageField($input, SiweMessageField::URI);
        $version = getMessageField($input, SiweMessageField::VERSION);
        $chainId = getMessageField($input, SiweMessageField::CHAIN_ID);
        $nonce = getMessageField($input, SiweMessageField::NONCE);
        $issuedAt = getMessageField($input, SiweMessageField::ISSUED_AT);
        $expirationTime = getMessageField($input, SiweMessageField::EXPIRATION_TIME);
        $notBefore = getMessageField($input, SiweMessageField::NOT_BEFORE);
        $requestId = getMessageField($input, SiweMessageField::REQUEST_ID);
        $resources = getMessageField($input, SiweMessageField::RESOURCES);

        return array(
            "domain" => $domain,
            "address" => $address,
            "statement" => $statement,
            "uri" => $uri,
            "version" => $version,
            "chainId" => $chainId,
            "nonce" => $nonce,
            "issuedAt" => $issuedAt,
            "expirationTime" => $expirationTime,
            "notBefore" => $notBefore,
            "requestId" => $requestId,
            "resources" => $resources,
        );
    }
}
