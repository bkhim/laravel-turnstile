<?php

namespace Bkhim\LaravelTurnstile\Components;

use Illuminate\View\Component;

/**
 * The <x-turnstile-widget /> Blade component.
 *
 * Part of the long-term community-maintained Bkhim fork of laravel-turnstile.
 */
class TurnstileWidget extends Component
{
    public function render(): string
    {
        return 'turnstile::components.turnstile-widget';
    }
}
