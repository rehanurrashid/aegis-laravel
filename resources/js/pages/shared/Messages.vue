<!--
  pages/shared/Messages.vue — Messages page used by all five portals.
  Mirrors legacy PHP messages template: two-column layout (contacts list with
  search + bucket filter pills · chat area with daily-grouped bubbles + compose),
  plus a contact details drawer, new-message modal with recipient picker, and
  send-on-Enter compose behavior.
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
        <button type="button" class="btn-hero-solid is-on-light" @click="openCompose">
          <AegisIcon name="pencil" :size="14" />
          New message
        </button>
      </template>
    </AegisHeroBanner>

    <!-- ── Layout shell ──────────────────────────────── -->
    <div class="msg-layout">

      <!-- LEFT — Contacts pane -->
      <div class="msg-pane is-contacts">
        <div class="msg-contacts-head">
          <div class="msg-search-row">
            <div class="msg-search-input-wrap">
              <AegisIcon name="search" :size="14" />
              <input
                v-model="filter"
                type="text"
                class="msg-search-input"
                placeholder="Search messages…"
              />
            </div>
            <button
              type="button"
              class="btn-icon"
              data-tooltip="New message"
              @click="openCompose"
            >
              <AegisIcon name="plus" :size="14" />
            </button>
          </div>
        </div>

        <!-- Bucket filter tabs -->
        <div class="msg-filter-row">
          <button
            v-for="b in buckets"
            :key="b.key"
            type="button"
            class="tab-pill"
            :class="{ active: activeBucket === b.key }"
            :data-tooltip="b.tip"
            @click="activeBucket = b.key"
          >
            {{ b.label }}
            <span class="badge-pill">{{ bucketCounts[b.key] ?? 0 }}</span>
          </button>
        </div>

        <!-- Thread list -->
        <div class="msg-contact-list">
          <div v-if="!filteredThreads.length" class="msg-list-empty">
            No conversations yet. Start one with the New Message button above.
          </div>
          <div
            v-for="t in filteredThreads"
            :key="t.id"
            class="msg-contact-item"
            :class="{ 'is-active': t.id === activeThread?.id }"
            @click="selectThread(t)"
          >
            <div
              class="msg-avatar"
              :class="{ 'is-gold': t.is_continuity_contact }"
            >{{ t.counterpart?.avatar_initials || '·' }}</div>
            <div class="msg-contact-info">
              <div class="msg-contact-name">{{ t.counterpart?.display_name }}</div>
              <div class="msg-contact-preview" :class="{ 'is-unread': t.last_message_unread }">
                {{ t.last_message_snippet }}
              </div>
            </div>
            <div class="msg-contact-meta">
              <div class="msg-contact-time">{{ activity.timeAgo(t.last_message_at) }}</div>
              <span
                v-if="t.last_message_unread"
                class="msg-contact-dot"
                aria-label="Unread"
              ></span>
            </div>
          </div>
        </div>
      </div>

      <!-- CENTER — Chat pane -->
      <div class="msg-pane is-chat">
        <!-- Empty state -->
        <div v-if="!activeThread" class="msg-empty-state">
          <div class="msg-empty-icon"><AegisIcon name="message" :size="24" /></div>
          <div class="msg-empty-title">No conversation selected</div>
          <div class="msg-empty-text">Pick a contact on the left, or start a new conversation.</div>
          <button
            type="button"
            class="btn btn-primary"
            style="margin-top: 0.875rem;"
            @click="openCompose"
          >
            <AegisIcon name="plus" :size="12" />
            Start a Conversation
          </button>
        </div>

        <template v-else>
          <!-- Chat header -->
          <div class="msg-chat-head">
            <div
              class="msg-avatar is-sm"
              :class="{ 'is-gold': activeThread.is_continuity_contact }"
            >{{ activeThread.counterpart?.avatar_initials || '·' }}</div>
            <div class="msg-chat-head-info">
              <div class="msg-chat-head-name">{{ activeThread.counterpart?.display_name }}</div>
              <div class="msg-chat-head-sub">
                {{ activeThread.counterpart?.role_label }}
                <template v-if="activeThread.is_continuity_contact">
                  <span class="msg-sub-dot">·</span>
                  <span class="msg-continuity-flag">Continuity Contact</span>
                </template>
              </div>
            </div>
            <div class="msg-chat-head-actions">
              <button
                type="button"
                class="btn-icon"
                data-tooltip="Conversation info"
                @click="showInfo = !showInfo"
              >
                <AegisIcon name="more" :size="14" />
              </button>
            </div>
          </div>

          <!-- Messages stream -->
          <div ref="msgStream" class="msg-stream">
            <template v-for="(m, i) in activeMessages" :key="m.id">
              <div
                v-if="shouldShowDayChip(m, i)"
                class="msg-day-chip"
              >{{ formatDay(m.sent_at) }}</div>
              <div class="msg-row" :class="m.is_sent ? 'is-sent' : 'is-received'">
                <div
                  class="msg-avatar is-xs"
                  :class="{
                    'is-gold': m.is_sent || activeThread.is_continuity_contact,
                  }"
                >{{ m.is_sent ? currentUserInitials : (activeThread.counterpart?.avatar_initials || '·') }}</div>
                <div class="msg-bubble-wrap">
                  <div class="msg-bubble">{{ m.body }}</div>
                  <div class="msg-bubble-time">
                    {{ formatTime(m.sent_at) }}
                    <AegisIcon v-if="m.is_sent" name="check" :size="10" />
                  </div>
                </div>
              </div>
            </template>
          </div>

          <!-- Compose -->
          <div class="msg-compose">
            <div class="msg-compose-row">
              <textarea
                v-model="replyForm.body"
                class="msg-compose-input"
                placeholder="Type your message…"
                rows="1"
                @keydown="onComposeKey"
                @input="autoResize"
              ></textarea>
              <button
                type="button"
                class="btn-icon btn-icon-primary"
                data-tooltip="Send"
                :disabled="!replyForm.body.trim() || replyForm.processing"
                @click="sendReply"
              >
                <AegisIcon name="send" :size="14" />
              </button>
            </div>
          </div>
        </template>
      </div>

      <!-- RIGHT — Conversation info side panel (drawer on mobile) -->
      <transition name="slide">
        <div
          v-if="activeThread && showInfo"
          class="msg-info-panel"
        >
          <div class="msg-info-head">
            <div class="msg-info-title">Conversation Info</div>
            <button
              type="button"
              class="btn-icon"
              data-tooltip="Close"
              @click="showInfo = false"
            >
              <AegisIcon name="x" :size="14" />
            </button>
          </div>

          <div class="msg-info-body">
            <!-- Identity -->
            <div class="msg-info-section">
              <div class="msg-info-section-label">Contact</div>
              <div class="msg-info-row" v-if="activeThread.counterpart?.email">
                <span class="msg-info-icon"><AegisIcon name="mail" :size="14" /></span>
                <span class="msg-info-label">Email</span>
                <a class="msg-info-value is-link" :href="`mailto:${activeThread.counterpart.email}`">{{ activeThread.counterpart.email }}</a>
              </div>
              <div class="msg-info-row" v-if="activeThread.counterpart?.phone">
                <span class="msg-info-icon"><AegisIcon name="phone" :size="14" /></span>
                <span class="msg-info-label">Phone</span>
                <span class="msg-info-value">{{ activeThread.counterpart.phone }}</span>
              </div>
              <div class="msg-info-row" v-if="activeThread.counterpart?.organization">
                <span class="msg-info-icon"><AegisIcon name="briefcase" :size="14" /></span>
                <span class="msg-info-label">Org</span>
                <span class="msg-info-value">{{ activeThread.counterpart.organization }}</span>
              </div>
              <div class="msg-info-row" v-if="activeThread.counterpart?.location">
                <span class="msg-info-icon"><AegisIcon name="map-pin" :size="14" /></span>
                <span class="msg-info-label">Based</span>
                <span class="msg-info-value">{{ activeThread.counterpart.location }}</span>
              </div>
            </div>

            <!-- Stats -->
            <div class="msg-info-section">
              <div class="msg-info-section-label">Conversation</div>
              <div class="msg-info-row">
                <span class="msg-info-icon"><AegisIcon name="message" :size="14" /></span>
                <span class="msg-info-label">Messages</span>
                <span class="msg-info-value">{{ activeMessages.length }}</span>
              </div>
              <div class="msg-info-row">
                <span class="msg-info-icon"><AegisIcon name="clock" :size="14" /></span>
                <span class="msg-info-label">Latest</span>
                <span class="msg-info-value">{{ activity.timeAgo(activeThread.last_message_at) }} ago</span>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </div>

    <!-- ── New Message Modal ──────────────────────── -->
    <AegisModal
      v-model="modals.compose"
      title="New Message"
      size="md"
      @close="resetCompose"
    >
      <div class="form-group">
        <label class="form-label" for="compose-search">To <span class="text-danger">*</span></label>
        <div class="compose-search-wrap">
          <AegisIcon name="search" :size="14" />
          <input
            id="compose-search"
            v-model="recipientFilter"
            type="text"
            class="compose-search-input"
            placeholder="Search by name…"
            autocomplete="off"
          />
        </div>
        <div class="compose-recipient-list">
          <div
            v-for="r in filteredRecipients"
            :key="r.id"
            class="compose-recipient-row"
            :class="{ active: selectedRecipientId === r.id }"
            @click="selectedRecipientId = r.id"
          >
            <div class="msg-avatar is-sm">{{ r.avatar_initials }}</div>
            <div class="compose-recipient-body">
              <div class="compose-recipient-name">{{ r.display_name }}</div>
              <div class="compose-recipient-role">{{ r.role_label }}{{ r.organization ? ' · ' + r.organization : '' }}</div>
            </div>
          </div>
          <div v-if="!filteredRecipients.length" class="compose-recipient-empty">No matches.</div>
        </div>
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
          placeholder="Type your message…"
          @blur="v$.body.$touch()"
        ></textarea>
        <div v-if="composeFieldError('body')" class="form-error">{{ composeFieldError('body') }}</div>
        <p class="form-hint">Messages are end-to-end encrypted and audit-logged.</p>
      </div>
      <template #footer>
        <button type="button" class="btn btn-ghost" @click="modals.compose = false">Cancel</button>
        <button
          type="button"
          class="btn btn-primary"
          :disabled="composeForm.processing"
          @click="sendNewThread"
        >
          <AegisIcon name="send" :size="12" />
          {{ composeForm.processing ? 'Sending…' : 'Send Message' }}
        </button>
      </template>
    </AegisModal>
  </AppLayout>
