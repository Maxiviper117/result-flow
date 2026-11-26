# Result Flow v2 API

- `Result::ok($value, array $meta = [])` and `Result::fail($error, array $meta = [])` construct tagged Results. Null and falsy payloads remain distinct by branch.
- `Result::attempt(callable $fn, string $catch)` runs once, wraps its return without flattening, and captures only the named Throwable class and subclasses. Other exceptions propagate.
- `map(callable $fn)` maps an Ok value; `mapError(callable $fn)` maps a Fail error. Both keep metadata and do not catch exceptions.
- `andThen(callable $fn)` chains an Ok to a Result; `orElse(callable $fn)` replaces a Fail with a Result. Both are lazy on the inactive branch, propagate exceptions, and shallow-merge metadata with child keys winning.
- `match(callable $onSuccess, callable $onFailure)` invokes exactly one handler. `isOk()` and `isFail()` inspect the branch.
- `unwrap()` returns success or throws the Throwable failure unchanged; non-Throwable failures cause `UnwrapFailure` with the original `failure`. `unwrapOr($default)` uses an eager default.
- `meta()` reads the bag; `withMeta(array $meta)` returns a new Result with the whole bag replaced.

`ResultBatch::firstFailure` and `allErrors` aggregate evaluated keyed Results; `mapFirstFailure` stops invoking callbacks at the first failure and `mapAllErrors` visits all inputs. Metadata merges in iteration order. All-errors failure results contain errors, not partial successes.

`Retry::config()` captures thrown exceptions; restrict retries with `when`. `ResourceScope::run()` provides acquire/use/release behavior, including release-exception metadata when use already failed. `Output\Serialization` includes metadata; map fields explicitly for HTTP clients. `Output\DebugOutput` offers diagnostic sanitization.

For a complete runnable chain and boundary examples, consult the package's v2 quickstart and recipes.

Debug sanitization is configurable per application; verify its redaction and truncation settings before using diagnostic output.

`ResourceScope::run` calls release once after a successful acquisition even when use throws; if release also throws, the use error remains primary and the release exception is retained in metadata.

`andThen` and `orElse` require actual Result return values; invalid callback returns raise `TypeError`.

A null error remains a tagged failure and short-circuits `andThen`. Unselected exceptions from `attempt` propagate unchanged.

Laravel is optional for the core API. Verify transaction and response integration against the application's installed Laravel version.

Default debug sanitization detects array cycles and bounds array depth independently (`array_max_depth`, default 32, range 1–128); disabled or custom sanitizers bypass that safeguard. XML requires SimpleXML and rejects invalid UTF-8 and XML 1.0 characters. Retry defaults to all failures; choose transient-only predicates, use idempotency keys for writes, and remember delays block PHP.

An optional PHPStan rule in `phpstan-result-flow.neon` flags discarded standalone Result-returning calls. Intentional discards can use `@phpstan-ignore resultFlow.ignoredResult`; assignment alone does not prove handling.

`ResultBatch::firstSuccess` selects the first success or last failure and rejects empty input; `zip` combines two evaluated Results. Use `mapAllErrors` with arrays of messages for field-level validation.

Structured error causes appear in `DebugOutput::toArray()` under `error_cause`, sanitized with the same configured sanitizer as metadata.

`Output\ProblemDetails::response` builds an explicit RFC 9457 problem JSON response. Map domain errors to safe public fields; metadata is not automatically included.

Use the getting-started order example to compare valid and blank inputs. A failed validator skips the save action and reaches the failure handler directly.

An empty validation batch succeeds with an empty array. Validate required cardinality separately before collecting per-field errors.
