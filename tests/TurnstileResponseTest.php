<?php

use Bkhim\LaravelTurnstile\Facades\LaravelTurnstile;
use Illuminate\Support\Facades\Http;

const RESPONSE_VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

function fakeResponse(array $payload): void
{
    Http::fake([
        RESPONSE_VERIFY_URL => Http::response($payload),
    ]);
}

it('exposes the cloudflare fields through friendly methods', function () {
    fakeResponse([
        'success' => true,
        'hostname' => 'example.com',
        'action' => 'signup',
        'challenge_ts' => '2026-11-04T10:00:00Z',
        'cdata' => 'custom-data',
        'metadata' => ['ephemeral_id' => 'abc'],
    ]);

    $result = LaravelTurnstile::validateResponse('token');

    expect($result->hostname())->toBe('example.com')
        ->and($result->action())->toBe('signup')
        ->and($result->challengeTimestamp())->toBe('2026-11-04T10:00:00Z')
        ->and($result->cdata())->toBe('custom-data')
        ->and($result->metadata())->toBe(['ephemeral_id' => 'abc']);
});

it('returns null for fields cloudflare did not send', function () {
    fakeResponse(['success' => true]);

    $result = LaravelTurnstile::validateResponse('token');

    expect($result->hostname())->toBeNull()
        ->and($result->action())->toBeNull()
        ->and($result->challengeTimestamp())->toBeNull()
        ->and($result->cdata())->toBeNull()
        ->and($result->metadata())->toBe([]);
});

it('can be read like an array', function () {
    fakeResponse(['success' => true, 'hostname' => 'example.com']);

    $result = LaravelTurnstile::validateResponse('token');

    expect($result['success'])->toBeTrue()
        ->and($result['hostname'])->toBe('example.com')
        ->and(isset($result['hostname']))->toBeTrue()
        ->and(isset($result['missing']))->toBeFalse();
});

it('can be converted to an array and to json', function () {
    fakeResponse(['success' => true, 'hostname' => 'example.com']);

    $result = LaravelTurnstile::validateResponse('token');

    expect($result->toArray())
        ->toMatchArray(['success' => true, 'hostname' => 'example.com', 'reason' => null]);

    expect(json_decode((string) json_encode($result), true))
        ->toMatchArray(['success' => true, 'hostname' => 'example.com']);
});

it('keeps the untouched cloudflare payload available', function () {
    fakeResponse(['success' => true, 'hostname' => 'example.com']);

    $result = LaravelTurnstile::validateResponse('token');

    expect($result->raw())->toMatchArray(['success' => true, 'hostname' => 'example.com']);
});

it('cannot be changed after it is created', function () {
    fakeResponse(['success' => true]);

    $result = LaravelTurnstile::validateResponse('token');

    $result['success'] = false;
})->throws(LogicException::class);

it('cannot have values removed', function () {
    fakeResponse(['success' => true]);

    $result = LaravelTurnstile::validateResponse('token');

    unset($result['success']);
})->throws(LogicException::class);

it('reports a successful result with no reason', function () {
    fakeResponse(['success' => true]);

    $result = LaravelTurnstile::validateResponse('token');

    expect($result->successful())->toBeTrue()
        ->and($result->reason())->toBeNull()
        ->and($result->errorCodes())->toBe([]);
});
