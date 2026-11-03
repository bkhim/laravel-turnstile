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
