<!--
  ContractModal.vue — Provider contract viewer with milestone CRUD + escrow funding + review.
  Supports payment_type: 'one_time' | 'milestone'
  Wave 3: Added escrow progress bar, Fund buttons, Sign CTA, MilestoneReviewModal wiring.
  AEGIS_VUE_RULES.md governs every pattern.
-->
<template>
  <AegisModal v-model="isOpen" title="Contract Details" size="lg" @update:model-value="onUpdateOpen">
    <div v-if="contract">
      <div class="modal-sub" style="margin-bottom:14px">
        {{ contract.bp?.display_name ?? 'Business Partner' }} · {{ contract.title }}
        <template v-if="contract.started_at"> · Started {{ formatDate(contract.started_at) }}</template>
      </div>

      <!-- Summary card -->
      <div class="contract-summary-card">
        <div class="contract-summary-row">
          <div class="contract-summary-item">
            <div class="contract-summary-label">Total Value</div>
            <div class="contract-summary-val is-money">{{ formatMoney(contract.total_value_cents) }}</div>
          </div>
          <div class="contract-summary-item">
            <div class="contract-summary-label">Payment Type</div>
            <AegisBadge
              :label="paymentType === 'milestone' ? 'Milestone-based' : 'One-time'"
              :variant="paymentType === 'milestone' ? 'blue' : 'gold'"
            />
          </div>
          <div class="contract-summary-item">
            <div class="contract-summary-label">Status</div>
            <AegisBadge :label="statusLabel" :variant="statusVariant" />
          </div>
        </div>
        <div class="contract-summary-row">
          <div class="contract-summary-item">
            <div class="contract-summary-label">Started</div>
            <div class="contract-summary-val">{{ formatDate(contract.started_at) }}</div>
          </div>
          <div class="contract-summary-item">
            <div class="contract-summary-label">Signed</div>
            <div class="contract-summary-val">{{ formatDate(contract.signed_at) }}</div>
          </div>
          <div class="contract-summary-item">
            <div class="contract-summary-label">Contract ID</div>
            <div class="contract-summary-val contract-id-mono">{{ contract.id }}</div>
          </div>
        </div>
      </div>

      <!-- Signature status banner (pending_signature state) -->
      <div v-if="statusVal === 'pending_signature'" class="contract-sig-banner">
        <div class="contract-sig-banner-title">
          <AegisIcon name="file-pen" :size="14" />
          Awaiting signatures before work can begin
        </div>
        <div class="contract-sig-status-row">
          <div class="contract-sig-item" :class="{ signed: contract.practitioner_signed_at }">
            <AegisIcon :name="contract.practitioner_signed_at ? 'check-circle' : 'circle'" :size="13" />
            You: {{ contract.practitioner_signed_at ? 'Signed' : 'Not signed' }}
          </div>
          <div class="contract-sig-item" :class="{ signed: contract.bp_signed_at }">
            <AegisIcon :name="contract.bp_signed_at ? 'check-circle' : 'circle'" :size="13" />
            {{ contract.bp?.display_name ?? 'BP' }}: {{ contract.bp_signed_at ? 'Signed' : 'Not signed' }}
          </div>
        </div>
        <button
          v-if="!contract.practitioner_signed_at"
          class="btn btn-primary"
          @click="openSignModal"
        >
          <AegisIcon name="file-pen" :size="13" />
          Sign contract
        </button>
      </div>

      <!-- Pending funding banner -->
      <div v-if="statusVal === 'pending_funding'" class="contract-fund-banner">
        <AegisIcon name="shield-check" :size="14" />
        <div>
          <div class="contract-fund-banner-title">Contract signed — ready to fund</div>
          <div class="contract-fund-banner-desc">
            Fund escrow to activate the contract and unlock milestones for the Business Partner.
          </div>
        </div>
        <button class="btn btn-primary" @click="openFundContract">
          <AegisIcon name="dollar" :size="13" />
          Fund escrow
        </button>
      </div>

      <!-- Escrow balance bar (active milestone contracts) -->
      <div v-if="statusVal === 'active' && paymentType === 'milestone'" class="contract-escrow-bar-wrap">
        <div class="contract-escrow-bar-header">
          <div class="contract-escrow-bar-title">
            <AegisIcon name="shield-check" :size="13" />
            Escrow balance
          </div>
          <div class="contract-escrow-bar-stats">
            <span class="escrow-stat-held">
              {{ formatMoney(escrowHeld) }} held
            </span>
            <span>·</span>
            <span class="escrow-stat-released">{{ formatMoney(escrowReleased) }} released</span>
            <span>·</span>
            <span>{{ formatMoney(escrowUnfunded) }} unfunded</span>
          </div>
        </div>
        <div class="contract-escrow-progress">
          <div class="contract-escrow-progress-released" :style="{ width: escrowPct(escrowReleased) }" />
          <div class="contract-escrow-progress-held"     :style="{ width: escrowPct(escrowHeld) }" />
        </div>
      </div>

      <!-- Milestone section (shown for milestone-based OR when milestones exist) -->
      <div v-if="paymentType === 'milestone' || localMilestones.length" class="milestone-section">
        <div class="milestone-section-header">
          <span class="milestone-section-title">
            <AegisIcon name="tasks" :size="14" />
            Milestones
            <span v-if="localMilestones.length" class="badge-pill">{{ localMilestones.length }}</span>
          </span>
          <button
            v-if="isActive && !showAddMilestone"
            class="btn btn-outline"
            @click="showAddMilestone = true"
          >
            <AegisIcon name="plus" :size="12" /> Add Milestone
          </button>
        </div>

        <!-- Add milestone inline form -->
        <div v-if="showAddMilestone" class="milestone-add-form">
          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Title</label>
              <input v-model="addForm.title" class="form-input" placeholder="Milestone title" maxlength="191" />
            </div>
            <div class="form-field">
              <label class="form-label">Amount</label>
              <input v-model="addForm.amount_dollars" type="number" min="0" step="0.01" class="form-input" placeholder="0.00" />
            </div>
          </div>
          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Due Date <span class="form-optional">(optional)</span></label>
              <input v-model="addForm.due_at" type="date" class="form-input" />
            </div>
            <div class="form-field">
              <label class="form-label">Description <span class="form-optional">(optional)</span></label>
              <input v-model="addForm.description" class="form-input" placeholder="Brief description" />
            </div>
          </div>
          <div class="milestone-add-actions">
            <button class="btn btn-outline" @click="cancelAdd">Cancel</button>
            <button class="btn btn-primary" :disabled="addingMilestone" @click="submitAdd">
              {{ addingMilestone ? 'Adding…' : 'Add Milestone' }}
            </button>
          </div>
        </div>

        <!-- Milestone list -->
        <AegisEmptyState
          v-if="!localMilestones.length && !showAddMilestone"
          icon="tasks"
          title="No milestones yet"
          description="Add milestones to track deliverables and release incremental payments."
        />

        <div v-else-if="localMilestones.length" class="milestone-list">
          <div v-for="m in localMilestones" :key="m.id" class="milestone-row">
            <div class="milestone-row-left">
              <div class="milestone-title">{{ m.title }}</div>
              <div class="milestone-meta">
                {{ formatMoney(m.amount_cents) }}
                <template v-if="m.due_at"> · Due {{ formatDate(m.due_at) }}</template>
              </div>
            </div>
            <div class="milestone-row-right">
              <AegisBadge :label="milestoneBadgeLabel(m.status)" :variant="milestoneBadgeVariant(m.status)" />
              <!-- Fund (pending / pending_funding) — escrow charge -->
              <button
                v-if="['pending', 'pending_funding'].includes(milestoneStatusVal(m)) && isActive"
                class="btn btn-primary"
                :disabled="busyMilestone === m.id"
                @click="openFundMilestone(m)"
              >
                <AegisIcon name="dollar" :size="12" />
                Fund
              </button>
              <!-- Review (submitted) — approve / revise / reject -->
              <button
                v-if="milestoneStatusVal(m) === 'submitted'"
                class="btn btn-primary"
                :disabled="busyMilestone === m.id"
                @click="openReviewMilestone(m)"
              >
                <AegisIcon name="check" :size="12" />
                Review
              </button>
              <!-- Auto-release countdown chip -->
              <span
                v-if="milestoneStatusVal(m) === 'submitted' && m.auto_release_at"
                class="milestone-auto-release-chip"
                :data-tooltip="`Auto-releases if not reviewed by ${m.auto_release_at}`"
              >
                <AegisIcon name="hourglass" :size="11" />
                {{ formatAutoRelease(m.auto_release_at) }}
              </span>
              <!-- Funded indicator -->
              <span
                v-if="milestoneStatusVal(m) === 'funded' || milestoneStatusVal(m) === 'in_progress'"
                class="milestone-funded-chip"
              >
                <AegisIcon name="shield-check" :size="11" />
                Funded
              </span>
              <!-- Released / paid -->
              <span
                v-if="['released', 'paid'].includes(milestoneStatusVal(m))"
                class="milestone-paid-chip"
              >
                <AegisIcon name="check-circle" :size="11" />
                Paid
              </span>
              <!-- Revision requested -->
              <span
                v-if="milestoneStatusVal(m) === 'revision_requested'"
                class="milestone-revision-chip"
                :data-tooltip="m.revision_notes"
              >
                <AegisIcon name="refresh-cw" :size="11" />
                Revision {{ m.revision_count > 1 ? `#${m.revision_count}` : '' }}
              </span>
              <!-- Delete (unfunded pending only) -->
              <button
                v-if="milestoneStatusVal(m) === 'pending' && isActive"
                class="btn-icon btn-icon-danger"
                data-tooltip="Remove milestone"
                :disabled="busyMilestone === m.id"
                @click="doDeleteMilestone(m)"
              >
                <AegisIcon name="trash" :size="12" />
              </button>
            </div>
          </div>
        </div>

        <!-- Milestone total -->
        <div v-if="localMilestones.length" class="milestone-total-row">
          <span class="milestone-total-label">Total milestones:</span>
          <span class="milestone-total-val">{{ formatMoney(milestoneTotalCents) }}</span>
          <AegisBadge
            v-if="allMilestonesPaid"
            label="All Paid"
            variant="green"
          />
        </div>
      </div>

      <!-- Invoice reference note -->
      <div class="contract-invoice-note">
        <AegisIcon name="file-text" :size="13" />
        <span>Invoices and payment records for this contract are available in
          <a :href="route('provider.finances.index')" class="link-gold">Finances</a>.
        </span>
      </div>

      <!-- Completed notice -->
      <div v-if="isCompleted" class="alert alert-success" style="margin-top:14px;margin-bottom:0">
        <div class="alert-icon"><AegisIcon name="check" :size="16" /></div>
        <div class="alert-content">
          <strong>Contract completed.</strong>
          This engagement has ended. The partner remains in your Hired Business Partners history.
        </div>
      </div>
    </div>

    <template #footer>
      <button type="button" class="btn btn-outline" :disabled="!contract" @click="downloadPdf">
        <AegisIcon name="download" :size="13" /> Download PDF
      </button>

      <!-- Milestone-driven: End Contract (all milestones must be paid first) -->
      <button
        v-if="isActive && isMilestoneDriven"
        class="btn btn-danger"
        :disabled="!allMilestonesPaid || busy"
        :data-tooltip="!allMilestonesPaid ? 'Pay all milestones before ending contract' : null"
        @click="endContract"
      >
        {{ busy ? 'Ending…' : 'End Contract' }}
      </button>

      <!-- One-time only (no milestones): End Contract & Release Payment -->
      <button
        v-else-if="isActive && !isMilestoneDriven"
        class="btn btn-primary"
        :disabled="busy"
        @click="endAndRelease"
      >
        <AegisIcon name="dollar" :size="13" />
        {{ busy ? 'Processing…' : 'End Contract & Release Payment' }}
      </button>
    </template>
  </AegisModal>

  <!-- Wave 3 sub-modals (rendered outside AegisModal to avoid z-index stacking) -->
  <SignContractModal
    v-model="showSign"
    :contract="contract"
    portal="provider"
  />
  <FundContractModal
    :contract="showFundContract ? contract : null"
    :has-payment-method="hasPaymentMethod"
    @update:model-value="showFundContract = false"
  />
  <FundMilestoneModal
    :contract="showFundMilestone ? contract : null"
    :milestone="activeMilestone"
    :has-payment-method="hasPaymentMethod"
    @update:model-value="showFundMilestone = false"
  />
  <MilestoneReviewModal
    :contract="showReview ? contract : null"
    :milestone="activeMilestone"
    :submission="activeSubmission"
    @update:model-value="showReview = false"
  />
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import { useToast }   from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import FundContractModal    from '@/components/modals/FundContractModal.vue'
import FundMilestoneModal   from '@/components/modals/FundMilestoneModal.vue'
import SignContractModal    from '@/components/modals/SignContractModal.vue'
import MilestoneReviewModal from '@/components/modals/MilestoneReviewModal.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  contract:   { type: Object, default: null },
  milestones: { type: Array,  default: () => [] },
})
const emit = defineEmits(['update:modelValue'])

