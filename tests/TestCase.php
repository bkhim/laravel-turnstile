<?php

namespace Bkhim\LaravelTurnstile\Tests;

use Bkhim\LaravelTurnstile\LaravelTurnstileServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelTurnstileServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Cloudflare's official dummy keys for testing.
        // @see https://developers.cloudflare.com/turnstile/reference/testing/
        $app['config']->set('turnstile.turnstile_site_key', '1x00000000000000000000AA');
        $app['config']->set('turnstile.turnstile_secret_key', '1x0000000000000000000000000000000AA');
    }
}
