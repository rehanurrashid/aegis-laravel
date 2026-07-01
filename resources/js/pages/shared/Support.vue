<!--
  pages/shared/Support.vue — Support page used by all five portals.
  Mirrors legacy PHP support template: 3-tab strip (My Tickets · Feedback · Help Center)
  with new-ticket modal, ticket-detail modal with chat bubbles, feedback chip selector,
  help-article accordion grouped by category.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      quiet
      eyebrow="Account"
      title="Support"
      subtitle="Open a ticket, send feedback, or browse the help center."
    >
      <template #actions>
        <a :href="supportActivityUrl" class="btn-hero-ghost is-on-light">
          <AegisIcon name="activity" :size="14" />
          Activity
        </a>
        <button type="button" class="btn-hero-solid is-on-light" @click="modals.newTicket = true">
          <AegisIcon name="plus" :size="14" />
          New ticket
        </button>
      </template>
    </AegisHeroBanner>

    <!-- ── Stat chips ────────────────────────────────────── -->
    <div class="stat-chips-row">
      <AegisStatChip icon="inbox" :value="tickets.length" label="My tickets" />
      <AegisStatChip icon="clock" :value="openCount" label="Open" />
      <AegisStatChip icon="check-circle" :value="resolvedCount" label="Resolved" />
    </div>

    <!-- ── Tabs ──────────────────────────────────────────── -->
    <nav class="tabs-segmented" role="tablist" style="margin-top: 1.25rem;">
      <button
        v-for="t in tabs"
        :key="t.key"
        type="button"
        role="tab"
        :aria-selected="active === t.key"
        class="tab-pill"
        :class="{ active: active === t.key }"
        @click="active = t.key"
      >
        <AegisIcon :name="t.icon" :size="12" />
        <span>{{ t.label }}</span>
        <span v-if="t.key === 'tickets' && openCount > 0" class="tab-badge">{{ openCount }}</span>
      </button>
    </nav>

    <!-- ── Tab 1 · My Tickets ─────────────────────────── -->
    <section v-show="active === 'tickets'" class="support-pane">
      <div v-if="!tickets.length" class="support-empty">
        <div class="support-empty-icon"><AegisIcon name="inbox" :size="24" /></div>
        <div class="support-empty-title">No tickets yet</div>
        <div class="support-empty-text">When you submit a support request it will appear here. Our team typically responds within 24 hours.</div>
        <button type="button" class="btn btn-primary" @click="modals.newTicket = true" style="margin-top: 1rem;">
          <AegisIcon name="plus" :size="12" />
          Submit a request
        </button>
      </div>

      <div v-else class="ticket-list">
        <div
          v-for="t in tickets"
          :key="t.id"
          class="ticket-card"
          @click="openTicketDetail(t)"
        >
          <div class="ticket-card-icon"><AegisIcon name="message-square" :size="18" /></div>
          <div class="ticket-card-body">
            <div class="ticket-card-subject">{{ t.subject }}</div>
            <div class="ticket-card-meta">
              <span>{{ activity.timeAgo(t.created_at) }}</span>
              <span v-if="replyCount(t.id) > 0">{{ replyCount(t.id) }} {{ replyCount(t.id) === 1 ? 'reply' : 'replies' }}</span>
            </div>
          </div>
          <div class="ticket-card-actions">
            <AegisBadge :label="statusLabel(t.status)" :variant="ticketStatusVariant(t.status)" />
          </div>
        </div>
      </div>
    </section>

    <!-- ── Tab 2 · Feedback ────────────────────────────── -->
    <section v-show="active === 'feedback'" class="support-pane">
      <div class="card feedback-composer">
        <div class="card-header">
          <div class="card-title">Share your thoughts</div>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">Category</label>
            <div class="feedback-chips">
              <button
                v-for="c in feedbackCategories"
                :key="c.value"
                type="button"
                class="feedback-chip"
                :class="{ active: feedbackForm.category === c.value }"
                @click="feedbackForm.category = c.value"
              >{{ c.label }}</button>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label" for="fb-body">Message <span class="text-danger">*</span></label>
            <textarea
              id="fb-body"
              v-model="feedbackForm.body"
              class="form-textarea"
              :class="{ 'is-error': feedbackFieldError('body') }"
              rows="5"
              placeholder="Tell us what's on your mind…"
              @blur="v$feedback.body.$touch()"
            ></textarea>
            <div v-if="feedbackFieldError('body')" class="form-error">{{ feedbackFieldError('body') }}</div>
          </div>
          <button
            type="button"
            class="btn btn-primary"
            :disabled="feedbackForm.processing"
            @click="submitFeedback"
          >
            <AegisIcon name="send" :size="12" />
            {{ feedbackForm.processing ? 'Sending…' : 'Send feedback' }}
          </button>
        </div>
      </div>

      <div v-if="feedbackHistory.length" class="feedback-history">
        <div class="feedback-history-label">Previous feedback</div>
        <div
          v-for="f in feedbackHistory"
          :key="f.id"
          class="feedback-entry"
        >
          <div class="feedback-entry-header">
            <span class="feedback-entry-subject">{{ f.subject }}</span>
            <span class="feedback-entry-meta">{{ activity.timeAgo(f.created_at) }}</span>
          </div>
          <div class="feedback-entry-body" v-html="f.body"></div>
        </div>
      </div>
    </section>

    <!-- ── Tab 3 · Help Center ─────────────────────────── -->
    <section v-show="active === 'help'" class="support-pane">
      <div class="help-search-bar">
        <div class="help-search-inner">
          <AegisIcon name="search" :size="16" />
          <input
            v-model="helpQuery"
            type="text"
            class="help-search-input"
            placeholder="Search help articles…"
          />
        </div>
      </div>

      <div v-if="!helpByCategory.length" class="support-empty">
        <div class="support-empty-icon"><AegisIcon name="book-open" :size="24" /></div>
        <div class="support-empty-title">Help articles coming soon</div>
        <div class="support-empty-text">Our team is building out the Help Center. In the meantime, submit a support ticket and we'll respond within 24 hours.</div>
      </div>

      <div v-else>
        <div
          v-for="section in filteredHelp"
          v-show="section.articles.length"
          :key="section.category"
          class="help-cat-section"
        >
          <div class="help-cat-label">{{ section.category }}</div>
          <details
            v-for="art in section.articles"
            :key="art.id"
            class="help-article"
          >
            <summary class="help-article-header">
              <span class="help-article-title">{{ art.title }}</span>
              <span class="help-chevron"><AegisIcon name="chevron-down" :size="14" /></span>
            </summary>
            <div class="help-article-body" v-html="art.body"></div>
          </details>
        </div>
        <div v-if="!hasAnyVisibleArticle" class="support-empty">
          <div class="support-empty-icon"><AegisIcon name="search" :size="24" /></div>
          <div class="support-empty-title">No matching articles</div>
          <div class="support-empty-text">Try a broader search or submit a ticket.</div>
        </div>
      </div>
    </section>

    <!-- ── New Ticket Modal ────────────────────────── -->
    <AegisModal
      v-model="modals.newTicket"
      title="New Support Ticket"
      size="md"
      @close="resetTicketForm"
    >
      <div class="form-group">
        <label class="form-label" for="tk-subject">Subject <span class="text-danger">*</span></label>
        <input
          id="tk-subject"
          v-model="ticketForm.subject"
          type="text"
          class="form-input"
          :class="{ 'is-error': ticketFieldError('subject') }"
          placeholder="Brief description of your issue"
          maxlength="200"
          @blur="v$ticket.subject.$touch()"
        />
        <div v-if="ticketFieldError('subject')" class="form-error">{{ ticketFieldError('subject') }}</div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="tk-category">Category</label>
          <select
            id="tk-category"
            v-model="ticketForm.category"
            class="form-select"
          >
            <option value="support_ticket">General Support</option>
            <option value="bug">Bug Report</option>
            <option value="feature_request">Feature Request</option>
            <option value="billing">Billing</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="tk-priority">Priority</label>
          <select id="tk-priority" v-model="ticketForm.priority" class="form-select">
            <option value="low">Low</option>
            <option value="normal">Normal</option>
            <option value="high">High</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="tk-body">Description <span class="text-danger">*</span></label>
        <textarea
          id="tk-body"
          v-model="ticketForm.body"
          class="form-textarea"
          :class="{ 'is-error': ticketFieldError('body') }"
          rows="5"
          placeholder="Describe your issue in detail. Include steps to reproduce if reporting a bug."
          @blur="v$ticket.body.$touch()"
        ></textarea>
        <div v-if="ticketFieldError('body')" class="form-error">{{ ticketFieldError('body') }}</div>
      </div>
      <div class="form-hint">Our team typically responds within 24 hours on business days.</div>
      <template #footer>
        <button type="button" class="btn btn-ghost" @click="modals.newTicket = false">Cancel</button>
        <button
          type="button"
          class="btn btn-primary"
          :disabled="ticketForm.processing"
          @click="submitTicket"
        >
          <AegisIcon name="send" :size="12" />
          {{ ticketForm.processing ? 'Submitting…' : 'Submit Ticket' }}
        </button>
      </template>
    </AegisModal>

    <!-- ── Ticket Detail Modal (chat bubbles) ──────── -->
    <AegisModal
      v-model="modals.ticketDetail"
      :title="selectedTicket?.subject || 'Ticket'"
      size="md"
      @close="resetReplyForm"
    >
      <div v-if="selectedTicket">
        <div class="ticket-modal-status">
          <AegisBadge :label="statusLabel(selectedTicket.status)" :variant="ticketStatusVariant(selectedTicket.status)" />
          <span class="form-hint" style="margin: 0;">Submitted {{ activity.timeAgo(selectedTicket.created_at) }}</span>
        </div>

        <div class="thread-list">
          <!-- Original ticket body -->
          <div class="thread-bubble is-user">
            <div class="thread-avatar is-gold">You</div>
            <div class="thread-content">
              <div class="thread-body" v-html="selectedTicket.body"></div>
              <div class="thread-meta">{{ activity.timeAgo(selectedTicket.created_at) }}</div>
            </div>
          </div>
          <!-- Replies -->
          <div
            v-for="r in repliesFor(selectedTicket.id)"
            :key="r.id"
            class="thread-bubble"
            :class="{ 'is-user': r.is_user }"
          >
            <div class="thread-avatar" :class="{ 'is-gold': r.is_user }">{{ r.is_user ? 'You' : 'S' }}</div>
            <div class="thread-content">
              <div class="thread-body" v-html="r.body"></div>
              <div class="thread-meta">{{ activity.timeAgo(r.created_at) }}</div>
            </div>
          </div>
        </div>

        <div v-if="!isTicketClosed(selectedTicket)" class="ticket-reply-form">
          <div class="form-group">
            <label class="form-label" for="tk-reply">Reply</label>
            <textarea
              id="tk-reply"
              v-model="replyForm.body"
              class="form-textarea"
              :class="{ 'is-error': replyFieldError('body') }"
              rows="3"
              placeholder="Add a reply…"
              @blur="v$reply.body.$touch()"
            ></textarea>
            <div v-if="replyFieldError('body')" class="form-error">{{ replyFieldError('body') }}</div>
          </div>
        </div>
      </div>

      <template #footer>
        <template v-if="selectedTicket && !isTicketClosed(selectedTicket)">
          <button type="button" class="btn btn-ghost btn-sm" @click="closeOwnTicket(selectedTicket)">
            <AegisIcon name="check" :size="12" />
            Mark Resolved
          </button>
          <button
            type="button"
            class="btn btn-primary btn-sm"
            :disabled="replyForm.processing"
            @click="submitReply"
          >
            <AegisIcon name="send" :size="12" />
            {{ replyForm.processing ? 'Sending…' : 'Send Reply' }}
          </button>
        </template>
        <button v-else type="button" class="btn btn-ghost" @click="modals.ticketDetail = false">Close</button>
      </template>
    </AegisModal>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisHeroBanner from '@/components/ui/AegisHeroBanner.vue'
