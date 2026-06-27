<!--
  pages/provider/Events.vue — upcoming events list.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Explore"
      title="Events"
      subtitle="Webinars, workshops, and community gatherings."
    />

    <nav class="tab-strip">
      <button type="button" class="tab-btn" :class="{ 'is-active': tab === 'upcoming' }" @click="tab = 'upcoming'">
        <AegisIcon name="calendar" :size="14" /> Upcoming
      </button>
      <button type="button" class="tab-btn" :class="{ 'is-active': tab === 'past' }" @click="tab = 'past'">
        <AegisIcon name="clock" :size="14" /> Past
      </button>
    </nav>

    <div class="events-list">
      <article v-for="e in visibleEvents" :key="e.id" class="event-card">
        <div class="event-date">
          <div class="event-date-month">{{ formatMonth(e.starts_at) }}</div>
          <div class="event-date-day">{{ formatDay(e.starts_at) }}</div>
        </div>
        <div class="event-body">
          <h3 class="event-title">{{ e.title }}</h3>
          <p class="event-desc">{{ e.description }}</p>
          <div class="event-meta">
            <AegisIcon name="map-pin" :size="12" />
            <span>{{ e.location_label }}</span>
            <span>·</span>
            <AegisIcon name="clock" :size="12" />
            <span>{{ formatTime(e.starts_at) }}</span>
          </div>
        </div>
        <div class="event-actions">
          <button v-if="tab === 'upcoming'" type="button" class="btn btn-primary btn-sm" @click="rsvp(e)">
            {{ e.attending ? 'Going' : 'RSVP' }}
          </button>
        </div>
      </article>
    </div>

    <AegisEmptyState
      v-if="!visibleEvents.length"
      icon="calendar"
      :title="tab === 'upcoming' ? 'No upcoming events' : 'No past events'"
      description="Check back later."
    />
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'

const props = defineProps({
  upcoming: { type: Array, default: () => [] },
  past:     { type: Array, default: () => [] },
})

const tab = ref('upcoming')
const visibleEvents = computed(() => tab.value === 'upcoming' ? props.upcoming : props.past)

function formatMonth(d) { return new Date(d).toLocaleString(undefined, { month: 'short' }).toUpperCase() }
function formatDay(d) { return new Date(d).getDate() }
function formatTime(d) { return new Date(d).toLocaleString(undefined, { hour: 'numeric', minute: '2-digit' }) }

function rsvp(e) {
  router.post(route('provider.events.rsvp', { event: e.id }), {}, { preserveScroll: true })
}
</script>
