<!--
  pages/public/About.vue — About Aegis & MAAT Practice Firm.
  Route: GET /about (about)
  Layout: PublicLayout (no auth required)
-->
<template>
  <Head title="About — Aegis" />
  <PublicLayout>

    <!-- Hero -->
    <section class="public-hero">
      <div class="public-hero-inner">
        <div class="public-hero-eyebrow">Our Mission</div>
        <h1 class="public-hero-title">Practice continuity, handled with care.</h1>
        <p class="public-hero-sub">
          Aegis is built for licensed practitioners who care about what happens to their
          clients when life intervenes. We make continuity planning accessible, structured,
          and stress-free.
        </p>
        <div class="public-hero-actions">
          <a :href="route('register')" class="btn btn-primary">Get Started</a>
          <a :href="route('contact')" class="btn btn-outline">Contact Us</a>
        </div>
      </div>
    </section>

    <!-- Mission cards -->
    <section class="public-section">
      <div class="public-section-inner">
        <div class="public-cards-grid">
          <div v-for="card in missionCards" :key="card.title" class="card">
            <div class="card-body">
              <div class="public-card-icon">
                <AegisIcon :name="card.icon" :size="24" />
              </div>
              <h3 class="card-title">{{ card.title }}</h3>
              <p class="public-card-body">{{ card.body }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- About MAAT -->
    <section class="public-section public-section--alt">
      <div class="public-section-inner public-about-grid">
        <div class="card">
          <div class="card-body">
            <div class="about-eyebrow">Who builds Aegis</div>
            <p class="public-section-body">
              Aegis is a SaaS platform from <strong>MAAT Practice Firm</strong>, a boutique
              practice-management firm founded by Dr. Carizma Chapman, PhD, DMFT. MAAT has
              served healthcare and social-service practitioners with continuity stewardship
              and operational support for years; Aegis delivers those services at scale.
            </p>
          </div>
        </div>
        <div class="card">
          <div class="card-body">
            <div class="about-eyebrow">Why it exists</div>
            <p class="public-section-body">
              When a solo or small-group practitioner becomes suddenly unavailable — through
              illness, death, missing, detainment, natural disaster, or geopolitical conflict
              — patients, records, billing credentials, and the practice itself can fall into
              chaos. A pre-signed continuity plan, executed by two pre-designated humans,
              prevents that.
            </p>
          </div>
        </div>
        <div class="card">
          <div class="card-body">
            <div class="about-eyebrow">What we don't do</div>
            <p class="public-section-body">
              Aegis is the operational console and system of record. We are <strong>not</strong>
              a legal service, a will, or a power-of-attorney. Your Continuity Plan complements
              those documents — it doesn't replace them. Aegis also never holds funds in transit;
              peer-to-peer payments route directly via Stripe Connect.
            </p>
          </div>
        </div>
        <div class="card">
          <div class="card-body">
            <div class="about-eyebrow">Security &amp; compliance</div>
            <p class="public-section-body">
              HIPAA-aligned data handling. Secure Credentials zone uses AES-256-GCM envelope
              encryption with keys held outside the database. The Document Vault stays sealed
              until a critical incident is verified — and every access is logged for legal audit.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact strip -->
    <section class="public-section">
      <div class="public-section-inner public-two-col">
        <div>
          <h2 class="public-section-title">Built by MAAT Practice Firm</h2>
          <p class="public-section-body">
            Every feature in Aegis reflects real-world practitioner needs, shaped by years of
            experience in clinical practice management. Questions or partnership inquiries are
            always welcome.
          </p>
          <a :href="route('contact')" class="btn btn-primary">Send a message</a>
        </div>
        <div class="card">
          <div class="card-body">
            <h3 class="card-title">Get in touch</h3>
            <div class="contact-info-list">
              <div class="contact-info-item">
                <div class="contact-info-icon">
                  <AegisIcon name="phone" :size="18" />
                </div>
                <div class="contact-info-body">
                  <div class="contact-info-label">Phone</div>
                  <a href="tel:+19094889415" class="contact-info-value">(909) 488-9415</a>
                </div>
              </div>
              <div class="contact-info-item">
                <div class="contact-info-icon">
                  <AegisIcon name="mail" :size="18" />
                </div>
                <div class="contact-info-body">
                  <div class="contact-info-label">Email</div>
                  <a href="mailto:support@maatpracticefirm.com" class="contact-info-value">
                    support@maatpracticefirm.com
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

  </PublicLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import PublicLayout from '@/layouts/PublicLayout.vue'

const missionCards = [
  {
    icon:  'shield',
    title: 'Continuity Planning',
    body:  'Create and maintain a structured continuity plan that protects your clients if you become suddenly unavailable. Document all seven critical-incident types in about 20 minutes.',
  },
  {
    icon:  'users',
    title: 'Trusted Stewards',
    body:  'Designate a Continuity Steward who verifies and executes your plan, and a Support Steward — family or staff — who monitors and triggers the alert. Both portals are covered by your subscription.',
  },
  {
    icon:  'lock',
    title: 'Secure Vault',
    body:  'Store critical credentials and documents in an encrypted vault, sealed until an active incident is verified. Every access is logged for legal defensibility.',
  },
]
</script>

<style scoped>
.about-eyebrow {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.4px;
  text-transform: uppercase;
  color: var(--gold-dark);
  margin-bottom: 10px;
}

.public-about-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

@media (max-width: 720px) {
  .public-about-grid {
    grid-template-columns: 1fr;
  }
}
</style>
