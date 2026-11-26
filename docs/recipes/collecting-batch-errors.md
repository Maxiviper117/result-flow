# Collecting batch errors

Validate every named input and retain each error by key.

```php
use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\ResultBatch;

$result = ResultBatch::mapAllErrors(['email' => '', 'name' => 'Ada'],
    fn (string $value): Result => $value === '' ? Result::fail('Required') : Result::ok($value));
// Fail(['email' => 'Required']); no partial successes are returned.
```

Related: [batch reference](/reference/batch-processing) and [batch strategy](/guides/batch-strategy).

## Run it and inspect the error bag

From the checkout, run `php examples/v2/batch.php`. It prints `{"email":"Required"}`. The callback visits both fields, but the resulting failure contains only fields with errors. If you supply a valid email, the success payload contains both names and values. To save that valid submission, pass the combined Result to `andThen($save)`.
