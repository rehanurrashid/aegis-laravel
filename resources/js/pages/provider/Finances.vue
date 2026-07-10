<!--
  pages/provider/Finances.vue — subscription summary, invoices to pay
  (CS + BP), payment methods, spend/earnings history, and disputes.

  Wired to Provider/FinancesController::index() props:
    subscription, paymentMethods, csInvoices, bpInvoices, paymentHistory,
    earnings, totalSpendCents, outstandingCents, stripeConnected,
    has_valid_default_pm, disputes
-->
<template>
  <AppLayout :user="$page.props.auth.user" portal="practitioner" activePage="finances" pageTitle="Finances">
    <AegisHeroBanner
      eyebrow="Money"
      title="Finances"
      subtitle="Manage subscription, invoices from Continuity Stewards and Business Partners, and service session earnings."
    />

    <div class="stat-chips-row">
      <AegisStatChip icon="dollar"      :value="formatCents(totalSpendCents)"   label="Total spend (paid)" />
      <AegisStatChip icon="hourglass"   :value="formatCents(outstandingCents)"  label="Outstanding"        bg-color="var(--icon-bg-gold)"  icon-color="var(--gold-dark)" />
      <AegisStatChip icon="credit-card" :value="paymentMethods.length"          label="Payment methods"    bg-color="var(--icon-bg-blue)"  icon-color="var(--blue-dark)" />
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

    <!-- CS invoices -->
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
            <td>
              <AegisBadge :label="inv.status" :variant="statusVariant(inv.status)" />
            </td>
            <td class="action-cell">
              <!-- Disputed: frozen -->
              <template v-if="inv.status === 'disputed'">
                <a
                  v-if="inv.active_dispute_id"
                  :href="route('provider.disputes.show', { dispute: inv.active_dispute_id })"
                  class="btn btn-outline btn-sm"
                >View dispute →</a>
                <span v-else class="text-muted">Disputed</span>
              </template>
              <!-- Payable -->
              <template v-else>
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
                <!-- Dispute trigger -->
                <button
                  v-if="canDispute(inv)"
                  type="button"
                  class="btn btn-ghost btn-sm dispute-btn"
                  @click="openDispute(inv, 'cs_invoice')"
                >
                  <AegisIcon name="scale" :size="12" /> Dispute
                </button>
              </template>
            </td>
          </tr>
        </tbody>
      </table>
      <AegisEmptyState v-else icon="receipt" title="No CS invoices" description="Invoices from your Continuity Stewards appear here." />
    </AegisCard>

    <!-- BP invoices -->
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
            <td>
              <AegisBadge :label="inv.status" :variant="statusVariant(inv.status)" />
            </td>
            <td class="action-cell">
              <!-- Disputed: frozen -->
              <template v-if="inv.status === 'disputed'">
                <a
                  v-if="inv.active_dispute_id"
                  :href="route('provider.disputes.show', { dispute: inv.active_dispute_id })"
                  class="btn btn-outline btn-sm"
                >View dispute →</a>
                <span v-else class="text-muted">Disputed</span>
              </template>
              <!-- Payable -->
              <template v-else>
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
                <!-- Dispute trigger -->
                <button
                  v-if="canDispute(inv)"
                  type="button"
                  class="btn btn-ghost btn-sm dispute-btn"
                  @click="openDispute(inv, 'bp_invoice')"
                >
                  <AegisIcon name="scale" :size="12" /> Dispute
                </button>
              </template>
            </td>
          </tr>
        </tbody>
      </table>
      <AegisEmptyState v-else icon="briefcase" title="No BP invoices" description="Invoices from your Business Partners appear here." />
    </AegisCard>

    <!-- Disputes section -->
    <AegisCard title="Disputes" v-if="disputes.length > 0 || activeDisputes.length > 0">
      <!-- Active disputes -->
      <div v-if="activeDisputes.length">
        <div class="section-label">Active</div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Subject</th>
              <th>Reason</th>
              <th>Amount disputed</th>
              <th>Role</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="d in activeDisputes" :key="d.id">
              <td class="data-table-primary">{{ d.subject_label }}</td>
              <td>{{ d.reason_label }}</td>
              <td>{{ formatCents(d.amount_disputed_cents) }}</td>
              <td>
                <span class="role-pill" :class="`role-pill--${d.role}`">
                  {{ d.role === 'disputer' ? 'You opened' : 'Respondent' }}
                </span>
              </td>
              <td>
                <AegisBadge :label="d.status_label" :variant="d.status_color === 'gold' ? 'gold' : d.status_color === 'blue' ? 'blue' : 'neutral'" />
              </td>
              <td>
                <a :href="route('provider.disputes.show', { dispute: d.id })" class="btn btn-outline btn-sm">
                  View →
                </a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Resolved disputes — collapsed by default -->
      <details v-if="resolvedDisputes.length" class="resolved-disputes">
        <summary class="resolved-summary">
          {{ resolvedDisputes.length }} resolved dispute{{ resolvedDisputes.length !== 1 ? 's' : '' }}
        </summary>
        <table class="data-table" style="margin-top: 8px;">
          <thead>
            <tr>
              <th>Subject</th>
              <th>Reason</th>
              <th>Amount disputed</th>
              <th>Resolved</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="d in resolvedDisputes" :key="d.id">
              <td class="data-table-primary">{{ d.subject_label }}</td>
              <td>{{ d.reason_label }}</td>
              <td>{{ formatCents(d.amount_disputed_cents) }}</td>
              <td>{{ d.resolved_at ?? '—' }}</td>
              <td><AegisBadge :label="d.status_label" variant="green" /></td>
              <td>
                <a :href="route('provider.disputes.show', { dispute: d.id })" class="btn btn-outline btn-sm">
                  View →
                </a>
              </td>
            </tr>
          </tbody>
        </table>
      </details>

      <AegisEmptyState
        v-if="!activeDisputes.length && !resolvedDisputes.length"
        icon="scale"
        title="No disputes"
        description="If a transaction goes wrong, open a dispute from the CS or BP invoice tables above."
      />
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

    <!-- Open Dispute modal -->
    <OpenDisputeModal
      v-model="disputeModal"
      :subject="disputeTarget"
      post-route="provider.disputes.store"
      @opened="router.reload({ only: ['csInvoices', 'bpInvoices', 'disputes'] })"
    />
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout        from '@/layouts/AppLayout.vue'
import OpenDisputeModal from '@/components/modals/OpenDisputeModal.vue'

