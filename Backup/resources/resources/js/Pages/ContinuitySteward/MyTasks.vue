<template>
  <AppLayout :user="user" portal="continuity_steward" activePage="my-tasks" pageTitle="My Tasks">

    <!-- ═══ HEADER ═══ -->
    <div class="mt-header">
      <div>
        <div class="mt-eyebrow">Continuity Steward</div>
        <h1 class="mt-title">My Tasks</h1>
        <p class="mt-sub">{{ headerSub }}</p>
      </div>
      <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
    </div>

    <!-- ═══ STAT CARDS ═══ -->
    <div class="mt-stats">
      <div class="mt-stat">
        <div class="mt-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
        <div class="mt-stat-num">{{ stats.active }}</div>
        <div class="mt-stat-label">Active Tasks</div>
      </div>
      <div class="mt-stat">
        <div class="mt-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
        <div class="mt-stat-num">{{ stats.providers }}</div>
        <div class="mt-stat-label">Your Provider</div>
      </div>
      <div class="mt-stat">
        <div class="mt-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
        <div class="mt-stat-num">{{ stats.completed }}</div>
        <div class="mt-stat-label">Completed in 30 days</div>
      </div>
      <div class="mt-stat">
        <div class="mt-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div>
        <div class="mt-stat-num">{{ stats.awaiting }}</div>
        <div class="mt-stat-label">Awaiting Certification</div>
      </div>
    </div>

    <!-- ═══ FILTER ROW ═══ -->
    <div class="mt-filters">
      <div class="mt-search">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" placeholder="Search tasks..." />
      </div>
      <select v-model="statusFilter" class="form-select mt-select"><option>All Statuses</option><option>Active</option><option>Pending</option><option>Completed</option></select>
      <select v-model="typeFilter" class="form-select mt-select"><option>All Incident Types</option><option>Short-Term Incapacitation</option><option>Permanent Incapacity</option><option>Death</option><option>Missing Person</option></select>
    </div>

    <!-- ═══ TAB PILLS ═══ -->
    <div class="mt-tabs">
      <button v-for="t in tabs" :key="t.key" type="button" class="mt-tab" :class="{ active: activeTab === t.key }" @click="activeTab = t.key">
        <span v-if="t.icon" class="mt-tab-ico" v-html="t.icon"></span>
        {{ t.label }}
      </button>
    </div>

    <!-- ═══ CONTENT ═══ -->
    <!-- incident-list view (All / Active Incidents) -->
    <template v-if="activeTab === 'all' || activeTab === 'active'">
      <div v-for="inc in incidents" :key="inc.provider" class="mt-incident">
        <div class="mt-incident-rail"></div>
        <div class="mt-incident-main">
          <div class="mt-incident-head">
            <div>
              <div class="mt-incident-title"><strong>{{ inc.provider }}</strong> · {{ inc.type }}</div>
              <div class="mt-incident-meta">Reported {{ inc.reported }}</div>
            </div>
            <div class="mt-incident-actions">
              <span class="mt-incident-status">{{ inc.status }}</span>
              <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Open incident</button>
            </div>
          </div>
          <div class="mt-incident-body">No tasks for this incident match the current filter.</div>
        </div>
      </div>
    </template>

    <!-- empty-state view (Standby / Awaiting Certification) -->
    <div v-else class="mt-empty">
      <div class="mt-empty-ico"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
      <div class="mt-empty-title">No tasks match the current view</div>
      <div class="mt-empty-sub">Adjust the filters or reset to see every task across your assigned providers.</div>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
defineProps({ user: Object });

const stats = { active: 0, providers: 0, completed: 0, awaiting: 1 };

const search = ref('');
const statusFilter = ref('All Statuses');
const typeFilter = ref('All Incident Types');

