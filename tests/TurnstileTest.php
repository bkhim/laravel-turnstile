<?php

use Bkhim\LaravelTurnstile\Exceptions\InvalidActionException;
use Bkhim\LaravelTurnstile\Exceptions\SecretKeyNotFoundException;
use Bkhim\LaravelTurnstile\Exceptions\UnknownErrorOccurredException;
use Bkhim\LaravelTurnstile\Facades\LaravelTurnstile;
use Bkhim\LaravelTurnstile\Rules\TurnstileCheck;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

const TURNSTILE_VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

function fakeTurnstile(array $payload = [], int $status = 200): void
{
    Http::fake([
        TURNSTILE_VERIFY_URL => Http::response(array_merge(['success' => true], $payload), $status),
    ]);
}

it('renders the turnstile widget', function () {
    Route::get('/turnstile', fn () => view('turnstile::components.turnstile-widget'));

    $this->get('/turnstile')
        ->assertOk()
        ->assertSee('cf-turnstile', false)
        ->assertSee('data-sitekey="1x00000000000000000000AA"', false)
        ->assertSee('data-theme="auto"', false)
        ->assertSee('data-language="en-US"', false)
        ->assertSee('data-size="normal"', false)
        ->assertSee('async defer', false)
        ->assertSee('https://challenges.cloudflare.com/turnstile/v0/api.js', false);
});

it('passes custom options through to the widget', function () {
    Route::get('/turnstile', fn () => view('turnstile::components.turnstile-widget', [
        'id' => 'my-turnstile',
        'theme' => 'dark',
        'language' => 'fr',
        'size' => 'compact',
        'callback' => 'onSuccess',
        'errorCallback' => 'onError',
        'expiredCallback' => 'onExpired',
        'timeoutCallback' => 'onTimeout',
        'unsupportedCallback' => 'onUnsupported',
        'beforeInteractiveCallback' => 'onBeforeInteractive',
        'afterInteractiveCallback' => 'onAfterInteractive',
        'action' => 'signup',
        'cdata' => 'custom-data',
        'appearance' => 'always',
        'execution' => 'execute',
        'responseFieldName' => 'turnstile-token',
        'retry' => 'never',
        'refreshExpired' => 'manual',
        'tabindex' => 3,
    ]));

    $this->get('/turnstile')
        ->assertOk()
        ->assertSee('id="my-turnstile"', false)
        ->assertSee('data-theme="dark"', false)
        ->assertSee('data-language="fr"', false)
        ->assertSee('data-size="compact"', false)
        ->assertSee('data-callback="onSuccess"', false)
        ->assertSee('data-error-callback="onError"', false)
        ->assertSee('data-expired-callback="onExpired"', false)
        ->assertSee('data-timeout-callback="onTimeout"', false)
        ->assertSee('data-unsupported-callback="onUnsupported"', false)
        ->assertSee('data-before-interactive-callback="onBeforeInteractive"', false)
        ->assertSee('data-after-interactive-callback="onAfterInteractive"', false)
        ->assertSee('data-action="signup"', false)
        ->assertSee('data-cdata="custom-data"', false)
        ->assertSee('data-appearance="always"', false)
        ->assertSee('data-execution="execute"', false)
        ->assertSee('data-response-field-name="turnstile-token"', false)
        ->assertSee('data-retry="never"', false)
        ->assertSee('data-refresh-expired="manual"', false)
        ->assertSee('data-tabindex="3"', false);
});

it('can use explicit rendering', function () {
    $html = Blade::render('<x-turnstile-widget render="explicit" onload="onTurnstileLoad" />');

    expect($html)
        ->toContain('turnstile/v0/api.js?render=explicit')
        ->toContain('onload=onTurnstileLoad');
});

it('includes the cloudflare script only once per page', function () {
    $html = Blade::render('<x-turnstile-widget /><x-turnstile-widget />');

    expect(substr_count($html, 'turnstile/v0/api.js'))->toBe(1);
});

it('validates a turnstile response successfully', function () {
    fakeTurnstile(['hostname' => 'example.com', 'action' => 'signup']);

    $response = LaravelTurnstile::validate('valid-token');

    expect($response)->toMatchArray(['success' => true]);

    Http::assertSent(fn ($request) => $request->url() === TURNSTILE_VERIFY_URL
        && $request['secret'] === '1x0000000000000000000000000000000AA'
        && $request['response'] === 'valid-token'
        && $request['remoteip'] === '127.0.0.1');
});

