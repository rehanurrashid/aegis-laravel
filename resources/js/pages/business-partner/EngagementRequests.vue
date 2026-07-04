<!--
  pages/business-partner/EngagementRequests.vue
  Route: GET /business/engagement-requests  →  bp.engagement-requests.index

  Shows all BpEngagementRequest rows directed at this BP.
  BP can Accept, Decline, or Reply (add a note without changing status).

  Accept flow:
    - hire   → BpJob (direct, status=filled) is auto-created server-side
               → practitioner can now build a contract from it
    - quote  → marked accepted + response note sent to practitioner
    - consult→ marked accepted (confirm meeting) + note sent

  Decline → both parties notified, request marked declined.
  Reply   → adds a response note without changing status (request for more info).
-->
<template>
  <AppLayout>

    <AegisHeroBanner
      eyebrow="Work"
      title="Engagement Requests"
      subtitle="Hire, quote, and consultation requests sent directly to your profile by practitioners."
      quiet
    >
      <template #actions>
        <a :href="route('bp.jobs.index')" class="btn-hero-ghost is-on-light">
          <AegisIcon name="search" :size="14" /> Find Jobs
        </a>
      </template>
    </AegisHeroBanner>

    <!-- Stat chips -->
    <div class="stat-chips-row">
      <AegisStatChip icon="clock"   :value="stats.pending"  label="Pending" />
      <AegisStatChip icon="check"   :value="stats.accepted" label="Accepted" />
      <AegisStatChip icon="x"       :value="stats.declined" label="Declined" />
      <AegisStatChip icon="briefcase" :value="stats.total"  label="Total" />
    </div>

    <!-- Filters -->
    <div class="page-toolbar" style="margin-bottom:16px">
      <div class="tab-bar-secondary">
        <button
          v-for="f in filters"
          :key="f.key"
          :class="['tab-secondary', activeFilter === f.key ? 'active' : '']"
          @click="activeFilter = f.key"
        >{{ f.label }}<span v-if="f.count" class="tab-count">{{ f.count }}</span></button>
      </div>
      <div class="toolbar-right">
        <div class="search-wrap">
          <AegisIcon name="search" :size="14" class="search-icon" />
          <input v-model="search" type="text" class="form-input search-input" placeholder="Search by practitioner or service…">
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <AegisEmptyState
      v-if="!filtered.length"
      icon="briefcase"
      title="No requests found"
      :description="activeFilter === 'all' ? 'Practitioners can send hire, quote, and consultation requests directly from your public profile.' : 'No ' + activeFilter + ' requests.'"
    />

    <!-- Requests table -->
    <div v-else class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Practitioner</th>
            <th>Type</th>
            <th>Details</th>
            <th>Status</th>
            <th>Received</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in pagedRequests" :key="r.id">

            <!-- Practitioner -->
            <td>
              <div class="ber-prac-cell">
                <div class="avatar avatar-sm avatar-gold" style="border-radius:var(--radius-sm);font-size:11px;font-weight:700;flex-shrink:0">
                  {{ r.practitioner?.avatar_initials ?? '??' }}
                </div>
                <div>
                  <a v-if="r.practitioner?.slug" :href="route('public.provider', r.practitioner.slug)" class="ber-prac-link" target="_blank">
                    {{ r.practitioner?.display_name ?? '—' }}
                  </a>
                  <span v-else class="ber-prac-name">{{ r.practitioner?.display_name ?? '—' }}</span>
                  <div class="ber-prac-sub">Practitioner</div>
                </div>
              </div>
            </td>

            <!-- Type -->
            <td>
              <span :class="['badge', typeBadge(r.type)]">
                <AegisIcon :name="typeIcon(r.type)" :size="10" />
                {{ typeLabel(r.type) }}
              </span>
            </td>

            <!-- Details -->
            <td class="ber-details-cell">
              <div class="ber-details-primary">{{ primaryDetail(r) }}</div>
              <div v-if="r.start_date" class="ber-details-sub">
                <AegisIcon name="calendar" :size="10" /> {{ r.start_date }}
              </div>
              <div v-if="r.budget" class="ber-details-sub">
                <AegisIcon name="dollar" :size="10" /> {{ r.budget }}
              </div>
              <span v-if="r.urgent" class="badge badge-orange" style="font-size:10px;margin-top:3px">Urgent</span>
              <div v-if="r.include_nda || r.require_baa" class="ber-flags">
                <span v-if="r.include_nda" class="badge badge-blue" style="font-size:10px">NDA</span>
                <span v-if="r.require_baa" class="badge badge-blue" style="font-size:10px">BAA</span>
              </div>
            </td>

            <!-- Status -->
            <td>
              <span :class="['badge', statusBadge(r.status)]">
                {{ r.status.charAt(0).toUpperCase() + r.status.slice(1) }}
              </span>
              <div v-if="r.response_note" class="ber-note">{{ r.response_note }}</div>
            </td>

            <!-- Received -->
            <td class="ber-date-cell">{{ r.created_at }}</td>

            <!-- Actions -->
            <td>
              <div class="ber-actions-cell">
                <button class="btn-icon" data-tooltip="View details" @click="openDetail(r)">
                  <AegisIcon name="eye" :size="13" />
                </button>
                <button v-if="r.status === 'pending'" class="btn-icon" data-tooltip="Accept" @click="openAccept(r)">
                  <AegisIcon name="check" :size="13" />
                </button>
                <button v-if="r.status === 'pending'" class="btn-icon btn-icon-danger" data-tooltip="Decline" @click="openDecline(r)">
                  <AegisIcon name="x" :size="13" />
                </button>
                <button v-if="r.status === 'pending'" class="btn-icon" data-tooltip="Reply" @click="openReply(r)">
                  <AegisIcon name="message-square" :size="13" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="ber-pagination">
      <button class="btn btn-outline btn-sm" :disabled="page === 1" @click="page--">
        <AegisIcon name="chevron-left" :size="13" /> Prev
      </button>
      <span class="ber-page-info">{{ page }} / {{ totalPages }}</span>
      <button class="btn btn-outline btn-sm" :disabled="page === totalPages" @click="page++">
        Next <AegisIcon name="chevron-right" :size="13" />
      </button>
    </div>

    <!-- Detail Modal -->
    <AegisModal v-model="modals.detail" :title="activeRequest ? typeLabel(activeRequest.type) + ' Request' : 'Request Details'" size="lg">
      <template v-if="activeRequest">

        <!-- Practitioner strip -->
        <div class="ber-strip">
          <div class="avatar avatar-md avatar-gold" style="border-radius:var(--radius-sm);font-size:14px;font-weight:700;flex-shrink:0">
            {{ activeRequest.practitioner?.avatar_initials ?? '??' }}
          </div>
          <div class="ber-strip-info">
            <div class="ber-strip-name">{{ activeRequest.practitioner?.display_name ?? 'Practitioner' }}</div>
            <div class="ber-strip-meta">
              <span :class="['badge', typeBadge(activeRequest.type)]"><AegisIcon :name="typeIcon(activeRequest.type)" :size="10" />{{ typeLabel(activeRequest.type) }}</span>
              <span :class="['badge', statusBadge(activeRequest.status)]">{{ activeRequest.status.charAt(0).toUpperCase() + activeRequest.status.slice(1) }}</span>
              <span v-if="activeRequest.urgent" class="badge badge-orange">Urgent</span>
            </div>
          </div>
          <a v-if="activeRequest.practitioner?.slug" :href="route('public.provider', activeRequest.practitioner.slug)" target="_blank" class="btn btn-outline btn-sm">
            <AegisIcon name="eye" :size="13" /> View Profile
          </a>
        </div>

        <!-- Type-specific details -->
        <div class="ber-detail-label">{{ typeLabel(activeRequest.type) }} Details</div>
        <div class="ber-detail-grid">
          <!-- Hire -->
          <template v-if="activeRequest.type === 'hire'">
            <div class="ber-detail-row"><span class="ber-detail-key">Engagement Type</span><span class="ber-detail-val">{{ activeRequest.engagement_type || '—' }}</span></div>
            <div class="ber-detail-row"><span class="ber-detail-key">Start Date</span><span class="ber-detail-val">{{ activeRequest.start_date || '—' }}</span></div>
            <div class="ber-detail-row"><span class="ber-detail-key">Duration</span><span class="ber-detail-val">{{ activeRequest.duration || '—' }}</span></div>
            <div class="ber-detail-row"><span class="ber-detail-key">Budget</span><span class="ber-detail-val">{{ activeRequest.budget || '—' }}</span></div>
            <div class="ber-detail-row"><span class="ber-detail-key">Payment Terms</span><span class="ber-detail-val">{{ activeRequest.payment_terms || '—' }}</span></div>
            <div v-if="activeRequest.notes" class="ber-detail-row ber-detail-row--full"><span class="ber-detail-key">Scope / Notes</span><span class="ber-detail-val">{{ activeRequest.notes }}</span></div>
            <div v-if="activeRequest.include_nda || activeRequest.require_baa" class="ber-detail-row ber-detail-row--full">
              <span class="ber-detail-key">Agreements</span>
              <span class="ber-detail-val">
                <span v-if="activeRequest.include_nda" class="badge badge-blue" style="margin-right:4px">NDA Requested</span>
                <span v-if="activeRequest.require_baa" class="badge badge-blue">HIPAA BAA Requested</span>
              </span>
            </div>
          </template>

          <!-- Quote -->
          <template v-else-if="activeRequest.type === 'quote'">
            <div class="ber-detail-row"><span class="ber-detail-key">Service</span><span class="ber-detail-val">{{ activeRequest.service || '—' }}</span></div>
            <div class="ber-detail-row"><span class="ber-detail-key">Practice Size</span><span class="ber-detail-val">{{ activeRequest.size || '—' }}</span></div>
            <div class="ber-detail-row"><span class="ber-detail-key">Budget</span><span class="ber-detail-val">{{ activeRequest.budget || '—' }}</span></div>
            <div class="ber-detail-row"><span class="ber-detail-key">Timeline</span><span class="ber-detail-val">{{ activeRequest.timeline || '—' }}</span></div>
            <div v-if="activeRequest.notes" class="ber-detail-row ber-detail-row--full"><span class="ber-detail-key">Notes</span><span class="ber-detail-val">{{ activeRequest.notes }}</span></div>
          </template>

          <!-- Consultation -->
          <template v-else-if="activeRequest.type === 'consultation'">
            <div class="ber-detail-row"><span class="ber-detail-key">Meeting Type</span><span class="ber-detail-val">{{ activeRequest.meeting_type || '—' }}</span></div>
            <div class="ber-detail-row"><span class="ber-detail-key">Date</span><span class="ber-detail-val">{{ activeRequest.start_date || '—' }}</span></div>
            <div class="ber-detail-row"><span class="ber-detail-key">Time</span><span class="ber-detail-val">{{ activeRequest.preferred_time || '—' }}</span></div>
            <div class="ber-detail-row"><span class="ber-detail-key">Duration</span><span class="ber-detail-val">{{ activeRequest.meeting_duration || '—' }}</span></div>
            <div class="ber-detail-row"><span class="ber-detail-key">Timezone</span><span class="ber-detail-val">{{ activeRequest.timezone || '—' }}</span></div>
            <div v-if="activeRequest.agenda" class="ber-detail-row ber-detail-row--full"><span class="ber-detail-key">Agenda</span><span class="ber-detail-val">{{ activeRequest.agenda }}</span></div>
          </template>
        </div>

        <!-- Response note (if already replied) -->
        <div v-if="activeRequest.response_note" class="ber-response-note">
          <div class="ber-detail-label">Your Response</div>
          <p>{{ activeRequest.response_note }}</p>
        </div>

        <!-- Received timestamp -->
        <div class="ber-meta-row">
          <span class="ber-meta-item"><AegisIcon name="clock" :size="12" /> Received {{ activeRequest.created_at }}</span>
        </div>
      </template>
      <template #footer>
        <button class="btn btn-outline" @click="modals.detail = false">Close</button>
        <template v-if="activeRequest?.status === 'pending'">
          <button class="btn btn-ghost" @click="() => { modals.detail = false; openDecline(activeRequest) }">
            <AegisIcon name="x" :size="13" /> Decline
          </button>
          <button class="btn btn-primary" @click="() => { modals.detail = false; openAccept(activeRequest) }">
            <AegisIcon name="check" :size="13" /> Accept
          </button>
        </template>
      </template>
    </AegisModal>

    <!-- Accept Modal -->
    <AegisModal v-model="modals.accept" title="Accept Request" size="md">
      <template v-if="activeRequest">
        <p class="modal-intro-text">
          <template v-if="activeRequest.type === 'hire'">
            Accepting creates a direct engagement job in your pipeline. The practitioner will be notified and you can proceed to a contract.
          </template>
          <template v-else-if="activeRequest.type === 'quote'">
            You're confirming interest. Use the response note to send your quote or pricing details.
          </template>
          <template v-else>
            You're confirming the consultation. Use the response note to confirm the time or suggest an alternative.
          </template>
        </p>
        <div class="form-group">
          <label class="form-label">Response Note <span class="form-label-opt">(optional)</span></label>
          <textarea class="form-textarea" rows="3"
            :placeholder="activeRequest.type === 'quote' ? 'e.g. Based on your requirements, our quote is $2,500 fixed for a 3-month engagement…' : activeRequest.type === 'consultation' ? 'e.g. Confirmed! Looking forward to speaking on ' + (activeRequest.start_date ?? 'the requested date') + '…' : 'e.g. Great — let\'s get started. I\'ll reach out via Aegis Messages to discuss next steps…'"
            v-model="acceptForm.response_note"
          ></textarea>
        </div>
      </template>
      <template #footer>
        <button class="btn btn-outline" :disabled="acceptForm.processing" @click="modals.accept = false">Cancel</button>
        <button class="btn btn-primary" :disabled="acceptForm.processing" @click="submitAccept">
          <AegisIcon name="check" :size="13" /> {{ acceptForm.processing ? 'Accepting…' : 'Accept Request' }}
        </button>
      </template>
    </AegisModal>

    <!-- Decline Modal -->
    <AegisModal v-model="modals.decline" title="Decline Request" size="md">
      <p class="modal-intro-text">The practitioner will be notified that you're unavailable for this request.</p>
      <div class="form-group">
        <label class="form-label">Reason <span class="form-label-opt">(optional)</span></label>
        <textarea class="form-textarea" rows="3"
          placeholder="e.g. We're at capacity for the requested start date, but happy to reconnect in Q4…"
          v-model="declineForm.response_note"
        ></textarea>
      </div>
      <template #footer>
        <button class="btn btn-outline" :disabled="declineForm.processing" @click="modals.decline = false">Cancel</button>
        <button class="btn btn-danger" :disabled="declineForm.processing" @click="submitDecline">
          <AegisIcon name="x" :size="13" /> {{ declineForm.processing ? 'Declining…' : 'Decline Request' }}
        </button>
      </template>
    </AegisModal>

    <!-- Reply Modal -->
    <AegisModal v-model="modals.reply" title="Send a Reply" size="md">
      <p class="modal-intro-text">Add a note to the practitioner without accepting or declining — useful to ask clarifying questions.</p>
      <div class="form-group">
        <label class="form-label">Message <span class="req">*</span></label>
        <textarea class="form-textarea" rows="4"
          placeholder="e.g. Could you share more detail about the scope? We'd also need a BAA signed before starting…"
          v-model="replyForm.response_note"
        ></textarea>
      </div>
      <div v-if="replyForm.errors.response_note" class="alert alert-danger">{{ replyForm.errors.response_note }}</div>
      <template #footer>
        <button class="btn btn-outline" :disabled="replyForm.processing" @click="modals.reply = false">Cancel</button>
        <button class="btn btn-primary" :disabled="replyForm.processing" @click="submitReply">
          <AegisIcon name="send" :size="13" /> {{ replyForm.processing ? 'Sending…' : 'Send Reply' }}
        </button>
      </template>
    </AegisModal>

  </AppLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisHeroBanner from '@/components/ui/AegisHeroBanner.vue'
