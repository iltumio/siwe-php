<?php

namespace Iltumio\SiwePhp;

use kornrunner\Keccak;
use Elliptic\EC;
use DateTime;

function isEIP55Address($address)
{
    if (strlen($address) != 42) {
        return false;
    }

    $lowerAddress = strtolower(str_replace('0x', '', $address));
    $hash = Keccak::hash($lowerAddress, 256);

    $ret = "0x";

    for ($i = 0; $i < 40; $i++) {
        $charInt = intval($hash[$i], 16);

        if ($charInt >= 8) {
            $ret .= strtoupper($lowerAddress[$i]);
        } else {
            $ret .= $lowerAddress[$i];
        }
    }

    return $address == $ret;
}

function isValidISO8601Date($dateString)
{
    $regex = '/^([\+-]?\d{4}(?!\d{2}\b))((-?)((0[1-9]|1[0-2])(\3([12]\d|0[1-9]|3[01]))?|W([0-4]\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\d|[12]\d{2}|3([0-5]\d|6[1-6])))([T\s]((([01]\d|2[0-3])((:?)[0-5]\d)?|24\:?00)([\.,]\d+(?!:))?)?(\17[0-5]\d([\.,]\d+)?)?([zZ]|([\+-])([01]\d|2[0-3]):?([0-5]\d)?)?)?)?$/';
    if (!is_string($dateString))
        return false;

    $match = preg_match($regex, $dateString);

    return $match == 1;
}

function generateNonce()
{
    return random_bytes(96);
}

function toResourcesString($resources = [])
{
    $ret = "Resources:\n";
    for ($i = 0; $i < count($resources); $i++) {
        $ret .= "- " . $resources[$i];
        if ($i < count($resources) - 1) {
            $ret .= "\n";
        }
    }
    // foreach ($resources as $resource) {
    //     $ret .= "- " . $resource . "\n";
    // }
    return $ret;
}

function checkInvalidKeys($obj, $keys = [])
{
    $invalidKeys = array_diff(array_keys((array) $obj), $keys);
    return $invalidKeys;
}

function hashMessage($message)
{
    $messageLength = strlen($message);
    $messageHash = Keccak::hash(ETHEREUM_MESSAGE_PREFIX . $messageLength . $message, 256);
    return $messageHash;
}

// function verifyMessage($message, $sig)
// {
//     $messageHash = hashMessage($message);
//     $signature   = ["r" => substr($sig, 2, 64), "s" => substr($sig, 66, 64)];
//     $recid  = (ord(hex2bin(substr($sig, 130, 2))) - 27);
//     $adjustedRecid = $recid & 1;
//     // if ($recid != ($recid & 1)) return false;
//     echo $recid . "($adjustedRecid)\n";
//     $ec = new EC('secp256k1');
//     $publicKey = $ec->recoverPubKey($messageHash, $signature, $adjustedRecid);
//     return "0x" . substr(Keccak::hash(substr(hex2bin($publicKey->encode("hex")), 1), 256), 24);
// }

function verifyMessage($message, $sig)
{
    $messageHash = hashMessage($message);
    $signature   = ["r" => substr($sig, 2, 64), "s" => substr($sig, 66, 64)];
    $recoveryId = hexdec(substr($sig, 130, 2));

    // TODO: check if this method is correct
    if ($recoveryId >= 27) {
        $recoveryId -= 27;
    }

    // ECDSA Recovery
    $ec = new EC('secp256k1');
    $recoveredKey = $ec->recoverPubKey($messageHash, $signature, $recoveryId);
    $recoveredAddress =  "0x" . substr(Keccak::hash(substr(hex2bin($recoveredKey->encode("hex")), 1), 256), 24);

    return $recoveredAddress;
}