it('returns a failure response for an invalid token', function () {
    fakeTurnstile(['success' => false, 'error-codes' => ['invalid-input-response']]);

    expect(LaravelTurnstile::validate('invalid-token'))
        ->toMatchArray(['success' => false]);
});

it('rejects an empty token before calling cloudflare', function () {
    $response = LaravelTurnstile::validate('');

    expect($response['success'])->toBeFalse();
    Http::assertNothingSent();
});

it('rejects an extremely long token before calling cloudflare', function () {
    $response = LaravelTurnstile::validate(str_repeat('a', 2049));

    expect($response['success'])->toBeFalse();
    Http::assertNothingSent();
});

it('throws when the secret key is missing', function () {
    Config::set('turnstile.turnstile_secret_key', null);

    LaravelTurnstile::validate('token');
})->throws(SecretKeyNotFoundException::class);

it('throws when cloudflare returns an error', function () {
    Http::fake([TURNSTILE_VERIFY_URL => Http::response('Server Error', 500)]);

    LaravelTurnstile::validate('token');
})->throws(UnknownErrorOccurredException::class);

it('returns a friendly result object for a valid token', function () {
    fakeTurnstile(['hostname' => 'example.com', 'action' => 'signup']);

    $result = LaravelTurnstile::validateResponse('token');

    expect($result->successful())->toBeTrue()
        ->and($result->hostname())->toBe('example.com')
        ->and($result->action())->toBe('signup')
        ->and($result['success'])->toBeTrue();
});

it('checks the hostname when one is configured', function () {
    fakeTurnstile(['hostname' => 'example.com']);

    expect(LaravelTurnstile::validateResponse('token', [
        'hostnames' => ['example.com'],
    ])->successful())->toBeTrue();

    $result = LaravelTurnstile::validateResponse('token', [
        'hostnames' => ['other.com'],
    ]);

    expect($result->successful())->toBeFalse()
        ->and($result->reason())->toBe('hostname_mismatch');
});

it('uses the hostnames from config', function () {
    Config::set('turnstile.hostnames', ['example.com']);
    fakeTurnstile(['hostname' => 'example.com']);

    expect(LaravelTurnstile::validateResponse('token')->successful())->toBeTrue();
});

it('rejects a token when hostnames are required but none are configured', function () {
    Config::set('turnstile.hostnames', []);
    fakeTurnstile();

    $result = LaravelTurnstile::validateResponse('token', ['require_hostnames' => true]);

    expect($result->successful())->toBeFalse()
        ->and($result->reason())->toBe('hostname_not_configured');
});

it('checks the action when one is configured', function () {
    fakeTurnstile(['action' => 'signup']);

    expect(LaravelTurnstile::validateResponse('token', ['action' => 'signup'])->successful())->toBeTrue();

    $result = LaravelTurnstile::validateResponse('token', ['action' => 'login']);

    expect($result->successful())->toBeFalse()
        ->and($result->reason())->toBe('action_mismatch');
});

it('sends the visitor ip address by default', function () {
    fakeTurnstile();

    LaravelTurnstile::validateResponse('token');

    Http::assertSent(fn ($request) => $request['remoteip'] === '127.0.0.1');
});

it('can leave the visitor ip address out', function () {
    Config::set('turnstile.send_remote_ip', false);
    fakeTurnstile();

    LaravelTurnstile::validateResponse('token');

    Http::assertSent(fn ($request) => ! isset($request->data()['remoteip']));
});

it('sends an idempotency key when one is provided', function () {
    fakeTurnstile();

    LaravelTurnstile::validateResponse('token', ['idempotency_key' => 'abc-123']);

    Http::assertSent(fn ($request) => $request['idempotency_key'] === 'abc-123');
});

it('returns a failed result instead of throwing when fail closed is on', function () {
    Http::fake([TURNSTILE_VERIFY_URL => Http::response('Server Error', 500)]);

    $result = LaravelTurnstile::validateResponse('token', ['fail_closed' => true]);

    expect($result->successful())->toBeFalse()
        ->and($result->reason())->toBe('validation_service_error');
});

it('throws on a cloudflare error by default', function () {
    Http::fake([TURNSTILE_VERIFY_URL => Http::response('Server Error', 500)]);

    LaravelTurnstile::validateResponse('token');
})->throws(UnknownErrorOccurredException::class);

it('lets you read the result like an array and as json', function () {
    fakeTurnstile(['hostname' => 'example.com', 'action' => 'signup']);

    $result = LaravelTurnstile::validateResponse('token');

    expect($result['success'])->toBeTrue()
        ->and($result['hostname'])->toBe('example.com')
        ->and($result->toArray())->toMatchArray(['success' => true]);
});

