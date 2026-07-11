<!--
  SettingsSubscriptionInvoices.vue — shared across Provider, CS (Business), BP portals.

  Props:
    invoices        Array  — from subscription.invoices (SubscriptionService::getFullSubscriptionData)
    portalLabel     String — e.g. 'Practice Continuity Subscription'
-->
<template>
  <div class="st-card">
    <div class="st-card-head">
      <div class="st-card-head-l">
        <span class="st-card-ico"><AegisIcon name="file-text" :size="17" /></span>
        <div>
          <div class="st-card-title">Subscription Invoices</div>
          <div class="st-card-sub">Billing history for your Aegis subscription</div>
        </div>
      </div>
      <AegisBadge
        :label="invoices.length + (invoices.length === 1 ? ' invoice' : ' invoices')"
        variant="neutral"
      />
    </div>

    <div class="st-card-body" style="padding:0;">
      <table v-if="invoices.length" class="table ssi-table" style="margin:0;">
        <thead>
          <tr>
            <th style="padding-left:20px;">Date</th>
            <th>Plan</th>
            <th>Amount</th>
            <th>Status</th>
            <th style="padding-right:20px;"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="inv in invoices"
            :key="inv.id"
            class="ssi-row"
            @click="open(inv)"
          >
            <td style="padding-left:20px;" class="ssi-date">{{ fmtDate(inv.date || inv.created) }}</td>
            <td>
              <div class="ssi-product">{{ inv.product_name || 'Aegis Subscription' }}</div>
              <div class="ssi-number">#{{ inv.number || inv.id }}</div>
            </td>
            <td class="ssi-amount">{{ fmtCents(inv.amount_cents) }}</td>
            <td><AegisBadge :label="inv.status" :variant="statusVariant(inv.status)" /></td>
            <td style="padding-right:20px;text-align:right;display:flex;align-items:center;justify-content:flex-end;gap:4px;padding-top:12px;padding-bottom:12px;">
              <a v-if="inv.hosted_url" :href="inv.hosted_url" target="_blank" class="btn-icon btn-icon-sm" data-tooltip="View on Stripe" @click.stop>
                <AegisIcon name="external-link" :size="12" />
              </a>
              <button type="button" class="btn-icon btn-icon-sm" data-tooltip="View invoice" @click.stop="open(inv)">
                <AegisIcon name="eye" :size="12" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <AegisEmptyState
        v-else
        icon="file-text"
        title="No invoices yet"
        description="Your subscription invoices will appear here after your first billing cycle."
        style="padding:32px 0;"
      />
    </div>
  </div>

  <!-- Invoice Detail Modal -->
  <AegisModal v-model="showModal" title="Subscription Invoice" size="lg">
    <div v-if="active" class="ssi-modal">
      <div class="ssi-sim-header">
        <div class="ssi-sim-logo"><AegisIcon name="star" :size="20" /></div>
        <div class="ssi-sim-brand">
          <div class="ssi-sim-from">Aegis Platform</div>
          <div class="ssi-sim-sub">{{ portalLabel }}</div>
        </div>
        <div class="ssi-sim-status">
          <AegisBadge :label="active.status" :variant="statusVariant(active.status)" />
          <div class="ssi-sim-date">{{ fmtDate(active.date || active.created) }}</div>
        </div>
      </div>

      <div class="ssi-sim-numrow">
        <span class="ssi-sim-numlabel">Invoice #</span>
        <span class="ssi-sim-num">{{ active.number || active.id }}</span>
      </div>

      <div class="ssi-sim-items">
        <div class="ssi-sim-item">
          <div class="ssi-sim-item-icon"><AegisIcon name="check-circle" :size="15" /></div>
          <div class="ssi-sim-item-name">{{ active.product_name || 'Aegis Subscription' }}</div>
          <div class="ssi-sim-item-price">{{ fmtCents(active.amount_cents) }}</div>
        </div>
      </div>

      <div class="ssi-sim-totals">
        <div class="ssi-sim-row">
          <span>Subtotal</span><span>{{ fmtCents(active.amount_cents) }}</span>
        </div>
        <div class="ssi-sim-row ssi-sim-row--main">
          <span>{{ active.status === 'paid' ? 'Total paid' : 'Amount due' }}</span>
          <span>{{ fmtCents(active.amount_cents) }}</span>
        </div>
      </div>

      <div class="ssi-sim-fine">
        <AegisIcon name="shield" :size="12" />
        Charged to your default card on file. Card details are securely tokenized by Stripe — Aegis never stores your full card number.
      </div>
    </div>
    <template #footer>
      <a v-if="active?.pdf_url" :href="active.pdf_url" target="_blank" class="btn btn-ghost">
        <AegisIcon name="download" :size="12" /> Download PDF
      </a>
      <a v-if="active?.hosted_url" :href="active.hosted_url" target="_blank" class="btn btn-gold">
        <AegisIcon name="external-link" :size="12" /> View Invoice on Stripe
      </a>
      <button v-else type="button" class="btn btn-outline" @click="showModal = false">Close</button>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  invoices:    { type: Array,  default: () => [] },
  portalLabel: { type: String, default: 'Aegis Subscription' },
})

