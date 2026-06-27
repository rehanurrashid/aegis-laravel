<template>
  <AppLayout :user="user" portal="business_partner" activePage="payment-setup" pageTitle="Payment Setup">

    <!-- ═══ HEADER ═══ -->
    <div class="ps-header">
      <div class="ps-header-l">
        <h1 class="ps-title">Payment Setup</h1>
        <div class="ps-subtitle">Your account is ready to receive payouts</div>
      </div>
      <div class="ps-header-actions">
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
        <a href="/business-partner/invoices" class="btn btn-primary btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> View Invoices</a>
      </div>
    </div>

    <!-- ═══ STATS ═══ -->
    <div class="ps-stats">
      <div class="ps-stat"><div class="ps-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div><div class="ps-stat-num">Connected</div><div class="ps-stat-label">Connection Status</div></div></div>
      <div class="ps-stat"><div class="ps-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div><div class="ps-stat-num">$1,800</div><div class="ps-stat-label">Pending Payouts</div></div></div>
      <div class="ps-stat"><div class="ps-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div><div><div class="ps-stat-num">Jun 20</div><div class="ps-stat-label">Next Payout</div></div></div>
      <div class="ps-stat"><div class="ps-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div><div><div class="ps-stat-num">$5,350</div><div class="ps-stat-label">Lifetime Earnings</div></div></div>
    </div>

    <!-- ═══ STRIPE CONNECT ═══ -->
    <div class="ps-stripe-card">
      <div class="ps-stripe-top">
        <div class="ps-stripe-l">
          <div class="ps-stripe-eyebrow">Stripe Connect</div>
          <div class="ps-stripe-title"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg> Connected and ready</div>
        </div>
        <span class="ps-connected-badge">Connected</span>
      </div>
      <div class="ps-stripe-checks">
        <span class="ps-check"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Payouts</span>
        <span class="ps-check"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Card payments</span>
        <span class="ps-check"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Transfers</span>
      </div>
      <p class="ps-stripe-note">Stripe deposits funds directly to your bank account. Aegis never holds your money.</p>
      <div class="ps-stripe-actions">
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg> Manage on Stripe</button>
        <button type="button" class="btn btn-danger btn-sm">Disconnect</button>
      </div>
    </div>

    <!-- ═══ TAX INFORMATION ═══ -->
    <div class="ps-card">
      <div class="ps-card-title">Tax Information <span class="ps-card-title-light">(Business Entity)</span></div>
      <div class="ps-card-sub">Stripe needs your tax information to issue payouts.</div>

      <div class="ps-warn-band"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Complete your tax information to receive payouts.</div>

      <div class="ps-tax-row"><div class="ps-tax-k">Company Legal Name</div><div class="ps-tax-v">Acme Practice Services</div><a href="/business-partner/profile" class="ps-tax-link">Edit on Profile</a></div>
      <div class="ps-tax-row"><div class="ps-tax-k">EIN</div><div class="ps-tax-v mono muted">Not provided</div><button type="button" class="btn btn-outline btn-xs" @click="showEIN = true">Update</button></div>
      <div class="ps-tax-row"><div class="ps-tax-k">Entity Type</div><div class="ps-tax-v muted">Not set</div><button type="button" class="btn btn-outline btn-xs" @click="showEntity = true">Update</button></div>
      <div class="ps-tax-row"><div class="ps-tax-k">Mailing Address</div><div class="ps-tax-v muted">Not provided</div><button type="button" class="btn btn-outline btn-xs" @click="showAddress = true">Edit</button></div>
      <div class="ps-tax-row"><div class="ps-tax-k">W-9 Status</div><div class="ps-tax-v"><span class="ps-w9-badge">Not Submitted</span></div><button type="button" class="btn btn-outline btn-xs" @click="showW9 = true">Submit W-9</button></div>

      <div class="ps-info-band"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg> Business tax documents are available in your <a href="#" class="ps-inline-link">Stripe Dashboard</a>.</div>
      <button type="button" class="ps-why" @click="showWhy = !showWhy"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg> Why this matters</button>
      <p v-if="showWhy" class="ps-why-text">Tax information is required by Stripe and the IRS before payouts can be released. Your EIN, entity type, and W-9 verify your business identity for 1099 reporting. Aegis only stores the last four digits of your EIN — the full number is sent securely to Stripe.</p>
    </div>

    <!-- ═══ SECURITY ═══ -->
    <div class="ps-card">
      <div class="ps-sec-head">
        <span class="ps-sec-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
        <div><div class="ps-card-title">Security</div><div class="ps-card-sub">Manage access and account protection</div></div>
      </div>

      <div class="ps-2fa-row">
        <span class="ps-2fa-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
        <div class="ps-2fa-info"><div class="ps-2fa-title">Two-factor authentication</div><div class="ps-2fa-sub">Not enabled — we recommend enabling 2FA for your account</div></div>
        <span class="ps-disabled-badge">Disabled</span>
        <button type="button" class="btn btn-outline btn-sm">Enable 2FA</button>
      </div>

      <div class="ps-section-eyebrow">Recent Payout Activity</div>
      <div class="ps-payout-list">
        <div v-for="(p, i) in payouts" :key="i" class="ps-payout-row">
          <span class="ps-payout-date">{{ p.date }}</span>
          <span class="ps-payout-amt">{{ p.amount }}</span>
          <span class="ps-payout-desc">{{ p.desc }}</span>
          <span class="ps-pill" :class="p.status === 'PAID' ? 'paid' : 'pending'">{{ p.status }}</span>
          <button type="button" class="btn-icon"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></button>
        </div>
      </div>

      <div class="ps-section-eyebrow">Active Sessions</div>
      <div class="ps-no-sessions">No active sessions found.</div>

      <button type="button" class="btn btn-outline btn-sm ps-download"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Download account data</button>
    </div>

    <!-- ═══ UPDATE EIN MODAL ═══ -->
    <Modal v-model="showEIN" title="Update EIN" size="sm">
      <div class="ps-modal-info"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg> Only the last four digits are stored. Your full EIN is sent directly to Stripe.</div>
      <div class="ps-field"><label class="ps-label">EIN <span class="ps-req">*</span></label><input class="ps-input mono" v-model="ein.value" placeholder="XX–XXXXXXX" /></div>
      <div class="ps-field"><label class="ps-label">Confirm EIN <span class="ps-req">*</span></label><input class="ps-input mono" v-model="ein.confirm" placeholder="Re-enter" /></div>
      <template #footer>
        <button type="button" class="btn btn-ghost btn-sm" @click="showEIN = false">Cancel</button>
        <button type="button" class="btn btn-gold btn-sm" @click="showEIN = false">Update</button>
      </template>
    </Modal>

    <!-- ═══ UPDATE ENTITY TYPE MODAL ═══ -->
    <Modal v-model="showEntity" title="Update Entity Type" size="sm">
      <div class="ps-field"><label class="ps-label">Entity Type <span class="ps-req">*</span></label>
        <select class="form-select" v-model="entityType"><option value="">Select entity type</option><option>Sole Proprietor</option><option>Single-Member LLC</option><option>Multi-Member LLC</option><option>S-Corporation</option><option>C-Corporation</option><option>Partnership</option><option>Non-profit</option></select>
      </div>
      <template #footer>
        <button type="button" class="btn btn-ghost btn-sm" @click="showEntity = false">Cancel</button>
        <button type="button" class="btn btn-gold btn-sm" @click="showEntity = false">Update</button>
      </template>
    </Modal>

    <!-- ═══ UPDATE MAILING ADDRESS MODAL ═══ -->
    <Modal v-model="showAddress" title="Update Mailing Address" size="lg">
      <div class="ps-field"><label class="ps-label">Street address <span class="ps-req">*</span></label><input class="ps-input" v-model="addr.street" placeholder="123 Main Street" /></div>
      <div class="ps-grid2">
        <div class="ps-field"><label class="ps-label">City <span class="ps-req">*</span></label><input class="ps-input" v-model="addr.city" placeholder="City" /></div>
        <div class="ps-field"><label class="ps-label">State <span class="ps-req">*</span></label><input class="ps-input" v-model="addr.state" placeholder="NY" /></div>
      </div>
      <div class="ps-grid2">
        <div class="ps-field"><label class="ps-label">Postal code <span class="ps-req">*</span></label><input class="ps-input" v-model="addr.zip" placeholder="10001" /></div>
        <div class="ps-field"><label class="ps-label">Country</label><input class="ps-input" v-model="addr.country" placeholder="US" /></div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-ghost btn-sm" @click="showAddress = false">Cancel</button>
        <button type="button" class="btn btn-gold btn-sm" @click="showAddress = false">Save</button>
      </template>
    </Modal>

    <!-- ═══ SUBMIT W-9 MODAL ═══ -->
    <Modal v-model="showW9" title="Submit W-9" size="sm">
      <p class="ps-w9-text">Upload a completed and signed W-9 form. PDF only, 10 MB maximum. The IRS W-9 form is available at <a href="#" class="ps-inline-link">irs.gov</a>.</p>
      <div class="ps-field"><label class="ps-label">W-9 File (PDF) <span class="ps-req">*</span></label>
        <div class="ps-upload"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg><div class="ps-upload-title">Drag &amp; drop or click to browse</div><div class="ps-upload-hint">PDF only · Max 10 MB</div></div>
      </div>
      <label class="ps-checkbox"><input type="checkbox" v-model="w9confirm" /> <span>I confirm that the information on this W-9 is accurate and matches my identity or business entity.</span></label>
      <template #footer>
        <button type="button" class="btn btn-ghost btn-sm" @click="showW9 = false">Cancel</button>
        <button type="button" class="btn btn-gold btn-sm" @click="showW9 = false"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Submit W-9</button>
      </template>
    </Modal>

  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
