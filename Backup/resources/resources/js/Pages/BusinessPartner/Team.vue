<template>
  <AppLayout :user="user" portal="business_partner" activePage="team" pageTitle="Team Management">

    <!-- ═══ HEADER ═══ -->
    <div class="tm-header">
      <div class="tm-header-l">
        <h1 class="tm-title">Team</h1>
        <div class="tm-subtitle">4 active members · 18 assigned contracts</div>
        <div class="tm-tagline"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> Agency Partner</div>
      </div>
      <div class="tm-header-actions">
        <button type="button" class="btn btn-outline btn-sm"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Activity</button>
        <button type="button" class="btn btn-outline btn-sm" @click="showRoles = true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Roles</button>
        <button type="button" class="btn btn-primary btn-sm" @click="showInvite = true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Invite Member</button>
      </div>
    </div>

    <!-- ═══ STATS ═══ -->
    <div class="tm-stats">
      <div class="tm-stat"><div class="tm-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div><div><div class="tm-stat-num">6</div><div class="tm-stat-label">Total Members</div></div></div>
      <div class="tm-stat"><div class="tm-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div><div><div class="tm-stat-num">4</div><div class="tm-stat-label">Active</div></div></div>
      <div class="tm-stat"><div class="tm-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></div><div><div class="tm-stat-num">18</div><div class="tm-stat-label">Assigned Contracts</div></div></div>
      <div class="tm-stat"><div class="tm-stat-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div><div><div class="tm-stat-num">2</div><div class="tm-stat-label">Pending Invites</div></div></div>
    </div>

    <!-- ═══ PENDING BAND ═══ -->
    <div class="tm-pending-band">
      <div class="tm-pending-l"><span class="tm-pending-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span><div><div class="tm-pending-title">2 pending invitations</div><div class="tm-pending-sub">Team members awaiting acceptance</div></div></div>
      <button type="button" class="btn btn-outline btn-sm" @click="showPending = true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> View</button>
    </div>

    <!-- ═══ FILTER BAR ═══ -->
    <div class="tm-filterbar">
      <div class="tm-search">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" placeholder="Search by name, role, or department..." />
      </div>
      <select class="form-select" v-model="statusFilter"><option value="">All Statuses</option><option value="active">Active</option><option value="idle">Idle</option><option value="inactive">Inactive</option></select>
    </div>

    <!-- ═══ MEMBER GRID ═══ -->
    <div class="tm-grid">
      <div v-for="m in filteredMembers" :key="m.id" class="tm-card">
        <div class="tm-card-top">
          <span class="tm-av"><span class="tm-av-text">{{ m.initials }}</span><span v-if="m.status !== 'inactive'" class="tm-av-dot" :class="m.status"></span></span>
          <div class="tm-card-info">
            <div class="tm-card-name">{{ m.name }}</div>
            <div class="tm-card-role">{{ m.title }}</div>
            <div class="tm-card-dept">{{ m.dept }} · {{ m.role }}</div>
          </div>
          <span class="tm-status" :class="'st-' + m.status">{{ m.statusLabel }}</span>
        </div>
        <div class="tm-card-stats">
          <div class="tm-cs"><div class="tm-cs-num">{{ m.contracts }}</div><div class="tm-cs-label">Contracts</div></div>
          <div class="tm-cs"><div class="tm-cs-role">{{ m.role }}</div><div class="tm-cs-label">Role</div></div>
        </div>
        <div class="tm-card-foot">
          <span class="tm-card-email">{{ m.email }}</span>
          <button type="button" class="btn-icon" @click="openMember(m)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
        </div>
      </div>
    </div>

    <!-- ═══ MEMBER DETAIL MODAL ═══ -->
    <Modal v-model="showMember" :title="sel.name || 'Member'" size="lg">
      <div class="tm-det-card">
        <span class="tm-av lg"><span class="tm-av-text">{{ sel.initials }}</span></span>
        <div>
          <div class="tm-det-name">{{ sel.name }}</div>
          <div class="tm-det-role">{{ sel.title }} — {{ sel.dept }}</div>
          <span class="tm-status" :class="'st-' + sel.status" style="margin-top:8px;display:inline-block">{{ sel.statusLabel }}</span>
        </div>
      </div>
      <div class="tm-det-rows">
        <div class="tm-det-row"><span class="tm-det-k">Email</span><span class="tm-det-v">{{ sel.email }}</span></div>
        <div class="tm-det-row"><span class="tm-det-k">Phone</span><span class="tm-det-v">{{ sel.phone }}</span></div>
        <div class="tm-det-row"><span class="tm-det-k">Department</span><span class="tm-det-v">{{ sel.dept }}</span></div>
        <div class="tm-det-row"><span class="tm-det-k">Member since</span><span class="tm-det-v">{{ sel.since }}</span></div>
        <div class="tm-det-row"><span class="tm-det-k">Active contracts</span><span class="tm-det-v">{{ sel.contracts }}</span></div>
      </div>
      <div class="tm-rp-head"><span class="tm-rp-eyebrow">Role &amp; Permissions</span><button type="button" class="tm-rp-link" @click="showMember = false; showRoles = true">View permission details →</button></div>
      <div class="tm-rp-controls">
        <select class="form-select" v-model="sel.role" style="width:auto;min-width:150px"><option>Admin</option><option>Manager</option><option>Specialist</option><option>Viewer</option></select>
        <button type="button" class="btn btn-gold btn-sm">Update Role</button>
      </div>
      <template #footer>
        <button type="button" class="btn btn-ghost btn-sm" @click="showMember = false">Close</button>
      </template>
    </Modal>

    <!-- ═══ PENDING INVITATIONS MODAL ═══ -->
    <Modal v-model="showPending" title="Pending invitations" size="sm">
      <div class="tm-inv-list">
        <div v-for="(p, i) in pendingInvites" :key="i" class="tm-inv-row">
          <span class="tm-inv-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
          <div class="tm-inv-info"><div class="tm-inv-email">{{ p.email }}</div><div class="tm-inv-meta">{{ p.meta }}</div></div>
          <button type="button" class="btn-icon"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-ghost btn-sm" @click="showPending = false">Close</button>
        <button type="button" class="btn btn-gold btn-sm" @click="showPending = false; showInvite = true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Invite Another</button>
      </template>
    </Modal>

    <!-- ═══ INVITE MEMBER MODAL ═══ -->
    <Modal v-model="showInvite" title="Invite team member" size="lg">
      <div class="tm-field"><label class="tm-label">Email address <span class="tm-req">*</span></label><input class="tm-input" v-model="invite.email" placeholder="colleague@example.com" /><div class="tm-hint">They will receive an invitation link valid for 7 days.</div></div>
      <div class="tm-field"><label class="tm-label">Permission role <span class="tm-req">*</span></label><select class="form-select" v-model="invite.role"><option>Admin</option><option>Manager</option><option>Specialist</option><option>Viewer</option></select></div>
      <div class="tm-field"><label class="tm-label">Job title</label><input class="tm-input" v-model="invite.title" placeholder="e.g. EMR Specialist" /></div>
      <div class="tm-field"><label class="tm-label">Personal message <span class="tm-optional">(optional)</span></label><textarea class="tm-input tm-textarea" rows="3" v-model="invite.message" placeholder="Add a welcome note..."></textarea></div>
      <template #footer>
        <button type="button" class="btn btn-ghost btn-sm" @click="showInvite = false">Cancel</button>
        <button type="button" class="btn btn-gold btn-sm" @click="showInvite = false"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg> Send Invitation</button>
      </template>
    </Modal>

    <!-- ═══ ROLES & PERMISSIONS MODAL ═══ -->
    <Modal v-model="showRoles" title="Roles and permissions" size="lg">
      <div class="tm-roles-list">
        <div v-for="r in roleDefs" :key="r.name" class="tm-role-def"><div class="tm-role-name">{{ r.name }}</div><div class="tm-role-desc">{{ r.desc }}</div></div>
      </div>
      <div class="tm-modal-section">Permission Matrix</div>
      <table class="tm-matrix">
        <thead><tr><th>Permission</th><th class="c">Admin</th><th class="c">Manager</th><th class="c">Specialist</th><th class="c">Viewer</th></tr></thead>
        <tbody>
          <tr v-for="row in matrix" :key="row.perm">
            <td class="tm-mx-perm">{{ row.perm }}</td>
            <td v-for="(v, ci) in row.vals" :key="ci" class="c">
              <svg v-if="v" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--green-dark)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              <span v-else class="tm-mx-dash">—</span>
            </td>
          </tr>
        </tbody>
      </table>
      <template #footer>
        <button type="button" class="btn btn-ghost btn-sm" @click="showRoles = false">Close</button>
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
const statusFilter = ref('');

