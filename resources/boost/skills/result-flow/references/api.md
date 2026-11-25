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
