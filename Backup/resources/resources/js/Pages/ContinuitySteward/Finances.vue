<template>
  <AppLayout :user="user" portal="continuity_steward" activePage="finances" pageTitle="Finances">

    <!-- ═══ HEADER ═══ -->
    <div class="fn-header">
      <div>
        <h1 class="fn-title">Finances</h1>
        <p class="fn-sub">Invoice history</p>
      </div>
      <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
    </div>

    <!-- ═══ STAT CARDS ═══ -->
    <div class="fn-stats">
      <div class="fn-stat">
        <div class="fn-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div>
        <div class="fn-stat-num">$0</div>
        <div class="fn-stat-label">This Month</div>
      </div>
      <div class="fn-stat">
        <div class="fn-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
        <div class="fn-stat-num">$0</div>
        <div class="fn-stat-label">Year to Date</div>
      </div>
      <div class="fn-stat">
        <div class="fn-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div class="fn-stat-num">—</div>
        <div class="fn-stat-label">Avg Days to Payment</div>
      </div>
    </div>

    <!-- ═══ INVITED CS BANNER ═══ -->
    <div class="fn-invited-banner">
      <div class="fn-invited-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
      <div class="fn-invited-body">
        <div class="fn-invited-title">Invited CS — view-only finances</div>
        <div class="fn-invited-sub">Invoice creation and Stripe payouts are available on the Business CS plan. Upgrade to unlock full billing.</div>
      </div>
      <button type="button" class="btn btn-outline btn-sm fn-invited-cta">Upgrade <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></button>
    </div>

    <!-- ═══ TABS ═══ -->
    <div class="fn-tabs">
      <button v-for="t in tabs" :key="t.key" type="button" class="fn-tab" :class="{ active: activeTab === t.key }" @click="activeTab = t.key">{{ t.label }}</button>
    </div>

    <!-- ═══ OVERVIEW ═══ -->
    <div v-if="activeTab === 'overview'" class="fn-empty">
      <div class="fn-empty-ico"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
      <div class="fn-empty-title">No earnings yet</div>
      <div class="fn-empty-sub">Your billing summary will appear here once you're on a Business CS plan and start invoicing practitioners.</div>
    </div>

    <!-- ═══ ACTIVE INVOICES ═══ -->
    <div v-else-if="activeTab === 'active'">
      <div class="fn-listbar">
        <span class="fn-count">{{ activeInvoices.length }} invoices</span>
        <button type="button" class="btn btn-gold btn-sm" @click="showNewInvoice = true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> New Invoice</button>
      </div>
      <div class="fn-filters">
        <div class="fn-search">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input v-model="search" placeholder="Search invoices..." />
        </div>
        <select v-model="statusFilter" class="form-select fn-select"><option>All Statuses</option><option>Draft</option><option>Sent</option><option>Paid</option></select>
        <select v-model="sortBy" class="form-select fn-select"><option>Newest first</option><option>Oldest first</option><option>Amount</option></select>
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg> Filter</button>
      </div>
      <div class="fn-empty">
        <div class="fn-empty-ico"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/></svg></div>
        <div class="fn-empty-title">No active invoices</div>
        <div class="fn-empty-sub">All invoices are settled, or none have been created yet.</div>
      </div>
    </div>

    <!-- ═══ AWAITING PAYMENT ═══ -->
    <div v-else-if="activeTab === 'awaiting'">
      <div class="fn-listbar"><span class="fn-count">0 invoices past due</span></div>
      <div class="fn-empty">
        <div class="fn-empty-ico"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
        <div class="fn-empty-title">No invoices awaiting payment</div>
        <div class="fn-empty-sub">All sent invoices are within their payment window.</div>
      </div>
    </div>

    <!-- ═══ PAYMENT HISTORY ═══ -->
    <div v-else>
      <div class="fn-filters">
        <select v-model="historyRange" class="form-select fn-select"><option>All time</option><option>This year</option><option>Last 90 days</option><option>Last 30 days</option></select>
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export CSV</button>
      </div>
      <div class="fn-empty">
        <div class="fn-empty-ico"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
        <div class="fn-empty-title">No payment history yet</div>
        <div class="fn-empty-sub">Paid and voided invoices will appear here.</div>
      </div>
    </div>

    <!-- ═══ NEW INVOICE MODAL ═══ -->
    <Modal v-model="showNewInvoice" title="New Invoice">
      <div class="fn-steps">
        <div v-for="s in invoiceSteps" :key="s.n" class="fn-step-wrap">
          <div class="fn-step" :class="{ active: invoiceStep === s.n, done: invoiceStep > s.n }">
            <span class="fn-stepnum">
              <svg v-if="invoiceStep > s.n" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              <template v-else>{{ s.n }}</template>
            </span>
            {{ s.label }}
          </div>
          <div v-if="s.n < 3" class="fn-stepline"></div>
        </div>
      </div>

      <!-- Step 1 -->
      <template v-if="invoiceStep === 1">
        <div class="fn-field">
          <label class="fn-label">Practitioner</label>
          <select v-model="invoiceForm.practitioner" class="form-select"><option value="">Select practitioner...</option><option v-for="p in practitionerOptions" :key="p.name">{{ p.name }}</option></select>
        </div>
        <div class="fn-field">
          <label class="fn-label">Service Type</label>
          <select v-model="invoiceForm.service" class="form-select"><option>MAAT Pro Service</option><option>Retainer</option><option>Critical Incident Activation</option><option>Readiness Review</option><option>Consultation</option></select>
        </div>
      </template>

      <!-- Step 2 -->
      <template v-else-if="invoiceStep === 2">
        <div class="fn-field">
          <label class="fn-label">Line Items</label>
          <div v-for="(item, i) in invoiceForm.lineItems" :key="i" class="fn-lineitem">
            <input v-model="item.description" class="fn-input" placeholder="Description" />
            <input v-model.number="item.qty" class="fn-input fn-li-qty" type="number" min="1" />
            <input v-model.number="item.rate" class="fn-input fn-li-rate" type="number" min="0" placeholder="Rate ($)" />
            <button type="button" class="fn-li-remove" :disabled="invoiceForm.lineItems.length === 1" @click="removeLineItem(i)" aria-label="Remove"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
          </div>
          <button type="button" class="fn-add-line" @click="addLineItem"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add line item</button>
        </div>
        <div class="fn-total-bar"><span>Total</span><strong>${{ invoiceTotal.toFixed(2) }}</strong></div>
        <div class="fn-field">
          <label class="fn-label">Due Date</label>
          <input v-model="invoiceForm.dueDate" class="fn-input" type="date" />
        </div>
        <div class="fn-field">
          <label class="fn-label">Notes <span class="fn-opt">(optional)</span></label>
          <input v-model="invoiceForm.notes" class="fn-input" placeholder="Any context for the practitioner" />
        </div>
      </template>

      <!-- Step 3 -->
      <template v-else>
        <div class="fn-review-lead">Review before sending.</div>
        <div class="fn-review-card">
          <div class="fn-review-name">{{ invoiceForm.practitioner || 'Practitioner' }}<template v-if="practitionerCred"> — {{ practitionerCred }}</template></div>
          <div class="fn-review-meta">{{ invoiceForm.service }}<template v-if="invoiceForm.dueDate"> · Due {{ invoiceForm.dueDate }}</template></div>
          <div class="fn-review-lines">
            <div v-for="(item, i) in invoiceForm.lineItems" :key="i" class="fn-review-line">
              <span>{{ item.description || 'Item' }} × {{ item.qty || 1 }}</span>
              <span>${{ ((item.qty || 0) * (item.rate || 0)).toFixed(2) }}</span>
            </div>
          </div>
          <div class="fn-review-total"><span>Total</span><strong>${{ invoiceTotal.toFixed(2) }}</strong></div>
          <div v-if="invoiceForm.notes" class="fn-review-notes">Notes: {{ invoiceForm.notes }}</div>
        </div>
      </template>

      <template #footer>
        <button v-if="invoiceStep > 1" type="button" class="fn-back" @click="invoiceStep--"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg> Back</button>
        <div class="fn-footer-spacer"></div>
        <button type="button" class="btn btn-outline btn-sm" @click="closeInvoice">Cancel</button>
        <button v-if="invoiceStep === 3" type="button" class="btn btn-outline btn-sm" @click="closeInvoice">Save as Draft</button>
        <button v-if="invoiceStep < 3" type="button" class="btn btn-gold btn-sm" @click="invoiceStep++">Next <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>
        <button v-else type="button" class="btn btn-gold btn-sm" @click="closeInvoice">Send Invoice</button>
      </template>
    </Modal>

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
import Modal from '../../Components/Modal.vue';
defineProps({ user: Object });

