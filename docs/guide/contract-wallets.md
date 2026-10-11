# Smart contract wallets

SIWE PHP supports EIP-1271 contract signatures through an Ethereum JSON-RPC `eth_call`.

```php
$result = $message->verify([
    'signature' => $signature,
    'domain' => 'example.com',
    'nonce' => $storedNonce,
], [
    'suppressExceptions' => true,
    'providerUrl' => $rpcUrl,
])->wait();
```

Choose a trusted HTTP(S) RPC endpoint for the message's chain. Select it from server-side configuration after checking that the chain is supported by your application. Do not accept arbitrary RPC URLs from the login request.

The library first attempts local EOA recovery. If the recovered address does not match, it calls the wallet contract's `isValidSignature` method and requires the EIP-1271 magic value. Contract signatures do not need the standard EOA length or recovery format, but the signature parameter must be a hexadecimal string prefixed with `0x`.

## Provider behavior

- An omitted `providerUrl` uses the existing default provider only for chain ID `1`.
- Other chains require an explicit endpoint for contract verification.
- `providerUrl: ''` disables RPC verification while keeping local EOA verification available.
- HTTP requests have a 10-second total timeout and a 5-second connection timeout.
- RPC errors, reverted calls and malformed responses fail verification.

Configure your own provider for production availability. Contract verification depends on the chain state reported by that provider.
