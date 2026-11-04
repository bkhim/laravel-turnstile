# Upgrading

This guide explains how to move between versions of `bkhim/laravel-turnstile`.

## From v3.0 to v3.1

**Nothing is required.** v3.1 is a safe upgrade. Your forms, configuration, and code keep working exactly as they are.

You can start using the new protections whenever you are ready.

### 1. Add your websites

Add the websites that are allowed to send tokens to your application:

```env
TURNSTILE_HOSTNAMES=example.com,www.example.com
```

During local development, add `localhost` and `127.0.0.1` as well. Cloudflare's test keys always report the hostname `localhost`.

Once this list is not empty, the package will reject tokens that came from a different website. If you leave the list empty, the package will not check the hostname, which is how v3.0 behaved.

### 2. Add an action to each form

Give each form a short name:

```blade
<x-turnstile-widget action="signup" />
```

Then check the same action on the server:

```php
use Bkhim\LaravelTurnstile\Rules\TurnstileCheck;

$request->validate([
    'cf-turnstile-response' => [
        'required',
        new TurnstileCheck(action: 'signup'),
    ],
]);
```

Using a different action for each form stops a token from one form being used on another.

### 3. Choose what happens when Cloudflare is unreachable

By default, v3.1 still throws an error, just like v3.0. If you would rather reject the request, turn this on:

```env
TURNSTILE_FAIL_CLOSED=true
```

Rejecting the request is safer, because a visitor cannot skip the check simply because Cloudflare is unavailable.

### 4. Try the new result object

`validateResponse()` is the recommended way to check a token when you also want hostname and action checks:

```php
use Bkhim\LaravelTurnstile\Facades\LaravelTurnstile;

$result = LaravelTurnstile::validateResponse();

if (! $result->successful()) {
    // $result->reason() tells you what went wrong.
}
```

`validate()` still works and returns the same array as before.

### 5. Check your setup

Run:

```bash
php artisan turnstile:check
```

It will show your settings, warn about common mistakes, and confirm that Cloudflare accepts your secret key.

## From v3.1 to v4

Version 4 is not released yet. This section explains what we plan to change so you can prepare.

### What will change

- **Allowed hostnames will be required.** If no hostnames are configured, tokens will be rejected. Today you can turn this on early with `TURNSTILE_REQUIRE_HOSTNAMES=true`.
- **Requests will be rejected when Cloudflare is unreachable.** Today this is controlled by `TURNSTILE_FAIL_CLOSED`, which is off by default. In v4 it will be on by default.
- **`validateResponse()` will be the recommended way to check a token.** `validate()` will keep working, but we may deprecate it in a later release.

### How to prepare

1. Set `TURNSTILE_HOSTNAMES` in every environment.
2. Turn on `TURNSTILE_REQUIRE_HOSTNAMES=true` and `TURNSTILE_FAIL_CLOSED=true` to try the v4 behavior early.
3. Move new code to `validateResponse()`.
4. Run `php artisan turnstile:check` in each environment.

If you find a problem while preparing, please [open an issue](https://github.com/bkhim/laravel-turnstile/issues). We would rather hear about it before v4 ships.

## From `coderflex/laravel-turnstile`

If you are moving from the original package, the main change is the namespace.

```bash
composer remove coderflex/laravel-turnstile
composer require bkhim/laravel-turnstile
```

Replace the imports:

```php
// Before
use Coderflex\LaravelTurnstile\Facades\LaravelTurnstile;
use Coderflex\LaravelTurnstile\Rules\TurnstileCheck;

// After
use Bkhim\LaravelTurnstile\Facades\LaravelTurnstile;
use Bkhim\LaravelTurnstile\Rules\TurnstileCheck;
```

The Blade component, configuration keys, publish tags, and facade alias are unchanged.
