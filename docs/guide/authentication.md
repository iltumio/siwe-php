# Authentication flow

A valid signature proves wallet ownership for a message. Your application remains responsible for issuing challenges and establishing sessions.

## Issue a challenge

1. Validate the requested wallet address and supported chain.
2. Generate the SIWE message with the application's configured domain and URI, an unpredictable nonce and a short expiration time.
3. Store the exact serialized message, nonce, expiration and intended chain in server-side state tied to the login attempt.
4. Return the message to the client and ask the wallet to sign it unchanged.

## Complete the login

1. Require an existing, unexpired challenge belonging to the login attempt.
2. Compare the returned message with the stored challenge, including its URI, address and chain. Comparing the exact message is a straightforward way to enforce all issued fields.
3. Parse the message and verify the signature with the configured domain and stored nonce. Select any RPC endpoint from trusted server configuration.
4. Atomically consume the challenge on successful verification so concurrent requests cannot replay it.
5. Create or rotate the authenticated session and associate it with the verified address and chain as appropriate for your application.

Never derive the expected domain or nonce solely from the submitted message. Passing neither to `verify()` leaves those bindings unchecked. Resources and the statement are message content; verification does not enforce application-specific authorization rules for them.

## Application responsibilities

Use HTTPS, protect session cookies, apply the application's CSRF and rate-limit policies, and reject malformed input. Avoid disclosing sensitive internal errors to clients. Expire unused challenges and enforce the permitted chains and authentication URI.

::: warning Security status
This library is released as is and has not undergone a formal security audit.
:::
