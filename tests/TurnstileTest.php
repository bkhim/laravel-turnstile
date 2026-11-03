<?php

use Bkhim\LaravelTurnstile\Exceptions\SecretKeyNotFoundException;
use Bkhim\LaravelTurnstile\Exceptions\UnknownErrorOccurredException;
use Bkhim\LaravelTurnstile\Facades\LaravelTurnstile;
use Bkhim\LaravelTurnstile\Rules\TurnstileCheck;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

const TURNSTILE_VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

it('renders the turnstile widget', function () {
    Route::get('/turnstile', fn () => view('turnstile::components.turnstile-widget'));

    $this->get('/turnstile')
        ->assertOk()
        ->assertSee('cf-turnstile', false)
        ->assertSee('data-sitekey="1x00000000000000000000AA"', false)
        ->assertSee('data-theme="auto"', false)
        ->assertSee('data-language="en-US"', false)
        ->assertSee('data-size="normal"', false)
        ->assertSee('https://challenges.cloudflare.com/turnstile/v0/api.js', false);
});

it('passes custom options through to the widget', function () {
    Route::get('/turnstile', fn () => view('turnstile::components.turnstile-widget', [
        'theme' => 'dark',
        'language' => 'fr',
        'size' => 'compact',
        'callback' => 'onSuccess',
        'errorCallback' => 'onError',
        'action' => 'login',
        'appearance' => 'always',
        'execution' => 'execute',
        'responseFieldName' => 'turnstile-token',
    ]));

    $this->get('/turnstile')
        ->assertOk()
        ->assertSee('data-theme="dark"', false)
        ->assertSee('data-language="fr"', false)
        ->assertSee('data-size="compact"', false)
        ->assertSee('data-callback="onSuccess"', false)
        ->assertSee('data-error-callback="onError"', false)
        ->assertSee('data-action="login"', false)
        ->assertSee('data-appearance="always"', false)
        ->assertSee('data-execution="execute"', false)
        ->assertSee('data-response-field-name="turnstile-token"', false);
});

it('includes the cloudflare script only once per page', function () {
    $html = Blade::render('<x-turnstile-widget /><x-turnstile-widget />');

    expect(substr_count($html, 'turnstile/v0/api.js'))->toBe(1);
});

it('validates a turnstile response successfully', function () {
    Http::fake([
        TURNSTILE_VERIFY_URL => Http::response(['success' => true, 'hostname' => 'example.com']),
    ]);

    $response = LaravelTurnstile::validate('valid-token');

    expect($response)->toMatchArray(['success' => true]);

    Http::assertSent(fn ($request) => $request->url() === TURNSTILE_VERIFY_URL
        && $request['secret'] === '1x0000000000000000000000000000000AA'
        && $request['response'] === 'valid-token');
});

it('returns a failure response for an invalid token', function () {
    Http::fake([
        TURNSTILE_VERIFY_URL => Http::response([
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ]),
    ]);

    expect(LaravelTurnstile::validate('invalid-token'))
        ->toMatchArray(['success' => false]);
});

it('throws when the secret key is missing', function () {
    Config::set('turnstile.turnstile_secret_key', null);

    LaravelTurnstile::validate('token');
})->throws(SecretKeyNotFoundException::class);

it('throws when cloudflare returns an error', function () {
    Http::fake([
        TURNSTILE_VERIFY_URL => Http::response('Server Error', 500),
    ]);

    LaravelTurnstile::validate('token');
})->throws(UnknownErrorOccurredException::class);

it('passes the validation rule for a valid token', function () {
    Http::fake([
        TURNSTILE_VERIFY_URL => Http::response(['success' => true]),
    ]);

    $failed = false;

    (new TurnstileCheck)->validate('cf-turnstile-response', 'valid-token', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

it('fails the validation rule for an empty token', function () {
    $message = null;

    (new TurnstileCheck)->validate('cf-turnstile-response', '', function (string $failMessage) use (&$message) {
        $message = $failMessage;
    });

    expect($message)->toBe('The CAPTCHA thinks you are a robot! Please refresh and try again.');
});

it('fails the validation rule for an invalid token', function () {
    Http::fake([
        TURNSTILE_VERIFY_URL => Http::response(['success' => false]),
    ]);

    $message = null;

    (new TurnstileCheck)->validate('cf-turnstile-response', 'invalid-token', function (string $failMessage) use (&$message) {
        $message = $failMessage;
    });

    expect($message)->toBe('The CAPTCHA thinks you are a robot! Please refresh and try again.');
});