import AegisStatChip from '@/components/ui/AegisStatChip.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import AegisModal from '@/components/ui/AegisModal.vue'
import AegisIcon from '@/components/ui/AegisIcon.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  requests: { type: Array,  default: () => [] },
  stats:    { type: Object, default: () => ({ pending: 0, accepted: 0, declined: 0, total: 0 }) },
})

const toast = useToast()

// ── Filters ───────────────────────────────────────────────────────────────
const activeFilter = ref('all')
const search       = ref('')

const filters = computed(() => [
  { key: 'all',         label: 'All',         count: props.stats.total },
  { key: 'pending',     label: 'Pending',     count: props.stats.pending },
  { key: 'accepted',    label: 'Accepted',    count: props.stats.accepted },
  { key: 'declined',    label: 'Declined',    count: props.stats.declined },
  { key: 'hire',        label: 'Hire',        count: props.requests.filter(r => r.type === 'hire').length },
  { key: 'quote',       label: 'Quote',       count: props.requests.filter(r => r.type === 'quote').length },
  { key: 'consultation',label: 'Consultation',count: props.requests.filter(r => r.type === 'consultation').length },
])

const filtered = computed(() => {
  let list = props.requests
  if (activeFilter.value !== 'all') {
    if (['pending', 'accepted', 'declined'].includes(activeFilter.value)) {
      list = list.filter(r => r.status === activeFilter.value)
    } else {
      list = list.filter(r => r.type === activeFilter.value)
    }
  }
  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    list = list.filter(r =>
      r.practitioner?.display_name?.toLowerCase().includes(q) ||
      r.service?.toLowerCase().includes(q) ||
      r.engagement_type?.toLowerCase().includes(q) ||
      r.meeting_type?.toLowerCase().includes(q)
    )
  }
  return list
})

