<template>
  <AppLayout :user="user" portal="practitioner" activePage="continuity-plan" pageTitle="Continuity Plan">

    <!-- HERO BANNER -->
    <div class="cp-hero">
      <div class="cp-hero-left">
        <div class="cp-eyebrow">CONTINUITY PLANNING</div>
        <h1 class="cp-title">My Continuity Plan</h1>
        <p class="cp-sub">Define what happens to your practice in 7 critical moments — and assign every task to a Steward who's certified to handle it.</p>
        <div class="cp-hero-meta">
          <span class="cp-meta-item">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
            Signed Jun 15, 2024
          </span>
          <span class="cp-meta-item">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            6 of 7 incident types enabled
          </span>
          <span class="cp-meta-item">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Annual review due Jun 15, 2026
          </span>
        </div>
      </div>
      <div class="cp-hero-actions">
        <button class="btn btn-outline btn-sm cp-btn-icon" @click="showToast('Opening activity log','info')">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          Activity
        </button>
        <button class="btn btn-outline btn-sm cp-btn-icon" @click="showToast('Beginning annual review','info')">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.95"/></svg>
          Begin Annual Review
        </button>
        <button class="btn btn-primary btn-sm cp-btn-icon" @click="showToast('Viewing signed plan','info')">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          View Signed Plan
        </button>
      </div>
    </div>

    <!-- STATUS BADGES -->
    <div class="cp-badges-row">
      <span class="cp-badge cp-badge-gold">✓ Plan Signed</span>
      <span class="cp-badge cp-badge-gold">✓ SS Certified</span>
      <span class="cp-badge cp-badge-gold">✓ CS Certified</span>
      <span class="cp-badge-active">● ACTIVE</span>
    </div>

    <!-- START OPTIONS -->
    <div class="cp-start-grid">
      <div class="cp-start-card cp-start-card-active" @click="showToast('Opening plan builder','info')">
        <div class="cp-start-check">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="cp-start-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </div>
        <div class="cp-start-title">Build in Aegis</div>
        <div class="cp-start-sub">Fill in the 7-incident grid step by step</div>
      </div>
      <div class="cp-start-card" @click="showToast('Opening upload','info')">
        <div class="cp-start-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        </div>
        <div class="cp-start-title">Upload Existing Plan</div>
        <div class="cp-start-sub">Import a PDF or Word document</div>
      </div>
      <div class="cp-start-card" @click="showToast('Opening template library','info')">
        <div class="cp-start-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="cp-start-title">Start from Template</div>
        <div class="cp-start-sub">Aegis Document Library — 12 templates</div>
      </div>
    </div>

    <!-- CONTINUITY TEAM -->
    <div class="cp-section">
      <div class="cp-section-header">
        <div>
          <div class="cp-section-title">Your Continuity Team</div>
          <div class="cp-section-sub">The people authorized to act on this plan when it activates.</div>
        </div>
        <div class="cp-section-actions">
          <button class="btn btn-outline btn-sm" @click="showToast('Managing CS','info')">Manage CS</button>
          <button class="btn btn-outline btn-sm" @click="showToast('Managing SS','info')">Manage SS</button>
        </div>
      </div>
      <div class="cp-team-grid">
        <div v-for="member in teamMembers" :key="member.name" class="cp-team-card">
          <div class="cp-team-role-label">{{ member.roleLabel }}</div>
          <div class="cp-team-top">
            <div class="cp-team-avatar" :style="{ background: member.avatarColor }">{{ member.initials }}</div>
            <div>
              <div class="cp-team-name">{{ member.name }}</div>
              <div class="cp-team-status" :class="member.certified ? 'cp-team-certified' : 'cp-team-pending'">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" v-if="member.certified"><polyline points="20 6 9 17 4 12"/></svg>
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" v-else><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                {{ member.certified ? 'Certified' : 'Pending' }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- CRITICAL MOMENTS -->
    <div class="cp-section">
      <div class="cp-section-header">
        <div>
          <div class="cp-section-title">Critical Moments</div>
          <div class="cp-section-sub">7 incident types. Toggle each on, assign documents, stewards, and tasks.</div>
        </div>
        <button class="cp-how-btn" @click="showToast('Opening how it works guide','info')">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          How this works
        </button>
      </div>

      <div class="cp-incidents-table">
        <div class="cp-incidents-head">
          <span class="cp-th cp-th-type">INCIDENT TYPE</span>
          <span class="cp-th cp-th-enabled">ENABLED</span>
          <span class="cp-th cp-th-docs">DOCS REQUIRED</span>
          <span class="cp-th cp-th-stewards">AUTHORIZED STEWARDS</span>
          <span class="cp-th cp-th-tasks">TASKS</span>
          <span class="cp-th"></span>
        </div>
        <div v-for="inc in incidents" :key="inc.title" class="cp-incident-row" :class="{ 'cp-row-disabled': !inc.enabled }">
          <div class="cp-td-type">
            <div class="cp-inc-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" v-html="inc.icon"></svg></div>
            <div>
              <div class="cp-inc-title">{{ inc.title }}</div>
              <div class="cp-inc-label">{{ inc.alwaysOn ? 'ALWAYS ON' : 'OPT-IN' }} · <span :class="inc.enabled ? 'cp-enabled' : 'cp-disabled'">{{ inc.enabled ? 'ENABLED' : 'DISABLED' }}</span></div>
            </div>
          </div>
          <div class="cp-td-toggle">
            <label class="cp-toggle" :class="{ disabled: inc.alwaysOn }">
              <input type="checkbox" v-model="inc.enabled" :disabled="inc.alwaysOn">
              <span class="cp-toggle-track" :class="{ on: inc.enabled }"><span class="cp-toggle-thumb"></span></span>
            </label>
          </div>
          <div class="cp-td-docs">
            <span v-for="doc in inc.docs" :key="doc" class="cp-doc-chip">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
              {{ doc }}
            </span>
            <span v-if="!inc.docs.length" class="cp-doc-none">None required</span>
          </div>
          <div class="cp-td-stewards">
            <div v-if="inc.enabled" class="cp-steward-chips">
              <span v-for="s in inc.stewards" :key="s.initials" class="cp-steward-chip" :style="{ background: s.color }" :title="s.name">{{ s.initials }}</span>
            </div>
            <span v-else class="cp-doc-none">—</span>
          </div>
          <div class="cp-td-tasks">
            <span v-if="inc.enabled" class="cp-tasks-text">{{ inc.tasks }}</span>
            <span v-else class="cp-doc-none">—</span>
          </div>
          <div class="cp-td-edit">
            <button v-if="inc.enabled" class="cp-edit-btn" @click="openIncidentModal(inc)">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- SIGNED PLAN FOOTER -->
    <div class="cp-signed-footer">
      <div class="cp-signed-header">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
        <span class="cp-signed-label">Plan Signed &amp; Active</span>
        <span class="cp-signed-version">v2.0 · Jun 15, 2024 at 2:00 PM UTC</span>
      </div>
      <div class="cp-signed-grid">
        <div class="cp-signed-cell">
          <div class="cp-signed-cell-label">SIGNED BY</div>
          <div class="cp-signed-cell-val">Dr. Sarah Johnson</div>
          <div class="cp-signed-cell-sub">PhD, LMFT</div>
        </div>
        <div class="cp-signed-cell">
          <div class="cp-signed-cell-label">TITLE</div>
          <div class="cp-signed-cell-val">Licensed Marriage &amp; Family Therapist</div>
          <div class="cp-signed-cell-sub">Lotus Psychology Group</div>
        </div>
        <div class="cp-signed-cell">
          <div class="cp-signed-cell-label">DATE</div>
          <div class="cp-signed-cell-val">Jun 15, 2024</div>
          <div class="cp-signed-cell-sub">2:00 PM UTC</div>
        </div>
        <div class="cp-signed-cell">
          <div class="cp-signed-cell-label">VERSION</div>
          <div class="cp-signed-cell-val">v2.0</div>
          <div class="cp-signed-cell-sub">Annual review Jun 2026</div>
        </div>
      </div>
      <div class="cp-signed-actions">
        <button class="btn btn-outline btn-sm cp-btn-icon" @click="showToast('Downloading PDF','info')">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          Download Signed PDF
        </button>
        <button class="btn btn-primary btn-sm cp-btn-icon" @click="showToast('Viewing signed PDF','info')">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          View Signed PDF
        </button>
      </div>
    </div>

    <!-- Toast -->
    <Teleport to="body">

      <!-- CONFIGURE INCIDENT MODAL -->
      <Transition name="cp-modal-fade">
        <div v-if="incidentModal.open" class="cp-modal-backdrop" @click.self="incidentModal.open=false">
          <div class="cp-modal">
            <!-- Header -->
            <div class="cp-modal-header">
              <div>
                <div class="cp-modal-title">Configure: {{ incidentModal.inc?.title }}</div>
                <div class="cp-modal-sub">Set documentation, authorized stewards, and task lists for this incident.</div>
              </div>
              <button class="cp-modal-close" @click="incidentModal.open=false">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>

            <div class="cp-modal-body">

              <!-- INCIDENT SETTINGS -->
              <div class="cp-modal-section-label">INCIDENT SETTINGS</div>
              <div class="cp-enable-row">
                <div>
                  <div class="cp-enable-label">Enable this incident type</div>
                  <div class="cp-enable-sub">Authorizes your stewards to act under this specific scenario.</div>
                </div>
                <label class="cp-toggle">
                  <input type="checkbox" v-model="incidentModal.enabled">
                  <span class="cp-toggle-track" :class="{ on: incidentModal.enabled }"><span class="cp-toggle-thumb"></span></span>
                </label>
              </div>

              <!-- DOCUMENTATION REQUIRED -->
              <div class="cp-modal-section-label" style="margin-top:14px;">DOCUMENTATION REQUIRED</div>
              <div class="cp-doc-checklist">
                <label v-for="doc in availableDocs" :key="doc" class="cp-doc-chk-row">
                  <input type="checkbox" :checked="incidentModal.docs.includes(doc)" @change="toggleDoc(doc)">
                  <span class="cp-doc-chk-lbl">{{ doc }}</span>
                </label>
              </div>

              <!-- AUTHORIZED STEWARDS -->
              <div class="cp-modal-section-label" style="margin-top:14px;">AUTHORIZED STEWARDS</div>
              <div class="cp-auth-stewards-grid">
                <!-- Support Stewards column -->
                <div class="cp-auth-col">
                  <div class="cp-auth-col-header">SUPPORT STEWARDS</div>
                  <div v-for="m in ssMembers" :key="m.name" class="cp-auth-row">
                    <input type="checkbox" :checked="incidentModal.authorizedSS.includes(m.name)" @change="toggleAuth(incidentModal.authorizedSS, m.name)">
                    <span class="cp-auth-name">{{ m.name }}</span>
                    <span class="cp-auth-role-lbl">{{ m.roleLabel === 'PRIMARY SS' ? 'Primary' : m.roleLabel === 'ALTERNATE SS' ? 'Alternate' : m.roleLabel }}</span>
                  </div>
                  <div v-for="extra in extraSSMembers" :key="extra.name" class="cp-auth-row">
                    <input type="checkbox">
                    <span class="cp-auth-name">{{ extra.name }}</span>
                    <span class="cp-auth-role-lbl">{{ extra.role }}</span>
                  </div>
                </div>
                <!-- Continuity Stewards column -->
                <div class="cp-auth-col">
                  <div class="cp-auth-col-header">CONTINUITY STEWARDS</div>
                  <div v-for="m in csMembers" :key="m.name" class="cp-auth-row">
                    <input type="checkbox" :checked="incidentModal.authorizedCS.includes(m.name)" @change="toggleAuth(incidentModal.authorizedCS, m.name)">
                    <span class="cp-auth-name">{{ m.name }}</span>
                    <span class="cp-auth-role-lbl">{{ m.roleLabel === 'PRIMARY CS' ? 'Primary' : m.roleLabel === 'ALTERNATE CS' ? 'Alternate' : m.roleLabel }}</span>
                  </div>
                  <div v-for="extra in extraCSMembers" :key="extra.name" class="cp-auth-row">
                    <input type="checkbox">
                    <span class="cp-auth-name">{{ extra.name }}</span>
                    <span class="cp-auth-role-lbl">{{ extra.role }}</span>
                  </div>
                </div>
              </div>

              <!-- SUPPORT STEWARD TASKS -->
              <div class="cp-tasks-header" style="margin-top:16px;">
                <div class="cp-modal-section-label" style="margin:0;">SUPPORT STEWARD TASKS <span class="cp-task-count">{{ incidentModal.ssTasks.length }}</span></div>
                <button class="cp-copy-btn" @click="showToast('Copied from SS list','success')">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                  Copy from SS list
                </button>
              </div>
              <div class="cp-task-rows">
                <div v-for="(task, i) in incidentModal.ssTasks" :key="'ss'+i" class="cp-task-item">
                  <input type="checkbox" v-model="task.done">
                  <span class="cp-task-lbl" :class="{ done: task.done }">{{ task.label }}</span>
                  <span class="cp-task-due">{{ task.date }}</span>
                  <button class="cp-task-del" @click="incidentModal.ssTasks.splice(i,1)">×</button>
                </div>
              </div>
              <div class="cp-task-add-bar">
                <input class="cp-task-new-input" v-model="incidentModal.newSSTask" placeholder="Type a new SS task and press Add..." @keyup.enter="addSSTask">
                <select class="cp-task-when-sel" v-model="incidentModal.ssTaskDue">
                  <option>Within 24 hrs</option>
                  <option>Within 48 hrs</option>
                  <option>Within 72 hrs</option>
                  <option>Day 1</option>
                  <option>Day 2</option>
                  <option>Within 1 week</option>
                </select>
                <button class="cp-task-add-btn" @click="addSSTask">Add</button>
              </div>
              <div class="cp-aegis-suggest" @click="showToast('Loading Aegis SS suggestions','info')">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Aegis suggested SS tasks (3)
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="margin-left:auto"><polyline points="20 6 9 17 4 12"/></svg>
              </div>

              <!-- CONTINUITY STEWARD TASKS -->
              <div class="cp-tasks-header" style="margin-top:16px;">
                <div class="cp-modal-section-label" style="margin:0;">CONTINUITY STEWARD TASKS <span class="cp-task-count">{{ incidentModal.csTasks.length }}</span></div>
              </div>
              <div class="cp-task-rows">
                <div v-for="(task, i) in incidentModal.csTasks" :key="'cs'+i" class="cp-task-item">
                  <input type="checkbox" v-model="task.done">
                  <span class="cp-task-lbl" :class="{ done: task.done }">{{ task.label }}</span>
                  <span class="cp-task-due">{{ task.date }}</span>
                  <button class="cp-task-del" @click="incidentModal.csTasks.splice(i,1)">×</button>
                </div>
              </div>
              <div class="cp-task-add-bar">
                <input class="cp-task-new-input" v-model="incidentModal.newCSTask" placeholder="Type a new CS task and press Add..." @keyup.enter="addCSTask">
                <select class="cp-task-when-sel" v-model="incidentModal.csTaskDue">
                  <option>Within 2 hrs</option>
                  <option>Within 4 hrs</option>
                  <option>Within 24 hrs</option>
                  <option>Within 48 hrs</option>
                  <option>Day 1</option>
                  <option>Day 1-14</option>
                </select>
                <button class="cp-task-add-btn" @click="addCSTask">Add</button>
              </div>
              <div class="cp-aegis-suggest" @click="showToast('Loading Aegis CS suggestions','info')">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Aegis suggested CS tasks (3)
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="margin-left:auto"><polyline points="20 6 9 17 4 12"/></svg>
              </div>

            </div><!-- end modal body -->

            <!-- Footer -->
            <div class="cp-modal-footer">
              <button class="btn btn-outline btn-sm" @click="incidentModal.open=false">Cancel</button>
              <button class="cp-save-btn" @click="saveIncident">Save Changes</button>
            </div>
          </div>
        </div>
      </Transition>

      <div class="dh-toast-stack">
        <div v-for="t in toasts" :key="t.id" class="dh-toast" :class="t.type">
          <svg v-if="t.type==='success'" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
          <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span>{{ t.msg }}</span>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import AppLayout from '../../Components/AppLayout.vue';

