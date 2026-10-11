# Getting started

SIWE PHP provides message generation, parsing and signature verification for Sign-In with Ethereum.

## Requirements

- PHP 8.3 or later.
- GMP, mbstring, ctype and JSON PHP extensions.
- Composer for dependency installation.

```bash
composer require iltumio/siwe-php
```

Load Composer's autoloader in standalone scripts:

```php
require __DIR__ . '/vendor/autoload.php';

use Iltumio\SiwePhp\SiweMessage;

$message = new SiweMessage([
    'domain' => 'example.com',
    'address' => '0xC02aaA39b223FE8D0A0e5C4F27eAD9083C756Cc2',
    'uri' => 'https://example.com/login',
    'version' => '1',
    'chainId' => 1,
]);

$signableMessage = $message->prepareMessage();
```

Store the message and `$message->nonce` for the login attempt, then ask the user's wallet to sign the exact message. The library verifies signatures; it does not manage wallet connections, sessions or user records.

Continue with [message generation](./messages.md) and [signature verification](./verification.md).