// ── Pagination ────────────────────────────────────────────────────────────
const PAGE_SIZE  = 10
const page       = ref(1)
const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / PAGE_SIZE)))
const pagedRequests = computed(() => filtered.value.slice((page.value - 1) * PAGE_SIZE, page.value * PAGE_SIZE))

// ── Modals ────────────────────────────────────────────────────────────────
const modals = reactive({ detail: false, accept: false, decline: false, reply: false })
const activeRequest = ref(null)

function openDetail(r)  { activeRequest.value = r; modals.detail  = true }
function openAccept(r)  { activeRequest.value = r; acceptForm.reset(); modals.accept  = true }
function openDecline(r) { activeRequest.value = r; declineForm.reset(); modals.decline = true }
function openReply(r)   { activeRequest.value = r; replyForm.reset(); modals.reply   = true }

// ── Forms ─────────────────────────────────────────────────────────────────
const acceptForm  = useForm({ response_note: '' })
const declineForm = useForm({ response_note: '' })
const replyForm   = useForm({ response_note: '' })

function submitAccept() {
  if (!activeRequest.value) return
  acceptForm.post(route('bp.engagement-requests.accept', activeRequest.value.id), {
    preserveScroll: true,
    onSuccess: () => { modals.accept = false; toast.success('Request accepted. Practitioner notified.') },
    onError: () => toast.error('Could not accept. Please try again.'),
  })
}

