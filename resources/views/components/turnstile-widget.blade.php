@props([
    'callback' => null,
    'errorCallback' => null,
    'theme' => 'auto',
    'language' => 'en-US',
    'size' => 'normal',
    'action' => null,
    'appearance' => null,
    'execution' => null,
    'responseFieldName' => null,
])

@php
    $turnstileAttributes = array_filter([
        'data-sitekey' => config('turnstile.turnstile_site_key'),
        'data-callback' => $callback,
        'data-error-callback' => $errorCallback,
        'data-theme' => $theme,
        'data-language' => $language,
        'data-size' => $size,
        'data-action' => $action,
        'data-appearance' => $appearance,
        'data-execution' => $execution,
        'data-response-field-name' => $responseFieldName,
    ], static fn ($value) => $value !== null && $value !== '');
@endphp

<div {{ $attributes->merge($turnstileAttributes)->merge(['class' => 'cf-turnstile']) }}></div>

@once
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" defer></script>
@endonce
