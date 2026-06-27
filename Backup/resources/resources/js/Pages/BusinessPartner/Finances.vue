<template>
  <AppLayout :user="user" portal="business_partner" activePage="finances" pageTitle="Finances">

    <!-- ═══ HEADER ═══ -->
    <div class="fn-header">
      <div class="fn-header-l">
        <h1 class="fn-title">Finances</h1>
        <div class="fn-subtitle">Track earnings, payouts, and platform fees across your contracts</div>
        <div class="fn-tagline"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> Agency Partner</div>
      </div>
      <div class="fn-header-actions">
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
        <button type="button" class="btn btn-primary btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg> Issue Invoice</button>
        <a href="/business-partner/payment-setup" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg> Payment Setup</a>
      </div>
    </div>

    <!-- ═══ STATS ═══ -->
    <div class="fn-stats">
      <div class="fn-stat"><div class="fn-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div><div><div class="fn-stat-num">$15,566</div><div class="fn-stat-label">Net Earnings YTD</div></div></div>
      <div class="fn-stat"><div class="fn-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="fn-stat-num">$1,800</div><div class="fn-stat-label">Pending Payout</div></div></div>
      <div class="fn-stat"><div class="fn-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div><div class="fn-stat-num">$1,354</div><div class="fn-stat-label">Platform Fee YTD</div></div></div>
      <div class="fn-stat"><div class="fn-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div><div><div class="fn-stat-num">12.2 days</div><div class="fn-stat-label">Avg Days to Pay</div></div></div>
    </div>

    <!-- ═══ REVENUE BY MONTH ═══ -->
    <div class="fn-card fn-chart-card">
      <div class="fn-card-head">
        <div><div class="fn-card-title">Revenue by month</div><div class="fn-card-sub">Gross billed from paid invoices, last 6 months</div></div>
        <div class="fn-legend"><span class="fn-leg"><span class="fn-leg-sw cur"></span> Current</span><span class="fn-leg"><span class="fn-leg-sw past"></span> Past</span></div>
      </div>
      <div class="fn-bars">
        <div v-for="b in months" :key="b.m" class="fn-bar-col">
          <div class="fn-bar-val">{{ b.v ? '$' + b.v.toLocaleString() : '' }}</div>
          <div class="fn-bar" :class="{ current: b.current }" :style="{ height: barH(b.v) }"></div>
          <div class="fn-bar-m">{{ b.m }}</div>
        </div>
      </div>
    </div>

    <!-- ═══ EARNINGS BREAKDOWN ═══ -->
    <div class="fn-card">
      <div class="fn-card-title">Earnings breakdown</div>
      <div class="fn-card-sub">Year-to-date gross, platform fee, and net</div>
      <div class="fn-split-bar"><div class="fn-split-net" style="width:92%"></div><div class="fn-split-fee" style="width:8%"></div></div>
      <div class="fn-split-legend"><span class="fn-leg"><span class="fn-leg-sw net"></span> Net to you (92%)</span><span class="fn-leg"><span class="fn-leg-sw fee"></span> Platform fee (8%)</span></div>
      <div class="fn-eb-row"><span class="fn-eb-l"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg> Gross billed</span><span class="fn-eb-v">$16,920</span></div>
      <div class="fn-eb-row"><span class="fn-eb-l"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg> Platform fee (8%)</span><span class="fn-eb-v neg">-$1,354</span></div>
      <div class="fn-eb-row total"><span class="fn-eb-l"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> <strong>Net to you</strong></span><span class="fn-eb-v pos">$15,566</span></div>
    </div>

    <!-- ═══ TOP CONTRACTS + PRACTITIONERS ═══ -->
    <div class="fn-two">
      <div class="fn-card">
        <div class="fn-card-head"><div class="fn-card-title">Top contracts by revenue</div><button type="button" class="btn btn-outline btn-xs"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> View all</button></div>
        <div v-for="c in topContracts" :key="c.name" class="fn-rank">
          <div class="fn-rank-top"><span class="fn-rank-name">{{ c.name }}</span> <span class="fn-rank-sub">· {{ c.who }} · <span class="fn-rank-tag">{{ c.type }}</span></span><span class="fn-rank-amt">{{ c.amount }} · {{ c.pct }}%</span></div>
          <div class="fn-rank-bar"><div class="fn-rank-fill" :class="c.color" :style="{ width: c.pct + '%' }"></div></div>
        </div>
      </div>
      <div class="fn-card">
        <div class="fn-card-head"><div class="fn-card-title">Top practitioners by revenue</div></div>
        <div v-for="p in topPractitioners" :key="p.name" class="fn-rank">
          <div class="fn-rank-top fn-rank-pr"><span class="fn-av sm">{{ p.initials }}</span><span class="fn-rank-name">{{ p.name }}</span> <span class="fn-rank-sub">· {{ p.org }} · {{ p.contracts }}</span><span class="fn-rank-amt">{{ p.amount }} · {{ p.pct }}%</span></div>
          <div class="fn-rank-bar"><div class="fn-rank-fill" :class="p.color" :style="{ width: p.pct + '%' }"></div></div>
        </div>
      </div>
    </div>

    <!-- ═══ ENGAGEMENT TYPE + TAX TOTALS ═══ -->
    <div class="fn-two">
      <div class="fn-card">
        <div class="fn-card-title">Revenue by engagement type</div>
        <div v-for="e in engagement" :key="e.name" class="fn-rank">
          <div class="fn-rank-top"><span class="fn-rank-name2">{{ e.name }}</span><span class="fn-rank-amt">{{ e.amount }} · {{ e.pct }}%</span></div>
          <div class="fn-rank-bar"><div class="fn-rank-fill" :class="e.color" :style="{ width: e.pct + '%' }"></div></div>
        </div>
      </div>
      <div class="fn-card">
        <div class="fn-card-title">Tax year totals</div>
        <div class="fn-tax-year">2026</div>
        <div class="fn-eb-row"><span class="fn-eb-l plain">Gross billed</span><span class="fn-eb-v">$16,920</span></div>
        <div class="fn-eb-row"><span class="fn-eb-l plain">Platform fees</span><span class="fn-eb-v neg">-$1,354</span></div>
        <div class="fn-eb-row total"><span class="fn-eb-l plain"><strong>Net to you</strong></span><span class="fn-eb-v pos">$15,566</span></div>
        <div class="fn-tax-note">Business tax documents are available in your Stripe Dashboard.</div>
      </div>
    </div>

    <!-- ═══ RECENT PAYOUTS ═══ -->
    <div class="fn-card">
      <div class="fn-card-title">Recent payouts</div>
      <div class="fn-card-sub">Stripe deposits to your bank account</div>
      <table class="fn-table">
        <thead><tr><th>Date</th><th>Description</th><th>Amount</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <tr v-for="(p, i) in payouts" :key="i">
            <td>{{ p.date }}</td>
            <td class="fn-td-desc">{{ p.desc }}</td>
            <td class="fn-td-amt">{{ p.amount }}</td>
            <td><span class="fn-pill" :class="p.status === 'PAID' ? 'paid' : 'pending'">{{ p.status }}</span></td>
            <td class="fn-td-link"><button v-if="p.status === 'PAID'" type="button" class="btn-icon"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></button></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ═══ RECENT PAID INVOICES ═══ -->
    <div class="fn-card">
      <div class="fn-card-head"><div><div class="fn-card-title">Recent paid invoices</div><div class="fn-card-sub">Invoices paid by practitioners</div></div><button type="button" class="btn btn-outline btn-xs"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> View all</button></div>
      <table class="fn-table">
        <thead><tr><th>Paid</th><th>Invoice</th><th>Practitioner</th><th>Contract</th><th>Gross</th><th>Fee</th><th>Net</th></tr></thead>
        <tbody>
          <tr v-for="(iv, i) in invoices" :key="i">
            <td>{{ iv.paid }}</td>
            <td class="fn-td-inv">{{ iv.inv }}</td>
            <td><span class="fn-td-pr"><span class="fn-av xs">{{ iv.initials }}</span> {{ iv.practitioner }}</span></td>
            <td class="fn-td-contract">{{ iv.contract }}</td>
            <td class="fn-td-amt">{{ iv.gross }}</td>
            <td class="fn-td-amt neg">{{ iv.fee }}</td>
            <td class="fn-td-amt pos">{{ iv.net }}</td>
          </tr>
        </tbody>
      </table>
    </div>

  </AppLayout>
