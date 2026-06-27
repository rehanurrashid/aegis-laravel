<template>
  <AppLayout :user="user" portal="business_partner" activePage="invoices" pageTitle="Invoices">

    <!-- ═══ HEADER ═══ -->
    <div class="iv-header">
      <div class="iv-header-l">
        <h1 class="iv-title">Invoices</h1>
        <div class="iv-subtitle">Manage billing across your active contracts</div>
      </div>
      <div class="iv-header-actions">
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
        <button type="button" class="btn btn-outline btn-sm" :class="{ 'iv-priv-on': privacy }" @click="privacy = !privacy">
          <svg v-if="privacy" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
          <svg v-else width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          {{ privacy ? 'Show amounts' : 'Privacy' }}
        </button>
        <button type="button" class="btn iv-issue-btn btn-sm" @click="openWizard"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg> Issue Invoice</button>
      </div>
    </div>

    <!-- ═══ STATS ═══ -->
    <div class="iv-stats">
      <div class="iv-stat"><div class="iv-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div><div class="iv-stat-num" :class="{ 'iv-blur': privacy }">$1,750.00</div><div class="iv-stat-label">Outstanding</div></div></div>
      <div class="iv-stat"><div class="iv-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div><div class="iv-stat-num" :class="{ 'iv-blur': privacy }">$3,385.00</div><div class="iv-stat-label">Paid this month</div></div></div>
      <div class="iv-stat danger"><div class="iv-stat-ico red"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div><div><div class="iv-stat-num red">3</div><div class="iv-stat-label">Overdue</div></div></div>
    </div>

    <!-- ═══ PROMO BAND ═══ -->
    <div class="iv-promo">
      <div><div class="iv-promo-title">Ready to bill a practitioner?</div><div class="iv-promo-sub">Issue an invoice tied to a contract milestone, or create one for off-platform work.</div></div>
      <button type="button" class="btn btn-gold btn-sm" @click="openWizard"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> New Invoice</button>
    </div>

    <!-- ═══ TABS ═══ -->
    <div class="iv-tabs">
      <button v-for="t in tabs" :key="t.key" type="button" class="iv-tab" :class="{ active: activeTab === t.key }" @click="activeTab = t.key">{{ t.label }} <span v-if="t.count" class="iv-tab-count">{{ t.count }}</span></button>
    </div>

    <!-- ═══ FILTER BAR ═══ -->
    <div class="iv-filterbar">
      <div class="iv-search">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" placeholder="Search by invoice number," />
      </div>
      <select class="form-select"><option>All contracts</option><option>SimplePractice migration &amp; setup</option><option>EHR Migration &amp; Training</option></select>
      <select class="form-select"><option>All practitioners</option><option>Dr. Sarah Johnson</option><option>Elena Vasquez</option><option>Dr. Anna Thompson</option></select>
      <select class="form-select"><option>All team members</option><option>Myself (owner)</option><option>Riley Chen</option></select>
      <select class="form-select"><option>Newest first</option><option>Oldest first</option><option>Amount: High to Low</option></select>
      <span class="iv-count">{{ filteredInvoices.length }} invoices</span>
    </div>

    <!-- ═══ TABLE ═══ -->
    <div class="iv-table-card">
      <table class="iv-table">
        <thead><tr><th>Invoice</th><th>Practitioner</th><th>Due</th><th>Amount</th><th>Status</th><th class="iv-th-actions">Actions</th></tr></thead>
        <tbody>
          <tr v-for="inv in filteredInvoices" :key="inv.id" :class="['iv-row-' + inv.status, { muted: inv.muted }]">
            <td class="iv-td-num">{{ inv.id }}</td>
            <td><div class="iv-td-pr"><span class="iv-av">{{ inv.initials }}</span><div><div class="iv-pr-name">{{ inv.practitioner }}</div><div class="iv-pr-org">{{ inv.org }}</div></div></div></td>
            <td><span class="iv-due" :class="{ red: inv.status === 'overdue' }">{{ inv.due }}</span></td>
            <td>
              <div class="iv-amt" :class="{ green: inv.status === 'paid', muted: inv.muted, 'iv-blur': privacy }">{{ inv.amount }}</div>
              <div v-if="inv.receive" class="iv-receive" :class="{ 'iv-blur': privacy }">You receive {{ inv.receive }}</div>
            </td>
            <td><span class="iv-pill" :class="'st-' + inv.status">{{ inv.statusLabel }}</span></td>
            <td>
              <div class="iv-actions">
                <button type="button" class="btn-icon" title="View" @click="openDetail(inv)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                <button type="button" class="btn-icon" title="Contract"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></button>
                <!-- draft -->
                <template v-if="inv.status === 'draft'">
                  <button type="button" class="btn-icon" title="Edit"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></button>
                  <button type="button" class="btn-icon" title="Send"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg></button>
                  <button type="button" class="btn-icon" title="Cancel"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                </template>
                <!-- paid -->
                <template v-else-if="inv.status === 'paid'">
                  <button type="button" class="btn-icon" title="Open"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></button>
                  <button v-if="inv.refundable" type="button" class="btn-icon" title="Refund" @click="showRefund = true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg></button>
                </template>
                <!-- overdue -->
                <template v-else-if="inv.status === 'overdue'">
                  <button type="button" class="btn-icon" title="Remind"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></button>
                  <button type="button" class="btn-icon" title="Cancel"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                </template>
                <!-- cancelled / disputed / refunded -->
                <template v-else>
                  <button type="button" class="btn-icon" title="More"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/></svg></button>
                </template>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ═══ ISSUE INVOICE WIZARD ═══ -->
    <Modal v-model="showWizard" title="Issue invoice" size="lg">
      <div class="iv-stepper">
        <div class="iv-step" :class="{ active: step === 1, done: step > 1 }"><span class="iv-step-n"><svg v-if="step > 1" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg><template v-else>1</template></span> Recipient &amp; contract</div>
        <div class="iv-step-line"></div>
        <div class="iv-step" :class="{ active: step === 2, done: step > 2 }"><span class="iv-step-n"><svg v-if="step > 2" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg><template v-else>2</template></span> Services</div>
        <div class="iv-step-line"></div>
        <div class="iv-step" :class="{ active: step === 3 }"><span class="iv-step-n">3</span> Review</div>
      </div>

      <!-- STEP 1 -->
      <div v-if="step === 1" class="iv-wz-body">
        <div class="iv-field"><label class="iv-label">Related Contract <span class="iv-req">*</span></label><select class="form-select" v-model="wz.contract"><option value="">Select a contract</option><option>SimplePractice migration &amp; setup · Dr. Sarah Johnson</option><option>EHR Migration &amp; Training · Elena Vasquez</option><option>Off-platform work</option></select></div>
        <div class="iv-field"><label class="iv-label">Payment Terms <span class="iv-req">*</span></label><select class="form-select" v-model="wz.terms"><option>Due in 30 days</option><option>Due in 14 days</option><option>Due on receipt</option></select></div>
        <div class="iv-field"><label class="iv-label">Notes</label><textarea class="iv-input iv-textarea" rows="3" v-model="wz.notes" placeholder="Payment instructions or any notes for the practitioner"></textarea></div>
        <div class="iv-field"><label class="iv-label">Issued by</label><select class="form-select" v-model="wz.issuedBy"><option>Myself (owner)</option><option>Riley Chen</option><option>Marcus T.</option></select></div>
      </div>

      <!-- STEP 2 -->
      <div v-if="step === 2" class="iv-wz-body">
        <div v-for="(s, i) in wz.services" :key="i" class="iv-svc-row">
          <input class="iv-input iv-svc-name" v-model="s.name" placeholder="Service description" />
          <input class="iv-input iv-svc-qty" v-model.number="s.qty" placeholder="Qty" />
          <input class="iv-input iv-svc-rate" v-model.number="s.rate" placeholder="Rate" />
          <button type="button" class="btn-icon iv-svc-del" @click="removeService(i)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        </div>
        <button type="button" class="iv-add-svc" @click="addService"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add service</button>
        <div class="iv-wz-divider"></div>
        <div class="iv-subtotal-row"><div class="iv-subtotal-label">Subtotal</div><div class="iv-subtotal-val">${{ subtotal.toFixed(2) }}</div></div>
      </div>

      <!-- STEP 3 -->
      <div v-if="step === 3" class="iv-wz-body">
        <div class="iv-review-card">
          <div class="iv-rev-k">Contract</div><div class="iv-rev-v">{{ wz.contract || 'Select a contract' }}</div>
          <div class="iv-rev-k">Due date</div><div class="iv-rev-v">{{ dueDate }}</div>
          <div class="iv-rev-k">Notes</div><div class="iv-rev-v">{{ wz.notes || '—' }}</div>
        </div>
        <div class="iv-modal-section">Services</div>
        <table class="iv-rev-table">
          <thead><tr><th>Service</th><th class="r">Qty</th><th class="r">Rate</th><th class="r">Amount</th></tr></thead>
          <tbody>
            <tr v-for="(s, i) in wz.services" :key="i"><td>{{ s.name || '—' }}</td><td class="r">{{ s.qty }}</td><td class="r">${{ Number(s.rate).toFixed(2) }}</td><td class="r b">${{ (s.qty * s.rate).toFixed(2) }}</td></tr>
          </tbody>
        </table>
        <div class="iv-rev-totals">
          <div class="iv-rev-trow"><span>Subtotal</span><span>${{ subtotal.toFixed(2) }}</span></div>
          <div class="iv-rev-trow"><span>Platform fee (8%)</span><span>${{ fee.toFixed(2) }}</span></div>
          <div class="iv-rev-trow grand"><span>Direct payment to your Stripe account (estimated)</span><span>${{ net.toFixed(2) }}</span></div>
          <div class="iv-rev-tiny">${{ net.toFixed(2) }}</div>
        </div>
      </div>

      <template #footer>
        <button v-if="step > 1" type="button" class="btn btn-ghost btn-sm" @click="step--">Back</button>
        <button type="button" class="btn btn-ghost btn-sm" @click="showWizard = false">Cancel</button>
        <button v-if="step < 3" type="button" class="btn btn-primary btn-sm" @click="step++">Next</button>
        <button v-else type="button" class="btn btn-gold btn-sm" @click="showWizard = false"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Issue invoice</button>
      </template>
    </Modal>

    <!-- ═══ INVOICE DETAIL MODAL ═══ -->
    <Modal v-model="showDetail" title="Invoice detail" size="lg">
      <div class="iv-det-grid">
        <div><div class="iv-det-k">Invoice number</div><div class="iv-det-v gold">{{ det.id }}</div></div>
        <div><div class="iv-det-k">Status</div><div><span class="iv-pill" :class="'st-' + det.status">{{ det.statusLabel }}</span></div></div>
        <div><div class="iv-det-k">Contract</div><div class="iv-det-v">{{ det.contract || '—' }}</div></div>
      </div>
      <div class="iv-det-row"><div class="iv-det-k">Practitioner</div><div class="iv-det-v">{{ det.practitioner }}</div></div>
      <div class="iv-det-grid2">
        <div><div class="iv-det-k">Issued</div><div class="iv-det-v">{{ det.issued }}</div></div>
        <div><div class="iv-det-k">Due</div><div class="iv-det-v">{{ det.dueIso }}</div></div>
      </div>
      <div class="iv-modal-section">Services</div>
      <table class="iv-rev-table">
        <thead><tr><th>Service</th><th class="r">Qty</th><th class="r">Rate</th><th class="r">Amount</th></tr></thead>
        <tbody><tr><td>Service</td><td class="r">1</td><td class="r">{{ det.amount }}</td><td class="r b">{{ det.amount }}</td></tr></tbody>
      </table>
      <div class="iv-rev-totals">
        <div class="iv-rev-trow"><span>Subtotal</span><span>{{ det.amount }}</span></div>
        <div class="iv-rev-trow"><span>Platform fee (8%)</span><span>{{ det.feeAmt }}</span></div>
        <div class="iv-rev-trow grand"><span>Direct payment to your Stripe account</span><span>{{ det.receive }}</span></div>
      </div>
      <div class="iv-modal-section">Notes</div>
      <p class="iv-det-notes">{{ det.notes || '—' }}</p>
      <template #footer>
        <button type="button" class="btn btn-ghost btn-sm" @click="showDetail = false">Close</button>
      </template>
    </Modal>

    <!-- ═══ CONFIRM ACTION (REFUND) ═══ -->
    <Modal v-model="showRefund" title="Confirm Action" size="sm">
      <p class="iv-confirm-text">Issue a refund for this invoice? This action cannot be undone.</p>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showRefund = false">Cancel</button>
        <button type="button" class="btn btn-danger btn-sm" @click="showRefund = false">Confirm</button>
      </template>
    </Modal>

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
import Modal from '../../Components/Modal.vue';
defineProps({ user: Object });

