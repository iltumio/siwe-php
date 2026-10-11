---
layout: home
hero:
  name: SIWE PHP
  text: Ethereum sign-in. Native PHP.
  tagline: Generate, parse and verify Sign-In with Ethereum messages for externally owned accounts and smart contract wallets.
  image:
    src: /assets/siwe-php-logo.png
    alt: SIWE PHP
  actions:
    - theme: brand
      text: Get started
      link: /guide/getting-started
    - theme: alt
      text: API reference
      link: /reference/siwe-message
features:
  - title: Build and parse messages
    details: Create signable SIWE messages from PHP fields or parse the message returned by a wallet.
  - title: Verify wallet ownership
    details: Recover EOA signatures locally and verify EIP-1271 contract signatures using an explicit RPC endpoint.
  - title: Fit your authentication flow
    details: Check the expected domain, stored nonce and validity window through a Guzzle promise API.
---

## Install with Composer

```bash
composer require iltumio/siwe-php
```

Requires PHP 8.3 or later and the GMP, mbstring, ctype and JSON extensions.

::: warning Security status
This library is released as is and has not undergone a formal security audit. Read the [authentication guide](./guide/authentication.md) before integrating it into a login flow.
:::

A PHP port of [SIWE](https://github.com/spruceid/siwe). Learn about Sign-In with Ethereum at [login.xyz](https://login.xyz).
