<template>
  <AppLayout :user="user" portal="admin" activePage="payments" pageTitle="Subscription Payments">
    <div class="hero-banner is-quiet">
      <div class="page-hero-inner">
        <div class="page-hero-text">
          <div class="page-hero-eyebrow">Finance</div>
          <h1 class="page-hero-title">Transaction Ledger</h1>
          <p class="page-hero-sub">Audit practitioner payment records, trace Stripe transaction IDs, and monitor recurring revenues.</p>
        </div>
      </div>
    </div>

    <!-- SUMMARY METRICS -->
    <div class="stat-chip-row" v-if="summary">
      <div class="stat-chip">
        <span class="stat-chip-label">Total Revenue</span>
        <span class="stat-chip-value">${{ summary.total_revenue }}</span>
      </div>
      <div class="stat-chip">
        <span class="stat-chip-label">Completed Payments</span>
        <span class="stat-chip-value">{{ summary.completed }}</span>
      </div>
      <div class="stat-chip">
        <span class="stat-chip-label">Pending Reviews</span>
        <span class="stat-chip-value">{{ summary.pending }}</span>
      </div>
      <div class="stat-chip">
        <span class="stat-chip-label">Total Audit Trail</span>
        <span class="stat-chip-value">{{ summary.total }}</span>
      </div>
    </div>

    <!-- FILTERS ROW -->
    <div class="filters-row">
      <div class="select-box">
        <label class="filter-label">Status</label>
        <select v-model="statusFilter" class="filter-input select-input" @change="applyFilters">
          <option value="">All Transactions</option>
          <option value="pending">Pending</option>
          <option value="completed">Completed</option>
          <option value="failed">Failed</option>
          <option value="refunded">Refunded</option>
        </select>
      </div>

      <div class="date-box">
        <label class="filter-label">Paid From</label>
        <input type="date" v-model="fromDate" class="filter-input" @change="applyFilters" />
      </div>

      <div class="date-box">
        <label class="filter-label">Paid To</label>
        <input type="date" v-model="toDate" class="filter-input" @change="applyFilters" />
      </div>

      <button v-if="statusFilter || fromDate || toDate" @click="resetFilters" class="btn btn-outline btn-sm reset-btn">Reset</button>
    </div>

    <!-- PAYMENTS TABLE -->
    <div class="table-wrap" v-if="payments">
      <table class="table">
        <thead>
          <tr>
            <th>User</th>
            <th>Package Plan</th>
            <th>Billing Period</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Status</th>
            <th>Paid Date</th>
            <th class="actions-col">Details</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in payments.data" :key="p.id">
            <td>
              <div class="user-cell">
                <span class="user-name">{{ p.user_name }}</span>
                <span class="user-email">{{ p.user_email }}</span>
              </div>
            </td>
            <td>
              <div class="pkg-cell">
                <strong>{{ p.package_name }}</strong>
                <span v-if="p.package_tier" class="tier-badge" :class="'tier-' + p.package_tier">{{ p.package_tier }}</span>
              </div>
            </td>
            <td>
              <span class="period-badge" :class="p.billing_period">{{ p.billing_period }}</span>
            </td>
            <td><strong>${{ p.amount }}</strong> <small class="currency-label">{{ p.currency }}</small></td>
            <td>{{ p.payment_method || 'Stripe Card' }}</td>
            <td>
              <span class="badge" :class="'badge-' + p.status">{{ p.status }}</span>
            </td>
            <td>{{ p.paid_at || p.created_at }}</td>
            <td>
              <div class="actions-cell">
                <button @click="showPaymentDetails(p.id)" class="btn btn-outline btn-xs">View Receipt</button>
              </div>
            </td>
          </tr>
          <tr v-if="payments.data.length === 0">
            <td colspan="8" class="empty-table-text">No payment records found matching filters.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- PAGINATION -->
    <div class="pagination-row" v-if="payments && payments.links && payments.links.length > 3">
      <button
        v-for="(link, index) in payments.links"
        :key="index"
        class="page-link-btn"
        :class="{ active: link.active, disabled: !link.url }"
        :disabled="!link.url"
        @click="navigateLink(link.url)"
        v-html="link.label"
      ></button>
    </div>

    <!-- TRANSACTION DETAIL MODAL OVERLAY -->
    <div class="modal-overlay" v-if="payment" @click.self="closePaymentDetails">
      <div class="modal-box">
        <div class="modal-header">
          <h3 class="modal-title">Receipt / Transaction Details</h3>
          <button class="close-modal-btn" @click="closePaymentDetails">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>

        <div class="modal-body">
          <div class="receipt-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
          </div>

          <div class="receipt-amount-display">
            <span class="r-amt">${{ payment.amount }}</span>
            <span class="r-curr">{{ payment.currency }}</span>
          </div>

          <div class="receipt-grid">
            <div class="receipt-row">
              <span class="r-label">Transaction Reference:</span>
              <span class="r-value font-mono">{{ payment.id }}</span>
            </div>
            <div class="receipt-row">
              <span class="r-label">Stripe Charge ID:</span>
              <span class="r-value font-mono">{{ payment.stripe_payment_id || 'N/A' }}</span>
            </div>
            <div class="receipt-row">
              <span class="r-label">Payer Account:</span>
              <span class="r-value"><strong>{{ payment.user_name }}</strong> ({{ payment.user_email }})</span>
            </div>
            <div class="receipt-row">
              <span class="r-label">Subscribed Product:</span>
              <span class="r-value">Plan: {{ payment.package_name }} ({{ payment.billing_period }})</span>
            </div>
            <div class="receipt-row">
              <span class="r-label">Funding Channel:</span>
              <span class="r-value">{{ payment.payment_method || 'Card' }}</span>
            </div>
            <div class="receipt-row">
              <span class="r-label">Payment Status:</span>
              <span class="badge" :class="'badge-' + payment.status">{{ payment.status }}</span>
            </div>
            <div class="receipt-row">
              <span class="r-label">Settlement Timestamp:</span>
              <span class="r-value">{{ payment.paid_at || 'Unsettled' }}</span>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="closePaymentDetails" class="btn btn-primary">Dismiss Receipt</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Components/AppLayout.vue';

