<template>
  <AppLayout :user="user" portal="support_steward" activePage="continuity-stewards" pageTitle="Continuity Stewards" :has-emergency="true">

    <!-- HEADER -->
    <div class="cw-header">
      <div>
        <div class="cw-eyebrow">Support Steward</div>
        <h1 class="cw-title">Continuity Stewards</h1>
        <p class="cw-sub">The Continuity Stewards assigned to the practitioners you support.</p>
      </div>
      <div class="cw-header-actions">
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
        <button type="button" class="btn btn-primary btn-sm" @click="openExecutor"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg> Add Executor</button>
      </div>
    </div>

    <!-- STAT CARDS -->
    <div class="cw-stats">
      <div class="cw-stat"><div class="cw-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div><div class="cw-stat-num">{{ allStewards.length }}</div><div class="cw-stat-label">Total Continuity Stewards</div></div>
      <div class="cw-stat"><div class="cw-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div class="cw-stat-num">{{ counts.active }}</div><div class="cw-stat-label">Active</div></div>
      <div class="cw-stat"><div class="cw-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div><div class="cw-stat-num">{{ groups.length }}</div><div class="cw-stat-label">Across {{ groups.length }} Practitioners</div></div>
      <div class="cw-stat"><div class="cw-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div class="cw-stat-num">{{ counts.pending }}</div><div class="cw-stat-label">Pending</div></div>
    </div>

    <!-- FILTERS -->
    <div class="cw-filters">
      <div class="cw-search">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" placeholder="Search by name or organization..." />
      </div>
      <select v-model="practFilter" class="form-select cw-select"><option>All Practitioners</option><option v-for="g in groups" :key="g.practitioner">{{ g.practitioner }}</option></select>
      <select v-model="statusFilter" class="form-select cw-select"><option>All Statuses</option><option>Active</option><option>Pending</option><option>Inactive</option></select>
      <select v-model="sortBy" class="form-select cw-select"><option>Sort: Practitioner</option><option>Sort: Name</option><option>Sort: Status</option></select>
    </div>

    <!-- TABS -->
    <div class="cw-tabs">
      <button v-for="t in tabs" :key="t.key" type="button" class="cw-tab" :class="{ active: tab === t.key }" @click="tab = t.key">{{ t.label }}<span v-if="t.count" class="cw-tab-count">{{ t.count }}</span></button>
    </div>

    <!-- GROUPS -->
    <template v-for="g in visibleGroups" :key="g.practitioner">
      <div class="cw-group-head">{{ g.practitioner }} <span class="cw-group-count">{{ g.stewards.length }} {{ g.stewards.length === 1 ? 'Steward' : 'Stewards' }}</span></div>
      <div class="cw-grid">
        <div v-for="s in g.stewards" :key="s.name + s.role" class="cw-card">
          <div class="cw-card-top">
            <div class="cw-av">{{ s.initials }}</div>
            <div class="cw-card-info">
              <div class="cw-card-name">{{ s.name }}</div>
              <div class="cw-card-role">{{ s.role }} <span class="cw-badge" :class="'b-' + s.status">{{ statusLabel(s.status) }}</span></div>
              <div class="cw-card-org">{{ s.org }}</div>
            </div>
          </div>
          <div class="cw-card-rows">
            <div class="cw-row"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg> {{ s.scope }}</div>
            <div v-if="s.date" class="cw-row"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> {{ s.invited ? 'Invited ' + s.date : s.date }}</div>
            <div class="cw-row"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg> {{ s.email }}</div>
            <div class="cw-row"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.81.36 1.6.7 2.34a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.74-1.74a2 2 0 0 1 2.11-.45c.74.34 1.53.57 2.34.7A2 2 0 0 1 22 16.92z"/></svg> {{ s.phone }}</div>
          </div>
          <div class="cw-card-foot">
            <button type="button" class="btn-icon" data-tip="View"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
            <button type="button" class="btn-icon" data-tip="Message"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></button>
            <button type="button" class="btn-icon" data-tip="Notify Practitioner" @click="openNotify(s, g)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></button>
          </div>
        </div>
      </div>
    </template>

    <!-- ACTIVATION REFERENCE -->
    <div class="cw-ref-eyebrow">Activation Reference</div>
    <h2 class="cw-ref-title">Task List 4 — Unresponsive or Unavailable Continuity Steward</h2>
    <p class="cw-ref-desc">If your designated Continuity Steward is unreachable when a critical incident occurs, follow these steps to escalate and maintain continuity until a qualified steward can engage.</p>

    <div class="cw-ref-card">
      <div class="cw-ref-head">
        <div>
          <div class="cw-ref-head-title">Steps to Take When Your Continuity Steward Is Unresponsive</div>
          <div class="cw-ref-head-sub">Complete in order — document each step with timestamp</div>
        </div>
        <span class="cw-ref-tag"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Escalation Protocol</span>
      </div>
      <div v-for="(step, i) in steps" :key="i" class="cw-step">
        <div class="cw-step-num">{{ i + 1 }}</div>
        <div class="cw-step-body">
          <div class="cw-step-title"><span class="cw-step-ico" v-html="step.icon"></span> {{ step.title }}</div>
          <div class="cw-step-desc">{{ step.desc }}</div>
        </div>
      </div>
      <div class="cw-ref-foot">This task list is for reference during a critical moment. All actions should be documented and reported to the Aegis Team as soon as a Continuity Steward is reachable.</div>
    </div>

    <!-- NOTIFY PRACTITIONER MODAL -->
    <Modal v-model="showNotify" title="Notify Practitioner">
      <div class="cw-warn">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <span v-if="notifyTarget">Flagging <strong>{{ notifyTarget.name }}</strong> as unresponsive to <strong>{{ notifyTarget.practitioner }}</strong>.</span>
      </div>
      <p class="cw-modal-text">This will notify the practitioner and other Continuity Stewards on the plan. It does not change the steward's designation — only the practitioner can do that.</p>
      <div class="cw-field">
        <label class="cw-field-label">What did you observe? <span class="cw-req">*</span></label>
        <textarea v-model="notifyText" maxlength="500" class="cw-textarea" rows="4" placeholder="Describe your attempts to reach the Continuity Steward and what you observed..."></textarea>
        <div class="cw-textarea-foot"><span class="cw-count">{{ notifyText.length }} / 500</span></div>
        <div class="cw-field-help">Be specific — this becomes part of the audit record.</div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showNotify = false">Cancel</button>
        <button type="button" class="btn btn-gold btn-sm" @click="showNotify = false"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg> Send Notification</button>
      </template>
    </Modal>

    <!-- ADD EXECUTOR MODAL -->
    <Modal v-model="showExecutor" title="Add Executor" subtitle="Designate a backup person to act on your behalf if you are unavailable">
      <div class="cw-field">
        <label class="cw-field-label">Find Person <span class="cw-req">*</span></label>
        <input v-model="exec.person" class="cw-input" placeholder="Search by name or email..." />
        <div class="cw-field-help">Search Aegis users by name or email address.</div>
      </div>
      <div class="cw-field">
        <label class="cw-field-label">Relationship to Provider <span class="cw-req">*</span></label>
        <select v-model="exec.relationship" class="form-select"><option value="">Select relationship...</option><option>Spouse / Partner</option><option>Family Member</option><option>Colleague</option><option>Attorney</option><option>Other</option></select>
      </div>
      <div class="cw-field">
        <label class="cw-field-label">Role <span class="cw-req">*</span></label>
        <select v-model="exec.role" class="form-select"><option>Primary Executor</option><option>Alternate Executor</option></select>
      </div>
      <div class="cw-field">
        <label class="cw-field-label">Responsibilities</label>
        <textarea v-model="exec.responsibilities" class="cw-textarea" rows="3" placeholder="Describe what this person is responsible for if you are unavailable..."></textarea>
        <div class="cw-field-help">Be specific — this person will act on your behalf during a critical moment.</div>
      </div>
      <div class="cw-field">
        <label class="cw-field-label">Triggering Event <span class="cw-req">*</span></label>
        <input v-model="exec.trigger" class="cw-input" placeholder="e.g. I am hospitalized and unable to communicate..." />
        <div class="cw-field-help">Describe the circumstance under which this executor should act.</div>
      </div>
      <div class="cw-field">
        <label class="cw-field-label">Additional Notes</label>
        <textarea v-model="exec.notes" class="cw-textarea" rows="2" placeholder="Any other details this executor should know..."></textarea>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="showExecutor = false">Cancel</button>
        <button type="button" class="btn btn-gold btn-sm" @click="showExecutor = false"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg> Add Executor</button>
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
const practFilter = ref('All Practitioners');
const statusFilter = ref('All Statuses');
const sortBy = ref('Sort: Practitioner');
const tab = ref('all');

