<!--
  pages/shared/Overview.vue — "Start Here" orientation page for all five portals.
  Content (hero, notice, checklist, terms, why, how, FAQ) arrives from OverviewController
  scoped to the viewer's role. Mirrors the legacy PHP overview template structure:
  hero + setup checklist · notice band · tabs (terms / why / how / FAQ) · faq-support CTA.
-->
<template>
  <AppLayout>
    <!-- ── HERO BAND with setup checklist tiles ─────────────── -->
    <AegisHeroBanner
      quiet
      :eyebrow="heroEyebrow"
      :title="`Hello, ${firstName}.`"
      :subtitle="heroSub"
    >
    </AegisHeroBanner>

    <!-- Setup checklist strip — only when there are items -->
    <div v-if="checklist.length" class="overview-setup">
      <div class="overview-setup-head">
        <span class="overview-setup-label">Your Setup Path</span>
        <div class="overview-setup-progress" aria-hidden="true">
          <div class="overview-setup-progress-fill" :style="{ width: setupPct + '%' }"></div>
        </div>
        <span class="overview-setup-counter">
          {{ setupDone }}/{{ checklist.length }} <span class="overview-setup-counter-faded">complete</span>
        </span>
      </div>
      <div class="overview-setup-grid" :style="{ '--ov-tiles': checklist.length }">
        <a
          v-for="(tile, i) in checklist"
          :key="tile.label"
          :href="tile.href || '#'"
          class="overview-setup-tile"
          :class="{ 'is-done': tile.done, 'is-next': nextIndex === i }"
        >
          <div class="overview-setup-tile-row">
            <span class="overview-setup-num">
              <AegisIcon v-if="tile.done" name="check" :size="12" />
              <template v-else>{{ String(i + 1).padStart(2, '0') }}</template>
            </span>
            <span class="overview-setup-tile-label">{{ tile.label }}</span>
          </div>
          <span class="overview-setup-tile-desc">{{ tile.desc }}</span>
        </a>
      </div>
    </div>

    <!-- Notice band — only when the controller provides notice text -->
    <div v-if="notice" class="overview-notice">
      <span class="overview-notice-icon">
        <AegisIcon name="alert-triangle" :size="18" />
      </span>
      <!-- eslint-disable-next-line vue/no-v-html -->
      <div class="overview-notice-text" v-html="notice"></div>
    </div>

    <!-- ── Tabs ─────────────────────────────────────── -->
    <nav class="tabs-segmented" role="tablist" style="margin-top: 1.5rem;">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        type="button"
        role="tab"
        :aria-selected="active === tab.key"
        class="tab-pill"
        :class="{ active: active === tab.key }"
        @click="active = tab.key"
      >
        <AegisIcon :name="tab.icon" :size="12" />
        <span>{{ tab.label }}</span>
      </button>
    </nav>

    <!-- Key Terms -->
    <section v-show="active === 'terms'" class="overview-pane">
      <div class="overview-terms-grid">
        <article v-for="item in keyTerms" :key="item.term" class="overview-term-card">
          <div class="overview-term-name">{{ item.term }}</div>
          <div class="overview-term-def">{{ item.def }}</div>
        </article>
      </div>
    </section>

    <!-- Why Aegis -->
    <section v-show="active === 'why'" class="overview-pane">
      <div class="overview-why-grid">
        <article
          v-for="(card, i) in whyCards"
          :key="card.title"
          class="overview-why-card"
        >
          <div class="overview-why-num">
            <span>{{ String(i + 1).padStart(2, '0') }}</span>
          </div>
          <div class="overview-why-title">{{ card.title }}</div>
          <p class="overview-why-body">{{ card.body }}</p>
        </article>
      </div>
    </section>

    <!-- How to Use — timeline -->
    <section v-show="active === 'how'" class="overview-pane">
      <div class="overview-how-wrap">
        <div class="overview-how-steps">
          <div
            v-for="(step, i) in howSteps"
            :key="step.title"
            class="overview-how-step"
          >
            <div class="overview-how-num">{{ i + 1 }}</div>
            <div class="overview-how-content">
              <div class="overview-how-title">{{ step.title }}</div>
              <p class="overview-how-desc">{{ step.body }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section v-show="active === 'faq'" class="overview-pane">
      <div class="overview-faq-grid">
        <details
          v-for="faq in faqs"
          :key="faq.q"
          class="overview-faq-item"
        >
          <summary class="overview-faq-q">
            <span>{{ faq.q }}</span>
            <span class="overview-faq-caret"><AegisIcon name="chevron-down" :size="14" /></span>
          </summary>
          <div class="overview-faq-a">{{ faq.a }}</div>
        </details>
      </div>

      <div class="overview-faq-support">
        <span class="overview-faq-support-icon">
          <AegisIcon name="help-circle" :size="22" />
        </span>
        <div class="overview-faq-support-text">
          <div class="overview-faq-support-title">Still have questions?</div>
          <div class="overview-faq-support-sub">Our team responds within one business day, sooner during a critical moment.</div>
        </div>
        <a :href="route('messages.index')" class="overview-faq-support-cta">
          Contact Support
          <AegisIcon name="arrow-right" :size="12" />
        </a>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisHeroBanner from '@/components/ui/AegisHeroBanner.vue'

const props = defineProps({
  role:        { type: String, default: '' },
  firstName:   { type: String, default: 'there' },
  heroEyebrow: { type: String, default: 'Welcome to Aegis' },
  heroSub:     { type: String, default: '' },
  notice:      { type: String, default: '' },
  checklist:   { type: Array,  default: () => [] },
  keyTerms:    { type: Array,  default: () => [] },
  whyCards:    { type: Array,  default: () => [] },
  howSteps:    { type: Array,  default: () => [] },
  faqs:        { type: Array,  default: () => [] },
})

const tabs = [
  { key: 'terms', label: 'Key Terms',  icon: 'book-open' },
  { key: 'why',   label: 'Why Aegis',  icon: 'shield' },
  { key: 'how',   label: 'How to Use', icon: 'clock' },
  { key: 'faq',   label: 'FAQ',        icon: 'help-circle' },
]
const active = ref('terms')

const setupDone = computed(() => props.checklist.filter(t => t.done).length)
const setupPct  = computed(() => {
  if (!props.checklist.length) return 0
  return Math.round((setupDone.value / props.checklist.length) * 100)
})
const nextIndex = computed(() => props.checklist.findIndex(t => !t.done))
</script>

<style scoped>
/* ── Setup checklist (port of .ov-setup) ───────────────── */
.overview-setup {
  margin-top: 1.5rem;
  padding: 1.25rem 1.375rem 1.125rem;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
}
.overview-setup-head {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  margin-bottom: 0.875rem;
  flex-wrap: wrap;
}
.overview-setup-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1.4px;
  text-transform: uppercase;
  color: var(--gold-dark);
}
.overview-setup-progress {
  flex: 1;
  height: 4px;
  background: var(--surface-3);
  border-radius: var(--radius-full);
  overflow: hidden;
  min-width: 160px;
  max-width: 260px;
}
.overview-setup-progress-fill {
  height: 100%;
  background: var(--gold-dark);
  border-radius: var(--radius-full);
  transition: width 0.5s ease;
}
.overview-setup-counter {
  font-family: var(--font-serif);
  font-size: 14px;
  font-weight: 600;
  color: var(--text);
}
.overview-setup-counter-faded { color: var(--text-4); font-weight: 500; }