const toast = useToast()
const { confirmAction } = useConfirm()
const busy = ref(false)
const busyMilestone = ref(null)
const showAddMilestone = ref(false)
const addingMilestone = ref(false)

// Local milestone copy — stays in sync with prop
const localMilestones = ref([...props.milestones])
watch(() => props.milestones, (v) => { localMilestones.value = [...v] }, { deep: true })

const addForm = ref({ title: '', amount_dollars: '', due_at: '', description: '' })

const isOpen = computed(() => props.modelValue)
function onUpdateOpen(v) { emit('update:modelValue', v) }
function close() { emit('update:modelValue', false) }

// Helpers
const val = (v) => (v && typeof v === 'object' && 'value' in v) ? v.value : (v ?? '')

const page = usePage()

const statusVal   = computed(() => val(props.contract?.status))
const paymentType = computed(() => val(props.contract?.payment_type) || 'one_time')
// If milestones exist, always treat as milestone-driven regardless of payment_type
const isMilestoneDriven = computed(() => paymentType.value === 'milestone' || localMilestones.value.length > 0)
const isActive    = computed(() => statusVal.value === 'active')
const isCompleted = computed(() => ['completed', 'closed'].includes(statusVal.value))

const statusLabel = computed(() => ({
  active: 'Active', pending_signature: 'Awaiting Signature', pending_funding: 'Awaiting Funding',
  completed: 'Completed', cancelled: 'Cancelled', draft: 'Draft', closed: 'Closed', disputed: 'Disputed',
}[statusVal.value] ?? statusVal.value ?? '—'))

