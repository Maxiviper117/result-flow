---
title: Laravel action pattern (optional)
---

# Laravel action pattern (optional)

Some teams use an “action class” style where a single class encapsulates a unit of work. This page shows how Result Flow fits that pattern. If you prefer standard controllers + services, use the other examples instead.

## Invokable action

```php
namespace App\Actions;

use Maxiviper117\ResultFlow\Result;

final class SendWelcomeEmail
{
    public function __invoke(array $user): Result
    {
        return Result::ok($user)
            ->then(fn ($u) => $this->send($u));
    }

    private function send(array $user): Result
    {
        // ... send email
        return Result::ok(['sent' => true, 'user_id' => $user['id']]);
    }
}
```

## Controller usage

```php
namespace App\Http\Controllers;

use App\Actions\SendWelcomeEmail;
use Illuminate\Http\Request;

final class WelcomeController
{
    public function store(Request $request, SendWelcomeEmail $action)
    {
        return $action($request->all())->toResponse();
    }
}
```

Notes:
- The action can be injected like a service.
- `Result` keeps the action side-effect safe and composable.

## Result functions used

- `ok()`, `then()`, `toResponse()`
