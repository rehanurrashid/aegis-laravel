<template>
  <AppLayout :user="user" portal="support_steward" activePage="critical-incident-log" pageTitle="Critical Incident Log" :has-emergency="true">

    <!-- ════════════════ LOG VIEW ════════════════ -->
    <template v-if="view === 'log'">
      <div class="ci-header">
        <div>
          <div class="ci-eyebrow">Support Steward Portal</div>
          <h1 class="ci-title">Critical Incident Log</h1>
          <p class="ci-sub">A record of every critical incident you've reported for the practitioners you support.</p>
        </div>
        <div class="ci-header-actions">
          <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
          <button type="button" class="btn btn-primary btn-sm" @click="startReport"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Report Critical Incident</button>
        </div>
      </div>

      <div class="ci-stats">
        <div class="ci-stat"><div class="ci-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div class="ci-stat-num">{{ incidents.length }}</div><div class="ci-stat-label">Total Reports</div></div>
        <div class="ci-stat"><div class="ci-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div><div class="ci-stat-num">{{ activeIncidents.length }}</div><div class="ci-stat-label">Active</div></div>
        <div class="ci-stat"><div class="ci-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div><div class="ci-stat-num">{{ incidents.filter(i => i.status === 'closed').length }}</div><div class="ci-stat-label">Closed</div></div>
        <div class="ci-stat"><div class="ci-stat-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></div><div class="ci-stat-num">{{ incidents.filter(i => i.status === 'withdrawn').length }}</div><div class="ci-stat-label">Withdrawn</div></div>
      </div>

      <div class="ci-bar">
        <div class="ci-tabs">
          <button type="button" class="ci-tab" :class="{ active: logTab === 'active' }" @click="logTab = 'active'">Active <span class="ci-tab-count">{{ activeIncidents.length }}</span></button>
          <button type="button" class="ci-tab" :class="{ active: logTab === 'history' }" @click="logTab = 'history'">History</button>
        </div>
        <div class="ci-search">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input v-model="logSearch" placeholder="Search practitioner or type" />
        </div>
      </div>

      <div class="ci-list">
        <div v-for="inc in visibleIncidents" :key="inc.id" class="ci-row">
          <div class="ci-row-av">{{ inc.initials }}</div>
          <div class="ci-row-info">
            <div class="ci-row-name">{{ inc.name }}</div>
            <div class="ci-row-meta">{{ inc.type }} · {{ inc.reported }}</div>
          </div>
          <span class="ci-status" :class="'s-' + inc.status">{{ inc.status }}</span>
          <div class="ci-row-actions">
            <button type="button" class="btn-icon" data-tip="View"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
            <button type="button" class="btn-icon" data-tip="Append Note" @click="openNote(inc)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></button>
            <button type="button" class="btn-icon" data-tip="Withdraw"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
          </div>
        </div>
        <div v-if="!visibleIncidents.length" class="ci-empty">No incidents in this view.</div>
      </div>

      <!-- APPEND NOTE MODAL -->
      <Modal v-model="showNote" title="Append Note">
        <p class="ci-modal-text">Adds to the incident record — does not replace the original report.</p>
        <div class="ci-field">
          <label class="ci-field-label">Note <span class="ci-req">*</span></label>
          <textarea v-model="noteText" class="ci-textarea" rows="4" placeholder="Additional context or updates since the original report."></textarea>
        </div>
        <template #footer>
          <button type="button" class="btn btn-outline btn-sm" @click="showNote = false">Cancel</button>
          <button type="button" class="btn btn-gold btn-sm" @click="showNote = false">Save Note</button>
        </template>
      </Modal>
    </template>

    <!-- ════════════════ REPORT WIZARD ════════════════ -->
    <template v-else>
      <div class="ci-header">
        <div>
          <div class="ci-eyebrow">Critical Incident Log</div>
          <h1 class="ci-title">Report a Critical Incident</h1>
          <p class="ci-sub">Complete each step carefully. You will review everything before submitting.</p>
        </div>
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
      </div>

      <!-- STEPPER -->
      <div class="ci-stepper">
        <template v-for="s in wizardSteps" :key="s.n">
          <div class="ci-wstep" :class="{ active: step === s.n, done: step > s.n }">
            <span class="ci-wstep-num"><svg v-if="step > s.n" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg><template v-else>{{ s.n }}</template></span>
            {{ s.label }}
          </div>
          <div v-if="s.n < 6" class="ci-wstep-line"></div>
        </template>
      </div>

      <div class="ci-panel">
        <!-- STEP 1 PRACTITIONER -->
        <template v-if="step === 1">
          <div class="ci-panel-title">Who are you reporting for?</div>
          <div class="ci-panel-sub">Select the practitioner this critical incident concerns.</div>
          <button v-for="p in reportPractitioners" :key="p.name" type="button" class="ci-pick" :class="{ active: form.practitioner === p.name }" @click="form.practitioner = p.name">
            <div class="ci-pick-av">{{ p.initials }}</div>
            <div class="ci-pick-info"><div class="ci-pick-name">{{ p.name }}</div><div class="ci-pick-meta">{{ p.org }} · {{ p.role }}</div></div>
            <span v-if="form.practitioner === p.name" class="ci-pick-check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
          </button>
          <div class="ci-wfoot">
            <button type="button" class="ci-cancel" @click="view = 'log'">Cancel</button>
            <button type="button" class="btn btn-gold btn-sm" :disabled="!form.practitioner" @click="step = 2">Next: Incident Type <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>
          </div>
        </template>

        <!-- STEP 2 TYPE -->
        <template v-else-if="step === 2">
          <div class="ci-panel-title">What type of critical incident?</div>
          <div class="ci-panel-sub">Unavailable types are not enabled on this plan or you are not authorized to trigger them.</div>
          <button v-for="t in incidentTypes" :key="t.name" type="button" class="ci-type" :class="{ active: form.type === t.name }" @click="form.type = t.name">
            <div class="ci-type-ico" v-html="t.icon"></div>
            <div class="ci-type-info"><div class="ci-type-name">{{ t.name }}</div><div class="ci-type-note">{{ t.note }}</div></div>
            <span v-if="t.optIn" class="ci-type-optin">Opt-in</span>
            <span v-if="form.type === t.name" class="ci-pick-check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
          </button>
          <div class="ci-wfoot">
            <button type="button" class="ci-back" @click="step = 1"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg> Back</button>
            <button type="button" class="btn btn-gold btn-sm" :disabled="!form.type" @click="step = 3">Next: Narrative <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>
          </div>
        </template>

        <!-- STEP 3 NARRATIVE -->
        <template v-else-if="step === 3">
          <div class="ci-panel-title">Describe what you observed</div>
          <div class="ci-panel-sub">Write a clear, factual account. Include dates, times, and names. This becomes part of the permanent incident record.</div>
          <div class="ci-field" style="margin-top:18px;">
            <label class="ci-field-label">Incident Narrative <span class="ci-req">*</span></label>
            <textarea v-model="form.narrative" class="ci-textarea ci-textarea-lg" placeholder="Describe what happened — when it occurred, what you observed or were told, and any other relevant details."></textarea>
            <div class="ci-field-help">Minimum 20 characters.</div>
          </div>
          <div class="ci-warn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <span>Only submit when you have genuine grounds to believe a critical incident has occurred. Premature reports may affect your standing as a designated Support Steward.</span>
          </div>
          <div class="ci-wfoot">
            <button type="button" class="ci-back" @click="step = 2"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg> Back</button>
            <button type="button" class="btn btn-gold btn-sm" :disabled="form.narrative.trim().length < 20" @click="step = 4">Next: Contact Attempts <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>
          </div>
        </template>

        <!-- STEP 4 CONTACT -->
        <template v-else-if="step === 4">
          <div class="ci-panel-title">Contact attempts before reporting</div>
          <div class="ci-panel-sub">Log the attempts you made to reach the practitioner. Recommended for most incident types.</div>
          <div v-for="(c, i) in form.contacts" :key="i" class="ci-contact-row">
            <select v-model="c.method" class="form-select"><option>Phone</option><option>Text / SMS</option><option>Email</option><option>In person</option></select>
            <input v-model="c.when" type="datetime-local" class="ci-input" />
            <input v-model="c.outcome" class="ci-input" placeholder="Outcome (e.g. no answer)" />
            <button type="button" class="ci-contact-remove" @click="form.contacts.splice(i, 1)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
          </div>
          <button type="button" class="ci-add" @click="form.contacts.push({ method: 'Phone', when: '', outcome: '' })"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add Contact Attempt</button>
          <div class="ci-wfoot">
            <button type="button" class="ci-back" @click="step = 3"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg> Back</button>
            <button type="button" class="btn btn-gold btn-sm" @click="step = 5">Next: Documentation <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>
          </div>
        </template>

        <!-- STEP 5 DOCUMENTATION -->
        <template v-else-if="step === 5">
          <div class="ci-panel-title">Attach supporting documentation</div>
          <div class="ci-panel-sub">Optional at this stage — the Continuity Steward will request verification documents during their review.</div>
          <div v-if="recommendedDoc" class="ci-rec-banner">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <span>Recommended for {{ form.type }}: {{ recommendedDoc }}</span>
          </div>
          <div class="ci-field">
            <label class="ci-field-label">Document Type</label>
            <select v-model="form.docType" class="form-select"><option>— None —</option><option>Death Certificate</option><option>Hospital Admission</option><option>Police Report</option><option>Medical Record</option><option>Legal Notice</option><option>Other</option></select>
          </div>
          <div class="ci-field">
            <label class="ci-field-label">Upload File <span class="ci-opt">(PDF, JPG, PNG, DOC — max 10 MB)</span></label>
            <label class="ci-upload">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              <span>{{ form.docFile || 'Drag and drop or browse' }}</span>
              <input type="file" hidden @change="onFile" />
            </label>
          </div>
          <div class="ci-field">
            <label class="ci-field-label">Note <span class="ci-opt">(optional)</span></label>
            <input v-model="form.docNote" class="ci-input" placeholder="e.g., Hospital admission confirmation Apr 24" />
          </div>
          <div class="ci-wfoot">
            <button type="button" class="ci-back" @click="step = 4"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg> Back</button>
            <button type="button" class="btn btn-gold btn-sm" @click="step = 6">Review Report <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>
          </div>
        </template>

        <!-- STEP 6 REVIEW -->
        <template v-else>
          <div class="ci-panel-title">Review before submitting</div>
          <div class="ci-panel-sub">Confirm all details. Once submitted the Continuity Steward will be notified immediately.</div>
          <div class="ci-rev">
            <div class="ci-rev-block">
              <div class="ci-rev-label">Practitioner</div>
              <div class="ci-rev-val">{{ reviewPractitioner || '—' }}</div>
            </div>
            <div class="ci-rev-block">
              <div class="ci-rev-label">Incident Type</div>
              <div class="ci-rev-val">{{ form.type || '—' }}</div>
            </div>
            <div class="ci-rev-block">
              <div class="ci-rev-label">Narrative</div>
              <div class="ci-rev-val">{{ form.narrative || '—' }}</div>
              <button type="button" class="ci-rev-edit" @click="step = 3">Edit <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></button>
            </div>
            <div class="ci-rev-block">
              <div class="ci-rev-label">Contact Attempts</div>
              <div class="ci-rev-val">{{ form.contacts.length ? form.contacts.length + ' logged' : 'None logged' }}</div>
              <button type="button" class="ci-rev-edit" @click="step = 4">Edit <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></button>
            </div>
            <div class="ci-rev-block">
              <div class="ci-rev-label">Documentation</div>
              <div class="ci-rev-val">{{ form.docFile || (form.docType && form.docType !== '— None —' ? form.docType : 'None attached') }}</div>
              <button type="button" class="ci-rev-edit" @click="step = 5">Edit <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></button>
            </div>
          </div>
          <div class="ci-warn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <span>Submitting will immediately notify the Continuity Steward and create a permanent record.</span>
          </div>
          <div class="ci-wfoot">
            <button type="button" class="ci-back" @click="step = 5"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg> Back</button>
            <button type="button" class="btn btn-gold btn-sm" @click="view = 'log'"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Submit Report</button>
          </div>
        </template>
      </div>
    </template>

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';
import Modal from '../../Components/Modal.vue';
defineProps({ user: Object });

