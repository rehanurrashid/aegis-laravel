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

    {{-- Appearance: apply saved theme/darkMode before first paint.
         Reads from localStorage (fast, no flash) AND from server-injected value (cross-device). --}}
    <script>
    (function () {
        try {
            var prefs = JSON.parse(localStorage.getItem('aegis_appearance') || '{}');
            // Apply to <html> element (body doesn't exist yet in <head>)
            var el = document.documentElement;
            el.classList.remove('theme-dark', 'theme-gold-dark', 'theme-gold-deep', 'theme-slate');
            if (prefs.theme === 'gold-dark') el.classList.add('theme-gold-dark');
            if (prefs.theme === 'gold-deep') el.classList.add('theme-gold-deep');
            if (prefs.theme === 'slate')     el.classList.add('theme-slate');
            if (prefs.darkMode)              el.classList.add('theme-dark');
        } catch (e) {}
    })();
    </script>

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
            "STRIPE_PRICE_ACCESS_MONTHLY"       => env("STRIPE_PRICE_ACCESS_MONTHLY",       ""),
            "STRIPE_PRICE_ACCESS_ANNUAL"        => env("STRIPE_PRICE_ACCESS_ANNUAL",        ""),
            "STRIPE_PRICE_PRACTICE_MONTHLY"     => env("STRIPE_PRICE_PRACTICE_MONTHLY",     ""),
            "STRIPE_PRICE_PRACTICE_ANNUAL"      => env("STRIPE_PRICE_PRACTICE_ANNUAL",      ""),
            "STRIPE_PRICE_BP_MONTHLY"           => env("STRIPE_PRICE_BP_MONTHLY",           ""),
            "STRIPE_PRICE_BP_ANNUAL"            => env("STRIPE_PRICE_BP_ANNUAL",            ""),
            "STRIPE_PRICE_CS_BUSINESS_MONTHLY"  => env("STRIPE_PRICE_CS_BUSINESS_MONTHLY",  ""),
            "STRIPE_PRICE_CS_BUSINESS_ANNUAL"   => env("STRIPE_PRICE_CS_BUSINESS_ANNUAL",   ""),
            "STRIPE_PRICE_MAAT_MONTHLY"         => env("STRIPE_PRICE_MAAT_MONTHLY",         ""),
            "STRIPE_PRICE_MAAT_ANNUAL"          => env("STRIPE_PRICE_MAAT_ANNUAL",          ""),
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
