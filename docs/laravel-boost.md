# Laravel Boost

The package ships downstream application guidance in `resources/boost/guidelines/core.blade.php` and a central skill in `resources/boost/skills/result-flow/` with a local `references/api.md`. The guidance recommends explicit Result chains, exception boundaries, and response field mapping. Consult the [Laravel Boost documentation](https://laravel.com/docs/12.x/boost) for installation and discovery in your application.

Returning a Fail from a Laravel transaction does not cause rollback. See [transaction rollback](/guides/transaction-rollback) and the [reference](/reference/).