const view = ref('log');
const logTab = ref('active');
const logSearch = ref('');

const incidents = [
  { id: 1, initials: 'DM', name: 'Dr. Michael Torres, MD', type: 'Short-Term Incapacitation', reported: 'May 12, 2026 · 7:45 AM', status: 'active' },
  { id: 2, initials: 'DS', name: 'Dr. Sarah Johnson, PhD, LMFT', type: 'Short-Term Incapacitation', reported: 'Apr 25, 2026 · 10:20 AM', status: 'active' },
  { id: 3, initials: 'DS', name: 'Dr. Sarah Johnson, PhD, LMFT', type: 'Natural Disaster', reported: 'Nov 8, 2025 · 4:40 PM', status: 'closed' },
  { id: 4, initials: 'DR', name: 'Dr. Rachel Okafor, PsyD', type: 'Short-Term Incapacitation', reported: 'Mar 2, 2026 · 9:00 AM', status: 'withdrawn' },
];
const activeIncidents = computed(() => incidents.filter(i => i.status === 'active'));
const visibleIncidents = computed(() => {
  let list = logTab.value === 'active' ? activeIncidents.value : incidents.filter(i => i.status !== 'active');
  if (logSearch.value.trim()) { const q = logSearch.value.toLowerCase(); list = list.filter(i => i.name.toLowerCase().includes(q) || i.type.toLowerCase().includes(q)); }
  return list;
});