const activeTab = ref('active');
const tabs = [
  { key: 'overview', label: 'Overview' },
  { key: 'active', label: 'Active Invoices' },
  { key: 'awaiting', label: 'Awaiting Payment' },
  { key: 'history', label: 'Payment History' },
];

const search = ref('');
const statusFilter = ref('All Statuses');
const sortBy = ref('Newest first');
const historyRange = ref('All time');

const activeInvoices = [];

const showNewInvoice = ref(false);
const invoiceStep = ref(1);
const invoiceSteps = [
  { n: 1, label: 'Practitioner' },
  { n: 2, label: 'Line Items' },
  { n: 3, label: 'Review' },
];
const practitionerOptions = [
  { name: 'Dr. Sarah Johnson', credentials: 'PhD, LMFT' },
];
const invoiceForm = reactive({
  practitioner: '',
  service: 'MAAT Pro Service',
  lineItems: [{ description: '', qty: 1, rate: '' }],
  dueDate: '',
  notes: '',
});
const invoiceTotal = computed(() =>
  invoiceForm.lineItems.reduce((sum, it) => sum + (Number(it.qty) || 0) * (Number(it.rate) || 0), 0)
);
const practitionerCred = computed(() => {
  const p = practitionerOptions.find(o => o.name === invoiceForm.practitioner);
  return p ? p.credentials : '';
});
function addLineItem() { invoiceForm.lineItems.push({ description: '', qty: 1, rate: '' }); }
function removeLineItem(i) { if (invoiceForm.lineItems.length > 1) invoiceForm.lineItems.splice(i, 1); }
function closeInvoice() {
  showNewInvoice.value = false;
  invoiceStep.value = 1;
  invoiceForm.practitioner = '';
  invoiceForm.service = 'MAAT Pro Service';
  invoiceForm.lineItems = [{ description: '', qty: 1, rate: '' }];
  invoiceForm.dueDate = '';
  invoiceForm.notes = '';
}
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════════
   CS — FINANCES
   ════════════════════════════════════════════════════════════════ */