const statusVariant = computed(() => ({
  active: 'green', pending_signature: 'gold', pending_funding: 'blue',
  completed: 'grey', cancelled: 'red', draft: 'grey', closed: 'grey', disputed: 'red',
}[statusVal.value] ?? 'grey'))

// ── Escrow computed ───────────────────────────────────────────────────────────
const escrowFunded   = computed(() => props.contract?.escrow_funded_cents ?? 0)
const escrowReleased = computed(() => props.contract?.escrow_released_cents ?? 0)
const escrowRefunded = computed(() => props.contract?.escrow_refunded_cents ?? 0)
const escrowHeld     = computed(() => Math.max(0, escrowFunded.value - escrowReleased.value - escrowRefunded.value))
const escrowUnfunded = computed(() => Math.max(0, (props.contract?.total_value_cents ?? 0) - escrowFunded.value))
const hasPaymentMethod = computed(() => !!(page.props.auth?.user?.stripe_payment_method_id))

function escrowPct(cents) {
  const total = props.contract?.total_value_cents ?? 0
  if (!total) return '0%'
  return Math.min(100, Math.round((cents / total) * 100)) + '%'
}

function formatAutoRelease(iso) {
  const diff  = new Date(iso) - Date.now()
  if (diff <= 0) return 'soon'
  const days  = Math.floor(diff / 86400000)
  const hours = Math.floor((diff % 86400000) / 3600000)
  return days > 0 ? `in ${days}d ${hours}h` : `in ${hours}h`
}