const groups = [
  {
    practitioner: 'Dr. Michael Torres',
    stewards: [
      { initials: 'TC', name: 'Dr. Thomas Chen', role: 'Alternate Continuity Steward', status: 'pending', org: 'Chen Continuity Group', scope: 'Practice succession, clinical handoff', date: 'Mar 1, 2026', invited: true, email: 'thomas@chencontinuity.com', phone: '(415) 555-0211' },
      { initials: 'SJ', name: 'Dr. Sarah Johnson', role: 'Primary Continuity Steward', status: 'active', org: 'Lotus Psychology Group', scope: 'Trauma & EMDR, Family Systems', date: 'Nov 9, 2025', invited: false, email: 'sarah.johnson@lotuspsychology.com', phone: '(415) 555-0124' },
      { initials: 'MC', name: 'Marcus Chen', role: 'Primary Continuity Steward', status: 'active', org: 'Chen Practice Solutions', scope: 'Healthcare succession, licensing compliance', date: 'Nov 20, 2025', invited: false, email: 'marcus@chenpracticesolutions.com', phone: '(510) 555-0189' },
    ],
  },
  {
    practitioner: 'Dr. Rachel Okafor',
    stewards: [
      { initials: 'LR', name: 'Dr. Laura Reyes', role: 'Alternate Continuity Steward', status: 'pending', org: 'Reyes Continuity', scope: 'Records transfer, patient notification', date: 'Apr 10, 2026', invited: true, email: 'laura.reyes@reyescontinuity.net', phone: '(213) 555-0298' },
    ],
  },
  {
    practitioner: 'Dr. Sarah Johnson',
    stewards: [
      { initials: 'PR', name: 'Dr. Priya Raman', role: 'Alternate Continuity Steward', status: 'active', org: 'Continuity Partners', scope: 'Clinical record transfer, patient handoff', date: 'Feb 16, 2026', invited: false, email: 'priya.raman@continuitypartners.net', phone: '(415) 555-0167' },
      { initials: 'TC', name: 'Dr. Thomas Chen', role: 'Alternate Continuity Steward', status: 'pending', org: 'Chen Continuity Group', scope: 'Practice succession, clinical handoff', date: 'Jan 10, 2026', invited: true, email: 'thomas@chencontinuity.com', phone: '(415) 555-0211' },
      { initials: 'MC', name: 'Marcus Chen', role: 'Primary Continuity Steward', status: 'active', org: 'Chen Practice Solutions', scope: 'Healthcare succession, licensing compliance', date: 'Feb 15, 2026', invited: false, email: 'marcus@chenpracticesolutions.com', phone: '(510) 555-0189' },
      { initials: 'AR', name: 'Dr. Amelia Rodriguez', role: 'Continuity Steward', status: 'declined', org: 'Continuity Partners', scope: 'Clinical record transfer, patient handoff', date: '', invited: false, email: 'amelia.rodriguez@continuitypartners.net', phone: '(415) 555-0322' },
      { initials: 'LR', name: 'Dr. Laura Reyes', role: 'Continuity Steward', status: 'incoming', org: 'Reyes Continuity', scope: 'Records transfer, patient notification', date: '', invited: false, email: 'laura.reyes@reyescontinuity.net', phone: '(213) 555-0298' },
    ],
  },
];

