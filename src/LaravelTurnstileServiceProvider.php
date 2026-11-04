<?php

namespace Bkhim\LaravelTurnstile;

use Bkhim\LaravelTurnstile\Components\TurnstileWidget;
use Bkhim\LaravelTurnstile\Console\Commands\CheckTurnstileCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Laravel Turnstile service provider.
 *
 * This package is a long-term community-maintained fork of
 * coderflexx/laravel-turnstile. Contributions, suggestions, and improvements
 * are always welcome at https://github.com/bkhim/laravel-turnstile.
 */
class LaravelTurnstileServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-turnstile')
            ->hasConfigFile()
            ->hasViews('turnstile')
            ->hasViewComponent('', TurnstileWidget::class)
            ->hasCommand(CheckTurnstileCommand::class);
    }
}