defineProps({ user: Object });

const toasts = ref([]);
let tid = 0;
function showToast(msg, type = 'info') {
  const id = ++tid;
  toasts.value.push({ id, msg, type });
  setTimeout(() => { toasts.value = toasts.value.filter(t => t.id !== id); }, 3500);
}

const teamMembers = [
  { initials:'MC', name:'Marcus Chen',     avatarColor:'#a0813e', certified:true,  roleLabel:'PRIMARY CS'   },
  { initials:'TC', name:'Dr. Thomas Chen', avatarColor:'#6a4c8c', certified:false, roleLabel:'ALTERNATE CS' },
  { initials:'LJ', name:'Linda Johnson',   avatarColor:'#4a7a6a', certified:true,  roleLabel:'PRIMARY SS'   },
  { initials:'BS', name:'Brian Santos',    avatarColor:'#7a5c4a', certified:false, roleLabel:'ALTERNATE SS' },
];

// ── Incident modal ──
const availableDocs = ['Death Certificate', "Doctor's Note", 'Police Report', 'Legal Documentation', 'Other'];

const ssMembers = teamMembers.filter(m => m.roleLabel.includes('SS'));
const csMembers = teamMembers.filter(m => m.roleLabel.includes('CS'));
const extraSSMembers = [
  { name:'Rachel Pham',   role:'Alternate' },
  { name:'Jordan Taylor', role:'Alternate' },
  { name:'Brian Santos',  role:'Alternate' },
];
const extraCSMembers = [
  { name:'Dr. Laura Reyes',      role:'Secondary' },
  { name:'Dr. Amelia Rodriguez', role:'Secondary' },
];

