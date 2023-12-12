<?php

namespace Iltumio\SiwePhp;

const VerifyParamsKeys = [
    'signature',
    'domain',
    'nonce',
    'time',
];

const ETHEREUM_MESSAGE_PREFIX = "\x19Ethereum Signed Message:\n";

// function isValidSignature(bytes32 _message, bytes _signature) public view returns (bytes4)
const EIP1271_ABI = '
    [
        {
            "inputs": [
                {
                    "internalType": "bytes32",
                    "name": "hash",
                    "type": "bytes32"
                },
                {
                    "internalType": "bytes",
                    "name": "signature",
                    "type": "bytes"
                }
            ],
            "name": "isValidSignature",
            "outputs": [
                {
                    "internalType": "bytes4",
                    "name": "magicValue",
                    "type": "bytes4"
                }
            ],
            "stateMutability": "view",
            "type": "function"
        }
    ]
';

const EIP1271_MAGICVALUE = '0x1626ba7e';