</template>

<script setup>
import { ref, computed, reactive, nextTick, onMounted, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisHeroBanner from '@/components/ui/AegisHeroBanner.vue'
import { useToast } from '@/composables/useToast'
import { useActivity } from '@/composables/useActivity'
import { useVuelidate } from '@vuelidate/core'
import { required, minLength, helpers } from '@vuelidate/validators'

const props = defineProps({
  threads:              { type: Array,  default: () => [] },
  activeThread:         { type: Object, default: null },
  activeMessages:       { type: Array,  default: () => [] },
  recipients:           { type: Array,  default: () => [] },
  unreadCounts:         { type: Object, default: () => ({}) },
  buckets:              { type: Array,  default: () => [{ key: 'all', label: 'All', tip: 'All conversations' }] },
  bucketCounts:         { type: Object, default: () => ({}) },
  currentUserId:        { type: String, default: '' },
  currentUserInitials:  { type: String, default: 'U' },
})

const toast    = useToast()
const activity = useActivity()
const filter   = ref('')
const activeBucket = ref('all')
const showInfo = ref(false)
const recipientFilter = ref('')
const selectedRecipientId = ref('')
const msgStream = ref(null)

const modals = reactive({ compose: false })

// ── Thread list filtering ────────────────────────
const filteredThreads = computed(() => {
  let list = props.threads
  if (activeBucket.value !== 'all') {
    list = list.filter(t => t.bucket === activeBucket.value)
  }
  const q = filter.value.trim().toLowerCase()
  if (q) {
    list = list.filter(t =>
      `${t.counterpart?.display_name ?? ''} ${t.last_message_snippet ?? ''}`
        .toLowerCase()
        .includes(q)
    )
  }
  return list
})

function selectThread(t) {
  router.get(route('messages.index', { thread: t.id }), {}, {
    preserveScroll: true,
    preserveState:  true,
    only: ['activeThread', 'activeMessages', 'unreadCounts', 'threads'],
  })
}

// ── Day chip + message formatting ────────────────
function shouldShowDayChip(msg, idx) {
  if (idx === 0) return true
  const prev = props.activeMessages[idx - 1]
  return formatDay(prev.sent_at) !== formatDay(msg.sent_at)
}

function formatDay(ts) {
  if (!ts) return ''
  const d = new Date(ts)
  const today = new Date()
  const yesterday = new Date()
  yesterday.setDate(today.getDate() - 1)
  const sameDay = (a, b) =>
    a.getFullYear() === b.getFullYear() &&
    a.getMonth() === b.getMonth() &&
    a.getDate() === b.getDate()
  if (sameDay(d, today))     return 'Today'
  if (sameDay(d, yesterday)) return 'Yesterday'
  return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
}

function formatTime(ts) {
  if (!ts) return ''
  const d = new Date(ts)
  return d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })
}