const incidentModal = ref({
  open:false, inc:null, enabled:true,
  authorizedCS:[], authorizedSS:[],
  docs:[],
  ssTasks:[], csTasks:[],
  newSSTask:'', newCSTask:'',
  ssTaskDue:'Within 24 hrs', csTaskDue:'Within 2 hrs',
});

function openIncidentModal(inc) {
  incidentModal.value = {
    open:true, inc,
    enabled: inc.enabled,
    authorizedCS: teamMembers.filter(m => m.roleLabel.includes('CS')).map(m => m.name),
    authorizedSS: teamMembers.filter(m => m.roleLabel.includes('SS')).map(m => m.name),
    docs: [...inc.docs],
    ssTasks: [
      { label:'File missing person report with law enforcement', date:'Within 24 hrs', done:false },
      { label:'Provide police report to Continuity Steward',      date:'Within 48 hrs', done:false },
    ],
    csTasks: [
      { label:'Verify incident with documented police report',                         date:'Within 4 hrs', done:false },
      { label:'Implement holding pattern for active clients (72-hour update cycle)',   date:'Day 1-14',     done:false },
    ],
    newSSTask:'', newCSTask:'',
    ssTaskDue:'Within 24 hrs', csTaskDue:'Within 2 hrs',
  };
}

function toggleAuth(arr, name) {
  const i = arr.indexOf(name);
  if (i === -1) arr.push(name); else arr.splice(i, 1);
}
function toggleDoc(doc) {
  const docs = incidentModal.value.docs;
  const i = docs.indexOf(doc);
  if (i === -1) docs.push(doc); else docs.splice(i, 1);
}
function addCSTask() {
  const val = incidentModal.value.newCSTask.trim();
  if (val) { incidentModal.value.csTasks.push({ label:val, date:incidentModal.value.csTaskDue, done:false }); incidentModal.value.newCSTask = ''; }
}
function addSSTask() {
  const val = incidentModal.value.newSSTask.trim();
  if (val) { incidentModal.value.ssTasks.push({ label:val, date:incidentModal.value.ssTaskDue, done:false }); incidentModal.value.newSSTask = ''; }
}
function saveIncident() {
  if (incidentModal.value.inc) {
    incidentModal.value.inc.enabled = incidentModal.value.enabled;
    incidentModal.value.inc.docs = [...incidentModal.value.docs];
  }
  incidentModal.value.open = false;
  showToast('Incident configuration saved', 'success');
}

