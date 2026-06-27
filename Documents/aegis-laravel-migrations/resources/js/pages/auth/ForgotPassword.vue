<!--
  pages/auth/ForgotPassword.vue — request a password reset link.

  Route: POST password.email
  Source: login.php (forgot-panel section)
  Layout: AuthLayout
-->
<template>
  <AuthLayout>
    <Head title="Reset password" />

    <div class="auth-heading">
      <div class="auth-eyebrow">Account Recovery</div>
      <h1 class="auth-title">Reset Password</h1>
      <p class="auth-subtitle">
        Enter your email address and we will send you a link to reset your password.
      </p>
    </div>

    <!-- Success state — shown after link sent -->
    <div v-if="$page.props.flash?.status" class="auth-success-box">
      <div class="auth-success-icon">
        <AegisIcon name="check-circle" :size="28" />
      </div>
      <div class="auth-success-title">Check Your Email</div>
      <p class="auth-success-body">
        {{ $page.props.flash.status }}
        Check your spam folder if you do not see it within a few minutes.
      </p>
      <a :href="route('login')" class="btn btn-outline btn-block">Back to Sign In</a>
    </div>

    <!-- Form state -->
    <form v-else class="auth-form" @submit.prevent="submit">
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
    </form>

    <p class="auth-altline">
      <a :href="route('login')" class="auth-altline-link">Back to Sign In</a>
    </p>

  </AuthLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'

const form = useForm({ email: '' })

function submit() {
  form.post(route('password.email'))
}
</script>
