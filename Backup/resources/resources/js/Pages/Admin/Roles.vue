<template>
  <AppLayout :user="user" portal="admin" activePage="roles" pageTitle="Roles &amp; Permissions">
    <div class="hero-banner is-quiet">
      <div class="page-hero-inner">
        <div class="page-hero-text">
          <div class="page-hero-eyebrow">Security</div>
          <h1 class="page-hero-title">Roles &amp; Permissions</h1>
          <p class="page-hero-sub">Create, edit, and remove roles and permissions, and control exactly what each role can do.</p>
        </div>
        <div class="page-hero-actions">
          <button v-if="!isPermissionsView" class="btn btn-primary btn-sm" @click="openCreateRole">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Role
          </button>
          <button v-else class="btn btn-primary btn-sm" @click="openCreatePermission">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Permission
          </button>
        </div>
      </div>
    </div>

    <!-- TABS -->
    <div class="tabs-segmented mb-24">
      <a href="/admin/roles" class="tab-pill-link" :class="{ active: !isPermissionsView }">Roles</a>
      <a href="/admin/permissions" class="tab-pill-link" :class="{ active: isPermissionsView }">Permissions</a>
    </div>

    <!-- VIEW 1: ROLES -->
    <div v-if="!isPermissionsView">
      <div class="roles-grid">
        <div v-for="role in roles" :key="role.id" class="role-card">
          <div class="role-card-header">
            <div class="role-title-row">
              <span class="role-icon-box">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              </span>
              <h3 class="role-card-name">{{ role.name }}</h3>
            </div>
            <span class="users-count-badge">{{ role.users_count }} user{{ role.users_count === 1 ? '' : 's' }}</span>
          </div>

          <div class="role-card-body">
            <h4 class="card-section-label">Permissions Assigned ({{ role.permissions_count }})</h4>
            <div class="role-perms-chips">
              <span v-for="p in role.permissions" :key="p" class="perm-tag">{{ p }}</span>
              <span v-if="role.permissions.length === 0" class="empty-tag-text">No permissions assigned</span>
            </div>
          </div>

          <div class="role-card-footer">
            <span class="guard-label">Guard: {{ role.guard_name }}</span>
            <div class="card-actions">
              <button class="btn-icon-sm" title="Edit role" @click="openEditRole(role)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg></button>
              <button class="btn-icon-sm btn-icon-danger" :class="{ disabled: role.users_count > 0 }" :title="role.users_count > 0 ? 'Role has assigned users' : 'Delete role'" @click="deleteRole(role)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
            </div>
          </div>
        </div>

        <div v-if="!roles || roles.length === 0" class="empty-table-text">No roles yet. Create your first role.</div>
      </div>
    </div>

    <!-- VIEW 2: PERMISSIONS -->
    <div v-else>
      <div class="permissions-modules-list">
        <div v-for="group in permissionGroups" :key="group.module" class="module-card">
          <div class="module-card-header">
            <h3 class="module-card-title">{{ group.module.toUpperCase() }} Module</h3>
            <span class="module-perms-count">{{ group.count }} permission{{ group.count === 1 ? '' : 's' }}</span>
          </div>
          <div class="module-card-body">
            <div class="module-perms-grid">
              <div v-for="p in group.permissions" :key="p.id" class="perm-item-box">
                <span class="perm-bullet"></span>
                <span class="perm-name">{{ p.name }}</span>
                <div class="perm-item-actions">
                  <button class="btn-icon-xs" title="Edit" @click="openEditPermission(p)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg></button>
                  <button class="btn-icon-xs btn-icon-danger" title="Delete" @click="deletePermission(p)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg></button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-if="!permissionGroups || permissionGroups.length === 0" class="empty-table-text">No permissions registered.</div>
      </div>
    </div>

    <!-- ROLE MODAL -->
    <Modal :model-value="roleModal" @update:model-value="roleModal = false" size="lg" :title="editingRoleId ? 'Edit Role' : 'Create Role'">
      <div class="form-group">
        <label class="form-label">Role Name <span class="req">*</span></label>
        <input class="form-input" v-model="roleForm.name" placeholder="e.g., billing_manager" @keyup.enter="submitRole">
        <div v-if="roleForm.errors.name" class="form-err">{{ roleForm.errors.name }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">Permissions <span class="opt">({{ roleForm.permissions.length }} selected)</span></label>
        <div class="perm-pick-toolbar">
          <button type="button" class="link-btn" @click="roleForm.permissions = [...allPermissionsList]">Select all</button>
          <button type="button" class="link-btn" @click="roleForm.permissions = []">Clear</button>
        </div>
        <div class="perm-pick-grid">
          <label v-for="p in allPermissionsList" :key="p" class="perm-pick">
            <input type="checkbox" :value="p" v-model="roleForm.permissions">
            <span>{{ p }}</span>
          </label>
          <div v-if="allPermissionsList.length === 0" class="empty-tag-text">No permissions exist yet — create some first.</div>
        </div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="roleModal = false">Cancel</button>
        <button class="btn btn-primary" :disabled="roleForm.processing" @click="submitRole">{{ roleForm.processing ? 'Saving…' : (editingRoleId ? 'Save Changes' : 'Create Role') }}</button>
      </template>
    </Modal>

    <!-- PERMISSION MODAL -->
    <Modal :model-value="permModal" @update:model-value="permModal = false" size="sm" :title="editingPermId ? 'Edit Permission' : 'Create Permission'">
      <div class="form-group">
        <label class="form-label">Permission Name <span class="req">*</span></label>
        <input class="form-input" v-model="permForm.name" placeholder="e.g., users.create" @keyup.enter="submitPermission">
        <div v-if="permForm.errors.name" class="form-err">{{ permForm.errors.name }}</div>
        <div class="form-hint">Use a <code>module.action</code> convention (e.g. <code>payments.refund</code>) to group permissions.</div>
      </div>
      <template #footer>
        <button class="btn btn-outline" @click="permModal = false">Cancel</button>
        <button class="btn btn-primary" :disabled="permForm.processing" @click="submitPermission">{{ permForm.processing ? 'Saving…' : (editingPermId ? 'Save Changes' : 'Create Permission') }}</button>
      </template>
    </Modal>

    <Teleport to="body">
      <div class="rp-toast-stack">
        <div v-for="t in toasts" :key="t.id" class="rp-toast" :class="t.type">
          <svg v-if="t.type === 'success'" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
          <span>{{ t.msg }}</span>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Components/AppLayout.vue';
import Modal from '../../Components/Modal.vue';

const props = defineProps({
  user: Object,
  roles: { type: Array, default: () => [] },
  allPermissions: { type: Array, default: () => [] },
  permissionGroups: { type: Array, default: () => [] },
  view: { type: String, default: null },
});

const isPermissionsView = computed(() => props.view === 'permissions');
const allPermissionsList = computed(() => props.allPermissions || []);

/* ── Toasts (from flash) ── */
const toasts = ref([]);
let tid = 0;
function showToast(msg, type = 'info') {
  const id = ++tid;
  toasts.value.push({ id, msg, type });
  setTimeout(() => { toasts.value = toasts.value.filter((t) => t.id !== id); }, 3400);
}
const page = usePage();
watch(() => page.props.flash, (f) => { if (f && f.message) showToast(f.message, f.type || 'info'); }, { deep: true });

/* ── Role CRUD ── */
const roleModal = ref(false);
const editingRoleId = ref(null);
const roleForm = useForm({ name: '', permissions: [] });

function openCreateRole() {
  editingRoleId.value = null;
  roleForm.reset();
  roleForm.clearErrors();
  roleModal.value = true;
}
function openEditRole(role) {
  editingRoleId.value = role.id;
  roleForm.clearErrors();
  roleForm.name = role.name;
  roleForm.permissions = [...role.permissions];
  roleModal.value = true;
}
function submitRole() {
  const opts = { preserveScroll: true, onSuccess: () => { roleModal.value = false; roleForm.reset(); } };
  if (editingRoleId.value) roleForm.put(`/admin/roles/${editingRoleId.value}`, opts);
  else roleForm.post('/admin/roles', opts);
}
function deleteRole(role) {
  if (role.users_count > 0) { showToast(`"${role.name}" has ${role.users_count} assigned user(s) — reassign them first.`, 'error'); return; }
  if (!window.confirm(`Delete the role "${role.name}"? This cannot be undone.`)) return;
  router.delete(`/admin/roles/${role.id}`, { preserveScroll: true });
}

/* ── Permission CRUD ── */
const permModal = ref(false);
const editingPermId = ref(null);
const permForm = useForm({ name: '' });

function openCreatePermission() {
  editingPermId.value = null;
  permForm.reset();
  permForm.clearErrors();
  permModal.value = true;
}
function openEditPermission(p) {
  editingPermId.value = p.id;
  permForm.clearErrors();
  permForm.name = p.name;
  permModal.value = true;
}
function submitPermission() {
  const opts = { preserveScroll: true, onSuccess: () => { permModal.value = false; permForm.reset(); } };
  if (editingPermId.value) permForm.put(`/admin/permissions/${editingPermId.value}`, opts);
  else permForm.post('/admin/permissions', opts);
}
function deletePermission(p) {
  if (!window.confirm(`Delete the permission "${p.name}"? It will be removed from all roles.`)) return;
  router.delete(`/admin/permissions/${p.id}`, { preserveScroll: true });
}
</script>

<style scoped>
.hero-banner.is-quiet {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-xl, 18px); padding: 28px 32px;
  margin-bottom: 22px; border-left: 4px solid var(--gold-dark, #a0813e);
}
.page-hero-inner { display: flex; justify-content: space-between; align-items: center; gap: 20px; }
.page-hero-eyebrow { font-size: 10px; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase; color: var(--gold-dark); margin-bottom: 8px; }
.page-hero-title { font-family: var(--font-serif, 'Spectral', Georgia, serif); font-size: 28px; font-weight: 600; color: var(--text); margin: 0; letter-spacing: -0.3px; }
.page-hero-sub { font-size: 13.5px; color: var(--text-3); line-height: 1.6; margin-top: 6px; max-width: 600px; }
.page-hero-actions { flex-shrink: 0; }

.tabs-segmented { display: inline-flex; background: var(--surface-2); padding: 4px; border-radius: var(--radius-lg); border: 1px solid var(--border); }
.tab-pill-link { padding: 8px 18px; font-size: 13px; font-weight: 600; text-decoration: none; color: var(--text-3); border-radius: var(--radius); transition: all 0.18s; }
.tab-pill-link:hover { color: var(--text); }
.tab-pill-link.active { background: var(--surface); color: var(--gold-dark); box-shadow: var(--shadow-xs); }
.mb-24 { margin-bottom: 24px; }

/* Roles Grid */
.roles-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
.role-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-xl); box-shadow: var(--shadow-xs); display: flex; flex-direction: column; overflow: hidden; }
.role-card-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid var(--border); background: var(--surface-2); }
.role-title-row { display: flex; align-items: center; gap: 10px; }
.role-icon-box { width: 32px; height: 32px; border-radius: var(--radius-sm); background: rgba(196,169,106,0.1); color: var(--gold-dark); display: flex; align-items: center; justify-content: center; }
.role-card-name { font-family: var(--font-serif); font-size: 16px; font-weight: 700; color: var(--text); margin: 0; }
.users-count-badge { font-size: 11.5px; font-weight: 600; color: var(--gold-dark); }
.role-card-body { padding: 20px; flex: 1; }
.card-section-label { font-size: 10.5px; font-weight: 700; text-transform: uppercase; color: var(--text-4); margin-bottom: 10px; }
.role-perms-chips { display: flex; flex-wrap: wrap; gap: 5px; }
.perm-tag { background: #f7f5f1; border: 1px solid var(--border); color: var(--text-2); font-size: 11px; font-weight: 600; padding: 2px 6px; border-radius: 4px; font-family: monospace; }
.empty-tag-text { font-size: 12.5px; color: var(--text-4); font-style: italic; }
.role-card-footer { padding: 12px 20px; border-top: 1px solid var(--border); background: var(--surface-2); display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.guard-label { font-size: 11px; color: var(--text-4); font-weight: 600; }
.card-actions { display: flex; gap: 6px; }

/* Permissions modules */
.permissions-modules-list { display: flex; flex-direction: column; gap: 16px; }
.module-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-xl); overflow: hidden; box-shadow: var(--shadow-xs); }
.module-card-header { display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-bottom: 1px solid var(--border); background: var(--surface-2); }
.module-card-title { font-family: var(--font-serif); font-size: 15px; font-weight: 700; color: var(--text); margin: 0; }
.module-perms-count { font-size: 11.5px; font-weight: 600; color: var(--gold-dark); }
.module-card-body { padding: 16px 20px; }
.module-perms-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
.perm-item-box { display: flex; align-items: center; gap: 8px; padding: 7px 10px; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--surface); }
.perm-item-box:hover { border-color: var(--gold-light); }
.perm-bullet { width: 6px; height: 6px; background: var(--gold); border-radius: 50%; flex-shrink: 0; }
.perm-name { font-size: 12.5px; font-family: monospace; color: var(--text-2); font-weight: 600; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.perm-item-actions { display: flex; gap: 3px; flex-shrink: 0; opacity: 0; transition: opacity 0.15s; }
.perm-item-box:hover .perm-item-actions { opacity: 1; }

.empty-table-text { text-align: center; color: var(--text-4); padding: 32px 16px; }

/* Icon buttons */
.btn-icon-sm, .btn-icon-xs { display: inline-flex; align-items: center; justify-content: center; border: 1px solid var(--border); background: var(--surface); border-radius: var(--radius-sm); color: var(--text-3); cursor: pointer; transition: all 0.15s; }
.btn-icon-sm { width: 28px; height: 28px; }
.btn-icon-xs { width: 22px; height: 22px; }
.btn-icon-sm:hover, .btn-icon-xs:hover { border-color: var(--gold); color: var(--gold-dark); }
.btn-icon-danger:hover { border-color: var(--red); color: var(--red); background: var(--red-light); }
.btn-icon-sm.disabled { opacity: 0.4; cursor: not-allowed; }
.btn-icon-sm.disabled:hover { border-color: var(--border); color: var(--text-3); background: var(--surface); }

/* Modal form bits */
.form-group { margin-bottom: 18px; }
.form-label { display: block; font-size: 10.5px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; color: var(--text-2); margin-bottom: 6px; }
.req { color: var(--red); }
.opt { font-weight: 400; letter-spacing: 0; text-transform: none; color: var(--text-4); }
.form-err { font-size: 11.5px; color: var(--red); margin-top: 5px; }
.form-hint { font-size: 11.5px; color: var(--text-4); margin-top: 6px; line-height: 1.5; }
.form-hint code, .perm-name code { font-family: monospace; background: var(--surface-3); padding: 1px 4px; border-radius: 3px; }
.perm-pick-toolbar { display: flex; gap: 14px; margin-bottom: 10px; }
.link-btn { background: none; border: none; padding: 0; font-size: 12px; font-weight: 600; color: var(--gold-dark); cursor: pointer; }
.link-btn:hover { text-decoration: underline; }
.perm-pick-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px 16px; max-height: 320px; overflow-y: auto; padding: 4px; border: 1px solid var(--border); border-radius: var(--radius-sm); }
.perm-pick { display: flex; align-items: center; gap: 9px; font-size: 12.5px; font-family: monospace; color: var(--text-2); cursor: pointer; padding: 4px; }
.perm-pick input { width: 16px; height: 16px; accent-color: var(--gold-dark); cursor: pointer; flex-shrink: 0; }

/* Toasts */
.rp-toast-stack { position: fixed; bottom: 22px; right: 22px; z-index: 4000; display: flex; flex-direction: column; gap: 10px; }
.rp-toast { display: flex; align-items: center; gap: 9px; padding: 11px 16px; border-radius: var(--radius); background: var(--text); color: var(--text-inverted); font-size: 13px; font-weight: 600; box-shadow: var(--shadow-lg); max-width: 380px; }
.rp-toast.success { background: var(--green-dark); }
.rp-toast.error { background: var(--red); }
.rp-toast svg { flex-shrink: 0; }

@media (max-width: 900px) {
  .page-hero-inner { flex-direction: column; align-items: flex-start; gap: 14px; }
  .roles-grid { grid-template-columns: 1fr; }
  .module-perms-grid { grid-template-columns: 1fr 1fr; }
  .perm-pick-grid { grid-template-columns: 1fr; }
}
</style>