.overview-setup-grid {
  display: grid;
  grid-template-columns: repeat(var(--ov-tiles, 5), 1fr);
  gap: 0.625rem;
}
.overview-setup-tile {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  padding: 0.75rem 0.875rem;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  text-decoration: none;
  color: inherit;
  position: relative;
  transition: border-color var(--transition-fast),
              background var(--transition-fast),
              transform var(--transition-fast);
}
.overview-setup-tile:hover {
  border-color: var(--gold);
  background: var(--surface);
  transform: translateY(-1px);
}
.overview-setup-tile-row { display: flex; align-items: center; gap: 0.5rem; }
.overview-setup-num {
  width: 22px;
  height: 22px;
  border-radius: var(--radius-full);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-serif);
  font-size: 11px;
  font-weight: 700;
  background: var(--surface-3);
  color: var(--text-3);
  flex-shrink: 0;
}
.overview-setup-tile.is-done .overview-setup-num {
  background: var(--green-dark);
  color: var(--text-inverted);
}
.overview-setup-tile.is-next .overview-setup-num {
  background: var(--gold-dark);
  color: var(--text-inverted);
  box-shadow: 0 0 0 3px var(--badge-bg-gold);
}
.overview-setup-tile-label {
  font-size: 12.5px;
  font-weight: 600;
  color: var(--text);
}
.overview-setup-tile-desc { font-size: 11px; color: var(--text-4); }
.overview-setup-tile.is-next::after {
  content: "Next";
  position: absolute;
  top: 0.5rem;
  right: 0.625rem;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.6px;
  text-transform: uppercase;
  color: var(--gold-dark);
}

@media (max-width: 1100px) {
  .overview-setup-grid { grid-template-columns: repeat(3, 1fr) !important; }
}
@media (max-width: 820px) {
  .overview-setup-grid { grid-template-columns: repeat(2, 1fr) !important; }
}
@media (max-width: 480px) {
  .overview-setup-grid { grid-template-columns: 1fr !important; }
}

/* ── Notice band ──────────────────────────────────────── */
.overview-notice {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.875rem 1.125rem;
  margin: 1.5rem 0 0;
  background: var(--badge-bg-gold);
  border: 1px solid var(--soft-gold);
  border-left: 4px solid var(--gold);
  border-radius: var(--radius-lg);
}
.overview-notice-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: var(--radius-full);
  background: var(--surface);
  color: var(--gold-dark);
  flex-shrink: 0;
}
.overview-notice-text {
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.6;
  flex: 1;
  min-width: 0;
}
.overview-notice-text :deep(strong) { color: var(--text); }
.overview-notice-text :deep(a) { color: var(--gold-dark); font-weight: 600; }

