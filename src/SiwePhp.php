<?php

namespace Iltumio\SiwePhp;

use GuzzleHttp\Promise\Promise;
use Carbon\Carbon;
use Error;

require __DIR__ . "/utils.php";
require __DIR__ . "/constants.php";

class SiweMessage
{
    public $domain = "";
    public $address = "";
    public $statement = null;
    public $uri = "";
    public $version = "";
    public $chainId = 0;
    public $nonce = "";
    public $issuedAt = null;
    public $expirationTime = null;
    public $notBefore = null;
    public $requestId = null;
    public $resources = [];

    function __construct($param)
    {
        if (is_string($param)) {
            $parsedMessage = json_decode($param, true);
            $this->fromParsedMessage($parsedMessage);
        } else {
            $this->fromParsedMessage($param);
        }

        $this->validateMessage();
    }

    function fromParsedMessage($parsedMessage)
    {
        if (isset($parsedMessage["domain"])) {
            $this->domain = $parsedMessage["domain"];
        }

        if (isset($parsedMessage["address"])) {
            $this->address = $parsedMessage["address"];
        }

        if (isset($parsedMessage["statement"])) {
            $this->statement = $parsedMessage["statement"];
        }

        if (isset($parsedMessage["uri"])) {
            $this->uri = $parsedMessage["uri"];
        }

        if (isset($parsedMessage["version"])) {
            $this->version = $parsedMessage["version"];
        }

        if (isset($parsedMessage["chainId"])) {
            $this->chainId = intval($parsedMessage["chainId"], 10);
        }

        if (isset($parsedMessage["nonce"])) {
            $this->nonce = $parsedMessage["nonce"];
        }

        if (isset($parsedMessage["issuedAt"])) {
            $this->issuedAt = $parsedMessage["issuedAt"];
        }

        if (isset($parsedMessage["expirationTime"])) {
            $this->expirationTime = $parsedMessage["expirationTime"];
        }

        if (isset($parsedMessage["notBefore"])) {
            $this->notBefore = $parsedMessage["notBefore"];
        }

        if (isset($parsedMessage["requestId"])) {
            $this->requestId = $parsedMessage["requestId"];
        }

        if (isset($parsedMessage["resources"])) {
            $this->resources = $parsedMessage["resources"];
        }

        if (!$this->nonce) {
            $this->nonce = generateNonce();
        }
    }

    function toMessage()
    {
        $this->validateMessage();

        $header = "$this->domain wants you to sign in with your Ethereum account:";
        $uriField = "URI: $this->uri";

        $prefix = $header . "\n" . $this->address;
        $versionField = "Version: $this->version";

        if (!$this->chainId) {
            $this->chainId = 1;
        }

        $chainField = "Chain ID: $this->chainId";

        // Should never happen, but just in case.
        if (!$this->nonce) {
            $this->nonce = generateNonce();
        }

        $nonceField = "Nonce: $this->nonce";

        if (!$this->issuedAt) {
            $now = Carbon::now();
            $this->issuedAt = $now->format(Carbon::ATOM);
        }

        $issuedAtField = "Issued At: $this->issuedAt";

        $suffixArray = [$uriField, $versionField, $chainField, $nonceField, $issuedAtField];

        if ($this->expirationTime) {
            $expirationTimeField = "Expiration Time: $this->expirationTime";
            array_push($suffixArray, $expirationTimeField);
        }

        if ($this->notBefore) {
            $notBeforeField = "Not Before: $this->notBefore";
            array_push($suffixArray, $notBeforeField);
        }

        if ($this->requestId) {
            $requestIdField = "Request ID: $this->requestId";
            array_push($suffixArray, $requestIdField);
        }

        if (is_array($this->resources) && count($this->resources) > 0) {
            $resourcesField = toResourcesString($this->resources);
            array_push($suffixArray, $resourcesField);
        }

        $suffix = implode("\n", $suffixArray);

        $prefix .= "\n\n";
        if ($this->statement) {
            $prefix = $prefix . $this->statement . "\n";
        }

        return implode("\n", [$prefix, $suffix]);
    }

    function prepareMessage()
    {
        switch ($this->version) {
            case "1":
                return $this->toMessage();
            default:
                return $this->toMessage();
        }
    }

