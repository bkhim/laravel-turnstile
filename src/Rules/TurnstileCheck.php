<?php

namespace Bkhim\LaravelTurnstile\Rules;

use Bkhim\LaravelTurnstile\Facades\LaravelTurnstile;
use Bkhim\LaravelTurnstile\TurnstileResponse;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

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

        $options = [
            'action' => $this->action ?? config('turnstile.action'),
            'hostnames' => $this->hostnames ?? config('turnstile.hostnames'),
            'remote_ip' => $this->remoteIp,
            'require_hostnames' => $this->requireHostnames ?? (bool) config('turnstile.require_hostnames', false),
            'fail_closed' => $this->failClosed ?? (bool) config('turnstile.fail_closed', false),
        ];

        // Note: `validateResponse()` already honours the `fail_closed` option.
        // Anything else it throws, such as a missing secret key, is a real
        // configuration problem and should not be hidden behind a friendly
        // "you are a robot" message.
        $response = LaravelTurnstile::validateResponse($value, $options);

        if (! $response->successful()) {
            $this->logFailure($response);

            $fail($this->message());
        }
    }

    private function logFailure(TurnstileResponse $response): void
    {
        $reason = $response->reason();

        // A rejected secret key breaks every visitor, so always make it
        // visible even when failure logging is turned off.
        if ($reason === 'invalid_secret') {
            logger()->error(
                'Turnstile rejected the secret key. Every visitor will fail the check until TURNSTILE_SECRET_KEY is corrected.',
                $this->context($response)
            );

            return;
        }

        if (! (bool) config('turnstile.log_failures', false)) {
            return;
        }

        logger()->warning('Turnstile validation failed.', $this->context($response));
    }

    /**
     * @return array<string, mixed>
     */
    private function context(TurnstileResponse $response): array
    {
        return [
            'reason' => $response->reason(),
            'error_codes' => $response->errorCodes(),
            'hostname' => $response->hostname(),
            'action' => $response->action(),
        ];
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
