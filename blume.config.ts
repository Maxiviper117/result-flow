import { defineConfig } from 'blume'

const deployBase =
  process.env.CI === 'true' || process.env.GITHUB_ACTIONS === 'true'
    ? '/result-flow/'
    : '/'

export default defineConfig({
  title: 'Result Flow',
  description:
    'A type-safe Result type for explicit success, failure, and metadata handling in PHP',

  github: {
    owner: 'Maxiviper117',
    repo: 'result-flow',
  },

  deployment: {
    base: deployBase,
  },

  navigation: {
    tabs: [
      { label: 'Home', path: '/', icon: 'home' },
      { label: 'Concepts', path: '/concepts', icon: 'lightbulb' },
      { label: 'Guides', path: '/guides', icon: 'map' },
      { label: 'Recipes', path: '/recipes', icon: 'flask-conical' },
      { label: 'Kitchen sink', path: '/kitchen-sink', icon: 'grid-3x3' },
      { label: 'Reference', path: '/reference', icon: 'file-text' },
    ],
    featured: [
      { label: 'Getting started', href: '/getting-started', icon: 'rocket' },
      { label: 'Laravel Boost', href: '/laravel-boost', icon: 'sparkles' },
      { label: 'FAQ', href: '/faq', icon: 'circle-help' },
    ],
    sidebar: {
      display: 'group',
    },
  },
})
