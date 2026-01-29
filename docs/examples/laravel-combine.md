---
title: Laravel combine + combineAll example
---

# Laravel combine + combineAll example

This example shows how to combine multiple Results when you need to load several resources or run multiple validations.

## Service

```php
namespace App\Services;

use Maxiviper117\ResultFlow\Result;

final class ProfileService
{
    public function loadProfile(int $userId): Result
    {
        $user = $this->loadUser($userId);
        $account = $this->loadAccount($userId);
        $prefs = $this->loadPreferences($userId);

        return Result::combine([$user, $account, $prefs])
            ->map(fn (array $values) => [
                'user' => $values[0],
                'account' => $values[1],
                'prefs' => $values[2],
            ]);
    }

    public function validateProfile(array $data): Result
    {
        return Result::combineAll([
            $this->validateEmail($data['email'] ?? null),
            $this->validateName($data['name'] ?? null),
            $this->validateTimezone($data['timezone'] ?? null),
        ]);
    }

    private function loadUser(int $id): Result { return Result::ok(['id' => $id]); }
    private function loadAccount(int $id): Result { return Result::ok(['id' => $id]); }
    private function loadPreferences(int $id): Result { return Result::ok(['tz' => 'UTC']); }

    private function validateEmail(?string $email): Result
    {
        return $email ? Result::ok($email) : Result::fail('email required');
    }

    private function validateName(?string $name): Result
    {
        return $name ? Result::ok($name) : Result::fail('name required');
    }

    private function validateTimezone(?string $tz): Result
    {
        return $tz ? Result::ok($tz) : Result::fail('timezone required');
    }
}
```

Notes:
- `combine()` fails fast and returns the first error.
- `combineAll()` returns an array of all errors for full validation reporting.
- Metadata from each result is merged in order.

## Result functions used

- `combine()`, `combineAll()`, `ok()`, `fail()`, `map()`