// ── Compose (new thread) ──────────────────────────
const composeForm = useForm({
  participant_ids: [],
  title:           '',
  body:            '',
})

const composeRules = computed(() => ({
  participant_ids: {
    required: helpers.withMessage('Please select a recipient.',
      v => Array.isArray(v) ? v.length > 0 : !!v),
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

const filteredRecipients = computed(() => {
  const q = recipientFilter.value.trim().toLowerCase()
  if (!q) return props.recipients.slice(0, 15)
  return props.recipients
    .filter(r =>
      `${r.display_name} ${r.role_label} ${r.organization ?? ''}`
        .toLowerCase()
        .includes(q)
    )
    .slice(0, 15)
})

function openCompose() {
  resetCompose()
  modals.compose = true
}

function resetCompose() {
  composeForm.reset()
  composeForm.participant_ids = []
  v$.value.$reset()
  recipientFilter.value = ''
  selectedRecipientId.value = ''
}

async function sendNewThread() {
  composeForm.participant_ids = selectedRecipientId.value
    ? [selectedRecipientId.value]
    : []
  const ok = await v$.value.$validate()
  if (!ok) return toast.error('Please fix the highlighted fields.')

  composeForm.post(route('messages.store'), {
    onSuccess: () => {
      modals.compose = false
      toast.success('Message sent.')
      resetCompose()
    },
    onError: () => toast.error('Could not send message. Please try again.'),
  })
}

// ── Inline reply ──────────────────────────────────
const replyForm = useForm({ body: '' })

function onComposeKey(ev) {
  if (ev.key === 'Enter' && !ev.shiftKey) {
    ev.preventDefault()
    sendReply()
  }
}

function autoResize(ev) {
  const ta = ev.target
  ta.style.height = 'auto'
  ta.style.height = Math.min(ta.scrollHeight, 160) + 'px'
}

function sendReply() {
  if (!replyForm.body.trim() || !props.activeThread) return
  replyForm.post(route('messages.reply', props.activeThread.id), {
    preserveScroll: true,
    onSuccess: () => {
      replyForm.reset()
      nextTick(() => scrollToBottom())
    },
    onError: () => toast.error('Could not send reply.'),
  })
}

function scrollToBottom() {
  if (msgStream.value) {
    msgStream.value.scrollTop = msgStream.value.scrollHeight
  }
}

onMounted(() => nextTick(() => scrollToBottom()))
watch(() => props.activeMessages, () => nextTick(() => scrollToBottom()))
</script>

<style scoped>
/* ── Layout shell ──────────────────────────────── */
.msg-layout {
  display: grid;
  grid-template-columns: 300px 1fr auto;
  gap: 0.875rem;
  height: calc(100vh - 220px);
  min-height: 540px;
  margin-top: 1.25rem;
}
.msg-pane {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  min-width: 0;
}

/* ── LEFT contacts pane ────────────────────────── */
.msg-contacts-head {
  padding: 0.625rem 0.75rem;
  border-bottom: 1px solid var(--border);
}
.msg-search-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.msg-search-input-wrap {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.45rem 0.625rem;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text-3);
}
.msg-search-input {
  flex: 1;
  border: none;
  outline: none;
  background: transparent;
  font-size: 13px;
  color: var(--text);
  font-family: inherit;
}
.msg-search-input::placeholder { color: var(--text-4); }

.btn-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  background: var(--surface);
  color: var(--text-3);
  cursor: pointer;
  transition: border-color var(--transition-fast), color var(--transition-fast), background var(--transition-fast);
}
.btn-icon:hover { border-color: var(--gold); color: var(--text); }
.btn-icon-primary {
  background: var(--gold-dark);
  border-color: var(--gold-dark);
  color: var(--text-inverted);
}
.btn-icon-primary:hover { background: var(--gold); border-color: var(--gold); color: var(--text-inverted); }
.btn-icon-primary:disabled { opacity: 0.5; cursor: not-allowed; }

/* Bucket filter pills */
.msg-filter-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.3rem;
  padding: 0.625rem 0.75rem;
  border-bottom: 1px solid var(--border);
  background: var(--surface-2);
}
.badge-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 18px;
  height: 16px;
  padding: 0 0.3rem;
  border-radius: var(--radius-full);
  background: var(--surface);
  color: var(--text-3);
  font-size: 10px;
  font-weight: 700;
  margin-left: 0.35rem;
  border: 1px solid var(--border);
}
.tab-pill.active .badge-pill {
  background: var(--gold-dark);
  color: var(--text-inverted);
  border-color: var(--gold-dark);
}