import Modal from '../../Components/Modal.vue';
defineProps({ user: Object });

const showWhy = ref(false);
const showEIN = ref(false);
const showEntity = ref(false);
const showAddress = ref(false);
const showW9 = ref(false);

const ein = reactive({ value: '', confirm: '' });
const entityType = ref('');
const addr = reactive({ street: '', city: '', state: '', zip: '', country: 'US' });
const w9confirm = ref(false);

const payouts = [
  { date: 'May 16, 2026', amount: '$350.00', desc: 'Invoice INV-2026-003 — HIPAA training sessions', status: 'PAID' },
  { date: 'May 2, 2026', amount: '$1,800.00', desc: 'Contract retainer — Virtual Admin (contract_003)', status: 'PAID' },
  { date: 'Apr 18, 2026', amount: '$700.00', desc: 'Invoice INV-2026-002 — Medical Billing Phase 2', status: 'PAID' },
  { date: 'Mar 28, 2026', amount: '$2,500.00', desc: 'Invoice INV-2026-001 — Medical Billing Phase 1', status: 'PAID' },
  { date: 'Due Jun 20', amount: '$1,800.00', desc: 'Contract retainer — Virtual Admin (June)', status: 'PENDING' },
];
</script>

<style scoped>
.ps-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:28px 32px; margin-bottom:16px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.ps-title { font-family:var(--font-serif); font-size:32px; font-weight:600; letter-spacing:-0.5px; color:var(--text); margin:0; line-height:1.05; }
.ps-subtitle { font-size:13px; color:var(--text-3); margin-top:8px; }
.ps-header-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; }

