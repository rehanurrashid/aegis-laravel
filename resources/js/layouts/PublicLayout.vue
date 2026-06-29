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
      <a :href="route('home')" class="public-brand-link">
        <span class="public-brand-name">Aegis</span>
      </a>
      <nav class="public-header-nav">
        <a :href="route('about')"   class="public-header-link">About</a>
        <a :href="route('pricing')" class="public-header-link">Pricing</a>
        <a :href="route('contact')" class="public-header-link">Contact</a>
        <a :href="route('login')"   class="public-header-link">Sign in</a>
        <a :href="route('register')" class="btn btn-sm btn-primary">Get started</a>
      </nav>
    </header>

    <main class="public-main">
      <slot />
    </main>

    <footer class="public-footer">
      <div class="public-footer-inner">
        <div class="public-footer-brand">
          <span>Powered by Aegis</span>
        </div>
        <div class="public-footer-links">
          <a :href="route('about')">About</a>
          <a :href="route('pricing')">Pricing</a>
          <a :href="route('contact')">Contact</a>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
// Public layout: no auth, no Pinia subscriptions.
</script>
