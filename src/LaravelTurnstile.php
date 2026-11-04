<?php

namespace Bkhim\LaravelTurnstile;

use Bkhim\LaravelTurnstile\Exceptions\SecretKeyNotFoundException;
use Bkhim\LaravelTurnstile\Exceptions\UnknownErrorOccurredException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Laravel Turnstile.
 *
 * This package is a community-maintained fork of
 * {@see https://github.com/coderflexx/laravel-turnstile coderflexx/laravel-turnstile}.
 *
 * It is maintained by Bkhim at {@see https://github.com/bkhim/laravel-turnstile}.
 * We intend to keep it maintained for the long term and we warmly welcome bug
 * reports, feature suggestions, pull requests, and any other improvements.
 */
class LaravelTurnstile
{
    /**
     * The Cloudflare Turnstile verification endpoint.
     */
    protected string $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * Check a Turnstile token with Cloudflare.
     *
     * This is the original method and it keeps working exactly as before.
     * It returns the response from Cloudflare as an array. If you also want
     * to check the hostname and action, use `validateResponse()` instead.
     *
     * When no response is given, the value of the configured response field
     * on the current request is used (by default `cf-turnstile-response`).
     *
     * @return array<string, mixed>
     *
     * @throws SecretKeyNotFoundException
     * @throws UnknownErrorOccurredException
     */
    public function validate(?string $cfResponse = null): array
    {
        $secret = $this->resolveSecret();
        $token = $this->resolveToken($cfResponse);

        if (! $this->isValidToken($token)) {
            return [
                'success' => false,
                'error-codes' => ['invalid-input-response'],
                'message' => 'Invalid Turnstile token.',
            ];
        }

        return $this->send($this->buildPayload($token, $secret));
    }

    /**
     * Check a Turnstile token and optionally confirm the hostname and action.
     *
     * This returns a friendly result object. You can read it like an array
     * (`$result['success']`) or use methods such as `$result->successful()`
     * and `$result->reason()`.
     *
     * Supported options:
     * - `action`: the action you expect the token to have been created for
     * - `hostnames`: one hostname or a list of allowed hostnames
     * - `require_hostnames`: reject the token when no hostnames are configured
     * - `remote_ip`: the visitor's IP address (defaults to the request IP)
     * - `send_remote_ip`: set to false to leave the IP address out
     * - `idempotency_key`: a key Cloudflare can use to safely retry a request
     * - `timeout` / `connect_timeout`: request timeouts in seconds
     * - `fail_closed`: return a failed result instead of throwing when
     *   Cloudflare cannot be reached
     * - `response_field_name`: the request field that holds the token
     *
     * @param  array<string, mixed>  $options
     *
     * @throws SecretKeyNotFoundException
     * @throws UnknownErrorOccurredException
     */
    public function validateResponse(?string $cfResponse = null, array $options = []): TurnstileResponse
    {
        $secret = $this->resolveSecret();
        $token = $this->resolveToken($cfResponse, $options);
        $failClosed = array_key_exists('fail_closed', $options)
            ? (bool) $options['fail_closed']
            : (bool) config('turnstile.fail_closed', false);

        if (! $this->isValidToken($token)) {
            return TurnstileResponse::fromPayload(
                ['success' => false, 'error-codes' => ['invalid-input-response']],
                false,
                'invalid_token'
            );
        }

        try {
            $payload = $this->send($this->buildPayload($token, $secret, $options), $options);
        } catch (UnknownErrorOccurredException $exception) {
            if (! $failClosed) {
                throw $exception;
            }

            return TurnstileResponse::fromPayload(
                ['success' => false, 'error-codes' => ['internal-error']],
                false,
                'validation_service_error'
            );
        }

        if (! ($payload['success'] ?? false)) {
            return TurnstileResponse::fromPayload($payload, false, 'rejected');
        }

        $expectedAction = $options['action'] ?? config('turnstile.action');

        if (is_string($expectedAction) && $expectedAction !== '') {
            $receivedAction = $payload['action'] ?? null;

            if ($receivedAction !== $expectedAction) {
                return TurnstileResponse::fromPayload($payload, false, 'action_mismatch');
            }
        }

        $hostnames = $this->normalizeHostnames($options['hostnames'] ?? config('turnstile.hostnames', []));
        $requireHostnames = array_key_exists('require_hostnames', $options)
            ? (bool) $options['require_hostnames']
            : (bool) config('turnstile.require_hostnames', false);

        if ($requireHostnames && $hostnames === []) {
            return TurnstileResponse::fromPayload($payload, false, 'hostname_not_configured');
        }

        if ($hostnames !== []) {
            $receivedHostname = $payload['hostname'] ?? null;

            if (! is_string($receivedHostname) || ! in_array($this->normalizeHostname($receivedHostname), $hostnames, true)) {
                return TurnstileResponse::fromPayload($payload, false, 'hostname_mismatch');
            }
        }

        return TurnstileResponse::fromPayload($payload, true);
    }

    /**
     * Fake Cloudflare checks in your own tests.
     *
     * @param  array<string, mixed>  $payload
     */
    public function fake(array $payload = ['success' => true]): void
    {
        Http::fake([
            $this->getUrl() => Http::response($payload),
        ]);
    }

    /**
     * Get the Cloudflare verification endpoint.
     */
    public function getUrl(): string
    {
        $configured = config('turnstile.verify_url');

        return is_string($configured) && $configured !== '' ? $configured : $this->url;
    }

    /**
     * @throws SecretKeyNotFoundException
     */
    private function resolveSecret(): string
    {
        $secret = config('turnstile.turnstile_secret_key');

        if (! is_string($secret) || $secret === '') {
            throw new SecretKeyNotFoundException('Turnstile secret key is not defined.');
        }

        return $secret;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function resolveToken(?string $cfResponse, array $options = []): ?string
    {
        if ($cfResponse !== null) {
            return $cfResponse;
        }

        $field = $options['response_field_name'] ?? config('turnstile.response_field_name', 'cf-turnstile-response');
        $field = is_string($field) && $field !== '' ? $field : 'cf-turnstile-response';
        $value = request()->input($field);

        return is_string($value) ? $value : null;
    }

    private function isValidToken(?string $token): bool
    {
        return $token !== null && $token !== '' && strlen($token) <= 2048;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, string>
     */
    private function buildPayload(string $token, string $secret, array $options = []): array
    {
        $payload = [
            'secret' => $secret,
            'response' => $token,
        ];

        $sendRemoteIp = array_key_exists('send_remote_ip', $options)
            ? (bool) $options['send_remote_ip']
            : (bool) config('turnstile.send_remote_ip', true);

        $remoteIp = $options['remote_ip'] ?? null;

        if ($remoteIp === null && $sendRemoteIp) {
            $remoteIp = request()->ip();
        }

        if (is_string($remoteIp) && $remoteIp !== '') {
            $payload['remoteip'] = $remoteIp;
        }

        $idempotencyKey = $options['idempotency_key'] ?? null;

        if (is_string($idempotencyKey) && $idempotencyKey !== '') {
            $payload['idempotency_key'] = $idempotencyKey;
        }

        return $payload;
    }

    /**
     * @param  array<string, string>  $payload
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     *
     * @throws UnknownErrorOccurredException
     */
    private function send(array $payload, array $options = []): array
    {
        $timeout = array_key_exists('timeout', $options)
            ? (int) $options['timeout']
            : (int) config('turnstile.timeout', 10);

        $connectTimeout = array_key_exists('connect_timeout', $options)
            ? (int) $options['connect_timeout']
            : (int) config('turnstile.connect_timeout', 5);

        try {
            $response = Http::asForm()
                ->timeout(max(1, $timeout))
                ->connectTimeout(max(1, $connectTimeout))
                ->post($this->getUrl(), $payload);
        } catch (ConnectionException $exception) {
            throw new UnknownErrorOccurredException(
                'An unknown error occurred, please refresh the page and try again.',
                0,
                $exception
            );
        }

        if ($response->failed()) {
            throw new UnknownErrorOccurredException(
                'An unknown error occurred, please refresh the page and try again.'
            );
        }

        $data = $response->json();

        return is_array($data) && $data !== []
            ? $data
            : [
                'success' => false,
                'error-codes' => ['internal-error'],
                'message' => 'Unknown error occurred, please try again.',
            ];
    }

    /**
     * @return list<string>
     */
    private function normalizeHostnames(mixed $hostnames): array
    {
        if (is_string($hostnames)) {
            $hostnames = explode(',', $hostnames);
        }

        if (! is_array($hostnames)) {
            return [];
        }

        $normalized = [];

        foreach ($hostnames as $hostname) {
            if (! is_string($hostname)) {
                continue;
            }

            $hostname = $this->normalizeHostname($hostname);

            if ($hostname !== '') {
                $normalized[] = $hostname;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeHostname(string $hostname): string
    {
        return rtrim(strtolower(trim($hostname)), '.');
    }
}
