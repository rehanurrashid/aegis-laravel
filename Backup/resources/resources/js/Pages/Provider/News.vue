<template>
  <AppLayout :user="user" portal="practitioner" activePage="news" pageTitle="News & Resources">
    <!-- HERO -->
    <div class="hero-banner is-quiet">
      <div class="page-hero-inner">
        <div class="page-hero-text">
          <div class="page-hero-eyebrow">Resources</div>
          <h1 class="page-hero-title">News &amp; Resources</h1>
          <p class="page-hero-sub">Stay informed with the latest healthcare continuity news and best practices.</p>
        </div>
        <div class="page-hero-actions"></div>
      </div>
    </div>

    <!-- STAT CHIPS -->
    <div class="stat-chips-row">
      <div v-for="s in stats" :key="s.label" class="stat-chip">
        <div class="stat-chip-icon" style="background:var(--icon-bg-gold);color:var(--gold-dark)" v-html="s.icon"></div>
        <div>
          <div class="stat-chip-value">{{ s.value }}</div>
          <div class="stat-chip-label">{{ s.label }}</div>
        </div>
      </div>
    </div>

    <!-- ARTICLE CARDS -->
    <div class="pg-grid">
      <article v-for="a in articles" :key="a.title" class="card pg-card">
        <div class="pg-card-head">
          <div class="pg-card-heading">
            <div class="pg-card-title">{{ a.title }}</div>
            <div class="pg-card-sub">{{ a.meta }}</div>
          </div>
          <span v-if="a.badge" class="badge badge-blue">{{ a.badge }}</span>
        </div>
        <div class="pg-card-body">{{ a.excerpt }}</div>
      </article>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '../../Components/AppLayout.vue';

defineProps({ user: Object });

const icon = (p) => `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${p}</svg>`;

const stats = [
  { value: '2', label: 'New Today', icon: icon('<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>') },
  { value: '7', label: 'Active Authors', icon: icon('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>') },
  { value: '9', label: 'Upcoming Events', icon: icon('<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>') },
];

const articles = [
  { title: 'New HIPAA Guidelines for Telehealth in 2025', meta: 'Published Jan 5, 2025 — Compliance', badge: 'New',
    excerpt: "Updated HIPAA requirements for telehealth platforms take effect March 2025. Here's what practitioners need to know about encryption and patient consent." },
  { title: 'Building a Resilient Practice: 5 Key Strategies', meta: 'Published Dec 18, 2024 — Best Practices',
    excerpt: 'Learn the top strategies for building practice resilience, from diversifying your network to maintaining up-to-date continuity documentation.' },
  { title: 'Aegis Platform Update v2.4', meta: 'Published Dec 10, 2024 — Product',
    excerpt: 'New features include enhanced incident cockpit, improved document vault search, and streamlined referral workflow. Check the release notes for details.' },
  { title: 'Managing Patient Transitions During Continuity Events', meta: 'Published Nov 28, 2024 — Clinical',
    excerpt: 'Expert guidance on managing patient care transitions when a practitioner becomes unexpectedly unavailable, including communication templates.' },
];
</script>

<style scoped>
.pg-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 28px;
}
.pg-card {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-width: 0;
}
.pg-card-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding: 18px 20px 0;
}
.pg-card-heading { min-width: 0; }
.pg-card-title {
  font-family: var(--font-serif);
  font-size: 15px;
  font-weight: 700;
  color: var(--text);
  line-height: 1.3;
  margin-bottom: 4px;
}
.pg-card-sub { font-size: 12px; color: var(--text-3); }
.pg-card-body {
  padding: 12px 20px 18px;
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.6;
  flex: 1;
}
@media (max-width: 900px) {
  .pg-grid { grid-template-columns: 1fr; }
}
</style>
