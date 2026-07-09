<!--
  pages/business-partner/Invoices.vue — invoices issued to practitioners.
  Props from BP/InvoicesController::index (InvoiceService::getForBp shaped rows).
  Also loads active contracts from the same endpoint so the BP can pick one
  when creating a new invoice.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Money"
      title="Invoices"
      :subtitle="`${pendingCount} pending · ${pricing.formatCents(outstandingTotal)} outstanding`"
    >
      <template #actions>
        <button type="button" class="btn btn-primary" :disabled="!contracts.length" @click="openCreate = true">
          <AegisIcon name="plus" :size="14" />
          <span>Create invoice</span>
        </button>
      </template>
    </AegisHeroBanner>

    <div class="stat-chips-row">
      <AegisStatChip icon="receipt" :value="invoices.length" label="Total invoices" />
      <AegisStatChip icon="hourglass" :value="pendingCount" label="Pending" bg-color="var(--icon-bg-gold)" icon-color="var(--gold-dark)" />
      <AegisStatChip icon="check-circle" :value="paidCount" label="Paid" bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
      <AegisStatChip icon="dollar" :value="pricing.formatCents(outstandingTotal)" label="Outstanding" />
    </div>

    <AegisCard v-if="invoices.length">
      <table class="data-table">
        <thead>
          <tr>
            <th>Invoice #</th>
            <th>Client</th>
            <th>Contract</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Issued</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="i in invoices" :key="i.id">
            <td class="data-table-primary">{{ i.invoice_number }}</td>
            <td>{{ i.client_name }}</td>
            <td>{{ i.contract_title ?? '—' }}</td>
            <td>{{ pricing.formatCents(i.total_cents) }}</td>
            <td><AegisBadge :label="i.status" :variant="variant(i.status)" /></td>
            <td>{{ i.issued_at ?? '—' }}</td>
            <td class="row-actions">
              <button v-if="i.status === 'draft'"
                type="button" class="btn btn-sm btn-primary" :disabled="busy === i.id"
                @click="sendInvoice(i)"
              >
                <AegisIcon name="send" :size="12" /> Send
              </button>
              <button v-if="i.status !== 'paid' && i.status !== 'void'"
                type="button" class="btn btn-sm btn-outline btn-danger-ghost" :disabled="busy === i.id"
                @click="askVoid(i)"
              >
                Void
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </AegisCard>

    <AegisEmptyState v-else icon="receipt" title="No invoices yet" description="Once you bill a client, the invoice will appear here." />

    <!-- Create invoice modal — simplified single-line variant.
         For multi-line invoices, the underlying InvoiceService supports many
         line items via CreateInvoiceRequest; expose more UI in a follow-up. -->
    <AegisModal v-model="openCreate" title="Create invoice" size="md">
      <div class="form-stack">
        <div class="form-group">
          <label class="form-label">Contract</label>
          <select v-model="form.contract_id" class="form-input">
            <option value="">— Select a contract —</option>
            <option v-for="c in contracts" :key="c.id" :value="c.id">
              {{ c.title }} — {{ c.client_name }}
            </option>
          </select>
          <div v-if="form.errors.contract_id" class="form-error">{{ form.errors.contract_id }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <input v-model="form.description" class="form-input" maxlength="500" placeholder="Work performed…" />
          <div v-if="form.errors['line_items.0.description']" class="form-error">{{ form.errors['line_items.0.description'] }}</div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Quantity</label>
            <input v-model.number="form.quantity" type="number" min="1" step="1" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label">Unit price (USD)</label>
            <input v-model.number="form.unitDollars" type="number" min="0" step="0.01" class="form-input" placeholder="e.g. 150.00" />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Due date</label>
          <input v-model="form.due_at" type="date" class="form-input" required />
          <div v-if="form.errors.due_at" class="form-error">{{ form.errors.due_at }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Notes (optional)</label>
          <textarea v-model="form.notes" class="form-input" rows="2" maxlength="2000"></textarea>
        </div>
        <div class="form-total">
          <span class="form-total-label">Total</span>
          <span class="form-total-val">{{ pricing.formatCents(totalCents) }}</span>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="openCreate = false">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" :disabled="form.processing || !canCreate" @click="createInvoice">
          {{ form.processing ? 'Creating…' : 'Create draft' }}
        </button>
      </template>
    </AegisModal>

    <!-- Confirm void -->
    <AegisModal v-model="confirmVoid" title="Void invoice?" size="sm">
      <p v-if="voidTarget">
        Void invoice <strong>{{ voidTarget.invoice_number }}</strong>?
      </p>
      <p class="text-muted" style="font-size: 12px; margin-top: 8px;">Voided invoices cannot be paid or restored.</p>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="confirmVoid = false">Cancel</button>
        <button type="button" class="btn btn-danger btn-sm" :disabled="busy === voidTarget?.id" @click="doVoid">
          {{ busy === voidTarget?.id ? 'Voiding…' : 'Yes, void it' }}
        </button>
      </template>
    </AegisModal>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisHeroBanner from '@/components/ui/AegisHeroBanner.vue'
import AegisStatChip   from '@/components/ui/AegisStatChip.vue'
import AegisCard       from '@/components/ui/AegisCard.vue'
import AegisBadge      from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import AegisIcon       from '@/components/ui/AegisIcon.vue'
import AegisModal      from '@/components/ui/AegisModal.vue'
import { useToast }    from '@/composables/useToast'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({
  invoices:  { type: Array, default: () => [] },
  contracts: { type: Array, default: () => [] },
})

const toast   = useToast()
const pricing = usePricingStore()

const openCreate  = ref(false)
const confirmVoid = ref(false)
const voidTarget  = ref(null)
const busy        = ref(null)

const form = useForm({
  contract_id: '',
  description: '',
  quantity:    1,
  unitDollars: null,
  due_at:      '',
  notes:       '',
  // built at submit time:
  line_items:  [],
})

// Dollars → cents (single-line invoice)
const totalCents = computed(() => {
  const q  = Number(form.quantity ?? 0)
  const up = Number(form.unitDollars ?? 0) * 100
  return Math.round(q * up)
})

const canCreate = computed(() =>
  !!form.contract_id &&
  !!form.description &&
  form.quantity >= 1 &&
  Number(form.unitDollars ?? 0) > 0 &&
  !!form.due_at
)

const pendingCount = computed(() => props.invoices.filter((i) => i.status === 'sent' || i.status === 'overdue' || i.status === 'draft').length)
const paidCount    = computed(() => props.invoices.filter((i) => i.status === 'paid').length)
const outstandingTotal = computed(() => props.invoices
  .filter((i) => i.status === 'sent' || i.status === 'overdue')
  .reduce((s, i) => s + (i.total_cents || 0), 0)
)

function variant(s) {
  return { paid: 'green', sent: 'blue', overdue: 'red', draft: 'neutral', void: 'neutral' }[s] ?? 'neutral'
}

function createInvoice() {
  form.line_items = [{
    description:      form.description,
    quantity:         Number(form.quantity),
    unit_price_cents: Math.round(Number(form.unitDollars ?? 0) * 100),
  }]
  form.transform((d) => ({
    contract_id: d.contract_id,
    due_at:      d.due_at,
    notes:       d.notes,
    line_items:  d.line_items,
  })).post(route('bp.invoices.store'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Draft invoice created.')
      openCreate.value = false
      form.reset()
    },
  })
}

function sendInvoice(i) {
  busy.value = i.id
  router.post(route('bp.invoices.send', { invoice: i.id }), {}, {
    preserveScroll: true,
    onSuccess: () => toast.success('Invoice sent.'),
    onFinish:  () => { busy.value = null },
  })
}

function askVoid(i) { voidTarget.value = i; confirmVoid.value = true }

function doVoid() {
  if (!voidTarget.value) return
  busy.value = voidTarget.value.id
  router.post(route('bp.invoices.void', { invoice: voidTarget.value.id }), {}, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Invoice voided.'); confirmVoid.value = false; voidTarget.value = null },
    onFinish:  () => { busy.value = null },
  })
}
</script>

<style scoped>
.form-stack { display: flex; flex-direction: column; gap: 14px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-label { font-size: 11px; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; color: var(--text-2); }
.form-input { padding: 10px 12px; font-size: 13.5px; color: var(--text); background: var(--surface); border: 1.5px solid var(--border); border-radius: var(--radius-sm); }
.form-input:focus { border-color: var(--gold); outline: none; box-shadow: 0 0 0 3px rgba(196,169,106,.18); }
.form-error { font-size: 12px; color: var(--red); }
.form-row   { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-total { display: flex; justify-content: space-between; padding: 10px 12px; background: var(--surface-2); border-radius: var(--radius-sm); }
.form-total-label { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-3); }
.form-total-val   { font-family: var(--font-serif); font-size: 15px; font-weight: 700; color: var(--text); }
.row-actions { display: flex; gap: 8px; }
.text-muted  { color: var(--text-4); }
</style>
