<template>
  <AppLayout :user="user" portal="continuity_steward" activePage="continuity-management" pageTitle="Continuity Management">

    <!-- ════════════════ LIST VIEW ════════════════ -->
    <template v-if="!selected">
      <!-- HEADER -->
      <div class="cm-header">
        <div>
          <h1 class="cm-title">Continuity Management</h1>
          <p class="cm-sub">1 active critical incident for {{ primaryProvider }} requires your attention.</p>
        </div>
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
      </div>

      <!-- STAT CARDS -->
      <div class="cm-stats">
        <div class="cm-stat">
          <div class="cm-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
          <div class="cm-stat-num">{{ stats.active }}</div>
          <div class="cm-stat-label">Active</div>
        </div>
        <div class="cm-stat">
          <div class="cm-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></div>
          <div class="cm-stat-num">{{ stats.awaiting }}</div>
          <div class="cm-stat-label">Awaiting Documentation</div>
        </div>
        <div class="cm-stat">
          <div class="cm-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
          <div class="cm-stat-num">{{ stats.verified }}</div>
          <div class="cm-stat-label">Verified</div>
        </div>
        <div class="cm-stat">
          <div class="cm-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
          <div class="cm-stat-num">{{ stats.closed }}</div>
          <div class="cm-stat-label">Closed This Month</div>
        </div>
      </div>

      <!-- INVITED CS BANNER -->
      <div class="cm-invited-banner">
        <div class="cm-invited-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div class="cm-invited-body">
          <div class="cm-invited-title">Viewing incidents for {{ primaryProvider }}</div>
          <div class="cm-invited-sub">You are an Invited CS linked to one practitioner. Upgrade to Business CS to manage multiple practitioners.</div>
        </div>
        <button type="button" class="btn btn-outline btn-sm cm-invited-cta">Upgrade <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></button>
      </div>

      <!-- VERIFICATION ALERT -->
      <div class="cm-alert">
        <svg class="cm-alert-ico" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <div class="cm-alert-body">
          <div class="cm-alert-title">Critical incident requires your verification</div>
          <div class="cm-alert-sub">{{ primaryProvider }} — Short Term Incapacitation, reported Apr 25, 2026</div>
          <button type="button" class="btn btn-gold btn-sm" @click="openIncident(activeIncident)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Open Incident</button>
        </div>
      </div>

      <!-- TABS -->
      <div class="cm-tabs">
        <button v-for="t in tabs" :key="t.key" type="button" class="cm-tab" :class="{ active: activeTab === t.key }" @click="activeTab = t.key">
          {{ t.label }}
          <span v-if="t.count" class="cm-tab-count">{{ t.count }}</span>
        </button>
      </div>

      <!-- FILTER ROW -->
      <div class="cm-filters">
        <div class="cm-search">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input v-model="search" placeholder="Search incidents..." />
        </div>
        <select v-model="typeFilter" class="form-select cm-select"><option>All Types</option><option>Short-Term Incapacitation</option><option>Permanent Incapacity</option><option>Death</option><option>Missing Person</option><option>Natural Disaster</option></select>
      </div>

      <!-- INCIDENT LIST -->
      <template v-if="visibleIncidents.length">
        <div v-for="inc in visibleIncidents" :key="inc.type + inc.date" class="cm-card">
          <div class="cm-card-head">
            <div class="cm-card-title">
              <strong>{{ inc.provider }}</strong>
              <span class="cm-card-type">{{ inc.type }}</span>
              <span class="cm-status" :class="'status-' + inc.status">{{ inc.statusLabel }}</span>
            </div>
            <span class="cm-card-date">{{ inc.date }}</span>
          </div>
          <p class="cm-card-desc">{{ inc.desc }}</p>
          <div class="cm-card-foot">
            <button type="button" class="btn btn-gold btn-sm" @click="openIncident(inc)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Open Incident</button>
            <span v-if="inc.docUploaded" class="cm-doc-chip"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Documentation uploaded</span>
            <button v-if="inc.hasRecord" type="button" class="btn btn-outline btn-sm" @click="openIncident(inc)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> View Record</button>
          </div>
        </div>
      </template>

      <div v-else class="cm-empty">
        <div class="cm-empty-ico"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
        <div class="cm-empty-text">No {{ activeTab === 'verified' ? 'verified' : 'closed' }} incidents to show</div>
      </div>
    </template>

    <!-- ════════════════ DETAIL VIEW ════════════════ -->
    <template v-else>
      <!-- BREADCRUMB -->
      <div class="cd-breadcrumb">
        <a href="#" @click.prevent="selected = null">Continuity Management</a>
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        <span>{{ selected.type }} — {{ selected.provider }}</span>
      </div>

      <!-- DETAIL HEADER -->
      <div class="cd-header">
        <div>
          <h1 class="cd-title">{{ selected.type }}</h1>
          <div class="cd-header-meta">
            <span class="cm-status" :class="'status-' + selected.status">{{ selected.statusLabel }}</span>
            <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Reported {{ selected.detail.reportedDate }}</span>
            <span v-if="selected.detail.verifiedDate"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Verified {{ selected.detail.verifiedDate }}</span>
          </div>
        </div>
        <div class="cd-header-actions">
          <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg> {{ selected.status === 'closed' ? 'Re-open' : 'Close Incident' }}</button>
          <button type="button" class="btn-icon" data-tip="Activity"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></button>
        </div>
      </div>

      <!-- DETAIL GRID -->
      <div class="cd-grid">
        <!-- MAIN -->
        <div class="cd-main">
          <!-- Incident Summary -->
          <div class="cd-panel">
            <div class="cd-panel-head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Incident Summary</div>
            <div class="cd-panel-body">
              <div class="cd-summary-grid">
                <div><div class="cd-field-label">Practitioner</div><div class="cd-field-val gold">{{ selected.provider }}</div><div class="cd-field-sub">{{ selected.detail.practitionerCred }}</div></div>
                <div><div class="cd-field-label">Incident Type</div><div class="cd-field-val">{{ selected.type }}</div></div>
                <div><div class="cd-field-label">Reported</div><div class="cd-field-val">{{ selected.detail.reportedFull }}</div></div>
                <div><div class="cd-field-label">Status</div><div><span class="cm-status" :class="'status-' + selected.status">{{ selected.statusLabel }}</span></div></div>
                <div><div class="cd-field-label">Verified</div><div class="cd-field-val">{{ selected.detail.verifiedFull }}</div></div>
              </div>
              <div class="cd-divider"></div>
              <div class="cd-field-label">Support Steward Report</div>
              <p class="cd-text">{{ selected.detail.supportReport }}</p>
              <div class="cd-divider"></div>
              <div class="cd-field-label">Contact Attempts</div>
              <div class="cd-contact"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.81.36 1.6.7 2.34a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.74-1.74a2 2 0 0 1 2.11-.45c.74.34 1.53.57 2.34.7A2 2 0 0 1 22 16.92z"/></svg> {{ selected.detail.contactAttempt }}</div>
              <template v-if="selected.detail.verificationNote">
                <div class="cd-divider"></div>
                <div class="cd-field-label">Verification Note</div>
                <p class="cd-text">{{ selected.detail.verificationNote }}</p>
              </template>
            </div>
          </div>

          <!-- Documentation -->
          <div class="cd-panel">
            <div class="cd-panel-head-row">
              <div class="cd-panel-head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Documentation</div>
              <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Attach</button>
            </div>
            <div class="cd-panel-body">
              <div class="cd-field-label">Uploaded</div>
              <a v-for="doc in selected.detail.docs" :key="doc" href="#" class="cd-doc-row"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> {{ doc }}</a>
            </div>
          </div>

          <!-- Generated Task Checklist -->
          <div class="cd-panel">
            <div class="cd-panel-head-row">
              <div class="cd-panel-head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg> Generated Task Checklist</div>
              <a href="/continuity-steward/my-tasks" class="btn btn-outline btn-sm">View My Tasks</a>
            </div>
            <div class="cd-panel-body">
              <p class="cd-text gold-link">Tasks were generated from the plan. <a href="/continuity-steward/my-tasks">View them in My Tasks.</a></p>
            </div>
          </div>

          <!-- Activity Timeline -->
          <div class="cd-panel">
            <div class="cd-panel-head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Activity Timeline</div>
            <div class="cd-panel-body">
              <div class="cd-timeline">
                <div v-for="(ev, i) in selected.detail.timeline" :key="i" class="cd-tl-item">
                  <div class="cd-tl-dot"></div>
                  <div class="cd-tl-content">
                    <div class="cd-tl-title">{{ ev.title }}</div>
                    <div class="cd-tl-desc">{{ ev.desc }}</div>
                    <div class="cd-tl-time">{{ ev.time }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ASIDE -->
        <div class="cd-aside">
          <!-- Vault access -->
          <div class="cd-vault-card">
            <div class="cd-vault-head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg> Vault access active</div>
            <div class="cd-vault-sub">The document vault is unlocked for this practitioner.</div>
            <button type="button" class="btn btn-gold btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Open Vault</button>
          </div>

          <!-- Reporting Support Steward -->
          <div class="cd-panel">
            <div class="cd-panel-head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Reporting Support Steward</div>
            <div class="cd-panel-body">
              <div class="cd-ss-card">
                <div class="cd-ss-av">{{ selected.detail.supportSteward.initials }}</div>
                <div class="cd-ss-info">
                  <div class="cd-ss-name">{{ selected.detail.supportSteward.name }}</div>
                  <div class="cd-ss-role">Support Steward</div>
                  <div class="cd-ss-line"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg> {{ selected.detail.supportSteward.email }}</div>
                  <div class="cd-ss-line"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.81.36 1.6.7 2.34a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.74-1.74a2 2 0 0 1 2.11-.45c.74.34 1.53.57 2.34.7A2 2 0 0 1 22 16.92z"/></svg> {{ selected.detail.supportSteward.phone }}</div>
                </div>
              </div>
              <button type="button" class="btn btn-outline btn-sm cd-block-btn"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Send Message</button>
            </div>
          </div>

          <!-- Continuity Plan -->
          <div class="cd-panel">
            <div class="cd-panel-head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Continuity Plan</div>
            <div class="cd-panel-body">
              <p class="cd-text">{{ selected.detail.plan }}</p>
              <button type="button" class="btn btn-outline btn-sm cd-block-btn"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> View Continuity Plan</button>
            </div>
          </div>
        </div>
      </div>
    </template>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
defineProps({ user: Object });

const primaryProvider = 'Dr. Sarah Johnson';
const stats = { active: 1, awaiting: 0, verified: 0, closed: 0 };

const search = ref('');
const typeFilter = ref('All Types');
const activeTab = ref('active');
const selected = ref(null);

const supportSteward = { initials: 'LJ', name: 'Linda Johnson', email: 'linda.johnson@gmail.com', phone: '(415) 555-0142' };

const incidents = [
  {
    provider: 'Dr. Sarah Johnson', type: 'Short-Term Incapacitation', status: 'active', statusLabel: 'Active', date: 'Apr 25, 2026',
    desc: 'Dr. Johnson was admitted for an emergency appendectomy on Apr 24 and is expected to be out of practice for roughly two weeks. She asked me t...',
    docUploaded: true,
    detail: {
      practitionerCred: 'PhD, LMFT', reportedDate: 'Apr 25, 2026', verifiedDate: '',
      reportedFull: 'Apr 25, 2026 · 10:20 AM', verifiedFull: 'Pending verification',
      supportReport: 'Dr. Johnson was admitted for an emergency appendectomy on Apr 24 and is expected to be out of practice for roughly two weeks. She asked me to notify her clients and coordinate temporary coverage.',
      contactAttempt: 'Phone · Apr 25, 9:50 AM · confirmed safe',
      verificationNote: '',
      docs: ['Hospital admission note (Apr 25)'],
      supportSteward,
      plan: 'Plan for Dr. Sarah Johnson · Signed Jun 15, 2024',
      timeline: [
        { title: 'On standby for active incident', desc: 'Primary steward engaged; standing by as alternate for Dr. Sarah Johnson.', time: 'Apr 25, 2026 · 10:50 AM' },
        { title: 'Reviewed Continuity Plan — Dr. Sarah Johnson', desc: 'Confirmed alternate-steward responsibilities for the 2026 plan.', time: 'Mar 2, 2026 · 3:00 PM' },
      ],
    },
  },
  {
    provider: 'Dr. Sarah Johnson', type: 'Natural Disaster', status: 'closed', statusLabel: 'Closed', date: 'Nov 8, 2025',
    desc: "Regional flooding closed the practice facility for several days. Confirmed Dr. Johnson's safety and assessed impact on records and schedulin...",
    hasRecord: true,
    detail: {
      practitionerCred: 'PhD, LMFT', reportedDate: 'Nov 8, 2025', verifiedDate: 'Nov 8, 2025',
      reportedFull: 'Nov 8, 2025 · 4:40 PM', verifiedFull: 'Nov 8, 2025 · 6:00 PM',
      supportReport: "Regional flooding closed the practice facility for several days. Confirmed Dr. Johnson's safety and assessed impact on records and scheduling.",
      contactAttempt: 'Phone · Nov 8, 4:10 PM · confirmed safe',
      verificationNote: 'Facility inaccessible 3 days; coordinated temporary coverage.',
      docs: ['County flood advisory (Nov 8)'],
      supportSteward,
      plan: 'Plan for Dr. Sarah Johnson · Signed Jun 15, 2024',
      timeline: [
        { title: 'On standby for active incident', desc: 'Primary steward engaged; standing by as alternate for Dr. Sarah Johnson.', time: 'Apr 25, 2026 · 10:50 AM' },
        { title: 'Reviewed Continuity Plan — Dr. Sarah Johnson', desc: 'Confirmed alternate-steward responsibilities for the 2026 plan.', time: 'Mar 2, 2026 · 3:00 PM' },
      ],
    },
  },
];

const activeIncident = incidents.find(i => i.status === 'active');

const tabs = computed(() => [
  { key: 'active', label: 'Active Incidents', count: incidents.filter(i => i.status === 'active').length },
  { key: 'verified', label: 'Verified', count: incidents.filter(i => i.status === 'verified').length },
  { key: 'closed', label: 'Closed', count: 0 },
]);

const visibleIncidents = computed(() => {
  let list = incidents.filter(i => i.status === activeTab.value);
  if (typeFilter.value !== 'All Types') list = list.filter(i => i.type === typeFilter.value);
  if (search.value.trim()) { const q = search.value.toLowerCase(); list = list.filter(i => i.provider.toLowerCase().includes(q) || i.type.toLowerCase().includes(q) || i.desc.toLowerCase().includes(q)); }
  return list;
});

function openIncident(inc) {
  selected.value = inc;
  if (typeof window !== 'undefined') window.scrollTo({ top: 0 });
}
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════════
   CS — CONTINUITY MANAGEMENT
   ════════════════════════════════════════════════════════════════ */

/* HEADER */
.cm-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:26px 30px; margin-bottom:16px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.cm-title { font-family:var(--font-serif); font-size:34px; font-weight:600; letter-spacing:-0.5px; color:var(--text); margin:0; line-height:1.05; }
.cm-sub { font-size:13.5px; color:var(--text-3); margin-top:8px; }

/* STAT CARDS */
.cm-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px; }
.cm-stat { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px 18px; box-shadow:var(--shadow-xs); display:grid; grid-template-columns:auto 1fr; grid-template-rows:auto auto; column-gap:12px; align-items:center; }
.cm-stat-ico { grid-row:1 / 3; width:34px; height:34px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cm-stat-num { font-family:var(--font-serif); font-size:24px; font-weight:700; line-height:1; color:var(--text); }
.cm-stat-label { font-size:11.5px; color:var(--text-3); margin-top:3px; }

/* INVITED BANNER */
.cm-invited-banner { display:flex; align-items:center; gap:16px; padding:16px 20px; background:linear-gradient(95deg,var(--badge-bg-gold) 0%,var(--surface) 80%); border:1px solid rgba(192,154,82,.30); border-radius:var(--radius-lg); box-shadow:var(--shadow-sm); margin-bottom:16px; }
.cm-invited-ico { width:38px; height:38px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cm-invited-body { flex:1; min-width:0; }
.cm-invited-title { font-size:13px; font-weight:700; color:var(--text); margin-bottom:3px; }
.cm-invited-sub { font-size:12px; color:var(--text-3); line-height:1.5; }
.cm-invited-cta { flex-shrink:0; white-space:nowrap; }

/* VERIFICATION ALERT */
.cm-alert { display:flex; gap:14px; padding:18px 22px; background:rgba(160,45,34,.06); border:1px solid rgba(160,45,34,.18); border-radius:var(--radius-lg); margin-bottom:18px; }
.cm-alert-ico { color:var(--red); flex-shrink:0; margin-top:1px; }
.cm-alert-body { flex:1; min-width:0; }
.cm-alert-title { font-size:14px; font-weight:700; color:var(--red); margin-bottom:4px; }
.cm-alert-sub { font-size:12.5px; color:var(--text-2); margin-bottom:12px; }

/* TABS */
.cm-tabs { display:inline-flex; align-items:center; gap:2px; padding:4px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-full); margin-bottom:16px; flex-wrap:wrap; }
.cm-tab { display:inline-flex; align-items:center; gap:7px; padding:8px 18px; font-size:12.5px; font-weight:600; color:var(--text-3); background:none; border:none; border-radius:var(--radius-full); cursor:pointer; transition:all .15s ease; white-space:nowrap; }
.cm-tab:hover { color:var(--text); }
.cm-tab.active { background:var(--gold-dark); color:var(--text-inverted); }
.cm-tab-count { font-size:10px; font-weight:700; min-width:16px; height:16px; padding:0 5px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-3); display:inline-flex; align-items:center; justify-content:center; }
.cm-tab.active .cm-tab-count { background:rgba(255,255,255,.25); color:var(--text-inverted); }

