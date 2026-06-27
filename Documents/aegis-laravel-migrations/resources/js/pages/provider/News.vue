<!--
  pages/provider/News.vue — news & resources feed.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Explore"
      title="News &amp; Resources"
      subtitle="Curated reading for continuity-minded practitioners."
    />

    <div class="news-grid">
      <article v-for="n in articles" :key="n.id" class="news-card">
        <a :href="n.url" class="news-card-link">
          <div class="news-card-cover" v-if="n.cover_url">
            <img :src="n.cover_url" :alt="n.title" />
          </div>
          <div class="news-card-body">
            <div class="news-card-tag">{{ n.category }}</div>
            <h3 class="news-card-title">{{ n.title }}</h3>
            <p class="news-card-excerpt">{{ n.excerpt }}</p>
            <div class="news-card-meta">
              <AegisIcon name="calendar" :size="12" />
              <span>{{ activity.timeAgo(n.published_at) }}</span>
              <span>·</span>
              <span>{{ n.read_minutes }} min read</span>
            </div>
          </div>
        </a>
      </article>
    </div>

    <AegisEmptyState
      v-if="!articles.length"
      icon="megaphone"
      title="No articles yet"
      description="When the team publishes resources, they'll appear here."
    />
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import { useActivity } from '@/composables/useActivity'

defineProps({ articles: { type: Array, default: () => [] } })
const activity = useActivity()
</script>
