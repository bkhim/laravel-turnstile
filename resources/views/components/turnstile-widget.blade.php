@props([
    'id' => null,
    'callback' => null,
    'errorCallback' => null,
    'expiredCallback' => null,
    'timeoutCallback' => null,
    'unsupportedCallback' => null,
    'beforeInteractiveCallback' => null,
    'afterInteractiveCallback' => null,
    'theme' => 'auto',
    'language' => 'en-US',
    'size' => 'normal',
    'action' => null,
    'cdata' => null,
    'appearance' => null,
    'execution' => null,
    'responseFieldName' => null,
    'retry' => null,
    'refreshExpired' => null,
    'tabindex' => null,
    'render' => 'implicit',
    'onload' => null,
])

@php
    $turnstileAttributes = array_filter([
        'data-sitekey' => config('turnstile.turnstile_site_key'),
        'data-callback' => $callback,
        'data-error-callback' => $errorCallback,
        'data-expired-callback' => $expiredCallback,
        'data-timeout-callback' => $timeoutCallback,
        'data-unsupported-callback' => $unsupportedCallback,
        'data-before-interactive-callback' => $beforeInteractiveCallback,
        'data-after-interactive-callback' => $afterInteractiveCallback,
        'data-theme' => $theme,
        'data-language' => $language,
        'data-size' => $size,
        'data-action' => $action,
        'data-cdata' => $cdata,
        'data-appearance' => $appearance,
        'data-execution' => $execution,
        'data-response-field-name' => $responseFieldName,
        'data-retry' => $retry,
        'data-refresh-expired' => $refreshExpired,
        'data-tabindex' => $tabindex,
    ], static fn ($value) => $value !== null && $value !== '');

    $elementAttributes = $attributes->merge($turnstileAttributes)->merge(['class' => 'cf-turnstile']);

    if ($id !== null && $id !== '') {
        $elementAttributes = $elementAttributes->merge(['id' => $id]);
    }

    $scriptParameters = array_filter([
        'render' => $render === 'explicit' ? 'explicit' : null,
        'onload' => $onload,
    ]);

    $scriptUrl = 'https://challenges.cloudflare.com/turnstile/v0/api.js';

    if ($scriptParameters !== []) {
        $scriptUrl .= '?' . http_build_query($scriptParameters);
    }
@endphp

<div {{ $elementAttributes }}></div>

@once
    <script src="{{ $scriptUrl }}" async defer></script>
@endonce