const members = [
  { id: 1, initials: 'JO', name: "James O'Brien", title: 'Compliance Analyst', dept: 'Legal', role: 'Specialist', status: 'active', statusLabel: 'Active', contracts: 3, email: 'james.obrien@acme-practice.com', phone: '(415) 555-0101', since: 'Mar 2024' },
  { id: 2, initials: 'MR', name: 'Marcus Rivera', title: 'EMR Specialist', dept: 'Technical', role: 'Manager', status: 'active', statusLabel: 'Active', contracts: 3, email: 'marcus.rivera@acme-practice.com', phone: '(415) 555-0102', since: 'Jan 2024' },
  { id: 3, initials: 'PN', name: 'Priya Nair', title: 'Billing Coordinator', dept: 'Finance', role: 'Specialist', status: 'active', statusLabel: 'Active', contracts: 3, email: 'priya.nair@acme-practice.com', phone: '(415) 555-0103', since: 'Jun 2024' },
  { id: 4, initials: 'SC', name: 'Sarah Chen', title: 'Healthcare IT Lead', dept: 'Operations', role: 'Admin', status: 'active', statusLabel: 'Active', contracts: 3, email: 'sarah.chen@acme-practice.com', phone: '(415) 555-0104', since: 'Nov 2023' },
  { id: 5, initials: 'AD', name: 'Amara Diallo', title: 'Client Success Manager', dept: 'Operations', role: 'Manager', status: 'idle', statusLabel: 'Idle', contracts: 3, email: 'amara.diallo@acme-practice.com', phone: '(415) 555-0105', since: 'Feb 2024' },
  { id: 6, initials: 'KP', name: 'Kevin Park', title: 'Data Analyst', dept: 'Technical', role: 'Viewer', status: 'inactive', statusLabel: 'Inactive', contracts: 3, email: 'kevin.park@acme-practice.com', phone: '(415) 555-0106', since: 'Apr 2024' },
];

