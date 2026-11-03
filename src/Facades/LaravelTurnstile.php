<?php

namespace Bkhim\LaravelTurnstile\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Bkhim\LaravelTurnstile\LaravelTurnstile
 */
class LaravelTurnstile extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Bkhim\LaravelTurnstile\LaravelTurnstile::class;
    }
}
