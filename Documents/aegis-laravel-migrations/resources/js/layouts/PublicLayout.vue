<!--
  PublicLayout.vue — chrome for public profile pages.

  Used by /providers/{slug}, /continuity-stewards/{slug},
  /support-stewards/{slug}, /businesses/{slug}.

  Mirrors _shared/public_chrome.php — slim top bar, footer with the
  "Powered by Aegis" mark. No sidebar; no auth required.
-->
<template>
  <div class="public-shell">
    <header class="public-header">
      <a :href="route('public.home')" class="public-brand-link">
        <img src="/aegis-favicon.svg" alt="" class="public-brand-mark" />
        <span class="public-brand-name">Aegis</span>
      </a>
      <nav class="public-header-nav">
        <a :href="route('pricing')" class="public-header-link">Pricing</a>
        <a :href="route('login')" class="public-header-link">Sign in</a>
        <a :href="route('register')" class="btn btn-sm btn-primary">Get started</a>
      </nav>
    </header>

    <main class="public-main">
      <slot />
    </main>

    <footer class="public-footer">
      <div class="public-footer-inner">
        <div class="public-footer-brand">
          <img src="/aegis-favicon.svg" alt="" />
          <span>Powered by Aegis</span>
        </div>
        <div class="public-footer-links">
          <a :href="route('public.home')">About</a>
          <a :href="route('pricing')">Pricing</a>
          <a :href="route('support.public')">Support</a>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
// Public layout: no auth, no Pinia subscriptions.
</script>