const showModal = ref(false)
const active    = ref(null)

function open(inv) { active.value = inv; showModal.value = true }

function fmtCents(c) {
  return '$' + (Number(c ?? 0) / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function fmtDate(d) {
  if (!d) return '—'
  if (typeof d === 'number') return new Date(d * 1000).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
  return d
}
function statusVariant(s) {
  return { paid: 'green', open: 'blue', sent: 'blue', active: 'green', trialing: 'blue', past_due: 'gold', draft: 'neutral', void: 'neutral', canceled: 'neutral', uncollectible: 'red', overdue: 'red' }[s] ?? 'neutral'
}
</script>

<style scoped>
.ssi-table .ssi-row  { cursor: pointer; }
.ssi-table .ssi-row:hover td { background: var(--surface-2); }
.ssi-date    { font-size: 13px; color: var(--text-2); white-space: nowrap; }
.ssi-product { font-size: 13px; font-weight: 600; color: var(--text); }
.ssi-number  { font-size: 11px; color: var(--text-4); margin-top: 2px; font-family: var(--font-mono, monospace); }
.ssi-amount  { font-weight: 700; color: var(--text); white-space: nowrap; }

/* Modal */
.ssi-modal        { display: flex; flex-direction: column; gap: 0; }
.ssi-sim-header   { display: flex; align-items: center; gap: 14px; padding: 18px 20px; background: var(--badge-bg-gold); border: 1px solid var(--badge-border-gold); border-radius: var(--radius); margin-bottom: 16px; }
.ssi-sim-logo     { width: 44px; height: 44px; border-radius: var(--radius); background: var(--gold-dark); color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ssi-sim-brand    { flex: 1; }
.ssi-sim-from     { font-family: var(--font-serif); font-size: 16px; font-weight: 700; color: var(--text); }
.ssi-sim-sub      { font-size: 12px; color: var(--text-3); margin-top: 2px; }
.ssi-sim-status   { text-align: right; flex-shrink: 0; }
.ssi-sim-date     { font-size: 12px; color: var(--text-3); margin-top: 5px; }
.ssi-sim-numrow   { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
.ssi-sim-numlabel { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: var(--text-4); }
.ssi-sim-num      { font-family: var(--font-mono, monospace); font-size: 13px; font-weight: 600; color: var(--text-2); background: var(--surface-2); padding: 3px 10px; border-radius: var(--radius-sm); border: 1px solid var(--border); }
.ssi-sim-items    { border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; margin-bottom: 14px; }
.ssi-sim-item     { display: flex; align-items: center; gap: 12px; padding: 14px 16px; background: var(--surface); }
.ssi-sim-item-icon { width: 32px; height: 32px; border-radius: var(--radius-sm); background: var(--green-light); color: var(--green-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ssi-sim-item-name  { flex: 1; font-size: 14px; font-weight: 600; color: var(--text); }
.ssi-sim-item-price { font-family: var(--font-serif); font-size: 16px; font-weight: 700; color: var(--text); white-space: nowrap; }
.ssi-sim-totals   { border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; margin-bottom: 14px; }
.ssi-sim-row      { display: flex; justify-content: space-between; align-items: center; padding: 10px 16px; font-size: 13px; color: var(--text-2); border-bottom: 1px solid var(--border); }
.ssi-sim-row:last-child { border-bottom: none; }
.ssi-sim-row--main { font-family: var(--font-serif); font-size: 16px; font-weight: 700; color: var(--text); background: var(--surface-2); padding: 14px 16px; }
.ssi-sim-fine     { display: flex; align-items: center; gap: 8px; font-size: 11px; color: var(--text-4); padding: 10px 14px; background: var(--surface-2); border-radius: var(--radius-sm); border: 1px solid var(--border); line-height: 1.5; }

/* ── st-card design (scoped copy from Settings.vue — not inherited from parent) ── */
.st-card       { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); }
.st-card-head  { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 18px 20px; border-bottom: 1px solid var(--border); }
.st-card-head-l { display: flex; align-items: center; gap: 10px; min-width: 0; }
.st-card-ico   { width: 34px; height: 34px; border-radius: var(--radius-sm); background: var(--icon-bg-gold); color: var(--gold-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.st-card-title { font-size: 15px; font-weight: 700; color: var(--text); font-family: var(--font-serif); }
.st-card-sub   { font-size: 12px; color: var(--text-3); margin-top: 1px; }
.st-card-body  { padding: 18px 20px; }
</style>