/* Contact list */
.msg-contact-list {
  flex: 1;
  overflow-y: auto;
}
.msg-list-empty {
  padding: 1.5rem 1rem;
  text-align: center;
  font-size: 12px;
  color: var(--text-4);
  line-height: 1.55;
}
.msg-contact-item {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  padding: 0.8rem 0.875rem;
  border-bottom: 1px solid var(--border);
  cursor: pointer;
  transition: background var(--transition-fast);
}
.msg-contact-item:hover { background: var(--surface-2); }
.msg-contact-item.is-active {
  background: var(--badge-bg-gold);
  border-left: 3px solid var(--gold-dark);
}

.msg-avatar {
  width: 38px;
  height: 38px;
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
.msg-avatar.is-gold {
  background: var(--badge-bg-gold);
  color: var(--gold-dark);
}
.msg-avatar.is-sm { width: 30px; height: 30px; font-size: 11px; }
.msg-avatar.is-xs { width: 26px; height: 26px; font-size: 10px; }

.msg-contact-info { flex: 1; min-width: 0; }
.msg-contact-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 0.15rem;
}
.msg-contact-preview {
  font-size: 12px;
  color: var(--text-3);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.msg-contact-preview.is-unread { color: var(--text-2); font-weight: 600; }
.msg-contact-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.3rem;
  flex-shrink: 0;
}
.msg-contact-time { font-size: 11px; color: var(--text-4); }
.msg-contact-dot {
  width: 8px;
  height: 8px;
  border-radius: var(--radius-full);
  background: var(--gold-dark);
}

