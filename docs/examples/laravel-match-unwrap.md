---
title: Laravel match + unwrap example
---

# Laravel match + unwrap example

This example shows two common ways to consume Results at the boundary: `match()` for explicit branching and `unwrap*()` for fast access.

## Controller (match)

```php
namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;

final class ReportController
{
    public function show(Request $request, ReportService $reports)
    {
        $result = $reports->generate($request->all());

        return $result->match(
            onSuccess: fn ($payload, $meta) => response()->json($payload, 200),
            onFailure: fn ($error, $meta) => response()->json(['error' => (string) $error], 400),
        );
    }
}
```

## CLI command (unwrap)

```php
namespace App\Console\Commands;

use App\Services\ReportService;
use Illuminate\Console\Command;

final class GenerateReport extends Command
{
    protected $signature = 'report:generate';

    public function handle(ReportService $reports): int
    {
        $result = $reports->generate([]);

        $path = $result->unwrapOrElse(fn ($error) => storage_path('reports/fallback.json'));
        $this->info("Report written to {$path}");

        return self::SUCCESS;
    }
}
```

Notes:
- `match()` forces both branches to be handled explicitly.
- `unwrapOrElse()` computes a fallback lazily when failures occur.

## Result functions used

- `match()`, `unwrapOrElse()`