function submitDecline() {
  if (!activeRequest.value) return
  declineForm.post(route('bp.engagement-requests.decline', activeRequest.value.id), {
    preserveScroll: true,
    onSuccess: () => { modals.decline = false; toast.info('Request declined. Practitioner notified.') },
    onError: () => toast.error('Could not decline. Please try again.'),
  })
}

function submitReply() {
  if (!activeRequest.value) return
  replyForm.post(route('bp.engagement-requests.reply', activeRequest.value.id), {
    preserveScroll: true,
    onSuccess: () => { modals.reply = false; toast.success('Reply sent.') },
    onError: () => toast.error('Message is required.'),
  })
}

// ── Helpers ───────────────────────────────────────────────────────────────
function typeLabel(t) {
  return { hire: 'Engagement', quote: 'Quote', consultation: 'Consultation' }[t] ?? t
}
function typeIcon(t) {
  return { hire: 'briefcase', quote: 'clipboard', consultation: 'calendar' }[t] ?? 'briefcase'
}
function typeBadge(t) {
  return { hire: 'badge-gold', quote: 'badge-blue', consultation: 'badge-green' }[t] ?? 'badge-gold'
}
function statusBadge(s) {
  return { pending: 'badge-gold', accepted: 'badge-green', declined: 'badge-red', expired: 'badge-grey' }[s] ?? 'badge-gold'
}
function primaryDetail(r) {
  if (r.type === 'hire')         return r.engagement_type || '—'
  if (r.type === 'quote')        return r.service || '—'
  if (r.type === 'consultation') return r.meeting_type || '—'
  return '—'
}
</script>