const search = ref('');
const activeTab = ref('all');
const privacy = ref(false);
const showRefund = ref(false);

const tabs = [
  { key: 'all', label: 'All', count: 19 },
  { key: 'draft', label: 'Draft', count: 1 },
  { key: 'sent', label: 'Sent', count: 0 },
  { key: 'viewed', label: 'Viewed', count: 0 },
  { key: 'paid', label: 'Paid', count: 12 },
  { key: 'overdue', label: 'Overdue', count: 3 },
  { key: 'cancelled', label: 'Cancelled', count: 1 },
  { key: 'disputed', label: 'Disputed', count: 1 },
  { key: 'refunded', label: 'Refunded', count: 1 },
];

const SJ = { initials: 'SJ', practitioner: 'Dr. Sarah Johnson', org: 'Lotus Psychology Group' };
const EV = { initials: 'EV', practitioner: 'Elena Vasquez', org: 'Elena Vasquez Practice' };
const AT = { initials: 'AT', practitioner: 'Dr. Anna Thompson', org: 'Dr. Anna Thompson Practice' };

const invoices = [
  { id: 'INV-2026-004', ...SJ, due: 'Jul 10, 2026', dueIso: '2026-07-10', issued: '2026-06-10', amount: '$500.00', status: 'draft', statusLabel: 'DRAFT', notes: '' },
  { id: 'INV-2026-020', ...EV, due: 'Jun 22, 2026', dueIso: '2026-06-22', issued: '2026-06-08', amount: '$700.00', receive: '$644.00', feeAmt: '$56.00', status: 'paid', statusLabel: 'PAID', refundable: true, notes: 'EHR telehealth module setup' },
  { id: 'INV-2026-019', ...SJ, due: 'Jun 15, 2026', dueIso: '2026-06-15', issued: '2026-06-01', amount: '$900.00', receive: '$828.00', feeAmt: '$72.00', status: 'paid', statusLabel: 'PAID', refundable: true, notes: 'SimplePractice migration phase' },
  { id: 'INV-2026-018', ...SJ, due: 'Jun 4, 2026', dueIso: '2026-06-04', issued: '2026-05-21', amount: '$1,785.00', receive: '$1,642.20', feeAmt: '$142.80', status: 'paid', statusLabel: 'PAID', refundable: true, notes: 'Medical billing retainer' },
  { id: 'INV-2026-017', ...EV, due: 'May 27, 2026', dueIso: '2026-05-27', issued: '2026-05-13', amount: '$700.00', receive: '$644.00', feeAmt: '$56.00', status: 'paid', statusLabel: 'PAID', refundable: true, notes: 'EHR migration work' },
  { id: 'INV-2026-016', ...SJ, due: 'May 7, 2026', dueIso: '2026-05-07', issued: '2026-04-23', amount: '$2,125.00', receive: '$1,955.00', feeAmt: '$170.00', status: 'paid', statusLabel: 'PAID', notes: 'Medical billing retainer' },
  { id: 'INV-2026-015', ...EV, due: 'Apr 25, 2026', dueIso: '2026-04-25', issued: '2026-04-11', amount: '$700.00', receive: '$644.00', feeAmt: '$56.00', status: 'paid', statusLabel: 'PAID', notes: 'EHR migration work' },
  { id: 'INV-2026-014', ...AT, due: 'Apr 15, 2026', dueIso: '2026-04-15', issued: '2026-04-01', amount: '$1,900.00', receive: '$1,748.00', feeAmt: '$152.00', status: 'paid', statusLabel: 'PAID', notes: 'Practice website redesign' },
  { id: 'INV-2026-001', ...SJ, due: 'Apr 7, 2026', dueIso: '2026-04-07', issued: '2026-03-24', amount: '$600.00', receive: '$552.00', feeAmt: '$48.00', status: 'paid', statusLabel: 'PAID', notes: 'SimplePractice setup' },
  { id: 'INV-2026-013', ...SJ, due: 'Apr 4, 2026', dueIso: '2026-04-04', issued: '2026-03-21', amount: '$1,870.00', receive: '$1,720.40', feeAmt: '$149.60', status: 'paid', statusLabel: 'PAID', notes: 'Medical billing retainer' },
  { id: 'INV-2026-012', ...AT, due: 'Mar 15, 2026', dueIso: '2026-03-15', issued: '2026-03-01', amount: '$1,900.00', receive: '$1,748.00', feeAmt: '$152.00', status: 'paid', statusLabel: 'PAID', notes: 'Practice website redesign' },
  { id: 'INV-2026-011', ...SJ, due: 'Mar 5, 2026', dueIso: '2026-03-05', issued: '2026-02-19', amount: '$2,040.00', receive: '$1,876.80', feeAmt: '$163.20', status: 'paid', statusLabel: 'PAID', notes: 'Medical billing retainer' },
  { id: 'INV-2026-010', ...SJ, due: 'Feb 4, 2026', dueIso: '2026-02-04', issued: '2026-01-21', amount: '$1,700.00', receive: '$1,564.00', feeAmt: '$136.00', status: 'paid', statusLabel: 'PAID', notes: 'Medical billing retainer' },
  { id: 'INV-2026-005', ...SJ, due: 'Jun 4, 2026', dueIso: '2026-06-04', issued: '2026-05-05', amount: '$350.00', status: 'overdue', statusLabel: 'OVERDUE', notes: 'HIPAA training sessions' },
  { id: 'INV-2026-006', ...SJ, due: 'Apr 30, 2026', dueIso: '2026-04-30', issued: '2026-03-31', amount: '$700.00', status: 'overdue', statusLabel: 'OVERDUE', notes: 'Billing support' },
  { id: 'INV-2026-002', ...SJ, due: 'Apr 27, 2026', dueIso: '2026-04-27', issued: '2026-03-28', amount: '$700.00', status: 'overdue', statusLabel: 'OVERDUE', notes: 'Billing support' },
  { id: 'INV-2026-007', ...SJ, due: 'Mar 15, 2026', dueIso: '2026-03-15', issued: '2026-03-01', amount: '$200.00', status: 'cancelled', statusLabel: 'CANCELLED', muted: true, notes: 'Cancelled by mutual agreement' },
  { id: 'INV-2026-008', ...SJ, due: 'Feb 25, 2026', dueIso: '2026-02-25', issued: '2026-02-11', amount: '$600.00', status: 'disputed', statusLabel: 'DISPUTED', muted: true, notes: 'Under dispute review' },
  { id: 'INV-2026-009', ...SJ, due: 'Jan 19, 2026', dueIso: '2026-01-19', issued: '2026-01-05', amount: '$300.00', status: 'refunded', statusLabel: 'REFUNDED', muted: true, notes: 'Refunded to practitioner' },
];

