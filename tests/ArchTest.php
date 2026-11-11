<?php

arch('exceptions extend the base exception class')
    ->expect('Bkhim\LaravelTurnstile\Exceptions')
    ->toExtend('Exception');

arch('the facade extends the laravel facade')
    ->expect('Bkhim\LaravelTurnstile\Facades')
    ->toExtend('Illuminate\Support\Facades\Facade');

arch('the service provider extends the package provider')
    ->expect('Bkhim\LaravelTurnstile\LaravelTurnstileServiceProvider')
    ->toExtend('Spatie\LaravelPackageTools\PackageServiceProvider');

arch('source code does not use debugging helpers')
    ->expect('Bkhim\LaravelTurnstile')
    ->not->toUse(['dd', 'dump', 'var_dump', 'print_r', 'ray']);
