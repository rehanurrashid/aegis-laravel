<!--
  ContractModal.vue — Provider contract viewer.
  Shows BpContract details, milestones, invoice reference.
  "Download PDF" prints a branded HTML receipt via a new tab.
  "End Contract" cancels via provider.jobs.contract.cancel.
-->
<template>
  <AegisModal v-model="isOpen" title="Contract Details" size="lg" @update:model-value="onUpdateOpen">
    <div v-if="contract">
      <div class="modal-sub" style="margin-bottom:14px">
        {{ contract.bp?.display_name ?? 'Business Partner' }} · {{ contract.title }}
        <template v-if="contract.started_at"> · Started {{ formatDate(contract.started_at) }}</template>
      </div>

      <!-- Meta grid -->
      <div class="contract-meta-grid">
        <div class="contract-meta-cell">
          <div class="contract-meta-label">Total Value</div>
          <div class="contract-meta-val contract-meta-green">{{ formatCents(contract.total_value_cents) }}</div>
        </div>
        <div class="contract-meta-cell">
          <div class="contract-meta-label">Status</div>
          <div class="contract-meta-val" :style="{ color: isActive ? 'var(--green)' : isCompleted ? 'var(--text-3)' : 'var(--red)' }">{{ statusLabel }}</div>
        </div>
        <div class="contract-meta-cell">
          <div class="contract-meta-label">Started</div>
          <div class="contract-meta-val">{{ formatDate(contract.started_at) }}</div>
        </div>
        <div class="contract-meta-cell">
          <div class="contract-meta-label">Signed</div>
          <div class="contract-meta-val">{{ formatDate(contract.signed_at) }}</div>
        </div>
        <div v-if="contract.completed_at" class="contract-meta-cell">
          <div class="contract-meta-label">Completed</div>
          <div class="contract-meta-val">{{ formatDate(contract.completed_at) }}</div>
        </div>
        <div class="contract-meta-cell">
          <div class="contract-meta-label">Contract ID</div>
          <div class="contract-meta-val" style="font-size:11px;font-family:monospace;color:var(--text-3)">{{ contract.id }}</div>
        </div>
      </div>

      <!-- Scope -->
      <div class="contract-scope">
        <span class="contract-scope-label">Engagement:</span> {{ contract.title }}
      </div>

      <!-- Milestones -->
      <div v-if="milestones.length" class="contract-milestones">
        <div class="contract-section-label">Milestones</div>
        <div v-for="m in milestones" :key="m.id" class="contract-milestone-row">
          <div class="contract-milestone-info">
            <span class="contract-milestone-title">{{ m.title }}</span>
            <span class="badge" :class="milestoneBadge(m.status)" style="font-size:10px">{{ milestoneLabel(m.status) }}</span>
          </div>
          <div class="contract-milestone-amount">{{ formatCents(m.amount_cents) }}</div>
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
      <button type="button" class="btn btn-outline" @click="close">Close</button>
      <button type="button" class="btn btn-outline" :disabled="!contract" @click="downloadPdf">
        <AegisIcon name="download" :size="13" />
        Download PDF
      </button>
      <button v-if="isActive" type="button" class="btn btn-danger" :disabled="busy" @click="endContract">
        {{ busy ? 'Ending…' : 'End Contract' }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AegisIcon from '@/components/ui/AegisIcon.vue'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  contract:   { type: Object, default: null },
  milestones: { type: Array,  default: () => [] },
})
const emit = defineEmits(['update:modelValue'])

const toast = useToast()
const { confirmAction } = useConfirm()
const busy = ref(false)

const isOpen = computed(() => props.modelValue)
function onUpdateOpen(v) { emit('update:modelValue', v) }
function close() { emit('update:modelValue', false) }

