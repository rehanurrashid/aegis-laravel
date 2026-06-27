<!--
  pages/shared/Messages.vue — direct messages inbox.
  Two-pane layout: thread list on the left, active thread on the right.
  Supports compose (new thread) and reply via useForm(). Vuelidate on both.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      quiet
      eyebrow="Communication"
      title="Messages"
      :subtitle="threads.length
        ? `${threads.length} conversation${threads.length === 1 ? '' : 's'}.`
        : 'No conversations yet.'"
    >
      <template #actions>
        <a :href="route('activity.index')" class="btn-hero-ghost is-on-light">
          <AegisIcon name="activity" :size="14" />
          Activity
        </a>
        <button type="button" class="btn-hero-solid is-on-light" @click="modals.compose = true">
          <AegisIcon name="pencil" :size="14" />
          New message
        </button>
      </template>
    </AegisHeroBanner>

    <div class="messages-shell">
      <!-- Thread list -->
      <aside class="messages-sidebar">
        <div class="messages-sidebar-search">
          <AegisIcon name="search" :size="14" />
          <input
            v-model="filter"
            type="text"
            class="messages-search-input"
            placeholder="Search conversations…"
          />
        </div>

        <ul class="messages-thread-list">
          <li
            v-for="t in filteredThreads"
            :key="t.id"
            class="thread-item"
            :class="{
              'is-active':  t.id === activeThread?.id,
              'is-unread':  (unreadCounts[t.id] ?? 0) > 0,
            }"
            @click="selectThread(t)"
          >
            <div class="thread-item-avatar">{{ initials(t.counterpart?.display_name) }}</div>
            <div class="thread-item-body">
              <div class="thread-item-name">{{ t.counterpart?.display_name }}</div>
              <div class="thread-item-snippet">{{ t.last_message_snippet }}</div>
            </div>
            <div class="thread-item-meta">
              <div class="thread-item-time">{{ activity.timeAgo(t.last_message_at) }}</div>
              <span v-if="(unreadCounts[t.id] ?? 0) > 0" class="thread-item-badge">
                {{ unreadCounts[t.id] }}
              </span>
            </div>
          </li>

          <div v-if="!filteredThreads.length" class="empty-state">
            <div class="empty-state-icon"><AegisIcon name="message" :size="24" /></div>
            <div class="empty-state-title">No conversations</div>
            <div class="empty-state-sub">Send the first one to start a thread.</div>
          </div>
        </ul>
      </aside>

      <!-- Active thread panel -->
      <div class="messages-panel">
        <MessagesThread
          v-if="activeThread"
          :thread="activeThread"
          :messages="activeMessages"
          :can-reply="!activeThread.is_archived"
        />
        <div v-else class="messages-panel-empty">
          <div class="empty-state">
            <div class="empty-state-icon"><AegisIcon name="message-square" :size="28" /></div>
            <div class="empty-state-title">Select a conversation</div>
            <div class="empty-state-sub">Pick a thread from the list to read and reply.</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Compose Modal ──────────────────────────────── -->
    <AegisModal
      v-model="modals.compose"
      title="New Message"
      size="md"
      @close="resetCompose"
    >
      <div class="form-group">
        <label class="form-label" for="compose-to">To <span class="text-danger">*</span></label>
        <select
          id="compose-to"
          v-model="composeForm.participant_ids"
          class="form-input"
          :class="{ 'is-error': composeFieldError('participant_ids') }"
          @blur="v$.participant_ids.$touch()"
        >
          <option value="">Select recipient…</option>
          <option
            v-for="r in recipients"
            :key="r.id"
            :value="[r.id]"
          >{{ r.display_name }}</option>
        </select>
        <div v-if="composeFieldError('participant_ids')" class="form-error">{{ composeFieldError('participant_ids') }}</div>
      </div>
      <div class="form-group">
        <label class="form-label" for="compose-title">Subject</label>
        <input
          id="compose-title"
          v-model="composeForm.title"
          type="text"
          class="form-input"
          placeholder="Optional subject line"
          maxlength="200"
        />
      </div>
      <div class="form-group">
        <label class="form-label" for="compose-body">Message <span class="text-danger">*</span></label>
        <textarea
          id="compose-body"
          v-model="composeForm.body"
          class="form-input"
          :class="{ 'is-error': composeFieldError('body') }"
          rows="5"
          placeholder="Write your message…"
          @blur="v$.body.$touch()"
        ></textarea>
        <div v-if="composeFieldError('body')" class="form-error">{{ composeFieldError('body') }}</div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-ghost" @click="modals.compose = false">Cancel</button>
        <button
          type="button"
          class="btn btn-primary"
          :disabled="composeForm.processing"
          @click="sendMessage"
        >{{ composeForm.processing ? 'Sending…' : 'Send message' }}</button>
      </template>
    </AegisModal>
  </AppLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisHeroBanner from '@/components/ui/AegisHeroBanner.vue'
