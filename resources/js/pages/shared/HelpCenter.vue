<!--
  pages/shared/HelpCenter.vue — standalone Help Center page.
  Used by all portals. Receives articles filtered by role from
  the portal's SupportController::help() method.
  Props: articles (Array)
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      quiet
      eyebrow="Account"
      title="Help Center"
      subtitle="Browse help articles and guides for your role."
    >
      <template #actions>
        <a :href="route('support.index')" class="btn-hero-ghost is-on-light">
          <AegisIcon name="arrow-left" :size="14" />
          Back to Support
        </a>
      </template>
    </AegisHeroBanner>

    <div class="stat-chips-row">
      <AegisStatChip
        icon="book-open"
        :value="articles.length"
        label="Articles"
      />
      <AegisStatChip
        icon="search"
        :value="filteredArticles.length"
        label="Matching"
        bg-color="var(--badge-bg-blue)"
        icon-color="var(--blue-dark)"
      />
    </div>

    <!-- Search -->
    <div class="help-search-bar">
      <div class="help-search-inner">
        <AegisIcon name="search" :size="16" />
        <input
          v-model="query"
          type="text"
          class="help-search-input"
          placeholder="Search help articles…"
          aria-label="Search help articles"
        />
        <button
          v-if="query"
          type="button"
          class="btn-icon btn-icon-sm"
          data-tooltip="Clear search"
          @click="query = ''"
        >
          <AegisIcon name="x" :size="12" />
        </button>
      </div>
    </div>

    <!-- Articles -->
    <div v-if="filteredArticles.length" class="help-articles-grid">
      <a
        v-for="a in filteredArticles"
        :key="a.id"
        :href="route('help.index')"
        class="help-article-card"
        :data-tooltip="a.summary ?? a.excerpt ?? ''"
      >
        <div class="help-article-icon">
          <AegisIcon :name="a.icon ?? 'book-open'" :size="18" />
        </div>
        <div class="help-article-body">
          <div class="help-article-title">{{ a.title }}</div>
          <div class="help-article-snippet">{{ a.summary ?? a.excerpt }}</div>
        </div>
        <AegisIcon name="chevron-right" :size="14" class="help-article-arrow" />
      </a>
    </div>

    <!-- Empty state -->
    <div v-else class="empty-state">
      <div class="empty-state-icon">
        <AegisIcon name="book" :size="28" />
      </div>
      <div class="empty-state-title">
        {{ query ? 'No matching articles' : 'No articles yet' }}
      </div>
      <div class="empty-state-sub">
        {{ query ? 'Try a broader search, or open a support ticket.' : 'Check back soon — articles are added regularly.' }}
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisHeroBanner from '@/components/ui/AegisHeroBanner.vue'
import AegisStatChip from '@/components/ui/AegisStatChip.vue'

const props = defineProps({
  articles: { type: Array, default: () => [] },
})

const query = ref('')

const filteredArticles = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) return props.articles
  return props.articles.filter((a) =>
    `${a.title ?? ''} ${a.summary ?? ''} ${a.excerpt ?? ''}`.toLowerCase().includes(q),
  )
})
</script>

<style scoped>
.help-search-bar {
  margin: 1.25rem 0;
}
.help-search-inner {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.65rem 1rem;
  background: var(--surface);
  border: 1px solid var(--border-dark);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  color: var(--text-3);
}
.help-search-input {
  flex: 1;
  border: none;
  outline: none;
  background: transparent;
  font-size: 14px;
  color: var(--text);
  font-family: inherit;
}
.help-search-input::placeholder { color: var(--text-4); }

.help-articles-grid {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.help-article-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.9rem 1.1rem;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  text-decoration: none;
  color: var(--text);
  transition: box-shadow var(--transition-fast), transform var(--transition-fast);
}
.help-article-card:hover {
  box-shadow: var(--shadow);
  transform: translateY(-1px);
}
.help-article-icon {
  width: 40px;
  height: 40px;
  border-radius: var(--radius-sm);
  background: var(--badge-bg-gold);
  color: var(--gold-dark);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.help-article-body { flex: 1; min-width: 0; }
.help-article-title {
  font-weight: 600;
  font-size: 14px;
  color: var(--text);
  margin-bottom: 0.2rem;
}
.help-article-snippet {
  font-size: 12px;
  color: var(--text-3);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.help-article-arrow { color: var(--text-4); flex-shrink: 0; }
</style>
