<template>
  <AppLayout :user="user" portal="continuity_steward" activePage="vault" pageTitle="Document Vault">

    <!-- ════════════════ SEALED STATE ════════════════ -->
    <template v-if="!unlocked">
      <div class="dv-sealed-bar">
        <span class="dv-sealed-bar-l"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> <strong>Viewing vault for:</strong> {{ sealed.name }} · {{ sealed.cred }}</span>
        <span class="dv-sealed-badge"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Sealed</span>
      </div>

      <div class="dv-header">
        <div>
          <h1 class="dv-title">{{ sealed.name }}</h1>
          <p class="dv-sub">Vault sealed · Requires a verified critical incident to unlock</p>
        </div>
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
      </div>

      <div class="dv-locked">
        <div class="dv-locked-ico"><svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
        <div class="dv-locked-title">Vault Access Sealed</div>
        <div class="dv-locked-sub">There is an active incident for this practitioner, but it has not been verified by you yet. Verify the incident on the Continuity Management page to unlock the vault.</div>
        <a href="/continuity-steward/continuity-management" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Go to Continuity Management</a>
      </div>
    </template>

    <!-- ════════════════ UNLOCKED STATE ════════════════ -->
    <template v-else>
      <!-- practitioner selector -->
      <div class="dv-selector">
        <span class="dv-selector-label"><span class="dv-dot"></span> Practitioner:</span>
        <select class="form-select dv-selector-select"><option>{{ unlockedP.name }} · {{ unlockedP.cred }} — Vault Unlocked</option></select>
      </div>

      <!-- header -->
      <div class="dv-header">
        <div>
          <h1 class="dv-title">{{ unlockedP.name }}</h1>
          <p class="dv-sub">Read-only access · Every action logged · Vault unlocked by active critical incident</p>
        </div>
        <div class="dv-header-actions">
          <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
          <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export Audit</button>
        </div>
      </div>

      <!-- stats -->
      <div class="dv-stats">
        <div class="dv-stat">
          <div class="dv-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
          <div class="dv-stat-num">0</div>
          <div class="dv-stat-label">Support Documents</div>
        </div>
        <div class="dv-stat">
          <div class="dv-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
          <div class="dv-stat-num">0</div>
          <div class="dv-stat-label">Client Records</div>
        </div>
        <div class="dv-stat">
          <div class="dv-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg></div>
          <div class="dv-stat-num">0</div>
          <div class="dv-stat-label">Secure Credentials</div>
        </div>
      </div>

      <!-- incident alert -->
      <div class="dv-incident-alert">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <div>
          <div class="dv-incident-title">Active Critical Incident: Short-Term Incapacitation</div>
          <div class="dv-incident-sub">Reported May 12, 2026 · Verified May 12, 2026 · <a href="/continuity-steward/continuity-management">View incident details</a></div>
        </div>
      </div>

      <!-- audit banner -->
      <div class="dv-audit-banner">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        <span>Every view, download, and credential reveal on this page is logged to your activity feed and the practitioner's audit trail.</span>
      </div>

      <!-- sub-tabs -->
      <div class="dv-subtabs">
        <button v-for="t in vaultTabs" :key="t.key" type="button" class="dv-subtab" :class="{ active: vaultTab === t.key }" @click="vaultTab = t.key">
          <span class="dv-subtab-ico" v-html="t.icon"></span>
          {{ t.label }}
          <span class="dv-subtab-count">{{ t.count }}</span>
        </button>
      </div>

      <!-- inner filter pills -->
      <div class="dv-pills">
        <button v-for="p in currentPills" :key="p.key" type="button" class="dv-pill" :class="{ active: pill === p.key }" @click="pill = p.key">{{ p.label }} <span class="dv-pill-count">{{ p.count }}</span></button>
      </div>

      <!-- search -->
      <div class="dv-search">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" placeholder="Search by name, category, or tags..." />
      </div>

      <!-- ── SUPPORT DOCUMENTS ── -->
      <template v-if="vaultTab === 'docs'">
        <div class="dv-warn red">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <div><strong>Sensitive Information — Read Only</strong><span>These documents were uploaded by the practitioner for use during a verified critical incident. You cannot edit or upload documents from this view.</span></div>
        </div>
        <div class="dv-empty">
          <div class="dv-empty-ico"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
          <div class="dv-empty-title">No support documents</div>
          <div class="dv-empty-sub">The practitioner has not added any documents to their emergency vault.</div>
        </div>
      </template>

      <!-- ── SECURE CREDENTIALS ── -->
      <template v-else-if="vaultTab === 'creds'">
        <div class="dv-warn gold">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
          <div><strong>System Access Credentials — Read Only</strong><span>Credentials are vault-locked and only accessible to you as the verified Continuity Steward. Every reveal is permanently logged.</span></div>
        </div>
        <div class="dv-section-head">Practice System Credentials <span class="dv-section-badge">0 stored · CS access only</span></div>
        <div class="dv-empty">
          <div class="dv-empty-ico"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg></div>
          <div class="dv-empty-title">No credentials stored</div>
          <div class="dv-empty-sub">The practitioner has not added any system credentials to their vault.</div>
        </div>
      </template>

      <!-- ── CLIENT ROSTER ── -->
      <template v-else>
        <div class="dv-warn gold">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <div><strong>Client Roster — Vault Protected · Read Only</strong><span>This roster is sealed during normal operations and only accessible after a verified critical incident. It is never visible to the Support Steward or anyone else.</span></div>
        </div>
        <div class="dv-info">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
          <span>Priority clients require immediate outreach. Review their notes for specific care considerations before making contact.</span>
        </div>
        <div class="dv-section-head">Active Clients <span class="dv-section-badge red">0</span></div>
        <div class="dv-empty">
          <div class="dv-empty-ico"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
          <div class="dv-empty-title">No active clients on roster</div>
          <div class="dv-empty-sub">The practitioner has not added any clients to their roster.</div>
        </div>
      </template>
    </template>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
