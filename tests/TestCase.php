<?php

namespace Bkhim\LaravelTurnstile\Tests;

use Bkhim\LaravelTurnstile\LaravelTurnstileServiceProvider;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

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
        $app['config']->set('turnstile.hostnames', []);
        $app['config']->set('turnstile.action', null);
        $app['config']->set('turnstile.require_hostnames', false);
        $app['config']->set('turnstile.fail_closed', false);
        $app['config']->set('turnstile.send_remote_ip', true);
        $app['config']->set('turnstile.timeout', 10);
        $app['config']->set('turnstile.connect_timeout', 5);
        $app['config']->set('turnstile.log_failures', false);
    }
}