it('reports why a check failed', function () {
    fakeTurnstile(['success' => false, 'error-codes' => ['timeout-or-duplicate']]);

    $result = LaravelTurnstile::validateResponse('token');

    expect($result->successful())->toBeFalse()
        ->and($result->reason())->toBe('expired_or_duplicate')
        ->and($result->errorCodes())->toBe(['timeout-or-duplicate']);
});

it('passes the validation rule for a valid token', function () {
    fakeTurnstile();

    $failed = false;

    (new TurnstileCheck)->validate('cf-turnstile-response', 'valid-token', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

it('fails the validation rule for an invalid token', function () {
    fakeTurnstile(['success' => false, 'error-codes' => ['invalid-input-response']]);

    $message = null;

    (new TurnstileCheck)->validate('cf-turnstile-response', 'invalid-token', function (string $failMessage) use (&$message) {
        $message = $failMessage;
    });

    expect($message)->toBe('The CAPTCHA thinks you are a robot! Please refresh and try again.');
});

it('fails the validation rule for an empty token', function () {
    $message = null;

    (new TurnstileCheck)->validate('cf-turnstile-response', '', function (string $failMessage) use (&$message) {
        $message = $failMessage;
    });

    expect($message)->toBe('The CAPTCHA thinks you are a robot! Please refresh and try again.');
});

it('can check the action and hostname with the validation rule', function () {
    fakeTurnstile(['hostname' => 'example.com', 'action' => 'signup']);

    $failed = false;

    (new TurnstileCheck(action: 'signup', hostnames: ['example.com']))
        ->validate('cf-turnstile-response', 'valid-token', function () use (&$failed) {
            $failed = true;
        });

    expect($failed)->toBeFalse();
});

it('fails the validation rule when the hostname does not match', function () {
    fakeTurnstile(['hostname' => 'other.com']);

    $message = null;

    (new TurnstileCheck(hostnames: ['example.com']))
        ->validate('cf-turnstile-response', 'valid-token', function (string $failMessage) use (&$message) {
            $message = $failMessage;
        });

    expect($message)->toBe('The CAPTCHA thinks you are a robot! Please refresh and try again.');
});

it('fails the validation rule instead of throwing when fail closed is on', function () {
    Http::fake([TURNSTILE_VERIFY_URL => Http::response('Server Error', 500)]);

    $message = null;

    (new TurnstileCheck(failClosed: true))
        ->validate('cf-turnstile-response', 'valid-token', function (string $failMessage) use (&$message) {
            $message = $failMessage;
        });

    expect($message)->toBe('The CAPTCHA thinks you are a robot! Please refresh and try again.');
});

it('throws from the validation rule on a cloudflare error by default', function () {
    Http::fake([TURNSTILE_VERIFY_URL => Http::response('Server Error', 500)]);

    (new TurnstileCheck)->validate('cf-turnstile-response', 'valid-token', fn () => null);
})->throws(UnknownErrorOccurredException::class);

it('confirms a valid secret with the check command', function () {
    Http::fake([TURNSTILE_VERIFY_URL => Http::response([
        'success' => false,
        'error-codes' => ['invalid-input-response'],
    ])]);

    $this->artisan('turnstile:check')->assertExitCode(0);
});

it('fails the check command when the secret is rejected', function () {
    Http::fake([TURNSTILE_VERIFY_URL => Http::response([
        'success' => false,
        'error-codes' => ['invalid-input-secret'],
    ])]);

    $this->artisan('turnstile:check')->assertExitCode(1);
});

it('fails the check command when the secret is missing', function () {
    Config::set('turnstile.turnstile_secret_key', null);

    $this->artisan('turnstile:check')->assertExitCode(1);
});

it('accepts a cloudflare test key in the check command', function () {
    Http::fake([TURNSTILE_VERIFY_URL => Http::response([
        'success' => true,
        'hostname' => 'localhost',
    ])]);

    $this->artisan('turnstile:check')->assertExitCode(0);
});

it('uses the fail closed setting from config', function () {
    Config::set('turnstile.fail_closed', true);
    Http::fake([TURNSTILE_VERIFY_URL => Http::response('Server Error', 500)]);

    $result = LaravelTurnstile::validateResponse('token');

    expect($result->successful())->toBeFalse()
        ->and($result->reason())->toBe('validation_service_error');
});

it('can fake cloudflare checks with the fake helper', function () {
    LaravelTurnstile::fake(['success' => true, 'hostname' => 'example.com']);

    expect(LaravelTurnstile::validateResponse('token', ['hostnames' => ['example.com']])->successful())->toBeTrue();
});

it('maps cloudflare error codes to readable reasons', function (array $payload, string $reason) {
    fakeTurnstile($payload);

    expect(LaravelTurnstile::validateResponse('token')->reason())->toBe($reason);
})->with([
    'invalid secret' => [['success' => false, 'error-codes' => ['invalid-input-secret']], 'invalid_secret'],
    'missing secret' => [['success' => false, 'error-codes' => ['missing-input-secret']], 'invalid_secret'],
    'invalid token' => [['success' => false, 'error-codes' => ['invalid-input-response']], 'invalid_token'],
    'missing token' => [['success' => false, 'error-codes' => ['missing-input-response']], 'invalid_token'],
    'replayed token' => [['success' => false, 'error-codes' => ['timeout-or-duplicate']], 'expired_or_duplicate'],
    'upstream problem' => [['success' => false, 'error-codes' => ['internal-error']], 'upstream_error'],
    'unknown problem' => [['success' => false, 'error-codes' => ['bad-request']], 'rejected'],
]);

it('compares hostnames without case or a trailing dot', function () {
    fakeTurnstile(['hostname' => 'Example.COM.']);

    expect(LaravelTurnstile::validateResponse('token', ['hostnames' => ['example.com']])->successful())->toBeTrue();
});

it('ignores a port on a configured hostname', function () {
    fakeTurnstile(['hostname' => 'example.com']);

    expect(LaravelTurnstile::validateResponse('token', ['hostnames' => ['example.com:8080']])->successful())->toBeTrue();
});

it('accepts a comma separated hostname string', function () {
    fakeTurnstile(['hostname' => 'www.example.com']);

    expect(LaravelTurnstile::validateResponse('token', ['hostnames' => 'example.com, www.example.com'])->successful())->toBeTrue();
});

it('does not check hostnames in the original validate method', function () {
    Config::set('turnstile.hostnames', ['other.com']);
    fakeTurnstile(['hostname' => 'example.com']);

    expect(LaravelTurnstile::validate('token')['success'])->toBeTrue();
});

it('rejects an invalid action name', function () {
    fakeTurnstile(['action' => 'signup']);

    LaravelTurnstile::validateResponse('token', ['action' => 'sign up']);
})->throws(InvalidActionException::class);

it('accepts a valid action name', function () {
    fakeTurnstile(['action' => 'signup_form-1']);

    expect(LaravelTurnstile::validateResponse('token', ['action' => 'signup_form-1'])->successful())->toBeTrue();
});

it('retries a rate limited request once when an idempotency key is provided', function () {
    Http::fake([
        TURNSTILE_VERIFY_URL => Http::sequence()
            ->push(['success' => false, 'error-codes' => ['internal-error']], 429)
            ->push(['success' => true], 200),
    ]);

    $result = LaravelTurnstile::validateResponse('token', ['idempotency_key' => 'abc-123']);

    expect($result->successful())->toBeTrue();
    Http::assertSentCount(2);
});

it('does not retry a rate limited request without an idempotency key', function () {
    Http::fake([TURNSTILE_VERIFY_URL => Http::response('Too Many Requests', 429)]);

    LaravelTurnstile::validateResponse('token');
})->throws(UnknownErrorOccurredException::class);

it('always logs a rejected secret key, even when failure logging is off', function () {
    Config::set('turnstile.log_failures', false);
    fakeTurnstile(['success' => false, 'error-codes' => ['invalid-input-secret']]);

    Log::spy();

    (new TurnstileCheck)->validate('cf-turnstile-response', 'token', fn () => null);

    Log::shouldHaveReceived('error')->once();
});

it('does not log ordinary failures when failure logging is off', function () {
    Config::set('turnstile.log_failures', false);
    fakeTurnstile(['success' => false, 'error-codes' => ['invalid-input-response']]);

    Log::spy();

    (new TurnstileCheck)->validate('cf-turnstile-response', 'token', fn () => null);

    Log::shouldNotHaveReceived('warning');
});

it('logs ordinary failures when failure logging is on', function () {
    Config::set('turnstile.log_failures', true);
    fakeTurnstile(['success' => false, 'error-codes' => ['invalid-input-response']]);

    Log::spy();

    (new TurnstileCheck)->validate('cf-turnstile-response', 'token', fn () => null);

    Log::shouldHaveReceived('warning')->once();
});