// ── Wave 3 modal state ────────────────────────────────────────────────────────
const showFundContract  = ref(false)
const showFundMilestone = ref(false)
const showSign          = ref(false)
const showReview        = ref(false)
const activeMilestone   = ref(null)   // for fund + review
const activeSubmission  = ref(null)   // for review

function openFundContract() { showFundContract.value = true }
function openFundMilestone(m) { activeMilestone.value = m; showFundMilestone.value = true }
function openSignModal()    { showSign.value = true }
function openReviewMilestone(m) {
  activeMilestone.value = m
  // submission is passed in via prop (milestones include latest submission)
  activeSubmission.value = m.latest_submission ?? null
  showReview.value = true
}

function milestoneStatusVal(m) { return val(m.status) }
function milestoneBadgeLabel(s) {
  return {
    pending: 'Pending', pending_funding: 'Awaiting Funding', funded: 'Funded',
    in_progress: 'In Progress', submitted: 'Under Review', revision_requested: 'Revision',
    approved: 'Approved', released: 'Paid', paid: 'Paid', disputed: 'Disputed', refunded: 'Refunded',
  }[val(s)] ?? val(s)
}
function milestoneBadgeVariant(s) {
  return {
    pending: 'neutral', pending_funding: 'neutral', funded: 'blue', in_progress: 'blue',
    submitted: 'gold', revision_requested: 'gold', approved: 'green',
    released: 'green', paid: 'green', disputed: 'red', refunded: 'neutral',
  }[val(s)] ?? 'neutral'
}

