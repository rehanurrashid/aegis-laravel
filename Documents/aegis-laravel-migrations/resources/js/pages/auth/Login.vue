<!--
  pages/auth/Login.vue — sign in.

  Route: POST login.store
  Source: login.php (signin-panel section)
  Layout: AuthLayout — renders slot inside .auth-card
-->
<template>
  <AuthLayout>
    <Head title="Sign in" />

    <div class="auth-heading">
      <div class="auth-eyebrow">Secure Sign In</div>
      <h1 class="auth-title">Sign in to Aegis</h1>
      <p class="auth-subtitle">Enter your credentials to access your dashboard.</p>
    </div>

    <!-- Flash error (locked / deactivated / general) -->
    <div v-if="$page.props.flash?.error" class="auth-alert auth-alert--error">
      <AegisIcon name="alert-circle" :size="15" />
      <span>{{ $page.props.flash.error }}</span>
    </div>

    <form class="auth-form" @submit.prevent="submit">

      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input
          id="email"
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

      <div class="form-group">
        <div class="form-label-row">
          <label class="form-label" for="password">Password</label>
          <a :href="route('password.request')" class="form-label-link">Forgot password?</a>
        </div>
        <input
          id="password"
          v-model="form.password"
          type="password"
          class="form-input"
          :class="{ 'is-error': form.errors.password }"
          autocomplete="current-password"
          placeholder="Enter your password"
        />
        <div v-if="form.errors.password" class="form-error">{{ form.errors.password }}</div>
      </div>

      <div class="auth-remember">
        <input
          id="remember"
          v-model="form.remember"
          type="checkbox"
          class="auth-checkbox"
        />
        <label for="remember" class="auth-checkbox-label">Keep me signed in for 30 days</label>
      </div>

      <button
        type="submit"
        class="btn btn-primary btn-block"
        :disabled="form.processing"
      >{{ form.processing ? 'Signing in…' : 'Sign in' }}</button>

    </form>

    <p class="auth-altline">
      Don't have an account?
      <a :href="route('register')" class="auth-altline-link">Create Account</a>
    </p>

  </AuthLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'

const form = useForm({
  email:    '',
  password: '',
  remember: false,
})

function submit() {
  form.post(route('login.store'), {
    onFinish: () => form.reset('password'),
  })
}
</script>
