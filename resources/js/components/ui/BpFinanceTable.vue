<!--
  BpFinanceTable.vue — Business Partner finances section for provider/Finances.vue.

  Owns:
    - Two sub-tabs: Invoices | Contracts & Milestones
    - Search + filter chips per sub-tab
    - Client-side pagination (8 per page, mirroring SessionTable)
    - All modals for invoices (approve, reject, view, dispute)
    - All modals for contracts (sign, fund-contract, fund-milestone, refund-milestone,
      review-milestone, cancel, autopay, view, dispute-milestone)

  Props:
    invoices          — bpInvoices from FinancesController (all statuses)
    contracts         — activeContracts from FinancesController
    escrowSummary     — { total_held_cents, total_unfunded_cents, funded_count, contracts_needing_funding }
    hasPaymentMethod  — bool: provider has saved payment method
    has_valid_default_pm — alias for hasPaymentMethod (both accepted)

  No emits — self-contained. All Inertia calls fired internally.
-->
<template>
  <div class="bpft-root">

    <!-- ── Escrow summary banner (only when funds are held or unfunded) ── -->
    <div
      v-if="escrowSummary.total_held_cents > 0 || escrowSummary.total_unfunded_cents > 0"
      class="bpft-escrow-banner"
    >
      <div class="bpft-escrow-banner-inner">
        <div class="bpft-escrow-stat" v-if="escrowSummary.total_held_cents > 0">
          <AegisIcon name="shield-check" :size="15" />
          <div>
            <div class="bpft-escrow-stat-val">{{ formatCents(escrowSummary.total_held_cents) }}</div>
            <div class="bpft-escrow-stat-label">Held in escrow</div>
          </div>
        </div>
        <div class="bpft-escrow-stat bpft-escrow-stat--warn" v-if="escrowSummary.total_unfunded_cents > 0">
          <AegisIcon name="alert-circle" :size="15" />
          <div>
            <div class="bpft-escrow-stat-val">{{ formatCents(escrowSummary.total_unfunded_cents) }}</div>
            <div class="bpft-escrow-stat-label">Unfunded milestones</div>
          </div>
        </div>
        <div v-if="escrowSummary.funded_count > 0" class="bpft-escrow-stat">
          <AegisIcon name="check-circle" :size="15" />
          <div>
            <div class="bpft-escrow-stat-val">{{ escrowSummary.funded_count }}</div>
            <div class="bpft-escrow-stat-label">Milestone{{ escrowSummary.funded_count !== 1 ? 's' : '' }} funded</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Primary sub-tabs: Invoices | Contracts & Milestones ── -->
    <div class="tabs-segmented bpft-subtabs" style="margin-bottom:16px; width:fit-content;">
      <button
        type="button"
        class="tab-pill"
        :class="{ active: subTab === 'invoices' }"
        @click="subTab = 'invoices'; invPage = 1"
      >
        Invoices
        <span v-if="pendingInvoiceCount > 0" class="badge-pill badge-pill--warn">{{ pendingInvoiceCount }}</span>
        <span v-else-if="invoices.length > 0" class="badge-pill">{{ invoices.length }}</span>
      </button>
      <button
        type="button"
        class="tab-pill"
        :class="{ active: subTab === 'contracts' }"
        @click="subTab = 'contracts'; conPage = 1"
      >
        Contracts &amp; Milestones
        <span v-if="submittedMilestoneCount > 0" class="badge-pill badge-pill--warn">{{ submittedMilestoneCount }}</span>
        <span v-else-if="contracts.length > 0" class="badge-pill">{{ contracts.length }}</span>
      </button>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         INVOICES SUB-TAB
    ══════════════════════════════════════════════════════════════ -->
    <div v-show="subTab === 'invoices'">

      <!-- Toolbar -->
      <div class="bpft-toolbar">
        <div class="bpft-search-wrap">
          <AegisIcon name="search" :size="14" />
          <input
            v-model="invSearch"
            type="text"
            class="form-control bpft-search"
            placeholder="Search by partner or invoice…"
            @input="invPage = 1"
          />
          <button v-if="invSearch" type="button" class="bpft-search-clear" @click="invSearch = ''; invPage = 1">
            <AegisIcon name="x" :size="12" />
          </button>
        </div>

        <nav class="tabs-segmented" role="tablist" style="width:fit-content">
          <button
            v-for="chip in invChips"
            :key="chip.value"
            type="button"
            role="tab"
            class="tab-pill"
            :class="{ active: invFilter === chip.value }"
            @click="invFilter = chip.value; invPage = 1"
          >
            {{ chip.label }}
            <span v-if="chip.count > 0" class="badge-pill" :class="chip.warn ? 'badge-pill--warn' : ''">
              {{ chip.count }}
            </span>
          </button>
        </nav>
      </div>

      <!-- Empty: no invoices at all -->
      <AegisEmptyState
        v-if="!invoices.length"
        icon="file-text"
        title="No BP invoices yet"
        subtitle="Business Partner invoices will appear here once they are submitted for approval."
      />

      <!-- Empty: filter has no results -->
      <AegisEmptyState
        v-else-if="!filteredInvoices.length"
        icon="filter"
        title="No invoices match this filter"
        subtitle="Try selecting a different filter or clearing the search."
      />

      <!-- Table -->
      <template v-else>
        <div class="bpft-table-wrap">
          <table class="bpft-table">
            <thead>
              <tr>
                <th class="bpft-th">Business Partner</th>
                <th class="bpft-th">Contract / Invoice</th>
                <th class="bpft-th">Amount</th>
                <th class="bpft-th">Status</th>
                <th class="bpft-th">Due</th>
                <th class="bpft-th"></th>
              </tr>
            </thead>
            <tbody>
              <BpInvoiceRow
                v-for="inv in pagedInvoices"
                :key="inv.id"
                :invoice="inv"
                @approve="openApproveInvoice"
                @reject="openRejectInvoice"
                @view="openViewInvoice"
                @dispute="openBpInvoiceDispute"
              />
            </tbody>
          </table>
        </div>

        <AegisPagination
          v-if="filteredInvoices.length > PAGE_SIZE"
          :current-page="invPage"
          :total-pages="invTotalPages"
          :total="filteredInvoices.length"
          :from="(invPage - 1) * PAGE_SIZE + 1"
          :to="Math.min(invPage * PAGE_SIZE, filteredInvoices.length)"
          :show-meta="true"
          style="margin-top:12px"
          @change="invPage = $event"
        />
      </template>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         CONTRACTS & MILESTONES SUB-TAB
    ══════════════════════════════════════════════════════════════ -->
    <div v-show="subTab === 'contracts'">

      <!-- Toolbar -->
      <div class="bpft-toolbar">
        <div class="bpft-search-wrap">
          <AegisIcon name="search" :size="14" />
          <input
            v-model="conSearch"
            type="text"
            class="form-control bpft-search"
            placeholder="Search by partner or contract…"
            @input="conPage = 1"
          />
          <button v-if="conSearch" type="button" class="bpft-search-clear" @click="conSearch = ''; conPage = 1">
            <AegisIcon name="x" :size="12" />
          </button>
        </div>

        <nav class="tabs-segmented" role="tablist" style="width:fit-content">
          <button
            v-for="chip in conChips"
            :key="chip.value"
            type="button"
            role="tab"
            class="tab-pill"
            :class="{ active: conFilter === chip.value }"
            @click="conFilter = chip.value; conPage = 1"
          >
            {{ chip.label }}
            <span v-if="chip.count > 0" class="badge-pill" :class="chip.warn ? 'badge-pill--warn' : ''">
              {{ chip.count }}
            </span>
          </button>
        </nav>
      </div>

      <!-- Empty -->
      <AegisEmptyState
        v-if="!contracts.length"
        icon="briefcase"
        title="No active contracts"
        subtitle="Contracts with Business Partners appear here. Hire via Support Services to get started."
      >
        <template #action>
          <a :href="route('provider.jobs.index')" class="btn btn-primary">
            <AegisIcon name="plus" :size="13" />
            Go to Support Services
          </a>
        </template>
      </AegisEmptyState>

      <AegisEmptyState
        v-else-if="!filteredContracts.length"
        icon="filter"
        title="No contracts match this filter"
        subtitle="Try selecting a different filter or clearing the search."
      />

      <!-- Contract rows -->
      <template v-else>
        <div class="bpft-contract-list">
          <BpContractRow
            v-for="con in pagedContracts"
            :key="con.id"
            :contract="con"
            :has-payment-method="effectiveHasPm"
            @sign="openSignContract"
            @fund-contract="openFundContract"
            @cancel="openCancelContract"
            @autopay="openAutoPay"
            @view="openViewContract"
            @fund-milestone="openFundMilestone"
            @refund-milestone="openRefundMilestone"
            @review-milestone="openReviewMilestone"
            @dispute-milestone="openMilestoneDispute"
          />
        </div>

        <AegisPagination
          v-if="filteredContracts.length > PAGE_SIZE"
          :current-page="conPage"
          :total-pages="conTotalPages"
          :total="filteredContracts.length"
          :from="(conPage - 1) * PAGE_SIZE + 1"
          :to="Math.min(conPage * PAGE_SIZE, filteredContracts.length)"
          :show-meta="true"
          style="margin-top:12px"
          @change="conPage = $event"
        />
      </template>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         MODALS
    ══════════════════════════════════════════════════════════════ -->

    <!-- Approve & Pay Invoice -->
    <AegisModal v-model="modals.approveInvoice" title="Approve &amp; Pay Invoice" size="lg">
      <div v-if="activeInvoice">
        <div class="alert alert-success" style="margin-bottom:14px">
          <div class="alert-icon"><AegisIcon name="check" :size="18" /></div>
          <div class="alert-content">
            <strong>Invoice #{{ activeInvoice.invoice_number }} · {{ activeInvoice.bp_name }}</strong>
            — {{ formatCents(activeInvoice.total_cents) }} will be charged to your default card and routed directly to
            {{ activeInvoice.bp_name }} via Stripe Connect. Aegis holds no funds.
          </div>
        </div>
        <div class="receipt">
          <div class="receipt-row">
            <span>{{ activeInvoice.contract_title || 'Services' }}</span>
            <span>{{ formatCents(activeInvoice.total_cents) }}</span>
          </div>
          <div class="receipt-row total">
            <span>Total</span>
            <span>{{ formatCents(activeInvoice.total_cents) }}</span>
          </div>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.approveInvoice = false">Cancel</button>
        <button
          type="button"
          class="btn btn-success"
          :disabled="paying === activeInvoice?.id"
          @click="doApproveInvoice"
        >
          <AegisIcon name="check" :size="13" />
          {{ paying === activeInvoice?.id ? 'Processing…' : 'Approve & Pay ' + (activeInvoice ? formatCents(activeInvoice.total_cents) : '') }}
        </button>
      </template>
    </AegisModal>

    <!-- Reject Invoice -->
    <AegisModal v-model="modals.rejectInvoice" title="Reject Invoice" size="lg">
      <div v-if="activeInvoice">
        <div class="alert alert-danger" style="margin-bottom:14px">
          <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
          <div class="alert-content">
            <strong>Rejecting will notify {{ activeInvoice.bp_name }}.</strong>
            They can revise and resubmit.
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Reason for Rejection <span class="required">*</span></label>
          <select class="form-select" v-model="rejectForm.reason" :class="{ 'is-error': rejectForm.errors.reason }">
            <option>Incorrect amount</option>
            <option>Services not delivered</option>
            <option>Duplicate invoice</option>
            <option>Unauthorized charges</option>
            <option>Missing documentation</option>
            <option>Other</option>
          </select>
          <div v-if="rejectForm.errors.reason" class="form-error">{{ rejectForm.errors.reason }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Message to Business Partner</label>
          <textarea
            class="form-textarea"
            rows="3"
            v-model="rejectForm.message"
            :class="{ 'is-error': rejectForm.errors.message }"
            placeholder="Explain the rejection so they can resubmit correctly…"
          ></textarea>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.rejectInvoice = false">Cancel</button>
        <button
          type="button"
          class="btn btn-danger"
          :disabled="rejectForm.processing"
          @click="doRejectInvoice"
        >
          <AegisIcon name="x" :size="13" />
          {{ rejectForm.processing ? 'Rejecting…' : 'Reject Invoice' }}
        </button>
      </template>
    </AegisModal>

    <!-- View Invoice Receipt -->
    <ViewInvoiceModal
      v-model="modals.viewInvoice"
      :invoice="activeInvoice"
      :can-approve="activeInvoice?.payable && !activeInvoice?.active_dispute_id"
      @approve="handleReceiptApprove"
    />

    <!-- Cancel Contract -->
    <AegisModal v-model="modals.cancelContract" title="Cancel Contract" size="lg">
      <div class="alert alert-danger" style="margin-bottom:14px">
        <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
        <div class="alert-content">
          <strong>Cancelling {{ activeContract ? 'your contract with ' + activeContract.bp_name : 'this contract' }} will stop scheduled payments.</strong>
          Per contract terms, 30-day notice is required.
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Reason <span class="required">*</span></label>
        <select class="form-select" v-model="cancelForm.reason" :class="{ 'is-error': cancelForm.errors.reason }">
          <option>Switching to different provider</option>
          <option>No longer needed</option>
          <option>Cost reduction</option>
          <option>Service quality issues</option>
          <option>Other</option>
        </select>
        <div v-if="cancelForm.errors.reason" class="form-error">{{ cancelForm.errors.reason }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">Feedback (optional)</label>
        <textarea
          class="form-textarea"
          rows="2"
          v-model="cancelForm.feedback"
          placeholder="Let the Business Partner know why you're cancelling…"
        ></textarea>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.cancelContract = false">Keep Contract</button>
        <button
          type="button"
          class="btn btn-danger"
          :disabled="cancelForm.processing"
          @click="doCancelContract"
        >
          <AegisIcon name="x" :size="13" />
          Send Cancellation Notice
        </button>
      </template>
    </AegisModal>

    <!-- Auto-Pay Settings -->
    <AegisModal
      v-model="modals.autoPay"
      :title="'Auto-Pay Settings' + (activeContract ? ' — ' + activeContract.bp_name : '')"
      size="lg"
    >
      <div class="setting-row" style="margin-bottom:14px">
        <div class="setting-info">
          <div class="setting-label">Enable Auto-Pay</div>
          <div class="setting-desc">Automatically charge your default method when an invoice is due. Payment routes directly to the Business Partner.</div>
        </div>
        <button
          type="button"
          class="toggle"
          :class="{ on: autoPayForm.enabled }"
          :aria-pressed="autoPayForm.enabled"
          @click="autoPayForm.enabled = !autoPayForm.enabled"
        ></button>
      </div>
      <div class="form-group">
        <label class="form-label">Payment Day</label>
        <select class="form-select" v-model="autoPayForm.day">
          <option value="1st">1st of month</option>
          <option value="15th">15th of month</option>
          <option value="last">Last day of month</option>
          <option value="due">Invoice due date</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Notify me before charge</label>
        <select class="form-select" v-model="autoPayForm.notify">
          <option value="3_days">3 days before</option>
          <option value="1_day">1 day before</option>
          <option value="same_day">Same day only</option>
          <option value="none">Don't notify</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Max auto-pay limit (leave blank for no limit)</label>
        <input class="form-input" type="number" min="0" v-model="autoPayForm.limit" placeholder="e.g. 2500">
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.autoPay = false">Cancel</button>
        <button
          type="button"
          class="btn btn-primary"
          :disabled="autoPayForm.processing"
          @click="doSaveAutoPay"
        >
          <AegisIcon name="check" :size="13" /> Save Settings
        </button>
      </template>
    </AegisModal>

    <!-- View Contract (summary) -->
    <AegisModal
      v-model="modals.viewContract"
      :title="activeContract ? 'Contract — ' + activeContract.bp_name : 'Contract'"
      size="lg"
    >
      <div v-if="activeContract" class="contract-preview">
        <div class="contract-preview-title">Aegis Service Agreement</div>
        <div class="contract-preview-row"><strong>Business Partner:</strong> {{ activeContract.bp_name }}</div>
        <div class="contract-preview-row"><strong>Services:</strong> {{ activeContract.title }}</div>
        <div class="contract-preview-row"><strong>Payment Type:</strong> {{ activeContract.billing_type_label }}</div>
        <div class="contract-preview-row"><strong>Total Value:</strong> {{ formatCents(activeContract.total_cents) }}</div>
        <div class="contract-preview-row"><strong>Term:</strong> {{ activeContract.term }}</div>
        <div class="contract-preview-row"><strong>Termination:</strong> 30-day written notice</div>
      </div>
      <template #footer>
        <a
          v-if="activeContract"
          :href="route('provider.jobs.contract.pdf', activeContract.id)"
          target="_blank"
          rel="noopener"
          class="btn btn-ghost"
        >
          <AegisIcon name="download" :size="12" /> Download PDF
        </a>
        <a :href="route('provider.jobs.index')" class="btn btn-ghost">
          <AegisIcon name="external-link" :size="12" /> Open in Support Services
        </a>
        <button type="button" class="btn btn-outline" @click="modals.viewContract = false">Close</button>
      </template>
    </AegisModal>

    <!-- Sign Contract — reuse existing SignContractModal -->
    <SignContractModal
      v-model="modals.signContract"
      :contract="activeContract"
      portal="provider"
    />

    <!-- Fund Contract (full upfront) — reuse FundContractModal -->
    <FundContractModal
      v-model="modals.fundContract"
      :contract="activeContract"
      :has-payment-method="effectiveHasPm"
    />

    <!-- Fund Milestone (per-milestone escrow) — reuse FundMilestoneModal -->
    <FundMilestoneModal
      v-model="modals.fundMilestone"
      :contract="activeContract"
      :milestone="activeMilestone"
      :has-payment-method="effectiveHasPm"
    />

    <!-- Refund Milestone escrow — reuse MilestoneRefundModal -->
    <MilestoneRefundModal
      v-model="modals.refundMilestone"
      :contract="activeContract"
      :milestone="activeMilestone"
    />

    <!-- Review Milestone (approve / revise / dispute path) — reuse MilestoneReviewModal -->
    <MilestoneReviewModal
      v-model="modals.reviewMilestone"
      :contract="activeContract"
      :milestone="activeMilestone"
      :submission="activeSubmission"
    />

    <!-- Open Dispute (invoice or milestone) -->
    <OpenDisputeModal
      v-model="modals.openDispute"
      :subject="disputeTarget"
      post-route="provider.disputes.store"
      @opened="router.reload({ only: ['bpInvoices', 'activeContracts', 'disputes'] })"
    />

  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router, useForm }       from '@inertiajs/vue3'
import { useToast }              from '@/composables/useToast'
import AegisPagination           from '@/components/ui/AegisPagination.vue'
import BpInvoiceRow              from '@/components/ui/BpInvoiceRow.vue'
import BpContractRow             from '@/components/ui/BpContractRow.vue'
import ViewInvoiceModal          from '@/components/modals/ViewInvoiceModal.vue'
import OpenDisputeModal          from '@/components/modals/OpenDisputeModal.vue'
import SignContractModal         from '@/components/modals/SignContractModal.vue'
import FundContractModal         from '@/components/modals/FundContractModal.vue'
import FundMilestoneModal        from '@/components/modals/FundMilestoneModal.vue'
import MilestoneRefundModal      from '@/components/modals/MilestoneRefundModal.vue'
import MilestoneReviewModal      from '@/components/modals/MilestoneReviewModal.vue'

const props = defineProps({
  invoices:           { type: Array,   default: () => [] },
  contracts:          { type: Array,   default: () => [] },
  escrowSummary:      { type: Object,  default: () => ({ total_held_cents: 0, total_unfunded_cents: 0, funded_count: 0, contracts_needing_funding: 0 }) },
  hasPaymentMethod:   { type: Boolean, default: false },
  has_valid_default_pm: { type: Boolean, default: false },
})

const toast = useToast()

// ── Constants ────────────────────────────────────────────────────────────────
const PAGE_SIZE = 8

// ── Sub-tab state ─────────────────────────────────────────────────────────────
const subTab = ref('invoices')

// ── Invoice state ─────────────────────────────────────────────────────────────
const invSearch = ref('')
const invFilter = ref('all')
const invPage   = ref(1)

// ── Contract state ────────────────────────────────────────────────────────────
const conSearch = ref('')
const conFilter = ref('all')
const conPage   = ref(1)

// ── Active record refs ─────────────────────────────────────────────────────────
const activeInvoice    = ref(null)
const activeContract   = ref(null)
const activeMilestone  = ref(null)
const activeSubmission = ref(null)
const disputeTarget    = ref(null)
const paying           = ref(null)

// ── Modal flags ────────────────────────────────────────────────────────────────
const modals = ref({
  approveInvoice: false,
  rejectInvoice:  false,
  viewInvoice:    false,
  cancelContract: false,
  autoPay:        false,
  viewContract:   false,
  signContract:   false,
  fundContract:   false,
  fundMilestone:  false,
  refundMilestone:false,
  reviewMilestone:false,
  openDispute:    false,
})

// ── Forms ─────────────────────────────────────────────────────────────────────
const rejectForm  = useForm({ reason: 'Incorrect amount', message: '' })
const cancelForm  = useForm({ reason: 'No longer needed', feedback: '' })
const autoPayForm = useForm({ enabled: false, day: '1st', notify: '3_days', limit: '' })

// ── Helpers ───────────────────────────────────────────────────────────────────
const sv = (v) => (v && typeof v === 'object' && 'value' in v) ? v.value : (v ?? '')

const effectiveHasPm = computed(() => props.hasPaymentMethod || props.has_valid_default_pm)

function formatCents(c) {
  return '$' + ((c ?? 0) / 100).toLocaleString('en-US', { minimumFractionDigits: 2 })
}

// ── Invoice computed ──────────────────────────────────────────────────────────
const pendingInvoiceCount = computed(() =>
  props.invoices.filter(i => ['sent','overdue'].includes(sv(i.status))).length
)

function applyInvFilter(list, f) {
  if (f === 'all')     return list
  if (f === 'pending') return list.filter(i => ['sent','overdue'].includes(sv(i.status)) && !i.active_dispute_id)
  if (f === 'overdue') return list.filter(i => sv(i.status) === 'overdue')
  if (f === 'disputed')return list.filter(i => !!i.active_dispute_id)
  if (f === 'paid')    return list.filter(i => sv(i.status) === 'paid')
  return list
}
function applyInvSearch(list, q) {
  if (!q.trim()) return list
  const lq = q.toLowerCase()
  return list.filter(i =>
    (i.bp_name ?? '').toLowerCase().includes(lq) ||
    (i.invoice_number ?? '').toLowerCase().includes(lq) ||
    (i.contract_title ?? '').toLowerCase().includes(lq)
  )
}

const filteredInvoices = computed(() =>
  applyInvSearch(applyInvFilter(props.invoices, invFilter.value), invSearch.value)
)
const pagedInvoices = computed(() => {
  const s = (invPage.value - 1) * PAGE_SIZE
  return filteredInvoices.value.slice(s, s + PAGE_SIZE)
})
const invTotalPages = computed(() => Math.max(1, Math.ceil(filteredInvoices.value.length / PAGE_SIZE)))

const invChips = computed(() => {
  const inv = props.invoices
  return [
    { value: 'all',      label: 'All',      count: 0 },
    { value: 'pending',  label: 'Awaiting Approval', count: inv.filter(i => ['sent','overdue'].includes(sv(i.status)) && !i.active_dispute_id).length, warn: true },
    { value: 'overdue',  label: 'Overdue',  count: inv.filter(i => sv(i.status) === 'overdue').length, warn: true },
    { value: 'disputed', label: 'Disputed', count: inv.filter(i => !!i.active_dispute_id).length, warn: true },
    { value: 'paid',     label: 'Paid',     count: inv.filter(i => sv(i.status) === 'paid').length },
  ]
})

// ── Contract computed ─────────────────────────────────────────────────────────
const submittedMilestoneCount = computed(() =>
  props.contracts.reduce((sum, c) =>
    sum + (c.milestones ?? []).filter(m => sv(m.status) === 'submitted').length, 0
  )
)

function applyConFilter(list, f) {
  if (f === 'all')        return list
  if (f === 'action')     return list.filter(c => {
    const needsSign = sv(c.status) === 'pending_signature' && !c.provider_has_signed
    const needsFund = sv(c.status) === 'pending_funding'
    const unfunded  = sv(c.status) === 'active' && (c.unfunded_cents ?? 0) > 0
    const hasReview = (c.milestones ?? []).some(m => sv(m.status) === 'submitted')
    return needsSign || needsFund || unfunded || hasReview
  })
  if (f === 'active')     return list.filter(c => sv(c.status) === 'active')
  if (f === 'milestone')  return list.filter(c => c.billing_type === 'milestone' || (c.milestones?.length ?? 0) > 0)
  if (f === 'pending')    return list.filter(c => ['pending_signature','pending_funding'].includes(sv(c.status)))
  return list
}
function applyConSearch(list, q) {
  if (!q.trim()) return list
  const lq = q.toLowerCase()
  return list.filter(c =>
    (c.bp_name ?? '').toLowerCase().includes(lq) ||
    (c.title ?? '').toLowerCase().includes(lq)
  )
}

const actionRequiredCount = computed(() =>
  applyConFilter(props.contracts, 'action').length
)

const filteredContracts = computed(() =>
  applyConSearch(applyConFilter(props.contracts, conFilter.value), conSearch.value)
)
const pagedContracts = computed(() => {
  const s = (conPage.value - 1) * PAGE_SIZE
  return filteredContracts.value.slice(s, s + PAGE_SIZE)
})
const conTotalPages = computed(() => Math.max(1, Math.ceil(filteredContracts.value.length / PAGE_SIZE)))

const conChips = computed(() => [
  { value: 'all',       label: 'All',              count: 0 },
  { value: 'action',    label: 'Action Required',  count: actionRequiredCount.value, warn: true },
  { value: 'active',    label: 'Active',           count: props.contracts.filter(c => sv(c.status) === 'active').length },
  { value: 'milestone', label: 'Milestones',       count: props.contracts.filter(c => c.billing_type === 'milestone' || (c.milestones?.length ?? 0) > 0).length },
  { value: 'pending',   label: 'Pending',          count: props.contracts.filter(c => ['pending_signature','pending_funding'].includes(sv(c.status))).length, warn: true },
])

// Reset pages on filter/search change
watch([invSearch, invFilter], () => { invPage.value = 1 })
watch([conSearch, conFilter], () => { conPage.value = 1 })

// ── Invoice actions ───────────────────────────────────────────────────────────
function openApproveInvoice(inv) {
  activeInvoice.value = inv
  modals.value.approveInvoice = true
}
function openRejectInvoice(inv) {
  activeInvoice.value = inv
  rejectForm.reason = 'Incorrect amount'
  rejectForm.message = ''
  modals.value.rejectInvoice = true
}
function openViewInvoice(inv) {
  activeInvoice.value = inv
  modals.value.viewInvoice = true
}
function openBpInvoiceDispute(inv) {
  disputeTarget.value = {
    type:         'bp_invoice',
    id:           inv.id,
    amount_cents: inv.total_cents,
    label:        `BP Invoice ${inv.invoice_number} · ${inv.bp_name}`,
  }
  modals.value.openDispute = true
}
function handleReceiptApprove(inv) {
  modals.value.viewInvoice = false
  activeInvoice.value = inv
  modals.value.approveInvoice = true
}

function doApproveInvoice() {
  if (!activeInvoice.value) return
  paying.value = activeInvoice.value.id
  router.post(
    route('provider.jobs.bp-invoice.pay', { invoice: activeInvoice.value.id }),
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        modals.value.approveInvoice = false
        toast.success('Payment sent — routed directly to the recipient via Stripe Connect.')
      },
      onError: () => toast.error('Payment failed. Please check your default payment method.'),
      onFinish: () => { paying.value = null },
    }
  )
}

