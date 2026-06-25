<!--
  pages/shared/Overview.vue — "Start Here" reference page.

  Replaces _shared/templates/overview.php. Used by all 5 portals. Tabs:
  Key Terms · Why Aegis · How to Use · FAQ. Content arrives from the
  OverviewController scoped to the viewer's portal.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Getting started"
      title="Overview — Start Here"
      :subtitle="content.hero_sub ?? 'A short orientation to Aegis for your role.'"
    />

    <!-- Tabs -->
    <nav class="tab-strip" role="tablist">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        type="button"
        role="tab"
        :aria-selected="active === tab.key"
        class="tab-btn"
        :class="{ 'is-active': active === tab.key }"
        @click="active = tab.key"
      >
        <AegisIcon :name="tab.icon" :size="14" />
        <span>{{ tab.label }}</span>
      </button>
    </nav>

    <!-- Panes -->
    <section v-show="active === 'terms'" class="overview-pane">
      <div class="overview-terms-grid">
        <article
          v-for="term in content.key_terms"
          :key="term.term"
          class="overview-term-card"
        >
          <div class="overview-term-name">{{ term.term }}</div>
          <div class="overview-term-def">{{ term.definition }}</div>
        </article>
      </div>
    </section>

    <section v-show="active === 'why'" class="overview-pane">
      <div class="overview-why-grid">
        <AegisCard
          v-for="card in content.why_cards"
          :key="card.title"
          :title="card.title"
        >
          <div class="overview-why-icon">
            <AegisIcon :name="card.icon" :size="24" />
          </div>
          <p class="overview-why-body">{{ card.body }}</p>
        </AegisCard>
      </div>
    </section>

    <section v-show="active === 'how'" class="overview-pane">
      <ol class="overview-steps">
        <li
          v-for="(step, i) in content.how_steps"
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

    <section v-show="active === 'faq'" class="overview-pane">
      <div class="overview-faq">
        <details
          v-for="faq in content.faqs"
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
import AegisCard from '@/components/ui/AegisCard.vue'

defineProps({
  content: {
    type: Object,
    default: () => ({ key_terms: [], why_cards: [], how_steps: [], faqs: [], hero_sub: '' }),
  },
  portal: { type: String, default: '' },
})

const tabs = [
  { key: 'terms', label: 'Key Terms',  icon: 'book-open' },
  { key: 'why',   label: 'Why Aegis',  icon: 'sparkles' },
  { key: 'how',   label: 'How to Use', icon: 'compliance' },
  { key: 'faq',   label: 'FAQ',        icon: 'help-circle' },
]

const active = ref('terms')
</script>