const showNote = ref(false);
const noteText = ref('');
function openNote(inc) { noteText.value = ''; showNote.value = true; }

// ── wizard ──
const step = ref(1);
const wizardSteps = [
  { n: 1, label: 'Practitioner' }, { n: 2, label: 'Type' }, { n: 3, label: 'Narrative' },
  { n: 4, label: 'Contact' }, { n: 5, label: 'Documentation' }, { n: 6, label: 'Review' },
];
const form = reactive({ practitioner: '', type: '', narrative: '', contacts: [], docType: '— None —', docFile: '', docNote: '' });
function startReport() { view.value = 'report'; step.value = 1; form.practitioner = ''; form.type = ''; form.narrative = ''; form.contacts = []; form.docType = '— None —'; form.docFile = ''; form.docNote = ''; }
function onFile(e) { const f = e.target.files && e.target.files[0]; form.docFile = f ? f.name : ''; }

const recommendedDocMap = {
  'Death': 'death certificate',
  'Short-Term Incapacitation': 'hospital admission confirmation',
  'Long-Term Incapacitation': 'medical documentation',
  'Missing Person': 'police report',
  'Detainment': 'detainment notice',
  'Natural Disaster': 'incident or news report',
  'Geopolitical or Conflict Related Events': 'official advisory',
  'Pre-Mature Labor': 'hospital admission confirmation',
};
const recommendedDoc = computed(() => recommendedDocMap[form.type] || '');
const reviewPractitioner = computed(() => (form.practitioner || '').replace(/,.*$/, ''));