function doRejectInvoice() {
  if (!activeInvoice.value) return
  rejectForm.post(
    route('provider.finances.bp-invoice.reject', { invoice: activeInvoice.value.id }),
    {
      preserveScroll: true,
      onSuccess: () => {
        modals.value.rejectInvoice = false
        rejectForm.reset()
        toast.success('Invoice rejected — Business Partner notified.')
      },
      onError: () => toast.error('Please check the form.'),
    }
  )
}

// ── Contract actions ──────────────────────────────────────────────────────────
function openSignContract(con) {
  activeContract.value = con
  modals.value.signContract = true
}
function openFundContract(con) {
  activeContract.value = con
  modals.value.fundContract = true
}
function openCancelContract(con) {
  activeContract.value = con
  cancelForm.reset()
  modals.value.cancelContract = true
}
function openAutoPay(con) {
  activeContract.value = con
  autoPayForm.enabled = !!con.autopay_enabled
  autoPayForm.day     = '1st'
  autoPayForm.notify  = '3_days'
  autoPayForm.limit   = ''
  modals.value.autoPay = true
}
function openViewContract(con) {
  activeContract.value = con
  modals.value.viewContract = true
}

function doCancelContract() {
  if (!activeContract.value) return
  cancelForm.post(
    route('provider.finances.bp-contract.cancel', { contract: activeContract.value.id }),
    {
      preserveScroll: true,
      onSuccess: () => {
        modals.value.cancelContract = false
        toast.info('Cancellation notice sent — contract cancelled.')
      },
    }
  )
}
function doSaveAutoPay() {
  if (!activeContract.value) return
  autoPayForm.post(
    route('provider.finances.bp-contract.autopay', { contract: activeContract.value.id }),
    {
      preserveScroll: true,
      onSuccess: () => {
        modals.value.autoPay = false
        toast.success(autoPayForm.enabled ? 'Auto-pay enabled.' : 'Auto-pay turned off.')
      },
    }
  )
}

