# Contributing

Thank you for considering a contribution to **bkhim/laravel-turnstile**! This package is
a long-term community-maintained fork, and it only stays healthy because people like you
take the time to report bugs, suggest ideas, and send improvements.

**Every kind of contribution is welcome**, including:

- Bug reports and reproduction cases
- Feature requests and design suggestions
- Documentation and typo fixes
- Test improvements
- Pull requests for code changes

You do not need to be a maintainer or a long-time contributor to help. If you are unsure
whether something is worth reporting, please open an issue anyway.

## Reporting bugs

When opening an issue, please include:

- The versions of PHP, Laravel, and this package you are using.
- A clear description of the expected and actual behaviour.
- Steps to reproduce the issue.
- Relevant logs, stack traces, or a minimal reproduction repository if possible.

Please **do not** include your Turnstile secret key or any other credentials.

## Suggesting features

Open an issue describing the problem you are trying to solve, not only the solution you
have in mind. That helps us find the best approach together.

## Pull requests

1. Fork the repository and create a branch from `main`.
2. Install dependencies with `composer install`.
3. Add or update tests for your change.
4. Run the test suite and static analysis:
   ```bash
   composer test
   composer analyse
   ```
5. Format your code:
   ```bash
   composer format
   ```
6. Open a pull request with a clear description of the change and the reasoning behind it.

### Coding guidelines

- Follow the existing code style (Laravel Pint).
- Keep the public API backwards compatible within a major version.
- Add tests for new behaviour and bug fixes.
- Update the `README.md` and `CHANGELOG.md` when appropriate.

## Support policy

We only support non-EOL versions of PHP and Laravel:

- PHP 8.3, 8.4, and 8.5
- Laravel 12.x and 13.x

When a supported version reaches end of life, it will be dropped in the next major release.

## Code of conduct

Please be respectful and constructive. Harassment or personal attacks are not welcome.
We want this to be a friendly project for everyone.

## Questions

If you have a question that is not a bug report or a feature request, feel free to open an
issue with the `question` label. We are happy to help.
