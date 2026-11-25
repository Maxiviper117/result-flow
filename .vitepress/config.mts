import { defineConfig } from 'vitepress'

export default defineConfig({
  srcDir: 'docs',
  base: process.env.RESULT_FLOW_DOCS_BASE || '/result-flow/',
  title: 'Result Flow v2',
  description: 'Explicit success and failure for PHP actions',
  themeConfig: {
    nav: [
      { text: 'Quickstart', link: '/getting-started' },
      { text: 'Guides', link: '/guides/' },
      { text: 'Reference', link: '/reference/' },
      { text: 'Migration', link: '/migration-v2' },
    ],
    sidebar: [
      { text: 'Start', items: [
        { text: 'Overview', link: '/' },
        { text: 'Getting started', link: '/getting-started' },
        { text: 'Migrating from v1', link: '/migration-v2' },
        { text: 'FAQ', link: '/faq' },
      ] },
      { text: 'Concepts', items: [
        { text: 'Overview', link: '/concepts/' },
        { text: 'Result model', link: '/concepts/result-model' },
        { text: 'Chaining', link: '/concepts/chaining' },
        { text: 'Failure handling', link: '/concepts/failure-handling' },
        { text: 'Metadata', link: '/concepts/metadata' },
      ] },
      { text: 'Guides', items: [
        { text: 'Overview', link: '/guides/' },
        { text: 'Validate then persist', link: '/guides/validate-then-persist' },
        { text: 'Transactions', link: '/guides/transaction-rollback' },
        { text: 'Batch strategy', link: '/guides/batch-strategy' },
      ] },
      { text: 'Recipes', items: [
        { text: 'Overview', link: '/recipes/' },
        { text: 'API boundary', link: '/recipes/api-boundary-mapping' },
        { text: 'Collect errors', link: '/recipes/collecting-batch-errors' },
        { text: 'Resource cleanup', link: '/recipes/resource-cleanup' },
      ] },
      { text: 'Reference', items: [
        { text: 'Overview', link: '/reference/' },
        { text: 'Construction', link: '/reference/construction' },
        { text: 'Chaining', link: '/reference/chaining' },
        { text: 'Failure handling', link: '/reference/failure-handling' },
        { text: 'Boundaries', link: '/reference/boundaries' },
        { text: 'Metadata and debugging', link: '/reference/metadata-debugging' },
        { text: 'Batch processing', link: '/reference/batch-processing' },
        { text: 'Laravel Boost', link: '/laravel-boost' },
      ] },
      { text: 'Kitchen sink', items: [
        { text: 'Overview', link: '/kitchen-sink/' },
        { text: 'Construction', link: '/kitchen-sink/construction' },
        { text: 'Chaining and recovery', link: '/kitchen-sink/chaining-and-recovery' },
        { text: 'State and metadata', link: '/kitchen-sink/state-and-metadata' },
        { text: 'Collections', link: '/kitchen-sink/collections' },
        { text: 'Finalization and output', link: '/kitchen-sink/finalization-and-output' },
      ] },
    ],
    socialLinks: [{ icon: 'github', link: 'https://github.com/Maxiviper117/result-flow' }],
    search: { provider: 'local' },
  },
})
