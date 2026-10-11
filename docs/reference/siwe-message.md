# SiweMessage

```php
use Iltumio\SiwePhp\SiweMessage;
```

## Constructor

`new SiweMessage($param)` accepts an array of fields or a serialized SIWE message string. Construction parses or assigns fields and validates them. Invalid input throws; catch `\Throwable` at your request boundary.

## Fields

| Field | Value and behavior |
| --- | --- |
| `domain` | Required authority, such as `example.com` or `example.com:3000`; no URL scheme, path or whitespace. |
| `address` | Required Ethereum address passing EIP-55 validation. |
| `uri` | Required valid URL identifying the authentication request. |
| `version` | SIWE version `1`; use the string `'1'`. |
| `chainId` | Positive integer, or a digit string normalized to an integer within PHP's integer range. Defaults to `1` on serialization if omitted. |
| `nonce` | At least eight alphanumeric characters; generated securely when absent from the field array. |
| `issuedAt` | RFC 3339 timestamp; populated on first serialization when omitted. |
| `statement` | Optional single line of printable ASCII text. |
| `expirationTime` | Optional RFC 3339 expiration timestamp. Verification fails at or after this time. |
| `notBefore` | Optional RFC 3339 timestamp. Verification fails before this time. |
| `requestId` | Optional request identifier using the permitted URI characters or percent escapes. |
| `resources` | Array of valid resource URLs; defaults to `[]`. |

Fields are public. Prefer constructing a new message to mutating a challenge that has already been issued.

## Methods

| Method | Behavior |
| --- | --- |
| `prepareMessage()` | Returns the signable message string via `toMessage()`. |
| `toMessage()` | Validates and serializes fields; populates missing serialization defaults. |
| `validateMessage()` | Validates current fields and throws on invalid values. Accepts no arguments. |
| `fromParsedMessage($fields)` | Assigns recognized fields and normalizes the chain ID; used internally by the constructor. Does not independently perform full validation. |
| `verify(array $params, array $opts = [])` | Returns a Guzzle promise for signature and constraint verification. |

## Verification parameters

| Parameter | Behavior |
| --- | --- |
| `signature` | Required hexadecimal signature string with `0x` prefix. |
| `domain` | Optional exact comparison with the message domain; supply the application's expected domain for login. |
| `nonce` | Optional exact comparison with the message nonce; supply the stored challenge nonce for login. |
| `time` | Optional RFC 3339 verification time; defaults to the current time. |

## Verification options

| Option | Behavior |
| --- | --- |
| `suppressExceptions` | When truthy, verification failures resolve to a structured failure result. Otherwise the promise is rejected. Defaults to disabled. |
| `providerUrl` | HTTP(S) Ethereum RPC URL for contract verification. Empty string disables RPC; omission selects the default mainnet provider for chain ID `1` only. |

Unknown parameter or option keys are rejected. `providerUrl`, when provided, must be a string.

## Results

Success: `['success' => true, 'data' => $message]`.

Suppressed failure: `['success' => false, 'data' => $message, 'error' => $error]`. Read `$error->getMessage()` for the reason. Constructor exceptions occur before verification and are not suppressed by this option.