// ── Milestone actions ─────────────────────────────────────────────────────────
function openFundMilestone(con, ms) {
  activeContract.value   = con
  activeMilestone.value  = ms
  modals.value.fundMilestone = true
}
function openRefundMilestone(con, ms) {
  activeContract.value   = con
  activeMilestone.value  = ms
  modals.value.refundMilestone = true
}
function openReviewMilestone(con, ms) {
  activeContract.value   = con
  activeMilestone.value  = ms
  activeSubmission.value = ms.latest_submission ?? null
  modals.value.reviewMilestone = true
}
function openMilestoneDispute(con, ms) {
  disputeTarget.value = {
    type:         'bp_milestone',
    id:           ms.id,
    amount_cents: ms.amount_cents,
    label:        `Milestone — ${ms.title} (${con.bp_name})`,
  }
  modals.value.openDispute = true
}
</script>

<style scoped>
.bpft-root { display: flex; flex-direction: column; gap: 0; }

/* ── Escrow banner ── */
.bpft-escrow-banner {
  background: var(--primary);
  border-radius: var(--radius-lg);
  padding: 16px 20px;
  margin-bottom: 18px;
}
.bpft-escrow-banner-inner {
  display: flex;
  align-items: center;
  gap: 28px;
  flex-wrap: wrap;
}
.bpft-escrow-stat {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  color: rgba(255,255,255,0.7);
}
.bpft-escrow-stat--warn { color: var(--gold); }
.bpft-escrow-stat-val {
  font-family: var(--font-serif);
  font-size: 20px;
  font-weight: 700;
  color: var(--gold);
  line-height: 1;
}
.bpft-escrow-stat--warn .bpft-escrow-stat-val { color: #fff; }
.bpft-escrow-stat-label {
  font-family: var(--font-sans);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.8px;
  text-transform: uppercase;
  color: rgba(255,255,255,0.5);
  margin-top: 4px;
}

/* ── Sub-tab pills ── */
.bpft-subtabs { margin-bottom: 16px; }

/* ── Toolbar ── */
.bpft-toolbar {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 14px;
}
.bpft-search-wrap {
  position: relative;
  display: flex;
  align-items: center;
}
.bpft-search-wrap .aegis-icon:first-child {
  position: absolute;
  left: 11px;
  color: var(--text-4);
  pointer-events: none;
}
.bpft-search {
  padding-left: 34px !important;
  padding-right: 32px !important;
  width: 100%;
  font-size: 13px;
}
.bpft-search-clear {
  position: absolute;
  right: 9px;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--text-4);
  display: flex;
  align-items: center;
  padding: 2px;
}
.bpft-search-clear:hover { color: var(--text); }

/* ── Invoice table ── */
.bpft-table-wrap {
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
}
.bpft-table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
}
.bpft-th {
  padding: 9px 12px;
  text-align: left;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-4);
  background: var(--surface-2);
  border-bottom: 1px solid var(--border);
}

/* ── Contract list ── */
.bpft-contract-list {
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
}

/* ── Modal utilities (shared with Finances.vue design language) ── */
.receipt { border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
.receipt-row {
  display: flex;
  justify-content: space-between;
  padding: 10px 14px;
  font-family: var(--font-sans);
  font-size: 13px;
  border-bottom: 1px solid var(--border);
}
.receipt-row:last-child { border-bottom: none; }
.receipt-row.total { font-weight: 700; background: var(--surface-2); }

.contract-preview { display: flex; flex-direction: column; gap: 10px; }
.contract-preview-title { font-family: var(--font-serif); font-size: 16px; font-weight: 700; color: var(--text); }
.contract-preview-row { font-family: var(--font-sans); font-size: 13px; color: var(--text-2); }

/* badge-pill warning variant */
.badge-pill--warn {
  background: var(--gold-dark) !important;
  color: #fff !important;
}
</style>