<style scoped>
.page-toolbar        { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.toolbar-right       { display: flex; align-items: center; gap: 8px; }
.search-wrap         { position: relative; }
.search-icon         { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-4); pointer-events: none; }
.search-input        { padding-left: 32px; min-width: 220px; }
.modal-intro-text    { font-size: 13px; color: var(--text-2); line-height: 1.6; margin: 0 0 16px; }
.form-label-opt      { color: var(--text-4); font-weight: 500; }

/* Table cells */
.ber-prac-cell   { display: flex; align-items: center; gap: 10px; }
.ber-prac-link   { font-size: 13px; font-weight: 600; color: var(--gold-dark); text-decoration: none; }
.ber-prac-link:hover { text-decoration: underline; }
.ber-prac-name   { font-size: 13px; font-weight: 600; color: var(--text); }
.ber-prac-sub    { font-size: 11px; color: var(--text-4); margin-top: 2px; }
.ber-details-cell { max-width: 220px; }
.ber-details-primary { font-size: 12px; font-weight: 600; color: var(--text); }
.ber-details-sub { display: inline-flex; align-items: center; gap: 3px; font-size: 11px; color: var(--text-4); margin-top: 2px; }
.ber-flags       { display: flex; gap: 4px; margin-top: 3px; flex-wrap: wrap; }
.ber-note        { font-size: 11px; color: var(--text-3); margin-top: 4px; font-style: italic; }
.ber-date-cell   { font-size: 11px; color: var(--text-4); white-space: nowrap; }
.ber-actions-cell { display: flex; gap: 6px; justify-content: flex-end; }

