<!--
  pages/shared/Support.vue — 3-tab support page.
  Tabs: My Tickets · Feedback · Help Center.
  Modals: New Ticket · Ticket Detail (with reply) · Feedback.
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
        <a :href="route('activity.index')" class="btn-hero-ghost is-on-light">
          <AegisIcon name="activity" :size="14" />
          Activity
        </a>
        <button type="button" class="btn-hero-solid is-on-light" @click="openNewTicket">
          <AegisIcon name="plus" :size="14" />
          New ticket
        </button>
      </template>
    </AegisHeroBanner>

    <div class="stat-chips-row">
      <AegisStatChip icon="inbox" :value="tickets.length" label="My tickets" />
      <AegisStatChip
        icon="clock"
        :value="openCount"
        label="Open"
        bg-color="var(--badge-bg-gold)"
        icon-color="var(--gold-dark)"
      />
      <AegisStatChip
        icon="check-circle"
        :value="resolvedCount"
        label="Resolved"
        bg-color="var(--badge-bg-green)"
        icon-color="var(--green-dark)"
      />
    </div>

    <!-- Tabs -->
    <nav class="tabs-segmented" role="tablist">
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
      </button>
    </nav>

    <!-- ── My Tickets ─────────────────────────────────── -->
    <section v-show="active === 'tickets'" class="support-pane">
      <div class="card">
        <div class="card-header">
          <div class="card-title">My tickets</div>
          <div class="card-header-actions">
            <button type="button" class="btn btn-primary btn-sm" @click="openNewTicket">
              <AegisIcon name="plus" :size="12" />
              <span>New ticket</span>
            </button>
          </div>
        </div>
        <div class="card-body">
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
                <td>
                  <AegisBadge :label="t.status" :variant="ticketStatusVariant(t.status)" />
                </td>
                <td class="text-muted">{{ activity.timeAgo(t.created_at) }}</td>
                <td>
                  <button
                    type="button"
                    class="btn btn-ghost btn-sm"
                    @click="viewTicket(t)"
                  >View</button>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-else class="empty-state">
            <div class="empty-state-icon"><AegisIcon name="inbox" :size="28" /></div>
            <div class="empty-state-title">No tickets yet</div>
            <div class="empty-state-sub">When you open a ticket, it will appear here.</div>
          </div>
        </div>
      </div>
    </section>

    <!-- ── Feedback ───────────────────────────────────── -->
    <section v-show="active === 'feedback'" class="support-pane">
      <div class="card">
        <div class="card-header">
          <div class="card-title">Send feedback</div>
        </div>
        <div class="card-body">
          <form @submit.prevent="submitFeedback">
            <div class="form-group">
              <label class="form-label" for="fb-category">Category</label>
              <select
                id="fb-category"
                v-model="feedbackForm.category"
                class="form-input"
                :class="{ 'is-error': feedbackErrors.category }"
              >
                <option value="">Select a category</option>
                <option value="suggestion">Suggestion</option>
                <option value="bug">Bug report</option>
                <option value="praise">Praise</option>
                <option value="other">Other</option>
              </select>
              <div v-if="feedbackErrors.category" class="form-error">{{ feedbackErrors.category }}</div>
            </div>
            <div class="form-group">
              <label class="form-label" for="fb-body">Message</label>
              <textarea
                id="fb-body"
                v-model="feedbackForm.body"
                class="form-input"
                :class="{ 'is-error': feedbackErrors.body }"
                rows="6"
                placeholder="Tell us what's on your mind…"
              ></textarea>
              <div v-if="feedbackErrors.body" class="form-error">{{ feedbackErrors.body }}</div>
            </div>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="feedbackForm.processing"
            >{{ feedbackForm.processing ? 'Sending…' : 'Send feedback' }}</button>
          </form>
        </div>
      </div>
    </section>

    <!-- ── Help Center ────────────────────────────────── -->
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

      <div v-if="filteredArticles.length" class="help-articles-grid">
        <a
          v-for="a in filteredArticles"
          :key="a.id"
          :href="route('help.index')"
          class="help-article-card"
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

      <div v-else class="empty-state">
        <div class="empty-state-icon"><AegisIcon name="book" :size="28" /></div>
        <div class="empty-state-title">No matching articles</div>
        <div class="empty-state-sub">Try a broader search, or open a ticket above.</div>
      </div>
    </section>

    <!-- ── New Ticket Modal ───────────────────────────── -->
    <AegisModal
      v-model="modals.newTicket"
      title="New Support Ticket"
      size="md"
      @close="resetTicketForm"
    >
      <form @submit.prevent="submitTicket">
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
            <label class="form-label" for="tk-category">Category <span class="text-danger">*</span></label>
            <select
              id="tk-category"
              v-model="ticketForm.category"
              class="form-input"
              :class="{ 'is-error': ticketFieldError('category') }"
              @blur="v$ticket.category.$touch()"
            >
              <option value="">Select category</option>
              <option value="technical">Technical issue</option>
              <option value="billing">Billing</option>
              <option value="account">Account</option>
              <option value="feature">Feature request</option>
              <option value="other">Other</option>
            </select>
            <div v-if="ticketFieldError('category')" class="form-error">{{ ticketFieldError('category') }}</div>
          </div>
          <div class="form-group">
            <label class="form-label" for="tk-priority">Priority</label>
            <select
              id="tk-priority"
              v-model="ticketForm.priority"
              class="form-input"
            >
              <option value="normal">Normal</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="tk-body">Description <span class="text-danger">*</span></label>
          <textarea
            id="tk-body"
            v-model="ticketForm.body"
            class="form-input"
            :class="{ 'is-error': ticketFieldError('body') }"
            rows="5"
            placeholder="Please describe your issue in detail…"
            @blur="v$ticket.body.$touch()"
          ></textarea>
          <div v-if="ticketFieldError('body')" class="form-error">{{ ticketFieldError('body') }}</div>
        </div>
      </form>
      <template #footer>
        <button type="button" class="btn btn-ghost" @click="modals.newTicket = false">Cancel</button>
        <button
          type="button"
          class="btn btn-primary"
          :disabled="ticketForm.processing"
          @click="submitTicket"
        >{{ ticketForm.processing ? 'Submitting…' : 'Submit ticket' }}</button>
      </template>
    </AegisModal>

    <!-- ── Ticket Detail Modal ────────────────────────── -->
    <AegisModal
      v-model="modals.ticketDetail"
      title="Ticket Details"
      size="lg"
      @close="resetReplyForm"
    >
      <template v-if="selectedTicket">
        <div class="ticket-detail-header">
          <div class="ticket-detail-subject">{{ selectedTicket.subject }}</div>
          <div class="ticket-detail-meta">
            <AegisBadge :label="selectedTicket.status" :variant="ticketStatusVariant(selectedTicket.status)" />
            <span class="text-muted">Opened {{ activity.timeAgo(selectedTicket.created_at) }}</span>
          </div>
        </div>
        <div class="ticket-detail-body">{{ selectedTicket.body }}</div>

        <div v-if="selectedTicket.replies?.length" class="ticket-replies">
          <div
            v-for="reply in selectedTicket.replies"
            :key="reply.id"
            class="ticket-reply"
            :class="{ 'ticket-reply--support': !reply.is_user }"
          >
            <div class="ticket-reply-meta">
              <strong>{{ reply.author_name ?? (reply.is_user ? 'You' : 'Aegis Support') }}</strong>
              <span class="text-muted">{{ activity.timeAgo(reply.created_at) }}</span>
            </div>
            <div class="ticket-reply-body">{{ reply.body }}</div>
          </div>
        </div>

        <div v-if="selectedTicket.status !== 'closed' && selectedTicket.status !== 'resolved'" class="ticket-reply-form">
          <label class="form-label">Reply</label>
          <textarea
            v-model="replyForm.body"
            class="form-input"
            :class="{ 'is-error': replyFieldError('body') }"
            rows="3"
            placeholder="Write your reply…"
            @blur="v$reply.body.$touch()"
          ></textarea>
          <div v-if="replyFieldError('body')" class="form-error">{{ replyFieldError('body') }}</div>
        </div>
      </template>
      <template #footer>
        <button
          v-if="selectedTicket?.status !== 'closed'"
          type="button"
          class="btn btn-outline btn-sm"
          @click="closeTicket(selectedTicket)"
        >Close ticket</button>
        <button type="button" class="btn btn-ghost" @click="modals.ticketDetail = false">Cancel</button>
        <button
          v-if="selectedTicket?.status !== 'closed' && selectedTicket?.status !== 'resolved'"
          type="button"
          class="btn btn-primary"
          :disabled="replyForm.processing"
          @click="submitReply"
        >{{ replyForm.processing ? 'Sending…' : 'Send reply' }}</button>
      </template>
    </AegisModal>
  </AppLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisHeroBanner from '@/components/ui/AegisHeroBanner.vue'