const reportPractitioners = [
  { initials: 'DM', name: 'Dr. Michael Torres, MD', org: 'Bay Area Psychiatry Associates', role: 'Primary SS' },
  { initials: 'DR', name: 'Dr. Rachel Okafor, PsyD', org: 'North Bay Therapy Collective', role: 'Alternate SS' },
  { initials: 'DS', name: 'Dr. Sarah Johnson, PhD, LMFT', org: 'Lotus Psychology Group', role: 'Primary SS' },
];

const ti = 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';
const incidentTypes = [
  { name: 'Death', note: 'Not enabled on this plan', optIn: false, icon: `<svg viewBox="0 0 24 24" ${ti}><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>` },
  { name: 'Short-Term Incapacitation', note: 'Not enabled on this plan', optIn: false, icon: `<svg viewBox="0 0 24 24" ${ti}><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>` },
  { name: 'Long-Term Incapacitation', note: 'Not enabled on this plan', optIn: false, icon: `<svg viewBox="0 0 24 24" ${ti}><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>` },
  { name: 'Missing Person', note: 'Not enabled on this plan', optIn: true, icon: `<svg viewBox="0 0 24 24" ${ti}><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>` },
  { name: 'Detainment', note: 'Not enabled on this plan', optIn: true, icon: `<svg viewBox="0 0 24 24" ${ti}><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>` },
  { name: 'Natural Disaster', note: 'Not enabled on this plan', optIn: true, icon: `<svg viewBox="0 0 24 24" ${ti}><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>` },
  { name: 'Geopolitical or Conflict Related Events', note: 'Not enabled on this plan', optIn: true, icon: `<svg viewBox="0 0 24 24" ${ti}><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>` },
  { name: 'Pre-Mature Labor', note: 'Not enabled on this plan', optIn: true, icon: `<svg viewBox="0 0 24 24" ${ti}><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>` },
];
</script>