const filteredInvoices = computed(() => {
  return invoices.filter(inv => {
    if (activeTab.value !== 'all' && inv.status !== activeTab.value) return false;
    if (search.value) {
      const q = search.value.toLowerCase();
      if (!inv.id.toLowerCase().includes(q) && !inv.practitioner.toLowerCase().includes(q)) return false;
    }
    return true;
  });
});

// ── wizard ──
const showWizard = ref(false);
const step = ref(1);
const wz = reactive({ contract: '', terms: 'Due in 30 days', notes: '', issuedBy: 'Myself (owner)', services: [{ name: '', qty: 1, rate: 0 }] });
const dueDate = '2026-07-22';
function openWizard() { step.value = 1; showWizard.value = true; }
function addService() { wz.services.push({ name: '', qty: 1, rate: 0 }); }
function removeService(i) { if (wz.services.length > 1) wz.services.splice(i, 1); }
const subtotal = computed(() => wz.services.reduce((sum, s) => sum + (Number(s.qty) || 0) * (Number(s.rate) || 0), 0));
const fee = computed(() => subtotal.value * 0.08);
const net = computed(() => subtotal.value - fee.value);

// ── detail ──
const showDetail = ref(false);
const det = ref({});
function openDetail(inv) { det.value = inv; showDetail.value = true; }
</script>

