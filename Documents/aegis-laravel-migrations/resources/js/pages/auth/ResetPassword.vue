<!--
  pages/auth/ResetPassword.vue — set a new password with the reset token.

  Route: POST password.update
  Props: token (from URL), email (from query string, pre-filled)
  Source: login.php (reset-password logic from PasswordResetController)
  Layout: AuthLayout
-->
<template>
  <AuthLayout>
    <Head title="Set new password" />

    <div class="auth-heading">
      <div class="auth-eyebrow">Account Recovery</div>
      <h1 class="auth-title">Set a New Password</h1>
      <p class="auth-subtitle">Choose a strong password for your account.</p>
    </div>

    <form class="auth-form" @submit.prevent="submit">

      <div class="form-group">
        <label class="form-label" for="rp-email">Email Address</label>
        <input
          id="rp-email"
          v-model="form.email"
          type="email"
          class="form-input"
          :class="{ 'is-error': form.errors.email }"
          autocomplete="email"
          autofocus
        />
        <div v-if="form.errors.email" class="form-error">{{ form.errors.email }}</div>
      </div>

      <div class="form-group">
        <label class="form-label" for="rp-password">New Password</label>
        <input
          id="rp-password"
          v-model="form.password"
          type="password"
          class="form-input"
          :class="{ 'is-error': form.errors.password }"
          autocomplete="new-password"
        />
        <div v-if="form.errors.password" class="form-error">{{ form.errors.password }}</div>
        <div class="form-hint">Minimum 8 characters.</div>
      </div>

      <div class="form-group">
        <label class="form-label" for="rp-confirm">Confirm New Password</label>
        <input
          id="rp-confirm"
          v-model="form.password_confirmation"
          type="password"
          class="form-input"
          autocomplete="new-password"
        />
      </div>

      <!-- Token validation error -->
      <div v-if="form.errors.token" class="auth-alert auth-alert--error">
        <AegisIcon name="alert-circle" :size="15" />
        <span>{{ form.errors.token }}</span>
      </div>

      <button
        type="submit"
        class="btn btn-primary btn-block"
        :disabled="form.processing"
      >{{ form.processing ? 'Saving…' : 'Save New Password' }}</button>

    </form>

    <p class="auth-altline">
      <a :href="route('login')" class="auth-altline-link">Back to Sign In</a>
    </p>

  </AuthLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'

const props = defineProps({
  token: { type: String, required: true },
  email: { type: String, default: '' },
})

const form = useForm({
  token:                 props.token,
  email:                 props.email,   // pre-filled from controller query param
  password:              '',
  password_confirmation: '',
})

function submit() {
  form.post(route('password.update'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>