import AegisStatChip from '@/components/ui/AegisStatChip.vue'
import { useToast } from '@/composables/useToast'
import { useActivity } from '@/composables/useActivity'
import { useVuelidate } from '@vuelidate/core'
import { required, minLength, maxLength, helpers } from '@vuelidate/validators'

const props = defineProps({
  tickets:         { type: Array,  default: () => [] },
  feedbackHistory: { type: Array,  default: () => [] },
  helpByCategory:  { type: Array,  default: () => [] },
  ticketReplies:   { type: Object, default: () => ({}) },
  openCount:       { type: Number, default: 0 },
  resolvedCount:   { type: Number, default: 0 },
  currentUserId:   { type: String, default: '' },
})

const toast    = useToast()
const activity = useActivity()

const tabs = [
  { key: 'tickets',  label: 'My Tickets',  icon: 'inbox' },
  { key: 'feedback', label: 'Feedback',    icon: 'message-square' },
  { key: 'help',     label: 'Help Center', icon: 'book-open' },
]
const active = ref('tickets')

const modals = reactive({
  newTicket:    false,
  ticketDetail: false,
})

const selectedTicket = ref(null)
const helpQuery      = ref('')

// ── Ticket form ─────────────────────────────────────
const ticketForm = useForm({
  subject:  '',
  body:     '',
  category: 'support_ticket',
  priority: 'normal',
})

