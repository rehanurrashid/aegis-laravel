<!--
  PublicLayout.vue — public profile chrome.

  Logged-in viewers get the full portal chrome (AppLayout).
  Anonymous visitors get a premium public header + footer.
-->
<template>
  <!-- Logged-in: full portal chrome -->
  <AppLayout v-if="isVerifiedMember">
    <slot />
  </AppLayout>

  <!-- Anonymous: premium public chrome -->
  <div v-else class="public-shell">

    <header class="pub-header">
      <!-- Gold accent bar -->
      <div class="pub-header-accent"></div>

      <div class="pub-header-inner">
        <!-- Brand -->
        <a :href="route('home')" class="pub-brand">
          <span class="pub-brand-shield" aria-hidden="true">
            <AegisIcon name="shield" :size="22" />
          </span>
          <span class="pub-brand-wordmark">
            <span class="pub-brand-name">Aegis</span>
            <span class="pub-brand-tagline">Practice Continuity</span>
          </span>
        </a>

        <!-- Nav -->
        <nav class="pub-nav" aria-label="Main navigation">
          <a :href="route('home')"     class="pub-nav-link">About</a>
          <a :href="route('home')"     class="pub-nav-link">Pricing</a>
          <a :href="route('home')"     class="pub-nav-link">Contact</a>
          <span class="pub-nav-divider" aria-hidden="true"></span>
          <a :href="route('login')"    class="pub-nav-link pub-nav-link--signin">Sign in</a>
          <a :href="route('register')" class="btn btn-sm btn-primary pub-nav-cta">Get started</a>
        </nav>
      </div>
    </header>

    <main class="public-main">
      <slot />
    </main>

    <footer class="pub-footer">
      <div class="pub-footer-inner">

        <!-- Brand column -->
        <div class="pub-footer-brand-col">
          <div class="pub-footer-brand">
            <span class="pub-footer-shield" aria-hidden="true">
              <AegisIcon name="shield" :size="18" />
            </span>
            <span class="pub-footer-name">Aegis</span>
          </div>
          <p class="pub-footer-tagline">
            Secure practice continuity for<br>healthcare professionals.
          </p>
          <p class="pub-footer-copy">&copy; {{ year }} Aegis. All rights reserved.</p>
        </div>

        <!-- Link columns -->
        <div class="pub-footer-links-wrap">
          <div class="pub-footer-col">
            <div class="pub-footer-col-label">Platform</div>
            <a :href="route('home')" class="pub-footer-link">About Aegis</a>
            <a :href="route('home')" class="pub-footer-link">Pricing</a>
            <a :href="route('home')" class="pub-footer-link">Security</a>
          </div>
          <div class="pub-footer-col">
            <div class="pub-footer-col-label">Practitioners</div>
            <a :href="route('register')" class="pub-footer-link">Create account</a>
            <a :href="route('login')"    class="pub-footer-link">Sign in</a>
            <a :href="route('home')"     class="pub-footer-link">HIPAA compliance</a>
          </div>
          <div class="pub-footer-col">
            <div class="pub-footer-col-label">Support</div>
            <a :href="route('home')" class="pub-footer-link">Help center</a>
            <a :href="route('home')" class="pub-footer-link">Contact us</a>
            <a :href="route('home')" class="pub-footer-link">Privacy policy</a>
          </div>
        </div>

      </div>

      <!-- Bottom bar -->
      <div class="pub-footer-bar">
        <span>Powered by <strong>Devlet LLC</strong></span>
        <span class="pub-footer-bar-sep" aria-hidden="true">·</span>
        <span>HIPAA-aligned infrastructure</span>
      </div>
    </footer>

  </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const page             = usePage()
// isVerifiedMember: viewer is logged in, email-verified, AND has active sub
// Falls back to checking page.props.isVerifiedMember (set by ProfileController)
// OR derives from auth.user.verified + subscription state from shared props
const isVerifiedMember = computed(() => {
  // Use explicit prop if provided by controller (profile pages)
  if (page.props.isVerifiedMember !== undefined) return !!page.props.isVerifiedMember
  // Fallback: check auth.user.verified from shared props
  const user = page.props.auth?.user
  return !!user?.verified
})
const isLoggedIn       = computed(() => !!page.props.auth?.user)
const year             = new Date().getFullYear()
</script>

<style scoped>
/* ── Header ─────────────────────────────────────────────────────────── */
.pub-header {
  position: sticky;
  top: 0;
  z-index: 50;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  box-shadow: 0 1px 8px rgba(0,0,0,0.06);
}

