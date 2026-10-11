import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'SIWE PHP',
  description: 'Sign-In with Ethereum for PHP. Generate, parse and verify SIWE messages.',
  base: '/siwe-php/',
  lang: 'en-US',
  sitemap: { hostname: 'https://iltumio.github.io/siwe-php/' },
  themeConfig: {
    nav: [
      { text: 'Guide', link: '/guide/getting-started' },
      { text: 'API reference', link: '/reference/siwe-message' },
      { text: 'AI agents', link: '/agents/' },
      { text: 'Packagist', link: 'https://packagist.org/packages/iltumio/siwe-php' }
    ],
    sidebar: [
      { text: 'Guide', items: [
        { text: 'Getting started', link: '/guide/getting-started' },
        { text: 'Generate a message', link: '/guide/messages' },
        { text: 'Verify a signature', link: '/guide/verification' },
        { text: 'Smart contract wallets', link: '/guide/contract-wallets' },
        { text: 'Authentication flow', link: '/guide/authentication' }
      ] },
      { text: 'AI agents', items: [{ text: 'Documentation and prompts', link: '/agents/' }] },
      { text: 'Reference', items: [
        { text: 'SiweMessage', link: '/reference/siwe-message' },
        { text: 'Development', link: '/reference/development' }
      ] }
    ],
    search: { provider: 'local' },
    socialLinks: [{ icon: 'github', link: 'https://github.com/iltumio/siwe-php' }],
    editLink: { pattern: 'https://github.com/iltumio/siwe-php/edit/main/docs/:path' },
    footer: {
      message: 'A PHP port of SIWE. See the repository for license details.',
      copyright: 'Made by Manuel Tumiati'
    }
  }
})
