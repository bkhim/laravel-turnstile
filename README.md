# Laravel Turnstile

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bkhim/laravel-turnstile.svg?style=flat-square)](https://packagist.org/packages/bkhim/laravel-turnstile)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/bkhim/laravel-turnstile/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/bkhim/laravel-turnstile/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/bkhim/laravel-turnstile/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/bkhim/laravel-turnstile/actions?query=workflow%3A%22Fix+PHP+code+style+issues%22+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/bkhim/laravel-turnstile.svg?style=flat-square)](https://packagist.org/packages/bkhim/laravel-turnstile)

This package makes it easy to add [Cloudflare Turnstile](https://developers.cloudflare.com/turnstile/) to a Laravel application. Turnstile is a friendly alternative to a CAPTCHA: most visitors never have to solve a puzzle, and your forms get protection from bots.

> ### A community-maintained fork
>
> This package is a fork of [coderflexx/laravel-turnstile](https://github.com/coderflexx/laravel-turnstile).
> The original project is no longer actively maintained, so [bkhim](https://github.com/bkhim)
> has taken over stewardship of it.
>
> **We plan to maintain this package indefinitely.** We genuinely welcome bug reports,
> feature requests, suggestions, and pull requests from the community. If there is
> something you would like to see improved, please [open an issue](https://github.com/bkhim/laravel-turnstile/issues)
> or send a pull request. See [Contributing](#contributing).

## Requirements

We only support versions that are still maintained. End-of-life versions are **not** supported, so the package never keeps you on an unsupported runtime.

| What | Supported versions |
|------|--------------------|
| PHP | 8.3, 8.4, 8.5 |
| Laravel | 12.x, 13.x |
| Testbench (for package development) | 10.x for Laravel 12, 11.x for Laravel 13 |

A few dates worth knowing:

- Laravel 12 gets security fixes until **February 24, 2027**.
- Laravel 13 gets security fixes until **March 17, 2028**.
- PHP 8.2 reaches end of life on **December 31, 2026**, which is why the oldest supported version is PHP 8.3.

When a supported version reaches end of life, we will drop it in the next major release.

## Installation

Install the package with Composer:

```bash
composer require bkhim/laravel-turnstile
```

Then publish the configuration file:

```bash
php artisan vendor:publish --tag="turnstile-config"
```

You can publish the views too, if you want to change how the widget is rendered:

```bash
php artisan vendor:publish --tag="turnstile-views"
```

## Getting your Turnstile keys

You need two keys from Cloudflare: a **site key** (public, used by the widget in the browser) and a **secret key** (private, used by your server).

1. Open the [Cloudflare Turnstile dashboard](https://dash.cloudflare.com/?to=/:account/turnstile).
2. Create a widget and add the websites where it will be used.
3. Copy the site key and the secret key into your `.env` file:

```env
TURNSTILE_SITE_KEY=your-site-key
TURNSTILE_SECRET_KEY=your-secret-key
```

Never put the secret key in your frontend code or commit it to your repository.

### Cloudflare's test keys

Cloudflare provides test keys that always pass or always fail. They are handy for local development and automated tests. You can read the full list in [Cloudflare's testing guide](https://developers.cloudflare.com/turnstile/reference/testing/), but the most useful ones are:

| Site key | Secret key | What happens |
|----------|------------|--------------|
| `1x00000000000000000000AA` | `1x0000000000000000000000000000000AA` | Always passes |
| `2x00000000000000000000AB` | `2x0000000000000000000000000000000AA` | Always fails |
| `3x00000000000000000000FF` | `1x0000000000000000000000000000000AA` | Shows an interactive challenge, then passes |

Test keys work on any hostname, including `localhost`, and they always report the hostname `localhost`. Keep that in mind when you turn on hostname checking locally.

## Adding the widget to a form

Put the widget inside the form you want to protect:

```blade
<form action="/signup" method="POST">
    @csrf

    <input type="text" name="name" />

    <x-turnstile-widget
        id="signup-turnstile"
        theme="auto"
        language="en-US"
        size="normal"
        action="signup"
    />

    <button type="submit">Sign up</button>
</form>
```

Here is what each option does. All of them are optional.

| Option | Default | What it does |
|--------|---------|--------------|
| `id` | none | Gives the widget an HTML id. Useful when you need to reset it later. |
| `theme` | `auto` | `auto`, `light`, or `dark`. |
| `language` | `en-US` | The language of the widget. |
| `size` | `normal` | `normal` or `compact`. |
| `action` | none | A short name for the form, such as `signup` or `login`. Your server can check it later. |
| `cdata` | none | Extra custom data that Cloudflare will return with the token. |
| `appearance` | none | `always`, `execute`, or `interaction-only`. |
| `execution` | none | `render` or `execute`. |
| `responseFieldName` | none | Changes the name of the hidden field that holds the token. |
| `callback` | none | The name of a JavaScript function to call when the challenge succeeds. |
| `errorCallback` | none | The name of a JavaScript function to call when the challenge fails. |
| `expiredCallback` | none | Called when the token expires. |
| `timeoutCallback` | none | Called when the challenge times out. |
| `unsupportedCallback` | none | Called when the visitor's browser is not supported. |
| `beforeInteractiveCallback` | none | Called before an interactive challenge starts. |
| `afterInteractiveCallback` | none | Called after an interactive challenge finishes. |
| `retry` | none | `auto` or `never`. Controls automatic retries. |
| `refreshExpired` | none | `auto`, `manual`, or `never`. Controls what happens when a token expires. |
| `tabindex` | none | Sets the keyboard tab order. |
| `render` | `implicit` | Use `explicit` if you want to render the widget yourself with JavaScript. |
| `onload` | none | The name of a JavaScript function to call when the Turnstile script has loaded. |

The package adds the Turnstile script only once per page, so you can safely use more than one widget. Because the script is loaded once, the first widget on the page decides whether the script uses explicit rendering. If you need to mix rendering styles, include the Turnstile script yourself instead.

## Checking the token on your server

When a visitor submits the form, their browser sends a token in the `cf-turnstile-response` field. Your server must send that token to Cloudflare to confirm it is real. The package does that for you.

### The recommended way

Use `validateResponse()`. It checks the token and, if you ask it to, also confirms the website hostname and the form action:

```php
use Bkhim\LaravelTurnstile\Facades\LaravelTurnstile;

public function store(Request $request)
{
    $request->validate([
        'name' => ['required', 'string', 'max:250'],
    ]);

    $result = LaravelTurnstile::validateResponse(
        $request->input('cf-turnstile-response')
    );

    if (! $result->successful()) {
        // The token is not valid. You can look at $result->reason()
        // to find out why, for example "hostname_mismatch".
        return back()->withErrors([
            'cf-turnstile-response' => 'Please try the challenge again.',
        ]);
    }

    // The token is valid. Continue with your normal logic.
}
```

If the token is in the default `cf-turnstile-response` field, you can leave the argument out:

```php
$result = LaravelTurnstile::validateResponse();
```

The result object is friendly to read. You can use methods:

```php
$result->successful(); // true or false
$result->reason();     // a short reason when it failed
$result->hostname();   // the website Cloudflare says the token came from
$result->action();     // the action that was set on the widget
$result->errorCodes(); // Cloudflare's error codes, if any
```

You can also read it like an array, which is useful in Blade views:

```blade
@if (! $result['success'])
    <p>{{ $result['reason'] }}</p>
@endif
```

### The simple way (still supported)

If you only want to know whether Cloudflare accepted the token, you can keep using `validate()`. It works exactly as it did before and returns the response as an array:

```php
use Bkhim\LaravelTurnstile\Facades\LaravelTurnstile;

$response = LaravelTurnstile::validate();

if (! $response['success']) {
    // The token was not accepted.
}
```

`validate()` does not check the hostname or action. If you want those checks, use `validateResponse()` or pass them to the validation rule below.

## Using the validation rule

The `TurnstileCheck` rule keeps your controller tidy and shows a normal validation message when the check fails.

```php
use Bkhim\LaravelTurnstile\Rules\TurnstileCheck;

public function store(Request $request)
{
    $request->validate([
        'name' => ['required', 'string', 'max:250'],
        'cf-turnstile-response' => ['required', new TurnstileCheck()],
    ]);
}
```

To also check the form action and website hostname:

```php
use Bkhim\LaravelTurnstile\Rules\TurnstileCheck;

$request->validate([
    'cf-turnstile-response' => [
        'required',
        new TurnstileCheck(
            action: 'signup',
            hostnames: ['example.com', 'www.example.com'],
        ),
    ],
]);
```

You can change the validation message in `config/turnstile.php`:

```php
return [
    // ...
    'error_messages' => [
        'turnstile_check_message' => 'The CAPTCHA thinks you are a robot! Please refresh and try again.',
    ],
];
```

The message is passed through Laravel's translator, so you can translate it.

## Checking the website and the form

Cloudflare can tell you which website and which form a token was created for. Checking those details makes your protection stronger, especially when one widget is used on more than one website.

### Website hostnames

If someone copies a token from one website and tries to use it on another, a hostname check stops it.

Add the websites you trust to your `.env` file:

```env
TURNSTILE_HOSTNAMES=example.com,www.example.com
```

Once the list is set, `validateResponse()` uses it automatically:

```php
$result = LaravelTurnstile::validateResponse();
```

The package compares the hostname Cloudflare returns with your list. The match is exact and ignores case and a trailing dot, so `Example.com.` matches `example.com`.

A few rules of thumb:

- **During local development**, add `localhost` and `127.0.0.1` to the list. Cloudflare's test keys always report `localhost`.
- **In production**, never include `localhost`, `127.0.0.1`, or `0.0.0.0`.
- If you use more than one domain, list every hostname that serves the form. For example, `example.com` and `www.example.com` are different hostnames.
- If you use wildcard domains on the widget, Cloudflare still reports the exact hostname that was used. List the exact hostnames you expect.

If you want the package to reject tokens when no hostnames are configured, turn on the stricter setting:

```env
TURNSTILE_REQUIRE_HOSTNAMES=true
```

This is off by default so that existing applications keep working. It will become the default in a future major release. See [What is coming in v4](#what-is-coming-in-v4).

### Form actions

An action is a short name that tells your server which form the token belongs to. Use a different action for each form:

```blade
<x-turnstile-widget action="signup" />
```

```blade
<x-turnstile-widget action="login" />
```

Then check the same action on the server:

```php
new TurnstileCheck(action: 'signup')
```

or:

```php
$result = LaravelTurnstile::validateResponse(null, ['action' => 'signup']);
```

This stops a token from the login form being used on the signup form.

Actions can be 1 to 32 characters long and may only contain letters, numbers, underscores, and hyphens.

## What happens when Cloudflare is unreachable

Sometimes Cloudflare cannot be reached, or it returns an error.

If you use `validateResponse()` or the validation rule, you can choose what happens:

- **By default**, the package throws an error. This is how earlier versions behaved.
- **If you turn on `TURNSTILE_FAIL_CLOSED=true`**, the check fails instead, and the request is rejected. This is the safer choice, because a visitor cannot skip the check just because Cloudflare is having a bad day.

```env
TURNSTILE_FAIL_CLOSED=true
```

The older `validate()` method always throws when Cloudflare cannot be reached, because that is how it has always worked. If you want the safer behavior, use `validateResponse()` or the validation rule.

The default will change in a future major release. See [What is coming in v4](#what-is-coming-in-v4).

## If your page stays open

Turnstile tokens are single-use, and they expire after about five minutes. If your form submits with AJAX and the page stays open, ask Turnstile for a fresh token after a failed attempt.

Give the widget an `id`:

```blade
<x-turnstile-widget id="signup-turnstile" />
```

Then reset it after the request finishes:

```js
const response = await fetch('/signup', { method: 'POST', body: formData });

if (! response.ok) {
    // Let Turnstile create a new token before the visitor tries again.
    window.turnstile.reset('signup-turnstile');
}
```

If you render the widget yourself with JavaScript, keep the widget id that `turnstile.render()` returns and pass that to `turnstile.reset()`.

Do not send the same token twice. A second attempt with the same token will be rejected with the `timeout-or-duplicate` error code.

## Checking your setup

Run this command to see how the package is configured and to check that Cloudflare accepts your secret key:

```bash
php artisan turnstile:check
```

The command:

- shows your site key, allowed hostnames, action, and timeout settings;
- warns you if hostname checking is turned on but no hostnames are configured;
- warns you if `localhost` or a Cloudflare test key is being used in production;
- sends a dummy token to Cloudflare to confirm that the secret key works.

It never prints your secret key.

## Testing your application

You do not need to call Cloudflare from your own test suite. You can fake the response instead.

With the package helper:

```php
use Bkhim\LaravelTurnstile\Facades\LaravelTurnstile;

LaravelTurnstile::fake([
    'success' => true,
    'hostname' => 'example.com',
    'action' => 'signup',
]);
```

With Laravel's HTTP fake, if you prefer:

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    'challenges.cloudflare.com/*' => Http::response([
        'success' => true,
        'hostname' => 'example.com',
        'action' => 'signup',
    ]),
]);
```

You can also use Cloudflare's test keys in your `.env.testing` file. Remember that they always report the hostname `localhost`.

## Troubleshooting

| What you see | What it means | What to do |
|--------------|---------------|------------|
| `invalid-input-secret` | Cloudflare did not recognise your secret key. | Check `TURNSTILE_SECRET_KEY`. |
| `invalid-input-response` | The token is missing, malformed, expired, or was already used. | Ask the visitor to try again, and reset the widget. |
| `timeout-or-duplicate` | The token has expired or has already been used once. | Reset the widget and ask the visitor to try again. |
| `internal-error` | Cloudflare had a temporary problem. | Try again. If it keeps happening, check Cloudflare's status page. |
| `invalid_token` (package reason) | The token was empty or too long, so the package did not send it to Cloudflare. | Make sure the widget is submitting a token. |
| `hostname_mismatch` (package reason) | The token came from a different website than the ones you allow. | Check `TURNSTILE_HOSTNAMES`. |
| `hostname_not_configured` (package reason) | Hostname checking is required, but no hostnames are configured. | Add `TURNSTILE_HOSTNAMES`, or turn off `TURNSTILE_REQUIRE_HOSTNAMES`. |
| `action_mismatch` (package reason) | The token was created for a different form. | Use the same action on the widget and in the check. |
| `validation_service_error` (package reason) | Cloudflare could not be reached, and the package treated it as a failed check because `TURNSTILE_FAIL_CLOSED` is on. | Check your network and Cloudflare's status page. |

The package reasons listed above are not Cloudflare error codes. They are short explanations that the package adds to help you understand what happened.

## Content Security Policy

If your application uses a Content Security Policy, allow Cloudflare's script and frame:

```text
script-src https://challenges.cloudflare.com;
frame-src https://challenges.cloudflare.com;
```

## Proxies and the visitor's IP address

By default, the package sends the visitor's IP address to Cloudflare. Laravel uses your `TrustProxies` middleware to work out the real address. If your application runs behind a proxy or a load balancer, make sure `TrustProxies` is configured correctly.

If you do not want to share IP addresses with Cloudflare, set:

```env
TURNSTILE_SEND_REMOTE_IP=false
```

## What is coming in v4

The next major release will make the safer behavior the default. Nothing changes yet, but this is what to expect:

- Allowed hostnames will be required, and requests will be rejected when none are configured.
- Requests will be rejected when Cloudflare cannot be reached, unless you explicitly turn that off.
- `validateResponse()` will become the recommended way to check a token.

You can prepare today by setting `TURNSTILE_HOSTNAMES`, and by turning on `TURNSTILE_REQUIRE_HOSTNAMES` and `TURNSTILE_FAIL_CLOSED` when you are comfortable with them.

## Upgrading from v3.0

Nothing is required. v3.1 is a safe upgrade, and existing forms, configuration, and code keep working.

If you want to use the new protections, you can add them when you are ready:

1. Add `TURNSTILE_HOSTNAMES` with your websites.
2. Add `data-action` (or the `action` option) to your widget, and check the same action on the server.
3. Use `validateResponse()` or pass `action` and `hostnames` to `TurnstileCheck` for a stronger check.
4. Run `php artisan turnstile:check` to confirm everything is set up correctly.

See [UPGRADING.md](UPGRADING.md) for a step-by-step guide.

## Upgrading from `coderflex/laravel-turnstile`

This fork uses the `Bkhim\LaravelTurnstile` namespace. To move over:

```bash
composer remove coderflex/laravel-turnstile
composer require bkhim/laravel-turnstile
```

Then replace the imports:

```php
// Before
use Coderflex\LaravelTurnstile\Facades\LaravelTurnstile;
use Coderflex\LaravelTurnstile\Rules\TurnstileCheck;

// After
use Bkhim\LaravelTurnstile\Facades\LaravelTurnstile;
use Bkhim\LaravelTurnstile\Rules\TurnstileCheck;
```

The Blade component (`<x-turnstile-widget />`), config keys, publish tags, and facade alias (`LaravelTurnstile`) are unchanged.

## Testing this package

```bash
composer test
composer analyse
composer format
```

## Contributing

This is a community project and contributions are very welcome, not just code. Bug reports, documentation fixes, feature suggestions, and ideas for improvements are all valuable. Please read [CONTRIBUTING](CONTRIBUTING.md) before opening a pull request.

In short:

1. Fork the repository and create a feature branch.
2. Add tests for any new behavior.
3. Make sure `composer test` and `composer analyse` pass.
4. Open a pull request with a clear description.

We aim to review pull requests promptly and to keep this package maintained for the long haul.

## Security

Please read [our security policy](SECURITY.md) before reporting a security issue. Please do not disclose security vulnerabilities publicly until they have been addressed.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for a list of changes.

## Credits

- [ousid](https://github.com/ousid) — original author of `coderflexx/laravel-turnstile`
- [bkhim](https://github.com/bkhim) — current maintainer
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [the License File](LICENSE.md) for more information.
