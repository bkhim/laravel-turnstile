# Security Policy

## Supported versions

We only support versions of this package that run on non-EOL versions of PHP and Laravel:

| Package version | PHP        | Laravel |
|-----------------|------------|---------|
| 3.x             | 8.3 – 8.5  | 12 – 13 |

When a supported PHP or Laravel version reaches end of life, support for it will be
dropped in the next major release.

## Keeping your Turnstile setup safe

A short checklist for a safer setup:

- **Keep your secret key private.** Store it in your environment or secret manager, never in frontend code or your repository.
- **Use HTTPS.** Tokens should only travel over a secure connection.
- **Check the hostname.** Set `TURNSTILE_HOSTNAMES` so a token from another website cannot be used on yours.
- **Use an action for each form.** Set `data-action` on the widget and check the same action on the server.
- **Reject requests when Cloudflare is unreachable.** Turn on `TURNSTILE_FAIL_CLOSED` if you would rather fail safe.
- **Do not reuse tokens.** Turnstile tokens are single-use and expire after about five minutes. Reset the widget after a failed attempt.
- **Never log tokens or secrets.** The package can log the reason a check failed without logging either of them.
- **Run `php artisan turnstile:check` after deployment.** It catches missing keys, local hostnames in production, and other common mistakes.
- **Keep the package and your framework up to date.** We only support maintained PHP and Laravel versions.

## Reporting a vulnerability

Please **do not** open a public issue for security vulnerabilities.

Instead, report the issue privately using GitHub's
[security advisory](https://github.com/bkhim/laravel-turnstile/security/advisories/new)
feature. Please include:

- A description of the vulnerability and its potential impact.
- Steps to reproduce it.
- The affected package version(s), PHP version, and Laravel version.

We will acknowledge your report as soon as possible and work with you on a fix and a
coordinated disclosure. Thank you for helping keep the package and its users safe.