.ps-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px; }
.ps-stat { display:flex; align-items:center; gap:13px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px 20px; box-shadow:var(--shadow-xs); }
.ps-stat-ico { width:34px; height:34px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.ps-stat-num { font-family:var(--font-serif); font-size:20px; font-weight:700; color:var(--text); line-height:1; }
.ps-stat-label { font-size:11px; color:var(--text-3); margin-top:5px; }

/* stripe card */
.ps-stripe-card { background:var(--green-light); border:1px solid color-mix(in srgb, var(--green) 28%, transparent); border-radius:var(--radius-lg); padding:24px 28px; margin-bottom:16px; }
.ps-stripe-top { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; }
.ps-stripe-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:var(--text-4); }
.ps-stripe-title { display:flex; align-items:center; gap:10px; font-family:var(--font-serif); font-size:19px; font-weight:600; color:var(--text); margin-top:6px; }
.ps-stripe-title svg { color:var(--green-dark); }
.ps-connected-badge { font-size:10px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:var(--green-dark); flex-shrink:0; }
.ps-stripe-checks { display:flex; gap:22px; flex-wrap:wrap; margin-top:16px; }
.ps-check { display:inline-flex; align-items:center; gap:7px; font-size:13px; color:var(--green-dark); }
.ps-stripe-note { font-size:12.5px; color:var(--text-2); margin:16px 0; line-height:1.5; }
.ps-stripe-actions { display:flex; align-items:center; gap:10px; }

/* generic card */
.ps-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:24px 28px; box-shadow:var(--shadow-xs); margin-bottom:16px; }
.ps-card-title { font-family:var(--font-serif); font-size:18px; font-weight:600; color:var(--text); }
.ps-card-title-light { color:var(--text-3); font-weight:400; }
.ps-card-sub { font-size:12.5px; color:var(--text-3); margin-top:5px; }

.ps-warn-band { display:flex; align-items:center; gap:10px; font-size:13px; color:var(--orange-dark, var(--gold-deep)); background:var(--badge-bg-gold); border-radius:var(--radius); padding:13px 16px; margin:18px 0; }

.ps-tax-row { display:flex; align-items:center; gap:18px; padding:16px 0; border-top:1px solid var(--border); }
.ps-tax-k { width:180px; flex-shrink:0; font-size:12.5px; color:var(--text-3); }
.ps-tax-v { flex:1; font-size:13.5px; font-weight:600; color:var(--text); }
.ps-tax-v.mono { font-family:ui-monospace,monospace; }
.ps-tax-v.muted { color:var(--text-4); font-weight:500; }
.ps-tax-link { font-size:12.5px; font-weight:600; color:var(--gold-dark); text-decoration:none; flex-shrink:0; }
.ps-w9-badge { display:inline-block; font-size:9.5px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; padding:4px 11px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-3); }
.btn-xs { padding:6px 14px; font-size:11.5px; }