const props = defineProps({
  user: Object,
  payments: Object,
  summary: Object,
  filters: Object,
  payment: Object,
});

const statusFilter = ref(props.filters?.status || '');
const fromDate = ref(props.filters?.from || '');
const toDate = ref(props.filters?.to || '');

function applyFilters() {
  const query = {};
  if (statusFilter.value) query.status = statusFilter.value;
  if (fromDate.value) query.from = fromDate.value;
  if (toDate.value) query.to = toDate.value;

  router.get('/admin/payments', query, {
    preserveState: true,
    replace: true,
  });
}

function resetFilters() {
  statusFilter.value = '';
  fromDate.value = '';
  toDate.value = '';
  applyFilters();
}

function showPaymentDetails(id) {
  router.get('/admin/payments/' + id, {}, {
    preserveState: true,
  });
}

function closePaymentDetails() {
  router.get('/admin/payments', {
    status: statusFilter.value || undefined,
    from: fromDate.value || undefined,
    to: toDate.value || undefined,
  }, {
    preserveState: true,
  });
}

function navigateLink(url) {
  if (url) {
    router.get(url, {}, { preserveState: true });
  }
}
</script>

<style scoped>
.hero-banner.is-quiet {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-xl, 18px); padding: 28px 32px;
  margin-bottom: 22px; border-left: 4px solid var(--gold-dark, #a0813e);
}
.page-hero-inner { display: flex; justify-content: space-between; align-items: center; gap: 20px; }
.page-hero-eyebrow { font-size: 10px; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase; color: var(--gold-dark); margin-bottom: 8px; }
.page-hero-title { font-family: var(--font-serif, 'Spectral', Georgia, serif); font-size: 28px; font-weight: 600; color: var(--text); margin: 0; letter-spacing: -0.3px; }
.page-hero-sub { font-size: 13.5px; color: var(--text-3); line-height: 1.6; margin-top: 6px; max-width: 600px; }

/* Stat cards */
.stat-chip-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; }
.stat-chip { padding: 14px 18px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg, 14px); min-width: 140px; flex: 1; }
.stat-chip-label { font-size: 10px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: var(--text-4); display: block; margin-bottom: 6px; }
.stat-chip-value { font-family: var(--font-serif); font-size: 24px; font-weight: 700; color: var(--text); display: block; }

/* Filters row */
.filters-row {
  display: flex; gap: 14px; margin-bottom: 22px; align-items: flex-end; flex-wrap: wrap;
}
.select-box, .date-box { display: flex; flex-direction: column; gap: 5px; min-width: 160px; }
.filter-label { font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--text-4); letter-spacing: 0.5px; }
.filter-input {
  display: block; width: 100%; padding: 10px 13px; font-size: 13px; color: var(--text);
  background: var(--surface); border: 1.5px solid var(--border); border-radius: var(--radius-sm, 8px);
  outline: none; transition: border-color var(--transition);
}
.filter-input:focus { border-color: var(--gold); }
.select-input { appearance: auto; cursor: pointer; }
.reset-btn { height: 38px; padding: 0 16px; display: inline-flex; align-items: center; }

/* Table wrap styles */
.table-wrap { overflow-x: auto; border-radius: var(--radius-lg); border: 1px solid var(--border); margin-bottom: 24px; }
.table { width: 100%; border-collapse: collapse; }
.table th { font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--text-4); padding: 12px 16px; text-align: left; background: var(--surface-2); border-bottom: 1px solid var(--border); }
.table td { font-size: 13px; color: var(--text-2); padding: 12px 16px; border-bottom: 1px solid var(--border); }
.table tr:last-child td { border-bottom: none; }
.table tr:hover td { background: var(--surface-2); }