const ss = [
  { initials:'LI', name:'Linda',  color:'#a0813e' },
  { initials:'JA', name:'James',  color:'#6a4c8c' },
  { initials:'MA', name:'Marcus', color:'#4a7a6a' },
  { initials:'PR', name:'Priya',  color:'#7a5c4a' },
];
const ssLJ = [{ initials:'LI', name:'Linda', color:'#a0813e' }];
const ssJM = [{ initials:'JA', name:'James', color:'#6a4c8c' }, { initials:'MA', name:'Marcus', color:'#4a7a6a' }];

const incidents = ref([
  { title:'Death',                              alwaysOn:true,  enabled:true,  icon:'<circle cx="12" cy="12" r="10"/>',                             docs:['Death Certificate'],              stewards:ss,                tasks:'4 SS · 6 CS tasks' },
  { title:'Short-Term Incapacitation',          alwaysOn:true,  enabled:true,  icon:'<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',                        docs:["Doctor's Note"],                  stewards:ss.slice(0,3),     tasks:'3 SS · 4 CS tasks' },
  { title:'Long-Term Incapacitation',           alwaysOn:true,  enabled:true,  icon:'<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',                        docs:["Doctor's Note","Legal Documentation"], stewards:ss,             tasks:'3 SS · 4 CS tasks' },
  { title:'Missing Person',                     alwaysOn:false, enabled:true,  icon:'<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>', docs:['Police Report'], stewards:ssJM,         tasks:'2 SS · 2 CS tasks' },
  { title:'Detainment',                         alwaysOn:false, enabled:false, icon:'<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',docs:[],                stewards:[],            tasks:'' },
  { title:'Natural Disaster',                   alwaysOn:false, enabled:true,  icon:'<path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9z"/>',docs:[],               stewards:ss,            tasks:'2 SS · 2 CS tasks' },
  { title:'Geopolitical or Conflict-Related Events', alwaysOn:false, enabled:false, icon:'<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/>',docs:[],  stewards:[],            tasks:'' },
]);
</script>

