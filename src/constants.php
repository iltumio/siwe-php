<?php

namespace Iltumio\SiwePhp;

const VerifyParamsKeys = [
    'signature',
    'domain',
    'nonce',
    'time',
];

const ETHEREUM_MESSAGE_PREFIX = "\x19Ethereum Signed Message:\n";