/* HEADER */
.fn-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:26px 30px; margin-bottom:16px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.fn-title { font-family:var(--font-serif); font-size:34px; font-weight:600; letter-spacing:-0.5px; color:var(--text); margin:0; line-height:1.05; }
.fn-sub { font-size:13.5px; color:var(--text-3); margin-top:8px; }

/* STAT CARDS (3) */
.fn-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:16px; }
.fn-stat { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px 20px; box-shadow:var(--shadow-xs); display:grid; grid-template-columns:auto 1fr; grid-template-rows:auto auto; column-gap:14px; align-items:center; }
.fn-stat-ico { grid-row:1 / 3; width:36px; height:36px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.fn-stat-num { font-family:var(--font-serif); font-size:26px; font-weight:700; line-height:1; color:var(--text); }
.fn-stat-label { font-size:12px; color:var(--text-3); margin-top:3px; }

/* INVITED BANNER */
.fn-invited-banner { display:flex; align-items:center; gap:16px; padding:16px 20px; background:linear-gradient(95deg,var(--badge-bg-gold) 0%,var(--surface) 80%); border:1px solid rgba(192,154,82,.30); border-radius:var(--radius-lg); box-shadow:var(--shadow-sm); margin-bottom:18px; }
.fn-invited-ico { width:38px; height:38px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.fn-invited-body { flex:1; min-width:0; }
.fn-invited-title { font-size:13px; font-weight:700; color:var(--text); margin-bottom:3px; }
.fn-invited-sub { font-size:12px; color:var(--text-3); line-height:1.5; }
.fn-invited-cta { flex-shrink:0; white-space:nowrap; }

/* TABS */
.fn-tabs { display:inline-flex; align-items:center; gap:2px; padding:4px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-full); margin-bottom:18px; flex-wrap:wrap; }
.fn-tab { padding:8px 18px; font-size:12.5px; font-weight:600; color:var(--text-3); background:none; border:none; border-radius:var(--radius-full); cursor:pointer; transition:all .15s ease; white-space:nowrap; }
.fn-tab:hover { color:var(--text); }
.fn-tab.active { background:var(--gold-dark); color:var(--text-inverted); }

/* LIST BAR */
.fn-listbar { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:14px; }
.fn-count { font-size:13px; color:var(--text-3); }

/* FILTERS */
.fn-filters { display:flex; gap:10px; margin-bottom:18px; flex-wrap:wrap; }
.fn-search { position:relative; flex:1; min-width:200px; }
.fn-search svg { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--text-4); pointer-events:none; }
.fn-search input { width:100%; padding:9px 13px 9px 36px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; transition:border-color .15s ease; }
.fn-search input:focus { border-color:var(--gold-dark); }
.fn-search input::placeholder { color:var(--text-4); }
.fn-select { width:auto; min-width:150px; }

