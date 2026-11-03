# Changelog

All notable changes to `laravel-turnstile` will be documented in this file.

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
