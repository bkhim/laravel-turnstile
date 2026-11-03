# Laravel Turnstile

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bkhim/laravel-turnstile.svg?style=flat-square)](https://packagist.org/packages/bkhim/laravel-turnstile)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/bkhim/laravel-turnstile/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/bkhim/laravel-turnstile/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/bkhim/laravel-turnstile/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/bkhim/laravel-turnstile/actions?query=workflow%3A%22Fix+PHP+code+style+issues%22+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/bkhim/laravel-turnstile.svg?style=flat-square)](https://packagist.org/packages/bkhim/laravel-turnstile)

__Laravel Turnstile__ helps you implement [Cloudflare Turnstile](https://developers.cloudflare.com/turnstile/) — a CAPTCHA alternative — in your Laravel application, quickly and cleanly.

> ### A community-maintained fork
>
> This package is a fork of [coderflexx/laravel-turnstile](https://github.com/coderflexx/laravel-turnstile).
> The original project is no longer actively maintained, so [bkhim](https://github.com/bkhim)
> has taken over stewardship of it.
>
> **We plan to maintain this package indefinitely.** We genuinely welcome bug reports,
> feature requests, suggestions, and pull requests from the community. If there is
> something you would like to see improved, please [open an issue](https://github.com/bkhim/laravel-turnstile/issues)
> or send a pull request — see [Contributing](#contributing).

## Requirements

We deliberately support only currently maintained versions. End-of-life (EOL) versions
are **not** supported, so the package never locks you into an unsupported runtime.

| Dependency | Supported versions                            |
|------------|-----------------------------------------------|
| PHP        | 8.3, 8.4, 8.5                                 |
| Laravel    | 12.x, 13.x                                    |
| Testbench  | 10.x (Laravel 12), 11.x (Laravel 13)          |

Support policy:

- **Laravel 12** receives security fixes until **February 24, 2027**.
- **Laravel 13** is the current release and receives security fixes until **March 17, 2028**.
- **PHP 8.2** reaches end of life on **December 31, 2026**, which is why the minimum is PHP 8.3.

When a supported version reaches end of life, we will drop it in the next major release.

## Installation

Install the package via Composer:

```bash
composer require bkhim/laravel-turnstile
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="turnstile-config"
```

This is the contents of the published config file:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Turnstile Keys
    |--------------------------------------------------------------------------
    |
    | This value is the site and the secret key of your application. After
    | creating an application with Cloudflare Turnstile, copy the site key
    | and use it here, or in the .env file.
    |
    | Note that the secret key should not be publicly accessible.
    |
    | @see: https://developers.cloudflare.com/turnstile/get-started/#get-a-sitekey-and-secret-key
    |
    */
    'turnstile_site_key' => env('TURNSTILE_SITE_KEY'),

    'turnstile_secret_key' => env('TURNSTILE_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Error Messages
    |--------------------------------------------------------------------------
    |
    | Here you can find the error messages for the application. You can modify
    | or translate the error message as you like.
    |
    */
    'error_messages' => [
        'turnstile_check_message' => 'The CAPTCHA thinks you are a robot! Please refresh and try again.',
    ],
];
```

Optionally, you can publish the views:

```bash
php artisan vendor:publish --tag="turnstile-views"
```

## Turnstile Keys

To use __Cloudflare Turnstile__, you need a `SiteKey` and a `SecretKey` from your
[Cloudflare dashboard](https://developers.cloudflare.com/turnstile/get-started/#get-a-sitekey-and-secret-key).

After generating the keys, add them to your `.env` file:

```env
TURNSTILE_SITE_KEY=2x00000000000000000000AB
TURNSTILE_SECRET_KEY=2x0000000000000000000000000000000AA
```

If you want to test the widget, you can use the
[Dummy site keys and secret keys](https://developers.cloudflare.com/turnstile/reference/testing/)
that Cloudflare provides.

## Usage

### Turnstile Widget Component

Use the widget in your form like this:

```blade
<x-turnstile-widget
    theme="dark"
    language="en-US"
    size="normal"
    callback="callbackFunction"
    errorCallback="errorCallbackFunction"
/>
```

The component supports the following options (all are optional):

| Attribute           | Default   | Description                                                        |
|---------------------|-----------|--------------------------------------------------------------------|
| `theme`             | `auto`    | `auto`, `light`, or `dark`                                         |
| `language`          | `en-US`   | The widget language                                                |
| `size`              | `normal`  | `normal` or `compact`                                              |
| `callback`          | `null`    | JavaScript callback invoked on success                             |
| `errorCallback`     | `null`    | JavaScript callback invoked on error                               |
| `action`            | `null`    | A custom action name for the challenge                             |
| `appearance`        | `null`    | `always`, `execute`, or `interaction-only`                         |
| `execution`         | `null`    | `render` or `execute`                                              |
| `responseFieldName` | `null`    | Overrides the name of the hidden response field                    |

You can learn more about these options in the
[Cloudflare configuration docs](https://developers.cloudflare.com/turnstile/get-started/client-side-rendering/#configurations).

### Turnstile Backend Validation

Once the widget is on the page, validate the Cloudflare response with the `validate` method:

```php
use Bkhim\LaravelTurnstile\Facades\LaravelTurnstile;

public function store(Request $request)
{
    // optionally validate your form first

    $response = LaravelTurnstile::validate();

    if (! $response['success']) { // boolean
        // handle a failed challenge
    }
}
```

You may also pass the Cloudflare response explicitly:

```php
public function store(Request $request)
{
    $response = LaravelTurnstile::validate(
        $request->get('cf-turnstile-response') // created by the Cloudflare widget
    );
}
```

### Turnstile Custom Rule

For clean validation, use the `TurnstileCheck` rule alongside your form validation:

```php
use Bkhim\LaravelTurnstile\Rules\TurnstileCheck;

public function store(Request $request)
{
    $request->validate([
        'cf-turnstile-response' => [new TurnstileCheck()],
    ]);
}
```

The rule uses the same backend logic, but returns a validation message when the
challenge fails. You can change the message in `config/turnstile.php`:

```php
return [
    // ...
    'error_messages' => [
        'turnstile_check_message' => 'The CAPTCHA thinks you are a robot! Please refresh and try again.',
    ],
];
```

> **Note:** The message is passed through Laravel's translator, so you can translate it
> by publishing and editing the config file.

## Real Life Example

In your Blade file:

```blade
<form action="" method="post">
    @csrf

    <div>
        <input type="text" name="name" />
        @error('name')
            <p class="error">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <x-turnstile-widget theme="auto" language="fr" />
        @error('cf-turnstile-response')
            <p class="error">{{ $message }}</p>
        @enderror
    </div>

    <button>Submit</button>
</form>
```

In your controller:

```php
use Bkhim\LaravelTurnstile\Facades\LaravelTurnstile;
use Bkhim\LaravelTurnstile\Rules\TurnstileCheck;

public function store(Request $request)
{
    $request->validate([
        'name' => ['required', 'string', 'max:250'],
        'cf-turnstile-response' => ['required', new TurnstileCheck()],
    ]);

    // or
    $response = LaravelTurnstile::validate();

    if (! $response['success']) {
        // handle a failed challenge
    }

    // do your thing
}
```

## Upgrading from `coderflex/laravel-turnstile`

This fork uses the `Bkhim\LaravelTurnstile` namespace. To migrate:

```bash
composer remove coderflex/laravel-turnstile
composer require bkhim/laravel-turnstile
```

Then replace imports such as:

```php
// Before
use Coderflex\LaravelTurnstile\Facades\LaravelTurnstile;
use Coderflex\LaravelTurnstile\Rules\TurnstileCheck;

// After
use Bkhim\LaravelTurnstile\Facades\LaravelTurnstile;
use Bkhim\LaravelTurnstile\Rules\TurnstileCheck;
```

The Blade component (`<x-turnstile-widget />`), config keys, publish tags, and facade
alias (`LaravelTurnstile`) are unchanged.

## Testing

```bash
composer test
```

Run static analysis and formatting checks with:

```bash
composer analyse
composer format
```

## Contributing

This is a community project and contributions are very welcome — not just code.
Bug reports, documentation fixes, feature suggestions, and ideas for improvements are
all valuable. Please read [CONTRIBUTING](CONTRIBUTING.md) before opening a pull request.

In short:

1. Fork the repository and create a feature branch.
2. Add tests for any new behaviour.
3. Make sure `composer test` and `composer analyse` pass.
4. Open a pull request with a clear description.

We aim to review pull requests promptly and to keep this package maintained for the long haul.

## Security Vulnerabilities

Please review [our security policy](SECURITY.md) before reporting a security issue.
Please do not disclose security vulnerabilities publicly until they have been addressed.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [ousid](https://github.com/ousid) — original author of `coderflexx/laravel-turnstile`
- [bkhim](https://github.com/bkhim) — current maintainer
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see the [License File](LICENSE.md) for more information.