const ticketRules = computed(() => ({
  subject: {
    required: helpers.withMessage('Subject is required.',  required),
    maxLen:   helpers.withMessage('Maximum 200 characters.', maxLength(200)),
  },
  body: {
    required: helpers.withMessage('Please describe your issue.', required),
    minLen:   helpers.withMessage('Please provide more detail (min 10 characters).', minLength(10)),
  },
}))
const v$ticket = useVuelidate(ticketRules, ticketForm)

function ticketFieldError(field) {
  if (v$ticket.value[field]?.$error) return v$ticket.value[field].$errors[0]?.$message
  if (ticketForm.errors[field]) return ticketForm.errors[field]
  return null
}

function resetTicketForm() {
  ticketForm.reset()
  v$ticket.value.$reset()
}

async function submitTicket() {
  const ok = await v$ticket.value.$validate()
  if (!ok) return toast.error('Please fix the highlighted fields.')

  ticketForm.post(route('support.ticket.store'), {
    onSuccess: () => {
      modals.newTicket = false
      toast.success('Ticket submitted.')
      resetTicketForm()
    },
    onError: () => toast.error('Please check the form and try again.'),
  })
}

// ── Reply form ──────────────────────────────────────
const replyForm = useForm({ body: '' })

const replyRules = computed(() => ({
  body: {
    required: helpers.withMessage('Reply cannot be empty.', required),
    minLen:   helpers.withMessage('Minimum 5 characters.',  minLength(5)),
  },
}))
const v$reply = useVuelidate(replyRules, replyForm)