const allStewards = computed(() => groups.flatMap(g => g.stewards));
const counts = computed(() => ({
  active: allStewards.value.filter(s => s.status === 'active').length,
  pending: allStewards.value.filter(s => s.status === 'pending').length,
  inactive: allStewards.value.filter(s => s.status === 'declined' || s.status === 'incoming').length,
}));

const tabs = computed(() => [
  { key: 'all', label: 'All', count: 0 },
  { key: 'active', label: 'Active', count: counts.value.active },
  { key: 'pending', label: 'Pending', count: counts.value.pending },
  { key: 'inactive', label: 'Inactive', count: counts.value.inactive },
]);

function statusMatch(s) {
  if (tab.value === 'active') return s.status === 'active';
  if (tab.value === 'pending') return s.status === 'pending';
  if (tab.value === 'inactive') return s.status === 'declined' || s.status === 'incoming';
  return true;
}

const visibleGroups = computed(() => groups
  .filter(g => practFilter.value === 'All Practitioners' || g.practitioner === practFilter.value)
  .map(g => ({ ...g, stewards: g.stewards.filter(s => {
    if (!statusMatch(s)) return false;
    if (search.value.trim()) { const q = search.value.toLowerCase(); return s.name.toLowerCase().includes(q) || s.org.toLowerCase().includes(q); }
    return true;
  }) }))
  .filter(g => g.stewards.length > 0)
);

