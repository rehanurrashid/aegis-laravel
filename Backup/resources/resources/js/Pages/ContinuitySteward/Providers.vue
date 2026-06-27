<template>
  <AppLayout :user="user" portal="continuity_steward" activePage="providers" pageTitle="My Practitioners">

    <!-- ═══ HEADER ═══ -->
    <div class="mp-header">
      <div>
        <div class="mp-eyebrow">My Work</div>
        <h1 class="mp-title">My Practitioners</h1>
        <p class="mp-sub">{{ practitioners.length }} practitioner{{ practitioners.length === 1 ? '' : 's' }} in your caseload</p>
      </div>
      <div class="mp-header-actions">
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
        <button type="button" class="btn btn-primary btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Invite Practitioner</button>
      </div>
    </div>

    <!-- ═══ STAT CARDS ═══ -->
    <div class="mp-stats">
      <div class="mp-stat">
        <div class="mp-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
        <div class="mp-stat-num">{{ stats.practitioners }}</div>
        <div class="mp-stat-label">Practitioners</div>
      </div>
      <div class="mp-stat">
        <div class="mp-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
        <div class="mp-stat-num">{{ stats.activeIncidents }}</div>
        <div class="mp-stat-label">Active Incidents</div>
      </div>
      <div class="mp-stat">
        <div class="mp-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
        <div class="mp-stat-num">{{ stats.plansCertified }}</div>
        <div class="mp-stat-label">Plans Certified</div>
      </div>
      <div class="mp-stat">
        <div class="mp-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div class="mp-stat-num">{{ stats.certsDue }}</div>
        <div class="mp-stat-label">Certifications Due</div>
      </div>
    </div>

    <!-- ═══ FREE ACCOUNT BANNER ═══ -->
    <div class="mp-free-banner">
      <div class="mp-free-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
      <div class="mp-free-body">
        <div class="mp-free-title">Free Account — Connected to {{ primaryProvider }}</div>
        <div class="mp-free-sub">Your invited account is linked exclusively to this practitioner. Upgrade to a Business CS plan to invite additional practitioners and expand your practice.</div>
      </div>
      <button type="button" class="btn btn-gold btn-sm mp-free-cta"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg> Upgrade</button>
    </div>

    <!-- ═══ FILTER ROW ═══ -->
    <div class="mp-filters">
      <div class="mp-search">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" placeholder="Search practitioners..." />
      </div>
      <select v-model="statusFilter" class="form-select mp-select"><option>All Statuses</option><option>Active</option><option>Standby</option><option>Cert Due</option></select>
      <select v-model="roleFilter" class="form-select mp-select"><option>All Roles</option><option>Primary CS</option><option>Alternate CS</option></select>
      <span class="mp-count">{{ countText }}</span>
    </div>

    <!-- ═══ TAB PILLS ═══ -->
    <div class="mp-tabs">
      <button v-for="t in tabs" :key="t.key" type="button" class="mp-tab" :class="{ active: activeTab === t.key }" @click="activeTab = t.key">
        <span v-if="t.icon" class="mp-tab-ico" v-html="t.icon"></span>
        {{ t.label }}
        <span v-if="t.count" class="mp-tab-count">{{ t.count }}</span>
      </button>
    </div>

    <!-- ═══ CONTENT ═══ -->
    <template v-if="visiblePractitioners.length">
      <div v-for="pv in visiblePractitioners" :key="pv.initials" class="mp-card">
        <div class="mp-card-rail"></div>
        <div class="mp-card-main">
          <div class="mp-card-pad">
            <div class="mp-card-top">
              <div class="mp-av">{{ pv.initials }}</div>
              <div class="mp-card-info">
                <div class="mp-card-name">{{ pv.name }} <span class="mp-card-cred">{{ pv.credentials }} · {{ pv.csRole }}</span></div>
                <div class="mp-card-org">{{ pv.org }} · {{ pv.specialty }}</div>
                <div class="mp-card-meta">
                  <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> {{ pv.location }}</span>
                  <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> {{ pv.vaultStatus }}</span>
                </div>
              </div>
              <span v-if="pv.incident" class="mp-incident-pill"><span class="mp-pill-dot"></span> Active Critical Incident</span>
            </div>
          </div>

          <div v-if="pv.incident" class="mp-incident-panel">
            <div class="mp-incident-type"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> {{ pv.incident.type }}</div>
            <div class="mp-incident-line"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Triggered <strong>{{ pv.incident.triggered }}</strong> by {{ pv.incident.triggeredBy }} <span class="mp-role-tag">SS</span></div>
            <div class="mp-incident-line ok"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Verified by <span class="mp-role-tag">CS</span> {{ pv.incident.verifiedBy }}</div>
            <div class="mp-progress-head"><span>Task progress</span><span>{{ pv.incident.done }} of {{ pv.incident.total }} completed</span></div>
            <div class="mp-progress-bar"><div class="mp-progress-fill" :style="{ width: (pv.incident.done / pv.incident.total * 100) + '%' }"></div></div>
          </div>

          <div class="mp-card-foot">
            <button type="button" class="btn-icon" data-tip="Task List"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg></button>
            <button type="button" class="btn-icon" data-tip="Vault"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></button>
            <button type="button" class="btn-icon" data-tip="Message"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></button>
            <button type="button" class="btn-icon" data-tip="Refresh"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg></button>
          </div>
        </div>
      </div>
    </template>

    <div v-else class="mp-empty">
      <div class="mp-empty-ico"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
      <div class="mp-empty-title">No practitioners found</div>
      <div class="mp-empty-sub">Try adjusting your search or filters, or reset them to see your full caseload.</div>
      <button type="button" class="btn btn-outline btn-sm" @click="clearFilters"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Clear filters</button>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