<style scoped>
/* Hero */
.cp-hero { display:flex; align-items:flex-start; justify-content:space-between; gap:20px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); padding:22px 26px; margin-bottom:10px; box-shadow:var(--shadow-xs); flex-wrap:wrap; }
.cp-eyebrow { font-family:var(--font-sans); font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--gold-dark); margin-bottom:5px; }
.cp-title { font-family:var(--font-serif); font-size:26px; font-weight:700; color:var(--text); margin:0 0 6px; }
.cp-sub { font-family:var(--font-sans); font-size:12.5px; color:var(--text-3); margin:0 0 10px; line-height:1.5; max-width:580px; }
.cp-hero-meta { display:flex; flex-wrap:wrap; gap:14px; }
.cp-meta-item { display:inline-flex; align-items:center; gap:5px; font-family:var(--font-sans); font-size:12px; color:var(--text-4); }
.cp-hero-actions { display:flex; gap:8px; flex-shrink:0; align-items:center; flex-wrap:wrap; }
.cp-btn-icon { display:inline-flex; align-items:center; gap:6px; }

/* Badges row */
.cp-badges-row { display:flex; align-items:center; gap:8px; margin-bottom:14px; flex-wrap:wrap; }
.cp-badge { display:inline-flex; align-items:center; gap:5px; font-size:11.5px; font-weight:600; padding:4px 11px; border-radius:99px; background:rgba(160,129,62,.1); color:var(--gold-dark); border:1px solid var(--fade-gold); }
.cp-badge-active { margin-left:auto; font-size:11.5px; font-weight:700; padding:4px 12px; border-radius:99px; background:rgba(76,175,125,.12); color:var(--green-dark); border:1px solid var(--soft-green); }

