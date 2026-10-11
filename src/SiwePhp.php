<?php

namespace Iltumio\SiwePhp;

use GuzzleHttp\Promise\Promise;
use Carbon\Carbon;
use Error;

require_once __DIR__ . "/utils.php";
require_once __DIR__ . "/constants.php";
require_once __DIR__ . "/siwe-parser/SiweParser.php";

class SiweMessage
{
    public $domain = "";
    public $address = "";
    public $statement = null;
    public $uri = "";
    public $version = "";
    public $chainId = null;
    public $nonce = "";
    public $issuedAt = null;
    public $expirationTime = null;
    public $notBefore = null;
    public $requestId = null;
    public $resources = [];
    private bool $resourcesHeaderPresent = false;

    function __construct($param)
    {
        if (is_string($param)) {
            $parser = new SiweParser();

            $parsedMessage = $parser->parse($param);
            $this->resourcesHeaderPresent = array_key_exists("resources", $parsedMessage);
            $this->fromParsedMessage($parsedMessage);
        } else {
            if (!is_array($param)) {
                throw new \InvalidArgumentException("Expected a SIWE string or an array of fields.");
            }
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
            if (!is_int($parsedMessage['chainId'])
                && !(is_string($parsedMessage['chainId']) && ctype_digit($parsedMessage['chainId']))) {
                throw new \InvalidArgumentException('Chain ID must be a positive integer.');
            }
            $chainId = filter_var($parsedMessage['chainId'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($chainId === false) {
                throw new \InvalidArgumentException('Chain ID must be a positive integer within the PHP integer range.');
            }
            $this->chainId = $chainId;
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

        if (!array_key_exists("nonce", $parsedMessage)) {
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

        // Needed to achieve parity with the original js library
        // const chainField = `Chain ID: ` + this.chainId || "1";
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

        if ($this->requestId !== null) {
            $requestIdField = "Request ID: $this->requestId";
            array_push($suffixArray, $requestIdField);
        }

        if (count($this->resources) > 0 || $this->resourcesHeaderPresent) {
            $resourcesField = $this->resources ? toResourcesString($this->resources) : "Resources:";
            array_push($suffixArray, $resourcesField);
        }

        $suffix = implode("\n", $suffixArray);

        $prefix .= "\n\n";
        if ($this->statement !== null && $this->statement !== "") {
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

    function verify(array $params, array $opts = [])
    {
        $promise = new Promise(function () use (&$promise, $params, $opts) {
            $fail = function ($result) use (&$promise, $opts) {
                if (isset($opts["suppressExceptions"]) && $opts["suppressExceptions"]) {
                    $promise->resolve($result);
                } else {
                    $promise->reject($result);
                }
            };

            try {
                $this->validateMessage();
            } catch (\Throwable $e) {
                return $fail(['success' => false, 'data' => $this, 'error' => $e]);
            }
            $invalidOptions = checkInvalidKeys($opts, ['suppressExceptions', 'providerUrl']);
            if ($invalidOptions) {
                return $fail(['success' => false, 'data' => $this, 'error' => new Error('Invalid verification options.')]);
            }
            if (isset($opts['providerUrl']) && !is_string($opts['providerUrl'])) {
                return $fail(['success' => false, 'data' => $this, 'error' => new Error('Invalid provider URL.')]);
            }

            $invalidParams = checkInvalidKeys($params, VerifyParamsKeys);

            if (count($invalidParams) > 0) {
                return $fail(array(
                    "success" => false,
                    "data" => $this,
                    "error" => new Error(implode(", ", $invalidParams) . " is/are not valid key(s) for VerifyParams."),
                ));
            }

            if (!isset($params['signature']) || !is_string($params['signature'])
                || !preg_match('/\A0x(?:[0-9a-fA-F]{2})*\z/', $params['signature'])) {
                return $fail(['success' => false, 'data' => $this, 'error' => new Error('Invalid signature.')]);
            }
            $signature = $params["signature"];
            $domain = "";
            $nonce = "";
            $time = "";

            if (isset($params["domain"])) {
                $domain = $params["domain"];
            }

            if (isset($params["nonce"])) {
                $nonce = $params["nonce"];
            }

            if (isset($params["time"])) {
                $time = $params["time"];
            }

            if (array_key_exists("domain", $params) && $domain !== $this->domain) {
                return $fail(array(
                    "success" => false,
                    "data" => $this,
                    "error" => new Error("Domain does not match."),
                ));
            }

            if (array_key_exists("nonce", $params) && $nonce !== $this->nonce) {
                return $fail(array(
                    "success" => false,
                    "data" => $this,
                    "error" => new Error("Nonce does not match."),
                ));
            }

            /** Check time or now */
            if (array_key_exists('time', $params) && !isValidISO8601Date($time)) {
                return $fail(['success' => false, 'data' => $this, 'error' => new Error('Invalid verification time.')]);
            }
            $checkTime = $time ? new Carbon($time) : new Carbon();

            /** Message not expired */
            if ($this->expirationTime) {
                $expirationTime = new Carbon($this->expirationTime);
                if ($checkTime >= $expirationTime) {
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
                if ($checkTime < $notBefore) {
                    return $fail(array(
                        "success" => false,
                        "data" => $this,
                        "error" => new Error("Message is not valid yet."),
                    ));
                }
            }

            try {
                $EIP4361Message = $this->prepareMessage();
            } catch (\Throwable $e) {
                return $fail(array(
                    "success" => false,
                    "data" => $this,
                    "error" => new Error("unable to prepare message"),
                ));
            }

            /** Recover address from signature */
            try {
                $addr = verifyMessage($EIP4361Message, $signature);
            } catch (\Throwable $e) {
                // Contract signatures need not have the EOA length or recovery format.
                $addr = '';
            }

            if (strtolower($addr) == strtolower($this->address)) {
                return $promise->resolve(array(
                    "success" => true,
                    "data" => $this,
                ));
            } else {
                $providerUrl = $opts['providerUrl'] ?? ($this->chainId === 1 ? DEFAULT_PROVIDER_URL : '');

                $isValid = checkContractWalletSignature($this, $signature, $providerUrl);

                if ($isValid) {
                    return $promise->resolve(array(
                        "success" => true,
                        "data" => $this,
                    ));
                } else {
                    return $fail(array(
                        "success" => false,
                        "data" => $this,
                        "error" => new Error("Signature does not match."),
                    ));
                }
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
            || !is_string($this->domain)
            || !preg_match('/\A[^\s\/?#]+\z/', $this->domain)
            || !is_string(parse_url('https://' . $this->domain, PHP_URL_HOST))
        ) {
            throw new \InvalidArgumentException("Invalid domain.");
        }

        /* EIP-55 `address` check. */
        if (!isEIP55Address($this->address)) {
            throw new \InvalidArgumentException("Invalid EIP-55 address.");
        }

        /** Check if the URI is valid. */
        if (
            !isset($this->uri)
            || !is_string($this->uri)
            || strlen($this->uri) == 0
            || filter_var($this->uri, FILTER_VALIDATE_URL) === false
        ) {
            throw new \InvalidArgumentException("Invalid URI.");
        }

        /** Check if the version is 1. */
        if ($this->version != "1") {
            throw new \InvalidArgumentException("Version must be 1.");
        }

        /** Check if the nonce is alphanumeric and bigger then 8 characters */
        if (
            !isset($this->nonce)
            || !is_string($this->nonce)
            || !preg_match("/\A[a-zA-Z0-9]{8,}\z/", $this->nonce)
        ) {
            throw new \InvalidArgumentException("Nonce must contain at least 8 alphanumeric characters.");
        }

        if ($this->chainId !== null && (!is_int($this->chainId) || $this->chainId < 1)) {
            throw new \InvalidArgumentException('Chain ID must be a positive integer.');
        }
        if ($this->statement !== null && (!is_string($this->statement)
            || preg_match('/[^\x20-\x7E]/', $this->statement))) {
            throw new \InvalidArgumentException('Statement must be a single line.');
        }
        if ($this->requestId !== null && (!is_string($this->requestId)
            || !preg_match("/\A(?:[a-zA-Z0-9._~!$&'()*+,;=:@-]|%[0-9a-fA-F]{2})*\z/", $this->requestId))) {
            throw new \InvalidArgumentException('Invalid request ID.');
        }
        if (!is_array($this->resources)) {
            throw new \InvalidArgumentException('Resources must be an array of URIs.');
        }
        foreach ($this->resources as $resource) {
            if (!is_string($resource) || filter_var($resource, FILTER_VALIDATE_URL) === false) {
                throw new \InvalidArgumentException('Invalid resource URI.');
            }
        }

        /** `issuedAt` conforms to ISO-8601 and is a valid date. */
        if (
            isset($this->issuedAt)
            && !isValidISO8601Date($this->issuedAt)
        ) {
            throw new \InvalidArgumentException("Invalid Issued At date.");
        }

        /** `expirationTime` conforms to ISO-8601 and is a valid date. */
        if (
            isset($this->expirationTime)
            && !isValidISO8601Date($this->expirationTime)
        ) {
            throw new \InvalidArgumentException("Invalid Expiration Time date.");
        }

        /** `notBefore` conforms to ISO-8601 and is a valid date. */
        if (
            isset($this->notBefore)
            && !isValidISO8601Date($this->notBefore)
        ) {
            throw new \InvalidArgumentException("Invalid Not Before date.");
        }
    }
}
