<template>
  <AppLayout :user="user" portal="support_steward" activePage="providers" pageTitle="My Practitioners" :has-emergency="true">

    <!-- HEADER -->
    <div class="mp-header">
      <div>
        <div class="mp-eyebrow">Critical Moment Plans</div>
        <h1 class="mp-title">My Practitioners</h1>
        <p class="mp-sub">Active incident reported — monitoring in progress</p>
      </div>
      <div class="mp-header-actions">
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
        <button type="button" class="btn btn-primary btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Report Critical Incident</button>
      </div>
    </div>

    <!-- STAT CARDS -->
    <div class="mp-stats">
      <div class="mp-stat"><div class="mp-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div><div class="mp-stat-num">{{ practitioners.length }}</div><div class="mp-stat-label">Total Practitioners</div></div>
      <div class="mp-stat"><div class="mp-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div><div class="mp-stat-num">{{ activeCount }}</div><div class="mp-stat-label">Active Incidents</div></div>
      <div class="mp-stat"><div class="mp-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div><div class="mp-stat-num">4</div><div class="mp-stat-label">Pending Tasks</div></div>
      <div class="mp-stat"><div class="mp-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div><div class="mp-stat-num">0</div><div class="mp-stat-label">Activity</div></div>
    </div>

    <!-- FILTERS -->
    <div class="mp-filters">
      <div class="mp-search">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" placeholder="Search practitioners..." />
      </div>
      <select v-model="sort" class="form-select mp-select"><option>Sort: Default</option><option>Sort: Name</option><option>Sort: Re-attestation</option></select>
      <span class="mp-count">Showing {{ visible.length }} of {{ practitioners.length }}</span>
    </div>

    <!-- TABS -->
    <div class="mp-tabs">
      <button v-for="t in tabs" :key="t.key" type="button" class="mp-tab" :class="{ active: tab === t.key }" @click="tab = t.key">
        <span v-if="t.icon" class="mp-tab-ico" v-html="t.icon"></span>
        {{ t.label }}
        <span v-if="t.count !== null" class="mp-tab-count">{{ t.count }}</span>
      </button>
    </div>

    <!-- LIST -->
    <template v-if="visible.length">
      <div v-for="p in visible" :key="p.initials" class="mp-card" :class="{ incident: p.status === 'incident' }">
        <div class="mp-card-rail" :class="p.status === 'incident' ? 'rail-red' : 'rail-green'"></div>
        <div class="mp-card-main">
          <div class="mp-card-pad">
            <div class="mp-card-top">
              <div class="mp-av" :class="p.status === 'incident' ? 'av-red' : 'av-gold'">{{ p.initials }}</div>
              <div class="mp-card-info">
                <div class="mp-card-name">{{ p.name }}</div>
                <div class="mp-card-spec">{{ p.specialty }}</div>
                <div class="mp-card-meta">
                  <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/></svg> {{ p.org }}</span>
                  <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> {{ p.location }}</span>
                </div>
              </div>
              <span class="mp-status" :class="p.status === 'incident' ? 'st-inc' : 'st-active'"><span class="mp-status-dot"></span> {{ p.status === 'incident' ? 'Critical Incident Active' : 'Active' }}</span>
            </div>

            <div class="mp-rows">
              <div class="mp-row"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Continuity Steward: <strong :class="{ muted: !p.cs }">{{ p.cs || 'No Continuity Steward assigned' }}</strong></div>
              <div class="mp-row"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Annual re-attestation date: <strong>{{ p.reattest }}</strong></div>
              <div class="mp-row"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Vault: <strong class="warn">{{ p.vault }}</strong></div>
              <div class="mp-row"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Task list: <strong class="ok">{{ p.taskList }}</strong></div>
            </div>

            <div class="mp-note"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Private note — only you can see this.</div>
          </div>

          <div v-if="p.incident" class="mp-incident">
            <div class="mp-incident-title"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Incident In Progress</div>
            <div class="mp-incident-desc"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> {{ p.incident.desc }}</div>
            <div class="mp-incident-assigned"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> {{ p.incident.assigned }}</div>
          </div>

          <div class="mp-card-foot">
            <button type="button" class="btn-icon" data-tip="Incident"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></button>
            <button type="button" class="btn-icon" data-tip="Profile"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></button>
            <button type="button" class="btn-icon" data-tip="Documents"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></button>
            <button type="button" class="btn-icon" data-tip="Plan"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></button>
            <button type="button" class="btn-icon" data-tip="Message"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></button>
            <button v-if="p.incident" type="button" class="btn-icon" data-tip="Notify"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></button>
            <button type="button" class="btn-icon" data-tip="Edit note"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></button>
          </div>
        </div>
      </div>
    </template>

    <div v-else class="mp-empty">
      <div class="mp-empty-ico"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
      <div class="mp-empty-title">No practitioners found</div>
      <div class="mp-empty-sub">Try adjusting your search or filters, or clear them to see your full list.</div>
      <button type="button" class="btn btn-outline btn-sm" @click="clearFilters"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Clear filters</button>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