defineProps({ user: Object });

const primaryProvider = 'Dr. Sarah Johnson';
const stats = { practitioners: 1, activeIncidents: 1, plansCertified: 0, certsDue: 1 };

const search = ref('');
const statusFilter = ref('All Statuses');
const roleFilter = ref('All Roles');
const activeTab = ref('all');

const practitioners = [
  {
    initials: 'SJ', name: 'Dr. Sarah Johnson', credentials: 'PhD, LMFT', csRole: 'Alternate CS',
    org: 'Lotus Psychology Group', specialty: 'Licensed Marriage & Family Therapist',
    location: 'San Francisco, CA', vaultStatus: 'Vault attestation pending',
    status: 'active', certDue: true,
    incident: { type: 'Short-Term Incapacitation', triggered: 'Apr 25, 2026 · 10:20 AM', triggeredBy: 'Linda Johnson', verifiedBy: 'Dr. Priya Raman', done: 3, total: 7 },
  },
];

const tabs = computed(() => [
  { key: 'all', label: 'All', icon: '', count: practitioners.length },
  { key: 'active', label: 'Active', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>', count: practitioners.filter(p => p.status === 'active').length },
  { key: 'standby', label: 'Standby', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>', count: practitioners.filter(p => p.status === 'standby').length },
  { key: 'cert', label: 'Cert Due', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>', count: practitioners.filter(p => p.certDue).length },
]);

const visiblePractitioners = computed(() => {
  let list = practitioners;
  if (activeTab.value === 'active') list = list.filter(p => p.status === 'active');
  else if (activeTab.value === 'standby') list = list.filter(p => p.status === 'standby');
  else if (activeTab.value === 'cert') list = list.filter(p => p.certDue);
  if (search.value.trim()) {
    const q = search.value.toLowerCase();
    list = list.filter(p => p.name.toLowerCase().includes(q) || p.org.toLowerCase().includes(q));
  }
  return list;
});

const countText = computed(() => {
  const n = visiblePractitioners.value.length;
  if (activeTab.value === 'all') return `${n} practitioner${n === 1 ? 's' : 's'}`;
  return `Showing ${n} practitioner${n === 1 ? '' : 's'}`;
});

function clearFilters() {
  search.value = '';
  statusFilter.value = 'All Statuses';
  roleFilter.value = 'All Roles';
  activeTab.value = 'all';
}
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════════
   CS — MY PRACTITIONERS
   ════════════════════════════════════════════════════════════════ */

/* HEADER */
.mp-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:24px 30px; margin-bottom:18px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.mp-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.6px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.mp-title { font-family:var(--font-serif); font-size:30px; font-weight:600; letter-spacing:-0.4px; color:var(--text); margin:0; line-height:1.05; }
.mp-sub { font-size:13.5px; color:var(--text-3); margin-top:8px; }
.mp-header-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; flex-wrap:wrap; }

/* STAT CARDS */
.mp-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px; }
.mp-stat { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px 18px; box-shadow:var(--shadow-xs); display:grid; grid-template-columns:auto 1fr; grid-template-rows:auto auto; column-gap:12px; align-items:center; transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease; }
.mp-stat:hover { transform:translateY(-2px); box-shadow:var(--shadow-sm); border-color:var(--surface-4); }
.mp-stat-ico { grid-row:1 / 3; width:34px; height:34px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.mp-stat-num { font-family:var(--font-serif); font-size:24px; font-weight:700; line-height:1; color:var(--text); }
.mp-stat-label { font-size:11.5px; color:var(--text-3); margin-top:3px; }

/* FREE ACCOUNT BANNER */
.mp-free-banner { display:flex; align-items:center; gap:16px; padding:16px 20px; background:linear-gradient(95deg,var(--badge-bg-gold) 0%,var(--surface) 80%); border:1px solid rgba(192,154,82,.30); border-radius:var(--radius-lg); box-shadow:var(--shadow-sm); margin-bottom:18px; }
.mp-free-ico { width:38px; height:38px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.mp-free-body { flex:1; min-width:0; }
.mp-free-title { font-size:13px; font-weight:700; color:var(--text); margin-bottom:3px; }
.mp-free-sub { font-size:12px; color:var(--text-3); line-height:1.5; }
.mp-free-cta { flex-shrink:0; white-space:nowrap; }

/* FILTERS */
.mp-filters { display:flex; align-items:center; gap:10px; margin-bottom:16px; flex-wrap:wrap; }
.mp-search { position:relative; flex:1; min-width:220px; }
.mp-search svg { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--text-4); pointer-events:none; }
.mp-search input { width:100%; padding:9px 13px 9px 36px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; transition:border-color .15s ease; }
.mp-search input:focus { border-color:var(--gold-dark); }
.mp-search input::placeholder { color:var(--text-4); }
.mp-select { width:auto; min-width:150px; }
.mp-count { font-size:12px; color:var(--text-4); margin-left:auto; white-space:nowrap; }

/* TAB PILLS */
.mp-tabs { display:inline-flex; align-items:center; gap:2px; padding:4px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-full); margin-bottom:18px; flex-wrap:wrap; }
.mp-tab { display:inline-flex; align-items:center; gap:7px; padding:8px 16px; font-size:12.5px; font-weight:600; color:var(--text-3); background:none; border:none; border-radius:var(--radius-full); cursor:pointer; transition:all .15s ease; white-space:nowrap; }
.mp-tab:hover { color:var(--text); }
.mp-tab.active { background:var(--gold-dark); color:var(--text-inverted); }
.mp-tab-ico { display:inline-flex; }
.mp-tab-ico :deep(svg) { width:13px; height:13px; }
.mp-tab-count { font-size:10px; font-weight:700; min-width:16px; height:16px; padding:0 5px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-3); display:inline-flex; align-items:center; justify-content:center; }
.mp-tab.active .mp-tab-count { background:rgba(255,255,255,.25); color:var(--text-inverted); }