</template>

<script setup>
import AppLayout from '../../Components/AppLayout.vue';
defineProps({ user: Object });

const months = [
  { m: 'Jan', v: 0, current: false },
  { m: 'Feb', v: 1700, current: false },
  { m: 'Mar', v: 4540, current: false },
  { m: 'Apr', v: 4470, current: false },
  { m: 'May', v: 2825, current: false },
  { m: 'Jun', v: 3385, current: true },
];
const maxV = 4540;
function barH(v) { return v ? Math.max(4, Math.round((v / maxV) * 100)) + '%' : '2%'; }

const topContracts = [
  { name: 'Medical Billing Retainer — 2026', who: 'Dr. Sarah Johnson', type: 'HOURLY', amount: '$9,520', pct: 56, color: 'gold' },
  { name: 'Practice Website Redesign', who: 'Dr. Anna Thompson', type: 'FIXED', amount: '$3,800', pct: 22, color: 'blue' },
  { name: 'EHR Migration & Training', who: 'Elena Vasquez', type: 'MILESTONE', amount: '$2,100', pct: 12, color: 'green' },
  { name: 'SimplePractice migration & setup', who: 'Dr. Sarah Johnson', type: 'MILESTONE', amount: '$1,500', pct: 9, color: 'gold' },
];

const topPractitioners = [
  { initials: 'SJ', name: 'Dr. Sarah Johnson', org: 'Lotus Psychology Group', contracts: '2 contracts', amount: '$11,020', pct: 65, color: 'gold' },
  { initials: 'AT', name: 'Dr. Anna Thompson', org: 'Dr. Anna Thompson Practice', contracts: '1 contract', amount: '$3,800', pct: 22, color: 'blue' },
  { initials: 'EV', name: 'Elena Vasquez', org: 'Elena Vasquez Practice', contracts: '1 contract', amount: '$2,100', pct: 12, color: 'green' },
];