/* ── CENTER chat pane ──────────────────────────── */
.msg-chat-head {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1.125rem;
  border-bottom: 1px solid var(--border);
  background: var(--surface);
}
.msg-chat-head-info { flex: 1; min-width: 0; }
.msg-chat-head-name {
  font-size: 14px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 0.15rem;
}
.msg-chat-head-sub {
  font-size: 12px;
  color: var(--text-3);
}
.msg-sub-dot { margin: 0 0.3rem; color: var(--text-4); }
.msg-continuity-flag {
  color: var(--gold-dark);
  font-weight: 700;
}

.msg-empty-state {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 2rem;
}
.msg-empty-icon {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-full);
  background: var(--badge-bg-gold);
  color: var(--gold-dark);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
}
.msg-empty-title {
  font-family: var(--font-serif);
  font-size: 16px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 0.4rem;
}
.msg-empty-text {
  font-size: 13px;
  color: var(--text-3);
  line-height: 1.55;
  max-width: 320px;
}

/* Message stream */
.msg-stream {
  flex: 1;
  overflow-y: auto;
  padding: 1rem 1.125rem;
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
  background: var(--bg-2);
}
.msg-day-chip {
  align-self: center;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-4);
  background: var(--surface);
  border: 1px solid var(--border);
  padding: 0.2rem 0.6rem;
  border-radius: var(--radius-full);
  margin: 0.5rem 0;
}
.msg-row {
  display: flex;
  gap: 0.5rem;
  align-items: flex-end;
  max-width: 80%;
}
.msg-row.is-sent {
  align-self: flex-end;
  flex-direction: row-reverse;
}
.msg-bubble-wrap {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.msg-row.is-sent .msg-bubble-wrap { align-items: flex-end; }
.msg-bubble {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 0.55rem 0.8rem;
  font-size: 13px;
  color: var(--text);
  line-height: 1.5;
  white-space: pre-line;
  word-break: break-word;
}
.msg-row.is-sent .msg-bubble {
  background: var(--badge-bg-gold);
  border-color: var(--soft-gold);
  color: var(--text);
}
.msg-bubble-time {
  font-size: 10px;
  color: var(--text-4);
  margin-top: 0.2rem;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

/* Compose */
.msg-compose {
  padding: 0.75rem 1rem;
  border-top: 1px solid var(--border);
  background: var(--surface);
}
.msg-compose-row {
  display: flex;
  align-items: flex-end;
  gap: 0.5rem;
}
.msg-compose-input {
  flex: 1;
  resize: none;
  min-height: 40px;
  max-height: 160px;
  padding: 0.55rem 0.75rem;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 13px;
  color: var(--text);
  font-family: inherit;
  outline: none;
  transition: border-color var(--transition-fast);
}
.msg-compose-input:focus { border-color: var(--gold); }

/* ── RIGHT info panel ──────────────────────────── */
.msg-info-panel {
  width: 280px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.msg-info-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--border);
}
.msg-info-title {
  font-family: var(--font-serif);
  font-size: 14px;
  font-weight: 700;
  color: var(--text);
}
.msg-info-body {
  flex: 1;
  overflow-y: auto;
  padding: 0.75rem 1rem;
}
.msg-info-section { margin-bottom: 1rem; }
.msg-info-section-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
  color: var(--text-4);
  margin-bottom: 0.5rem;
}
.msg-info-row {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.35rem 0;
  font-size: 12px;
  color: var(--text-2);
}
.msg-info-icon {
  display: inline-flex;
  align-items: center;
  color: var(--text-3);
  flex-shrink: 0;
}
.msg-info-label {
  color: var(--text-3);
  font-weight: 600;
  width: 50px;
  flex-shrink: 0;
}
.msg-info-value {
  color: var(--text);
  flex: 1;
  word-break: break-word;
}
.msg-info-value.is-link { color: var(--gold-dark); text-decoration: none; }
.msg-info-value.is-link:hover { text-decoration: underline; }