const activeTab = ref('all');
const tabs = [
  { key: 'all', label: 'All', icon: '' },
  { key: 'active', label: 'Active Incidents', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>' },
  { key: 'standby', label: 'Standby', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>' },
  { key: 'awaiting', label: 'Awaiting Certification', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>' },
];

const headerSub = computed(() => {
  if (activeTab.value === 'all' || activeTab.value === 'active') return '0 open tasks across 1 active incident.';
  return 'Standby. 0 providers ready for activation.';
});

const incidents = [
  { provider: 'Dr. Sarah Johnson', type: 'Short-Term Incapacitation', reported: 'Apr 25', status: 'ACTIVE' },
];
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════════
   CS — MY TASKS
   ════════════════════════════════════════════════════════════════ */

/* HEADER */
.mt-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:24px 30px; margin-bottom:18px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.mt-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.6px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.mt-title { font-family:var(--font-serif); font-size:30px; font-weight:600; letter-spacing:-0.4px; color:var(--text); margin:0; line-height:1.05; }
.mt-sub { font-size:13.5px; color:var(--text-3); margin-top:8px; }

/* STAT CARDS */
.mt-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:18px; }
.mt-stat { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px 18px; box-shadow:var(--shadow-xs); display:grid; grid-template-columns:auto 1fr; grid-template-rows:auto auto; column-gap:12px; align-items:center; transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease; }
.mt-stat:hover { transform:translateY(-2px); box-shadow:var(--shadow-sm); border-color:var(--surface-4); }
.mt-stat-ico { grid-row:1 / 3; width:34px; height:34px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.mt-stat-num { font-family:var(--font-serif); font-size:24px; font-weight:700; line-height:1; color:var(--text); }
.mt-stat-label { font-size:11.5px; color:var(--text-3); margin-top:3px; }

/* FILTERS */
.mt-filters { display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap; }
.mt-search { position:relative; flex:1; min-width:220px; }
.mt-search svg { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--text-4); pointer-events:none; }
.mt-search input { width:100%; padding:9px 13px 9px 36px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; transition:border-color .15s ease; }
.mt-search input:focus { border-color:var(--gold-dark); }
.mt-search input::placeholder { color:var(--text-4); }
.mt-select { width:auto; min-width:160px; }

/* TAB PILLS */
.mt-tabs { display:inline-flex; align-items:center; gap:2px; padding:4px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-full); margin-bottom:18px; flex-wrap:wrap; }
.mt-tab { display:inline-flex; align-items:center; gap:7px; padding:8px 16px; font-size:12.5px; font-weight:600; color:var(--text-3); background:none; border:none; border-radius:var(--radius-full); cursor:pointer; transition:all .15s ease; white-space:nowrap; }
.mt-tab:hover { color:var(--text); }
.mt-tab.active { background:var(--gold-dark); color:var(--text-inverted); }
.mt-tab-ico { display:inline-flex; }
.mt-tab-ico :deep(svg) { width:13px; height:13px; }

/* INCIDENT CARD */
.mt-incident { display:flex; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; margin-bottom:12px; }
.mt-incident-rail { width:4px; background:var(--red); flex-shrink:0; }
.mt-incident-main { flex:1; min-width:0; }
.mt-incident-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:16px 20px; border-bottom:1px solid var(--border); flex-wrap:wrap; }
.mt-incident-title { font-size:14px; color:var(--text-2); }
.mt-incident-title strong { font-family:var(--font-serif); font-size:15px; font-weight:600; color:var(--text); }
.mt-incident-meta { font-size:11.5px; color:var(--text-4); margin-top:3px; }
.mt-incident-actions { display:flex; align-items:center; gap:14px; flex-shrink:0; }
.mt-incident-status { font-size:10px; font-weight:700; letter-spacing:1px; color:var(--red); }
.mt-incident-body { padding:22px 20px; font-size:13px; color:var(--text-4); }

/* EMPTY STATE */
.mt-empty { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); padding:60px 24px; text-align:center; }
.mt-empty-ico { width:54px; height:54px; border-radius:50%; background:var(--surface-3); color:var(--text-4); display:flex; align-items:center; justify-content:center; margin:0 auto 16px; }
.mt-empty-title { font-family:var(--font-serif); font-size:18px; font-weight:600; color:var(--text); margin-bottom:6px; }
.mt-empty-sub { font-size:13px; color:var(--text-4); line-height:1.55; max-width:380px; margin:0 auto; }

@media (max-width:900px) {
  .mt-stats { grid-template-columns:1fr 1fr; }
  .mt-header { flex-direction:column; }
}
</style>
