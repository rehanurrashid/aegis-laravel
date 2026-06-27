<!--
  pages/shared/Overview.vue — "Start Here" orientation page.
  Used by all 5 portals. Content arrives from OverviewController
  scoped to the viewer's role. Tabs: Key Terms · Why Aegis · How to Use · FAQ.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      quiet
      eyebrow="Getting started"
      title="Overview — Start Here"
      subtitle="A short orientation to Aegis for your role."
    >
      <template #actions>
        <a
          :href="route('activity.index')"
          class="btn-hero-ghost is-on-light"
        >
          <AegisIcon name="activity" :size="14" />
          Activity
        </a>
      </template>
    </AegisHeroBanner>

    <!-- Tabs -->
    <nav class="tabs-segmented" role="tablist">
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
        <article
          v-for="item in keyTerms"
          :key="item.term"
          class="overview-term-card"
        >
          <div class="overview-term-name">{{ item.term }}</div>
          <div class="overview-term-def">{{ item.def }}</div>
        </article>
      </div>
    </section>

    <!-- Why Aegis -->
    <section v-show="active === 'why'" class="overview-pane">
      <div class="overview-why-grid">
        <div
          v-for="card in whyCards"
          :key="card.title"
          class="card overview-why-card"
        >
          <div class="overview-why-icon">
            <AegisIcon :name="card.icon ?? 'sparkles'" :size="22" />
          </div>
          <div class="card-title overview-why-title">{{ card.title }}</div>
          <p class="overview-why-body">{{ card.body }}</p>
        </div>
      </div>
    </section>

    <!-- How to Use -->
    <section v-show="active === 'how'" class="overview-pane">
      <ol class="overview-steps">
        <li
          v-for="(step, i) in howSteps"
          :key="step.title"
          class="overview-step"
        >
          <div class="overview-step-num">{{ i + 1 }}</div>
          <div class="overview-step-body">
            <div class="overview-step-title">{{ step.title }}</div>
            <div class="overview-step-desc">{{ step.body }}</div>
          </div>
        </li>
      </ol>
    </section>

    <!-- FAQ -->
    <section v-show="active === 'faq'" class="overview-pane">
      <div class="overview-faq">
        <details
          v-for="faq in faqs"
          :key="faq.q"
          class="overview-faq-item"
        >
          <summary class="overview-faq-q">
            <span>{{ faq.q }}</span>
            <AegisIcon name="chevron-down" :size="14" />
          </summary>
          <div class="overview-faq-a">{{ faq.a }}</div>
        </details>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisHeroBanner from '@/components/ui/AegisHeroBanner.vue'

defineProps({
  role:     { type: String, default: '' },
  keyTerms: { type: Array,  default: () => [] },
  whyCards: { type: Array,  default: () => [] },
  howSteps: { type: Array,  default: () => [] },
  faqs:     { type: Array,  default: () => [] },
})

const tabs = [
  { key: 'terms', label: 'Key Terms',  icon: 'book-open' },
  { key: 'why',   label: 'Why Aegis',  icon: 'sparkles' },
  { key: 'how',   label: 'How to Use', icon: 'check-circle' },
  { key: 'faq',   label: 'FAQ',        icon: 'help-circle' },
]

const active = ref('terms')
</script>

<style scoped>
.overview-pane {
  padding: 1.5rem 0;
}

/* Key terms */
.overview-terms-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
}
.overview-term-card {
  padding: 1rem 1.2rem;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
}
.overview-term-name {
  font-family: var(--font-serif);
  font-size: 15px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 0.35rem;
}
.overview-term-def {
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.55;
}

/* Why cards */
.overview-why-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 1rem;
}
.overview-why-card {
  display: flex;
  flex-direction: column;
  padding: 1.25rem;
  gap: 0.6rem;
}
.overview-why-icon {
  width: 44px;
  height: 44px;
  border-radius: var(--radius-sm);
  background: var(--badge-bg-gold);
  color: var(--gold-dark);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.overview-why-title {
  font-family: var(--font-serif);
  font-size: 15px;
  font-weight: 700;
  color: var(--text);
  margin: 0;
}
.overview-why-body {
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.55;
  margin: 0;
}

/* How steps */
.overview-steps {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.overview-step {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem 1.2rem;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
}
.overview-step-num {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-full);
  background: var(--gold-dark);
  color: var(--text-inverted);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-serif);
  font-size: 15px;
  font-weight: 700;
  flex-shrink: 0;
}
.overview-step-body { flex: 1; min-width: 0; }
.overview-step-title {
  font-family: var(--font-serif);
  font-size: 14px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 0.25rem;
}
.overview-step-desc {
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.5;
}

/* FAQ */
.overview-faq {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
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
.overview-faq-item[open] .overview-faq-q { border-bottom: 1px solid var(--border); }
.overview-faq-a {
  padding: 0.85rem 1.2rem;
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.6;
}
</style>