<style scoped>
.iv-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:28px 32px; margin-bottom:16px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.iv-title { font-family:var(--font-serif); font-size:32px; font-weight:600; letter-spacing:-0.5px; color:var(--text); margin:0; line-height:1.05; }
.iv-subtitle { font-size:13px; color:var(--text-3); margin-top:8px; }
.iv-header-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; flex-wrap:wrap; }
.iv-issue-btn { background:var(--text-3); border:1px solid var(--text-3); color:var(--text-inverted); }
.iv-issue-btn:hover { background:var(--text-2); }
.iv-priv-on { background:var(--gold-dark); border-color:var(--gold-dark); color:var(--text-inverted); }
.iv-blur { filter:blur(6px); user-select:none; pointer-events:none; }
.iv-confirm-text { font-size:13.5px; color:var(--text-2); line-height:1.6; margin:0; }

.iv-stats { display:grid; grid-template-columns:1.1fr 1.1fr .8fr; gap:14px; margin-bottom:16px; }
.iv-stat { display:flex; align-items:center; gap:14px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:18px 22px; box-shadow:var(--shadow-xs); }
.iv-stat.danger { background:var(--red-light); border-color:color-mix(in srgb, var(--red) 25%, transparent); }
.iv-stat-ico { width:34px; height:34px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.iv-stat-ico.red { background:color-mix(in srgb, var(--red) 15%, transparent); color:var(--red); }
.iv-stat-num { font-family:var(--font-serif); font-size:22px; font-weight:700; color:var(--text); line-height:1; }
.iv-stat-num.red { color:var(--red); }
.iv-stat-label { font-size:11.5px; color:var(--text-3); margin-top:5px; }

.iv-promo { display:flex; align-items:center; justify-content:space-between; gap:16px; background:linear-gradient(90deg, var(--badge-bg-gold), color-mix(in srgb, var(--badge-bg-gold) 40%, var(--surface))); border:1px solid color-mix(in srgb, var(--gold) 30%, transparent); border-radius:var(--radius-lg); padding:18px 24px; margin-bottom:18px; }
.iv-promo-title { font-size:14.5px; font-weight:700; color:var(--text); }
.iv-promo-sub { font-size:12.5px; color:var(--text-3); margin-top:4px; }

.iv-tabs { display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:16px; }
.iv-tab { display:inline-flex; align-items:center; gap:7px; padding:8px 15px; font-size:12.5px; font-weight:600; color:var(--text-3); background:none; border:none; border-radius:var(--radius-full); cursor:pointer; transition:all .15s ease; }
.iv-tab:hover { color:var(--gold-dark); }
.iv-tab.active { background:var(--gold-dark); color:var(--text-inverted); }
.iv-tab-count { display:inline-flex; align-items:center; justify-content:center; min-width:18px; height:18px; padding:0 5px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-3); font-size:10.5px; font-weight:700; }
.iv-tab.active .iv-tab-count { background:rgba(255,255,255,.25); color:var(--text-inverted); }

