# Result Flow v2 API

- `Result::ok($value, array $meta = [])` and `Result::fail($error, array $meta = [])` construct tagged Results. Null and falsy payloads remain distinct by branch.
- `Result::attempt(callable $fn, string $catch)` runs once, wraps its return without flattening, and captures only the named Throwable class and subclasses. Other exceptions propagate.
- `map(callable $fn)` maps an Ok value; `mapError(callable $fn)` maps a Fail error. Both keep metadata and do not catch exceptions.
- `andThen(callable $fn)` chains an Ok to a Result; `orElse(callable $fn)` replaces a Fail with a Result. Both are lazy on the inactive branch, propagate exceptions, and shallow-merge metadata with child keys winning.
- `match(callable $onSuccess, callable $onFailure)` invokes exactly one handler. `isOk()` and `isFail()` inspect the branch.
- `unwrap()` returns success or throws the Throwable failure unchanged; non-Throwable failures cause `UnwrapFailure` with the original `failure`. `unwrapOr($default)` uses an eager default.
- `meta()` reads the bag; `withMeta(array $meta)` returns a new Result with the whole bag replaced.

`ResultBatch::firstFailure` and `allErrors` aggregate evaluated keyed Results; `mapFirstFailure` stops invoking callbacks at the first failure and `mapAllErrors` visits all inputs. Metadata merges in iteration order. All-errors failure results contain errors, not partial successes.

`Retry::config()` provides v1-compatible retries and captures thrown exceptions. Restrict retries with `when`. `ResourceScope::run()` provides v1-compatible acquire/use/release behavior, including release-exception metadata when use already failed. `Output\LegacyOutput` and `Laravel\LegacyResponse` preserve legacy output including metadata; map fields explicitly for new HTTP clients. `Output\DebugOutput` offers diagnostic sanitization.

For a complete runnable chain and boundary examples, consult the package's v2 quickstart and recipes. Keep the stable v1 and prerelease v2 API contracts distinct.

Debug sanitization is configurable per application; verify its redaction and truncation settings before using diagnostic output.

When preserving a legacy HTTP response, remove or allowlist internal metadata first and compare both status and body against the existing contract.

Use the documentation matching the installed major version; v1 chaining and exception behavior differs from v2.

Before stable v2 is released, explicitly select a v2 prerelease in Composer rather than assuming an unqualified requirement installs v2.

During prerelease, use the v2 documentation preview; the stable v1 site retains v1 API guidance until the stable major is published.

For v1 migrations, select `map` or `andThen` according to the callback return and review exception capture and metadata collisions at each step.

`ResourceScope::run` calls release once after a successful acquisition even when use throws; if release also throws, the use error remains primary and the release exception is retained in metadata.

`andThen` and `orElse` require actual Result return values; invalid callback returns raise `TypeError`.

A null error remains a tagged failure and short-circuits `andThen`. Unselected exceptions from `attempt` propagate unchanged.

Laravel is optional for the core API. Verify transaction and response integration against the application's installed Laravel version.

Default debug sanitization detects array cycles and bounds array depth independently (`array_max_depth`, default 32, range 1–128); disabled or custom sanitizers bypass that safeguard. Legacy XML requires SimpleXML and rejects invalid UTF-8 and XML 1.0 characters. Retry defaults to all failures; choose transient-only predicates, use idempotency keys for writes, and remember delays block PHP.

An optional PHPStan rule in `phpstan-result-flow.neon` flags discarded standalone Result-returning calls. Intentional discards can use `@phpstan-ignore resultFlow.ignoredResult`; assignment alone does not prove handling.