function replyFieldError(field) {
  if (v$reply.value[field]?.$error) return v$reply.value[field].$errors[0]?.$message
  if (replyForm.errors[field]) return replyForm.errors[field]
  return null
}

function resetReplyForm() {
  replyForm.reset()
  v$reply.value.$reset()
}

function openTicketDetail(t) {
  selectedTicket.value = t
  modals.ticketDetail = true
}

async function submitReply() {
  const ok = await v$reply.value.$validate()
  if (!ok) return
  replyForm.post(route('support.ticket.reply', selectedTicket.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      resetReplyForm()
      toast.success('Reply sent.')
    },
    onError: () => toast.error('Please check your reply.'),
  })
}

function closeOwnTicket(ticket) {
  router.post(route('support.ticket.close', ticket.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      modals.ticketDetail = false
      toast.success('Ticket marked as resolved.')
    },
  })
}

function isTicketClosed(t) {
  return t.status === 'closed' || t.status === 'resolved'
}

// ── Feedback form ───────────────────────────────────
// ── Portal-aware activity URL ──────────────────────────────
const page = usePage()
const supportActivityUrl = computed(() => {
  const portal = page.props.auth?.portal ?? 'provider'
  const map = {
    provider: 'provider.activity',
    cs:       'cs.activity',
    ss:       'ss.activity',
    bp:       'bp.activity',
    admin:    'admin.activity',
  }
  try { return route(map[portal] ?? 'provider.activity') + '?event_type=support' }
  catch { return '/activity?event_type=support' }
})

