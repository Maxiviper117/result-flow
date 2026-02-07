import { defineConfig } from 'vitepress'

export default defineConfig({
  srcDir: 'docs',
  base: '/result-flow/',

  title: 'Result Flow',
  description: 'A lightweight, type-safe Result type for PHP',

  themeConfig: {
    nav: [
      { text: 'Home', link: '/' },
      { text: 'Quickstart', link: '/getting-started' },
      { text: 'Result Guide', link: '/result/' },
      { text: 'API', link: '/api' },
      { text: 'Examples', link: '/examples/' },
      { text: 'FAQ', link: '/faq' },
    ],

    sidebar: [
      {
        text: 'Start',
        items: [
          { text: 'Overview', link: '/' },
          { text: 'Getting Started', link: '/getting-started' },
          { text: 'FAQ', link: '/faq' },
        ],
      },
      {
        text: 'Result Guide',
        items: [
          { text: 'Result Overview', link: '/result/' },
          { text: 'Constructing Results', link: '/result/constructing' },
          { text: 'Chaining and Transforming', link: '/result/chaining' },
          { text: 'Error Handling', link: '/result/error-handling' },
          { text: 'Batch Processing', link: '/result/batch-processing' },
          { text: 'Retrying', link: '/result/retrying' },
          { text: 'Matching and Unwrapping', link: '/result/matching-unwrapping' },
          { text: 'Metadata and Debugging', link: '/result/metadata-debugging' },
          { text: 'Transformers', link: '/result/transformers' },
        ],
      },
      {
        text: 'Reference',
        items: [
          { text: 'API Reference', link: '/api' },
          { text: 'Debugging and Meta', link: '/debugging' },
          { text: 'Sanitization Guide', link: '/sanitization' },
          { text: 'Testing Recipes', link: '/testing' },
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
        text: 'Examples',
        collapsed: true,
        items: [
          { text: 'Examples Overview', link: '/examples/' },
          {
            text: 'Plain PHP',
            collapsed: false,
            items: [
              { text: 'Basics', link: '/examples/plain-php-basics' },
              { text: 'Batch Processing', link: '/examples/plain-php-batch' },
              { text: 'Error Handling', link: '/examples/plain-php-errors' },
            ],
          },
          {
            text: 'Laravel',
            collapsed: true,
            items: [
              { text: 'Workflow', link: '/examples/laravel' },
              { text: 'Controller-only', link: '/examples/laravel-controller-only' },
              { text: 'Validation', link: '/examples/laravel-validation' },
              { text: 'Transactions', link: '/examples/laravel-transactions' },
              { text: 'Retries', link: '/examples/laravel-retries' },
              { text: 'Debugging', link: '/examples/laravel-debugging' },
              { text: 'Combine', link: '/examples/laravel-combine' },
              { text: 'Match and unwrap', link: '/examples/laravel-match-unwrap' },
              { text: 'Metadata and taps', link: '/examples/laravel-meta-taps' },
              { text: 'Jobs and queues', link: '/examples/laravel-jobs' },
              { text: 'Actions', link: '/examples/laravel-actions' },
              { text: 'Actions pipeline', link: '/examples/laravel-actions-pipeline' },
              { text: 'Actions retries', link: '/examples/laravel-actions-retries' },
              { text: 'Actions exceptions', link: '/examples/laravel-actions-exceptions' },
            ],
          },
        ],
      },
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/Maxiviper117/result-flow' },
    ],

    search: {
      provider: 'local',
    },
  },
})
