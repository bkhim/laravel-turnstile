<?php

namespace Bkhim\LaravelTurnstile;

use ArrayAccess;
use JsonSerializable;

/**
 * A friendly result object for a Turnstile check.
 *
 * You can read it like an array (`$result['success']`), or use the helper
 * methods below. The original Cloudflare payload is always available through
 * `raw()`.
 *
 * @implements ArrayAccess<string, mixed>
 */
final class TurnstileResponse implements ArrayAccess, JsonSerializable
{
    /**
     * @param  array<string, mixed>  $payload
     */
    private function __construct(
        private readonly array $payload,
        private readonly bool $valid,
        private readonly ?string $reason = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload, bool $valid = true, ?string $reason = null): self
    {
        return new self($payload, $valid, $reason);
    }

    /**
     * Did the token pass every check?
     */
    public function successful(): bool
    {
        return $this->valid;
    }

    /**
     * A short machine-readable reason when the check failed.
     *
     * Examples: `invalid_token`, `rejected`, `action_mismatch`,
     * `hostname_mismatch`, `hostname_not_configured`,
     * `validation_service_error`.
     */
    public function reason(): ?string
    {
        return $this->reason;
    }

    /**
     * The hostname that Cloudflare says the challenge was solved on.
     */
    public function hostname(): ?string
    {
        return $this->stringValue('hostname');
    }

    /**
     * The action that was set on the widget, if one was used.
     */
    public function action(): ?string
    {
        return $this->stringValue('action');
    }

    /**
     * The moment Cloudflare says the challenge was solved.
     */
    public function challengeTimestamp(): ?string
    {
        return $this->stringValue('challenge_ts');
    }

    /**
     * Any custom data that was sent from the widget.
     */
    public function cdata(): ?string
    {
        return $this->stringValue('cdata');
    }

    /**
     * The error codes returned by Cloudflare, if any.
     *
     * @return list<string>
     */
    public function errorCodes(): array
    {
        $codes = $this->payload['error-codes'] ?? [];
        $result = [];

        if (is_array($codes)) {
            foreach ($codes as $code) {
                if (is_string($code)) {
                    $result[] = $code;
                }
            }
        }

        return $result;
    }

    /**
     * Extra information returned by Cloudflare, if any.
     *
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        $metadata = $this->payload['metadata'] ?? [];

        if (! is_array($metadata)) {
            return [];
        }

        /** @var array<string, mixed> $metadata */
        return $metadata;
    }

    /**
     * The untouched response from Cloudflare.
     *
     * @return array<string, mixed>
     */
    public function raw(): array
    {
        return $this->payload;
    }

    /**
     * The result as an array, with `success` and `reason` included.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge($this->payload, [
            'success' => $this->valid,
            'reason' => $this->reason,
        ]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->toArray());
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->toArray()[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        // Results are read-only.
    }

    public function offsetUnset(mixed $offset): void
    {
        // Results are read-only.
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private function stringValue(string $key): ?string
    {
        $value = $this->payload[$key] ?? null;

        return is_string($value) ? $value : null;
    }
}