const feedbackCategories = [
  { value: 'feedback',         label: 'General' },
  { value: 'feature_request',  label: 'Feature Request' },
  { value: 'bug',              label: 'Bug Report' },
  { value: 'other',            label: 'Other' },
]

const feedbackForm = useForm({
  body:     '',
  category: 'feedback',
  channel:  'in_app',
})

const feedbackRules = computed(() => ({
  body: {
    required: helpers.withMessage('Please enter a message.', required),
    minLen:   helpers.withMessage('Please provide at least 5 characters.', minLength(5)),
  },
}))
const v$feedback = useVuelidate(feedbackRules, feedbackForm)

function feedbackFieldError(field) {
  if (v$feedback.value[field]?.$error) return v$feedback.value[field].$errors[0]?.$message
  if (feedbackForm.errors[field]) return feedbackForm.errors[field]
  return null
}

async function submitFeedback() {
  const ok = await v$feedback.value.$validate()
  if (!ok) return toast.error('Please fix the highlighted fields.')

  feedbackForm.post(route('support.feedback'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Thank you — your feedback was received.')
      feedbackForm.reset()
      feedbackForm.category = 'feedback'
      v$feedback.value.$reset()
    },
    onError: () => toast.error('Could not send feedback. Please try again.'),
  })
}

// ── Help center filter ──────────────────────────────
const filteredHelp = computed(() => {
  const q = helpQuery.value.trim().toLowerCase()
  if (!q) return props.helpByCategory
  return props.helpByCategory.map(s => ({
    ...s,
    articles: s.articles.filter(a =>
      a.title.toLowerCase().includes(q) || (a.body || '').toLowerCase().includes(q)
    ),
  }))
})

const hasAnyVisibleArticle = computed(() =>
  filteredHelp.value.some(s => s.articles.length > 0)
)

// ── Helpers ─────────────────────────────────────────
function statusLabel(status) {
  return {
    open:        'Open',
    pending:     'Pending',
    in_progress: 'In Progress',
    resolved:    'Resolved',
    closed:      'Closed',
    escalated:   'Escalated',
  }[status] ?? (status ?? '').replace(/_/g, ' ')
}

function ticketStatusVariant(status) {
  return {
    open:        'gold',
    pending:     'orange',
    in_progress: 'blue',
    resolved:    'green',
    closed:      'neutral',
    escalated:   'red',
  }[status] ?? 'neutral'
}

function repliesFor(ticketId) {
  return props.ticketReplies?.[ticketId] ?? []
}

function replyCount(ticketId) {
  return repliesFor(ticketId).length
}
</script>

<style scoped>
/* ── Tabs ──────────────────────────────────────────── */
.tab-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 18px;
  height: 18px;
  padding: 0 0.3rem;
  border-radius: var(--radius-full);
  background: var(--gold-dark);
  color: var(--text-inverted);
  font-size: 10px;
  font-weight: 700;
  margin-left: 0.4rem;
}

/* ── Panes ─────────────────────────────────────────── */
.support-pane { padding-top: 1.25rem; }

/* ── Empty state ──────────────────────────────────── */
.support-empty {
  text-align: center;
  padding: 3rem 1.5rem;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
}
.support-empty-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  border-radius: var(--radius-full);
  background: var(--badge-bg-gold);
  color: var(--gold-dark);
  margin-bottom: 1rem;
}
.support-empty-title {
  font-family: var(--font-serif);
  font-size: 16px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 0.5rem;
}
.support-empty-text {
  font-size: 13px;
  color: var(--text-3);
  line-height: 1.6;
  max-width: 480px;
  margin: 0 auto;
}