const statusVal  = computed(() => {
  const s = props.contract?.status
  return (s && typeof s === 'object' && 'value' in s) ? s.value : (s ?? '')
})
const statusLabel = computed(() => ({
  active: 'Active', completed: 'Completed', cancelled: 'Cancelled', draft: 'Draft'
}[statusVal.value] ?? statusVal.value ?? '—'))
const isActive    = computed(() => statusVal.value === 'active')
const isCompleted = computed(() => statusVal.value === 'completed')

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : '—'
}
function formatCents(c) {
  return c ? '$' + (c / 100).toLocaleString(undefined, { minimumFractionDigits: 2 }) : '—'
}

function milestoneLabel(s) {
  return { pending: 'Pending', submitted: 'Submitted', approved: 'Approved', rejected: 'Rejected' }[s] ?? s
}
function milestoneBadge(s) {
  return { pending: 'badge-gray', submitted: 'badge-gold', approved: 'badge-green', rejected: 'badge-red' }[s] ?? 'badge-gray'
}

// ── PDF Download ──────────────────────────────────────────────────────────────
function downloadPdf() {
  const c = props.contract
  if (!c) return

  const milestoneRows = props.milestones.map(m => `
    <tr>
      <td>${m.title}</td>
      <td>${milestoneLabel(m.status)}</td>
      <td style="text-align:right">${formatCents(m.amount_cents)}</td>
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
    <div><div class="label">Total Value</div><div class="value" style="color:#2a6f2a">${formatCents(c.total_value_cents)}</div></div>
    <div><div class="label">Business Partner</div><div class="value">${c.bp?.display_name ?? '—'}</div></div>
    <div><div class="label">Signed</div><div class="value">${formatDate(c.signed_at)}</div></div>
    <div><div class="label">Started</div><div class="value">${formatDate(c.started_at)}</div></div>
    <div><div class="label">Completed</div><div class="value">${formatDate(c.completed_at)}</div></div>
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

function endContract() {
  if (!props.contract) return
  confirmAction(
    { title: 'End Contract', message: 'End this contract? This cannot be undone.', confirmLabel: 'End Contract', destructive: true },
    () => {
      busy.value = true
      router.post(route('provider.jobs.contract.cancel', props.contract.id), {}, {
        preserveScroll: true,
        onSuccess: () => { toast.info('Contract ended.'); busy.value = false; close() },
        onError:   () => { toast.error('Could not end contract.'); busy.value = false },
      })
    },
  )
}
</script>

<style scoped>
.contract-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1px; background: var(--border); border: 1px solid var(--border); border-radius: 10px; overflow: hidden; margin-bottom: 14px; }
.contract-meta-cell { background: var(--surface-2); padding: 12px 14px; }
.contract-meta-label { font-size: 10px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-4); margin-bottom: 4px; }
.contract-meta-val { font-size: 14px; font-weight: 600; color: var(--text); }
.contract-meta-green { color: var(--green-dark); }
.contract-scope { font-size: 13px; color: var(--text-2); line-height: 1.6; background: var(--surface-2); border: 1px solid var(--border); border-radius: 8px; padding: 12px 14px; margin-bottom: 14px; }
.contract-scope-label { font-weight: 600; color: var(--text); }
.contract-section-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: var(--text-3); margin: 14px 0 8px; }
.contract-milestones { margin-bottom: 14px; }
.contract-milestone-row { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: var(--surface-2); border-radius: var(--radius-sm); margin-bottom: 4px; }
.contract-milestone-info { display: flex; align-items: center; gap: 8px; min-width: 0; }
.contract-milestone-title { font-size: 12.5px; font-weight: 500; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 260px; }
.contract-milestone-amount { font-size: 13px; font-weight: 700; color: var(--green-dark); flex-shrink: 0; }
.contract-invoice-note { display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--text-3); background: var(--badge-bg-gold); border-radius: var(--radius-sm); padding: 10px 14px; border-left: 3px solid var(--gold); }
.link-gold { color: var(--gold-dark); font-weight: 600; text-decoration: none; }
.link-gold:hover { text-decoration: underline; }
</style>