import AegisStatChip from '@/components/ui/AegisStatChip.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import { useToast } from '@/composables/useToast'
import { useActivity } from '@/composables/useActivity'
import { useVuelidate } from '@vuelidate/core'
import { required, minLength, maxLength, helpers } from '@vuelidate/validators'

const props = defineProps({
  tickets:      { type: Array, default: () => [] },
  helpArticles: { type: Array, default: () => [] },
  initialTab:   { type: String, default: 'tickets' },
})

const toast = useToast()
const activity = useActivity()

const active = ref(props.initialTab)
const tabs = [
  { key: 'tickets',  label: 'My Tickets',  icon: 'inbox' },
  { key: 'feedback', label: 'Feedback',     icon: 'message-square' },
  { key: 'help',     label: 'Help Center',  icon: 'book-open' },
]

const modals = reactive({
  newTicket:    false,
  ticketDetail: false,
})
const selectedTicket = ref(null)
const helpQuery = ref('')

// Computed stats
const openCount     = computed(() => props.tickets.filter((t) => ['open','in_progress','pending'].includes(t.status)).length)
const resolvedCount = computed(() => props.tickets.filter((t) => ['resolved','closed'].includes(t.status)).length)

const filteredArticles = computed(() => {
  const q = helpQuery.value.trim().toLowerCase()
  if (!q) return props.helpArticles
  return props.helpArticles.filter((a) =>
    `${a.title ?? ''} ${a.summary ?? ''} ${a.excerpt ?? ''}`.toLowerCase().includes(q),
  )
})

