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
