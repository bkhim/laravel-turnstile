# Changelog

All notable changes to `laravel-turnstile` will be documented in this file.

## v3.1.0 - 2026-11-04

This release adds stronger checks that you can turn on when you are ready. **Nothing here breaks an existing v3.0 installation.** Your forms, configuration, and code keep working as they are.

### New ways to protect your forms

- **Check the website hostname.** You can now tell the package which websites are allowed to send tokens. It will then compare the hostname that Cloudflare reports with your list. This matters when one widget is used on more than one website, including `localhost` during development.
- **Check the form action.** If you use `data-action="signup"` (or the `action` option) on the widget, you can ask the package to confirm that the token was created for that same action. This stops a token from one form being reused on another.
- **Send the visitor's IP address.** The package now includes the visitor's IP address in the request to Cloudflare by default, so Cloudflare has more context when it checks the token. You can turn this off with `TURNSTILE_SEND_REMOTE_IP=false`.
- **A new `validateResponse()` method.** It does the same Cloudflare check as `validate()`, but it can also check the hostname and action. It returns a friendly result object that you can read like an array (`$result['success']`) or with methods such as `$result->successful()`, `$result->reason()`, and `$result->hostname()`. `validate()` still works exactly as before.
- **A new `turnstile:check` command.** Run `php artisan turnstile:check` to see your settings, catch common mistakes, and confirm that Cloudflare accepts your secret key. It never prints the secret.
- **A `LaravelTurnstile::fake()` helper.** This makes it easy to fake Turnstile in your own tests, so your test suite never calls Cloudflare.
- **More widget options.** You can now set `cdata`, expired/timeout/unsupported callbacks, before/after interactive callbacks, retry behavior, refresh behavior, a tab index, an element id, and explicit rendering. The Turnstile script now loads with `async defer`.

### Changes you might notice

- The default timeout for the request to Cloudflare is now 10 seconds instead of 30. You can change it with `TURNSTILE_TIMEOUT`.
- Tokens that are empty, not text, or longer than 2048 characters are rejected before the package contacts Cloudflare. This is what Cloudflare would do anyway; it just saves a round trip.
- Failed checks made with the validation rule can be written to your application log without the token or secret by setting `TURNSTILE_LOG_FAILURES=true`.
- The `TurnstileCheck` rule can now be given an action, hostnames, a remote IP, and a preference for what to do when Cloudflare is unreachable.

### Still safe to upgrade

- `validate()` keeps its signature and still returns the same array.
- `TurnstileCheck` only gained optional arguments, so existing usages keep working.
- Existing configuration keys are unchanged. New keys are added with safe defaults.
- No supported PHP or Laravel versions were removed.

### Preparing for v4

A future major release will make the safer behavior the default. Nothing changes yet, but here is what to expect:

- Allowed hostnames will be required, and requests will be rejected when none are configured.
- Requests will be rejected when Cloudflare cannot be reached, unless you explicitly turn that off.
- `validateResponse()` will become the recommended way to check a token.

You can prepare now by setting `TURNSTILE_HOSTNAMES`, and by turning on `TURNSTILE_REQUIRE_HOSTNAMES` and `TURNSTILE_FAIL_CLOSED` when you are ready. See [UPGRADING.md](UPGRADING.md) for details.

### Thanks

Thank you to everyone using and reporting issues against the package. Your feedback is what keeps it improving.

## v3.0.0 - 2026-11-03

### Fork & rebrand

- First release under the `bkhim/laravel-turnstile` package name.
- Forked and now maintained by [bkhim](https://github.com/bkhim) as
  [`bkhim/laravel-turnstile`](https://github.com/bkhim/laravel-turnstile).
  We intend to maintain the package indefinitely and welcome all contributions.
- Renamed the namespace from `Coderflex\LaravelTurnstile` to `Bkhim\LaravelTurnstile`.
- Updated the Composer package name, service provider, facade, and autoloading.

### Support

- Dropped Laravel 10 and 11 because they have reached end of life.
- Dropped PHP 8.1 and 8.2 because they are end-of-life or about to reach it.
- Added PHP 8.5 support.
- The supported matrix is now Laravel 12.x and 13.x on PHP 8.3, 8.4, and 8.5.

### Changed

- Removed the redundant direct `guzzlehttp/guzzle` dependency. Laravel's HTTP client
  already provides it.
- Fixed the `UnkownErrorOccuredException` typo; the class is now
  `UnknownErrorOccurredException`.
- Fixed the "unkown"/"occurred" typos in error messages.
- Added return types and improved type safety throughout the codebase.
- The widget now renders the Cloudflare script only once per page and omits empty
  data attributes. Added `action`, `appearance`, `execution`, and `responseFieldName`
  options.
- The validation rule now reads only string values and falls back to a default message.
- Updated dev tooling: Larastan 3.x (`larastan/larastan`), PHPStan 2.x, Pest 4/5,
  Testbench 10/11, and Pint 1.x.
- Rewrote the test suite to use `Http::fake()` so tests never call Cloudflare.

## v1.0.3 - 2023-05-30

### What's Changed

- Refactoring by @ousid in https://github.com/coderflexx/laravel-turnstile/pull/3

**Full Changelog**: https://github.com/coderflexx/laravel-turnstile/compare/v1.0.2...v1.0.3

## v1.0.2 - 2023-05-30

### What's Changed

- Component Prefix by @ousid in https://github.com/coderflexx/laravel-turnstile/pull/2

**Full Changelog**: https://github.com/coderflexx/laravel-turnstile/compare/v1.0.1...v1.0.2

## v1.0.1 - 2023-05-30

### What's Changed

- Fix Known Bug by @ousid in https://github.com/coderflexx/laravel-turnstile/pull/1

### New Contributors

- @ousid made their first contribution in https://github.com/coderflexx/laravel-turnstile/pull/1

**Full Changelog**: https://github.com/coderflexx/laravel-turnstile/compare/v1.0.0...v1.1.0

## v1.0.0 - 2023-05-27

**Full Changelog**: https://github.com/coderflexx/laravel-turnstile/commits/v1.0.0