.iv-filterbar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:16px; }
.iv-search { display:flex; align-items:center; gap:8px; flex:1; min-width:200px; padding:9px 14px; background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius-full); color:var(--text-4); }
.iv-search input { flex:1; border:none; outline:none; background:none; font-size:13px; color:var(--text); }
.iv-filterbar .form-select { width:auto; min-width:130px; border-radius:var(--radius-full); }
.iv-count { font-size:12px; color:var(--text-4); margin-left:auto; flex-shrink:0; }

.iv-table-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; }
.iv-table { width:100%; border-collapse:collapse; }
.iv-table th { text-align:left; font-size:9.5px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:var(--text-4); padding:14px 16px; border-bottom:1px solid var(--border); }
.iv-th-actions { text-align:right; }
.iv-table td { padding:13px 16px; border-bottom:1px solid var(--border); vertical-align:middle; }
.iv-table tbody tr:last-child td { border-bottom:none; }
.iv-table tbody tr:hover { background:var(--surface-2); }
.iv-row-overdue { background:var(--red-light); }
.iv-row-overdue:hover { background:color-mix(in srgb, var(--red) 12%, var(--surface)); }
.iv-row-cancelled, .iv-row-disputed, .iv-row-refunded { opacity:.62; }
.iv-td-num { font-size:11.5px; font-weight:600; color:var(--gold-dark); letter-spacing:.3px; }
.iv-td-pr { display:flex; align-items:center; gap:10px; }
.iv-av { width:30px; height:30px; border-radius:50%; background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; font-size:10.5px; flex-shrink:0; }
.iv-pr-name { font-size:12.5px; font-weight:700; color:var(--text); }
.iv-pr-org { font-size:11px; color:var(--text-4); margin-top:1px; }
.iv-due { font-size:12.5px; color:var(--text-2); }
.iv-due.red { color:var(--red); font-weight:700; }
.iv-amt { font-size:13.5px; font-weight:700; color:var(--text); }
.iv-amt.green { color:var(--green-dark); }
.iv-amt.muted { color:var(--text-4); }
.iv-receive { font-size:11px; color:var(--text-4); margin-top:2px; }
.iv-pill { font-size:9px; font-weight:700; letter-spacing:.6px; padding:4px 10px; border-radius:var(--radius-full); }
.iv-pill.st-draft { background:var(--surface-3); color:var(--text-3); }
.iv-pill.st-paid { background:var(--green-light); color:var(--green-dark); }
.iv-pill.st-overdue { background:transparent; color:var(--red); border:1px solid color-mix(in srgb, var(--red) 30%, transparent); }
.iv-pill.st-cancelled { background:var(--surface-3); color:var(--text-4); }
.iv-pill.st-disputed { background:var(--badge-bg-gold); color:var(--orange-dark, var(--gold-dark)); }
.iv-pill.st-refunded { background:var(--surface-3); color:var(--text-4); }
.iv-actions { display:flex; align-items:center; justify-content:flex-end; gap:5px; }

