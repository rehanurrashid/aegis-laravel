<!--
  pages/provider/Finances.vue — subscription summary, invoices to pay
  (CS + BP), payment methods, spend/earnings history.

  Wired to Provider/FinancesController::index() props:
    subscription, paymentMethods, csInvoices, bpInvoices, paymentHistory,
    earnings, totalSpendCents, outstandingCents, stripeConnected
-->
<template>
  <AppLayout :user="$page.props.auth.user" portal="practitioner" activePage="finances" pageTitle="Finances">
    <AegisHeroBanner
      eyebrow="Money"
      title="Finances"
      subtitle="Manage subscription, invoices from Continuity Stewards and Business Partners, and service session earnings."
    />

    <div class="stat-chips-row">
      <AegisStatChip icon="dollar"    :value="formatCents(totalSpendCents)"   label="Total spend (paid)" />
      <AegisStatChip icon="hourglass" :value="formatCents(outstandingCents)"  label="Outstanding"        bg-color="var(--icon-bg-gold)"  icon-color="var(--gold-dark)" />
      <AegisStatChip icon="credit-card" :value="paymentMethods.length"        label="Payment methods"    bg-color="var(--icon-bg-blue)"  icon-color="var(--blue-dark)" />
      <AegisStatChip icon="check-circle" :value="subscription?.plan_label ?? '—'" label="Subscription"    bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
    </div>

    <!-- Subscription strip -->
    <AegisCard title="Subscription">
      <div class="sub-row">
        <div class="sub-name">{{ subscription?.plan_label ?? 'No active plan' }}</div>
        <div class="sub-price" v-if="subscription?.amount_cents">
          {{ formatCents(subscription.amount_cents) }} / {{ subscription.interval ?? 'month' }}
        </div>
        <AegisBadge v-if="subscription?.status" :label="subscription.status" :variant="statusVariant(subscription.status)" />
      </div>
      <template #footer>
        <a :href="route('provider.settings.index') + '?section=billing'" class="btn btn-outline btn-sm">
          <AegisIcon name="settings" :size="13" /> Manage subscription
        </a>
      </template>
    </AegisCard>

    <!-- Outstanding CS invoices -->
    <AegisCard title="Continuity Steward invoices">
      <table v-if="csInvoices.length" class="data-table">
        <thead>
          <tr>
            <th>Invoice</th>
            <th>From</th>
            <th>Amount</th>
            <th>Issued</th>
            <th>Due</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="inv in csInvoices" :key="inv.id">
            <td class="data-table-primary">{{ inv.invoice_number }}</td>
            <td>{{ inv.cs_name }}</td>
            <td>{{ formatCents(inv.total_cents) }}</td>
            <td>{{ inv.issued_at ?? '—' }}</td>
            <td>{{ inv.due_at ?? '—' }}</td>
            <td><AegisBadge :label="inv.status" :variant="statusVariant(inv.status)" /></td>
            <td>
              <button
                v-if="inv.payable"
                type="button"
                class="btn btn-sm btn-primary"
                :disabled="paying === inv.id"
                @click="askPayCs(inv)"
              >
                <AegisIcon name="dollar" :size="12" />
                {{ paying === inv.id ? 'Paying…' : 'Pay' }}
              </button>
              <span v-else class="text-muted">—</span>
            </td>
          </tr>
        </tbody>
      </table>
      <AegisEmptyState v-else icon="receipt" title="No CS invoices" description="Invoices from your Continuity Stewards appear here." />
    </AegisCard>

    <!-- Outstanding BP invoices -->
    <AegisCard title="Business Partner invoices">
      <table v-if="bpInvoices.length" class="data-table">
        <thead>
          <tr>
            <th>Invoice</th>
            <th>From</th>
            <th>Amount</th>
            <th>Issued</th>
            <th>Due</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="inv in bpInvoices" :key="inv.id">
            <td class="data-table-primary">{{ inv.invoice_number }}</td>
            <td>{{ inv.bp_name }}</td>
            <td>{{ formatCents(inv.total_cents) }}</td>
            <td>{{ inv.issued_at ?? '—' }}</td>
            <td>{{ inv.due_at ?? '—' }}</td>
            <td><AegisBadge :label="inv.status" :variant="statusVariant(inv.status)" /></td>
            <td>
              <button
                v-if="inv.payable"
                type="button"
                class="btn btn-sm btn-primary"
                :disabled="paying === inv.id"
                @click="askPayBp(inv)"
              >
                <AegisIcon name="dollar" :size="12" />
                {{ paying === inv.id ? 'Paying…' : 'Pay' }}
              </button>
              <span v-else class="text-muted">—</span>
            </td>
          </tr>
        </tbody>
      </table>
      <AegisEmptyState v-else icon="briefcase" title="No BP invoices" description="Invoices from your Business Partners appear here." />
    </AegisCard>

    <!-- Payment methods -->
    <AegisCard title="Payment methods">
      <div v-if="paymentMethods.length" class="pm-list">
        <div v-for="pm in paymentMethods" :key="pm.id" class="pm-item">
          <AegisIcon name="credit-card" :size="18" />
          <div class="pm-info">
            <div class="pm-brand">{{ (pm.brand || 'card').toUpperCase() }} •••• {{ pm.last4 }}</div>
            <div class="pm-exp">Expires {{ pm.exp_month }}/{{ pm.exp_year }}</div>
          </div>
          <AegisBadge v-if="pm.is_default" label="Default" variant="green" />
        </div>
      </div>
      <AegisEmptyState v-else icon="credit-card" title="No payment methods" description="Add a card in Settings → Billing." />
      <template #footer>
        <a :href="route('provider.settings.index') + '?section=billing'" class="btn btn-outline btn-sm">
          <AegisIcon name="plus" :size="13" /> Manage cards in Settings
        </a>
      </template>
    </AegisCard>

    <!-- Spend history -->
    <AegisCard title="Spend history" v-if="paymentHistory.length">
      <table class="data-table">
        <thead>
          <tr><th>Date</th><th>Kind</th><th>Method</th><th>Amount</th><th>Status</th></tr>
        </thead>
        <tbody>
          <tr v-for="p in paymentHistory" :key="p.id">
            <td>{{ p.date }}</td>
            <td>{{ p.kind_label }}</td>
            <td>{{ p.method }}</td>
            <td>{{ formatCents(p.amount_cents) }}</td>
            <td><AegisBadge :label="p.status" :variant="statusVariant(p.status)" /></td>
          </tr>
        </tbody>
      </table>
    </AegisCard>

    <!-- Earnings (service sessions) -->
    <AegisCard title="Session earnings" v-if="earnings.length">
      <table class="data-table">
        <thead>
          <tr><th>Date</th><th>Amount</th><th>Status</th></tr>
        </thead>
        <tbody>
          <tr v-for="e in earnings" :key="e.id">
            <td>{{ e.date }}</td>
            <td>{{ formatCents(e.amount_cents) }}</td>
            <td><AegisBadge :label="e.status" :variant="statusVariant(e.status)" /></td>
          </tr>
        </tbody>
      </table>
    </AegisCard>

    <!-- Confirm-pay modal (CS invoice) -->
    <AegisModal v-model="confirmCsPay" title="Confirm payment" size="sm">
      <p v-if="csTarget">
        Pay <strong>{{ formatCents(csTarget.total_cents) }}</strong> to
        <strong>{{ csTarget.cs_name }}</strong> for invoice
        <strong>{{ csTarget.invoice_number }}</strong>?
      </p>
      <p class="text-muted" style="font-size: 12px; margin-top: 8px;">
        Funds are transferred directly to your Continuity Steward's Stripe account.
        Aegis does not hold or take a cut of this payment.
      </p>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="confirmCsPay = false">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" :disabled="paying === csTarget?.id" @click="doPayCs">
          {{ paying === csTarget?.id ? 'Processing…' : 'Pay now' }}
        </button>
      </template>
    </AegisModal>

    <!-- Confirm-pay modal (BP invoice) -->
    <AegisModal v-model="confirmBpPay" title="Confirm payment" size="sm">
      <p v-if="bpTarget">
        Pay <strong>{{ formatCents(bpTarget.total_cents) }}</strong> to
        <strong>{{ bpTarget.bp_name }}</strong> for invoice
        <strong>{{ bpTarget.invoice_number }}</strong>?
      </p>
      <p class="text-muted" style="font-size: 12px; margin-top: 8px;">
        Funds are transferred directly to your Business Partner's Stripe account.
        Aegis does not hold or take a cut of this payment.
      </p>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="confirmBpPay = false">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" :disabled="paying === bpTarget?.id" @click="doPayBp">
          {{ paying === bpTarget?.id ? 'Processing…' : 'Pay now' }}
        </button>
      </template>
    </AegisModal>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
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
  subscription:     { type: Object, default: () => ({}) },
  paymentMethods:   { type: Array,  default: () => [] },
  csInvoices:       { type: Array,  default: () => [] },
  bpInvoices:       { type: Array,  default: () => [] },
  paymentHistory:   { type: Array,  default: () => [] },
  earnings:         { type: Array,  default: () => [] },
  totalSpendCents:  { type: Number, default: 0 },
  outstandingCents: { type: Number, default: 0 },
  stripeConnected:  { type: Boolean, default: false },
})

