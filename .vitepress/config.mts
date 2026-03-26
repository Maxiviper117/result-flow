import { defineConfig } from 'vitepress'

export default defineConfig({
  srcDir: 'docs',
  base: '/result-flow/',

  title: 'Result Flow',
  description: 'A type-safe Result type for explicit success, failure, and metadata handling in PHP',

  themeConfig: {
    nav: [
      { text: 'Home', link: '/' },
      { text: 'Quickstart', link: '/getting-started' },
      {
        text: 'Learn',
        activeMatch: '^/(kitchen-sink|concepts|guides|recipes)/',
        items: [
          { text: 'Kitchen sink', link: '/kitchen-sink/' },
          {
            text: 'Concepts',
            items: [
              { text: 'Overview', link: '/concepts/' },
              { text: 'Result model', link: '/concepts/result-model' },
              { text: 'Chaining', link: '/concepts/chaining' },
              { text: 'Failure handling', link: '/concepts/failure-handling' },
            ],
          },
          {
            text: 'Guides',
            items: [
              { text: 'Overview', link: '/guides/' },
              { text: 'Validate then persist', link: '/guides/validate-then-persist' },
              { text: 'Observability', link: '/guides/observability' },
            ],
          },
          {
            text: 'Recipes',
            items: [
              { text: 'Overview', link: '/recipes/' },
              { text: 'Input validation', link: '/recipes/input-validation' },
              { text: 'Resource cleanup', link: '/recipes/resource-cleanup' },
            ],
          },
        ],
      },
      {
        text: 'Reference',
        activeMatch: '^/(reference|faq|laravel-boost)/',
        items: [
          {
            text: 'Reference Docs',
            items: [
              { text: 'Overview', link: '/reference/' },
              { text: 'Kitchen sink', link: '/kitchen-sink/' },
              { text: 'Construction', link: '/reference/construction' },
              { text: 'Chaining', link: '/reference/chaining' },
              { text: 'Failure handling', link: '/reference/failure-handling' },
              { text: 'Boundaries', link: '/reference/boundaries' },
              { text: 'Metadata and debugging', link: '/reference/metadata-debugging' },
              { text: 'Batch processing', link: '/reference/batch-processing' },
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
      '/concepts/': [
        {
          text: 'Overview',
          items: [
            { text: 'Concepts Overview', link: '/concepts/' },
            { text: 'Result model', link: '/concepts/result-model' },
            { text: 'Constructing results', link: '/concepts/constructing' },
          ],
        },
        {
          text: 'Flow',
          items: [
            { text: 'Chaining', link: '/concepts/chaining' },
            { text: 'Failure handling', link: '/concepts/failure-handling' },
            { text: 'Deferred execution', link: '/concepts/deferred-execution' },
            { text: 'Retries', link: '/concepts/retries' },
          ],
        },
        {
          text: 'Data and boundaries',
          items: [
            { text: 'Metadata', link: '/concepts/metadata' },
            { text: 'Resource safety', link: '/concepts/resource-safety' },
            { text: 'Batch processing', link: '/concepts/batch-processing' },
            { text: 'Finalization boundaries', link: '/concepts/finalization-boundaries' },
          ],
        },
      ],
      '/guides/': [
        {
          text: 'Guides',
          items: [
            { text: 'Overview', link: '/guides/' },
            { text: 'Validate then persist', link: '/guides/validate-then-persist' },
            { text: 'Error normalization', link: '/guides/error-normalization' },
            { text: 'Transaction rollback', link: '/guides/transaction-rollback' },
            { text: 'Observability', link: '/guides/observability' },
            { text: 'Batch strategy', link: '/guides/batch-strategy' },
          ],
        },
      ],
      '/recipes/': [
        {
          text: 'Recipes',
          items: [
            { text: 'Overview', link: '/recipes/' },
            { text: 'Input validation', link: '/recipes/input-validation' },
            { text: 'API boundary mapping', link: '/recipes/api-boundary-mapping' },
            { text: 'Transient retries', link: '/recipes/transient-retries' },
            { text: 'Collecting batch errors', link: '/recipes/collecting-batch-errors' },
            { text: 'Resource cleanup', link: '/recipes/resource-cleanup' },
          ],
        },
      ],
      '/kitchen-sink/': [
        {
          text: 'Overview',
          items: [{ text: 'Kitchen sink', link: '/kitchen-sink/' }],
        },
        {
          text: 'Categories',
          items: [
            {
              text: 'Construction and entry points',
              link: '/kitchen-sink/construction',
              items: [
                { text: 'ok', link: '/kitchen-sink/construction#ok' },
                { text: 'fail', link: '/kitchen-sink/construction#fail' },
                { text: 'failWithValue', link: '/kitchen-sink/construction#failwithvalue' },
                { text: 'of', link: '/kitchen-sink/construction#of' },
                { text: 'defer', link: '/kitchen-sink/construction#defer' },
                { text: 'retry', link: '/kitchen-sink/construction#retry' },
                { text: 'retryDefer', link: '/kitchen-sink/construction#retrydefer' },
                { text: 'retrier', link: '/kitchen-sink/construction#retrier' },
                { text: 'bracket', link: '/kitchen-sink/construction#bracket' },
              ],
            },
            {
              text: 'Collections',
              link: '/kitchen-sink/collections',
              items: [
                { text: 'combine', link: '/kitchen-sink/collections#combine' },
                { text: 'combineAll', link: '/kitchen-sink/collections#combineall' },
                { text: 'mapItems', link: '/kitchen-sink/collections#mapitems' },
                { text: 'mapAll', link: '/kitchen-sink/collections#mapall' },
                { text: 'mapCollectErrors', link: '/kitchen-sink/collections#mapcollecterrors' },
              ],
            },
            {
              text: 'Branch state and metadata',
              link: '/kitchen-sink/state-and-metadata',
              items: [
                { text: 'isOk', link: '/kitchen-sink/state-and-metadata#isok' },
                { text: 'isFail', link: '/kitchen-sink/state-and-metadata#isfail' },
                { text: 'value', link: '/kitchen-sink/state-and-metadata#value' },
                { text: 'error', link: '/kitchen-sink/state-and-metadata#error' },
                { text: 'meta', link: '/kitchen-sink/state-and-metadata#meta' },
                { text: 'tapMeta', link: '/kitchen-sink/state-and-metadata#tapmeta' },
                { text: 'mapMeta', link: '/kitchen-sink/state-and-metadata#mapmeta' },
                { text: 'mergeMeta', link: '/kitchen-sink/state-and-metadata#mergemeta' },
                { text: 'tap', link: '/kitchen-sink/state-and-metadata#tap' },
                { text: 'onSuccess', link: '/kitchen-sink/state-and-metadata#onsuccess' },
                { text: 'inspect', link: '/kitchen-sink/state-and-metadata#inspect' },
                { text: 'onFailure', link: '/kitchen-sink/state-and-metadata#onfailure' },
                { text: 'inspectError', link: '/kitchen-sink/state-and-metadata#inspecterror' },
              ],
            },
            {
              text: 'Chaining and recovery',
              link: '/kitchen-sink/chaining-and-recovery',
              items: [
                { text: 'map', link: '/kitchen-sink/chaining-and-recovery#map' },
                { text: 'ensure', link: '/kitchen-sink/chaining-and-recovery#ensure' },
                { text: 'mapError', link: '/kitchen-sink/chaining-and-recovery#maperror' },
                { text: 'otherwise', link: '/kitchen-sink/chaining-and-recovery#otherwise' },
                { text: 'catchException', link: '/kitchen-sink/chaining-and-recovery#catchexception' },
                { text: 'recover', link: '/kitchen-sink/chaining-and-recovery#recover' },
                { text: 'then', link: '/kitchen-sink/chaining-and-recovery#then' },
                { text: 'flatMap', link: '/kitchen-sink/chaining-and-recovery#flatmap' },
                { text: 'thenUnsafe', link: '/kitchen-sink/chaining-and-recovery#thenunsafe' },
              ],
            },
            {
              text: 'Finalization and output',
              link: '/kitchen-sink/finalization-and-output',
              items: [
                { text: 'match', link: '/kitchen-sink/finalization-and-output#match' },
                { text: 'matchException', link: '/kitchen-sink/finalization-and-output#matchexception' },
                { text: 'unwrap', link: '/kitchen-sink/finalization-and-output#unwrap' },
                { text: 'unwrapOr', link: '/kitchen-sink/finalization-and-output#unwrapor' },
                { text: 'unwrapOrElse', link: '/kitchen-sink/finalization-and-output#unwraporelse' },
                { text: 'getOrThrow', link: '/kitchen-sink/finalization-and-output#getorthrow' },
                { text: 'throwIfFail', link: '/kitchen-sink/finalization-and-output#throwiffail' },
                { text: 'toArray', link: '/kitchen-sink/finalization-and-output#toarray' },
                { text: 'toDebugArray', link: '/kitchen-sink/finalization-and-output#todebugarray' },
                { text: 'toJson', link: '/kitchen-sink/finalization-and-output#tojson' },
                { text: 'toXml', link: '/kitchen-sink/finalization-and-output#toxml' },
                { text: 'toResponse', link: '/kitchen-sink/finalization-and-output#toresponse' },
              ],
            },
            {
              text: 'Retry builder',
              link: '/kitchen-sink/retry-builder',
              items: [
                { text: 'maxAttempts', link: '/kitchen-sink/retry-builder#maxattempts' },
                { text: 'delay', link: '/kitchen-sink/retry-builder#delay' },
                { text: 'exponential', link: '/kitchen-sink/retry-builder#exponential' },
                { text: 'jitter', link: '/kitchen-sink/retry-builder#jitter' },
                { text: 'attachAttemptMeta', link: '/kitchen-sink/retry-builder#attachattemptmeta' },
                { text: 'when', link: '/kitchen-sink/retry-builder#when' },
                { text: 'onRetry', link: '/kitchen-sink/retry-builder#onretry' },
                { text: 'attempt', link: '/kitchen-sink/retry-builder#attempt' },
              ],
            },
          ],
        },
      ],
      '/reference/': [
        {
          text: 'Construction',
          items: [
            { text: 'Overview', link: '/reference/' },
            { text: 'Kitchen sink', link: '/kitchen-sink/' },
            { text: 'Construction', link: '/reference/construction' },
            { text: 'Batch processing', link: '/reference/batch-processing' },
          ],
        },
        {
          text: 'Flow and recovery',
          items: [
            { text: 'Chaining', link: '/reference/chaining' },
            { text: 'Failure handling', link: '/reference/failure-handling' },
            { text: 'Boundaries', link: '/reference/boundaries' },
          ],
        },
        {
          text: 'Observability and output',
          items: [
            { text: 'Metadata and debugging', link: '/reference/metadata-debugging' },
            { text: 'FAQ', link: '/faq' },
          ],
        },
      ],
      '/': [
        {
          text: 'Start',
          items: [
            { text: 'Overview', link: '/' },
            { text: 'Getting started', link: '/getting-started' },
            { text: 'Kitchen sink', link: '/kitchen-sink/' },
            { text: 'Concepts overview', link: '/concepts/' },
            { text: 'Laravel Boost', link: '/laravel-boost' },
          ],
        },
        {
          text: 'Learn',
          items: [
            { text: 'Guides', link: '/guides/' },
            { text: 'Recipes', link: '/recipes/' },
            { text: 'Reference', link: '/reference/' },
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