defineProps({ user: Object });

// Toggle: true = unlocked (verified incident), false = sealed.
const unlocked = ref(true);

const sealed = { name: 'Dr. Sarah Johnson', cred: 'PhD, LMFT' };
const unlockedP = { name: 'Dr. Michael Torres', cred: 'MD' };

const vaultTab = ref('docs');
const vaultTabs = [
  { key: 'docs', label: 'Support Documents', count: 0, icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>' },
  { key: 'creds', label: 'Secure Credentials', count: 0, icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>' },
  { key: 'roster', label: 'Client Roster', count: 0, icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' },
];

const pill = ref('all');
const pillSets = {
  docs: [{ key: 'all', label: 'All', count: 0 }],
  creds: [{ key: 'all', label: 'All', count: 0 }],
  roster: [{ key: 'all', label: 'All', count: 0 }, { key: 'priority', label: 'Priority', count: 0 }, { key: 'standard', label: 'Standard', count: 0 }],
};
const currentPills = computed(() => pillSets[vaultTab.value]);

const search = ref('');
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════════
   CS — DOCUMENT VAULT
   ════════════════════════════════════════════════════════════════ */

/* shared header */
.dv-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:24px 30px; margin-bottom:16px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.dv-title { font-family:var(--font-serif); font-size:32px; font-weight:600; letter-spacing:-0.5px; color:var(--text); margin:0; line-height:1.05; }
.dv-sub { font-size:13px; color:var(--text-3); margin-top:8px; }
.dv-header-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; flex-wrap:wrap; }

/* ── SEALED ── */
.dv-sealed-bar { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:12px 18px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); margin-bottom:16px; }
.dv-sealed-bar-l { display:inline-flex; align-items:center; gap:8px; font-size:13px; color:var(--text-2); }
.dv-sealed-bar-l svg { color:var(--gold-dark); }
.dv-sealed-bar-l strong { color:var(--text); font-weight:700; }
.dv-sealed-badge { display:inline-flex; align-items:center; gap:6px; font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--text-3); }
.dv-locked { display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:64px 24px; }
.dv-locked-ico { color:var(--text-4); margin-bottom:14px; }
.dv-locked-title { font-family:var(--font-serif); font-size:24px; font-weight:600; color:var(--text); margin-bottom:12px; }
.dv-locked-sub { font-size:13.5px; color:var(--text-3); line-height:1.65; max-width:520px; margin:0 auto 22px; }

/* ── UNLOCKED ── */
.dv-selector { display:flex; align-items:center; gap:14px; margin-bottom:16px; }
.dv-selector-label { display:inline-flex; align-items:center; gap:8px; font-size:13px; font-weight:600; color:var(--text-2); white-space:nowrap; }
.dv-dot { width:8px; height:8px; border-radius:50%; border:2px solid var(--gold-dark); }
.dv-selector-select { flex:1; }