/* EMPTY STATE */
.fn-empty { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); padding:56px 24px; text-align:center; }
.fn-empty-ico { width:54px; height:54px; border-radius:50%; background:var(--surface-3); color:var(--text-3); display:flex; align-items:center; justify-content:center; margin:0 auto 16px; }
.fn-empty-title { font-family:var(--font-serif); font-size:18px; font-weight:600; color:var(--text); margin-bottom:6px; }
.fn-empty-sub { font-size:13px; color:var(--text-4); line-height:1.55; max-width:420px; margin:0 auto; }

/* NEW INVOICE MODAL */
.fn-steps { display:flex; align-items:center; gap:10px; margin-bottom:22px; }
.fn-step-wrap { display:flex; align-items:center; gap:10px; }
.fn-step-wrap:last-child { flex:0; }
.fn-step-wrap:not(:last-child) { flex:1; }
.fn-step { display:inline-flex; align-items:center; gap:8px; font-size:13px; font-weight:600; color:var(--text-4); white-space:nowrap; }
.fn-step.active { color:var(--text); }
.fn-step.done { color:var(--green-dark); }
.fn-stepnum { width:22px; height:22px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-4); font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.fn-step.active .fn-stepnum { background:var(--gold-dark); color:var(--text-inverted); }
.fn-step.done .fn-stepnum { background:var(--green-dark); color:var(--text-inverted); }
.fn-stepline { flex:1; height:1px; background:var(--border); min-width:20px; }
.fn-field { margin-bottom:16px; }
.fn-field:last-child { margin-bottom:0; }
.fn-label { display:block; font-size:12px; font-weight:600; color:var(--text-2); margin-bottom:7px; }
.fn-opt { font-weight:400; color:var(--text-4); }
.fn-input { display:block; width:100%; padding:10px 13px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; transition:border-color .15s ease; }
.fn-input:focus { border-color:var(--gold-dark); }
.fn-input::placeholder { color:var(--text-4); }

/* line items */
.fn-lineitem { display:flex; gap:8px; margin-bottom:8px; }
.fn-lineitem .fn-input { flex:1; }
.fn-li-qty { width:60px; flex:0 0 60px !important; text-align:center; }
.fn-li-rate { width:110px; flex:0 0 110px !important; }
.fn-li-remove { width:38px; flex-shrink:0; border:1px solid var(--border-dark); border-radius:var(--radius); background:var(--surface); color:var(--red); display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all .15s ease; }
.fn-li-remove:hover:not(:disabled) { border-color:var(--red); background:var(--red-light); }
.fn-li-remove:disabled { opacity:.4; cursor:not-allowed; }
.fn-add-line { display:inline-flex; align-items:center; gap:6px; margin-top:4px; padding:7px 12px; font-size:12px; font-weight:600; color:var(--text-3); background:none; border:none; cursor:pointer; transition:color .15s ease; }
.fn-add-line:hover { color:var(--gold-dark); }
.fn-total-bar { display:flex; align-items:center; justify-content:space-between; padding:14px 16px; margin:14px 0 18px; background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius); }
.fn-total-bar span { font-size:11px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); }
.fn-total-bar strong { font-family:var(--font-serif); font-size:18px; font-weight:700; color:var(--text); }

/* review */
.fn-review-lead { font-size:13px; color:var(--text-3); margin-bottom:14px; }
.fn-review-card { border:1px solid rgba(192,154,82,.28); background:var(--badge-bg-gold); border-radius:var(--radius-lg); padding:18px 20px; }
.fn-review-name { font-family:var(--font-serif); font-size:15px; font-weight:700; color:var(--text); }
.fn-review-meta { font-size:12.5px; color:var(--text-3); margin-top:3px; }
.fn-review-lines { margin-top:14px; padding-top:12px; border-top:1px solid rgba(192,154,82,.25); }
.fn-review-line { display:flex; align-items:center; justify-content:space-between; gap:16px; font-size:13px; color:var(--text-2); padding:3px 0; }
.fn-review-total { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-top:10px; padding-top:12px; border-top:1px solid rgba(192,154,82,.25); }
.fn-review-total span { font-size:13px; font-weight:600; color:var(--text); }
.fn-review-total strong { font-family:var(--font-serif); font-size:17px; font-weight:700; color:var(--text); }
.fn-review-notes { font-size:12px; color:var(--text-3); margin-top:14px; padding-top:12px; border-top:1px solid rgba(192,154,82,.25); }

/* modal footer */
.fn-back { display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:600; color:var(--text-3); background:none; border:none; cursor:pointer; padding:6px 4px; transition:color .15s ease; }
.fn-back:hover { color:var(--gold-dark); }
.fn-footer-spacer { flex:1; }

@media (max-width:900px) { .fn-stats { grid-template-columns:1fr; } .fn-header { flex-direction:column; } .fn-field-row { grid-template-columns:1fr; } }
</style>
