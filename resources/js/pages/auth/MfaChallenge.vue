<!--
  pages/auth/MfaChallenge.vue — TOTP verification on login.

  Route: POST mfa.challenge.store
  Session-gated: controller redirects to login if no pending MFA session.
  Source: onboarding.php step 5 (2FA) / MfaController
  Layout: AuthLayout
-->
<template>
  <AuthLayout>
    <Head title="Two-Factor Verification" />

    <div class="auth-mfa-icon">
      <AegisIcon name="shield-check" :size="40" />
    </div>

    <div class="auth-heading">
      <h1 class="auth-title">Two-Factor Verification</h1>
      <p class="auth-subtitle">Enter the 6-digit code from your authenticator app.</p>
    </div>

    <form @submit.prevent="submit" novalidate>

      <div class="form-group mfa-code-group">
        <label class="form-label" for="mfa-code">Authentication Code</label>
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
          placeholder="_ _ _ _ _ _"
        />
        <div v-if="form.errors.code" class="form-error">{{ form.errors.code }}</div>
      </div>

      <button
        type="submit"
        class="btn btn-primary btn-block"
        :disabled="form.processing || form.code.length !== 6"
      >{{ form.processing ? 'Verifying…' : 'Verify' }}</button>

    </form>

    <p class="auth-altline" style="margin-top: 16px;">
      Having trouble?
      <a href="#" class="auth-altline-link">Use Backup Code</a>
    </p>

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
import { Head, useForm, router, usePage } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'

const form    = useForm({ code: '' })
const mfaPage = usePage()

const portalRouteMap = {
  practitioner:       'provider.dashboard',
  business_partner:   'bp.dashboard',
  continuity_steward: 'cs.dashboard',
  support_steward:    'ss.dashboard',
  admin:              'admin.dashboard',
}

function submit() {
  form.post(route('mfa.challenge.store'), {
    onSuccess: () => {
      const role      = mfaPage.props.auth?.user?.role
      const routeName = portalRouteMap[role]
      if (routeName) {
        router.visit(route(routeName), { replace: true })
      }
    },
    onFinish: () => form.reset('code'),
  })
}

function logout() {
  router.post(route('logout'))
}
</script>

<style scoped>
/* Center + enlarge the code input to match PHP step 5 OTP style */
.mfa-code-group .auth-code-input {
  text-align: center;
  font-family: var(--font-serif);
  font-size: 22px;
  font-weight: 700;
  letter-spacing: 8px;
}

/* Pill button */
.btn-primary {
  border-radius: var(--radius-full);
  padding: 11px 22px;
  background: var(--primary);
  color: var(--text-inverted);
  border: 1.5px solid var(--primary);
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-mid);
  border-color: var(--primary-mid);
}
</style>