function statusLabel(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

const sa = 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';
const steps = [
  { title: 'Attempt contact via all available channels', desc: 'Call, text, and email the Continuity Steward. Try all contact methods on file. Wait at least 15 minutes between attempts before escalating.', icon: `<svg viewBox="0 0 24 24" ${sa}><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.81.36 1.6.7 2.34a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.74-1.74a2 2 0 0 1 2.11-.45c.74.34 1.53.57 2.34.7A2 2 0 0 1 22 16.92z"/></svg>` },
  { title: 'Contact the Alternate Continuity Steward (if designated)', desc: "Check the practitioner's Continuity Plan for an alternate CS. Contact them immediately and explain the situation.", icon: `<svg viewBox="0 0 24 24" ${sa}><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>` },
  { title: 'Notify the Aegis Team', desc: 'Contact Aegis directly to report the unresponsive Continuity Steward. Aegis will assign a qualified steward or provide interim guidance.', icon: `<svg viewBox="0 0 24 24" ${sa}><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>` },
  { title: 'Document all contact attempts', desc: 'Record the time, method, and outcome of every contact attempt. This becomes part of the permanent incident record.', icon: `<svg viewBox="0 0 24 24" ${sa}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>` },
  { title: 'Preserve practice access and records', desc: 'Do not allow access to practice records, client files, or the Document Vault until a qualified Continuity Steward is confirmed and active.', icon: `<svg viewBox="0 0 24 24" ${sa}><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>` },
  { title: "Notify the practitioner's emergency contact (if applicable)", desc: 'If the incident involves incapacitation or death and no CS is reachable, contact the emergency contact listed in the Continuity Plan.', icon: `<svg viewBox="0 0 24 24" ${sa}><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>` },
  { title: 'Do not act beyond your designated responsibilities', desc: 'Only take actions explicitly authorized in your task list. Do not access client records, make clinical decisions, or communicate with clients on behalf of the practice without CS authorization.', icon: `<svg viewBox="0 0 24 24" ${sa}><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>` },
];

const showNotify = ref(false);
const notifyTarget = ref(null);
const notifyText = ref('');
function openNotify(s, g) { notifyTarget.value = { name: s.name, practitioner: g.practitioner }; notifyText.value = ''; showNotify.value = true; }

const showExecutor = ref(false);
const exec = reactive({ person: '', relationship: '', role: 'Primary Executor', responsibilities: '', trigger: '', notes: '' });
function openExecutor() { exec.person = ''; exec.relationship = ''; exec.role = 'Primary Executor'; exec.responsibilities = ''; exec.trigger = ''; exec.notes = ''; showExecutor.value = true; }
</script>

<style scoped>
/* ── SS CONTINUITY STEWARDS ── */
.cw-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:24px 30px; margin-bottom:18px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.cw-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.6px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.cw-title { font-family:var(--font-serif); font-size:30px; font-weight:600; letter-spacing:-0.4px; color:var(--text); margin:0; line-height:1.05; }
.cw-sub { font-size:13.5px; color:var(--text-3); margin-top:8px; }
.cw-header-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; flex-wrap:wrap; }

