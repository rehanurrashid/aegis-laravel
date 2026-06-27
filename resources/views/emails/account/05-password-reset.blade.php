{{-- Alias: dispatched as 'emails.account.05-password-reset' -> renders canonical 'emails.auth.03-password-reset'. --}}
@php
    $reset_url = $reset_url ?? (isset($token) ? route('password.reset', ['token' => $token]) : null);
    $expires_minutes = $expires_minutes ?? 120;
@endphp
@include('emails.auth.03-password-reset')