const filteredMembers = computed(() => {
  return members.filter(m => {
    if (statusFilter.value && m.status !== statusFilter.value) return false;
    if (search.value) {
      const q = search.value.toLowerCase();
      if (!m.name.toLowerCase().includes(q) && !m.role.toLowerCase().includes(q) && !m.dept.toLowerCase().includes(q) && !m.title.toLowerCase().includes(q)) return false;
    }
    return true;
  });
});

const pendingInvites = [
  { email: 'david.wu@email.com', meta: 'EMR Specialist · Specialist · Sent Jun 14' },
  { email: 'lisa.torres@email.com', meta: 'Billing Coordinator · Specialist · Sent Jun 9' },
];

const roleDefs = [
  { name: 'Admin', desc: 'Full access to all agency settings, contracts, and billing' },
  { name: 'Manager', desc: 'Can manage team, contracts, and proposals' },
  { name: 'Specialist', desc: 'Can view and work on assigned contracts and tasks' },
  { name: 'Viewer', desc: 'Read-only access to contracts and reports' },
];

const matrix = [
  { perm: 'View Contracts', vals: [true, true, true, true] },
  { perm: 'Create Proposals', vals: [true, true, true, false] },
  { perm: 'Sign Contracts', vals: [true, true, false, false] },
  { perm: 'Manage Team Members', vals: [true, true, false, false] },
  { perm: 'View Finances', vals: [true, true, true, false] },
  { perm: 'Process Invoices', vals: [true, true, true, false] },
  { perm: 'Access Settings', vals: [true, false, false, false] },
  { perm: 'Manage Billing', vals: [true, false, false, false] },
];

// ── modals ──
const showMember = ref(false);
const sel = ref({});
function openMember(m) { sel.value = m; showMember.value = true; }

const showPending = ref(false);
const showInvite = ref(false);
const invite = reactive({ email: '', role: 'Admin', title: '', message: '' });