/* ── Ticket list ────────────────────────────────────── */
.ticket-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.ticket-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.25rem;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  cursor: pointer;
  transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
}
.ticket-card:hover {
  border-color: var(--gold-light);
  box-shadow: var(--shadow);
}
.ticket-card-icon {
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
.ticket-card-body { flex: 1; min-width: 0; }
.ticket-card-subject {
  font-size: 14px;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 0.2rem;
}
.ticket-card-meta {
  display: flex;
  gap: 0.75rem;
  font-size: 12px;
  color: var(--text-3);
}
.ticket-card-meta span + span::before {
  content: '·';
  margin-right: 0.5rem;
  color: var(--text-4);
}
.ticket-card-actions { flex-shrink: 0; }

/* ── Feedback ─────────────────────────────────────── */
.feedback-composer { margin-bottom: 1.25rem; }
.feedback-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}
.feedback-chip {
  padding: 0.4rem 0.9rem;
  border-radius: var(--radius-full);
  border: 1px solid var(--border);
  background: var(--surface);
  color: var(--text-2);
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  transition: border-color var(--transition-fast),
              background var(--transition-fast),
              color var(--transition-fast);
}
.feedback-chip:hover {
  border-color: var(--gold);
  color: var(--text);
}
.feedback-chip.active {
  background: var(--badge-bg-gold);
  border-color: var(--gold);
  color: var(--gold-dark);
}

.feedback-history-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  color: var(--text-4);
  margin: 1rem 0 0.625rem;
}
.feedback-entry {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  padding: 1rem 1.125rem;
  margin-bottom: 0.5rem;
}
.feedback-entry-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.4rem;
}
.feedback-entry-subject {
  font-size: 13px;
  font-weight: 600;
  color: var(--text);
}
.feedback-entry-meta {
  font-size: 11px;
  color: var(--text-4);
}
.feedback-entry-body {
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.55;
  white-space: pre-line;
}

/* ── Help center ─────────────────────────────────── */
.help-search-bar { margin-bottom: 1.25rem; }
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

.help-cat-section { margin-bottom: 1.5rem; }
.help-cat-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: var(--gold-dark);
  margin-bottom: 0.6rem;
}
.help-article {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  margin-bottom: 0.4rem;
}
.help-article-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.85rem 1.125rem;
  font-size: 14px;
  font-weight: 600;
  color: var(--text);
  cursor: pointer;
  list-style: none;
  user-select: none;
}
.help-article-header::-webkit-details-marker { display: none; }
.help-chevron {
  display: inline-flex;
  align-items: center;
  color: var(--text-3);
  transition: transform var(--transition-fast);
}
.help-article[open] .help-chevron { transform: rotate(180deg); }
.help-article[open] .help-article-header { border-bottom: 1px solid var(--border); }
.help-article-body {
  padding: 0.875rem 1.125rem;
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.65;
  white-space: pre-line;
}

/* ── Ticket detail modal — thread bubbles ────────── */
.ticket-modal-status {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}
.thread-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1rem;
  max-height: 360px;
  overflow-y: auto;
}
.thread-bubble {
  display: flex;
  gap: 0.625rem;
  align-items: flex-start;
}
.thread-bubble.is-user { flex-direction: row-reverse; }
.thread-avatar {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-full);
  background: var(--surface-3);
  color: var(--text-2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  flex-shrink: 0;
}
.thread-avatar.is-gold {
  background: var(--badge-bg-gold);
  color: var(--gold-dark);
}
.thread-content { max-width: 75%; min-width: 0; }
.thread-bubble.is-user .thread-content { text-align: right; }
.thread-body {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 0.625rem 0.875rem;
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.55;
  white-space: pre-line;
}
.thread-bubble.is-user .thread-body {
  background: var(--badge-bg-gold);
  border-color: var(--soft-gold);
  color: var(--text);
}
.thread-meta {
  font-size: 11px;
  color: var(--text-4);
  margin-top: 0.3rem;
}
.ticket-reply-form { margin-top: 0.75rem; }
</style>
