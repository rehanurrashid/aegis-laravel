<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title data-inertia>Aegis</title>
    <link rel="icon" type="image/svg+xml" href="/aegis-favicon.svg">
    <link rel="stylesheet" href="/css/_shared.css">
    @routes
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead

    {{-- Stripe.js — must load synchronously (no defer/async) so window.Stripe is available when Vue mounts --}}
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    {{--
        Stripe price IDs injected safely via data-aegis-config attribute.
        OnboardingPayment.vue reads this as: window.__AEGIS_CONFIG__
        Never echo PHP values directly inside <script> blocks.
    --}}
    <div
        id="aegis-config"
        style="display:none"
        data-config='{!! json_encode([
            "STRIPE_PRICE_ACCESS_MONTHLY"   => env("STRIPE_PRICE_ACCESS_MONTHLY",   ""),
            "STRIPE_PRICE_ACCESS_ANNUAL"    => env("STRIPE_PRICE_ACCESS_ANNUAL",    ""),
            "STRIPE_PRICE_PRACTICE_MONTHLY" => env("STRIPE_PRICE_PRACTICE_MONTHLY", ""),
            "STRIPE_PRICE_PRACTICE_ANNUAL"  => env("STRIPE_PRICE_PRACTICE_ANNUAL",  ""),
            "STRIPE_PRICE_BP_MONTHLY"       => env("STRIPE_PRICE_BP_MONTHLY",       ""),
            "STRIPE_PRICE_BP_ANNUAL"        => env("STRIPE_PRICE_BP_ANNUAL",        ""),
            "STRIPE_PRICE_SERVICES_ADDON"   => env("STRIPE_PRICE_SERVICES_ADDON",   ""),
            "STRIPE_PRICE_MAAT_ADDON"       => env("STRIPE_PRICE_MAAT_ADDON",       ""),
        ], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_HEX_APOS) !!}'
    ></div>
    <script>
        (function() {
            var el = document.getElementById('aegis-config');
            if (el) window.__AEGIS_CONFIG__ = JSON.parse(el.getAttribute('data-config'));
        })();
    </script>

    @inertia
</body>
</html>
