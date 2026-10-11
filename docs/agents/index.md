# Use SIWE PHP with AI agents

Use these pages as context for coding agents integrating or reviewing SIWE PHP. The documentation is statically rendered, so its content is available without executing JavaScript.

## Machine-readable documentation

- [llms.txt](../llms.txt): a concise index of documentation and integration constraints.
- [llms-full.txt](../llms-full.txt): all guides, API reference and agent prompts in one plain-text file.

These files follow the llms.txt convention; individual agents may need you to supply the URL explicitly. They do not guarantee automatic discovery by every AI tool. The full-text file is generated from the Markdown source on every build, keeping it aligned with the website.

## Integration prompt

Copy this prompt into your coding agent and replace the bracketed values:

```text
Integrate iltumio/siwe-php into [application/framework] using PHP 8.3+.
Read https://iltumio.github.io/siwe-php/llms-full.txt first. Inspect the
application's existing routes, session storage and authentication conventions.
Use the documented Iltumio\SiwePhp\SiweMessage API; do not invent methods.

Configured domain: [example.com]
Authentication URI: [https://example.com/login]
Allowed chain IDs: [1]
Contract wallet support: [yes/no]
Trusted RPC configuration: [server-side configuration name, never a secret value]

Implement challenge issuance and verification. Store the exact message and
nonce in server-side state tied to the login attempt, with a short expiration.
Require the submitted message to match the stored challenge. Verify the
signature with the server-configured domain and stored nonce, then atomically
consume the challenge before creating or rotating the session. Validate the
allowed chain and select RPC URLs only from trusted server configuration.
Use providerUrl => '' if contract wallet support is disabled.

Handle constructor errors separately from verification failures. verify()
returns a Guzzle promise; use suppressExceptions => true and wait(), then
check success before authenticating. Enforce challenge lifetime server-side;
issuedAt alone does not impose a maximum age. Keep wallet connection and
signing in the client; this PHP library does not provide those features.

Add tests for a valid login, malformed message, changed challenge, wrong
domain, wrong nonce, expiry, unsupported chain, replay and concurrent reuse.
Follow the application's existing test tools. Report the implemented flow,
checks run and any remaining limitations. Never claim a security audit.
```

## Review prompt

```text
Review this application's SIWE PHP authentication integration.
Read https://iltumio.github.io/siwe-php/llms-full.txt and inspect the actual code.
Check that issued challenges are stored server-side and submitted messages
match them; domain, nonce, URI, address and chain are bound to the challenge;
expiry is enforced; nonce consumption is atomic; sessions are rotated after
success; RPC endpoints come from trusted configuration; parse failures and
promise failures reject login; unsupported chains and replay attempts fail.
Check that no session is created before successful verification and nonce
consumption. Check the existing CSRF, cookie and rate-limit protections.
Report findings with severity, file/line evidence and concrete fixes.
Distinguish confirmed issues from unverified assumptions. Run relevant tests
and state their results. Do not describe this review as a formal security audit.
```

## Documentation maintenance prompt

```text
Update SIWE PHP documentation for the current implementation. Read
src/SiwePhp.php, src/utils.php, src/constants.php, composer.json and tests.
Keep examples aligned with actual field validation and promise behavior.
Edit the relevant Markdown under docs/; do not hand-edit generated text files.
Run npm ci and npm run docs:build. Ensure llms.txt and llms-full.txt are
regenerated, links resolve and examples use the documented namespace/API.
Summarize behavior changes and validation results.
```
