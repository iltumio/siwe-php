<?php

namespace Iltumio\SiwePhp;

use SiweMessageField;

require __DIR__ . "/parser_utils.php";

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
    /** @var Grammar */
    private $grammar;

    function __construct()
    {
        global $SIWE_URI_LINE_REGEX;
        global $SIWE_ISSUED_AT_REGEX;
        global $SIWE_EXPIRATION_TIME_REGEX;
        global $SIWE_NOT_BEFORE_REGEX;
        global $SIWE_REQUEST_ID_REGEX;
        global $SIWE_RESOURCES_REGEX;
        global $SIWE_DOMAIN_REGEX;
        global $SIWE_ADDRESS_REGEX;
        global $SIWE_STATEMENT_REGEX;
        global $SIWE_VERSION_REGEX;
        global $SIWE_CHAIN_ID_REGEX;
        global $SIWE_NONCE_REGEX;

        $this->grammar = new Grammar(
            "SIWEMessage",
            array(
                new Rule(SiweMessageField::DOMAIN, $SIWE_DOMAIN_REGEX, true),
                new Rule(SiweMessageField::ADDRESS, $SIWE_ADDRESS_REGEX, true),
                new Rule(SiweMessageField::STATEMENT, $SIWE_STATEMENT_REGEX, false),
                new Rule(SiweMessageField::URI, $SIWE_URI_LINE_REGEX, true),
                new Rule(SiweMessageField::VERSION, $SIWE_VERSION_REGEX, true),
                new Rule(SiweMessageField::CHAIN_ID, $SIWE_CHAIN_ID_REGEX, true),
                new Rule(SiweMessageField::NONCE, $SIWE_NONCE_REGEX, true),
                new Rule(SiweMessageField::ISSUED_AT, $SIWE_ISSUED_AT_REGEX, true),
                new Rule(SiweMessageField::EXPIRATION_TIME, $SIWE_EXPIRATION_TIME_REGEX, false),
                new Rule(SiweMessageField::NOT_BEFORE, $SIWE_NOT_BEFORE_REGEX, false),
                new Rule(SiweMessageField::REQUEST_ID, $SIWE_REQUEST_ID_REGEX, false),
                new Rule(SiweMessageField::RESOURCES, $SIWE_RESOURCES_REGEX, false),
            )
        );
    }

    function parse(string $input)
    {
        $result = $this->grammar->parse($input);

        if (strlen($result[SiweMessageField::DOMAIN]) == 0) {
            throw new \Exception("Domain cannot be empty.");
        }

        if (!isEIP55Address($result[SiweMessageField::ADDRESS])) {
            throw new \Exception("Address not conformant to EIP-55.");
        }

        if (!$result[SiweMessageField::CHAIN_ID]) {
            throw new \Exception("Chain ID cannot be empty.");
        }

        if (!$result[SiweMessageField::ISSUED_AT]) {
            throw new \Exception("Issued At cannot be empty.");
        }

        if (isset($result[SiweMessageField::RESOURCES])) {
            $resources = array_map(function ($resource) {
                return trim($resource, "- ");
            }, explode("\n", $result[SiweMessageField::RESOURCES]));

            foreach ($resources as $resource) {
                if (!filter_var($resource, FILTER_VALIDATE_URL)) {
                    throw new \Exception("Resource $resource is not a valid URL.");
                }
            }
        }

        return $result;
    }
}