.ps-info-band { display:flex; align-items:center; gap:9px; font-size:12.5px; color:var(--text-3); background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius); padding:12px 16px; margin-top:18px; }
.ps-info-band svg { color:var(--gold-dark); flex-shrink:0; }
.ps-inline-link { color:var(--gold-dark); font-weight:600; text-decoration:none; }
.ps-why { display:flex; align-items:center; gap:9px; width:100%; text-align:left; font-size:12.5px; color:var(--text-3); background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:12px 16px; margin-top:10px; cursor:pointer; }
.ps-why svg { color:var(--text-4); }
.ps-why-text { font-size:12.5px; color:var(--text-3); line-height:1.6; margin:10px 2px 0; }

/* security */
.ps-sec-head { display:flex; align-items:flex-start; gap:13px; margin-bottom:18px; }
.ps-sec-ico { width:36px; height:36px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.ps-2fa-row { display:flex; align-items:center; gap:14px; padding:16px 0; border-top:1px solid var(--border); }
.ps-2fa-ico { color:var(--text-3); flex-shrink:0; }
.ps-2fa-info { flex:1; min-width:0; }
.ps-2fa-title { font-size:13.5px; font-weight:700; color:var(--text); }
.ps-2fa-sub { font-size:12px; color:var(--text-3); margin-top:2px; }
.ps-disabled-badge { font-size:9.5px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; padding:4px 11px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-3); flex-shrink:0; }

.ps-section-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:var(--text-4); margin:22px 0 12px; }
.ps-payout-list { display:flex; flex-direction:column; }
.ps-payout-row { display:flex; align-items:center; gap:16px; padding:13px 0; border-top:1px solid var(--border); }
.ps-payout-date { width:90px; flex-shrink:0; font-size:12px; color:var(--text-3); }
.ps-payout-amt { width:90px; flex-shrink:0; font-size:13px; font-weight:700; color:var(--text); font-family:ui-monospace,monospace; }
.ps-payout-desc { flex:1; min-width:0; font-size:12.5px; color:var(--gold-dark); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.ps-pill { font-size:9px; font-weight:700; letter-spacing:.6px; padding:4px 10px; border-radius:var(--radius-full); flex-shrink:0; }
.ps-pill.paid { background:var(--green-light); color:var(--green-dark); }
.ps-pill.pending { background:var(--badge-bg-gold); color:var(--gold-dark); }
.ps-no-sessions { font-size:13px; color:var(--text-4); padding:4px 0; }
.ps-download { margin-top:22px; }

/* modal fields */
.ps-modal-info { display:flex; align-items:flex-start; gap:9px; font-size:12.5px; color:#3b6fb5; background:#e3edf9; border-radius:var(--radius); padding:12px 14px; margin-bottom:18px; line-height:1.45; }
.ps-modal-info svg { flex-shrink:0; margin-top:1px; }
.ps-field { margin-bottom:16px; }
.ps-field:last-of-type { margin-bottom:0; }
.ps-label { display:block; font-size:12.5px; font-weight:600; color:var(--text-2); margin-bottom:7px; }
.ps-req { color:var(--red); }
.ps-input { display:block; width:100%; padding:10px 13px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; transition:border-color .15s ease; }
.ps-input:focus { border-color:var(--gold-dark); }
.ps-input::placeholder { color:var(--text-4); }
.ps-input.mono { font-family:ui-monospace,monospace; letter-spacing:.5px; }
.ps-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.ps-w9-text { font-size:13px; color:var(--text-2); line-height:1.55; margin:0 0 16px; }
.ps-upload { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; padding:26px; border:1.5px dashed var(--border-dark); border-radius:var(--radius-lg); background:var(--surface-2); color:var(--text-3); cursor:pointer; transition:border-color .15s ease; }
.ps-upload:hover { border-color:var(--gold-dark); }
.ps-upload-title { font-size:13.5px; font-weight:700; color:var(--text); }
.ps-upload-hint { font-size:11px; color:var(--text-4); }
.ps-checkbox { display:flex; align-items:flex-start; gap:9px; font-size:12.5px; color:var(--text-2); line-height:1.5; margin-top:16px; cursor:pointer; }
.ps-checkbox input { margin-top:2px; flex-shrink:0; accent-color:var(--gold-dark); }

@media (max-width:1000px) { .ps-stats { grid-template-columns:repeat(2,1fr); } }
@media (max-width:760px) { .ps-header { flex-direction:column; } .ps-stats { grid-template-columns:1fr; } .ps-tax-row { flex-wrap:wrap; gap:8px; } .ps-tax-k { width:100%; } .ps-grid2 { grid-template-columns:1fr; } .ps-payout-row { flex-wrap:wrap; } .ps-payout-desc { width:100%; order:5; white-space:normal; } }
</style>