/* FILTERS */
.cm-filters { display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap; }
.cm-search { position:relative; flex:1; min-width:220px; }
.cm-search svg { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--text-4); pointer-events:none; }
.cm-search input { width:100%; padding:10px 13px 10px 36px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; transition:border-color .15s ease; }
.cm-search input:focus { border-color:var(--gold-dark); }
.cm-search input::placeholder { color:var(--text-4); }
.cm-select { width:auto; min-width:170px; }

/* INCIDENT CARD (list) */
.cm-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); padding:18px 22px; margin-bottom:12px; transition:box-shadow .18s ease,border-color .18s ease; }
.cm-card:hover { box-shadow:var(--shadow-sm); border-color:var(--border-dark); }
.cm-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; }
.cm-card-title { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.cm-card-title strong { font-family:var(--font-serif); font-size:16px; font-weight:600; color:var(--text); }
.cm-card-type { font-size:13px; color:var(--text-3); }
.cm-status { font-size:9.5px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; padding:3px 9px; border-radius:var(--radius-full); }
.cm-status.status-active { background:var(--badge-bg-gold); color:var(--gold-dark); }
.cm-status.status-closed { background:var(--green-light); color:var(--green-dark); }
.cm-status.status-verified { background:rgba(80,120,190,.12); color:#4a6ba8; }
.cm-card-date { font-size:11.5px; color:var(--text-4); flex-shrink:0; white-space:nowrap; }
.cm-card-desc { font-size:13px; color:var(--text-2); line-height:1.6; margin:10px 0 14px; }
.cm-card-foot { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.cm-doc-chip { display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:600; color:var(--green-dark); }

/* LIST EMPTY STATE */
.cm-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:70px 24px; text-align:center; }
.cm-empty-ico { width:44px; height:44px; border-radius:50%; color:var(--text-4); display:flex; align-items:center; justify-content:center; margin-bottom:14px; }
.cm-empty-text { font-size:14px; color:var(--text-3); }

/* ══════════ DETAIL VIEW ══════════ */
.cd-breadcrumb { display:flex; align-items:center; gap:8px; font-size:12.5px; color:var(--text-3); margin-bottom:14px; }
.cd-breadcrumb a { color:var(--gold-dark); font-weight:600; text-decoration:none; }
.cd-breadcrumb a:hover { text-decoration:underline; }
.cd-breadcrumb svg { color:var(--text-4); }

.cd-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:26px 30px; margin-bottom:18px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.cd-title { font-family:var(--font-serif); font-size:32px; font-weight:600; letter-spacing:-0.5px; color:var(--text); margin:0; line-height:1.05; }
.cd-header-meta { display:flex; align-items:center; gap:16px; margin-top:12px; flex-wrap:wrap; }
.cd-header-meta span { display:inline-flex; align-items:center; gap:6px; font-size:12px; color:var(--text-3); }
.cd-header-meta span svg { color:var(--text-4); }
.cd-header-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; }

.cd-grid { display:grid; grid-template-columns:1fr 340px; gap:18px; align-items:start; }
.cd-main { display:flex; flex-direction:column; gap:18px; }
.cd-aside { display:flex; flex-direction:column; gap:18px; }

.cd-panel { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; }
.cd-panel-head { display:flex; align-items:center; gap:9px; padding:16px 22px; font-size:14px; font-weight:700; color:var(--text); border-bottom:1px solid var(--border); }
.cd-panel-head svg { color:var(--gold-dark); }
.cd-panel-head-row { display:flex; align-items:center; justify-content:space-between; gap:12px; border-bottom:1px solid var(--border); padding-right:18px; }
.cd-panel-head-row .cd-panel-head { border-bottom:none; flex:1; }
.cd-panel-body { padding:18px 22px; }

.cd-summary-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px 24px; }
.cd-field-label { font-size:9.5px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); margin-bottom:5px; }
.cd-field-val { font-size:13.5px; font-weight:600; color:var(--text); }
.cd-field-val.gold { color:var(--gold-dark); }
.cd-field-sub { font-size:11.5px; color:var(--text-4); margin-top:1px; }
.cd-divider { height:1px; background:var(--border); margin:18px 0; }
.cd-text { font-size:13px; color:var(--text-2); line-height:1.65; margin:0; }
.cd-text.gold-link a, .cd-doc-row { color:var(--gold-dark); }
.cd-text a { color:var(--gold-dark); font-weight:600; text-decoration:none; }
.cd-text a:hover { text-decoration:underline; }
.cd-contact { display:flex; align-items:center; gap:8px; font-size:13px; color:var(--text-2); }
.cd-contact svg { color:var(--text-4); }
.cd-doc-row { display:inline-flex; align-items:center; gap:8px; font-size:13px; font-weight:600; text-decoration:none; margin-top:4px; }
.cd-doc-row:hover { text-decoration:underline; }