/* wizard */
.iv-stepper { display:flex; align-items:center; gap:12px; padding-bottom:20px; margin-bottom:20px; border-bottom:1px solid var(--border); }
.iv-step { display:inline-flex; align-items:center; gap:9px; font-size:13px; font-weight:600; color:var(--text-4); }
.iv-step-n { width:24px; height:24px; border-radius:50%; background:var(--surface-3); color:var(--text-3); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; flex-shrink:0; }
.iv-step.active { color:var(--text); }
.iv-step.active .iv-step-n { background:var(--gold-dark); color:var(--text-inverted); }
.iv-step.done { color:var(--green-dark); }
.iv-step.done .iv-step-n { background:var(--green); color:var(--text-inverted); }
.iv-step-line { flex:1; height:1.5px; background:var(--border); }

.iv-wz-body { min-height:120px; }
.iv-field { margin-bottom:16px; }
.iv-field:last-child { margin-bottom:0; }
.iv-label { display:block; font-size:12.5px; font-weight:600; color:var(--text-2); margin-bottom:7px; }
.iv-req { color:var(--red); }
.iv-input { display:block; width:100%; padding:10px 13px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; transition:border-color .15s ease; }
.iv-input:focus { border-color:var(--gold-dark); }
.iv-input::placeholder { color:var(--text-4); }
.iv-textarea { resize:vertical; min-height:80px; line-height:1.55; font-family:var(--font-sans); }

