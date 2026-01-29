import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  srcDir: "docs",
  base: "/result-flow/",

  title: "Result Flow",
  description: "A lightweight, type-safe Result type for PHP",
  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    nav: [
      { text: 'Home', link: '/' },
      { text: 'Getting Started', link: '/getting-started' },
      { text: 'Result Deep Dive', link: '/result/' },
      { text: 'Examples', link: '/examples/' },
      { text: 'API', link: '/api' },
      { text: 'FAQ', link: '/faq' },
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
          { text: 'FAQ', link: '/faq' },
        ]
      },
      {
        text: 'Result Deep Dive',
        items: [
          { text: 'Result overview', link: '/result/' },
          { text: 'Constructing & Combining', link: '/result/constructing' },
          { text: 'Chaining & Transformations', link: '/result/chaining' },
          { text: 'Retrying Operations', link: '/result/retrying' },
          { text: 'Matching & Unwrapping', link: '/result/matching-unwrapping' },
          { text: 'Transformers (JSON/XML)', link: '/result/transformers' },
          { text: 'Metadata & Debugging', link: '/result/metadata-debugging' },
          { text: 'Sanitization & Safety', link: '/sanitization' },
        ],
      },
      {
        text: 'Examples',
        collapsed: true,
        items: [
          { text: 'Overview', link: '/examples/' },
          {
            text: 'Default patterns',
            collapsed: true,
            items: [
              { text: 'Laravel workflow', link: '/examples/laravel' },
              { text: 'Laravel controller-only', link: '/examples/laravel-controller-only' },
              { text: 'Laravel validation', link: '/examples/laravel-validation' },
              { text: 'Laravel transactions', link: '/examples/laravel-transactions' },
              { text: 'Laravel retries', link: '/examples/laravel-retries' },
              { text: 'Laravel debugging', link: '/examples/laravel-debugging' },
              { text: 'Laravel combine', link: '/examples/laravel-combine' },
              { text: 'Laravel match + unwrap', link: '/examples/laravel-match-unwrap' },
              { text: 'Laravel metadata + taps', link: '/examples/laravel-meta-taps' },
              { text: 'Laravel jobs + queues', link: '/examples/laravel-jobs' },
            ],
          },
          {
            text: 'Action pattern',
            collapsed: true,
            items: [
              { text: 'Laravel actions (optional)', link: '/examples/laravel-actions' },
              { text: 'Laravel actions pipeline', link: '/examples/laravel-actions-pipeline' },
              { text: 'Laravel actions retries', link: '/examples/laravel-actions-retries' },
              { text: 'Laravel actions exceptions', link: '/examples/laravel-actions-exceptions' },
            ],
          },
        ],
      },
      {
        text: 'Reference',
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