const props = defineProps({
  subscription:        { type: Object,  default: () => ({}) },
  paymentMethods:      { type: Array,   default: () => [] },
  csInvoices:          { type: Array,   default: () => [] },
  bpInvoices:          { type: Array,   default: () => [] },
  paymentHistory:      { type: Array,   default: () => [] },
  earnings:            { type: Array,   default: () => [] },
  totalSpendCents:     { type: Number,  default: 0 },
  outstandingCents:    { type: Number,  default: 0 },
  stripeConnected:     { type: Boolean, default: false },
  has_valid_default_pm:{ type: Boolean, default: false },
  disputes:            { type: Array,   default: () => [] },
})

// ── Pay CS ──────────────────────────────────────────────────────────────────
const paying       = ref(null)
const confirmCsPay = ref(false)
const confirmBpPay = ref(false)
const csTarget     = ref(null)
const bpTarget     = ref(null)

// ── Dispute ─────────────────────────────────────────────────────────────────
const disputeModal  = ref(false)
const disputeTarget = ref(null)

const activeDisputes   = computed(() => (props.disputes ?? []).filter(d =>
  ['open', 'awaiting_response', 'under_review'].includes(d.status)
))
const resolvedDisputes = computed(() => (props.disputes ?? []).filter(d =>
  d.status === 'resolved' || d.status === 'closed_no_action'
))

// ── Helpers ─────────────────────────────────────────────────────────────────
function formatCents(cents) {
  const n = Number(cents ?? 0) / 100
  return '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function statusVariant(s) {
  return {
    active: 'green', trialing: 'blue', past_due: 'gold', canceled: 'neutral',
    paid: 'green', sent: 'blue', overdue: 'red', draft: 'neutral', void: 'neutral',
    disputed: 'gold',
    pending: 'gold', failed: 'red', refunded: 'neutral', partially_refunded: 'gold',
  }[s] ?? 'neutral'
}

/**
 * Show "Dispute" button when:
 * - Invoice is paid or sent (not already disputed/draft/void)
 * - Not already disputed
 * - Issued within 60 days (or no issued_at)
 * - Provider has a valid default payment method on file
 */
function canDispute(inv) {
  if (!['paid', 'sent'].includes(inv.status)) return false
  if (inv.status === 'disputed') return false
  if (!props.has_valid_default_pm) return false
  if (!inv.issued_at) return true
  const days = (Date.now() - new Date(inv.issued_at).getTime()) / 86400000
  return days <= 60
}

function openDispute(inv, type) {
  const name = type === 'cs_invoice' ? (inv.cs_name ?? 'CS') : (inv.bp_name ?? 'BP')
  disputeTarget.value = {
    type,
    id:           inv.id,
    amount_cents: inv.total_cents,
    label:        `${type === 'cs_invoice' ? 'CS' : 'BP'} Invoice ${inv.invoice_number} · ${name}`,
  }
  disputeModal.value = true
}

// ── Pay actions ──────────────────────────────────────────────────────────────
function askPayCs(inv) { csTarget.value = inv; confirmCsPay.value = true }
function askPayBp(inv) { bpTarget.value = inv; confirmBpPay.value = true }

function doPayCs() {
  if (!csTarget.value) return
  paying.value = csTarget.value.id
  router.post(route('provider.finances.cs-invoice.pay', { invoice: csTarget.value.id }), {}, {
    preserveScroll: true,
    onSuccess: () => {
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
      confirmBpPay.value = false
      bpTarget.value = null
    },
    onFinish: () => { paying.value = null },
  })
}
</script>

<style scoped>
.sub-row   { display: flex; align-items: center; gap: 14px; padding: 4px 2px; }
.sub-name  { font-family: var(--font-serif); font-size: 16px; font-weight: 600; color: var(--text); }
.sub-price { font-size: 13px; color: var(--text-3); }

.pm-list  { display: flex; flex-direction: column; gap: 8px; }
.pm-item  { display: flex; align-items: center; gap: 12px; padding: 12px 14px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); }
.pm-info  { flex: 1; min-width: 0; }
.pm-brand { font-size: 13.5px; font-weight: 600; color: var(--text); }
.pm-exp   { font-size: 12px; color: var(--text-3); }

.action-cell      { display: flex; align-items: center; gap: 6px; white-space: nowrap; }
.dispute-btn      { color: var(--text-3); font-size: 12px; }
.dispute-btn:hover{ color: var(--gold-dark); }

.section-label { font-size: 11px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: var(--text-3); margin-bottom: 8px; }

.role-pill        { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
.role-pill--disputer   { background: var(--icon-bg-gold); color: var(--gold-dark); }
.role-pill--respondent { background: var(--surface-2); color: var(--text-3); }

.resolved-disputes { margin-top: 12px; }
.resolved-summary  { cursor: pointer; font-size: 12px; color: var(--text-3); user-select: none; }
.resolved-summary:hover { color: var(--text-2); }

.text-muted { color: var(--text-4); font-size: 13px; }
</style>
