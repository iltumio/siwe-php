<?php

/**
 * SIWE message structure definitions
 */
const SIWE_DOMAIN_REGEX = '/(?P<domain>([^\/?#]+)) wants you to sign in with your Ethereum account:\\n/';
const SIWE_ADDRESS_REGEX = '/(?P<address>0x[a-zA-Z0-9]{40})\\n\\n/';
const SIWE_STATEMENT_REGEX = '/\\n\\n((?P<statement>[^\\n]+)\\n)?\\n/';
const RFC3986_REGEX = '/(([^ :\/?#]+):)?(\/\/([^ \/?#]*))?([^ ?#]*)(\\?([^ #]*))?(#(.*))?/';
const SIWE_VERSION_REGEX = '/Version: (?P<version>1)\\n/';
const SIWE_CHAIN_ID_REGEX = '/Chain ID: (?P<chainId>[0-9]+)\\n/';
const SIWE_NONCE_REGEX = '/Nonce: (?P<nonce>[a-zA-Z0-9]{8,})\\n/';
const SIWE_DATETIME_REGEX = '/\b\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z\b/';
const SIWE_REQUEST_ID_REGEX = '/^Request ID: (?P<requestId>[-._~!$&\'()*+,;=:@%a-zA-Z0-9]*)?$/';

$SIWE_URI_LINE_REGEX = sprintf('/URI: (?P<uri>%s?)\\n/', substr(RFC3986_REGEX, 1, -1));
$SIWE_ISSUED_AT_REGEX = sprintf('/Issued At: (?P<issuedAt>%s)/', substr(SIWE_DATETIME_REGEX, 1, -1));
$SIWE_EXPIRATION_TIME_REGEX = sprintf('/Expiration Time: (?P<expirationTime>%s)?/', substr(SIWE_DATETIME_REGEX, 1, -1));
$SIWE_NOT_BEFORE_REGEX = sprintf('/Not Before: (?P<notBefore>%s)?/', substr(SIWE_DATETIME_REGEX, 1, -1));
$SIWE_RESOURCES_REGEX = sprintf('/Resources:(?P<resources>(\\n- %s)+)?/', substr(RFC3986_REGEX, 1, -1));

class SiweMessageField
{
    const DOMAIN = "domain";
    const ADDRESS = "address";
    const STATEMENT = "statement";
    const URI = "uri";
    const VERSION = "version";
    const CHAIN_ID = "chainId";
    const NONCE = "nonce";
    const ISSUED_AT = "issuedAt";
    const EXPIRATION_TIME = "expirationTime";
    const NOT_BEFORE = "notBefore";
    const REQUEST_ID = "requestId";
    const RESOURCES = "resources";
}

$RegexMap = array(
    SiweMessageField::DOMAIN => SIWE_DOMAIN_REGEX,
    SiweMessageField::ADDRESS => SIWE_ADDRESS_REGEX,
    SiweMessageField::STATEMENT => SIWE_STATEMENT_REGEX,
    SiweMessageField::URI => $SIWE_URI_LINE_REGEX,
    SiweMessageField::VERSION => SIWE_VERSION_REGEX,
    SiweMessageField::CHAIN_ID => SIWE_CHAIN_ID_REGEX,
    SiweMessageField::NONCE => SIWE_NONCE_REGEX,
    SiweMessageField::ISSUED_AT => $SIWE_ISSUED_AT_REGEX,
    SiweMessageField::EXPIRATION_TIME => $SIWE_EXPIRATION_TIME_REGEX,
    SiweMessageField::NOT_BEFORE => $SIWE_NOT_BEFORE_REGEX,
    SiweMessageField::REQUEST_ID => SIWE_REQUEST_ID_REGEX,
    SiweMessageField::RESOURCES => $SIWE_RESOURCES_REGEX,
);

function getRegex(string $field)
{
    global $RegexMap;
    return $RegexMap[$field];
}

function getMessageField(string $input, string $field)
{
    $regex = getRegex($field);
    $matches = array();
    $result = preg_match($regex, $input, $matches);

    if ($result && isset($matches[$field])) {
        return $matches[$field];
    } else {
        return null;
    }
}