.user-cell { display: flex; flex-direction: column; }
.user-name { font-weight: 600; color: var(--text); }
.user-email { font-size: 11.5px; color: var(--text-4); }

.pkg-cell { display: flex; align-items: center; gap: 6px; }
.tier-badge {
  font-size: 9.5px; font-weight: 700; text-transform: uppercase;
  padding: 1px 4px; border-radius: 4px;
}
.tier-free { background: #e0f2f1; color: #00796b; }
.tier-basic { background: #e8f5e9; color: #2e7d32; }
.tier-pro { background: #e3f2fd; color: #1565c0; }
.tier-enterprise { background: #f3e5f5; color: #6a1b9a; }

.period-badge {
  font-size: 10px; font-weight: 600; text-transform: capitalize;
  padding: 2px 6px; border-radius: 4px; border: 1px solid transparent;
}
.period-badge.monthly { background: #fff3e0; color: #e65100; border-color: rgba(230,81,0,0.15); }
.period-badge.annual { background: #e8f5e9; color: #2e7d32; border-color: rgba(46,125,50,0.15); }

.currency-label { font-size: 10px; color: var(--text-4); font-weight: normal; }

/* Badges */
.badge { display: inline-flex; padding: 2px 8px; font-size: 10.5px; font-weight: 600; border-radius: var(--radius-full); text-transform: capitalize; }
.badge-completed { background: rgba(76,175,125,0.12); color: var(--green-dark); }
.badge-pending { background: rgba(232,169,74,0.12); color: var(--orange-dark); }
.badge-failed { background: rgba(224,92,92,0.12); color: var(--red); }
.badge-refunded { background: var(--surface-3); color: var(--text-3); }

.actions-cell { display: flex; gap: 6px; }
.btn-xs { padding: 4px 10px; font-size: 11px; }
.empty-table-text { text-align: center; color: var(--text-4); padding: 32px 16px; }

/* Pagination style */
.pagination-row { display: flex; gap: 6px; justify-content: center; margin-top: 10px; margin-bottom: 24px; }
.page-link-btn { border: 1px solid var(--border); background: var(--surface); padding: 6px 12px; font-size: 12.5px; font-weight: 600; border-radius: var(--radius-sm); color: var(--text-2); cursor: pointer; transition: all var(--transition); }
.page-link-btn:hover { background: var(--surface-2); border-color: var(--gold-light); }
.page-link-btn.active { background: var(--gold-dark); border-color: var(--gold-dark); color: #fff; }
.page-link-btn.disabled { opacity: 0.4; cursor: not-allowed; }

/* Modal Overlay styling */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(30,28,26,0.5);
  backdrop-filter: blur(2px); z-index: 1000; display: flex;
  align-items: center; justify-content: center; padding: 20px;
}
.modal-box {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-xl, 18px); width: 100%; max-width: 480px;
  box-shadow: 0 12px 36px rgba(30,28,26,0.25); overflow: hidden;
  animation: modalFadeIn 0.25s ease both;
}
@keyframes modalFadeIn {
  from { opacity: 0; transform: translateY(12px); }
  to { opacity: 1; transform: translateY(0); }
}
.modal-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 16px 22px; border-bottom: 1px solid var(--border);
}
.modal-title { font-family: var(--font-serif); font-size: 16px; font-weight: 700; color: var(--text); margin: 0; }
.close-modal-btn { background: none; border: none; padding: 4px; cursor: pointer; color: var(--text-4); display: flex; align-items: center; }
.close-modal-btn:hover { color: var(--text); }

.modal-body { padding: 24px 22px; text-align: center; }
.receipt-icon {
  width: 54px; height: 54px; border-radius: 50%;
  background: rgba(196,169,106,0.1); color: var(--gold-dark);
  display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;
}
.receipt-amount-display { margin-bottom: 20px; }
.r-amt { font-family: var(--font-serif); font-size: 36px; font-weight: 700; color: var(--text); }
.r-curr { font-size: 14px; font-weight: 700; color: var(--text-4); margin-left: 4px; }

.receipt-grid {
  border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);
  padding: 16px 0; display: flex; flex-direction: column; gap: 10px; text-align: left;
}
.receipt-row { display: flex; justify-content: space-between; font-size: 12.5px; align-items: center; gap: 8px; }
.r-label { font-weight: 600; color: var(--text-4); }
.r-value { color: var(--text-2); text-align: right; }
.font-mono { font-family: monospace; font-size: 11.5px; background: var(--surface-2); padding: 1px 5px; border-radius: 4px; }

.modal-footer { padding: 16px 22px; display: flex; justify-content: flex-end; border-top: 1px solid var(--border); }

@media (max-width: 900px) {
  .page-hero-inner { flex-direction: column; align-items: flex-start; gap: 14px; }
  .stat-chip-row { flex-direction: column; }
  .filters-row { flex-direction: column; align-items: stretch; }
  .select-box, .date-box { min-width: 0; }
  .reset-btn { align-self: flex-start; }
}
</style>