const showRoles = ref(false);
</script>

<style scoped>
.tm-header { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--gold-dark); border-radius:var(--radius-xl,18px); padding:28px 32px; margin-bottom:16px; display:flex; align-items:flex-start; justify-content:space-between; gap:20px; box-shadow:var(--shadow-sm); }
.tm-title { font-family:var(--font-serif); font-size:32px; font-weight:600; letter-spacing:-0.5px; color:var(--text); margin:0; line-height:1.05; }
.tm-subtitle { font-size:13px; color:var(--text-3); margin-top:8px; }
.tm-tagline { display:inline-flex; align-items:center; gap:6px; font-size:12px; color:var(--text-4); margin-top:10px; }
.tm-header-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; }

.tm-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px; }
.tm-stat { display:flex; align-items:center; gap:13px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px 20px; box-shadow:var(--shadow-xs); }
.tm-stat-ico { width:34px; height:34px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.tm-stat-num { font-family:var(--font-serif); font-size:22px; font-weight:700; color:var(--text); line-height:1; }
.tm-stat-label { font-size:11px; color:var(--text-3); margin-top:5px; }

.tm-pending-band { display:flex; align-items:center; justify-content:space-between; gap:16px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px 22px; margin-bottom:18px; box-shadow:var(--shadow-xs); }
.tm-pending-l { display:flex; align-items:center; gap:13px; }
.tm-pending-ico { width:34px; height:34px; border-radius:var(--radius-sm); background:var(--icon-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.tm-pending-title { font-size:13.5px; font-weight:700; color:var(--text); }
.tm-pending-sub { font-size:12px; color:var(--text-3); margin-top:2px; }

.tm-filterbar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:16px; }
.tm-search { display:flex; align-items:center; gap:8px; flex:1; min-width:240px; padding:10px 15px; background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius-full); color:var(--text-4); }
.tm-search input { flex:1; border:none; outline:none; background:none; font-size:13px; color:var(--text); }
.tm-filterbar .form-select { width:auto; min-width:150px; border-radius:var(--radius-full); }

.tm-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }
.tm-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:var(--shadow-xs); overflow:hidden; transition:transform .18s ease,box-shadow .18s ease; }
.tm-card:hover { transform:translateY(-2px); box-shadow:var(--shadow-sm); }
.tm-card-top { display:flex; align-items:flex-start; gap:12px; padding:18px 20px 16px; }
.tm-av { position:relative; width:40px; height:40px; border-radius:50%; background:var(--gold-dark); display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; }
.tm-av.lg { width:52px; height:52px; }
.tm-av-text { font-family:var(--font-serif); font-weight:700; font-size:13px; color:var(--text-inverted); }
.tm-av.lg .tm-av-text { font-size:17px; }
.tm-av-dot { position:absolute; right:0; bottom:0; width:11px; height:11px; border-radius:50%; border:2px solid var(--surface); }
.tm-av-dot.active { background:var(--green); }
.tm-av-dot.idle { background:var(--gold); }
.tm-card-info { flex:1; min-width:0; }
.tm-card-name { font-size:14.5px; font-weight:700; color:var(--text); }
.tm-card-role { font-size:12.5px; color:var(--text-2); margin-top:2px; }
.tm-card-dept { font-size:11.5px; color:var(--text-4); margin-top:2px; }
.tm-status { font-size:9px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; padding:4px 10px; border-radius:var(--radius-full); flex-shrink:0; }
.tm-status.st-active { background:var(--green-light); color:var(--green-dark); }
.tm-status.st-idle { background:var(--badge-bg-gold); color:var(--gold-dark); }
.tm-status.st-inactive { background:var(--surface-3); color:var(--text-4); }
.tm-card-stats { display:grid; grid-template-columns:1fr 1fr; border-top:1px solid var(--border); }
.tm-cs { padding:14px 20px; }
.tm-cs:last-child { border-left:1px solid var(--border); }
.tm-cs-num { font-family:var(--font-serif); font-size:20px; font-weight:700; color:var(--text); }
.tm-cs-role { font-family:var(--font-serif); font-size:18px; font-weight:600; color:var(--text); }
.tm-cs-label { font-size:9.5px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:var(--text-4); margin-top:4px; }
.tm-card-foot { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:12px 20px; border-top:1px solid var(--border); background:var(--surface-2); }
.tm-card-email { font-size:11.5px; color:var(--text-3); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

/* member detail modal */
.tm-det-card { display:flex; align-items:center; gap:16px; background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius-lg); padding:18px 20px; margin-bottom:18px; }
.tm-det-name { font-family:var(--font-serif); font-size:18px; font-weight:600; color:var(--text); }
.tm-det-role { font-size:13px; color:var(--text-3); margin-top:3px; }
.tm-det-rows { border-top:1px solid var(--border); }
.tm-det-row { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:13px 2px; border-bottom:1px solid var(--border); }
.tm-det-k { font-size:12.5px; color:var(--text-3); }
.tm-det-v { font-size:13.5px; font-weight:600; color:var(--text); }
.tm-rp-head { display:flex; align-items:center; justify-content:space-between; gap:14px; margin-top:20px; }
.tm-rp-eyebrow { font-size:10px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:var(--text-4); }
.tm-rp-link { font-size:12.5px; font-weight:600; color:var(--gold-dark); background:none; border:none; cursor:pointer; }
.tm-rp-controls { display:flex; align-items:center; gap:10px; margin-top:12px; }

