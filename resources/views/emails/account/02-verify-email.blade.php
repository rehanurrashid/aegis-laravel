{{-- Alias: dispatched as 'emails.account.02-verify-email' -> renders canonical 'emails.auth.02-email-verification'. --}}
@php
    $verify_url = $verify_url ?? ($verification_url ?? null);
    $expires_hours = $expires_hours ?? 24;
@endphp
@include('emails.auth.02-email-verification')
