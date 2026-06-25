<!--
  pages/auth/ForgotPassword.vue — request a password reset link.

  Route: POST password.email
  Source: login.php (forgot-panel section)
  Layout: AuthLayout
-->
<template>
  <AuthLayout>
    <Head title="Reset Password" />

    <!-- Success state -->
    <div v-if="$page.props.flash?.status" class="auth-success-box">
      <div class="auth-success-icon">
        <AegisIcon name="check-circle" :size="28" />
      </div>
      <div class="auth-success-title">Check Your Email</div>
      <p class="auth-success-body">
        We've sent a password reset link to <strong>{{ submittedEmail }}</strong>.<br /><br />
        The link will expire in 30 minutes. Check your spam folder if you don't see it.
      </p>
      <a :href="route('login')" class="btn btn-outline btn-block">Back to Sign In</a>
    </div>

    <!-- Form state -->
    <template v-else>
      <div class="auth-heading">
        <div class="auth-eyebrow">Account Recovery</div>
        <h1 class="auth-title">Reset Password</h1>
        <p class="auth-subtitle">Enter your email address and we'll send you a link to reset your password.</p>
      </div>

      <form @submit.prevent="submit" novalidate>
        <div class="form-group">
          <label class="form-label" for="fp-email">Email Address</label>
          <input
            id="fp-email"
            v-model="form.email"
            type="email"
            class="form-input"
            :class="{ 'is-error': form.errors.email }"
            autocomplete="email"
            autofocus
            placeholder="your@email.com"
          />
          <div v-if="form.errors.email" class="form-error">{{ form.errors.email }}</div>
        </div>

        <button
          type="submit"
          class="btn btn-primary btn-block"
          :disabled="form.processing"
        >{{ form.processing ? 'Sending…' : 'Send Reset Link' }}</button>

        <!-- Hint text — from onboarding.php reset flow -->
        <p class="fp-reset-hint">
          We'll send a password reset link to your registered email address. Check your spam folder if you don't see it.
        </p>
      </form>

      <p class="auth-altline">
        <a :href="route('login')" class="auth-altline-link">Back to Sign In</a>
      </p>
    </template>

  </AuthLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'

const submittedEmail = ref('')

const form = useForm({ email: '' })

function submit() {
  submittedEmail.value = form.email
  form.post(route('password.email'))
}
</script>

<style scoped>
/* Hint text below Send Reset Link button */
.fp-reset-hint {
  text-align: center;
  margin-top: 16px;
  font-size: 12px;
  color: var(--text-2);
  line-height: 1.5;
}

/* Pill buttons matching onboarding.php .btn */
.btn-primary,
.btn-outline {
  border-radius: var(--radius-full);
  padding: 11px 22px;
  border: 1.5px solid transparent;
}

.btn-primary {
  background: var(--primary);
  color: var(--text-inverted);
  border-color: var(--primary);
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-mid);
  border-color: var(--primary-mid);
}

.btn-outline {
  background: transparent;
  color: var(--text-2);
  border-color: var(--border-dark);
}

.btn-outline:hover {
  border-color: var(--gold);
  color: var(--gold-dark);
}
</style>
