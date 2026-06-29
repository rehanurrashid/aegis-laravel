<!--
  PublicLayout.vue — public profile chrome.

  When the viewer is authenticated, delegates to AppLayout so they get
  the full portal sidebar + header (same as any other portal page).
  When anonymous, shows a slim public header + footer.
-->
<template>
  <!-- Logged-in: full portal chrome with sidebar + header -->
  <AppLayout v-if="isLoggedIn">
    <slot />
  </AppLayout>

  <!-- Anonymous: slim public chrome -->
  <div v-else class="public-shell">
    <header class="public-header">
      <a :href="route('home')" class="public-brand-link">
        <span class="public-brand-name">Aegis</span>
      </a>
      <nav class="public-header-nav">
        <a :href="route('home')" class="public-header-link">About</a>
        <a :href="route('home')" class="public-header-link">Pricing</a>
        <a :href="route('home')" class="public-header-link">Contact</a>
        <a :href="route('login')"    class="public-header-link">Sign in</a>
        <a :href="route('register')" class="btn btn-sm btn-primary">Get started</a>
      </nav>
    </header>

    <main class="public-main">
      <slot />
    </main>

    <footer class="public-footer">
      <div class="public-footer-inner">
        <div class="public-footer-brand"><span>Powered by Aegis</span></div>
        <div class="public-footer-links">
          <a :href="route('home')">About</a>
          <a :href="route('home')">Pricing</a>
          <a :href="route('home')">Contact</a>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const page = usePage()
const isLoggedIn = computed(() => !!page.props.auth?.user)
</script>
