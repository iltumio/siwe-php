import { copyFile, mkdir, readFile, writeFile } from 'node:fs/promises'

const base = 'https://iltumio.github.io/siwe-php/'
const pages = [
  ['guide/getting-started', 'Getting started', 'Installation and first message'],
  ['guide/messages', 'Messages', 'Generation, parsing and validation'],
  ['guide/verification', 'Verification', 'Promise results, failures and time checks'],
  ['guide/contract-wallets', 'Contract wallets', 'EIP-1271 and trusted RPC configuration'],
  ['guide/authentication', 'Authentication', 'Challenge binding, expiry and replay protection'],
  ['reference/siwe-message', 'API reference', 'Fields, methods, parameters and options'],
  ['reference/development', 'Development', 'Tests, documentation build and licensing'],
  ['agents/index', 'Agent prompts', 'Integration, review and maintenance prompts']
]
const intro = `# SIWE PHP

> PHP library for Sign-In with Ethereum message generation, parsing and EOA/EIP-1271 signature verification.

Package: iltumio/siwe-php. PHP 8.3+ with GMP, mbstring, ctype and JSON.
Namespace: Iltumio\\SiwePhp\\SiweMessage.
verify() returns a Guzzle promise. Use wait() and check success.
Bind login to the configured domain and stored nonce, compare the exact stored
challenge, enforce its lifetime and atomically consume it before authentication.
Use trusted server-configured RPC endpoints for the permitted chain only.
Constructor failures are not suppressed by verification options.
The library has not undergone a formal security audit.
`
await mkdir('docs/public/assets', { recursive: true })
await copyFile('docs/assets/siwe-php-logo.png', 'docs/public/assets/siwe-php-logo.png')
await writeFile('docs/public/llms.txt', intro + '\n## Documentation\n\n' + pages.map(([path, title, detail]) => `- [${title}](${base}${path}.html): ${detail}`).join('\n') + `\n\n## Full text\n\n- [Complete documentation](${base}llms-full.txt)\n- [Source repository](https://github.com/iltumio/siwe-php)\n`)
const sections = await Promise.all(pages.map(async ([path]) => {
  const content = await readFile(`docs/${path}.md`, 'utf8')
  return `Source: ${base}${path}.html\n\n${content}`
}))
await writeFile('docs/public/llms-full.txt', intro + '\n\n' + sections.join('\n\n---\n\n'))