/* PRACTITIONER CARD */
.mp-card { display:flex; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; margin-bottom:12px; }
.mp-card-rail { width:4px; background:var(--gold-dark); flex-shrink:0; }
.mp-card-main { flex:1; min-width:0; }
.mp-card-pad { padding:18px 20px 14px; }
.mp-card-top { display:flex; align-items:flex-start; gap:14px; }
.mp-av { width:42px; height:42px; border-radius:var(--radius-sm); background:var(--red-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; font-size:14px; flex-shrink:0; }
.mp-card-info { flex:1; min-width:0; }
.mp-card-name { font-family:var(--font-serif); font-size:16px; font-weight:600; color:var(--text); line-height:1.25; }
.mp-card-cred { font-family:var(--font-sans); font-size:12px; font-weight:500; color:var(--text-3); }
.mp-card-org { font-size:12.5px; color:var(--text-3); margin-top:3px; }
.mp-card-meta { display:flex; align-items:center; gap:16px; flex-wrap:wrap; margin-top:8px; }
.mp-card-meta span { display:inline-flex; align-items:center; gap:5px; font-size:11.5px; color:var(--text-4); }
.mp-incident-pill { display:inline-flex; align-items:center; gap:6px; font-size:10px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; padding:4px 11px; border-radius:var(--radius-full); background:var(--red-light); color:var(--red); flex-shrink:0; }
.mp-pill-dot { width:6px; height:6px; border-radius:50%; background:var(--red); }

/* INCIDENT PANEL */
.mp-incident-panel { padding:14px 20px; background:rgba(160,45,34,.05); border-top:1px solid rgba(160,45,34,.12); border-bottom:1px solid rgba(160,45,34,.12); }
.mp-incident-type { display:inline-flex; align-items:center; gap:7px; font-size:13px; font-weight:700; color:var(--red); margin-bottom:8px; }
.mp-incident-line { display:flex; align-items:center; gap:7px; font-size:12px; color:var(--text-3); margin-bottom:5px; }
.mp-incident-line svg { color:var(--text-4); flex-shrink:0; }
.mp-incident-line strong { color:var(--text-2); font-weight:600; }
.mp-incident-line.ok { color:var(--green-dark); }
.mp-incident-line.ok svg { color:var(--green-dark); }
.mp-role-tag { font-size:9px; font-weight:700; letter-spacing:.5px; padding:1px 5px; border-radius:4px; background:var(--surface-3); color:var(--text-3); }
.mp-progress-head { display:flex; align-items:center; justify-content:space-between; font-size:11px; color:var(--text-4); margin:12px 0 5px; }
.mp-progress-bar { height:6px; background:rgba(160,45,34,.12); border-radius:var(--radius-full); overflow:hidden; }
.mp-progress-fill { height:100%; background:var(--red); border-radius:var(--radius-full); transition:width .6s ease; }

/* CARD FOOTER */
.mp-card-foot { display:flex; align-items:center; gap:8px; padding:12px 20px; }

/* EMPTY STATE */
.mp-empty { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); padding:54px 24px; text-align:center; }
.mp-empty-ico { width:54px; height:54px; border-radius:50%; background:var(--surface-3); color:var(--text-4); display:flex; align-items:center; justify-content:center; margin:0 auto 16px; }
.mp-empty-title { font-family:var(--font-serif); font-size:18px; font-weight:600; color:var(--text); margin-bottom:6px; }
.mp-empty-sub { font-size:13px; color:var(--text-4); line-height:1.55; max-width:380px; margin:0 auto 16px; }

@media (max-width:900px) {
  .mp-stats { grid-template-columns:1fr 1fr; }
  .mp-header, .mp-free-banner { flex-direction:column; align-items:flex-start; }
  .mp-count { margin-left:0; }
}
</style>
