<!--
  pages/auth/MfaChallenge.vue — TOTP verification on login.

  Route: POST mfa.challenge.store
  Session-gated: controller redirects to login if no pending MFA session.
  Source: MfaController::showChallenge() / challenge()
  Layout: AuthLayout
-->
<template>
  <AuthLayout>
    <Head title="Two-factor verification" />

    <div class="auth-mfa-icon">
      <AegisIcon name="shield-check" :size="40" />
    </div>

    <div class="auth-heading">
      <h1 class="auth-title">Two-Factor Verification</h1>
      <p class="auth-subtitle">
        Enter the 6-digit code from your authenticator app.
      </p>
    </div>

    <form class="auth-form" @submit.prevent="submit">

      <div class="form-group">
        <label class="form-label" for="mfa-code">Verification Code</label>
        <input
          id="mfa-code"
          v-model="form.code"
          type="text"
          inputmode="numeric"
          pattern="[0-9]*"
          maxlength="6"
          class="form-input auth-code-input"
          :class="{ 'is-error': form.errors.code }"
          autocomplete="one-time-code"
          autofocus
          placeholder="000000"
        />
        <div v-if="form.errors.code" class="form-error">{{ form.errors.code }}</div>
      </div>

      <button
        type="submit"
        class="btn btn-primary btn-block"
        :disabled="form.processing || form.code.length !== 6"
      >{{ form.processing ? 'Verifying…' : 'Verify' }}</button>

    </form>

    <p class="auth-altline">
      <a
        :href="route('logout')"
        class="auth-altline-link"
        @click.prevent="logout"
      >Sign in with a different account</a>
    </p>

  </AuthLayout>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'

const form = useForm({ code: '' })

function submit() {
  form.post(route('mfa.challenge.store'), {
    onFinish: () => form.reset('code'),
  })
}

function logout() {
  router.post(route('logout'))
}
</script>