    function verify($params, $opts = [])
    {
        $promise = new Promise(function () use (&$promise, $params, $opts) {
            $fail = function ($result) use (&$promise, $opts) {
                if (isset($opts["suppressExceptions"]) && $opts["suppressExceptions"]) {
                    $promise->resolve($result);
                } else {
                    $promise->reject($result);
                }
            };

            if (isset($opts["debug"]) && $opts["debug"]) {
                print_r($params);
            }

            $invalidParams = checkInvalidKeys($params, VerifyParamsKeys);

            if (count($invalidParams) > 0) {
                return $fail(array(
                    "success" => false,
                    "data" => $this,
                    "error" => new Error(implode(", ", $invalidParams) . " is/are not valid key(s) for VerifyOpts."),
                ));
            }

            $signature = $params["signature"];
            $domain = $params["domain"];
            $nonce = $params["nonce"];
            $time = $params["time"];

            if ($domain && $domain != $this->domain) {
                return $fail(array(
                    "success" => false,
                    "data" => $this,
                    "error" => new Error("Domain does not match."),
                ));
            }

            if ($nonce && $nonce != $this->nonce) {
                return $fail(array(
                    "success" => false,
                    "data" => $this,
                    "error" => new Error("Nonce does not match."),
                ));
            }

            /** Check time or now */
            $checkTime = $time ? new Carbon($time) : new Carbon();

            /** Message not expired */
            if ($this->expirationTime) {
                $expirationTime = new Carbon($this->expirationTime);
                if ($checkTime->getTimestamp() >= $expirationTime->getTimestamp()) {
                    return $fail(array(
                        "success" => false,
                        "data" => $this,
                        "error" => new Error("Message expired."),
                    ));
                }
            }

            /** Message is valid already */
            if ($this->notBefore) {
                $notBefore = new Carbon($this->notBefore);
                if ($checkTime->getTimestamp() < $notBefore->getTimestamp()) {
                    return $fail(array(
                        "success" => false,
                        "data" => $this,
                        "error" => new Error("Message is not valid yet."),
                    ));
                }
            }

            try {
                $EIP4361Message = $this->prepareMessage();
            } catch (Error $e) {
                return $fail(array(
                    "success" => false,
                    "data" => $this,
                    "error" => new Error("unable to prepare message"),
                ));
            }

            /** Recover address from signature */
            try {
                $addr = verifyMessage($EIP4361Message, $signature);
            } catch (Error $e) {
                return $fail(array(
                    "success" => false,
                    "data" => $this,
                    // "error" => $e,
                    "error" => new Error("Unable to recover address")
                ));
            }

            if (strtolower($addr) == strtolower($this->address)) {
                return $promise->resolve(array(
                    "success" => true,
                    "data" => $this,
                ));
            } else {
                // TODO: check contract wallet signature
                return $fail(array(
                    "success" => false,
                    "data" => $this,
                    "error" => new Error("Signature does not match."),
                ));
            }
        });

        return $promise;
    }

    /**
     * Validates the values of this object fields.
     * @throws Throws an {ErrorType} if a field is invalid.
     */
    function validateMessage()
    {
        if (func_num_args() > 0) {
            throw new \Exception("Unexpected argument in the validateMessage function.");
        }

        /* `domain` check. */
        if (
            !isset($this->domain)
            || strlen($this->domain) == 0
            || !preg_match("/[^#?]*/", $this->domain)
        ) {
            throw new \Exception("$this->domain to be a valid domain.");
        }

        /* EIP-55 `address` check. */
        if (!isEIP55Address($this->address)) {
            throw new \Exception("$this->address is not a valid EIP-55 address.");
        }

        /** Check if the URI is valid. */
        if (
            !isset($this->uri)
            || strlen($this->uri) == 0
            || filter_var($this->uri, FILTER_VALIDATE_URL) === false
        ) {
            throw new \Exception("$this->uri to be a valid URI.");
        }

        /** Check if the version is 1. */
        if ($this->version != "1") {
            throw new \Exception("$this->version to be 1.");
        }

        /** Check if the nonce is alphanumeric and bigger then 8 characters */
        if (
            !isset($this->nonce)
            || strlen($this->nonce) < 8
            || !preg_match("/[a-zA-Z0-9]{8,}/", $this->nonce)
        ) {
            throw new \Exception("$this->nonce to be a valid nonce.");
        }

        /** `issuedAt` conforms to ISO-8601 and is a valid date. */
        if (
            isset($this->issuedAt)
            && !isValidISO8601Date($this->issuedAt)
        ) {
            throw new \Exception("$this->issuedAt to be a valid ISO-8601 date.");
        }

        /** `expirationTime` conforms to ISO-8601 and is a valid date. */
        if (
            isset($this->expirationTime)
            && !isValidISO8601Date($this->expirationTime)
        ) {
            throw new \Exception("$this->expirationTime to be a valid ISO-8601 date.");
        }

        /** `notBefore` conforms to ISO-8601 and is a valid date. */
        if (
            isset($this->notBefore)
            && !isValidISO8601Date($this->notBefore)
        ) {
            throw new \Exception("$this->notBefore to be a valid ISO-8601 date.");
        }
    }
}