/* Pagination */
.ber-pagination  { display: flex; align-items: center; gap: 10px; justify-content: center; margin-top: 20px; }
.ber-page-info   { font-size: 12px; color: var(--text-3); font-weight: 600; min-width: 60px; text-align: center; }

/* Detail modal */
.ber-strip       { display: flex; align-items: center; gap: 14px; padding: 14px; background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); margin-bottom: 20px; }
.ber-strip-info  { flex: 1; min-width: 0; }
.ber-strip-name  { font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 5px; }
.ber-strip-meta  { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.ber-detail-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text-4); margin-bottom: 10px; }
.ber-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 20px; padding: 14px 16px; background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); margin-bottom: 14px; }
.ber-detail-row  { display: flex; flex-direction: column; gap: 2px; }
.ber-detail-row--full { grid-column: 1 / -1; }
.ber-detail-key  { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text-4); }
.ber-detail-val  { font-size: 13px; font-weight: 500; color: var(--text); line-height: 1.5; }
.ber-response-note { padding: 12px 14px; background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); margin-bottom: 14px; font-size: 13px; color: var(--text-2); line-height: 1.6; }
.ber-meta-row    { display: flex; gap: 16px; padding-top: 12px; border-top: 1px solid var(--border); }
.ber-meta-item   { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; color: var(--text-4); font-weight: 500; }
</style>