const engagement = [
  { name: 'Hourly', amount: '$9,520', pct: 56, color: 'gold' },
  { name: 'Fixed Price', amount: '$3,800', pct: 22, color: 'blue' },
  { name: 'Milestone', amount: '$3,600', pct: 21, color: 'green' },
];

const payouts = [
  { date: 'Scheduled Jun 20', desc: 'Contract retainer — Virtual Admin (June)', amount: '$1,800', status: 'PENDING' },
  { date: 'May 16, 2026', desc: 'Invoice INV-2026-003 — HIPAA training sessions', amount: '$350', status: 'PAID' },
  { date: 'May 2, 2026', desc: 'Contract retainer — Virtual Admin (contract_003)', amount: '$1,800', status: 'PAID' },
  { date: 'Apr 18, 2026', desc: 'Invoice INV-2026-002 — Medical Billing Phase 2', amount: '$700', status: 'PAID' },
  { date: 'Mar 28, 2026', desc: 'Invoice INV-2026-001 — Medical Billing Phase 1', amount: '$2,500', status: 'PAID' },
];

const invoices = [
  { paid: 'Jun 18', inv: 'INV-2026-020', initials: 'EV', practitioner: 'Elena Vasquez', contract: 'EHR Migration & Training', gross: '$700', fee: '-$56', net: '$644' },
  { paid: 'Jun 12', inv: 'INV-2026-019', initials: 'SJ', practitioner: 'Dr. Sarah Johnson', contract: 'SimplePractice migration & setup', gross: '$900', fee: '-$72', net: '$828' },
  { paid: 'Jun 2', inv: 'INV-2026-018', initials: 'SJ', practitioner: 'Dr. Sarah Johnson', contract: 'Medical Billing Retainer — 2026', gross: '$1,785', fee: '-$143', net: '$1,642' },
  { paid: 'May 25', inv: 'INV-2026-017', initials: 'EV', practitioner: 'Elena Vasquez', contract: 'EHR Migration & Training', gross: '$700', fee: '-$56', net: '$644' },
  { paid: 'May 5', inv: 'INV-2026-016', initials: 'SJ', practitioner: 'Dr. Sarah Johnson', contract: 'Medical Billing Retainer — 2026', gross: '$2,125', fee: '-$170', net: '$1,955' },
  { paid: 'Apr 22', inv: 'INV-2026-015', initials: 'EV', practitioner: 'Elena Vasquez', contract: 'EHR Migration & Training', gross: '$700', fee: '-$56', net: '$644' },
  { paid: 'Apr 14', inv: 'INV-2026-014', initials: 'AT', practitioner: 'Dr. Anna Thompson', contract: 'Practice Website Redesign', gross: '$1,900', fee: '-$152', net: '$1,748' },
  { paid: 'Apr 2', inv: 'INV-2026-013', initials: 'SJ', practitioner: 'Dr. Sarah Johnson', contract: 'Medical Billing Retainer — 2026', gross: '$1,870', fee: '-$150', net: '$1,720' },
  { paid: 'Mar 28', inv: 'INV-2026-001', initials: 'SJ', practitioner: 'Dr. Sarah Johnson', contract: 'SimplePractice migration & setup', gross: '$600', fee: '-$48', net: '$552' },
  { paid: 'Mar 12', inv: 'INV-2026-012', initials: 'AT', practitioner: 'Dr. Anna Thompson', contract: 'Practice Website Redesign', gross: '$1,900', fee: '-$152', net: '$1,748' },
];
</script>

