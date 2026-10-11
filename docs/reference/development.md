# Development

## PHP library

```bash
composer install
composer test
composer audit
```

The default test suite does not depend on a live Ethereum provider. To include the mainnet contract-wallet integration fixtures:

```bash
SIWE_RPC_URL=https://your-mainnet-rpc.example ./run-tests.sh
```

CI tests PHP 8.3, 8.4 and 8.5.

## Documentation

The site uses [VitePress](https://vitepress.dev/) with Markdown source in `docs/`. Use Node.js 22 or later and npm:

```bash
npm ci
npm run docs:dev
```

Build and preview the static output:

```bash
npm run docs:build
npm run docs:preview
```

The build regenerates `llms.txt` and `llms-full.txt` from the Markdown guides and [agent prompts](../agents/index.md). Vite is pinned through an override to a patched 6.4 release while the site uses stable VitePress 1.6.4.

The production base path is `/siwe-php/`. GitHub Actions builds documentation on pull requests and deploys changes pushed to `main` to [GitHub Pages](https://iltumio.github.io/siwe-php/). The deployment can also be triggered manually from the Actions tab.

## Credits and licenses

A PHP port of [SIWE](https://github.com/spruceid/siwe). See [login.xyz](https://login.xyz).

Consult the repository's [LICENSE](https://github.com/iltumio/siwe-php/blob/main/LICENSE), [MIT license](https://github.com/iltumio/siwe-php/blob/main/LICENSE-MIT) and [Apache license](https://github.com/iltumio/siwe-php/blob/main/LICENSE-APACHE) for licensing details.