const milestoneTotalCents = computed(() =>
  localMilestones.value.reduce((sum, m) => sum + (m.amount_cents ?? 0), 0)
)
const allMilestonesPaid = computed(() =>
  localMilestones.value.length > 0 &&
  localMilestones.value.every(m => ['paid', 'released'].includes(val(m.status)))
)

function formatMoney(cents) {
  return '$' + ((cents ?? 0) / 100).toLocaleString('en-US', { minimumFractionDigits: 2 })
}
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

// ── Add milestone ─────────────────────────────────────────────────────────────
function cancelAdd() {
  showAddMilestone.value = false
  addForm.value = { title: '', amount_dollars: '', due_at: '', description: '' }
}

function submitAdd() {
  if (!addForm.value.title.trim()) { toast.error('Title is required.'); return }
  const amountCents = Math.round(parseFloat(addForm.value.amount_dollars || '0') * 100)

  addingMilestone.value = true
  router.post(
    route('provider.jobs.contract.milestones.store', props.contract.id),
    {
      title:        addForm.value.title,
      description:  addForm.value.description || null,
      amount_cents: amountCents,
      due_at:       addForm.value.due_at || null,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Milestone added.')
        cancelAdd()
        addingMilestone.value = false
      },
      onError: () => { toast.error('Could not add milestone.'); addingMilestone.value = false },
    }
  )
}

// ── Approve milestone ─────────────────────────────────────────────────────────
function doApproveMilestone(m) {
  confirmAction(
    { title: 'Approve Milestone', message: `Approve "${m.title}"? This marks it ready for payment.`, confirmLabel: 'Approve', destructive: false },
    () => {
      busyMilestone.value = m.id
      router.post(
        route('provider.jobs.contract.milestones.approve', [props.contract.id, m.id]),
        {},
        {
          preserveScroll: true,
          onSuccess: () => { toast.success('Milestone approved.'); busyMilestone.value = null },
          onError:   () => { toast.error('Could not approve milestone.'); busyMilestone.value = null },
        }
      )
    }
  )
}

// ── Pay milestone ─────────────────────────────────────────────────────────────
function doPayMilestone(m) {
  confirmAction(
    { title: 'Release Milestone Payment', message: `Release ${formatMoney(m.amount_cents)} to ${props.contract.bp?.display_name ?? 'the BP'} via Stripe for "${m.title}"?`, confirmLabel: 'Release Payment', destructive: false },
    () => {
      busyMilestone.value = m.id
      router.post(
        route('provider.jobs.contract.milestones.pay', [props.contract.id, m.id]),
        {},
        {
          preserveScroll: true,
          onSuccess: () => { toast.success('Milestone payment released via Stripe.'); busyMilestone.value = null },
          onError:   (e) => { toast.error(e.milestone || e.contract || 'Payment failed. Check your payment method in Settings → Billing.'); busyMilestone.value = null },
        }
      )
    }
  )
}

