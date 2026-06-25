<!--
  pages/shared/Messages.vue — direct messages inbox.

  Replaces _shared/templates/messages.php. Two-pane layout: thread list
  on the left, active thread on the right. Selecting a thread updates
  the right pane in-place via Inertia partial reload.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Communication"
      title="Messages"
      :subtitle="threads.length
        ? `${threads.length} conversation${threads.length === 1 ? '' : 's'}.`
        : 'No conversations yet.'"
    >
      <template #actions>
        <button type="button" class="btn btn-primary" @click="openModal('composeModal')">
          <AegisIcon name="square-pen" :size="14" />
          <span>New message</span>
        </button>
      </template>
    </AegisHeroBanner>

    <div class="messages-page">
      <!-- Thread list -->
      <aside class="messages-list-pane">
        <div class="messages-list-search">
          <AegisIcon name="search" :size="14" />
          <input
            v-model="filter"
            type="text"
            class="messages-list-input"
            placeholder="Search conversations…"
          />
        </div>

        <ul class="messages-thread-list">
          <li
            v-for="t in filteredThreads"
            :key="t.id"
            class="messages-thread-row"
            :class="{ 'is-active': t.id === activeThread?.id, 'is-unread': t.unread_count > 0 }"
            @click="selectThread(t)"
          >
            <div class="messages-thread-row-avatar">{{ initials(t.counterpart?.display_name) }}</div>
            <div class="messages-thread-row-body">
              <div class="messages-thread-row-name">{{ t.counterpart?.display_name }}</div>
              <div class="messages-thread-row-snippet">{{ t.last_message_snippet }}</div>
            </div>
            <div class="messages-thread-row-meta">
              <div class="messages-thread-row-time">{{ activity.timeAgo(t.last_message_at) }}</div>
              <span v-if="t.unread_count" class="messages-thread-row-unread">{{ t.unread_count }}</span>
            </div>
          </li>

          <AegisEmptyState
            v-if="!filteredThreads.length"
            icon="message"
            title="No conversations"
            description="Send the first one to start a thread."
          />
        </ul>
      </aside>

      <!-- Active thread -->
      <div class="messages-thread-pane">
        <MessagesThread
          v-if="activeThread"
          :thread="activeThread"
          :messages="activeMessages"
          :can-reply="!activeThread.is_archived"
        />
        <AegisEmptyState
          v-else
          icon="message-square"
          title="Select a conversation"
          description="Pick a thread from the list to read and reply."
        />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import MessagesThread from '@/components/features/MessagesThread.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import { useModal } from '@/composables/useModal'
import { useActivity } from '@/composables/useActivity'

const props = defineProps({
  threads:        { type: Array,  default: () => [] },
  activeThread:   { type: Object, default: null },
  activeMessages: { type: Array,  default: () => [] },
})

const { openModal } = useModal()
const activity = useActivity()
const filter = ref('')

const filteredThreads = computed(() => {
  const q = filter.value.trim().toLowerCase()
  if (!q) return props.threads
  return props.threads.filter((t) =>
    `${t.counterpart?.display_name ?? ''} ${t.last_message_snippet ?? ''}`
      .toLowerCase()
      .includes(q),
  )
})

function initials(name) {
  if (!name) return '·'
  return name.split(/\s+/).map((s) => s[0]).slice(0, 2).join('').toUpperCase()
}

function selectThread(t) {
  router.get(route(`${portalPrefix()}.messages`, { thread: t.id }), {}, {
    preserveScroll: true,
    preserveState: true,
    only: ['activeThread', 'activeMessages'],
  })
}

function portalPrefix() {
  // Reads from active Inertia page component (e.g. "provider/Messages" → "provider").
  const c = router.page?.component ?? ''
  const prefix = c.split('/')[0]
  return { provider: 'provider', 'continuity-steward': 'cs', 'support-steward': 'ss', 'business-partner': 'bp' }[prefix] ?? 'provider'
}
</script>
