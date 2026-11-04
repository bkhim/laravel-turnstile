<?php

namespace Bkhim\LaravelTurnstile\Rules;

use Bkhim\LaravelTurnstile\Facades\LaravelTurnstile;
use Bkhim\LaravelTurnstile\TurnstileResponse;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

/**
 * Validates that the submitted Turnstile response is accepted by Cloudflare.
 *
 * You can use it without any options (the same behaviour as before), or pass
 * an expected action and hostnames for a stronger check.
 *
 * Part of the long-term community-maintained Bkhim fork of laravel-turnstile.
 */
class TurnstileCheck implements ValidationRule
{
    /**
     * @param  array<int, string>|string|null  $hostnames
     */
    public function __construct(
        private readonly ?string $action = null,
        private readonly array|string|null $hostnames = null,
        private readonly ?string $remoteIp = null,
        private readonly ?bool $requireHostnames = null,
        private readonly ?bool $failClosed = null,
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail($this->message());

            return;
        }

        $failClosed = $this->failClosed ?? (bool) config('turnstile.fail_closed', false);

        $options = [
            'action' => $this->action ?? config('turnstile.action'),
            'hostnames' => $this->hostnames ?? config('turnstile.hostnames'),
            'remote_ip' => $this->remoteIp,
            'require_hostnames' => $this->requireHostnames ?? (bool) config('turnstile.require_hostnames', false),
            'fail_closed' => $failClosed,
        ];

        try {
            $response = LaravelTurnstile::validateResponse($value, $options);
        } catch (Throwable $exception) {
            if (! $failClosed) {
                throw $exception;
            }

            report($exception);
            $fail($this->message());

            return;
        }

        if (! $response->successful()) {
            $this->logFailure($response);

            $fail($this->message());
        }
    }

    private function logFailure(TurnstileResponse $response): void
    {
        if (! (bool) config('turnstile.log_failures', false)) {
            return;
        }

        logger()->warning('Turnstile validation failed.', [
            'reason' => $response->reason(),
            'error_codes' => $response->errorCodes(),
            'hostname' => $response->hostname(),
            'action' => $response->action(),
        ]);
    }

    /**
     * Get the translated validation failure message.
     */
    private function message(): string
    {
        return __((string) config(
            'turnstile.error_messages.turnstile_check_message',
            'The CAPTCHA thinks you are a robot! Please refresh and try again.'
        ));
    }
}
