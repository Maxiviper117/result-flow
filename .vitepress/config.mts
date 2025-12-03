import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  srcDir: "docs",
  
  title: "Result Flow",
  description: "A lightweight, type-safe Result monad for PHP",
  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    nav: [
      { text: 'Home', link: '/' },
      { text: 'Getting Started', link: '/getting-started' },
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
        text: 'API Reference',
        items: [
          { text: 'Result API', link: '/api' },
          { text: 'Debugging & Meta', link: '/debugging' },
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
    ]
  }
})