.dv-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:16px; }
.dv-stat { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px 20px; box-shadow:var(--shadow-xs); display:grid; grid-template-columns:auto 1fr; grid-template-rows:auto auto; column-gap:14px; align-items:center; }
.dv-stat-ico { grid-row:1 / 3; width:36px; height:36px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.dv-stat-num { font-family:var(--font-serif); font-size:24px; font-weight:700; line-height:1; color:var(--text); }
.dv-stat-label { font-size:12px; color:var(--text-3); margin-top:3px; }

.dv-incident-alert { display:flex; gap:12px; padding:14px 18px; background:rgba(160,45,34,.06); border:1px solid rgba(160,45,34,.18); border-radius:var(--radius-lg); margin-bottom:12px; }
.dv-incident-alert svg { color:var(--red); flex-shrink:0; margin-top:1px; }
.dv-incident-title { font-size:13.5px; font-weight:700; color:var(--red); margin-bottom:3px; }
.dv-incident-sub { font-size:12.5px; color:var(--text-3); }
.dv-incident-sub a { color:var(--gold-dark); font-weight:600; text-decoration:none; }
.dv-incident-sub a:hover { text-decoration:underline; }

.dv-audit-banner { display:flex; align-items:center; gap:10px; padding:12px 18px; background:rgba(80,120,190,.06); border:1px solid rgba(80,120,190,.18); border-radius:var(--radius-lg); margin-bottom:16px; font-size:12.5px; color:var(--text-2); line-height:1.5; }
.dv-audit-banner svg { color:#5078be; flex-shrink:0; }

/* sub-tabs (underline) */
.dv-subtabs { display:flex; align-items:stretch; gap:8px; border-bottom:1px solid var(--border); margin-bottom:16px; flex-wrap:wrap; }
.dv-subtab { display:inline-flex; align-items:center; gap:8px; padding:12px 6px; margin-bottom:-1px; font-size:13px; font-weight:600; color:var(--text-3); background:none; border:none; border-bottom:2px solid transparent; cursor:pointer; transition:all .15s ease; white-space:nowrap; }
.dv-subtab:hover { color:var(--text); }
.dv-subtab.active { color:var(--gold-dark); border-bottom-color:var(--gold-dark); }
.dv-subtab-ico { display:inline-flex; }
.dv-subtab-ico :deep(svg) { width:14px; height:14px; }
.dv-subtab-count { font-size:10px; font-weight:700; min-width:18px; height:18px; padding:0 5px; border-radius:var(--radius-full); background:var(--badge-bg-gold); color:var(--gold-dark); display:inline-flex; align-items:center; justify-content:center; }

/* inner pills */
.dv-pills { display:inline-flex; align-items:center; gap:2px; padding:4px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-full); margin-bottom:14px; flex-wrap:wrap; }
.dv-pill { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:12px; font-weight:600; color:var(--text-3); background:none; border:none; border-radius:var(--radius-full); cursor:pointer; transition:all .15s ease; white-space:nowrap; }
.dv-pill:hover { color:var(--text); }
.dv-pill.active { background:var(--gold-dark); color:var(--text-inverted); }
.dv-pill-count { font-size:10px; font-weight:700; min-width:15px; height:15px; padding:0 4px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-3); display:inline-flex; align-items:center; justify-content:center; }
.dv-pill.active .dv-pill-count { background:rgba(255,255,255,.25); color:var(--text-inverted); }

/* search */
.dv-search { position:relative; margin-bottom:14px; }
.dv-search svg { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-4); pointer-events:none; }
.dv-search input { width:100%; padding:11px 14px 11px 38px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; transition:border-color .15s ease; }
.dv-search input:focus { border-color:var(--gold-dark); }
.dv-search input::placeholder { color:var(--text-4); }

/* warning / info banners */
.dv-warn { display:flex; align-items:flex-start; gap:11px; padding:13px 16px; border-radius:var(--radius-lg); margin-bottom:14px; }
.dv-warn.red { background:rgba(160,45,34,.06); border:1px solid rgba(160,45,34,.18); }
.dv-warn.gold { background:var(--badge-bg-gold); border:1px solid rgba(192,154,82,.25); }
.dv-warn svg { flex-shrink:0; margin-top:2px; }
.dv-warn.red svg { color:var(--red); }
.dv-warn.gold svg { color:var(--gold-dark); }
.dv-warn strong { display:block; font-size:12.5px; font-weight:700; color:var(--text); margin-bottom:2px; }
.dv-warn.red strong { color:var(--red); }
.dv-warn.gold strong { color:var(--gold-dark); }
.dv-warn span { font-size:12px; color:var(--text-2); line-height:1.55; }
.dv-info { display:flex; align-items:flex-start; gap:10px; padding:12px 16px; background:rgba(80,120,190,.06); border:1px solid rgba(80,120,190,.18); border-radius:var(--radius-lg); margin-bottom:16px; font-size:12.5px; color:var(--text-2); line-height:1.5; }
.dv-info svg { color:#5078be; flex-shrink:0; margin-top:1px; }

/* section head */
.dv-section-head { display:flex; align-items:center; gap:10px; font-family:var(--font-serif); font-size:16px; font-weight:600; color:var(--text); margin-bottom:14px; }
.dv-section-badge { font-family:var(--font-sans); font-size:9.5px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; padding:3px 9px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-3); }
.dv-section-badge.red { background:var(--red-light); color:var(--red); }

/* empty state */
.dv-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:54px 24px; }
.dv-empty-ico { width:54px; height:54px; border-radius:var(--radius-sm); background:var(--surface-3); color:var(--text-4); display:flex; align-items:center; justify-content:center; margin-bottom:16px; }
.dv-empty-title { font-family:var(--font-serif); font-size:18px; font-weight:600; color:var(--text); margin-bottom:6px; }
.dv-empty-sub { font-size:13px; color:var(--text-4); line-height:1.55; max-width:360px; margin:0 auto; }

@media (max-width:900px) {
  .dv-stats { grid-template-columns:1fr; }
  .dv-header { flex-direction:column; }
  .dv-subtabs { gap:4px; }
}
</style>