<style scoped>
/* ── SS CRITICAL INCIDENT LOG ── */
.ci-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:24px 30px; margin-bottom:18px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.ci-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.6px; text-transform:uppercase; color:var(--gold-dark); margin-bottom:8px; }
.ci-title { font-family:var(--font-serif); font-size:30px; font-weight:600; letter-spacing:-0.4px; color:var(--text); margin:0; line-height:1.05; }
.ci-sub { font-size:13.5px; color:var(--text-3); margin-top:8px; max-width:640px; }
.ci-header-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; flex-wrap:wrap; }

.ci-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px; }
.ci-stat { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px 18px; box-shadow:var(--shadow-xs); display:grid; grid-template-columns:auto 1fr; grid-template-rows:auto auto; column-gap:12px; align-items:center; }
.ci-stat-ico { grid-row:1 / 3; width:34px; height:34px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.ci-stat-num { font-family:var(--font-serif); font-size:24px; font-weight:700; line-height:1; color:var(--text); }
.ci-stat-label { font-size:11.5px; color:var(--text-3); margin-top:3px; }

.ci-bar { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
.ci-tabs { display:inline-flex; align-items:center; gap:2px; padding:4px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-full); }
.ci-tab { display:inline-flex; align-items:center; gap:7px; padding:8px 16px; font-size:12.5px; font-weight:600; color:var(--text-3); background:none; border:none; border-radius:var(--radius-full); cursor:pointer; transition:all .15s ease; }
.ci-tab:hover { color:var(--text); }
.ci-tab.active { background:var(--gold-dark); color:var(--text-inverted); }
.ci-tab-count { font-size:10px; font-weight:700; min-width:16px; height:16px; padding:0 5px; border-radius:var(--radius-full); background:rgba(255,255,255,.25); display:inline-flex; align-items:center; justify-content:center; }
.ci-tab:not(.active) .ci-tab-count { background:var(--surface-3); color:var(--text-3); }
.ci-search { position:relative; min-width:240px; }
.ci-search svg { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--text-4); pointer-events:none; }
.ci-search input { width:100%; padding:9px 13px 9px 36px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; }
.ci-search input:focus { border-color:var(--gold-dark); }
.ci-search input::placeholder { color:var(--text-4); }

