<?php

namespace Iltumio\SiwePhp;

use SiweMessageField;

require_once __DIR__ . "/parser_utils.php";

class Rule
{
    /** @var string */
    public $field;
    /** @var bool */
    public $required;
    /** @var string */
    public $regex;

    function __construct(string $field, string $regex, bool $required)
    {
        $this->field = $field;
        $this->regex = $regex;
        $this->required = $required;
    }

    /** @return array */
    function match(string $input)
    {
        $matches = array();
        $result = preg_match($this->regex, $input, $matches, PREG_OFFSET_CAPTURE);

        if ($this->required && !$result) {
            throw new \Exception("Field $this->field not found.");
        }

        if (count($matches) == 0) {
            return array(0, "");
        }

        $firstMatch = $matches[0][0];
        $startIndex = $matches[0][1];

        if ($startIndex != 0) {
            throw new \Exception("Field $this->field out of order.");
        }

        $offset = strlen($firstMatch);
        $match = $matches[$this->field][0];

        return array($offset, $match, $startIndex);
    }
}

class Grammar
{
    /** @var string */
    public $label;
    /** @var Rule[] */
    public $rules = array();

    function __construct(string $label = "Grammar", $rules = array())
    {
        $this->label = $label;
        $this->rules = $rules;
    }

    function addRegexRule(string $field, string $regex, bool $required)
    {
        $rule = new Rule($field, $regex, $required);
        $this->addRule($rule);
    }

    function addRule(Rule $rule)
    {
        $this->rules[] = $rule;
    }

    function parse(string $input)
    {
        $to_test = $input;
        $parsedObject = array();
        foreach ($this->rules as $rule) {
            [$offset, $match] = $rule->match($to_test);

            if ($offset > 0 && $match != "") {
                $to_test = substr($to_test, $offset);
                $parsedObject[$rule->field] = $match;
            }
        }

        return $parsedObject;
    }
}

class SiweParser
{
    /** Parse the entire message; reject unknown, repeated or out-of-order fields. */
    public function parse(string $input): array
    {
        $pattern = '~\A(?P<domain>[^\s/?#]+) wants you to sign in with your Ethereum account:\n'
            . '(?P<address>0x[0-9a-fA-F]{40})\n\n'
            . '(?:(?P<statement>[^\r\n]+)\n)?\n'
            . 'URI: (?P<uri>[^\r\n]+)\nVersion: (?P<version>1)\n'
            . 'Chain ID: (?P<chainId>[0-9]+)\nNonce: (?P<nonce>[a-zA-Z0-9]{8,})\n'
            . 'Issued At: (?P<issuedAt>[^\r\n]+)'
            . '(?:\nExpiration Time: (?P<expirationTime>[^\r\n]+))?'
            . '(?:\nNot Before: (?P<notBefore>[^\r\n]+))?'
            . '(?:\nRequest ID: (?P<requestId>[^\r\n]*))?'
            . '(?:\nResources:(?P<resources>(?:\n- [^\r\n]+)*))?\z~';

        if (preg_match($pattern, $input, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
            throw new \InvalidArgumentException('Invalid SIWE message format.');
        }

        $result = [];
        foreach ($matches as $field => $value) {
            if (is_string($field) && $value !== null) {
                $result[$field] = $value;
            }
        }
        if (isset($result['resources'])) {
            $result['resources'] = $result['resources'] === '' ? []
                : array_map(static fn ($line) => substr($line, 2), explode("\n", substr($result['resources'], 1)));
        }
        if (!isEIP55Address($result['address'])) {
            throw new \InvalidArgumentException('Address not conformant to EIP-55.');
        }
        return $result;
    }
}