.cw-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px; }
.cw-stat { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px 18px; box-shadow:var(--shadow-xs); display:grid; grid-template-columns:auto 1fr; grid-template-rows:auto auto; column-gap:12px; align-items:center; }
.cw-stat-ico { grid-row:1 / 3; width:34px; height:34px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cw-stat-num { font-family:var(--font-serif); font-size:24px; font-weight:700; line-height:1; color:var(--text); }
.cw-stat-label { font-size:11.5px; color:var(--text-3); margin-top:3px; }

.cw-filters { display:flex; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
.cw-search { position:relative; flex:1; min-width:220px; }
.cw-search svg { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--text-4); pointer-events:none; }
.cw-search input { width:100%; padding:9px 13px 9px 36px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; }
.cw-search input:focus { border-color:var(--gold-dark); }
.cw-search input::placeholder { color:var(--text-4); }
.cw-select { width:auto; min-width:140px; }

.cw-tabs { display:inline-flex; align-items:center; gap:2px; padding:4px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-full); margin-bottom:18px; flex-wrap:wrap; }
.cw-tab { display:inline-flex; align-items:center; gap:7px; padding:8px 16px; font-size:12.5px; font-weight:600; color:var(--text-3); background:none; border:none; border-radius:var(--radius-full); cursor:pointer; transition:all .15s ease; white-space:nowrap; }
.cw-tab:hover { color:var(--text); }
.cw-tab.active { background:var(--gold-dark); color:var(--text-inverted); }
.cw-tab-count { font-size:10px; font-weight:700; min-width:16px; height:16px; padding:0 5px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-3); display:inline-flex; align-items:center; justify-content:center; }
.cw-tab.active .cw-tab-count { background:rgba(255,255,255,.25); color:var(--text-inverted); }

.cw-group-head { display:flex; align-items:center; gap:10px; font-family:var(--font-serif); font-size:16px; font-weight:600; color:var(--text); margin:20px 0 12px; }
.cw-group-count { font-family:var(--font-sans); font-size:10px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; padding:3px 9px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-4); }
.cw-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:6px; }