defineProps({ user: Object });

const search = ref('');
const sort = ref('Sort: Default');
const tab = ref('all');

const practitioners = [
  { initials: 'MT', name: 'Dr. Michael Torres', specialty: 'Adult Psychiatry, Mood Disorders', org: 'Bay Area Psychiatry Associates', location: 'Oakland, CA', status: 'incident', cs: 'Dr. Sarah Johnson', reattest: 'Nov 8, 2026', vault: 'Attestation pending', taskList: 'Certified Jan 10, 2026', incident: { desc: 'Dr. Torres experienced a sudden illness and is expected to be away from practice for approximately one week. Linda confirmed via phone and initiated the short-term coverage protocol.', assigned: 'Dr. Sarah Johnson notified and assigned' } },
  { initials: 'RO', name: 'Dr. Rachel Okafor', specialty: 'Anxiety, OCD, CBT', org: 'North Bay Therapy Collective', location: 'Santa Rosa, CA', status: 'active', cs: '', reattest: 'Jan 20, 2027', vault: 'Attestation pending', taskList: 'Certified Mar 5, 2026', incident: null },
  { initials: 'SJ', name: 'Dr. Sarah Johnson', specialty: 'Trauma & EMDR, Family Systems', org: 'Lotus Psychology Group', location: 'San Francisco, CA', status: 'incident', cs: 'Marcus Chen', reattest: 'Jun 15, 2026', vault: 'Attestation pending', taskList: 'Certified Feb 14, 2026', incident: { desc: 'Dr. Johnson was admitted for an emergency appendectomy on Apr 24 and is expected to be out of practice for roughly two weeks. She asked me to trigger short-term coverage so appointments are handled while she recovers.', assigned: 'Marcus Chen notified and assigned' } },
];

const activeCount = computed(() => practitioners.filter(p => p.status === 'incident').length);

const tabs = computed(() => [
  { key: 'all', label: 'All', icon: '', count: practitioners.length },
  { key: 'active', label: 'Active', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>', count: activeCount.value },
  { key: 'activity', label: 'Activity', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>', count: null },
]);

const visible = computed(() => {
  if (tab.value === 'activity') return [];
  let list = practitioners;
  if (tab.value === 'active') list = list.filter(p => p.status === 'incident');
  if (search.value.trim()) { const q = search.value.toLowerCase(); list = list.filter(p => p.name.toLowerCase().includes(q) || p.specialty.toLowerCase().includes(q)); }
  return list;
});

function clearFilters() { search.value = ''; sort.value = 'Sort: Default'; tab.value = 'all'; }
</script>

<style scoped>
/* ── SS MY PRACTITIONERS ── */
.mp-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:24px 30px; margin-bottom:18px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.mp-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.6px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.mp-title { font-family:var(--font-serif); font-size:30px; font-weight:600; letter-spacing:-0.4px; color:var(--text); margin:0; line-height:1.05; }
.mp-sub { font-size:13.5px; color:var(--text-3); margin-top:8px; }
.mp-header-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; flex-wrap:wrap; }

.mp-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px; }
.mp-stat { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px 18px; box-shadow:var(--shadow-xs); display:grid; grid-template-columns:auto 1fr; grid-template-rows:auto auto; column-gap:12px; align-items:center; }
.mp-stat-ico { grid-row:1 / 3; width:34px; height:34px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.mp-stat-num { font-family:var(--font-serif); font-size:24px; font-weight:700; line-height:1; color:var(--text); }
.mp-stat-label { font-size:11.5px; color:var(--text-3); margin-top:3px; }

.mp-filters { display:flex; align-items:center; gap:10px; margin-bottom:16px; flex-wrap:wrap; }
.mp-search { position:relative; flex:1; min-width:220px; }
.mp-search svg { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--text-4); pointer-events:none; }
.mp-search input { width:100%; padding:9px 13px 9px 36px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; }
.mp-search input:focus { border-color:var(--gold-dark); }
.mp-search input::placeholder { color:var(--text-4); }
.mp-select { width:auto; min-width:150px; }
.mp-count { font-size:12px; color:var(--text-4); margin-left:auto; white-space:nowrap; }

