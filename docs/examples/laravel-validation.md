---
title: Laravel validation + error shaping
---

# Laravel validation + error shaping

This example focuses on validation and consistent error shapes using common Laravel patterns: Form Requests + services. The goal is to keep validation errors readable for API clients while still using Result Flow end-to-end.

## Form Request

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'min:10'],
        ];
    }
}
```

## Service

```php
namespace App\Services;

use Maxiviper117\ResultFlow\Result;

final class UserService
{
    public function register(array $data): Result
    {
        // Domain-level validation or checks can still be done here
        if (str_ends_with($data['email'], '@blocked.test')) {
            return Result::fail([
                'type' => 'blocked',
                'message' => 'Email domain is blocked',
            ]);
        }

        // ... create user and return Result::ok($user)
        return Result::ok(['id' => 1, 'email' => $data['email']]);
    }
}
```

## Controller

```php
namespace App\Http\Controllers;

use App\Http\Requests\RegisterUserRequest;
use App\Services\UserService;
use Maxiviper117\ResultFlow\Result;

final class RegisterController
{
    public function store(RegisterUserRequest $request, UserService $users)
    {
        $result = Result::ok($request->validated())
            ->then(fn ($data, $meta) => $users->register($data))
            ->otherwise(function ($error, $meta) {
                // Normalize all failures into a consistent response shape
                if (is_array($error) && ($error['type'] ?? null) === 'blocked') {
                    return Result::fail([
                        'message' => $error['message'],
                        'code' => 'blocked_domain',
                    ], $meta);
                }

                return Result::fail([
                    'message' => 'Registration failed',
                ], $meta);
            });

        return $result->toResponse();
    }
}
```

Notes:
- Form Requests keep input validation in the HTTP layer.
- Domain-specific checks live in the service.
- `otherwise()` centralizes error formatting.
- The controller always returns `toResponse()` for a consistent JSON response.

## Result functions used

- `ok()`, `then()`, `otherwise()`, `fail()`, `toResponse()`
