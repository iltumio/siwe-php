# Generate and parse messages

## Generate

Pass an array of SIWE fields to `SiweMessage` and serialize it with `prepareMessage()`.

```php
use Iltumio\SiwePhp\SiweMessage;

$message = new SiweMessage([
    'domain' => 'example.com',
    'address' => '0xC02aaA39b223FE8D0A0e5C4F27eAD9083C756Cc2',
    'statement' => 'Sign in to Example.',
    'uri' => 'https://example.com/login',
    'version' => '1',
    'chainId' => 1,
    'expirationTime' => gmdate('Y-m-d\TH:i:s\Z', time() + 300),
    'resources' => ['https://example.com/terms'],
]);

$text = $message->prepareMessage();
$nonce = $message->nonce;
```

When omitted, the nonce is generated using cryptographically secure randomness. `issuedAt` is populated on first serialization. Set an explicit `chainId` for the wallet's chain; serialization defaults an omitted chain ID to `1`.

Dates must be valid RFC 3339 timestamps. The address must pass the library's EIP-55 validation. A custom nonce must contain at least eight alphanumeric characters. See the [field reference](../reference/siwe-message.md#fields).

## Parse

```php
$message = new SiweMessage($signedMessage);

$address = $message->address;
$chainId = $message->chainId;
$resources = $message->resources;
```

Parsing validates the input and preserves resources. Malformed dates, invalid nonces, duplicate fields, line breaks in fields and trailing data are rejected. Handle constructor failures before calling `verify()`; `suppressExceptions` only applies to verification.

## Validate changes

Fields are public. After changing them, call `validateMessage()` to check their values. Serialization and verification also validate the message. Keep the stored challenge unchanged once the wallet has been asked to sign it.
