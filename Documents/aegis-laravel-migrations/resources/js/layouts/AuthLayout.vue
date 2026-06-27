<!--
  AuthLayout.vue — minimal chrome for login / register / reset flows.

  No sidebar, no header. Branded background image + centered card.
  Mirrors login.php from the PHP prototype.
-->
<template>
  <div class="auth-shell">
    <div class="auth-bg" aria-hidden="true"></div>

    <div class="auth-container">
      <div class="auth-brand">
        <img src="/aegis-favicon.svg" alt="" class="auth-brand-mark" />
        <span class="auth-brand-name">Aegis</span>
      </div>

      <div class="auth-card">
        <slot />
      </div>

      <div class="auth-footer">
        <a :href="route('public.home')" class="auth-footer-link">About Aegis</a>
        <span class="auth-footer-sep">·</span>
        <a :href="route('pricing')" class="auth-footer-link">Pricing</a>
        <span class="auth-footer-sep">·</span>
        <a :href="route('support.public')" class="auth-footer-link">Support</a>
      </div>
    </div>

    <!-- Toast stack also available on auth pages -->
    <div class="toast-container">
      <AegisToast
        v-for="toast in ui.toastQueue"
        :key="toast.id"
        :message="toast.message"
        :level="toast.level"
        @dismiss="ui.dismissToast(toast.id)"
      />
    </div>
  </div>
</template>

<script setup>
import { useUiStore } from '@/stores/ui'
const ui = useUiStore()
</script>
