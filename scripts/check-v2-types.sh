#!/usr/bin/env sh
set -eu

vendor/bin/phpstan analyse -c phpstan-v2.neon --no-progress

if output=$(vendor/bin/phpstan analyse -c phpstan-v2.neon --no-progress tests/Types/InvalidAndThen.php 2>&1); then
    printf '%s\n' 'Expected PHPStan to reject a plain-value andThen callback.' >&2
    exit 1
fi
printf '%s\n' "$output" | grep -F 'Closure(int): int given.' >/dev/null || {
    printf '%s\n' "$output" >&2
    exit 1
}
printf '%s\n' 'PHPStan rejected the plain-value andThen callback as expected.'
php scripts/check-unused-result.php