<style scoped>
.fn-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:28px 32px; margin-bottom:16px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.fn-title { font-family:var(--font-serif); font-size:32px; font-weight:600; letter-spacing:-0.5px; color:var(--text); margin:0; line-height:1.05; }
.fn-subtitle { font-size:13px; color:var(--text-3); margin-top:8px; }
.fn-tagline { display:inline-flex; align-items:center; gap:6px; font-size:12px; color:var(--text-4); margin-top:10px; }
.fn-header-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; flex-wrap:wrap; }

.fn-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px; }
.fn-stat { display:flex; align-items:center; gap:13px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px 20px; box-shadow:var(--shadow-xs); }
.fn-stat-ico { width:34px; height:34px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.fn-stat-num { font-family:var(--font-serif); font-size:21px; font-weight:700; color:var(--text); line-height:1; }
.fn-stat-label { font-size:11px; color:var(--text-3); margin-top:5px; }

.fn-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:22px 24px; box-shadow:var(--shadow-xs); margin-bottom:16px; }
.fn-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; margin-bottom:6px; }
.fn-card-title { font-family:var(--font-serif); font-size:16px; font-weight:600; color:var(--text); }
.fn-card-sub { font-size:12px; color:var(--text-4); margin-top:3px; }
.btn-xs { padding:5px 12px; font-size:11px; }