.iv-svc-row { display:flex; align-items:center; gap:10px; margin-bottom:10px; }
.iv-svc-name { flex:1; }
.iv-svc-qty, .iv-svc-rate { width:84px; flex-shrink:0; }
.iv-svc-del { color:var(--red); border-color:color-mix(in srgb, var(--red) 30%, transparent); flex-shrink:0; }
.iv-add-svc { display:inline-flex; align-items:center; gap:6px; font-size:12.5px; font-weight:600; color:var(--gold-dark); background:none; border:none; cursor:pointer; padding:6px 0; margin-top:2px; }
.iv-wz-divider { height:1px; background:var(--border); margin:16px 0; }
.iv-subtotal-row { text-align:right; }
.iv-subtotal-label { font-size:11px; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:var(--text-4); }
.iv-subtotal-val { font-family:var(--font-serif); font-size:24px; font-weight:700; color:var(--text); margin-top:4px; }

.iv-modal-section { font-size:10px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:var(--text-4); margin:20px 0 12px; }
.iv-review-card { display:grid; grid-template-columns:120px 1fr; gap:6px 16px; background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius-lg); padding:18px 20px; }
.iv-rev-k { font-size:9.5px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:var(--text-4); align-self:center; }
.iv-rev-v { font-size:13px; color:var(--text); font-weight:600; }
.iv-rev-table { width:100%; border-collapse:collapse; }
.iv-rev-table th { text-align:left; font-size:9.5px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:var(--text-4); padding:8px 4px; border-bottom:1px solid var(--border); }
.iv-rev-table th.r, .iv-rev-table td.r { text-align:right; }
.iv-rev-table td { font-size:13px; color:var(--text-2); padding:11px 4px; border-bottom:1px solid var(--border); }
.iv-rev-table td.b { font-weight:700; color:var(--text); }
.iv-rev-totals { margin-top:14px; }
.iv-rev-trow { display:flex; justify-content:space-between; font-size:13px; color:var(--text-2); padding:7px 4px; }
.iv-rev-trow.grand { font-size:14px; font-weight:700; color:var(--text); border-top:1px solid var(--border); margin-top:4px; padding-top:12px; }
.iv-rev-tiny { font-size:11px; color:var(--text-4); padding:2px 4px; }

/* detail */
.iv-det-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-bottom:18px; }
.iv-det-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin:16px 0; }
.iv-det-row { margin-bottom:4px; }
.iv-det-k { font-size:9.5px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:var(--text-4); margin-bottom:6px; }
.iv-det-v { font-size:13.5px; color:var(--text); }
.iv-det-v.gold { color:var(--gold-dark); font-weight:700; }
.iv-det-notes { font-size:13px; color:var(--text-2); line-height:1.6; margin:0; }

@media (max-width:1000px) { .iv-stats { grid-template-columns:1fr; } .iv-table { display:block; overflow-x:auto; white-space:nowrap; } .iv-det-grid { grid-template-columns:1fr; } }
@media (max-width:760px) { .iv-header { flex-direction:column; } .iv-promo { flex-direction:column; align-items:flex-start; } }
</style>