.slide-enter-active, .slide-leave-active { transition: all 0.2s ease; }
.slide-enter-from, .slide-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

/* ── Compose modal recipient picker ────────────── */
.compose-search-wrap {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  color: var(--text-3);
  margin-bottom: 0.5rem;
}
.compose-search-input {
  flex: 1;
  border: none;
  outline: none;
  background: transparent;
  font-size: 13px;
  color: var(--text);
  font-family: inherit;
}
.compose-recipient-list {
  max-height: 240px;
  overflow-y: auto;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
}
.compose-recipient-row {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  padding: 0.5rem 0.75rem;
  border-bottom: 1px solid var(--border);
  cursor: pointer;
  transition: background var(--transition-fast);
}
.compose-recipient-row:last-child { border-bottom: none; }
.compose-recipient-row:hover { background: var(--surface-2); }
.compose-recipient-row.active {
  background: var(--badge-bg-gold);
  border-left: 3px solid var(--gold-dark);
}
.compose-recipient-body { flex: 1; min-width: 0; }
.compose-recipient-name {
  font-size: 13px;
  font-weight: 700;
  color: var(--text);
}
.compose-recipient-role {
  font-size: 11px;
  color: var(--text-3);
  margin-top: 0.1rem;
}
.compose-recipient-empty {
  padding: 1rem;
  text-align: center;
  font-size: 12px;
  color: var(--text-4);
}

@media (max-width: 1100px) {
  .msg-layout { grid-template-columns: 260px 1fr auto; }
  .msg-info-panel { width: 240px; }
}
@media (max-width: 820px) {
  .msg-layout {
    grid-template-columns: 1fr;
    grid-auto-rows: minmax(0, 1fr);
    height: auto;
  }
  .msg-info-panel { display: none; }
  .msg-pane.is-contacts { max-height: 50vh; }
}
</style>
