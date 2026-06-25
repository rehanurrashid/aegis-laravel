<!--
  pages/auth/ResetPassword.vue — set a new password with the reset token.

  Route: POST password.update
  Props: token (from URL), email (from query string, pre-filled)
  Source: login.php + onboarding.php password section
  Layout: AuthLayout
-->
<template>
  <AuthLayout>
    <Head title="Set New Password" />

    <div class="auth-heading">
      <div class="auth-eyebrow">Account Recovery</div>
      <h1 class="auth-title">Set a New Password</h1>
      <p class="auth-subtitle">Choose a strong password for your account.</p>
    </div>

    <form @submit.prevent="submit" novalidate>

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
        <div class="rp-password-wrap">
          <input
            id="rp-password"
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            class="form-input"
            :class="{ 'is-error': form.errors.password }"
            autocomplete="new-password"
            placeholder="Create a strong password"
            @input="checkPasswordStrength"
          />
          <button
            type="button"
            class="rp-password-toggle"
            :aria-label="showPassword ? 'Hide password' : 'Show password'"
            @click="showPassword = !showPassword"
          >
            <AegisIcon :name="showPassword ? 'eye-off' : 'eye'" :size="15" />
          </button>
        </div>
        <div v-if="form.errors.password" class="form-error">{{ form.errors.password }}</div>
        <!-- Password requirements -->
        <div class="rp-password-reqs">
          <div class="rp-req-item" :class="{ valid: reqs.length, invalid: form.password && !reqs.length }">
            <AegisIcon :name="reqs.length ? 'check-circle' : 'x-circle'" :size="11" />
            8+ characters
          </div>
          <div class="rp-req-item" :class="{ valid: reqs.uppercase, invalid: form.password && !reqs.uppercase }">
            <AegisIcon :name="reqs.uppercase ? 'check-circle' : 'x-circle'" :size="11" />
            Uppercase
          </div>
          <div class="rp-req-item" :class="{ valid: reqs.number, invalid: form.password && !reqs.number }">
            <AegisIcon :name="reqs.number ? 'check-circle' : 'x-circle'" :size="11" />
            Number
          </div>
          <div class="rp-req-item" :class="{ valid: reqs.special, invalid: form.password && !reqs.special }">
            <AegisIcon :name="reqs.special ? 'check-circle' : 'x-circle'" :size="11" />
            Special char
          </div>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="rp-confirm">Confirm New Password</label>
        <div class="rp-password-wrap">
          <input
            id="rp-confirm"
            v-model="form.password_confirmation"
            :type="showConfirm ? 'text' : 'password'"
            class="form-input"
            :class="{ 'is-error': passwordMismatch }"
            autocomplete="new-password"
            placeholder="Re-enter your password"
          />
          <button
            type="button"
            class="rp-password-toggle"
            :aria-label="showConfirm ? 'Hide password' : 'Show password'"
            @click="showConfirm = !showConfirm"
          >
            <AegisIcon :name="showConfirm ? 'eye-off' : 'eye'" :size="15" />
          </button>
        </div>
        <div v-if="passwordMismatch" class="form-error">
          <AegisIcon name="x-circle" :size="11" />
          Passwords do not match
        </div>
      </div>

      <!-- Token validation error -->
      <div v-if="form.errors.token" class="auth-alert auth-alert--error">
        <AegisIcon name="alert-circle" :size="15" />
        <span>{{ form.errors.token }}</span>
      </div>

      <button
        type="submit"
        class="btn btn-primary btn-block"
        :disabled="form.processing || passwordMismatch"
      >{{ form.processing ? 'Saving…' : 'Save New Password' }}</button>

    </form>

    <p class="auth-altline">
      <a :href="route('login')" class="auth-altline-link">Back to Sign In</a>
    </p>

  </AuthLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'

const props = defineProps({
  token: { type: String, required: true },
  email: { type: String, default: '' },
})

const form = useForm({
  token:                 props.token,
  email:                 props.email,
  password:              '',
  password_confirmation: '',
})

const showPassword = ref(false)
const showConfirm  = ref(false)

const reqs = reactive({
  length:    false,
  uppercase: false,
  number:    false,
  special:   false,
})

const passwordMismatch = computed(() =>
  form.password_confirmation.length > 0 &&
  form.password !== form.password_confirmation
)

function checkPasswordStrength() {
  const pw = form.password
  reqs.length    = pw.length >= 8
  reqs.uppercase = /[A-Z]/.test(pw)
  reqs.number    = /[0-9]/.test(pw)
  reqs.special   = /[^A-Za-z0-9]/.test(pw)
}

function submit() {
  form.post(route('password.update'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<style scoped>
.rp-password-wrap {
  position: relative;
}

.rp-password-toggle {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  padding: 4px;
  cursor: pointer;
  color: var(--text-2);
  display: flex;
  align-items: center;
  transition: color var(--transition);
}

.rp-password-toggle:hover { color: var(--gold-dark); }

.rp-password-reqs {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 14px;
  margin-top: 10px;
}

.rp-req-item {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  color: var(--text-4);
  transition: color var(--transition);
}

.rp-req-item.valid   { color: var(--green); }
.rp-req-item.invalid { color: var(--red); }

/* Pill buttons */
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
