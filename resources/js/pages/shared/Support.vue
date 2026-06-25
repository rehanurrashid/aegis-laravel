<!--
  pages/shared/Support.vue — 3-tab support page.

  Replaces _shared/templates/support.php. Tabs:
    • My Tickets  — open + resolved support requests
    • Feedback    — quick feedback compose
    • Help Center — searchable help articles
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Account"
      title="Support"
      subtitle="Open a ticket, send feedback, or browse the help center."
    />

    <nav class="tab-strip" role="tablist">
      <button
        v-for="t in tabs"
        :key="t.key"
        type="button"
        role="tab"
        :aria-selected="active === t.key"
        class="tab-btn"
        :class="{ 'is-active': active === t.key }"
        @click="active = t.key"
      >
        <AegisIcon :name="t.icon" :size="14" />
        <span>{{ t.label }}</span>
      </button>
    </nav>

    <!-- ── My Tickets ──────────────────────────────────────────── -->
    <section v-show="active === 'tickets'" class="support-pane">
      <AegisCard title="My tickets">
        <template #actions>
          <button type="button" class="btn btn-primary btn-sm" @click="openModal('newTicketModal')">
            <AegisIcon name="plus" :size="12" />
            <span>New ticket</span>
          </button>
        </template>

        <table v-if="tickets.length" class="data-table">
          <thead>
            <tr>
              <th>Subject</th>
              <th>Module</th>
              <th>Status</th>
              <th>Opened</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="t in tickets" :key="t.id">
              <td class="data-table-primary">{{ t.subject }}</td>
              <td>{{ t.module ?? '—' }}</td>
              <td><AegisBadge :label="t.status" :variant="ticketStatusVariant(t.status)" /></td>
              <td>{{ activity.timeAgo(t.created_at) }}</td>
              <td>
                <a :href="route('support.show', { ticket: t.id })" class="btn btn-ghost btn-sm">View</a>
              </td>
            </tr>
          </tbody>
        </table>

        <AegisEmptyState
          v-else
          icon="inbox"
          title="No tickets yet"
          description="When you open a ticket, it will appear here."
        />
      </AegisCard>
    </section>

    <!-- ── Feedback ────────────────────────────────────────────── -->
    <section v-show="active === 'feedback'" class="support-pane">
      <AegisCard title="Send feedback">
        <form @submit.prevent="submitFeedback">
          <div class="form-group">
            <label class="form-label">Category</label>
            <select v-model="feedback.category" class="form-input">
              <option value="suggestion">Suggestion</option>
              <option value="bug">Bug report</option>
              <option value="praise">Praise</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Message</label>
            <textarea
              v-model="feedback.body"
              class="form-input"
              rows="6"
              placeholder="Tell us what's on your mind…"
            ></textarea>
          </div>
          <button
            type="submit"
            class="btn btn-primary"
            :disabled="feedback.processing || !feedback.body.trim()"
          >{{ feedback.processing ? 'Sending…' : 'Send feedback' }}</button>
        </form>
      </AegisCard>
    </section>

    <!-- ── Help Center ─────────────────────────────────────────── -->
    <section v-show="active === 'help'" class="support-pane">
      <div class="help-search">
        <AegisIcon name="search-lg" :size="16" />
        <input
          v-model="helpQuery"
          type="text"
          class="help-search-input"
          placeholder="Search help articles…"
        />
      </div>

      <div class="help-grid">
        <a
          v-for="a in filteredArticles"
          :key="a.id"
          :href="route('help.show', { slug: a.slug })"
          class="help-card"
        >
          <AegisIcon :name="a.icon ?? 'book-open'" :size="18" />
          <div>
            <div class="help-card-title">{{ a.title }}</div>
            <div class="help-card-snippet">{{ a.summary }}</div>
          </div>
        </a>

        <AegisEmptyState
          v-if="!filteredArticles.length"
          icon="book"
          title="No matching articles"
          description="Try a broader search, or open a ticket above."
        />
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'
import { useActivity } from '@/composables/useActivity'

const props = defineProps({
  tickets:    { type: Array,  default: () => [] },
  articles:   { type: Array,  default: () => [] },
  initialTab: { type: String, default: 'tickets' },
})

const { openModal } = useModal()
const toast = useToast()
const activity = useActivity()

const active = ref(props.initialTab)
const tabs = [
  { key: 'tickets',  label: 'My Tickets', icon: 'inbox' },
  { key: 'feedback', label: 'Feedback',   icon: 'message-square' },
  { key: 'help',     label: 'Help Center', icon: 'book-open' },
]

const helpQuery = ref('')
const filteredArticles = computed(() => {
  const q = helpQuery.value.trim().toLowerCase()
  if (!q) return props.articles
  return props.articles.filter((a) =>
    `${a.title} ${a.summary ?? ''}`.toLowerCase().includes(q),
  )
})

const feedback = useForm({
  category: 'suggestion',
  body:     '',
})

function submitFeedback() {
  feedback.post(route('support.feedback'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Thank you. Your feedback was sent.')
      feedback.reset('body')
    },
  })
}

function ticketStatusVariant(status) {
  return {
    open: 'gold', pending: 'orange', resolved: 'green', closed: 'neutral',
  }[status] ?? 'neutral'
}
</script>
