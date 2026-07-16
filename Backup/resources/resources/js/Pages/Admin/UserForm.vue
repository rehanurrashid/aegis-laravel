<template>
  <AppLayout :user="user" portal="admin" activePage="users" pageTitle="Edit User Access">
    <div class="settings-layout">
      <!-- Left sidebar summary card -->
      <div class="summary-card">
        <div class="user-avatar-large">{{ editUser.display_name.substring(0, 2).toUpperCase() }}</div>
        <h3 class="summary-title">{{ editUser.display_name }}</h3>
        <p class="summary-desc">{{ editUser.email }}</p>

        <div class="summary-meta">
          <div class="meta-row">
            <span class="meta-label">Original Portal:</span>
            <span class="meta-value">{{ editUser.role }}</span>
          </div>
          <div class="meta-row">
            <span class="meta-label">Verified Status:</span>
            <span class="meta-value badge" :class="editUser.verified ? 'badge-verified' : 'badge-unverified'">
              {{ editUser.verified ? 'Verified' : 'Unverified' }}
            </span>
          </div>
          <div class="meta-row" v-if="editUser.tier">
            <span class="meta-label">Subscription Tier:</span>
            <span class="meta-value font-uppercase">{{ editUser.tier }}</span>
          </div>
        </div>
      </div>

      <!-- Main Form Panel -->
      <div class="settings-panel">
        <h2 class="settings-section-title">Modify Account Access</h2>
        <p class="settings-section-desc">Change the user type, details, and configure Spatie roles/permissions below.</p>

        <form @submit.prevent="submit">
          <div class="form-section-title">Core Profile</div>
          
          <div class="pg-form-group">
            <label class="pg-form-label">Display Name</label>
            <input
              v-model="form.display_name"
              type="text"
              class="pg-form-input"
              :class="{ error: form.errors.display_name }"
              required
            />
            <span v-if="form.errors.display_name" class="form-error">{{ form.errors.display_name }}</span>
          </div>

          <div class="pg-form-group">
            <label class="pg-form-label">Email Address</label>
            <input
              v-model="form.email"
              type="email"
              class="pg-form-input"
              :class="{ error: form.errors.email }"
              required
            />
            <span v-if="form.errors.email" class="form-error">{{ form.errors.email }}</span>
          </div>

          <div class="pg-form-row">
            <div class="pg-form-group">
              <label class="pg-form-label">User Type (Aegis Role)</label>
              <select
                v-model.number="form.user_type"
                class="pg-form-input select-input"
                :class="{ error: form.errors.user_type }"
                required
              >
                <option :value="0">Administrator</option>
                <option :value="1">Provider</option>
                <option :value="2">Continuity Steward (CS)</option>
                <option :value="3">Support Steward (SS)</option>
                <option :value="4">Business Partner (BP)</option>
              </select>
              <span v-if="form.errors.user_type" class="form-error">{{ form.errors.user_type }}</span>
            </div>

            <div class="pg-form-group checkbox-cell">
              <label class="checkbox-label">
                <input type="checkbox" v-model="form.verified" class="checkbox-input" />
                <span>Mark User Verified</span>
              </label>
            </div>
          </div>

          <!-- SPATIE ROLES ASSIGNMENT -->
          <div class="form-section-title">Spatie Security Roles</div>
          <p class="section-desc-small">Select one or more Spatie roles to assign to this user. Roles group multiple permissions together.</p>
          <div class="roles-checklist-grid">
            <div v-for="r in allRoles" :key="r.id" class="role-checkbox-item">
              <label class="checkbox-label">
                <input
                  type="checkbox"
                  :value="r.name"
                  v-model="form.roles"
                  class="checkbox-input"
                />
                <div class="role-info">
                  <span class="role-name-text">{{ r.name }}</span>
                  <span class="role-perms-preview">Permissions: {{ r.permissions.join(', ') || 'none' }}</span>
                </div>
              </label>
            </div>
            <div v-if="allRoles.length === 0" class="no-items-text">No roles created in the system yet.</div>
          </div>

          <!-- DIRECT PERMISSIONS -->
          <div class="form-section-title">Direct Permissions Override</div>
          <p class="section-desc-small">Assign permissions directly to this user as overrides, bypassing role assignments.</p>
          <div class="perms-grid-wrapper">
            <div v-for="perm in allPermissions" :key="perm" class="perm-checkbox-item">
              <label class="checkbox-label compact">
                <input
                  type="checkbox"
                  :value="perm"
                  v-model="form.permissions"
                  class="checkbox-input"
                />
                <span class="perm-name-text">{{ perm }}</span>
              </label>
            </div>
            <div v-if="allPermissions.length === 0" class="no-items-text">No permissions created in the system yet.</div>
          </div>

          <div class="form-actions">
            <a href="/admin/users" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              {{ form.processing ? 'Saving...' : 'Update User Access' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '../../Components/AppLayout.vue';

const props = defineProps({
  user: Object,
  editUser: Object,
  allRoles: Array,
  allPermissions: Array,
});

const form = useForm({
  display_name: props.editUser.display_name,
  email: props.editUser.email,
  user_type: props.editUser.user_type,
  verified: !!props.editUser.verified,
  roles: props.editUser.assigned_roles || [],
  permissions: props.editUser.assigned_permissions || [],
});

function submit() {
  form.put('/admin/users/' + props.editUser.id);
}
</script>

<style scoped>
.settings-layout { display: grid; grid-template-columns: 240px 1fr; gap: 28px; }
.summary-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-xl, 18px); padding: 24px;
  height: fit-content; text-align: center;
}
.user-avatar-large {
  width: 72px; height: 72px; border-radius: 50%;
  background: var(--gold-dark); color: #fff; font-family: var(--font-serif);
  font-size: 26px; font-weight: 700; display: flex; align-items: center;
  justify-content: center; margin: 0 auto 16px;
}
.summary-title { font-family: var(--font-serif); font-size: 18px; font-weight: 600; color: var(--text); margin-bottom: 4px; }
.summary-desc { font-size: 12.5px; color: var(--text-4); margin-bottom: 20px; word-break: break-all; }
.summary-meta { border-top: 1px solid var(--border); padding-top: 16px; display: flex; flex-direction: column; gap: 12px; text-align: left; }
.meta-row { display: flex; justify-content: space-between; align-items: center; font-size: 12.5px; }
.meta-label { font-weight: 600; color: var(--text-4); }
.meta-value { color: var(--text); }
.font-uppercase { text-transform: uppercase; font-weight: 700; font-size: 11px; }