/* Start options */
.cp-start-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:18px; }
.cp-start-card { background:var(--surface); border:1.5px solid var(--border); border-radius:10px; padding:16px; cursor:pointer; transition:box-shadow .15s,border-color .15s; position:relative; }
.cp-start-card:hover { box-shadow:var(--shadow-sm); border-color:var(--gold-dark); }
.cp-start-card-active { border-color:var(--gold-dark); background:rgba(160,129,62,.04); }
.cp-start-check { position:absolute; top:12px; right:12px; width:20px; height:20px; border-radius:50%; background:var(--gold-dark); color:#fff; display:flex; align-items:center; justify-content:center; }
.cp-start-icon { color:var(--gold-dark); margin-bottom:8px; }
.cp-start-title { font-family:var(--font-sans); font-size:13.5px; font-weight:700; color:var(--text); margin-bottom:3px; }
.cp-start-sub   { font-size:12px; color:var(--text-4); }

/* Section */
.cp-section { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg,14px); padding:20px 22px; margin-bottom:14px; box-shadow:var(--shadow-xs); }
.cp-section-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
.cp-section-title { font-family:var(--font-sans); font-size:15px; font-weight:700; color:var(--text); margin-bottom:3px; }
.cp-section-sub   { font-size:12px; color:var(--text-4); }
.cp-section-actions { display:flex; gap:8px; flex-shrink:0; }
.cp-how-btn { display:inline-flex; align-items:center; gap:5px; font-family:var(--font-sans); font-size:12px; color:var(--text-4); background:transparent; border:none; cursor:pointer; }
.cp-how-btn:hover { color:var(--gold-dark); }