// ── New Ticket ────────────────────────────────────────────────
const ticketForm = useForm({
  subject:  '',
  body:     '',
  category: '',
  priority: 'normal',
})

const ticketRules = computed(() => ({
  subject:  {
    required: helpers.withMessage('Subject is required.', required),
    maxLen:   helpers.withMessage('Maximum 200 characters.', maxLength(200)),
  },
  body:     {
    required: helpers.withMessage('Please describe your issue.', required),
    minLen:   helpers.withMessage('Please provide more detail (min 10 characters).', minLength(10)),
  },
  category: {
    required: helpers.withMessage('Please select a category.', required),
  },
}))
const v$ticket = useVuelidate(ticketRules, ticketForm)

function ticketFieldError(field) {
  if (v$ticket.value[field]?.$error) return v$ticket.value[field].$errors[0]?.$message
  if (ticketForm.errors[field]) return ticketForm.errors[field]
  return null
}

function openNewTicket() {
  modals.newTicket = true
}

function resetTicketForm() {
  ticketForm.reset()
  v$ticket.value.$reset()
}

async function submitTicket() {
  const valid = await v$ticket.value.$validate()
  if (!valid) return toast.error('Please fix the highlighted fields.')
  ticketForm.post(route('support.ticket.store'), {
    onSuccess: () => {
      modals.newTicket = false
      toast.success('Ticket submitted. We\'ll be in touch soon.')
      resetTicketForm()
    },
    onError: () => toast.error('Please check the form and try again.'),
  })
}

