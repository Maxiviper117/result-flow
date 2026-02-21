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
      {
        text: 'Learn',
        activeMatch: '^/(result|examples)/',
        items: [
          {
            text: 'Result',
            items: [
              { text: 'Result Guide', link: '/result/' },
              { text: 'Composition Patterns', link: '/result/compositions' },
            ],
          },
          {
            text: 'Examples',
            items: [{ text: 'Examples Overview', link: '/examples/' }],
          },
        ],
      },
      {
        text: 'Reference',
        activeMatch: '^/(api|debugging|sanitization|testing|faq|laravel-boost)/',
        items: [
          {
            text: 'Reference Docs',
            items: [
              { text: 'API', link: '/api' },
              { text: 'Debugging and Meta', link: '/debugging' },
              { text: 'Sanitization Guide', link: '/sanitization' },
              { text: 'Testing Recipes', link: '/testing' },
              { text: 'FAQ', link: '/faq' },
            ],
          },
          {
            text: 'Integrations',
            items: [{ text: 'Laravel Boost', link: '/laravel-boost' }],
          },
        ],
      },
    ],

    sidebar: {
      '/result/': [
        {
          text: 'Foundations',
          items: [
            { text: 'Result Overview', link: '/result/' },
            { text: 'Constructing Results', link: '/result/constructing' },
            { text: 'Chaining and Transforming', link: '/result/chaining' },
          ],
        },
        {
          text: 'Compositions',
          items: [
            { text: 'Composition Patterns', link: '/result/compositions' },
            { text: 'Core Pipelines', link: '/result/compositions/core-pipelines' },
            { text: 'Failure and Recovery', link: '/result/compositions/failure-recovery' },
            { text: 'Finalization Boundaries', link: '/result/compositions/finalization-boundaries' },
            { text: 'Metadata and Observability', link: '/result/compositions/metadata-observability' },
          ],
        },
        {
          text: 'Operational Flows',
          items: [
            { text: 'Error Handling', link: '/result/error-handling' },
            { text: 'Retrying', link: '/result/retrying' },
            { text: 'Batch Processing', link: '/result/batch-processing' },
          ],
        },
        {
          text: 'Boundaries and Output',
          items: [
            { text: 'Matching and Unwrapping', link: '/result/matching-unwrapping' },
            { text: 'Transformers', link: '/result/transformers' },
            { text: 'Metadata and Debugging', link: '/result/metadata-debugging' },
          ],
        },
      ],
      '/examples/': [
        {
          text: 'Overview',
          items: [{ text: 'Examples Overview', link: '/examples/' }],
        },
        {
          text: 'Core Pipelines',
          items: [
            { text: 'Plain PHP Basics', link: '/examples/plain-php-basics' },
            { text: 'Laravel Workflow', link: '/examples/laravel' },
            { text: 'Laravel Actions', link: '/examples/laravel-actions' },
            { text: 'Laravel Actions Pipeline', link: '/examples/laravel-actions-pipeline' },
          ],
        },
        {
          text: 'Failure and Recovery',
          items: [
            { text: 'Plain PHP Error Handling', link: '/examples/plain-php-errors' },
            { text: 'Laravel Actions Exceptions', link: '/examples/laravel-actions-exceptions' },
            { text: 'Laravel Retries', link: '/examples/laravel-retries' },
            { text: 'Laravel Actions Retries', link: '/examples/laravel-actions-retries' },
          ],
        },
        {
          text: 'Collections and Combining',
          items: [
            { text: 'Plain PHP Batch Processing', link: '/examples/plain-php-batch' },
            { text: 'Laravel Combine', link: '/examples/laravel-combine' },
            { text: 'Laravel Validation', link: '/examples/laravel-validation' },
          ],
        },
        {
          text: 'Boundaries',
          items: [
            { text: 'Laravel Controller-only', link: '/examples/laravel-controller-only' },
            { text: 'Laravel Match and Unwrap', link: '/examples/laravel-match-unwrap' },
            { text: 'Laravel Transactions', link: '/examples/laravel-transactions' },
            { text: 'Laravel Jobs and Queues', link: '/examples/laravel-jobs' },
          ],
        },
        {
          text: 'Observability',
          items: [
            { text: 'Laravel Debugging', link: '/examples/laravel-debugging' },
            { text: 'Laravel Metadata and Taps', link: '/examples/laravel-meta-taps' },
          ],
        },
      ],
      '/guides/': [
        {
          text: 'Guides',
          items: [
            { text: 'Usage Patterns', link: '/guides/patterns' },
            { text: 'Anti-Patterns', link: '/guides/anti-patterns' },
            { text: 'Internals', link: '/guides/internals' },
          ],
        },
      ],
      '/': [
        {
          text: 'Start',
          items: [
            { text: 'Overview', link: '/' },
            { text: 'Getting Started', link: '/getting-started' },
            { text: 'Laravel Boost', link: '/laravel-boost' },
            { text: 'FAQ', link: '/faq' },
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
      ],
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/Maxiviper117/result-flow' },
    ],

    search: {
      provider: 'local',
    },
  },
})