const toast = useToast()

const paying       = ref(null)
const confirmCsPay = ref(false)
const confirmBpPay = ref(false)
const csTarget     = ref(null)
const bpTarget     = ref(null)

function formatCents(cents) {
  const n = Number(cents ?? 0) / 100
  return '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function statusVariant(s) {
  return {
    active: 'green', trialing: 'blue', past_due: 'gold', canceled: 'neutral',
    paid: 'green', sent: 'blue', overdue: 'red', draft: 'neutral', void: 'neutral',
    pending: 'gold', failed: 'red', refunded: 'neutral', partially_refunded: 'gold',
  }[s] ?? 'neutral'
}

function askPayCs(inv) { csTarget.value = inv; confirmCsPay.value = true }
function askPayBp(inv) { bpTarget.value = inv; confirmBpPay.value = true }

function doPayCs() {
  if (!csTarget.value) return
  paying.value = csTarget.value.id
  router.post(route('provider.finances.cs-invoice.pay', { invoice: csTarget.value.id }), {}, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Invoice paid.')
      confirmCsPay.value = false
      csTarget.value = null
    },
    onFinish: () => { paying.value = null },
  })
}

function doPayBp() {
  if (!bpTarget.value) return
  paying.value = bpTarget.value.id
  router.post(route('provider.jobs.bp-invoice.pay', { invoice: bpTarget.value.id }), {}, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Invoice paid.')
      confirmBpPay.value = false
      bpTarget.value = null
    },
    onFinish: () => { paying.value = null },
  })
}
</script>

<style scoped>
.sub-row { display: flex; align-items: center; gap: 14px; padding: 4px 2px; }
.sub-name { font-family: var(--font-serif); font-size: 16px; font-weight: 600; color: var(--text); }
.sub-price { font-size: 13px; color: var(--text-3); }
.pm-list { display: flex; flex-direction: column; gap: 8px; }
.pm-item { display: flex; align-items: center; gap: 12px; padding: 12px 14px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); }
.pm-info { flex: 1; min-width: 0; }
.pm-brand { font-size: 13.5px; font-weight: 600; color: var(--text); }
.pm-exp   { font-size: 12px; color: var(--text-3); }
.text-muted { color: var(--text-4); }
</style>