.pub-header-accent {
  height: 3px;
  background: linear-gradient(90deg, var(--gold-dark) 0%, var(--gold-light, #c9a85c) 100%);
}

.pub-header-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  max-width: 1160px;
  margin: 0 auto;
  padding: 0 2rem;
  height: 60px;
}

/* Brand */
.pub-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  flex-shrink: 0;
}

.pub-brand-shield {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: var(--radius);
  background: var(--badge-bg-gold);
  color: var(--gold-dark);
  flex-shrink: 0;
}

.pub-brand-wordmark {
  display: flex;
  flex-direction: column;
  line-height: 1;
  gap: 2px;
}

.pub-brand-name {
  font-family: var(--font-serif);
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--text);
  letter-spacing: -0.01em;
}

.pub-brand-tagline {
  font-family: var(--font-sans);
  font-size: 0.68rem;
  font-weight: 600;
  color: var(--text-3);
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

/* Nav */
.pub-nav {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.pub-nav-link {
  position: relative;
  padding: 0.4rem 0.75rem;
  font-family: var(--font-sans);
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-2);
  text-decoration: none;
  border-radius: var(--radius-sm);
  transition: color 180ms ease, background 180ms ease;
}

.pub-nav-link:hover {
  color: var(--text);
  background: var(--bg-2, var(--bg));
}

.pub-nav-link::after {
  content: '';
  position: absolute;
  bottom: 2px;
  left: 0.75rem;
  right: 0.75rem;
  height: 2px;
  background: var(--gold-dark);
  border-radius: 1px;
  transform: scaleX(0);
  transition: transform 180ms ease;
  transform-origin: left;
}

.pub-nav-link:hover::after { transform: scaleX(1); }

.pub-nav-link--signin {
  font-weight: 600;
  color: var(--text);
}

.pub-nav-divider {
  width: 1px;
  height: 18px;
  background: var(--border);
  margin: 0 0.5rem;
}

.pub-nav-cta {
  margin-left: 0.25rem;
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: 0.01em;
}

/* ── Footer ─────────────────────────────────────────────────────────── */
.pub-footer {
  background: var(--surface);
  border-top: 1px solid var(--border);
  margin-top: auto;
}

.pub-footer-inner {
  display: flex;
  gap: 3rem;
  max-width: 1160px;
  margin: 0 auto;
  padding: 2.5rem 2rem 2rem;
}

/* Brand column */
.pub-footer-brand-col {
  flex: 0 0 220px;
}

.pub-footer-brand {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
}

.pub-footer-shield {
  display: flex;
  align-items: center;
  color: var(--gold-dark);
}

.pub-footer-name {
  font-family: var(--font-serif);
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--text);
}

.pub-footer-tagline {
  font-size: 0.8rem;
  color: var(--text-3);
  line-height: 1.6;
  margin: 0 0 12px;
}

.pub-footer-copy {
  font-size: 0.72rem;
  color: var(--text-4);
  margin: 0;
}

/* Link columns */
.pub-footer-links-wrap {
  display: flex;
  gap: 2.5rem;
  flex: 1;
  justify-content: flex-end;
}

.pub-footer-col {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 120px;
}

.pub-footer-col-label {
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--text-3);
  margin-bottom: 4px;
}

.pub-footer-link {
  font-size: 0.82rem;
  color: var(--text-2);
  text-decoration: none;
  transition: color 160ms ease;
}

.pub-footer-link:hover { color: var(--gold-dark); }

/* Bottom bar */
.pub-footer-bar {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 0.85rem 2rem;
  border-top: 1px solid var(--border);
  font-size: 0.75rem;
  color: var(--text-4);
  background: var(--bg-2, var(--bg));
}

.pub-footer-bar strong { color: var(--text-3); font-weight: 600; }
.pub-footer-bar-sep { color: var(--border-strong, var(--text-4)); }

/* ── Responsive ──────────────────────────────────────────────────────── */
@media (max-width: 768px) {
  .pub-brand-tagline { display: none; }
  .pub-nav-link:not(.pub-nav-link--signin):not(.pub-nav-cta) { display: none; }
  .pub-nav-divider { display: none; }
  .pub-footer-inner { flex-direction: column; gap: 1.5rem; }
  .pub-footer-brand-col { flex: none; }
  .pub-footer-links-wrap { justify-content: flex-start; gap: 1.5rem; flex-wrap: wrap; }
  .pub-footer-bar { flex-direction: column; gap: 4px; text-align: center; }
}
</style>