.ci-list { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; }
.ci-row { display:flex; align-items:center; gap:14px; padding:16px 20px; border-bottom:1px solid var(--border); border-left:3px solid var(--red); }
.ci-row:last-child { border-bottom:none; }
.ci-row-av { width:38px; height:38px; border-radius:var(--radius-sm); background:var(--red-dark); color:var(--text-inverted); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; font-size:12px; flex-shrink:0; }
.ci-row-info { flex:1; min-width:0; }
.ci-row-name { font-size:14px; font-weight:700; color:var(--text); }
.ci-row-meta { font-size:12px; color:var(--text-4); margin-top:2px; }
.ci-status { font-size:9.5px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; padding:3px 10px; border-radius:var(--radius-full); flex-shrink:0; }
.ci-status.s-active { background:var(--red-light); color:var(--red); }
.ci-status.s-closed { background:var(--green-light); color:var(--green-dark); }
.ci-status.s-withdrawn { background:var(--surface-3); color:var(--text-3); }
.ci-row-actions { display:flex; gap:6px; flex-shrink:0; }
.ci-empty { padding:40px; text-align:center; font-size:13px; color:var(--text-4); }

/* ── WIZARD ── */
.ci-stepper { display:flex; align-items:center; gap:8px; margin-bottom:16px; padding:0 6px; flex-wrap:wrap; }
.ci-wstep { display:inline-flex; align-items:center; gap:8px; font-size:13px; font-weight:600; color:var(--text-4); white-space:nowrap; }
.ci-wstep.active { color:var(--text); }
.ci-wstep.done { color:var(--green-dark); }
.ci-wstep-num { width:24px; height:24px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-4); font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.ci-wstep.active .ci-wstep-num { background:var(--gold-dark); color:var(--text-inverted); }
.ci-wstep.done .ci-wstep-num { background:var(--green-dark); color:var(--text-inverted); }
.ci-wstep-line { flex:1; height:1px; background:var(--border); min-width:14px; }

.ci-panel { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); padding:24px 28px; }
.ci-panel-title { font-family:var(--font-serif); font-size:19px; font-weight:600; color:var(--text); }
.ci-panel-sub { font-size:13px; color:var(--text-3); margin-top:6px; margin-bottom:18px; line-height:1.5; }

.ci-pick { width:100%; display:flex; align-items:center; gap:14px; padding:16px 18px; margin-bottom:10px; background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); cursor:pointer; text-align:left; transition:all .15s ease; }
.ci-pick:hover { border-color:var(--gold-dark); }
.ci-pick.active { border-color:var(--gold-dark); background:var(--badge-bg-gold); box-shadow:0 0 0 3px rgba(192,154,82,.12); }
.ci-pick-av { width:40px; height:40px; border-radius:50%; background:var(--surface-3); color:var(--text-3); display:flex; align-items:center; justify-content:center; font-family:var(--font-serif); font-weight:700; font-size:13px; flex-shrink:0; }
.ci-pick-info { flex:1; min-width:0; }
.ci-pick-name { font-size:14px; font-weight:700; color:var(--text); }
.ci-pick-meta { font-size:12px; color:var(--text-4); margin-top:2px; }
.ci-pick-check { color:var(--gold-dark); flex-shrink:0; }

