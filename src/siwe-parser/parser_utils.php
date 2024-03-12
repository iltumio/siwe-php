<?php

/**
 * SIWE message structure definitions
 */
$SIWE_DOMAIN_REGEX = '/(?P<domain>([^\/?#]+)) wants you to sign in with your Ethereum account:\\n/';
$SIWE_ADDRESS_REGEX = '/(?P<address>0x[a-zA-Z0-9]{40})\\n/';
$SIWE_STATEMENT_REGEX = '/\\n((?P<statement>[^\\n]+)\\n)?\\n/';
$RFC3986_REGEX = '/^(https?|ftp):\/\/([^\s\/$.#\-_]+):?([0-9]+)?\/?([^\s]*)$/i';
$SIWE_VERSION_REGEX = '/Version: (?P<version>1)\\n/';
$SIWE_CHAIN_ID_REGEX = '/Chain ID: (?P<chainId>[0-9]+)\\n/';
$SIWE_NONCE_REGEX = '/Nonce: (?P<nonce>[a-zA-Z0-9]{8,})\\n/';
$SIWE_DATETIME_REGEX = '/\b\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z\b/';
$SIWE_REQUEST_ID_REGEX = '/Request ID: (?P<requestId>[-._~!$&\'()*+,;=:@%a-zA-Z0-9]*)\\n/';

$SIWE_URI_LINE_REGEX = sprintf('/^URI: (?P<uri>%s)(?:\n)?/', substr($RFC3986_REGEX, 2, -3));
$SIWE_ISSUED_AT_REGEX = sprintf('/Issued At: (?P<issuedAt>%s)(?:\n)?/', substr($SIWE_DATETIME_REGEX, 1, -1));
$SIWE_EXPIRATION_TIME_REGEX = sprintf('/Expiration Time: (?P<expirationTime>%s)(?:\n)?/', substr($SIWE_DATETIME_REGEX, 1, -1));
$SIWE_NOT_BEFORE_REGEX = sprintf('/Not Before: (?P<notBefore>%s)\\n/', substr($SIWE_DATETIME_REGEX, 1, -1));
$SIWE_RESOURCES_REGEX = '/^Resources:\n(?P<resources>(?:- .*\n?)+)/m';

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
