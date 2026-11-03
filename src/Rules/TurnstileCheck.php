<?php

namespace Bkhim\LaravelTurnstile\Rules;

use Bkhim\LaravelTurnstile\Facades\LaravelTurnstile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that the submitted Turnstile response is accepted by Cloudflare.
 *
 * Part of the long-term community-maintained Bkhim fork of laravel-turnstile.
 */
class TurnstileCheck implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail($this->message());

            return;
        }

        $response = LaravelTurnstile::validate($value);

        if (! ($response['success'] ?? false)) {
            $fail($this->message());
        }
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