.ci-type { width:100%; display:flex; align-items:center; gap:14px; padding:14px 18px; margin-bottom:10px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); cursor:pointer; text-align:left; transition:all .15s ease; }
.ci-type:hover { border-color:var(--gold-dark); }
.ci-type.active { border-color:var(--gold-dark); background:var(--badge-bg-gold); box-shadow:0 0 0 3px rgba(192,154,82,.12); }
.ci-type-ico { width:34px; height:34px; border-radius:var(--radius-sm); background:var(--surface-3); color:var(--text-4); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.ci-type.active .ci-type-ico { background:var(--icon-bg-gold); color:var(--gold-dark); }
.ci-type-ico :deep(svg) { width:15px; height:15px; }
.ci-type-info { flex:1; min-width:0; }
.ci-type-name { font-size:13.5px; font-weight:600; color:var(--text-2); }
.ci-type-note { font-size:11.5px; color:var(--red); margin-top:2px; }
.ci-type-optin { font-size:9.5px; font-weight:700; letter-spacing:.4px; padding:3px 9px; border-radius:var(--radius-full); background:var(--surface-3); color:var(--text-4); flex-shrink:0; }

.ci-field-label { display:block; font-size:12px; font-weight:600; color:var(--text-2); margin-bottom:7px; }
.ci-req { color:var(--red); }
.ci-input { display:block; width:100%; padding:10px 13px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; }
.ci-input:focus { border-color:var(--gold-dark); }
.ci-textarea { display:block; width:100%; padding:12px 14px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; resize:vertical; min-height:96px; line-height:1.55; font-family:var(--font-sans); }
.ci-textarea:focus { border-color:var(--gold-dark); }
.ci-textarea::placeholder, .ci-input::placeholder { color:var(--text-4); }
.ci-textarea-lg { min-height:200px; }
.ci-field-help { font-size:11.5px; color:var(--text-4); margin-top:8px; }

.ci-warn { display:flex; align-items:flex-start; gap:10px; padding:13px 16px; background:var(--badge-bg-gold); border:1px solid rgba(192,154,82,.25); border-radius:var(--radius); margin-top:18px; font-size:12.5px; color:var(--text-2); line-height:1.55; }
.ci-warn svg { color:var(--gold-dark); flex-shrink:0; margin-top:1px; }

.ci-contact-row { display:grid; grid-template-columns:160px 1fr 1fr auto; gap:10px; margin-bottom:10px; align-items:center; }
.ci-contact-remove { width:38px; height:38px; border:1px solid var(--border-dark); border-radius:var(--radius); background:var(--surface); color:var(--red); display:flex; align-items:center; justify-content:center; cursor:pointer; }
.ci-contact-remove:hover { border-color:var(--red); background:var(--red-light); }
.ci-add { display:inline-flex; align-items:center; gap:6px; padding:9px 14px; font-size:12.5px; font-weight:600; color:var(--text-3); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius-full); cursor:pointer; transition:all .15s ease; }
.ci-add:hover { border-color:var(--gold-dark); color:var(--gold-dark); }

.ci-opt { font-weight:400; color:var(--text-4); }
.ci-rec-banner { display:flex; align-items:center; gap:10px; padding:12px 16px; background:rgba(80,120,190,.07); border:1px solid rgba(80,120,190,.20); border-radius:var(--radius); margin-bottom:18px; font-size:12.5px; color:#4a6ba8; font-weight:600; }
.ci-rec-banner svg { color:#5078be; flex-shrink:0; }
.ci-upload { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; padding:26px 16px; text-align:center; border:1.5px dashed var(--border-dark); border-radius:var(--radius); background:var(--surface-2); color:var(--text-4); font-size:12.5px; cursor:pointer; }
.ci-upload:hover { border-color:var(--gold-dark); background:var(--badge-bg-gold); }
.ci-upload svg { color:var(--text-3); }

/* review (step 6) */
.ci-rev { display:flex; flex-direction:column; gap:18px; }
.ci-rev-block { padding-bottom:16px; border-bottom:1px solid var(--border); }
.ci-rev-block:last-child { border-bottom:none; padding-bottom:0; }
.ci-rev-label { font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--text-4); margin-bottom:6px; }
.ci-rev-val { font-size:13px; color:var(--text); line-height:1.6; word-break:break-word; }
.ci-rev-edit { display:inline-flex; align-items:center; gap:5px; margin-top:8px; font-size:12px; font-weight:700; color:var(--text-2); background:none; border:none; cursor:pointer; padding:0; }
.ci-rev-edit:hover { color:var(--gold-dark); }
.ci-rev-edit svg { color:var(--text-4); }

.ci-wfoot { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:20px; padding-top:18px; border-top:1px solid var(--border); }
.ci-cancel, .ci-back { display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:600; color:var(--text-3); background:none; border:none; cursor:pointer; padding:6px 4px; }
.ci-cancel:hover, .ci-back:hover { color:var(--text); }
.btn-emergency { background:var(--red); color:var(--text-inverted); border:none; }
.btn-emergency:hover { background:var(--red-dark); }

/* append note modal */
.ci-modal-text { font-size:12.5px; color:var(--text-2); line-height:1.6; margin:0 0 16px; }

@media (max-width:900px) { .ci-stats { grid-template-columns:1fr 1fr; } .ci-header { flex-direction:column; } .ci-contact-row { grid-template-columns:1fr; } }
</style>
