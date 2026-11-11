# Laravel Turnstile

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bkhim/laravel-turnstile.svg?style=flat-square)](https://packagist.org/packages/bkhim/laravel-turnstile)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/bkhim/laravel-turnstile/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/bkhim/laravel-turnstile/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/bkhim/laravel-turnstile/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/bkhim/laravel-turnstile/actions?query=workflow%3A%22Fix+PHP+code+style+issues%22+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/bkhim/laravel-turnstile.svg?style=flat-square)](https://packagist.org/packages/bkhim/laravel-turnstile)

This package adds [Cloudflare Turnstile](https://developers.cloudflare.com/turnstile/) to a Laravel application. Turnstile is a friendly alternative to a CAPTCHA: most visitors never have to solve a puzzle, and your forms still get protection from bots.

> ### A community-maintained fork
>
> This package is a fork of [coderflexx/laravel-turnstile](https://github.com/coderflexx/laravel-turnstile).
> The original project is no longer actively maintained, so [bkhim](https://github.com/bkhim)
> has taken over stewardship of it.
>
> **We plan to maintain this package indefinitely.** We genuinely welcome bug reports,
> feature requests, suggestions, and pull requests. If there is something you would like
> to see improved, please [open an issue](https://github.com/bkhim/laravel-turnstile/issues)
> or send a pull request. See [Contributing](#17-contributing).

---

## Table of contents

1. [What this package does](#1-what-this-package-does)
2. [Requirements](#2-requirements)
3. [Installation](#3-installation)
4. [Getting your keys from Cloudflare](#4-getting-your-keys-from-cloudflare)
5. [Cloudflare's test keys](#5-cloudflares-test-keys)
6. [Adding the widget to a form](#6-adding-the-widget-to-a-form)
7. [Checking the token on your server](#7-checking-the-token-on-your-server)
8. [A complete form, step by step](#8-a-complete-form-step-by-step)
9. [Checking the website hostname](#9-checking-the-website-hostname)
10. [Checking the form action](#10-checking-the-form-action)
11. [What happens when Cloudflare is unreachable](#11-what-happens-when-cloudflare-is-unreachable)
12. [When the page stays open](#12-when-the-page-stays-open)
13. [Full configuration reference](#13-full-configuration-reference)
14. [Checking your setup](#14-checking-your-setup)
15. [Testing your own application](#15-testing-your-own-application)
16. [Troubleshooting](#16-troubleshooting)
17. [Contributing](#17-contributing)
18. [Content Security Policy](#18-content-security-policy)
19. [Proxies and the visitor's IP address](#19-proxies-and-the-visitors-ip-address)
20. [What is coming in v4](#20-what-is-coming-in-v4)
21. [Upgrading](#21-upgrading)
22. [Security policy](#22-security-policy)
23. [Changelog, credits, and license](#23-changelog-credits-and-license)

---

## 1. What this package does

When you add Turnstile to a form, three things happen:

1. **Cloudflare shows a small widget in the browser.** Most visitors pass it automatically without doing anything.
2. **The widget gives the browser a short-lived token.** It is placed in a hidden form field called `cf-turnstile-response`.
3. **Your server asks Cloudflare whether that token is real.** This is called *siteverify*, and it is the step that actually protects you. A token that was never issued by Cloudflare will be rejected.

This package gives you three things to make that easy in Laravel:

- A Blade component for the widget: `<x-turnstile-widget />`.
- A way to check the token on your server: `LaravelTurnstile::validateResponse()`.
- A validation rule for normal Laravel form validation: `new TurnstileCheck()`.

It can also check that the token came from **your** website (the hostname) and from **the right form** (the action). Those two checks are what stop someone from taking a token from one place and reusing it somewhere else.

---

## 2. Requirements

| What | Supported versions |
|------|--------------------|
| PHP | 8.3, 8.4, 8.5 |
| Laravel | 12.x, 13.x |
| Testbench (only for working on this package) | 10.x for Laravel 12, 11.x for Laravel 13 |

We deliberately support only versions that are still maintained:

- Laravel 12 receives security fixes until **February 24, 2027**.
- Laravel 13 receives security fixes until **March 17, 2028**.
- PHP 8.2 reaches end of life on **December 31, 2026**, which is why the oldest supported version is PHP 8.3.

When a supported version reaches end of life, we drop it in the next major release.

---

## 3. Installation

### Step 1: install the package

```bash
composer require bkhim/laravel-turnstile
```

Laravel will discover the service provider automatically. There is nothing to register by hand.

### Step 2: publish the configuration file

```bash
php artisan vendor:publish --tag="turnstile-config"
```

This creates `config/turnstile.php`. You can edit it whenever you need to change a default.

### Step 3 (optional): publish the views

Only do this if you want to change the HTML of the widget itself:

```bash
php artisan vendor:publish --tag="turnstile-views"
```

The view is copied to `resources/views/vendor/turnstile/components/turnstile-widget.blade.php`.

### Step 4: add your keys

Add these two lines to your `.env` file. The next section explains where to find the values.

```env
TURNSTILE_SITE_KEY=your-site-key
TURNSTILE_SECRET_KEY=your-secret-key
```

Then clear the config cache if you use one:

```bash
php artisan config:clear
```

---

## 4. Getting your keys from Cloudflare

You need two keys. They are not the same, and they are used in different places.

| Key | Where it is used | Is it secret? |
|-----|------------------|---------------|
| **Site key** | In the browser, inside the widget | No, it is public |
| **Secret key** | On your server, when checking the token | **Yes, keep it private** |

Here is how to get them:

1. Sign in to Cloudflare and open the **Turnstile** page in your dashboard:
   <https://dash.cloudflare.com/?to=/:account/turnstile>
2. Click **Add widget** (sometimes labelled **Add site**).
3. Give the widget a name you will recognise, for example `My Laravel App`.
4. Add the hostnames (websites) where the widget will appear. You can change these later.
5. Choose a widget mode. **Managed** is the right choice for most sites.
6. Click **Create**.
7. Cloudflare shows you two values. Copy them both:
   - the **Site Key**, which starts with `0x` or `1x`
   - the **Secret Key**, which is longer
8. Paste them into your `.env` file as shown in the installation step.

> **Important:** the secret key must stay on the server. Never put it in JavaScript, a Blade template, or a public repository. If it leaks, rotate it in the Cloudflare dashboard immediately.

### Adding the right hostnames in Cloudflare

The hostnames you add to the widget are the websites where the widget is allowed to load. You also tell this package which hostnames to trust, using `TURNSTILE_HOSTNAMES` (see [section 9](#9-checking-the-website-hostname)).

For most projects you will want:

- `example.com`
- `www.example.com`
- `localhost` and `127.0.0.1` while developing

Cloudflare lets you manage these under the widget's **Hostname management** settings.

---

## 5. Cloudflare's test keys

Cloudflare publishes test keys that always pass or always fail. They are perfect for local development and for automated tests, because they never show a real challenge.

These are the ones you will use most often:

| Site key | Secret key | What happens |
|----------|------------|--------------|
| `1x00000000000000000000AA` | `1x0000000000000000000000000000000AA` | Always passes |
| `2x00000000000000000000AB` | `2x0000000000000000000000000000000AA` | Always fails |
| `1x00000000000000000000BB` | `1x0000000000000000000000000000000AA` | Invisible widget, always passes |
| `2x00000000000000000000BB` | `2x0000000000000000000000000000000AA` | Invisible widget, always fails |
| `3x00000000000000000000FF` | `1x0000000000000000000000000000000AA` | Forces an interactive challenge, then passes |

There is also a secret key that simulates a token that has already been used:

| Secret key | What happens |
|------------|--------------|
| `3x0000000000000000000000000000000AA` | Always fails with `timeout-or-duplicate` |

Two important things to remember about test keys:

- They work on **any** hostname, including `localhost`. You do not need to add your local hostname to the widget.
- They are **not tied to your domain**, and they do **not** return an action. In our testing, the dummy token reports the hostname `example.com` and no action at all.

That has two practical consequences:

- If you turn on hostname checking locally, either allow the hostname Cloudflare reports or turn hostname checking off while developing.
- Action checking cannot succeed with test keys. If you need to verify that an action is matched, use one of Cloudflare's real keys, or simulate the response in a test with `LaravelTurnstile::fake()`.

The full list is in [Cloudflare's testing guide](https://developers.cloudflare.com/turnstile/reference/testing/).

> **Never use a test key in production.** `php artisan turnstile:check` warns you if you do.

---

## 6. Adding the widget to a form

### The smallest possible example

```blade
<form action="/signup" method="POST">
    @csrf

    <input type="text" name="name" />

    <x-turnstile-widget />

    <button type="submit">Sign up</button>
</form>
```

That is all you need to show the widget. The package adds Cloudflare's script automatically, and only once per page, so you can safely use more than one widget.

### A fuller example

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
        callback="onTurnstileSuccess"
    />

    @error('cf-turnstile-response')
        <p class="error">{{ $message }}</p>
    @enderror

    <button type="submit">Sign up</button>
</form>
```

### Every widget option

All options are optional. If you leave one out, the default is used.

| Option | Default | What it does |
|--------|---------|--------------|
| `id` | none | Gives the widget an HTML id. Useful when you want to reset it later. |
| `theme` | `auto` | `auto`, `light`, or `dark`. `auto` follows the visitor's system setting. |
| `language` | `en-US` | The language of the widget, for example `fr`, `de`, or `pt-BR`. |
| `size` | `normal` | `normal` or `compact`. |
| `action` | none | A short name for the form, such as `signup`. Your server can check it later. See [section 10](#10-checking-the-form-action). |
| `cdata` | none | Extra custom data that Cloudflare returns with the token. It may be up to 255 characters. |
| `appearance` | none | `always`, `execute`, or `interaction-only`. |
| `execution` | none | `render` or `execute`. |
| `responseFieldName` | none | Changes the name of the hidden field that holds the token. If you change this, also set `TURNSTILE_RESPONSE_FIELD`. |
| `callback` | none | The name of a JavaScript function to call when the visitor passes the challenge. |
| `errorCallback` | none | The name of a JavaScript function to call when the challenge fails. |
| `expiredCallback` | none | Called when the token expires. |
| `timeoutCallback` | none | Called when the challenge times out. |
| `unsupportedCallback` | none | Called when the visitor's browser is not supported. |
| `beforeInteractiveCallback` | none | Called before an interactive challenge starts. |
| `afterInteractiveCallback` | none | Called after an interactive challenge finishes. |
| `retry` | none | `auto` or `never`. Controls automatic retries. |
| `refreshExpired` | none | `auto`, `manual`, or `never`. Controls what happens when a token expires. |
| `tabindex` | none | Sets the keyboard tab order. |
| `render` | `implicit` | Use `explicit` when you want to render the widget yourself with JavaScript. |
| `onload` | none | The name of a JavaScript function to call when Cloudflare's script has loaded. |

Any other HTML attribute you add is passed through to the wrapper element, so `class`, `style`, and `data-*` attributes work as usual.

> **A note about explicit rendering:** the Cloudflare script is only included once per page. The first widget on the page decides whether the script uses explicit rendering. If you need to mix rendering styles, include the Turnstile script yourself instead of relying on the component.

---

## 7. Checking the token on your server

The widget alone does nothing. You must ask Cloudflare whether the token is real. This package offers three ways to do that. Pick the one that fits your code.

| If you want to… | Use this | Returns |
|-----------------|----------|---------|
| Use normal Laravel validation with an error message | `new TurnstileCheck()` | nothing (it calls `$fail` on failure) |
| Check the hostname and action as well | `LaravelTurnstile::validateResponse()` | a friendly result object |
| Keep your old code working | `LaravelTurnstile::validate()` | the raw Cloudflare array |

All three call Cloudflare. None of them trust the browser.

### Option A: the validation rule (recommended for forms)

```php
use Bkhim\LaravelTurnstile\Rules\TurnstileCheck;

public function store(Request $request)
{
    $request->validate([
        'name' => ['required', 'string', 'max:250'],
        'cf-turnstile-response' => ['required', new TurnstileCheck()],
    ]);

    // The token is valid, so continue as normal.
}
```

If the check fails, Laravel shows the message from `config/turnstile.php` next to the field.

### Option B: the result object (recommended when you want details)

```php
use Bkhim\LaravelTurnstile\Facades\LaravelTurnstile;

$result = LaravelTurnstile::validateResponse();

if (! $result->successful()) {
    // $result->reason() tells you what went wrong.
    return back()->withErrors([
        'cf-turnstile-response' => 'Please try the challenge again.',
    ]);
}
```

You can read the result in two ways.

With methods:

```php
$result->successful(); // true or false
$result->reason();     // a short explanation when it failed
$result->hostname();   // the website Cloudflare says the token came from
$result->action();     // the action that was set on the widget
$result->errorCodes(); // Cloudflare's error codes, if any
$result->cdata();      // custom data sent from the widget
$result->challengeTimestamp(); // when the challenge was solved
$result->metadata();   // any extra information from Cloudflare
```

Or like an array, which is handy in Blade:

```blade
@if (! $result['success'])
    <p>{{ $result['reason'] }}</p>
@endif
```

The result object is read-only. Trying to change it throws an exception, so you cannot accidentally alter the outcome.

### Option C: the original array method (still supported)

```php
use Bkhim\LaravelTurnstile\Facades\LaravelTurnstile;

$response = LaravelTurnstile::validate();

if (! $response['success']) {
    // The token was not accepted.
}
```

`validate()` works exactly as it did before. It does not check the hostname or action. It also always throws when Cloudflare cannot be reached, because that is how it has always behaved. Use one of the other options if you want the safer behaviour described in [section 11](#11-what-happens-when-cloudflare-is-unreachable).

---

## 8. A complete form, step by step

Here is the whole flow from start to finish. Follow the steps in order.

### Step 1: add the route

In `routes/web.php`:

```php
use App\Http\Controllers\SignupController;

Route::get('/signup', [SignupController::class, 'create'])->name('signup.create');
Route::post('/signup', [SignupController::class, 'store'])->name('signup.store');
```

### Step 2: build the form

In `resources/views/signup.blade.php`:

```blade
<form action="{{ route('signup.store') }}" method="POST">
    @csrf

    <label for="name">Name</label>
    <input id="name" type="text" name="name" value="{{ old('name') }}" />
    @error('name')
        <p class="error">{{ $message }}</p>
    @enderror

    <x-turnstile-widget
        id="signup-turnstile"
        action="signup"
        theme="auto"
    />
    @error('cf-turnstile-response')
        <p class="error">{{ $message }}</p>
    @enderror

    <button type="submit">Create account</button>
</form>
```

### Step 3: check the token in the controller

```php
namespace App\Http\Controllers;

use Bkhim\LaravelTurnstile\Rules\TurnstileCheck;
use Illuminate\Http\Request;

class SignupController extends Controller
{
    public function create()
    {
        return view('signup');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:250'],
            'cf-turnstile-response' => [
                'required',
                new TurnstileCheck(action: 'signup'),
            ],
        ]);

        // Create the account here.

        return redirect()->route('signup.create')->with('status', 'Welcome!');
    }
}
```

### Step 4: tell the package which hostnames to trust

In `.env`:

```env
TURNSTILE_HOSTNAMES=example.com,www.example.com
```

While developing:

```env
TURNSTILE_HOSTNAMES=localhost,127.0.0.1
```

### Step 5: confirm it works

```bash
php artisan turnstile:check
```

Then submit the form in your browser. If it fails, the troubleshooting table in [section 16](#16-troubleshooting) lists every reason and what to do about it.

### Step 6: add a test

```php
use Bkhim\LaravelTurnstile\Facades\LaravelTurnstile;

it('creates an account when the challenge passes', function () {
    LaravelTurnstile::fake(['success' => true, 'hostname' => 'example.com', 'action' => 'signup']);

    $this->post('/signup', [
        'name' => 'Ada Lovelace',
        'cf-turnstile-response' => 'test-token',
    ])->assertRedirect('/signup');

    $this->assertDatabaseHas('users', ['name' => 'Ada Lovelace']);
});
```

More about testing in [section 15](#15-testing-your-own-application).

---

## 9. Checking the website hostname

Cloudflare tells your server which website a token was created on. If you tell this package which websites you trust, it will reject tokens that came from anywhere else.

This protects you when:

- You use one widget on several websites.
- Someone copies a token from a form on another site and posts it to yours.
- You want to make sure a token was not generated on an attacker's page.

### How to turn it on

1. Add the trusted hostnames to your `.env` file:

   ```env
   TURNSTILE_HOSTNAMES=example.com,www.example.com
   ```

2. Nothing else is needed. `validateResponse()` and `TurnstileCheck` read this list automatically.

You can also pass the list directly if you need to be specific:

```php
LaravelTurnstile::validateResponse(null, [
    'hostnames' => ['example.com', 'www.example.com'],
]);
```

```php
new TurnstileCheck(hostnames: ['example.com', 'www.example.com'])
```

### How the comparison works

- The comparison is **exact**. `example.com` and `www.example.com` are different hostnames, so list both if both serve the form.
- Case is ignored, so `Example.com` matches `example.com`.
- A trailing dot is ignored, so `example.com.` matches `example.com`.
- A port is ignored, so `example.com:8080` matches `example.com`.
- Wildcards are not expanded. Cloudflare reports the exact hostname that was used, so list the exact hostnames you expect.

### What to do in each environment

| Environment | `TURNSTILE_HOSTNAMES` |
|-------------|------------------------|
| Local development with real keys | `localhost,127.0.0.1` |
| Local development with test keys | The hostname Cloudflare reports (we have seen `example.com`), or leave the list empty |
| Staging | `staging.example.com` |
| Production | `example.com,www.example.com` |

### Rules to remember

- **Never put `localhost`, `127.0.0.1`, or `0.0.0.0` in a production environment.** `php artisan turnstile:check` warns you if you do.
- If the list is empty, hostname checking is skipped. That keeps older applications working.
- If you want to reject tokens when no hostnames are configured, turn on the stricter setting:

  ```env
  TURNSTILE_REQUIRE_HOSTNAMES=true
  ```

  This is off by default and will become the default in a future major release.

---

## 10. Checking the form action

An action is a short name that tells your server which form a token belongs to. It stops a token from the login form being used on the signup form.

### Step 1: give each form an action

```blade
<x-turnstile-widget action="signup" />
```

```blade
<x-turnstile-widget action="login" />
```

```blade
<x-turnstile-widget action="contact" />
```

### Step 2: check the same action on the server

```php
new TurnstileCheck(action: 'signup')
```

or:

```php
LaravelTurnstile::validateResponse(null, ['action' => 'signup']);
```

### Action naming rules

Cloudflare has strict rules for actions:

- 1 to 32 characters.
- Only letters, numbers, underscores, and hyphens.
- No spaces, dots, or slashes.

The package checks this for you and throws `InvalidActionException` with a clear message if the action is invalid. This is better than silently failing every request.

### A common layout

| Form | Widget action | Server check |
|------|---------------|--------------|
| Sign up | `action="signup"` | `new TurnstileCheck(action: 'signup')` |
| Log in | `action="login"` | `new TurnstileCheck(action: 'login')` |
| Contact | `action="contact"` | `new TurnstileCheck(action: 'contact')` |
| Password reset | `action="password_reset"` | `new TurnstileCheck(action: 'password_reset')` |
| Download | `action="download"` | `new TurnstileCheck(action: 'download')` |

If you use the same action everywhere, the check still works, but it is weaker: a token from one form can be used on another.

---

## 11. What happens when Cloudflare is unreachable

Sometimes Cloudflare cannot be reached, or it returns an error. You can choose what should happen.

### For `validateResponse()` and `TurnstileCheck`

- **By default**, the package throws an error. This matches how earlier versions behaved.
- **If you turn on `TURNSTILE_FAIL_CLOSED=true`**, the check fails instead, and the visitor sees the normal validation message.

```env
TURNSTILE_FAIL_CLOSED=true
```

Failing closed is the safer choice, because a visitor cannot skip the check just because Cloudflare is having a bad day. The downside is that a Cloudflare outage blocks your forms. Pick whichever trade-off suits your application.

### For `validate()`

The original `validate()` method always throws when Cloudflare cannot be reached. That behaviour is kept for backwards compatibility. If you want the newer behaviour, move to `validateResponse()` or the validation rule.

### Starting the switch to v4

The default will change to fail-closed in v4. If you turn it on now, you are already prepared.

---

## 12. When the page stays open

Turnstile tokens are **single-use** and expire after about **five minutes**. A normal form that navigates away after submitting does not need to do anything special. A form that submits with JavaScript and stays on the page does.

### Step 1: give the widget an id

```blade
<x-turnstile-widget id="signup-turnstile" action="signup" />
```

### Step 2: reset it after a failed attempt

```js
const form = document.querySelector('form');

form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const response = await fetch('/signup', {
        method: 'POST',
        body: new FormData(form),
    });

    if (! response.ok) {
        // The token was used or rejected, so ask for a fresh one
        // before the visitor tries again.
        window.turnstile.reset('signup-turnstile');
    }
});
```

`turnstile.reset('signup-turnstile')` clears the old token and starts a new challenge.

### If you render the widget yourself

When you use JavaScript to render the widget, `turnstile.render()` returns an id. Keep that id and pass it to `turnstile.reset()`:

```js
const widgetId = window.turnstile.render('#my-widget', { sitekey: '...' });

// Later:
window.turnstile.reset(widgetId);
```

### Never reuse a token

If you send the same token twice, Cloudflare rejects the second attempt with `timeout-or-duplicate`. That is a feature: it stops replay attacks.

---

## 13. Full configuration reference

Here is every option in `config/turnstile.php`, what it does, and when to change it.

### Keys

| Config key | Environment variable | Default | What it does |
|------------|---------------------|---------|--------------|
| `turnstile_site_key` | `TURNSTILE_SITE_KEY` | none | The public key used by the widget in the browser. |
| `turnstile_secret_key` | `TURNSTILE_SECRET_KEY` | none | The private key used when checking tokens. Never expose it. |

### Website and form checks

| Config key | Environment variable | Default | What it does |
|------------|---------------------|---------|--------------|
| `hostnames` | `TURNSTILE_HOSTNAMES` | empty | Comma-separated list of trusted hostnames. When set, tokens from other hostnames are rejected. |
| `action` | `TURNSTILE_ACTION` | none | The default action to expect. Useful if your app only has one protected form. |
| `require_hostnames` | `TURNSTILE_REQUIRE_HOSTNAMES` | `false` | When on, tokens are rejected if no hostnames are configured. |
| `fail_closed` | `TURNSTILE_FAIL_CLOSED` | `false` | When on, the check fails instead of throwing if Cloudflare is unreachable. |

### Request settings

| Config key | Environment variable | Default | What it does |
|------------|---------------------|---------|--------------|
| `send_remote_ip` | `TURNSTILE_SEND_REMOTE_IP` | `true` | Sends the visitor's IP address to Cloudflare as an extra signal. |
| `timeout` | `TURNSTILE_TIMEOUT` | `10` | Total seconds to wait for Cloudflare. |
| `connect_timeout` | `TURNSTILE_CONNECT_TIMEOUT` | `5` | Seconds to wait for the connection to Cloudflare. |
| `verify_url` | `TURNSTILE_VERIFY_URL` | Cloudflare's endpoint | The address used to check tokens. You normally never change this. It is useful in tests or custom environments. |
| `response_field_name` | `TURNSTILE_RESPONSE_FIELD` | `cf-turnstile-response` | The form field that holds the token. Change it only if you also set `responseFieldName` on the widget. |

### Logging and messages

| Config key | Environment variable | Default | What it does |
|------------|---------------------|---------|--------------|
| `log_failures` | `TURNSTILE_LOG_FAILURES` | `false` | Writes failed checks to your log. Tokens and secrets are never written. |
| `error_messages.turnstile_check_message` | none | "The CAPTCHA thinks you are a robot! Please refresh and try again." | The message shown when validation fails. It goes through Laravel's translator, so you can translate it. |

### A complete `.env` example

```env
# Required
TURNSTILE_SITE_KEY=0x4AAAAAAA...
TURNSTILE_SECRET_KEY=0x4AAAAAAA...

# Strongly recommended
TURNSTILE_HOSTNAMES=example.com,www.example.com
TURNSTILE_FAIL_CLOSED=true

# Optional
TURNSTILE_REQUIRE_HOSTNAMES=false
TURNSTILE_ACTION=
TURNSTILE_SEND_REMOTE_IP=true
TURNSTILE_TIMEOUT=10
TURNSTILE_CONNECT_TIMEOUT=5
TURNSTILE_RESPONSE_FIELD=cf-turnstile-response
TURNSTILE_LOG_FAILURES=false
```

---

## 14. Checking your setup

Run this command in each environment:

```bash
php artisan turnstile:check
```

It prints a short report:

```
Turnstile configuration
  Site key: 0x4AAAAAAA...
  Secret key: set
  Allowed hostnames: example.com, www.example.com
  Action: none
  Require hostnames: no
  Reject when Cloudflare is unreachable: yes

Checking your secret key with Cloudflare...
Your secret key works. Cloudflare accepted it and handled the dummy token as expected.
```

It checks for these common mistakes:

- The secret key is missing.
- Hostname checking is required but no hostnames are configured.
- No hostnames are configured at all, so hostname checking is skipped.
- `localhost` or `127.0.0.1` is configured in production.
- A Cloudflare test key is being used in production.
- Cloudflare rejects the secret key.

It never prints the secret key itself. If anything is wrong, the command exits with a non-zero status, so you can use it in a deployment script:

```bash
php artisan turnstile:check || exit 1
```

---

## 15. Testing your own application

Your test suite should never call Cloudflare. The package gives you a helper for that.

### Faking a successful check

```php
use Bkhim\LaravelTurnstile\Facades\LaravelTurnstile;

LaravelTurnstile::fake(['success' => true]);
```

### Faking a check with hostname and action

```php
LaravelTurnstile::fake([
    'success' => true,
    'hostname' => 'example.com',
    'action' => 'signup',
]);
```

### Faking a failure

```php
LaravelTurnstile::fake([
    'success' => false,
    'error-codes' => ['invalid-input-response'],
]);
```

### Using Laravel's HTTP fake instead

If you prefer to control the HTTP layer yourself:

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

### Using test keys in `.env.testing`

```env
TURNSTILE_SITE_KEY=1x00000000000000000000AA
TURNSTILE_SECRET_KEY=1x0000000000000000000000000000000AA
TURNSTILE_HOSTNAMES=localhost
```

Test keys are not tied to a domain, and they do not return an action. If your tests check hostnames, either allow the hostname Cloudflare reports or fake the response instead.

---

## 16. Troubleshooting

### Cloudflare error codes

These come directly from Cloudflare.

| Code | What it means | What to do |
|------|---------------|------------|
| `missing-input-secret` | The secret key was not sent. | Check that `TURNSTILE_SECRET_KEY` is set. |
| `invalid-input-secret` | Cloudflare does not recognise the secret key. | Check the key in your `.env`. Run `php artisan turnstile:check`. |
| `missing-input-response` | No token was sent. | Make sure the widget is inside the form and that the field is named `cf-turnstile-response`. |
| `invalid-input-response` | The token is malformed, expired, or fake. | Ask the visitor to try again, and reset the widget. |
| `timeout-or-duplicate` | The token expired or was already used. | Reset the widget and ask the visitor to try again. |
| `bad-request` | The request was malformed. | Check your configuration; this usually points to a bug. |
| `internal-error` | Cloudflare had a temporary problem. | Try again. Check Cloudflare's status page if it continues. |

### Package reasons

These are short explanations the package adds so you do not have to read error codes.

| Reason | What it means | What to do |
|--------|---------------|------------|
| `invalid_token` | The token was empty or longer than 2048 characters, so it was never sent to Cloudflare. | Make sure the widget is submitting a token. |
| `invalid_secret` | Cloudflare rejected your secret key. **Every visitor will fail.** | Fix `TURNSTILE_SECRET_KEY` immediately. The package always logs this. |
| `expired_or_duplicate` | The token was used already or has expired. | Reset the widget. |
| `upstream_error` | Cloudflare reported a temporary internal problem. | Retry; check Cloudflare's status page. |
| `rejected` | Cloudflare rejected the token for another reason. | Look at `errorCodes()` for the exact code. |
| `action_mismatch` | The token was created for a different action. | Use the same action on the widget and in the check. |
| `hostname_mismatch` | The token came from a website that is not in your list. | Check `TURNSTILE_HOSTNAMES`. |
| `hostname_not_configured` | Hostname checking is required, but no hostnames are configured. | Add `TURNSTILE_HOSTNAMES`, or turn off `TURNSTILE_REQUIRE_HOSTNAMES`. |
| `validation_service_error` | Cloudflare could not be reached, and `TURNSTILE_FAIL_CLOSED` turned it into a failed check. | Check your network and Cloudflare's status page. |

### Common situations

**The widget does not appear.**
Check that the site key is set and that your Content Security Policy allows Cloudflare (see [section 18](#18-content-security-policy)). Also check the browser console for errors.

**The widget appears but the form always fails.**
Run `php artisan turnstile:check`. If the secret key is wrong, every request fails. If your hostnames do not include the site you are testing on, hostname checking fails.

**It works locally but not in production.**
Production almost certainly has different hostnames. Add them to `TURNSTILE_HOSTNAMES`, and never include `localhost` there.

**The second submission fails.**
The token was already used. See [section 12](#12-when-the-page-stays-open).

**Everything suddenly fails after a deploy.**
Your secret key may have been rotated, or the config cache still holds an old value. Run `php artisan config:clear` and `php artisan turnstile:check`.

---

## 17. Contributing

This is a community project and contributions are very welcome, not just code. Bug reports, documentation fixes, feature suggestions, and ideas for improvements are all valuable.

Please read [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) before opening a pull request.

In short:

1. Fork the repository and create a feature branch.
2. Add tests for any new behaviour.
3. Make sure `composer test` and `composer analyse` pass.
4. Run `composer format` to match the code style.
5. Open a pull request with a clear description.

We aim to review pull requests promptly and to keep this package maintained for the long haul.

## 18. Content Security Policy

If your application uses a Content Security Policy, allow Cloudflare's script and frame:

```text
script-src https://challenges.cloudflare.com;
frame-src https://challenges.cloudflare.com;
```

## 19. Proxies and the visitor's IP address

By default, the package sends the visitor's IP address to Cloudflare. Laravel uses your `TrustProxies` middleware to work out the real address. If your application runs behind a proxy or a load balancer, make sure `TrustProxies` is configured correctly.

If you do not want to share IP addresses with Cloudflare:

```env
TURNSTILE_SEND_REMOTE_IP=false
```

## 20. What is coming in v4

The next major release will make the safer behaviour the default:

- Allowed hostnames will be required.
- Requests will be rejected when Cloudflare is unreachable.
- `validateResponse()` will become the recommended way to check a token.

You can prepare today by setting `TURNSTILE_HOSTNAMES`, and by turning on `TURNSTILE_REQUIRE_HOSTNAMES` and `TURNSTILE_FAIL_CLOSED` when you are comfortable with them.

## 21. Upgrading

- From v3.0 to v3.1 or v3.1.x: nothing is required.
- From `coderflex/laravel-turnstile`: the only change is the namespace, `Bkhim\LaravelTurnstile`.

See [UPGRADING.md](UPGRADING.md) for the full guide.

## 22. Security policy

Please read [our security policy](SECURITY.md) before reporting a security issue. Please do not disclose security vulnerabilities publicly until they have been addressed.

## 23. Changelog, credits, and license

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for a list of changes.

### Credits

- [ousid](https://github.com/ousid) — original author of `coderflexx/laravel-turnstile`
- [bkhim](https://github.com/bkhim) — current maintainer
- [All Contributors](../../contributors)

### License

The MIT License (MIT). Please see the [License File](LICENSE.md) for more information.
