# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 0.x.x   | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability within this package, please send an email to the maintainer. All security vulnerabilities will be promptly addressed.

**Please do not report security vulnerabilities through public GitHub issues.**

When reporting a vulnerability, please include:

- A description of the vulnerability
- Steps to reproduce the issue
- Possible impact of the vulnerability
- Any potential solutions you've identified

You can expect:

- A response acknowledging your report within 48 hours
- An assessment of the vulnerability within 7 days
- A fix or mitigation plan within 30 days for confirmed vulnerabilities

## Security Best Practices

When using this package:

1. **Keep dependencies updated** - Run `composer update` regularly to get security patches
2. **Use type hints** - Leverage PHP's type system and PHPStan for static analysis
3. **Sanitize error data** - When using `toDebugArray()` or logging errors, ensure sensitive data is not exposed
4. **Handle exceptions properly** - Use `Result::of()` to wrap risky operations and prevent exception leakage

## Acknowledgments

We appreciate responsible disclosure of security vulnerabilities.
