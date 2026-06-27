<template>
  <AppLayout :user="user" portal="business_partner" activePage="contracts" pageTitle="Contracts">

    <!-- ═══ HEADER ═══ -->
    <div class="ct-header">
      <div class="ct-header-l">
        <h1 class="ct-title">Contracts</h1>
        <div class="ct-subtitle">Active engagements with the practitioners you serve</div>
      </div>
      <div class="ct-header-actions">
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
        <a href="/business-partner/find-jobs" class="btn btn-primary btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg> Find Jobs</a>
      </div>
    </div>

    <!-- ═══ STATS ═══ -->
    <div class="ct-stats">
      <div class="ct-stat"><div class="ct-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div><div class="ct-stat-num">3</div><div class="ct-stat-label">Active</div></div></div>
      <div class="ct-stat"><div class="ct-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></div><div><div class="ct-stat-num">0</div><div class="ct-stat-label">Pending Signature</div></div></div>
      <div class="ct-stat"><div class="ct-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div><div class="ct-stat-num">0</div><div class="ct-stat-label">Completed This Month</div></div></div>
      <div class="ct-stat"><div class="ct-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div><div class="ct-stat-num">$7,700</div><div class="ct-stat-label">Total Contract Value</div></div></div>
    </div>

    <!-- ═══ TABS ═══ -->
    <div class="ct-tabs">
      <button v-for="t in tabs" :key="t.key" type="button" class="ct-tab" :class="{ active: activeTab === t.key }" @click="activeTab = t.key">{{ t.label }} <span v-if="t.count" class="ct-tab-count">{{ t.count }}</span></button>
    </div>

    <!-- ═══ FILTER BAR ═══ -->
    <div class="ct-filterbar">
      <div class="ct-search">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" placeholder="Search by contract title or practitioner..." />
      </div>
      <select class="form-select"><option>All Practitioners</option><option>Dr. Sarah Johnson</option><option>Elena Vasquez</option></select>
      <select class="form-select"><option>All Engagements</option><option>Per-Deliverable / Milestone</option><option>Hourly</option></select>
      <select class="form-select"><option>Newest First</option><option>Oldest First</option><option>Value: High to Low</option></select>
    </div>

    <!-- ═══ CONTRACT GRID ═══ -->
    <div class="ct-grid">
      <div v-for="c in filteredContracts" :key="c.id" class="ct-card" :class="'rail-' + statusColor(c.status)">
        <div class="ct-card-body">
          <div class="ct-card-top">
            <span class="ct-av">{{ c.initials }}</span>
            <div class="ct-card-headinfo">
              <div class="ct-card-title">{{ c.title }}</div>
              <div class="ct-card-who">{{ c.practitioner }} · {{ c.org }}</div>
            </div>
            <div v-if="c.hourly" class="ct-card-amt-tr"><span class="ct-amt">{{ c.amount }}</span><span class="ct-amt-unit">{{ c.amountUnit }}</span></div>
          </div>

          <div class="ct-meta-row">
            <span class="ct-badge" :class="'st-' + statusColor(c.status)">{{ c.statusLabel }}</span>
            <span class="ct-meta-item"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg> {{ c.type }}</span>
            <span class="ct-meta-item"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> {{ c.dated }}</span>
          </div>

          <template v-if="!c.hourly">
            <div class="ct-amt-block">{{ c.amount }}</div>
            <div class="ct-billing">{{ c.billing }}</div>
          </template>

          <div v-if="c.milestones" class="ct-milestones">
            <div class="ct-ms-label">Milestones</div>
            <div class="ct-ms-row">
              <span class="ct-ms-dots"><span v-for="n in c.milestones.total" :key="n" class="ct-ms-dot" :class="{ done: n <= c.milestones.done }"></span></span>
              <span class="ct-ms-text"><strong>{{ c.milestones.done }}/{{ c.milestones.total }} complete</strong> · next: {{ c.milestones.next }} <span class="ct-ms-due">due {{ c.milestones.due }}</span></span>
            </div>
            <div class="ct-ms-bar"><div class="ct-ms-fill" :style="{ width: c.milestones.pct + '%' }"></div></div>
          </div>
        </div>

        <div class="ct-card-foot">
          <div class="ct-foot-dates">
            <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Started {{ c.started }}</span>
            <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Ends {{ c.ends }}</span>
          </div>
          <div class="ct-foot-actions">
            <button type="button" class="btn-icon" title="Contract"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></button>
            <button type="button" class="btn-icon" title="Documents"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></button>
            <button type="button" class="btn-icon" title="Message"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></button>
            <button type="button" class="btn-icon" title="Submit deliverable"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></button>
            <button type="button" class="btn-icon" title="Renew"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg></button>
          </div>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