.cw-card { background:var(--surface); border:1px solid var(--border); border-left:3px solid rgba(192,154,82,.4); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); padding:16px 18px; display:flex; flex-direction:column; }
.cw-card-top { display:flex; align-items:flex-start; gap:12px; }
.cw-av { width:38px; height:38px; border-radius:50%; background:var(--gold-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; font-size:12px; flex-shrink:0; }
.cw-card-info { flex:1; min-width:0; }
.cw-card-name { font-size:14px; font-weight:700; color:var(--text); line-height:1.2; }
.cw-card-role { font-size:11.5px; color:var(--text-3); margin-top:3px; display:flex; align-items:center; gap:7px; flex-wrap:wrap; }
.cw-badge { font-size:8.5px; font-weight:700; letter-spacing:.4px; text-transform:uppercase; padding:2px 7px; border-radius:var(--radius-full); }
.cw-badge.b-active { background:var(--green-light); color:var(--green-dark); }
.cw-badge.b-pending { background:var(--badge-bg-gold); color:var(--gold-dark); }
.cw-badge.b-declined { background:var(--red-light); color:var(--red); }
.cw-badge.b-incoming { background:rgba(80,120,190,.12); color:#4a6ba8; }
.cw-card-org { font-size:11px; color:var(--text-4); margin-top:4px; }
.cw-card-rows { display:flex; flex-direction:column; gap:6px; margin:14px 0; padding-top:12px; border-top:1px solid var(--border); }
.cw-row { display:flex; align-items:center; gap:8px; font-size:11.5px; color:var(--text-3); }
.cw-row svg { color:var(--text-4); flex-shrink:0; }
.cw-card-foot { display:flex; align-items:center; gap:8px; padding-top:12px; border-top:1px solid var(--border); margin-top:auto; }

/* activation reference */
.cw-ref-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.4px; text-transform:uppercase; color:var(--gold-dark); margin:30px 0 8px; }
.cw-ref-title { font-family:var(--font-serif); font-size:20px; font-weight:600; color:var(--text); margin:0 0 8px; }
.cw-ref-desc { font-size:13px; color:var(--text-3); line-height:1.6; max-width:760px; margin:0 0 16px; }
.cw-ref-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; margin-bottom:22px; }
.cw-ref-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:18px 22px; background:var(--surface-2); border-bottom:1px solid var(--border); flex-wrap:wrap; }
.cw-ref-head-title { font-family:var(--font-serif); font-size:16px; font-weight:600; color:var(--text); }
.cw-ref-head-sub { font-size:11.5px; color:var(--text-4); margin-top:2px; }
.cw-ref-tag { display:inline-flex; align-items:center; gap:5px; font-size:9.5px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:var(--gold-dark); flex-shrink:0; }
.cw-step { display:flex; gap:14px; padding:16px 22px; border-bottom:1px solid var(--border); }
.cw-step:last-of-type { border-bottom:none; }
.cw-step-num { width:24px; height:24px; border-radius:50%; background:var(--badge-bg-gold); color:var(--gold-dark); font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cw-step-body { flex:1; min-width:0; }
.cw-step-title { display:flex; align-items:center; gap:8px; font-size:13.5px; font-weight:700; color:var(--text); }
.cw-step-ico { display:inline-flex; color:var(--gold-dark); }
.cw-step-ico :deep(svg) { width:14px; height:14px; }
.cw-step-desc { font-size:12.5px; color:var(--text-3); line-height:1.6; margin-top:5px; }
.cw-ref-foot { padding:14px 22px; background:var(--surface-2); border-top:1px solid var(--border); font-size:11.5px; color:var(--text-4); line-height:1.5; }

/* modal */
.cw-warn { display:flex; align-items:flex-start; gap:10px; padding:13px 16px; background:var(--badge-bg-gold); border:1px solid rgba(192,154,82,.25); border-radius:var(--radius); margin-bottom:14px; font-size:12.5px; color:var(--gold-dark); line-height:1.5; }
.cw-warn svg { flex-shrink:0; margin-top:1px; }
.cw-warn strong { font-weight:700; }
.cw-modal-text { font-size:12.5px; color:var(--text-2); line-height:1.6; margin:0 0 16px; }
.cw-field-label { display:block; font-size:12px; font-weight:600; color:var(--text-2); margin-bottom:7px; }
.cw-req { color:var(--red); }
.cw-textarea { display:block; width:100%; padding:11px 13px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; resize:vertical; min-height:90px; line-height:1.55; font-family:var(--font-sans); }
.cw-textarea:focus { border-color:var(--gold-dark); }
.cw-textarea::placeholder { color:var(--text-4); }
.cw-textarea-foot { display:flex; justify-content:flex-end; margin-top:6px; }
.cw-count { font-size:11px; color:var(--text-4); }
.cw-field-help { font-size:11.5px; color:var(--text-4); margin-top:6px; }
.cw-field { margin-bottom:16px; }
.cw-field:last-child { margin-bottom:0; }
.cw-input { display:block; width:100%; padding:11px 13px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; }
.cw-input:focus { border-color:var(--gold-dark); }
.cw-input::placeholder { color:var(--text-4); }

@media (max-width:1100px) { .cw-grid { grid-template-columns:1fr 1fr; } }
@media (max-width:900px) { .cw-stats { grid-template-columns:1fr 1fr; } .cw-header { flex-direction:column; } }
@media (max-width:640px) { .cw-grid { grid-template-columns:1fr; } }
</style>
