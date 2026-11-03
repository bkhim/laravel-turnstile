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
     * Validate a Turnstile response against Cloudflare.
     *
     * When no response is given, the value of the `cf-turnstile-response`
     * request field is used (this is the field populated by the widget).
     *
     * @return array<string, mixed>
     *
     * @throws SecretKeyNotFoundException
     * @throws UnknownErrorOccurredException
     */
    public function validate(?string $cfResponse = null): array
    {
        $turnstileResponse = $cfResponse ?? request()->input('cf-turnstile-response');

        if (! $secret = config('turnstile.turnstile_secret_key')) {
            throw new SecretKeyNotFoundException('Turnstile secret key is not defined.');
        }

        try {
            $response = Http::asJson()
                ->timeout(30)
                ->connectTimeout(10)
                ->post($this->getUrl(), [
                    'secret' => $secret,
                    'response' => $turnstileResponse,
                ]);
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

        /** @var array<string, mixed>|null $payload */
        $payload = $response->json();

        return is_array($payload) && $payload !== []
            ? $payload
            : [
                'success' => false,
                'message' => 'Unknown error occurred, please try again.',
            ];
    }

    /**
     * Get the Cloudflare verification endpoint.
     */
    public function getUrl(): string
    {
        return $this->url;
    }
}