// ── Delete milestone ──────────────────────────────────────────────────────────
function doDeleteMilestone(m) {
  confirmAction(
    { title: 'Remove Milestone', message: `Remove "${m.title}"? This cannot be undone.`, confirmLabel: 'Remove', destructive: true },
    () => {
      busyMilestone.value = m.id
      router.delete(
        route('provider.jobs.contract.milestones.destroy', [props.contract.id, m.id]),
        {
          preserveScroll: true,
          onSuccess: () => { toast.info('Milestone removed.'); busyMilestone.value = null },
          onError:   () => { toast.error('Could not remove milestone.'); busyMilestone.value = null },
        }
      )
    }
  )
}

// ── End contract (milestone-based) ────────────────────────────────────────────
function endContract() {
  confirmAction(
    { title: 'End Contract', message: 'End this contract? This cannot be undone.', confirmLabel: 'End Contract', destructive: true },
    () => {
      busy.value = true
      router.post(
        route('provider.jobs.contract.end', props.contract.id),
        {},
        {
          preserveScroll: true,
          onSuccess: () => { toast.info('Contract ended.'); busy.value = false; close() },
          onError:   (e) => {
            toast.error(e.contract ?? 'Could not end contract.')
            busy.value = false
          },
        }
      )
    }
  )
}

// ── End & release (one-time) ──────────────────────────────────────────────────
const releaseForm = useForm({})

function endAndRelease() {
  confirmAction(
    {
      title: 'Release Payment & End Contract',
      message: `Release ${formatMoney(props.contract?.total_value_cents)} to ${props.contract?.bp?.display_name ?? 'the BP'} via Stripe and end the contract?`,
      confirmLabel: 'Release Payment',
      destructive: false,
    },
    () => {
      busy.value = true
      releaseForm.post(
        route('provider.jobs.contract.release-payment', props.contract.id),
        {
          preserveScroll: true,
          onSuccess: () => { toast.success('Payment released and contract ended.'); busy.value = false; close() },
          onError:   (e) => {
            toast.error(e.contract ?? 'Stripe transfer failed. Check BP Stripe connection.')
            busy.value = false
          },
        }
      )
    }
  )
}

// ── PDF Download ──────────────────────────────────────────────────────────────
function downloadPdf() {
  const c = props.contract
  if (!c) return

  const milestoneBadgeMap = { pending: 'Pending', submitted: 'Submitted', approved: 'Approved', rejected: 'Rejected', paid: 'Paid' }
  const milestoneRows = localMilestones.value.map(m => `
    <tr>
      <td>${m.title}</td>
      <td>${milestoneBadgeMap[val(m.status)] ?? val(m.status)}</td>
      <td style="text-align:right">${formatMoney(m.amount_cents)}</td>
    </tr>`).join('')

  const html = `<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8">
<title>Contract — ${c.title}</title>
<style>
  @media print { .no-print { display: none !important; } body { margin: 0; } }
  body { font-family: Georgia, 'Times New Roman', serif; color: #2a2a2a; padding: 36px; max-width: 780px; margin: 0 auto; }
  h1 { font-size: 22px; margin: 0 0 4px; }
  .sub { color: #6c6c6c; font-size: 13px; margin-bottom: 28px; }
  .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 24px; margin-bottom: 24px; background: #fbf8f1; border: 1px solid #e8dfc6; border-radius: 6px; padding: 16px 20px; }
  .label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #8a7d52; margin-bottom: 2px; }
  .value { font-size: 13px; color: #2a2a2a; font-weight: 600; margin-bottom: 10px; }
  .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #8a7d52; margin: 20px 0 8px; border-bottom: 1px solid #e8dfc6; padding-bottom: 4px; }
  table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
  th { text-align: left; padding: 8px 10px; background: #f7f5ef; font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.5px; color: #5b522f; border-bottom: 1px solid #e3e3e3; }
  td { padding: 8px 10px; border-bottom: 1px solid #f0ece0; vertical-align: top; }
  .invoice-note { margin-top: 24px; padding: 12px 16px; background: #f7f5ef; border-left: 3px solid #a0813e; font-size: 12.5px; color: #5b522f; border-radius: 0 6px 6px 0; }
  .print-btn { padding: 8px 18px; background: #8a7d52; color: #fff; border: 0; border-radius: 4px; font-size: 13px; font-weight: 700; cursor: pointer; margin-bottom: 20px; }
  footer { margin-top: 28px; padding-top: 16px; border-top: 1px solid #e3e3e3; font-size: 11px; color: #8a8a8a; }
</style>
</head><body>
  <div class="no-print"><button class="print-btn" onclick="window.print()">Print · Save as PDF</button></div>
  <h1>Contract Agreement</h1>
  <div class="sub">Aegis Practice Continuity Platform · Generated ${new Date().toLocaleDateString(undefined, { month: 'long', day: 'numeric', year: 'numeric' })}</div>
  <div class="grid">
    <div><div class="label">Contract ID</div><div class="value" style="font-family:monospace;font-size:11px">${c.id}</div></div>
    <div><div class="label">Status</div><div class="value">${statusLabel.value}</div></div>
    <div><div class="label">Engagement</div><div class="value">${c.title}</div></div>
    <div><div class="label">Total Value</div><div class="value" style="color:#2a6f2a">${formatMoney(c.total_value_cents)}</div></div>
    <div><div class="label">Payment Type</div><div class="value">${paymentType.value === 'milestone' ? 'Milestone-based' : 'One-time'}</div></div>
    <div><div class="label">Business Partner</div><div class="value">${c.bp?.display_name ?? '—'}</div></div>
    <div><div class="label">Signed</div><div class="value">${formatDate(c.signed_at)}</div></div>
    <div><div class="label">Started</div><div class="value">${formatDate(c.started_at)}</div></div>
  </div>
  ${milestoneRows ? `
  <div class="section-title">Milestones</div>
  <table>
    <thead><tr><th>Milestone</th><th>Status</th><th style="text-align:right">Amount</th></tr></thead>
    <tbody>${milestoneRows}</tbody>
  </table>` : ''}
  <div class="invoice-note">
    Invoices and payment records for this contract are managed in the Aegis Finances module.
    Reference Contract ID <strong>${c.id}</strong> when requesting payment documentation.
  </div>
  <footer>This document is generated by the Aegis Practice Continuity Platform. It serves as a record of the engagement terms agreed to on Aegis.</footer>
</body></html>`

  const win = window.open('', '_blank')
  win.document.write(html)
  win.document.close()
}
</script>