/* ── Tab panes ───────────────────────────────────────── */
.overview-pane { padding: 1.5rem 0; }

/* Key terms */
.overview-terms-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.875rem;
}
.overview-term-card {
  padding: 1.25rem 1.375rem;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
}
.overview-term-card:hover {
  border-color: var(--gold);
  box-shadow: var(--shadow);
}
.overview-term-name {
  font-family: var(--font-serif);
  font-size: 17px;
  font-weight: 700;
  color: var(--gold-dark);
  margin-bottom: 0.5rem;
  letter-spacing: 0.1px;
}
.overview-term-def {
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.65;
}

/* Why cards — numbered */
.overview-why-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}
.overview-why-card {
  padding: 1.5rem 1.5rem 1.375rem;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  transition: border-color var(--transition-fast), transform var(--transition-fast);
}
.overview-why-card:hover {
  border-color: var(--gold);
  transform: translateY(-2px);
}
.overview-why-num {
  font-family: var(--font-serif);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 1.4px;
  color: var(--gold-dark);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.875rem;
}
.overview-why-num::after {
  content: "";
  flex: 1;
  height: 1px;
  background: var(--border);
}
.overview-why-title {
  font-family: var(--font-serif);
  font-size: 17px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 0.5rem;
  line-height: 1.25;
}
.overview-why-body {
  font-size: 13px;
  color: var(--text-3);
  line-height: 1.7;
  margin: 0;
}

/* How to Use — timeline */
.overview-how-wrap {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 0.5rem 1.875rem;
}
.overview-how-steps {
  position: relative;
  padding: 0.5rem 0 0.5rem 0.875rem;
}
.overview-how-steps::before {
  content: "";
  position: absolute;
  left: 33px;
  top: 32px;
  bottom: 32px;
  width: 2px;
  background: var(--border);
}
.overview-how-step {
  display: flex;
  gap: 1.375rem;
  align-items: flex-start;
  padding: 1.375rem 0;
  border-bottom: 1px solid var(--border);
}
.overview-how-step:last-child { border-bottom: none; }
.overview-how-num {
  width: 38px;
  height: 38px;
  border-radius: var(--radius-full);
  background: var(--surface);
  border: 2px solid var(--border);
  color: var(--text-3);
  font-family: var(--font-serif);
  font-size: 15px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  position: relative;
  z-index: 2;
}
.overview-how-content { flex: 1; min-width: 0; }
.overview-how-title {
  font-family: var(--font-serif);
  font-size: 17px;
  font-weight: 700;
  color: var(--text);
  margin: 0.125rem 0 0.375rem;
}
.overview-how-desc {
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.65;
  margin: 0;
}

/* FAQ */
.overview-faq-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
  align-items: start;
}
.overview-faq-item {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}
.overview-faq-q {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.9rem 1.2rem;
  font-size: 14px;
  font-weight: 600;
  color: var(--text);
  cursor: pointer;
  list-style: none;
  user-select: none;
}
.overview-faq-q::-webkit-details-marker { display: none; }
.overview-faq-caret {
  display: inline-flex;
  align-items: center;
  color: var(--text-3);
  transition: transform var(--transition-fast);
}
.overview-faq-item[open] .overview-faq-caret { transform: rotate(180deg); }
.overview-faq-item[open] .overview-faq-q { border-bottom: 1px solid var(--border); }
.overview-faq-a {
  padding: 0.85rem 1.2rem;
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.6;
}

/* Still have questions? */
.overview-faq-support {
  display: flex;
  align-items: center;
  gap: 1.125rem;
  margin-top: 1.625rem;
  padding: 1.375rem 1.625rem;
  background: var(--surface);
  border: 1px solid var(--soft-gold);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
}
.overview-faq-support-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-full);
  background: var(--gold-dark);
  color: var(--text-inverted);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.overview-faq-support-text { flex: 1; min-width: 0; }
.overview-faq-support-title {
  font-family: var(--font-serif);
  font-size: 18px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 0.125rem;
}
.overview-faq-support-sub {
  font-size: 13px;
  color: var(--text-3);
  line-height: 1.5;
}
.overview-faq-support-cta {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.575rem 1.125rem;
  border-radius: var(--radius-full);
  background: var(--gold-dark);
  color: var(--text-inverted);
  font-size: 12.5px;
  font-weight: 700;
  text-decoration: none;
  flex-shrink: 0;
  transition: background var(--transition-fast);
}
.overview-faq-support-cta:hover { background: var(--gold); }

@media (max-width: 1100px) {
  .overview-why-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 820px) {
  .overview-terms-grid, .overview-why-grid, .overview-faq-grid { grid-template-columns: 1fr; }
  .overview-faq-support { flex-direction: column; align-items: flex-start; }
}
</style>