defineProps({ user: Object });

const search = ref('');
const activeTab = ref('active');

const tabs = [
  { key: 'active', label: 'Active', count: 3 },
  { key: 'pending', label: 'Pending Signature', count: 0 },
  { key: 'paused', label: 'Paused', count: 0 },
  { key: 'completed', label: 'Completed', count: 1 },
  { key: 'cancelled', label: 'Cancelled', count: 0 },
  { key: 'all', label: 'All', count: 4 },
];

function statusColor(s) { return s === 'active' ? 'green' : s === 'completed' ? 'gold' : s === 'cancelled' ? 'red' : 'neutral'; }

const contracts = [
  { id: 1, initials: 'SJ', title: 'SimplePractice migration & setup', practitioner: 'Dr. Sarah Johnson', org: 'Lotus Psychology Group', status: 'active', statusLabel: 'Active', type: 'Per-Deliverable / Milestone', dated: 'Mar 10, 2026', amount: '$1,800', billing: 'milestone billing', milestones: { done: 2, total: 3, next: 'Phase 3: Migration, training, & launch', due: 'May 10', pct: 66 }, started: 'Mar 10, 2026', ends: 'May 10, 2026' },
  { id: 2, initials: 'EV', title: 'EHR Migration & Training', practitioner: 'Elena Vasquez', org: 'Elena Vasquez Practice', status: 'active', statusLabel: 'Active', type: 'Per-Deliverable / Milestone', dated: 'Mar 1, 2026', amount: '$2,100', billing: 'milestone billing', started: 'Mar 1, 2026', ends: 'Jun 30, 2026' },
  { id: 3, initials: 'SJ', title: 'Medical Billing Retainer — 2026', practitioner: 'Dr. Sarah Johnson', org: 'Lotus Psychology Group', status: 'active', statusLabel: 'Active', type: 'Hourly', dated: 'Jan 15, 2026', amount: '$0', amountUnit: 'per hour', hourly: true, started: 'Jan 15, 2026', ends: 'Dec 31, 2026' },
  { id: 4, initials: 'AT', title: 'Practice Website Redesign & SEO', practitioner: 'Dr. Anna Thompson', org: 'Anna Thompson Practice', status: 'completed', statusLabel: 'Completed', type: 'Per-Deliverable / Milestone', dated: 'Nov 2, 2025', amount: '$1,800', billing: 'milestone billing', started: 'Nov 2, 2025', ends: 'Feb 18, 2026' },
];

const filteredContracts = computed(() => {
  return contracts.filter(c => {
    if (activeTab.value !== 'all' && c.status !== activeTab.value) return false;
    if (search.value) {
      const q = search.value.toLowerCase();
      if (!c.title.toLowerCase().includes(q) && !c.practitioner.toLowerCase().includes(q)) return false;
    }
    return true;
  });
});
</script>

<style scoped>
.ct-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:30px 34px; margin-bottom:18px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.ct-title { font-family:var(--font-serif); font-size:34px; font-weight:600; letter-spacing:-0.5px; color:var(--text); margin:0; line-height:1.05; }
.ct-subtitle { font-size:13.5px; color:var(--text-3); margin-top:9px; }
.ct-header-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; }

.ct-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:20px; }
.ct-stat { display:flex; align-items:center; gap:14px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:18px 22px; box-shadow:var(--shadow-xs); }
.ct-stat-ico { width:36px; height:36px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.ct-stat-num { font-family:var(--font-serif); font-size:24px; font-weight:700; color:var(--text); line-height:1; }
.ct-stat-label { font-size:11.5px; font-weight:500; color:var(--text-3); margin-top:6px; }

.ct-tabs { display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:16px; }
.ct-tab { display:inline-flex; align-items:center; gap:7px; padding:8px 16px; font-size:13px; font-weight:600; color:var(--text-3); background:none; border:none; border-radius:var(--radius-full); cursor:pointer; transition:all .15s ease; }
.ct-tab:hover { color:var(--gold-dark); }
.ct-tab.active { background:var(--gold-dark); color:var(--text-inverted); }
.ct-tab-count { display:inline-flex; align-items:center; justify-content:center; min-width:18px; height:18px; padding:0 5px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-3); font-size:10.5px; font-weight:700; }
.ct-tab.active .ct-tab-count { background:rgba(255,255,255,.25); color:var(--text-inverted); }