/* legend */
.fn-legend { display:flex; gap:14px; }
.fn-leg { display:inline-flex; align-items:center; gap:6px; font-size:11px; color:var(--text-3); }
.fn-leg-sw { width:11px; height:11px; border-radius:3px; }
.fn-leg-sw.cur { background:var(--gold-dark); }
.fn-leg-sw.past { background:var(--surface-3); }
.fn-leg-sw.net { background:var(--green); }
.fn-leg-sw.fee { background:#f0c8c8; }

/* bar chart */
.fn-bars { display:flex; align-items:flex-end; gap:18px; height:230px; margin-top:18px; padding-top:24px; }
.fn-bar-col { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; height:100%; }
.fn-bar-val { font-size:11px; font-weight:600; color:var(--text-3); margin-bottom:7px; }
.fn-bar { width:100%; max-width:130px; background:var(--surface-3); border-radius:6px 6px 0 0; transition:height .4s ease; }
.fn-bar.current { background:var(--gold-dark); }
.fn-bar-m { font-size:11px; color:var(--text-4); margin-top:9px; }

/* earnings breakdown */
.fn-split-bar { display:flex; height:11px; border-radius:var(--radius-full); overflow:hidden; margin:18px 0 12px; background:var(--surface-3); }
.fn-split-net { background:var(--green); }
.fn-split-fee { background:#f0c8c8; }
.fn-split-legend { display:flex; gap:18px; margin-bottom:14px; }
.fn-eb-row { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:11px 0; border-top:1px solid var(--border); }
.fn-eb-l { display:inline-flex; align-items:center; gap:9px; font-size:13px; color:var(--text-2); }
.fn-eb-l svg { color:var(--text-4); }
.fn-eb-l.plain { color:var(--text-3); }
.fn-eb-row.total .fn-eb-l svg { color:var(--green); }
.fn-eb-v { font-size:14px; font-weight:700; color:var(--text); font-family:var(--font-serif); }
.fn-eb-v.neg { color:var(--red); }
.fn-eb-v.pos { color:var(--green-dark); }

/* two col */
.fn-two { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.fn-two .fn-card { margin-bottom:16px; }

/* rank lists */
.fn-rank { margin-top:16px; }
.fn-rank:first-of-type { margin-top:14px; }
.fn-rank-top { display:flex; align-items:center; gap:6px; font-size:12.5px; margin-bottom:7px; }
.fn-rank-pr { gap:8px; }
.fn-rank-name { font-weight:700; color:var(--text); }
.fn-rank-name2 { font-weight:600; color:var(--text); }
.fn-rank-sub { color:var(--text-4); font-size:11.5px; flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.fn-rank-tag { font-size:9px; font-weight:700; letter-spacing:.5px; color:var(--text-3); }
.fn-rank-amt { margin-left:auto; font-weight:700; color:var(--text); font-size:12.5px; flex-shrink:0; }
.fn-rank-bar { height:6px; background:var(--surface-3); border-radius:var(--radius-full); overflow:hidden; }
.fn-rank-fill { height:100%; border-radius:var(--radius-full); }
.fn-rank-fill.gold { background:var(--gold-dark); }
.fn-rank-fill.blue { background:#3b6fb5; }
.fn-rank-fill.green { background:var(--green); }

.fn-av { border-radius:50%; background:var(--gold-dark); color:var(--text-inverted); display:inline-flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; flex-shrink:0; }
.fn-av.sm { width:28px; height:28px; font-size:10px; }
.fn-av.xs { width:22px; height:22px; font-size:8.5px; }

/* tax */
.fn-tax-year { font-size:13px; font-weight:700; color:var(--text); margin:16px 0 6px; }
.fn-tax-note { font-size:11.5px; color:var(--text-4); margin-top:12px; line-height:1.5; }

/* tables */
.fn-table { width:100%; border-collapse:collapse; margin-top:14px; }
.fn-table th { text-align:left; font-size:9.5px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:var(--text-4); padding:0 12px 11px; border-bottom:1px solid var(--border); }
.fn-table td { font-size:12.5px; color:var(--text-2); padding:13px 12px; border-bottom:1px solid var(--border); }
.fn-table tbody tr:last-child td { border-bottom:none; }
.fn-table tbody tr:hover { background:var(--surface-2); }
.fn-td-desc { color:var(--text); }
.fn-td-amt { font-weight:700; color:var(--text); }
.fn-td-amt.neg { color:var(--red); }
.fn-td-amt.pos { color:var(--green-dark); }
.fn-td-inv { font-weight:600; color:var(--text); }
.fn-td-contract { color:var(--text-3); }
.fn-td-pr { display:inline-flex; align-items:center; gap:8px; color:var(--text); font-weight:600; }
.fn-td-link { text-align:right; width:40px; }
.fn-pill { font-size:9.5px; font-weight:700; letter-spacing:.6px; padding:3px 10px; border-radius:var(--radius-full); }
.fn-pill.paid { background:var(--green-light); color:var(--green-dark); }
.fn-pill.pending { background:var(--badge-bg-gold); color:var(--gold-dark); }

@media (max-width:1000px) { .fn-stats { grid-template-columns:repeat(2,1fr); } .fn-two { grid-template-columns:1fr; } }
@media (max-width:760px) { .fn-header { flex-direction:column; } .fn-stats { grid-template-columns:1fr; } .fn-table { display:block; overflow-x:auto; white-space:nowrap; } }
</style>