import MessagesThread from '@/components/features/MessagesThread.vue'
import { useToast } from '@/composables/useToast'
import { useActivity } from '@/composables/useActivity'
import { useVuelidate } from '@vuelidate/core'
import { required, minLength, helpers } from '@vuelidate/validators'

const props = defineProps({
  threads:        { type: Array,  default: () => [] },
  activeThread:   { type: Object, default: null },
  activeMessages: { type: Array,  default: () => [] },
  recipients:     { type: Array,  default: () => [] },
  unreadCounts:   { type: Object, default: () => ({}) },
})

const toast = useToast()
const activity = useActivity()
const filter = ref('')
const modals = reactive({ compose: false })

// ── Thread list ───────────────────────────────────────────────
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
  return name.split(/\s+/).map((n) => n[0]).slice(0, 2).join('').toUpperCase()
}

function selectThread(t) {
  // Mark as read then reload thread data
  router.post(route('messages.read', t.id), {}, {
    preserveScroll: true,
    preserveState:  true,
  })
  router.get(route('messages.index', { thread: t.id }), {}, {
    preserveScroll: true,
    preserveState:  true,
    only: ['activeThread', 'activeMessages', 'unreadCounts'],
  })
}

// ── Compose ───────────────────────────────────────────────────
const composeForm = useForm({
  participant_ids: '',
  title:           '',
  body:            '',
})

const composeRules = computed(() => ({
  participant_ids: {
    required: helpers.withMessage('Please select a recipient.', required),
  },
  body: {
    required: helpers.withMessage('Message body is required.', required),
    minLen:   helpers.withMessage('Message must be at least 2 characters.', minLength(2)),
  },
}))
const v$ = useVuelidate(composeRules, composeForm)

function composeFieldError(field) {
  if (v$.value[field]?.$error) return v$.value[field].$errors[0]?.$message
  if (composeForm.errors[field]) return composeForm.errors[field]
  return null
}

function resetCompose() {
  composeForm.reset()
  v$.value.$reset()
}

async function sendMessage() {
  const valid = await v$.value.$validate()
  if (!valid) return toast.error('Please fix the highlighted fields.')

  // Normalize participant_ids to array
  const data = {
    participant_ids: Array.isArray(composeForm.participant_ids)
      ? composeForm.participant_ids
      : [composeForm.participant_ids],
    title: composeForm.title,
    body:  composeForm.body,
  }

  composeForm.transform(() => data).post(route('messages.store'), {
    onSuccess: () => {
      modals.compose = false
      toast.success('Message sent.')
      resetCompose()
    },
    onError: () => toast.error('Could not send message. Please try again.'),
  })
}
</script>

<style scoped>
/* Two-column shell */
.messages-shell {
  display: flex;
  gap: 0;
  height: calc(100vh - 200px);
  min-height: 500px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  background: var(--surface);
  box-shadow: var(--shadow-sm);
  margin-top: 1.25rem;
}

/* Left sidebar — thread list */
.messages-sidebar {
  width: 320px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  border-right: 1px solid var(--border);
  overflow: hidden;
}
.messages-sidebar-search {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--border);
  color: var(--text-3);
  background: var(--surface-2);
  flex-shrink: 0;
}
.messages-search-input {
  flex: 1;
  border: none;
  outline: none;
  background: transparent;
  font-size: 13px;
  color: var(--text);
  font-family: inherit;
}
.messages-search-input::placeholder { color: var(--text-4); }

.messages-thread-list {
  list-style: none;
  padding: 0;
  margin: 0;
  overflow-y: auto;
  flex: 1;
}

/* Thread rows */
.thread-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.85rem 1rem;
  border-bottom: 1px solid var(--border);
  cursor: pointer;
  transition: background var(--transition-fast);
}
.thread-item:hover { background: var(--surface-2); }
.thread-item.is-active { background: var(--badge-bg-gold); border-left: 3px solid var(--gold-dark); }
.thread-item.is-unread .thread-item-name { font-weight: 700; }
.thread-item.is-unread .thread-item-snippet { color: var(--text-2); }

.thread-item-avatar {
  width: 36px;
  height: 36px;
  border-radius: var(--radius-full);
  background: var(--surface-3);
  color: var(--text-2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 700;
  flex-shrink: 0;
}
.thread-item-body { flex: 1; min-width: 0; }
.thread-item-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 0.15rem;
}
.thread-item-snippet {
  font-size: 12px;
  color: var(--text-3);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.thread-item-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.3rem;
  flex-shrink: 0;
}
.thread-item-time { font-size: 11px; color: var(--text-4); }
.thread-item-badge {
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  border-radius: var(--radius-full);
  background: var(--gold-dark);
  color: var(--text-inverted);
  font-size: 10px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Right panel */
.messages-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.messages-panel-empty {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
