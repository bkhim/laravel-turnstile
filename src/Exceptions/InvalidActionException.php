<?php

namespace Bkhim\LaravelTurnstile\Exceptions;

use Exception;

/**
 * Thrown when a Turnstile action does not follow Cloudflare's rules.
 *
 * Cloudflare allows 1 to 32 characters, using only letters, numbers,
 * underscores, and hyphens.
 */
class InvalidActionException extends Exception
{
    protected int $status = 500;
}
