<?php

namespace Bkhim\LaravelTurnstile\Console\Commands;

use Bkhim\LaravelTurnstile\Exceptions\SecretKeyNotFoundException;
use Bkhim\LaravelTurnstile\Exceptions\UnknownErrorOccurredException;
use Bkhim\LaravelTurnstile\LaravelTurnstile;
use Illuminate\Console\Command;

class CheckTurnstileCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'turnstile:check';

    /**
     * @var string
     */
    protected $description = 'Check your Turnstile configuration and verify that your secret key works.';

    /**
     * Cloudflare's public test keys.
     *
     * @var list<string>
     */
    private const TEST_KEYS = [
        '1x0000000000000000000000000000000AA',
        '2x0000000000000000000000000000000AA',
        '3x0000000000000000000000000000000AA',
        '1x00000000000000000000AA',
        '2x00000000000000000000AB',
        '1x00000000000000000000BB',
        '2x00000000000000000000BB',
        '3x00000000000000000000FF',
    ];

    public function handle(LaravelTurnstile $turnstile): int
    {
        $secret = config('turnstile.turnstile_secret_key');
        $siteKey = config('turnstile.turnstile_site_key');
        $hostnames = config('turnstile.hostnames', []);
        $action = config('turnstile.action');
        $requireHostnames = (bool) config('turnstile.require_hostnames', false);
        $failClosed = (bool) config('turnstile.fail_closed', false);

        $this->line('Turnstile configuration');
        $this->line('  Site key: '.(is_string($siteKey) && $siteKey !== '' ? $siteKey : 'not set'));
        $this->line('  Secret key: '.(is_string($secret) && $secret !== '' ? 'set' : 'not set'));
        $this->line('  Allowed hostnames: '.(is_array($hostnames) && $hostnames !== [] ? implode(', ', $hostnames) : 'none'));
        $this->line('  Action: '.(is_string($action) && $action !== '' ? $action : 'none'));
        $this->line('  Require hostnames: '.($requireHostnames ? 'yes' : 'no'));
        $this->line('  Reject when Cloudflare is unreachable: '.($failClosed ? 'yes' : 'no'));
        $this->newLine();

        if (! is_string($secret) || $secret === '') {
            $this->error('The Turnstile secret key is not set. Add TURNSTILE_SECRET_KEY to your environment.');

            return self::FAILURE;
        }

        if ($requireHostnames && (! is_array($hostnames) || $hostnames === [])) {
            $this->error('Hostname checking is turned on, but no hostnames are configured. Add TURNSTILE_HOSTNAMES.');

            return self::FAILURE;
        }

        if (! is_array($hostnames) || $hostnames === []) {
            $this->warn('No hostnames are configured, so the package will not check which website a token came from.');
        }

        if (app()->environment('production')) {
            $localHostnames = array_values(array_intersect(
                is_array($hostnames) ? $hostnames : [],
                ['localhost', '127.0.0.1', '0.0.0.0']
            ));

            if ($localHostnames !== []) {
                $this->warn('Local hostnames are configured in production: '.implode(', ', $localHostnames));
            }

            if ($this->isTestKey($secret) || (is_string($siteKey) && $this->isTestKey($siteKey))) {
                $this->warn('A Cloudflare test key is configured in production.');
            }
        }

        $this->line('Checking your secret key with Cloudflare...');

        try {
            $response = $turnstile->validate('XXXX.DUMMY.TOKEN.XXXX');
        } catch (SecretKeyNotFoundException|UnknownErrorOccurredException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $codes = $response['error-codes'] ?? [];

        if (is_array($codes) && in_array('invalid-input-secret', $codes, true)) {
            $this->error('Cloudflare rejected the secret key. Check TURNSTILE_SECRET_KEY.');

            return self::FAILURE;
        }

        if (($response['success'] ?? false) === true || (is_array($codes) && in_array('invalid-input-response', $codes, true))) {
            $this->info('Your secret key works. Cloudflare accepted it and handled the dummy token as expected.');

            if ($this->isTestKey($secret)) {
                $this->warn('This is a Cloudflare test key. Do not use it in production.');
            }

            return self::SUCCESS;
        }

        $this->error('Cloudflare returned an unexpected response. Error codes: '.(is_array($codes) ? implode(', ', $codes) : 'none'));

        return self::FAILURE;
    }

    private function isTestKey(string $key): bool
    {
        return in_array($key, self::TEST_KEYS, true);
    }
}
