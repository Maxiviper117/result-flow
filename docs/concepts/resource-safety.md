---
title: Resource Safety
---

# Resource Safety

`bracket(...)` is the acquire/use/release pattern.

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::bracket(
    acquire: fn () => fopen($path, 'r'),
    use: fn ($handle) => fread($handle, 100),
    release: fn ($handle) => fclose($handle),
);
```

## Why it exists

Resource cleanup should not depend on every caller remembering to do the right thing.

`bracket(...)` makes cleanup part of the flow:

- acquire the resource
- use it
- always attempt release after acquisition succeeds

## Behavior to know

- if acquire fails, release is not called
- if use fails, release is still attempted
- if release throws after a use failure, the original failure stays and release exception is attached to metadata
- if use succeeds and release throws, the result becomes a failure

## Related pages

- [Resource cleanup recipe](/recipes/resource-cleanup)
- [Construction reference](/reference/construction)
