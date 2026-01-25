import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  srcDir: "docs",
  base: "/result-flow/",
  
  title: "Result Flow",
  description: "A lightweight, type-safe Result monad for PHP",
  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    nav: [
      { text: 'Home', link: '/' },
      { text: 'Getting Started', link: '/getting-started' },
      { text: 'Result Deep Dive', link: '/result/' },
      { text: 'API', link: '/api' },
      {
        text: 'Guides',
        items: [
          { text: 'Usage Patterns', link: '/guides/patterns' },
          { text: 'Anti-Patterns', link: '/guides/anti-patterns' },
          { text: 'Internals', link: '/guides/internals' },
        ]
      }
    ],

    sidebar: [
      {
        text: 'Getting Started',
        items: [
          { text: 'Overview', link: '/' },
          { text: 'Installation & Basics', link: '/getting-started' },
        ]
      },
      {
        text: 'Result Deep Dive',
        items: [
          { text: 'Result overview', link: '/result/' },
          { text: 'Constructing & Combining', link: '/result/constructing' },
          { text: 'Chaining & Transformations', link: '/result/chaining' },
          { text: 'Matching & Unwrapping', link: '/result/matching-unwrapping' },
          { text: 'Transformers (JSON/XML)', link: '/result/transformers' },
          { text: 'Metadata & Debugging', link: '/result/metadata-debugging' },
          { text: 'Sanitization & Safety', link: '/sanitization' },
        ],
      },
      {
        text: 'API Reference',
        items: [
          { text: 'Result API', link: '/api' },
          { text: 'Debugging & Meta', link: '/debugging' },
          { text: 'Sanitization Guide', link: '/sanitization' },
        ],
      },
      {
        text: 'Guides',
        items: [
          { text: 'Usage Patterns', link: '/guides/patterns' },
          { text: 'Anti-Patterns', link: '/guides/anti-patterns' },
          { text: 'Internals', link: '/guides/internals' },
        ],
      },
      {
        text: 'Testing',
        items: [
          { text: 'Test Recipes', link: '/testing' },
        ],
      }
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/Maxiviper117/result-flow' }
    ],
    search: {
      provider: 'local'
    }
  }
})