/* Team grid */
.cp-team-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; }
.cp-team-card { background:var(--surface-2); border:1px solid var(--border); border-radius:10px; padding:14px; }
.cp-team-role-label { font-size:9.5px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:var(--text-4); margin-bottom:8px; }
.cp-team-top { display:flex; align-items:center; gap:10px; }
.cp-team-avatar { width:34px; height:34px; border-radius:50%; color:#fff; font-family:var(--font-sans); font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cp-team-name { font-size:13px; font-weight:700; color:var(--text); margin-bottom:3px; }
.cp-team-certified { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:600; color:var(--green-dark); }
.cp-team-pending   { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:600; color:var(--orange-dark); }

/* Incidents table */
.cp-incidents-table { border:1px solid var(--border); border-radius:10px; overflow:hidden; }
.cp-incidents-head { display:grid; grid-template-columns:2fr .6fr 1.4fr 1.5fr 1fr .4fr; gap:0; padding:9px 14px; background:var(--surface-2); border-bottom:1px solid var(--border); }
.cp-th { font-size:9.5px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--text-4); }
.cp-incident-row { display:grid; grid-template-columns:2fr .6fr 1.4fr 1.5fr 1fr .4fr; gap:0; padding:12px 14px; border-bottom:1px solid var(--border); align-items:center; transition:background .12s; }
.cp-incident-row:last-child { border-bottom:none; }
.cp-incident-row:hover { background:var(--surface-2); }
.cp-row-disabled { opacity:.6; }
.cp-td-type { display:flex; align-items:center; gap:10px; }
.cp-inc-icon { width:28px; height:28px; border-radius:6px; background:var(--surface-3); color:var(--text-4); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cp-inc-title { font-size:13px; font-weight:600; color:var(--text); }
.cp-inc-label { font-size:10px; font-weight:700; letter-spacing:.05em; color:var(--text-4); margin-top:2px; }
.cp-enabled  { color:var(--green-dark); }
.cp-disabled { color:var(--red); }

/* Toggle */
.cp-toggle { position:relative; display:inline-block; width:36px; height:20px; }
.cp-toggle input { opacity:0; width:0; height:0; }
.cp-toggle-track { position:absolute; inset:0; background:var(--border-dark); border-radius:99px; cursor:pointer; transition:background .2s; }
.cp-toggle-track.on { background:var(--gold-dark); }
.cp-toggle.disabled .cp-toggle-track { cursor:not-allowed; opacity:.7; }
.cp-toggle-thumb { position:absolute; width:16px; height:16px; left:2px; top:2px; background:#fff; border-radius:50%; transition:transform .2s; }
.cp-toggle-track.on .cp-toggle-thumb { transform:translateX(16px); }

.cp-doc-chip { display:inline-flex; align-items:center; gap:4px; font-size:11.5px; color:var(--text-3); background:var(--surface-2); border:1px solid var(--border); border-radius:99px; padding:2px 8px; margin-right:4px; }
.cp-doc-none { font-size:12px; color:var(--text-4); }
.cp-steward-chips { display:flex; gap:3px; flex-wrap:wrap; }
.cp-steward-chip { width:24px; height:24px; border-radius:50%; color:#fff; font-family:var(--font-sans); font-size:9px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cp-tasks-text { font-size:12px; color:var(--text-3); }
.cp-edit-btn { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; border:1px solid var(--border); background:var(--surface); color:var(--text-3); cursor:pointer; transition:all .15s; }
.cp-edit-btn:hover { border-color:var(--gold-dark); color:var(--gold-dark); }

/* Signed footer */
.cp-signed-footer { background:rgba(76,175,125,.05); border:1.5px solid var(--soft-green); border-radius:var(--radius-lg,14px); padding:18px 22px; margin-bottom:14px; }
.cp-signed-header { display:flex; align-items:center; gap:8px; margin-bottom:16px; }
.cp-signed-label { font-family:var(--font-sans); font-size:13px; font-weight:700; color:var(--green-dark); }
.cp-signed-version { font-size:12px; color:var(--text-4); margin-left:4px; }
.cp-signed-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:16px; }
.cp-signed-cell-label { font-size:10px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:var(--text-4); margin-bottom:4px; }
.cp-signed-cell-val { font-size:13.5px; font-weight:600; color:var(--text); margin-bottom:2px; }
.cp-signed-cell-sub { font-size:11.5px; color:var(--text-4); }
.cp-signed-actions { display:flex; justify-content:flex-end; gap:8px; padding-top:14px; border-top:1px solid var(--soft-green); }

@media(max-width:1100px) { .cp-team-grid { grid-template-columns:repeat(2,1fr); } }
@media(max-width:860px)  { .cp-start-grid { grid-template-columns:1fr; } .cp-signed-grid { grid-template-columns:repeat(2,1fr); } }

/* ── Configure Incident Modal ───────────────────────────────── */
.cp-modal-backdrop { position:fixed; inset:0; z-index:1000; background:rgba(30,28,26,.45); backdrop-filter:blur(4px); display:flex; align-items:center; justify-content:center; padding:20px; }
.cp-modal { background:var(--surface); border:1px solid var(--border); border-radius:14px; box-shadow:0 24px 64px rgba(30,28,26,.2); width:100%; max-width:640px; max-height:90vh; display:flex; flex-direction:column; overflow:hidden; }
.cp-modal-header { display:flex; align-items:flex-start; justify-content:space-between; padding:18px 22px 14px; border-bottom:1px solid var(--border); flex-shrink:0; }
.cp-modal-title { font-family:var(--font-serif); font-size:17px; font-weight:700; color:var(--text); margin-bottom:3px; }
.cp-modal-sub   { font-family:var(--font-sans); font-size:12px; color:var(--text-4); }
.cp-modal-close { width:28px; height:28px; padding:0; display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--border); border-radius:6px; background:var(--surface); color:var(--text-3); cursor:pointer; flex-shrink:0; transition:all .15s; }
.cp-modal-close:hover { border-color:var(--text); color:var(--text); }
.cp-modal-body { padding:16px 20px; overflow-y:auto; display:flex; flex-direction:column; gap:14px; flex:1; }
.cp-modal-footer { display:flex; align-items:center; justify-content:flex-end; gap:8px; padding:12px 20px; border-top:1px solid var(--border); background:var(--surface-2); flex-shrink:0; }
.cp-save-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; font-family:var(--font-sans); font-size:12.5px; font-weight:600; background:var(--gold-dark); color:#fff; border:1px solid var(--gold-dark); border-radius:var(--radius-sm,8px); cursor:pointer; transition:background .15s; }
.cp-save-btn:hover { background:var(--gold); border-color:var(--gold); }

/* Section labels */
.cp-modal-section-label { font-family:var(--font-sans); font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--text-4); margin-bottom:8px; }
.cp-task-count { font-size:11px; font-weight:600; color:var(--text-4); margin-left:4px; }

/* Enable row */
.cp-enable-row { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:10px 14px; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; }
.cp-enable-label { font-family:var(--font-sans); font-size:13px; font-weight:600; color:var(--text); margin-bottom:2px; }
.cp-enable-sub   { font-size:12px; color:var(--text-4); }