.badge { padding: 1px 8px; border-radius: var(--radius-full); font-size: 10px; font-weight: 700; }
.badge-verified { background: #e8f5e9; color: #2e7d32; }
.badge-unverified { background: #efeff1; color: #555; }

.settings-panel { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-xl, 18px); padding: 28px 30px; }
.settings-section-title { font-family: var(--font-serif); font-size: 18px; font-weight: 600; color: var(--text); margin-bottom: 6px; }
.settings-section-desc { font-size: 13px; color: var(--text-3); margin-bottom: 22px; line-height: 1.5; }

.form-section-title {
  font-family: var(--font-serif); font-size: 15px; font-weight: 700;
  color: var(--gold-dark); border-bottom: 1px solid var(--border);
  padding-bottom: 6px; margin: 24px 0 12px;
}
.section-desc-small { font-size: 12px; color: var(--text-4); margin: -4px 0 12px; }

.pg-form-group { margin-bottom: 18px; }
.pg-form-label { display: block; font-size: 10.5px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; color: var(--text-2); margin-bottom: 6px; }
.pg-form-input { display: block; width: 100%; padding: 10px 13px; font-size: 13px; color: var(--text); background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm, 8px); transition: border-color var(--transition), box-shadow var(--transition); outline: none; }
.pg-form-input:focus { border-color: var(--gold); box-shadow: 0 0 0 3px rgba(196,169,106,0.18); }
.pg-form-input.error { border-color: var(--red); }
.pg-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; align-items: end; }

.select-input { appearance: auto; cursor: pointer; }
.checkbox-cell { height: 38px; display: flex; align-items: center; margin-bottom: 18px; }

.checkbox-label { display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--text-2); cursor: pointer; }
.checkbox-input { -webkit-appearance: none; appearance: none; width: 18px; height: 18px; border: 1px solid var(--border-dark); border-radius: 4px; background: var(--surface); cursor: pointer; transition: all var(--transition); flex-shrink: 0; }
.checkbox-input:checked { background: var(--gold); border-color: var(--gold); position: relative; }
.checkbox-input:checked::after { content: "\2713"; position: absolute; top: -1px; left: 3px; font-size: 13px; color: #fff; }

/* Spatie checklist styles */
.roles-checklist-grid {
  display: flex; flex-direction: column; gap: 10px;
  background: var(--surface-2); border: 1px solid var(--border);
  padding: 14px; border-radius: var(--radius); margin-bottom: 18px;
}
.role-checkbox-item {
  background: var(--surface); border: 1px solid var(--border);
  padding: 10px 14px; border-radius: var(--radius-sm);
}
.role-info { display: flex; flex-direction: column; gap: 2px; }
.role-name-text { font-size: 13px; font-weight: 700; color: var(--text); }
.role-perms-preview { font-size: 11px; color: var(--text-4); max-width: 500px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Direct permissions grid */
.perms-grid-wrapper {
  display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;
  background: var(--surface-2); border: 1px solid var(--border);
  padding: 14px; border-radius: var(--radius); margin-bottom: 24px;
}
.perm-checkbox-item {
  background: var(--surface); border: 1px solid var(--border);
  padding: 8px 12px; border-radius: var(--radius-sm);
}
.checkbox-label.compact { font-size: 12px; }
.perm-name-text { font-family: monospace; color: var(--text-2); font-weight: 600; }

.form-actions { display: flex; gap: 10px; border-top: 1px solid var(--border); padding-top: 20px; justify-content: flex-end; }
.form-error { font-size: 11.5px; color: var(--red); margin-top: 4px; display: block; }
.no-items-text { font-size: 12.5px; color: var(--text-4); font-style: italic; text-align: center; grid-column: span 2; padding: 14px; }

@media (max-width: 900px) {
  .settings-layout { grid-template-columns: 1fr; }
  .pg-form-row { grid-template-columns: 1fr; }
  .checkbox-cell { margin-bottom: 12px; }
  .perms-grid-wrapper { grid-template-columns: 1fr; }
}
</style>