.ct-filterbar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
.ct-search { display:flex; align-items:center; gap:8px; flex:1; min-width:240px; padding:10px 15px; background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius-full); color:var(--text-4); }
.ct-search input { flex:1; border:none; outline:none; background:none; font-size:13px; color:var(--text); }
.ct-filterbar .form-select { width:auto; min-width:150px; border-radius:var(--radius-full); }

.ct-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; align-items:start; }
.ct-card { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--border-dark); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; display:flex; flex-direction:column; transition:transform .18s ease,box-shadow .18s ease; }
.ct-card:hover { transform:translateY(-2px); box-shadow:var(--shadow-sm); }
.ct-card.rail-green { border-left-color:var(--green); }
.ct-card.rail-gold { border-left-color:var(--gold-dark); }
.ct-card.rail-red { border-left-color:var(--red); }
.ct-card.rail-neutral { border-left-color:var(--border-dark); }
.ct-card-body { padding:20px 22px; flex:1; }
.ct-card-top { display:flex; align-items:flex-start; gap:12px; }
.ct-av { width:38px; height:38px; border-radius:50%; background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; font-size:13px; flex-shrink:0; }
.ct-card-headinfo { flex:1; min-width:0; }
.ct-card-title { font-family:var(--font-serif); font-size:17px; font-weight:600; color:var(--text); line-height:1.25; }
.ct-card-who { font-size:12.5px; color:var(--gold-dark); margin-top:3px; }
.ct-card-amt-tr { text-align:right; flex-shrink:0; }
.ct-amt { font-family:var(--font-serif); font-size:22px; font-weight:700; color:var(--text); }
.ct-amt-unit { display:block; font-size:11px; color:var(--text-4); margin-top:2px; }

.ct-meta-row { display:flex; align-items:center; gap:14px; flex-wrap:wrap; margin-top:12px; }
.ct-badge { font-size:9.5px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; padding:3px 10px; border-radius:var(--radius-full); }
.ct-badge.st-green { background:var(--green-light); color:var(--green-dark); }
.ct-badge.st-gold { background:var(--badge-bg-gold); color:var(--gold-dark); }
.ct-badge.st-red { background:var(--red-light); color:var(--red); }
.ct-badge.st-neutral { background:var(--surface-3); color:var(--text-3); }
.ct-meta-item { display:inline-flex; align-items:center; gap:6px; font-size:12px; color:var(--text-3); }
.ct-meta-item svg { color:var(--text-4); }

.ct-amt-block { font-family:var(--font-serif); font-size:26px; font-weight:700; color:var(--text); margin-top:16px; line-height:1; }
.ct-billing { font-size:12px; color:var(--text-3); margin-top:6px; }

.ct-milestones { margin-top:18px; padding-top:16px; border-top:1px solid var(--border); }
.ct-ms-label { font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); margin-bottom:9px; }
.ct-ms-row { display:flex; align-items:center; gap:9px; }
.ct-ms-dots { display:inline-flex; gap:4px; flex-shrink:0; }
.ct-ms-dot { width:8px; height:8px; border-radius:50%; background:var(--surface-3); }
.ct-ms-dot.done { background:var(--green); }
.ct-ms-text { font-size:12px; color:var(--text-3); }
.ct-ms-text strong { color:var(--text); font-weight:600; }
.ct-ms-due { color:var(--gold-dark); font-weight:600; }
.ct-ms-bar { height:5px; background:var(--surface-3); border-radius:var(--radius-full); overflow:hidden; margin-top:10px; }
.ct-ms-fill { height:100%; background:linear-gradient(90deg,var(--gold),var(--gold-dark)); border-radius:var(--radius-full); }

.ct-card-foot { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:13px 22px; border-top:1px solid var(--border); background:var(--surface-2); flex-wrap:wrap; }
.ct-foot-dates { display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
.ct-foot-dates span { display:inline-flex; align-items:center; gap:6px; font-size:11.5px; color:var(--green-dark); }
.ct-foot-dates svg { color:var(--text-4); }
.ct-foot-actions { display:flex; align-items:center; gap:5px; }

@media (max-width:1000px) { .ct-stats { grid-template-columns:repeat(2,1fr); } .ct-grid { grid-template-columns:1fr; } }
@media (max-width:700px) { .ct-header { flex-direction:column; } .ct-stats { grid-template-columns:1fr; } }
</style>
