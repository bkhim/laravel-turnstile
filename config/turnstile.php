<?php

/*
|--------------------------------------------------------------------------
| Laravel Turnstile (Bkhim fork)
|--------------------------------------------------------------------------
|
| A long-term community-maintained fork of coderflexx/laravel-turnstile.
| Contributions, suggestions, and improvements are always welcome at
| https://github.com/bkhim/laravel-turnstile.
|
*/

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
    | Allowed Hostnames
    |--------------------------------------------------------------------------
    |
    | Here you can list the websites that are allowed to send Turnstile
    | tokens to your application. When this list is not empty, the package
    | checks the hostname that Cloudflare returns and rejects tokens that
    | came from a different website.
    |
    | Use a comma-separated list in your .env file:
    |
    | TURNSTILE_HOSTNAMES=example.com,www.example.com
    |
    | During local development you may need localhost and 127.0.0.1 in this
    | list, especially when you use Cloudflare's test keys. Do not include
    | localhost in a production environment.
    |
    */
    'hostnames' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TURNSTILE_HOSTNAMES', ''))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Expected Action
    |--------------------------------------------------------------------------
    |
    | If you set data-action on the widget, you can ask the package to make
    | sure that the token was created for that same action. This stops a
    | token from one form being used on a different form.
    |
    | Leave this empty if you do not use actions, or pass an action directly
    | to the validation rule.
    |
    */
    'action' => env('TURNSTILE_ACTION'),

    /*
    |--------------------------------------------------------------------------
    | Require Hostnames
    |--------------------------------------------------------------------------
    |
    | When this is turned on, the package will reject tokens if no allowed
    | hostnames are configured. This is the safer choice, but it is turned
    | off by default so that existing applications keep working.
    |
    | The default will change in a future major release.
    |
    */
    'require_hostnames' => (bool) env('TURNSTILE_REQUIRE_HOSTNAMES', false),

    /*
    |--------------------------------------------------------------------------
    | What To Do When Cloudflare Is Unreachable
    |--------------------------------------------------------------------------
    |
    | When this is turned on, a request is rejected if Cloudflare cannot be
    | reached or returns an error. This is the safer choice.
    |
    | When it is off, the package throws the error instead, which is the
    | behaviour of previous versions. The default will change in a future
    | major release.
    |
    */
    'fail_closed' => (bool) env('TURNSTILE_FAIL_CLOSED', false),

    /*
    |--------------------------------------------------------------------------
    | Send The Visitor's IP Address
    |--------------------------------------------------------------------------
    |
    | When this is turned on, the visitor's IP address is sent to Cloudflare
    | along with the token. Cloudflare can use it as an extra signal when it
    | checks the token. Turn it off if you do not want to share IP addresses.
    |
    */
    'send_remote_ip' => (bool) env('TURNSTILE_SEND_REMOTE_IP', true),

    /*
    |--------------------------------------------------------------------------
    | Timeouts
    |--------------------------------------------------------------------------
    |
    | How long the package should wait for Cloudflare, in seconds. The total
    | timeout is 10 seconds by default, which keeps a slow Cloudflare
    | response from holding up your visitors for too long.
    |
    */
    'timeout' => (int) env('TURNSTILE_TIMEOUT', 10),

    'connect_timeout' => (int) env('TURNSTILE_CONNECT_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | Verify URL
    |--------------------------------------------------------------------------
    |
    | The Cloudflare endpoint used to check tokens. You normally do not need
    | to change this, but it is useful in tests or custom environments.
    |
    */
    'verify_url' => env('TURNSTILE_VERIFY_URL', 'https://challenges.cloudflare.com/turnstile/v0/siteverify'),

    /*
    |--------------------------------------------------------------------------
    | Response Field Name
    |--------------------------------------------------------------------------
    |
    | The name of the form field that holds the Turnstile token. Change this
    | only if you also set data-response-field-name on the widget.
    |
    */
    'response_field_name' => env('TURNSTILE_RESPONSE_FIELD', 'cf-turnstile-response'),

    /*
    |--------------------------------------------------------------------------
    | Log Failed Checks
    |--------------------------------------------------------------------------
    |
    | When this is turned on, failed checks are written to your application
    | log. The token and secret key are never logged.
    |
    */
    'log_failures' => (bool) env('TURNSTILE_LOG_FAILURES', false),

    /*
    |--------------------------------------------------------------------------
    | Error Messages
    |--------------------------------------------------------------------------
    |
    | Here you can find the error messages for the application. You can modify
    | or translate the error message as you like.
    |
    | Note that you can translate the error message directly, without wrapping
    | them in translate helper.
    |
    */
    'error_messages' => [
        'turnstile_check_message' => 'The CAPTCHA thinks you are a robot! Please refresh and try again.',
    ],
];
