# Changelog

## [1.9.0](https://github.com/Maxiviper117/result-flow/compare/v1.8.0...v1.9.0) (2026-02-15)


### Features

* add Laravel Boost package guidelines and skills for improved AI-assisted development ([cbc0528](https://github.com/Maxiviper117/result-flow/commit/cbc0528c9692df87d056097a146ab9252eff42ec))
* codex/add-laravel-boost-package-guidelines ([#55](https://github.com/Maxiviper117/result-flow/issues/55)) ([cbc0528](https://github.com/Maxiviper117/result-flow/commit/cbc0528c9692df87d056097a146ab9252eff42ec))
* integrate Rector for code quality checks and update documentation for usage ([#56](https://github.com/Maxiviper117/result-flow/issues/56)) ([346dd98](https://github.com/Maxiviper117/result-flow/commit/346dd9843c01164d3a6047e919608a665df44024))

## [1.8.0](https://github.com/Maxiviper117/result-flow/compare/v1.7.1...v1.8.0) (2026-02-07)


### Features

* add collection mapping helpers for Result processing ([#51](https://github.com/Maxiviper117/result-flow/issues/51)) ([16e6aeb](https://github.com/Maxiviper117/result-flow/commit/16e6aebc4c801becc3882b3ce8dfccf0d0b96903))

## [1.7.1](https://github.com/Maxiviper117/result-flow/compare/v1.7.0...v1.7.1) (2026-01-29)


### Bug Fixes

* enhance typing with generics for Result handling across multiple classes ([#49](https://github.com/Maxiviper117/result-flow/issues/49)) ([4d7a3a0](https://github.com/Maxiviper117/result-flow/commit/4d7a3a08b41e1f80d6619ff0366a8f90476a959e))

## [1.7.0](https://github.com/Maxiviper117/result-flow/compare/v1.6.1...v1.7.0) (2026-01-29)


### Features

* Add opt-in attempt metadata to retrier ([#47](https://github.com/Maxiviper117/result-flow/issues/47)) ([a7faca6](https://github.com/Maxiviper117/result-flow/commit/a7faca641d97c7ceb09d976ee7084bae8ebdf5eb))


### Miscellaneous Chores

* retry helpers plan ([f82bb9f](https://github.com/Maxiviper117/result-flow/commit/f82bb9f4441aea0814c8461159108fc464efa380))
* update release-please config to include extra files ([514251a](https://github.com/Maxiviper117/result-flow/commit/514251ab8e4131af597bb2a265314a242a767526))

## [1.6.1](https://github.com/Maxiviper117/result-flow/compare/v1.6.0...v1.6.1) (2026-01-26)


### Bug Fixes

* relocate retry helper ([#41](https://github.com/Maxiviper117/result-flow/issues/41)) ([f98259a](https://github.com/Maxiviper117/result-flow/commit/f98259af35c109f170d920f3b6106102e414fece))

## [1.6.0](https://github.com/Maxiviper117/result-flow/compare/v1.5.1...v1.6.0) (2026-01-26)


### Features

* add Retry helper and Result::retry integration ([58e8741](https://github.com/Maxiviper117/result-flow/commit/58e874166e08f51c42f112652b322dd7390bd323))
* Add retry helpers for transient failures (optional utility) ([58e8741](https://github.com/Maxiviper117/result-flow/commit/58e874166e08f51c42f112652b322dd7390bd323))
* Add retry helpers for transient failures (optional utility) ([#39](https://github.com/Maxiviper117/result-flow/issues/39)) ([58e8741](https://github.com/Maxiviper117/result-flow/commit/58e874166e08f51c42f112652b322dd7390bd323)), closes [#17](https://github.com/Maxiviper117/result-flow/issues/17)

## [1.5.1](https://github.com/Maxiviper117/result-flow/compare/v1.5.0...v1.5.1) (2026-01-25)


### Bug Fixes

* resolve phpstan varTag.nativeType in ResultMatch.php ([#37](https://github.com/Maxiviper117/result-flow/issues/37)) ([498026d](https://github.com/Maxiviper117/result-flow/commit/498026db43b434358b33437a41cfd41d90155147))

## [1.5.0](https://github.com/Maxiviper117/result-flow/compare/v1.4.0...v1.5.0) (2026-01-25)


### Features

* Add glob pattern support for sensitive keys and update docs ([#35](https://github.com/Maxiviper117/result-flow/issues/35)) ([d83d70e](https://github.com/Maxiviper117/result-flow/commit/d83d70eaa5d1cb70806d83e1c7c1fa39ec1f02b4))
* add sanitization and safety guide, update documentation for sensitive data handling ([d83d70e](https://github.com/Maxiviper117/result-flow/commit/d83d70eaa5d1cb70806d83e1c7c1fa39ec1f02b4))
* add VitePress skill documentation for inspection and operation ([d83d70e](https://github.com/Maxiviper117/result-flow/commit/d83d70eaa5d1cb70806d83e1c7c1fa39ec1f02b4))


### Bug Fixes

* clarify sensitive_keys option in configuration instructions ([d83d70e](https://github.com/Maxiviper117/result-flow/commit/d83d70eaa5d1cb70806d83e1c7c1fa39ec1f02b4))
* remove unnecessary whitespace in VitePress config ([d83d70e](https://github.com/Maxiviper117/result-flow/commit/d83d70eaa5d1cb70806d83e1c7c1fa39ec1f02b4))
* update redaction string and improve formatting in test output ([d83d70e](https://github.com/Maxiviper117/result-flow/commit/d83d70eaa5d1cb70806d83e1c7c1fa39ec1f02b4))


### Miscellaneous Chores

* **deps:** bump dependabot/fetch-metadata from 2.4.0 to 2.5.0 ([#32](https://github.com/Maxiviper117/result-flow/issues/32)) ([cc69579](https://github.com/Maxiviper117/result-flow/commit/cc69579b0fca9430bbe8f998502d267803b3d8df))

## [1.4.0](https://github.com/Maxiviper117/result-flow/compare/v1.3.0...v1.4.0) (2026-01-11)


### Features

* slim down Result responsibilities ([#30](https://github.com/Maxiviper117/result-flow/issues/30)) ([1fd66a7](https://github.com/Maxiviper117/result-flow/commit/1fd66a73a5aa5585a22b28400888dd4ac7dfc06f))


### Bug Fixes

* Correct structure of release-please configuration file ([17898b1](https://github.com/Maxiviper117/result-flow/commit/17898b1bb9607873e664a997adb1552e87b2ae85))


### Miscellaneous Chores

* enhance test scripts for parallel execution and coverage ([38ddb59](https://github.com/Maxiviper117/result-flow/commit/38ddb594406f6cc84198cf62d1f7d6a4ebb822b0))

## [1.3.0](https://github.com/Maxiviper117/result-flow/compare/result-flow-v1.2.0...result-flow-v1.3.0) (2026-01-10)


### Features

* add feature request issue template ([8180070](https://github.com/Maxiviper117/result-flow/commit/81800704bbffeb50d6dc682cd93ea49bf9e45260))
* add GitHub Actions workflow for deploying documentation ([73c4a8d](https://github.com/Maxiviper117/result-flow/commit/73c4a8d4dfdc2a8095e4035e48a46f618f20831e))
* add IlluminateSupportStub for unit testing compatibility ([5efcb62](https://github.com/Maxiviper117/result-flow/commit/5efcb627f7c163388aa027213a3ac6d8aba65b17))
* add Laravel service provider and configuration for debug sanitization ([c021cf3](https://github.com/Maxiviper117/result-flow/commit/c021cf3dba92ee670a8e7955407dbb0e26facbe6))
* Add Result Transformers (JSON, XML, Response) ([#27](https://github.com/Maxiviper117/result-flow/issues/27)) ([cabd88b](https://github.com/Maxiviper117/result-flow/commit/cabd88bec361972019b61d88e3537f9f011ed5ea)), closes [#26](https://github.com/Maxiviper117/result-flow/issues/26)
* enhance debug sanitization with configurable options in Laravel ([3f21a13](https://github.com/Maxiviper117/result-flow/commit/3f21a1395465e9368d95d138f885666e9e9b600b))
* enhance documentation with new deep dive sections and metadata handling ([4e4e826](https://github.com/Maxiviper117/result-flow/commit/4e4e826a1c33e56994ad361012b7bbdd0e831878))
* support callable array steps in runChain method ([fb667ee](https://github.com/Maxiviper117/result-flow/commit/fb667ee0527ea21c5f6df6de05479d14a68a2513))
* update workflows to trigger on pull requests and allow manual dispatch ([a3c3ac9](https://github.com/Maxiviper117/result-flow/commit/a3c3ac9acafd5017794b6e6cd486dd09fdfdeca1))


### Bug Fixes

* correct base path in VitePress configuration ([4f75aff](https://github.com/Maxiviper117/result-flow/commit/4f75affb200a0a4b01c41f5c4b1e2849c13ccac7))
* update config path handling in ResultFlowServiceProvider for better compatibility ([16c1916](https://github.com/Maxiviper117/result-flow/commit/16c1916d178a0783762e37af78f1520486439643))
* update workflows to trigger on push instead of pull request ([5fbd299](https://github.com/Maxiviper117/result-flow/commit/5fbd2998325a1c20595680196b5a225a26ab20bd))


### Miscellaneous Chores

* add pnpm workspace configuration to include only built dependencies ([073c025](https://github.com/Maxiviper117/result-flow/commit/073c0258f0981a068502943974702a7386b97cb3))
* add VitePress cache to .gitignore and configure local search provider ([c8e2a85](https://github.com/Maxiviper117/result-flow/commit/c8e2a85d748ee9888620de50b9525c908bc4753b))
* apply workspace changes (CI, templates, docs) ([fe3d8b8](https://github.com/Maxiviper117/result-flow/commit/fe3d8b873094f3e513adba8f792733265b89ccc8))
* ignore .vitepress/cache/ and all subfolders ([92bc260](https://github.com/Maxiviper117/result-flow/commit/92bc2602cecded3dfefde8615bd97f650617186f))
