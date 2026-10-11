# Verify a signature

Parse the exact message signed by the wallet, then verify it against the domain configured by your application and the nonce stored for that login attempt.

```php
use Iltumio\SiwePhp\SiweMessage;

try {
    $message = new SiweMessage($signedMessage);
    $result = $message->verify([
        'signature' => $signature,
        'domain' => 'example.com',
        'nonce' => $storedNonce,
    ], [
        'suppressExceptions' => true,
        'providerUrl' => '', // This example accepts EOA signatures only.
    ])->wait();
} catch (\Throwable $error) {
    // Reject malformed messages and unexpected failures.
    throw $error;
}

if ($result['success']) {
    $address = $result['data']->address;
    // Atomically consume the challenge before creating the authenticated session.
} else {
    $reason = $result['error']->getMessage();
    // Reject the login attempt.
}
```

`verify()` returns a Guzzle promise. Call `wait()` to execute verification and obtain its result. Successful results contain `success: true` and `data` (the `SiweMessage`). With `suppressExceptions: true`, verification failures resolve with `success: false`, `data` and `error`.

Without `suppressExceptions`, the promise is rejected on failure. The rejection reason is a result array; Guzzle's `wait()` throws when unwrapping it. Using suppressed verification failures is convenient when an application needs structured error results.

## Time checks

Verification rejects expired messages and messages whose `notBefore` is in the future. The default verification time is the current time. For deterministic tests, pass an RFC 3339 `time` parameter:

```php
$result = $message->verify([
    'signature' => $signature,
    'domain' => 'example.com',
    'nonce' => $storedNonce,
    'time' => '2026-01-01T12:00:00Z',
], [
    'suppressExceptions' => true,
    'providerUrl' => '',
])->wait();
```

Keep `time` under server control. The library does not automatically impose a maximum age based on `issuedAt`; enforce your application's challenge lifetime separately or set and enforce `expirationTime`.

## Signature checks

EOA signatures are recovered locally. If recovery does not match the message address, the library can fall back to [EIP-1271 verification](./contract-wallets.md). Unknown verification parameter or option keys fail verification.
