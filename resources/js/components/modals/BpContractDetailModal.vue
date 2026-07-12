<!--
  BpContractDetailModal.vue — Full contract detail view for the BP portal.

  Wave 4 complete:
  - Read contract terms in scrollable panel
  - Sign contract CTA (opens SignContractModal) when awaiting BP signature
  - Escrow balance summary (funded / released / held)
  - Milestone breakdown with per-row escrow status
  - "Fund awaiting" notice when contract is pending_funding (waiting on provider)
  - Cancel contract action with reason
  - Download PDF placeholder (Wave 6)

  Props:
    contract  Object | null  — shaped by ContractService::getForBp()
-->
<template>
  <AegisModal
    :model-value="!!contract"
    title="Contract details"
    size="xl"
    @update:model-value="onClose"
  >
    <div v-if="contract" class="bp-contract-detail">

      <!-- ── Header ──────────────────────────────────────────────── -->
      <div class="bpcd-header">
        <div class="bpcd-header-main">
          <div class="bpcd-eyebrow">Contract with {{ contract.client_name }}</div>
          <div class="bpcd-title">{{ contract.title }}</div>
          <div class="bpcd-meta">
            <span>
              <AegisIcon name="dollar" :size="12" />
              {{ pricing.formatCents(contract.amount_cents) }}
            </span>
            <span>
              <AegisIcon name="layers" :size="12" />
              {{ contract.payment_type === 'milestone' ? 'Milestone-based' : 'One-time' }}
            </span>
            <span v-if="contract.signed_at">
              <AegisIcon name="calendar" :size="12" />
              Signed {{ contract.signed_at }}
            </span>
          </div>
        </div>
        <AegisBadge :label="statusLabel(contract.status)" :variant="statusVariant(contract.status)" />
      </div>

      <!-- ── Signature banner — awaiting BP signature ──────────── -->
      <div v-if="contract.status === 'pending_signature' && !contract.bp_has_signed" class="bpcd-action-banner bpcd-banner-gold">
        <div class="bpcd-banner-body">
          <AegisIcon name="file-pen" :size="16" />
          <div>
            <div class="bpcd-banner-title">Your signature is required</div>
            <div class="bpcd-banner-desc">
              Review the contract terms below, then sign to proceed.
              {{ contract.provider_has_signed ? 'The provider has already signed.' : 'Both parties must sign before funding.' }}
            </div>
          </div>
        </div>
        <button class="btn btn-primary" @click="openSign">
          <AegisIcon name="file-pen" :size="13" />
          Sign contract
        </button>
      </div>

      <!-- Both signed but awaiting provider to fund -->
      <div v-else-if="contract.status === 'pending_funding'" class="bpcd-action-banner bpcd-banner-blue">
        <AegisIcon name="hourglass" :size="16" />
        <div>
          <div class="bpcd-banner-title">Contract signed — awaiting funding by provider</div>
          <div class="bpcd-banner-desc">
            Both parties have signed. The provider must fund the escrow before milestones are unlocked.
          </div>
        </div>
      </div>

      <!-- Both signed; provider hasn't signed yet but BP has -->
      <div v-else-if="contract.status === 'pending_signature' && contract.bp_has_signed && !contract.provider_has_signed" class="bpcd-action-banner bpcd-banner-gold">
        <AegisIcon name="hourglass" :size="16" />
        <div>
          <div class="bpcd-banner-title">Awaiting provider signature</div>
          <div class="bpcd-banner-desc">You have signed. Waiting for the provider to sign.</div>
        </div>
      </div>

      <!-- Signature status row -->
      <div v-if="['pending_signature', 'pending_funding'].includes(contract.status)" class="bpcd-sig-row">
        <div class="bpcd-sig-item" :class="{ 'is-signed': contract.provider_has_signed }">
          <AegisIcon :name="contract.provider_has_signed ? 'check-circle' : 'circle'" :size="13" />
          Provider: {{ contract.provider_has_signed ? 'Signed' : 'Not signed' }}
        </div>
        <div class="bpcd-sig-item" :class="{ 'is-signed': contract.bp_has_signed }">
          <AegisIcon :name="contract.bp_has_signed ? 'check-circle' : 'circle'" :size="13" />
          You: {{ contract.bp_has_signed ? 'Signed' : 'Not signed' }}
        </div>
      </div>

      <!-- ── Escrow summary (active contracts) ─────────────────── -->
      <div v-if="['active', 'pending_funding'].includes(contract.status)" class="bpcd-escrow">
        <div class="bpcd-escrow-title">
          <AegisIcon name="shield-check" :size="13" />
          Escrow balance
        </div>
        <div class="bpcd-escrow-grid">
          <div class="bpcd-escrow-item">
            <div class="bpcd-escrow-label">Contract value</div>
            <div class="bpcd-escrow-val">{{ pricing.formatCents(contract.amount_cents) }}</div>
          </div>
          <div class="bpcd-escrow-item">
            <div class="bpcd-escrow-label">Funded in escrow</div>
            <div class="bpcd-escrow-val is-funded">{{ pricing.formatCents(contract.escrow_funded_cents ?? 0) }}</div>
          </div>
          <div class="bpcd-escrow-item">
            <div class="bpcd-escrow-label">Released to you</div>
            <div class="bpcd-escrow-val is-released">{{ pricing.formatCents(contract.escrow_released_cents ?? 0) }}</div>
          </div>
          <div class="bpcd-escrow-item">
            <div class="bpcd-escrow-label">Still held</div>
            <div class="bpcd-escrow-val">{{ pricing.formatCents(escrowHeld) }}</div>
          </div>
        </div>
        <div class="bpcd-escrow-bar">
          <div class="bpcd-escrow-bar-released" :style="{ width: escrowPct(contract.escrow_released_cents) }" />
          <div class="bpcd-escrow-bar-held"     :style="{ width: escrowPct(escrowHeld) }" />
        </div>
        <div class="bpcd-escrow-desc">
          Funds held by Aegis are released to you after provider approval, or automatically after
          {{ autoReleaseDays }} days if provider does not respond.
        </div>
      </div>

      <!-- ── Milestone list ─────────────────────────────────────── -->
      <div v-if="contract.milestones && contract.milestones.length" class="bpcd-milestones">
        <div class="bpcd-section-title">Milestones</div>
        <div class="bpcd-milestone-list">
          <div
            v-for="(m, i) in contract.milestones"
            :key="m.id"
            class="bpcd-milestone-row"
            :class="{
              'is-funded':             ['funded','in_progress'].includes(m.status),
              'is-submitted':          m.status === 'submitted',
              'is-revision':           m.status === 'revision_requested',
              'is-released':           ['released','paid'].includes(m.status),
              'is-disputed':           m.status === 'disputed',
            }"
          >
            <div class="bpcd-milestone-num">{{ i + 1 }}</div>
            <div class="bpcd-milestone-body">
              <div class="bpcd-milestone-title">{{ m.title }}</div>
              <div class="bpcd-milestone-meta">
                {{ pricing.formatCents(m.amount_cents) }}
                <span v-if="m.due_at">· Due {{ m.due_at }}</span>
              </div>
              <!-- Revision notes from provider -->
              <div v-if="m.status === 'revision_requested' && m.revision_notes" class="bpcd-milestone-revision-notes">
                <AegisIcon name="message-circle" :size="11" />
                <em>{{ m.revision_notes }}</em>
              </div>
              <!-- Auto-release countdown for submitted -->
              <div v-if="m.status === 'submitted' && m.auto_release_at" class="bpcd-milestone-autorelease">
                <AegisIcon name="hourglass" :size="11" />
                Auto-releases {{ formatAutoRelease(m.auto_release_at) }}
              </div>
            </div>
            <AegisBadge :label="msStatusLabel(m.status)" :variant="msStatusVariant(m.status)" />
          </div>
        </div>
      </div>

      <!-- ── Contract terms ─────────────────────────────────────── -->
      <div v-if="contract.terms" class="bpcd-terms">
        <div class="bpcd-section-title">Contract terms</div>
        <div class="bpcd-terms-body">
          <pre class="bpcd-terms-text">{{ contract.terms }}</pre>
        </div>
      </div>

      <!-- ── Cancelled notice ──────────────────────────────────── -->
      <div v-if="contract.status === 'cancelled'" class="bpcd-cancelled-notice">
        <AegisIcon name="x-circle" :size="14" />
        <span>
          <strong>Cancelled</strong>
          <template v-if="contract.cancel_reason">: {{ contract.cancel_reason }}</template>
        </span>
      </div>
    </div>

    <template #footer>
      <!-- Cancel (active only) -->
      <button
        v-if="['active', 'pending_signature', 'pending_funding'].includes(contract?.status)"
        type="button"
        class="btn btn-ghost btn-danger-ghost"
        :disabled="cancelForm.processing"
        @click="confirmCancel"
      >
        Cancel contract
      </button>

      <a :href="route('bp.milestones.index')" class="btn btn-outline">
        <AegisIcon name="flag-2" :size="13" />
        View milestones
      </a>

      <button type="button" class="btn btn-ghost" @click="onClose">
        Close
      </button>
    </template>

    <!-- Nested sign modal -->
    <SignContractModal
      :contract="showSign ? contract : null"
      portal="business_partner"
      @update:model-value="showSign = false"
    />
  </AegisModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm }       from '@inertiajs/vue3'