<style scoped>
.contract-summary-card { background: var(--surface-2); border-radius: var(--radius); padding: 16px; margin-bottom: 16px; border: 1px solid var(--border); }
.contract-summary-row  { display: flex; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
.contract-summary-row:last-child { margin-bottom: 0; }
.contract-summary-item { flex: 1; min-width: 130px; }
.contract-summary-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-4); margin-bottom: 4px; }
.contract-summary-val   { font-size: 14px; color: var(--text); font-weight: 500; }
.contract-summary-val.is-money { font-size: 20px; font-weight: 700; color: var(--green-dark); }
.contract-id-mono { font-size: 11px; font-family: monospace; color: var(--text-3); }

.milestone-section { border-top: 1px solid var(--border); padding-top: 16px; margin-top: 4px; margin-bottom: 16px; }
.milestone-section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
.milestone-section-title  { display: inline-flex; align-items: center; gap: 6px; font-size: 14px; font-weight: 600; }
.milestone-add-form { background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius); padding: 14px; margin-bottom: 12px; }
.form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }
.milestone-add-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 4px; }
.milestone-list { display: flex; flex-direction: column; gap: 6px; }
.milestone-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: var(--surface-2); border-radius: var(--radius-sm); gap: 12px; border: 1px solid var(--border); }
.milestone-row-left { flex: 1; min-width: 0; }
.milestone-title { font-size: 14px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.milestone-meta  { font-size: 12px; color: var(--text-4); margin-top: 2px; }
.milestone-row-right { display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0; }
.milestone-total-row { display: flex; align-items: center; gap: 10px; margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border); font-size: 13px; }
.milestone-total-label { color: var(--text-3); }
.milestone-total-val { font-weight: 700; color: var(--text); }

.contract-invoice-note { display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--text-3); background: var(--badge-bg-gold); border-radius: var(--radius-sm); padding: 10px 14px; border-left: 3px solid var(--gold); margin-top: 16px; }
.link-gold { color: var(--gold-dark); font-weight: 600; text-decoration: none; }
.link-gold:hover { text-decoration: underline; }
</style>