.mp-tabs { display:inline-flex; align-items:center; gap:2px; padding:4px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-full); margin-bottom:18px; flex-wrap:wrap; }
.mp-tab { display:inline-flex; align-items:center; gap:7px; padding:8px 16px; font-size:12.5px; font-weight:600; color:var(--text-3); background:none; border:none; border-radius:var(--radius-full); cursor:pointer; transition:all .15s ease; white-space:nowrap; }
.mp-tab:hover { color:var(--text); }
.mp-tab.active { background:var(--gold-dark); color:var(--text-inverted); }
.mp-tab-ico { display:inline-flex; }
.mp-tab-ico :deep(svg) { width:13px; height:13px; }
.mp-tab-count { font-size:10px; font-weight:700; min-width:16px; height:16px; padding:0 5px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-3); display:inline-flex; align-items:center; justify-content:center; }
.mp-tab.active .mp-tab-count { background:rgba(255,255,255,.25); color:var(--text-inverted); }

.mp-card { display:flex; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; margin-bottom:14px; }
.mp-card-rail { width:4px; flex-shrink:0; }
.mp-card-rail.rail-red { background:var(--red); }
.mp-card-rail.rail-green { background:var(--green); }
.mp-card-main { flex:1; min-width:0; }
.mp-card-pad { padding:18px 22px 14px; }
.mp-card-top { display:flex; align-items:flex-start; gap:14px; }
.mp-av { width:42px; height:42px; border-radius:var(--radius-sm); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; font-size:14px; flex-shrink:0; }
.mp-av.av-red { background:var(--red-dark); }
.mp-av.av-gold { background:var(--gold-dark); }
.mp-card-info { flex:1; min-width:0; }
.mp-card-name { font-family:var(--font-serif); font-size:16px; font-weight:600; color:var(--text); line-height:1.2; }
.mp-card-spec { font-size:12.5px; color:var(--text-2); margin-top:2px; }
.mp-card-meta { display:flex; align-items:center; gap:16px; flex-wrap:wrap; margin-top:6px; }
.mp-card-meta span { display:inline-flex; align-items:center; gap:5px; font-size:11.5px; color:var(--text-4); }
.mp-status { display:inline-flex; align-items:center; gap:6px; font-size:10px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; flex-shrink:0; }
.mp-status.st-inc { color:var(--red); }
.mp-status.st-active { color:var(--green-dark); }
.mp-status-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

.mp-rows { display:flex; flex-direction:column; gap:7px; margin-top:14px; }
.mp-row { display:flex; align-items:center; gap:8px; font-size:12.5px; color:var(--text-3); }
.mp-row svg { color:var(--text-4); flex-shrink:0; }
.mp-row strong { color:var(--text-2); font-weight:600; }
.mp-row strong.muted { color:var(--text-4); font-weight:500; }
.mp-row strong.warn { color:var(--orange-dark); }
.mp-row strong.ok { color:var(--green-dark); }

.mp-note { display:flex; align-items:center; gap:8px; margin-top:14px; padding:11px 14px; background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius); font-size:12px; color:var(--text-4); font-style:italic; }
.mp-note svg { flex-shrink:0; }

.mp-incident { padding:14px 22px; background:rgba(160,45,34,.05); border-top:1px solid rgba(160,45,34,.12); border-bottom:1px solid rgba(160,45,34,.12); }
.mp-incident-title { display:inline-flex; align-items:center; gap:7px; font-size:12.5px; font-weight:700; color:var(--red); margin-bottom:8px; }
.mp-incident-desc { display:flex; align-items:flex-start; gap:7px; font-size:12px; color:var(--text-2); line-height:1.55; margin-bottom:7px; }
.mp-incident-desc svg { color:var(--text-4); flex-shrink:0; margin-top:2px; }
.mp-incident-assigned { display:inline-flex; align-items:center; gap:7px; font-size:12px; color:var(--green-dark); }

.mp-card-foot { display:flex; align-items:center; gap:8px; padding:12px 22px; }

.mp-empty { background:var(--surface); border:1px dashed var(--border-dark); border-radius:var(--radius-lg); padding:54px 24px; text-align:center; }
.mp-empty-ico { width:54px; height:54px; border-radius:50%; background:var(--surface-3); color:var(--text-4); display:flex; align-items:center; justify-content:center; margin:0 auto 16px; }
.mp-empty-title { font-family:var(--font-serif); font-size:18px; font-weight:600; color:var(--text); margin-bottom:6px; }
.mp-empty-sub { font-size:13px; color:var(--text-4); line-height:1.55; max-width:380px; margin:0 auto 16px; }

@media (max-width:900px) { .mp-stats { grid-template-columns:1fr 1fr; } .mp-header { flex-direction:column; } .mp-count { margin-left:0; } }
</style>
