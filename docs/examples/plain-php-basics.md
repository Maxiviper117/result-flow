---
title: Plain PHP Basics
---

# Plain PHP Basics

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok(['id' => 1], ['request_id' => 'r-1'])
    ->then(fn (array $user) => Result::ok([...$user, 'active' => true]))
    ->map(fn (array $user) => $user['id'])
    ->match(
        onSuccess: fn (int $id) => "user-id={$id}",
        onFailure: fn ($error) => "error={$error}",
    );
```

Use this pattern when you want explicit branch handling without framework coupling.

Related:
- [Getting Started](/getting-started)
- [Chaining and Transforming](/result/chaining)
