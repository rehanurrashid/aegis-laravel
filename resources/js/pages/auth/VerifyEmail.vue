<!--
  pages/auth/VerifyEmail.vue — email verification notice + resend.

  Route: GET verification.notice
  Verify: GET/POST verification.verify (signed, handled by controller)
  Resend: POST verification.send
  Source: onboarding.php step 4 (email OTP)
  Layout: AuthLayout
-->
<template>
  <AuthLayout>
    <Head title="Verify Your Email" />

    <div class="auth-heading">
      <div class="auth-eyebrow">Step 4</div>
      <h1 class="auth-title">Verify Your Email</h1>
      <p class="auth-subtitle">
        We sent a verification link to
        <strong style="color: var(--text); font-weight: 600;">{{ $page.props.auth?.user?.email }}</strong>.
        Click the link in that email to continue.
      </p>
    </div>

    <!-- Flash success (after resend) -->
    <div v-if="$page.props.flash?.status" class="auth-alert auth-alert--success">
      <AegisIcon name="check-circle" :size="15" />
      <span>{{ $page.props.flash.status }}</span>
    </div>

    <!-- Resend form -->
    <form @submit.prevent="resend" novalidate>
      <button
        type="submit"
        class="btn btn-primary btn-block"
        :disabled="resendForm.processing"
      >{{ resendForm.processing ? 'Sending…' : 'Resend Verification Email' }}</button>
    </form>

    <p class="auth-altline" style="margin-top: 16px;">
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

const resendForm = useForm({})

function resend() {
  resendForm.post(route('verification.send'))
}

function logout() {
  router.post(route('logout'))
}
</script>

<style scoped>
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
