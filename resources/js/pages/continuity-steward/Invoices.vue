<!--
  pages/continuity-steward/Invoices.vue — CS-issued invoices to practitioners.

  Wired to ContinuitySteward/InvoicesController::index() props:
    invoices, practitioners
-->
<template>
  <AppLayout :user="$page.props.auth.user" portal="continuity_steward" activePage="invoices" pageTitle="Invoices">
    <AegisHeroBanner
      eyebrow="Money"
      title="Invoices"
      :subtitle="`${draftCount} draft · ${sentCount} sent · ${paidCount} paid`"
    >
      <template #actions>
        <button type="button" class="btn btn-primary" @click="openCreate = true" :disabled="!practitioners.length">
          <AegisIcon name="plus" :size="14" /> New invoice
        </button>
      </template>
    </AegisHeroBanner>

    <div class="stat-chips-row">
      <AegisStatChip icon="edit-3"     :value="draftCount" label="Draft"    bg-color="var(--icon-bg-gray)" icon-color="var(--text-3)" />
      <AegisStatChip icon="send"       :value="sentCount"  label="Sent"     bg-color="var(--icon-bg-blue)" icon-color="var(--blue-dark)" />
      <AegisStatChip icon="check-circle" :value="paidCount" label="Paid"    bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
      <AegisStatChip icon="dollar"     :value="formatCents(outstandingCents)" label="Outstanding" />
    </div>

    <AegisCard v-if="invoices.length">
      <table class="data-table">
        <thead>
          <tr>
            <th>Invoice</th>
            <th>Practitioner</th>
            <th>Amount</th>
            <th>Issued</th>
            <th>Due</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="inv in invoices" :key="inv.id">
            <td class="data-table-primary">{{ inv.invoice_number }}</td>
            <td>{{ inv.practitioner }}</td>
            <td>{{ formatCents(inv.total_cents) }}</td>
            <td>{{ inv.issued_at ?? '—' }}</td>
            <td>{{ inv.due_at ?? '—' }}</td>
            <td><AegisBadge :label="inv.status" :variant="statusVariant(inv.status)" /></td>
            <td class="row-actions">
              <button v-if="inv.status === 'draft'"
                type="button" class="btn btn-sm btn-primary" :disabled="busy === inv.id"
                @click="sendInvoice(inv)"
              >
                <AegisIcon name="send" :size="12" /> Send
              </button>
              <button v-if="inv.status !== 'paid' && inv.status !== 'void'"
                type="button" class="btn btn-sm btn-outline btn-danger-ghost" :disabled="busy === inv.id"
                @click="askVoid(inv)"
              >
                Void
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </AegisCard>

    <AegisEmptyState
      v-else
      icon="receipt"
      title="No invoices yet"
      description="Draft an invoice for a practitioner you steward, then send it. They will pay via their Provider portal."
    />

    <!-- Create invoice modal -->
    <AegisModal v-model="openCreate" title="Create invoice" size="md">
      <div class="form-stack">
        <div class="form-group">
          <label class="form-label">Bill to (practitioner)</label>
          <select v-model="form.practitioner_id" class="form-input">
            <option value="">— Select a practitioner —</option>
            <option v-for="p in practitioners" :key="p.id" :value="p.id">
              {{ p.display_name }} ({{ p.email }})
            </option>
          </select>
          <div v-if="form.errors.practitioner_id" class="form-error">{{ form.errors.practitioner_id }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Amount (USD)</label>
          <input v-model.number="form.amountDollars" type="number" step="0.01" min="1" class="form-input" placeholder="e.g. 250.00" />
          <div v-if="form.errors.total_cents" class="form-error">{{ form.errors.total_cents }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Due date (optional)</label>
          <input v-model="form.due_at" type="date" class="form-input" />
          <div v-if="form.errors.due_at" class="form-error">{{ form.errors.due_at }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Memo (optional)</label>
          <textarea v-model="form.memo" class="form-input" rows="3" maxlength="500" placeholder="Describe services rendered…"></textarea>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="openCreate = false">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" :disabled="form.processing || !canCreate" @click="createInvoice">
          {{ form.processing ? 'Creating…' : 'Create draft' }}
        </button>
      </template>
    </AegisModal>

    <!-- Confirm void modal -->
    <AegisModal v-model="confirmVoid" title="Void invoice?" size="sm">
      <p v-if="voidTarget">
        Void invoice <strong>{{ voidTarget.invoice_number }}</strong> for
        <strong>{{ voidTarget.practitioner }}</strong>?
      </p>
      <p class="text-muted" style="font-size: 12px; margin-top: 8px;">Voided invoices cannot be paid or reactivated.</p>
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

const props = defineProps({
  invoices:      { type: Array, default: () => [] },
  practitioners: { type: Array, default: () => [] },
})

const toast = useToast()

const openCreate  = ref(false)
const confirmVoid = ref(false)
const voidTarget  = ref(null)
const busy        = ref(null)

const form = useForm({
  practitioner_id: '',
  amountDollars:   null,
  total_cents:     0,
  due_at:          '',
  memo:            '',
})

// Dollars-in / cents-out
watch(() => form.amountDollars, (v) => {
  form.total_cents = v ? Math.round(Number(v) * 100) : 0
})

const canCreate = computed(() => form.practitioner_id && form.total_cents >= 100)

const draftCount = computed(() => props.invoices.filter((i) => i.status === 'draft').length)
const sentCount  = computed(() => props.invoices.filter((i) => i.status === 'sent' || i.status === 'overdue').length)
const paidCount  = computed(() => props.invoices.filter((i) => i.status === 'paid').length)
const outstandingCents = computed(() => props.invoices
  .filter((i) => i.status === 'sent' || i.status === 'overdue')
  .reduce((s, i) => s + (i.total_cents || 0), 0))

function formatCents(cents) {
  const n = Number(cents ?? 0) / 100
  return '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function statusVariant(s) {
  return { draft: 'neutral', sent: 'blue', overdue: 'red', paid: 'green', void: 'neutral' }[s] ?? 'neutral'
}

function createInvoice() {
  form.post(route('cs.invoices.store'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Draft invoice created.')
      openCreate.value = false
      form.reset()
    },
  })
}

function sendInvoice(inv) {
  busy.value = inv.id
  router.post(route('cs.invoices.send', { invoice: inv.id }), {}, {
    preserveScroll: true,
    onSuccess: () => toast.success('Invoice sent.'),
    onFinish:  () => { busy.value = null },
  })
}

function askVoid(inv) { voidTarget.value = inv; confirmVoid.value = true }

function doVoid() {
  if (!voidTarget.value) return
  busy.value = voidTarget.value.id
  router.post(route('cs.invoices.void', { invoice: voidTarget.value.id }), {}, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Invoice voided.'); confirmVoid.value = false; voidTarget.value = null },
    onFinish:  () => { busy.value = null },
  })
}
</script>

<style scoped>
.form-stack   { display: flex; flex-direction: column; gap: 14px; }
.form-group   { display: flex; flex-direction: column; gap: 6px; }
.form-label   { font-size: 11px; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; color: var(--text-2); }
.form-input   { padding: 10px 12px; font-size: 13.5px; color: var(--text); background: var(--surface); border: 1.5px solid var(--border); border-radius: var(--radius-sm); }
.form-input:focus { border-color: var(--gold); outline: none; box-shadow: 0 0 0 3px rgba(196,169,106,.18); }
.form-error   { font-size: 12px; color: var(--red); }
.row-actions  { display: flex; gap: 8px; }
.text-muted   { color: var(--text-4); }
</style>