/* timeline */
.cd-timeline { position:relative; padding-left:6px; }
.cd-tl-item { display:flex; gap:14px; padding-bottom:18px; position:relative; }
.cd-tl-item:last-child { padding-bottom:0; }
.cd-tl-item:not(:last-child)::before { content:''; position:absolute; left:4px; top:14px; bottom:0; width:1.5px; background:var(--border); }
.cd-tl-dot { width:9px; height:9px; border-radius:50%; background:var(--gold-dark); flex-shrink:0; margin-top:4px; position:relative; z-index:1; }
.cd-tl-title { font-size:13px; font-weight:700; color:var(--text); }
.cd-tl-desc { font-size:12.5px; color:var(--text-3); line-height:1.5; margin-top:2px; }
.cd-tl-time { font-size:11px; color:var(--text-4); margin-top:4px; }

/* aside: vault */
.cd-vault-card { background:rgba(80,120,190,.07); border:1px solid rgba(80,120,190,.20); border-radius:var(--radius-lg); padding:18px 20px; }
.cd-vault-head { display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; color:var(--text); }
.cd-vault-head svg { color:#5078be; }
.cd-vault-sub { font-size:12.5px; color:var(--text-3); line-height:1.5; margin:6px 0 14px; }

/* aside: support steward */
.cd-ss-card { background:var(--badge-bg-gold); border:1px solid rgba(192,154,82,.25); border-radius:var(--radius); padding:14px 16px; display:flex; gap:12px; }
.cd-ss-av { width:38px; height:38px; border-radius:50%; background:var(--surface-3); color:var(--text-3); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; font-size:13px; flex-shrink:0; }
.cd-ss-info { flex:1; min-width:0; }
.cd-ss-name { font-size:14px; font-weight:700; color:var(--text); }
.cd-ss-role { font-size:12px; color:var(--text-3); margin-bottom:6px; }
.cd-ss-line { display:flex; align-items:center; gap:7px; font-size:12px; color:var(--gold-dark); margin-top:3px; }
.cd-ss-line svg { color:var(--text-4); flex-shrink:0; }
.cd-block-btn { width:100%; margin-top:14px; }

@media (max-width:1000px) { .cd-grid { grid-template-columns:1fr; } }
@media (max-width:900px) { .cm-stats { grid-template-columns:1fr 1fr; } .cm-header, .cd-header { flex-direction:column; } .cd-summary-grid { grid-template-columns:1fr; } }
</style>