/* pending invites modal */
.tm-inv-list { display:flex; flex-direction:column; gap:10px; }
.tm-inv-row { display:flex; align-items:center; gap:12px; border:1px solid var(--border); border-radius:var(--radius); padding:12px 14px; }
.tm-inv-ico { width:32px; height:32px; border-radius:50%; background:var(--badge-bg-gold); color:var(--gold-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.tm-inv-info { flex:1; min-width:0; }
.tm-inv-email { font-size:13px; font-weight:700; color:var(--text); }
.tm-inv-meta { font-size:11.5px; color:var(--text-4); margin-top:2px; }

/* invite modal fields */
.tm-field { margin-bottom:16px; }
.tm-field:last-of-type { margin-bottom:0; }
.tm-label { display:block; font-size:12.5px; font-weight:600; color:var(--text-2); margin-bottom:7px; }
.tm-req { color:var(--red); }
.tm-optional { color:var(--text-4); font-weight:400; }
.tm-input { display:block; width:100%; padding:10px 13px; font-size:13px; color:var(--text); background:var(--surface); border:1px solid var(--border-dark); border-radius:var(--radius); outline:none; transition:border-color .15s ease; }
.tm-input:focus { border-color:var(--gold-dark); }
.tm-input::placeholder { color:var(--text-4); }
.tm-textarea { resize:vertical; min-height:84px; line-height:1.55; font-family:var(--font-sans); }
.tm-hint { font-size:11.5px; color:var(--text-4); margin-top:6px; }

/* roles modal */
.tm-roles-list { display:flex; flex-direction:column; }
.tm-role-def { padding:14px 2px; border-bottom:1px solid var(--border); }
.tm-role-name { font-size:14px; font-weight:700; color:var(--text); }
.tm-role-desc { font-size:12.5px; color:var(--text-3); margin-top:4px; }
.tm-modal-section { font-size:10px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:var(--text-4); margin:20px 0 8px; }
.tm-matrix { width:100%; border-collapse:collapse; }
.tm-matrix th { font-size:9.5px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:var(--text-4); padding:10px 8px; border-bottom:1px solid var(--border); text-align:left; }
.tm-matrix th.c, .tm-matrix td.c { text-align:center; }
.tm-matrix td { padding:12px 8px; border-bottom:1px solid var(--border); font-size:13px; color:var(--text-2); }
.tm-matrix tbody tr:last-child td { border-bottom:none; }
.tm-mx-perm { font-weight:600; color:var(--text); }
.tm-mx-dash { color:var(--text-4); }

@media (max-width:1100px) { .tm-grid { grid-template-columns:1fr 1fr; } }
@media (max-width:1000px) { .tm-stats { grid-template-columns:repeat(2,1fr); } }
@media (max-width:760px) { .tm-header { flex-direction:column; } .tm-stats { grid-template-columns:1fr; } .tm-grid { grid-template-columns:1fr; } .tm-matrix { display:block; overflow-x:auto; } }
</style>