/* Docs checklist */
.cp-doc-checklist { display:flex; flex-direction:column; gap:4px; }
.cp-doc-chk-row { display:flex; align-items:center; gap:8px; padding:5px 2px; cursor:pointer; }
.cp-doc-chk-row input { cursor:pointer; accent-color:var(--gold-dark); width:14px; height:14px; flex-shrink:0; }
.cp-doc-chk-lbl { font-family:var(--font-sans); font-size:13px; color:var(--text-2); }

/* Two-column stewards grid */
.cp-auth-stewards-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.cp-auth-col { display:flex; flex-direction:column; gap:4px; }
.cp-auth-col-header { font-size:9.5px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:var(--text-4); margin-bottom:6px; }
.cp-auth-row { display:flex; align-items:center; gap:7px; padding:6px 8px; border:1px solid var(--border); border-radius:6px; cursor:pointer; transition:background .12s; }
.cp-auth-row:hover { background:var(--surface-2); }
.cp-auth-row input { cursor:pointer; accent-color:var(--gold-dark); width:14px; height:14px; flex-shrink:0; }
.cp-auth-name { font-family:var(--font-sans); font-size:12.5px; font-weight:500; color:var(--text); flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.cp-auth-role-lbl { font-size:10.5px; color:var(--text-4); flex-shrink:0; }

/* Tasks section header */
.cp-tasks-header { display:flex; align-items:center; justify-content:space-between; }
.cp-copy-btn { font-family:var(--font-sans); font-size:11px; font-weight:600; color:var(--gold-dark); background:transparent; border:1.5px solid var(--fade-gold); border-radius:6px; padding:3px 9px; cursor:pointer; transition:all .15s; display:inline-flex; align-items:center; gap:5px; }
.cp-copy-btn:hover { background:rgba(160,129,62,.08); }

/* Task rows */
.cp-task-rows { display:flex; flex-direction:column; gap:4px; margin-bottom:8px; }
.cp-task-item { display:flex; align-items:center; gap:8px; padding:7px 10px; background:var(--surface-2); border:1px solid var(--border); border-radius:7px; }
.cp-task-item input { cursor:pointer; accent-color:var(--gold-dark); flex-shrink:0; }
.cp-task-lbl  { flex:1; font-size:12.5px; color:var(--text-2); min-width:0; }
.cp-task-lbl.done { text-decoration:line-through; color:var(--text-4); }
.cp-task-due  { font-size:11px; color:var(--text-4); white-space:nowrap; flex-shrink:0; }
.cp-task-del  { background:none; border:none; cursor:pointer; color:var(--text-4); font-size:15px; line-height:1; padding:0 2px; flex-shrink:0; transition:color .12s; }
.cp-task-del:hover { color:var(--red); }

/* Add task bar */
.cp-task-add-bar { display:flex; align-items:center; gap:6px; }
.cp-task-new-input { flex:1; padding:7px 10px; font-family:var(--font-sans); font-size:12.5px; color:var(--text); background:var(--surface); border:1.5px solid var(--border); border-radius:6px; outline:none; transition:border-color .15s; }
.cp-task-new-input:focus { border-color:var(--gold-dark); }
.cp-task-new-input::placeholder { color:var(--text-4); }
.cp-task-when-sel { padding:6px 8px; font-family:var(--font-sans); font-size:11.5px; color:var(--text); background:var(--surface); border:1.5px solid var(--border); border-radius:6px; outline:none; cursor:pointer; appearance:none; -webkit-appearance:none; flex-shrink:0; }
.cp-task-add-btn { padding:6px 13px; font-family:var(--font-sans); font-size:12px; font-weight:600; color:var(--gold-dark); background:rgba(160,129,62,.08); border:1.5px solid var(--fade-gold); border-radius:6px; cursor:pointer; transition:all .15s; white-space:nowrap; }
.cp-task-add-btn:hover { background:var(--gold-dark); color:#fff; }

/* Aegis suggestions row */
.cp-aegis-suggest { display:flex; align-items:center; gap:6px; padding:8px 12px; background:var(--surface-2); border:1px solid var(--border); border-radius:7px; font-family:var(--font-sans); font-size:12.5px; font-weight:600; color:var(--gold-dark); cursor:pointer; transition:background .12s; margin-top:4px; }
.cp-aegis-suggest:hover { background:rgba(160,129,62,.08); }

/* Modal fade transition */
.cp-modal-fade-enter-active, .cp-modal-fade-leave-active { transition:opacity .2s ease; }
.cp-modal-fade-enter-active .cp-modal, .cp-modal-fade-leave-active .cp-modal { transition:transform .2s ease; }
.cp-modal-fade-enter-from, .cp-modal-fade-leave-to { opacity:0; }
.cp-modal-fade-enter-from .cp-modal { transform:translateY(-10px) scale(0.98); }
</style>