import { useConfirm }    from '@/composables/useConfirm'
import { useToast }      from '@/composables/useToast'
import { usePricingStore } from '@/stores/pricing'
import SignContractModal  from '@/components/modals/SignContractModal.vue'

const props = defineProps({
  contract: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue'])

const { confirmAction } = useConfirm()
const toast             = useToast()
const pricing           = usePricingStore()
const autoReleaseDays   = 7

// ── State ─────────────────────────────────────────────────────────────────────
const showSign   = ref(false)
const cancelForm = useForm({ reason: '' })

// ── Computed ──────────────────────────────────────────────────────────────────
const escrowHeld = computed(() =>
  Math.max(0,
    (props.contract?.escrow_funded_cents ?? 0)
    - (props.contract?.escrow_released_cents ?? 0)
    - (props.contract?.escrow_refunded_cents ?? 0),
  ),
)

function escrowPct(cents) {
  const total = props.contract?.amount_cents ?? 0
  if (!total) return '0%'
  return Math.min(100, Math.round(((cents ?? 0) / total) * 100)) + '%'
}

function formatAutoRelease(iso) {
  const diff  = new Date(iso) - Date.now()
  if (diff <= 0) return 'imminently'
  const days  = Math.floor(diff / 86400000)
  const hours = Math.floor((diff % 86400000) / 3600000)
  return days > 0 ? `in ${days}d ${hours}h` : `in ${hours}h`
}

// ── Labels ────────────────────────────────────────────────────────────────────
function statusLabel(s) {
  return {
    draft:             'Draft',
    pending_signature: 'Awaiting Signature',
    pending_funding:   'Awaiting Funding',
    active:            'Active',
    completed:         'Completed',
    cancelled:         'Cancelled',
    disputed:          'Disputed',
  }[s] ?? s
}

function statusVariant(s) {
  return {
    draft: 'neutral', pending_signature: 'gold', pending_funding: 'blue',
    active: 'green', completed: 'blue', cancelled: 'neutral', disputed: 'red',
  }[s] ?? 'neutral'
}

function msStatusLabel(s) {
  return {
    pending: 'Pending', pending_funding: 'Awaiting Funding',
    funded: 'Funded', in_progress: 'In Progress',
    submitted: 'Under Review', revision_requested: 'Revision Requested',
    approved: 'Approved', released: 'Paid', paid: 'Paid',
    disputed: 'Disputed', refunded: 'Refunded',
  }[s] ?? s
}

function msStatusVariant(s) {
  return {
    pending: 'neutral', pending_funding: 'neutral', funded: 'blue', in_progress: 'blue',
    submitted: 'gold', revision_requested: 'gold', approved: 'green',
    released: 'green', paid: 'green', disputed: 'red', refunded: 'neutral',
  }[s] ?? 'neutral'
}

// ── Actions ───────────────────────────────────────────────────────────────────
function onClose() {
  emit('update:modelValue', null)
}

function openSign() {
  showSign.value = true
}

function confirmCancel() {
  confirmAction(
    {
      title:        'Cancel this contract?',
      message:      'Any escrow funds held will be refunded to the provider. This cannot be undone.',
      confirmLabel: 'Cancel contract',
      destructive:  true,
    },
    () => {
      cancelForm.post(route('bp.contracts.cancel', { contract: props.contract.id }), {
        preserveScroll: true,
        onSuccess: () => {
          toast.success('Contract cancelled.')
          onClose()
        },
        onError: (e) => toast.error(e.contract ?? 'Could not cancel contract.'),
      })
    },
  )
}
</script>

<style scoped>
.bp-contract-detail { display: flex; flex-direction: column; gap: 20px; }

.bpcd-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
.bpcd-header-main { flex: 1; min-width: 0; }
.bpcd-eyebrow { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: var(--text-tertiary); margin-bottom: 4px; }
.bpcd-title { font-size: 18px; font-weight: 700; color: var(--text); margin-bottom: 8px; line-height: 1.3; }
.bpcd-meta { display: flex; align-items: center; gap: 14px; font-size: 12px; color: var(--text-secondary); flex-wrap: wrap; }
.bpcd-meta span { display: inline-flex; align-items: center; gap: 4px; }

/* Action banners */
.bpcd-action-banner { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; padding: 14px 16px; border-radius: var(--radius-lg); border: 1px solid; }
.bpcd-banner-body { display: flex; align-items: flex-start; gap: 10px; flex: 1; min-width: 0; }
.bpcd-banner-title { font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 3px; }
.bpcd-banner-desc  { font-size: 12px; color: var(--text-secondary); }
.bpcd-banner-gold  { background: var(--gold-bg, rgba(202, 158, 72, 0.08)); border-color: var(--gold-dark); }
.bpcd-banner-blue  { background: var(--blue-bg, rgba(59, 130, 246, 0.08)); border-color: var(--blue-dark, #3b82f6); }

/* Signature row */
.bpcd-sig-row { display: flex; gap: 20px; }
.bpcd-sig-item { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-secondary); }
.bpcd-sig-item.is-signed { color: var(--green-dark); }

/* Escrow */
.bpcd-escrow { background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 14px 16px; }
.bpcd-escrow-title { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; color: var(--text); margin-bottom: 12px; }
.bpcd-escrow-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 12px; }
.bpcd-escrow-label { font-size: 11px; color: var(--text-tertiary); margin-bottom: 2px; }
.bpcd-escrow-val { font-size: 14px; font-weight: 700; color: var(--text); }
.bpcd-escrow-val.is-funded   { color: var(--gold-dark); }
.bpcd-escrow-val.is-released { color: var(--green-dark); }
.bpcd-escrow-bar { height: 5px; background: var(--border); border-radius: 3px; display: flex; overflow: hidden; margin-bottom: 8px; }
.bpcd-escrow-bar-released { background: var(--green-dark); transition: width 0.3s; }
.bpcd-escrow-bar-held      { background: var(--gold-dark); transition: width 0.3s; }
.bpcd-escrow-desc { font-size: 11px; color: var(--text-tertiary); }

/* Milestones */
.bpcd-section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-tertiary); margin-bottom: 10px; }
.bpcd-milestone-list { display: flex; flex-direction: column; gap: 2px; border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; }
.bpcd-milestone-row { display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-bottom: 1px solid var(--border); transition: background var(--transition); }
.bpcd-milestone-row:last-child { border-bottom: none; }
.bpcd-milestone-row.is-funded   { background: rgba(59, 130, 246, 0.04); }
.bpcd-milestone-row.is-submitted { background: rgba(202, 158, 72, 0.06); }
.bpcd-milestone-row.is-revision  { background: rgba(234, 179, 8, 0.06); }
.bpcd-milestone-row.is-released  { background: rgba(34, 197, 94, 0.04); }
.bpcd-milestone-row.is-disputed  { background: rgba(220, 38, 38, 0.04); }
.bpcd-milestone-num  { font-size: 11px; font-weight: 700; color: var(--text-tertiary); min-width: 16px; }
.bpcd-milestone-body { flex: 1; min-width: 0; }
.bpcd-milestone-title { font-size: 13px; font-weight: 600; color: var(--text); }
.bpcd-milestone-meta  { font-size: 11px; color: var(--text-secondary); margin-top: 2px; }
.bpcd-milestone-revision-notes { display: flex; align-items: flex-start; gap: 4px; font-size: 11px; color: var(--text-secondary); font-style: italic; margin-top: 4px; }
.bpcd-milestone-autorelease     { display: flex; align-items: center; gap: 4px; font-size: 11px; color: var(--text-tertiary); margin-top: 3px; }

/* Terms */
.bpcd-terms-body { background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius); padding: 14px 16px; max-height: 200px; overflow-y: auto; }
.bpcd-terms-text { font-size: 12px; color: var(--text-secondary); white-space: pre-wrap; font-family: inherit; margin: 0; line-height: 1.6; }

/* Cancelled */
.bpcd-cancelled-notice { display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: var(--red-bg, rgba(220, 38, 38, 0.06)); border: 1px solid var(--red-dark); border-radius: var(--radius-lg); font-size: 13px; color: var(--red-dark); }
</style>