// ── Reply ─────────────────────────────────────────────────────
const replyForm = useForm({ body: '' })

const replyRules = computed(() => ({
  body: {
    required: helpers.withMessage('Reply cannot be empty.', required),
    minLen:   helpers.withMessage('Minimum 5 characters.', minLength(5)),
  },
}))
const v$reply = useVuelidate(replyRules, replyForm)

function replyFieldError(field) {
  if (v$reply.value[field]?.$error) return v$reply.value[field].$errors[0]?.$message
  if (replyForm.errors[field]) return replyForm.errors[field]
  return null
}

function viewTicket(ticket) {
  selectedTicket.value = ticket
  modals.ticketDetail = true
}

function resetReplyForm() {
  replyForm.reset()
  v$reply.value.$reset()
}

async function submitReply() {
  const valid = await v$reply.value.$validate()
  if (!valid) return
  replyForm.post(route('support.ticket.reply', selectedTicket.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      resetReplyForm()
      toast.success('Reply sent.')
    },
    onError: () => toast.error('Please check your reply.'),
  })
}

function closeTicket(ticket) {
  router.post(route('support.ticket.close', ticket.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      modals.ticketDetail = false
      toast.success('Ticket closed.')
    },
  })
}

// ── Feedback ──────────────────────────────────────────────────
const feedbackForm = useForm({
  category: '',
  body:     '',
})

const feedbackErrors = computed(() => feedbackForm.errors)

async function submitFeedback() {
  if (!feedbackForm.category) {
    feedbackForm.errors.category = 'Please select a category.'
    return
  }
  if (!feedbackForm.body.trim() || feedbackForm.body.length < 5) {
    feedbackForm.errors.body = 'Please provide at least 5 characters.'
    return
  }
  feedbackForm.post(route('support.feedback'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Thank you — your feedback was received.')
      feedbackForm.reset()
    },
    onError: () => toast.error('Could not send feedback. Please try again.'),
  })
}

// ── Helpers ───────────────────────────────────────────────────
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
</script>

<style scoped>
.support-pane {
  padding-top: 1.25rem;
}

/* Help center */
.help-search-bar {
  margin-bottom: 1.25rem;
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
.help-article-title { font-weight: 600; font-size: 14px; color: var(--text); margin-bottom: 0.2rem; }
.help-article-snippet { font-size: 12px; color: var(--text-3); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.help-article-arrow { color: var(--text-4); flex-shrink: 0; }

/* Ticket detail modal */
.ticket-detail-header { margin-bottom: 1rem; }
.ticket-detail-subject {
  font-family: var(--font-serif);
  font-size: 15px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 0.35rem;
}
.ticket-detail-meta {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 12px;
  color: var(--text-3);
}
.ticket-detail-body {
  font-size: 14px;
  color: var(--text-2);
  line-height: 1.6;
  padding: 0.85rem 1rem;
  background: var(--surface-2);
  border-radius: var(--radius-sm);
  margin-bottom: 1rem;
}
.ticket-replies {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  margin-bottom: 1rem;
}
.ticket-reply {
  padding: 0.75rem 1rem;
  border-radius: var(--radius-sm);
  background: var(--surface-2);
  border-left: 3px solid var(--border-dark);
}
.ticket-reply--support { border-left-color: var(--gold-dark); background: var(--badge-bg-gold); }
.ticket-reply-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 12px;
  margin-bottom: 0.3rem;
  color: var(--text-3);
}
.ticket-reply-meta strong { font-size: 12px; font-weight: 600; color: var(--text); }
.ticket-reply-body { font-size: 13px; color: var(--text-2); line-height: 1.5; }
.ticket-reply-form { margin-top: 0.75rem; }
</style>
