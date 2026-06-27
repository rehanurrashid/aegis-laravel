<template>
  <AppLayout :user="user" portal="admin" activePage="users" pageTitle="Manage Users">
    <div class="hero-banner is-quiet">
      <div class="page-hero-inner">
        <div class="page-hero-text">
          <div class="page-hero-eyebrow">Directory</div>
          <h1 class="page-hero-title">User Management</h1>
          <p class="page-hero-sub">Search users, view system roles, and assign fine-grained roles and permissions.</p>
        </div>
      </div>
    </div>

    <!-- FLASH MESSAGES -->
    <div v-if="$page.props.flash?.success" class="alert alert-success">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
      <span>{{ $page.props.flash.success }}</span>
    </div>

    <!-- FILTERS AND SEARCH -->
    <div class="filters-row">
      <div class="search-box">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input
          v-model="search"
          type="text"
          class="filter-input search-input"
          placeholder="Search name or email..."
          @input="debounceSearch"
        />
      </div>

      <div class="select-box">
        <label class="filter-label">Role Type</label>
        <select v-model="typeFilter" class="filter-input select-input" @change="applyFilters">
          <option value="">All Accounts</option>
          <option value="0">Administrator</option>
          <option value="1">Provider</option>
          <option value="2">Continuity Steward</option>
          <option value="3">Support Steward</option>
          <option value="4">Business Partner</option>
        </select>
      </div>

      <button v-if="search || typeFilter !== ''" @click="resetFilters" class="btn btn-outline btn-sm reset-btn">Reset</button>
    </div>

    <!-- USER DIRECTORY TABLE -->
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>User Details</th>
            <th>Account Type</th>
            <th>Status</th>
            <th>Package Tier</th>
            <th>Spatie Roles</th>
            <th>Direct Perms</th>
            <th>Registered</th>
            <th class="actions-col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users.data" :key="u.id">
            <td>
              <div class="user-details-cell">
                <span class="user-name">{{ u.display_name }}</span>
                <span class="user-email">{{ u.email }}</span>
              </div>
            </td>
            <td>
              <span class="user-type-pill" :class="'type-' + u.user_type">{{ u.type_label }}</span>
            </td>
            <td>
              <span class="status-indicator-pill" :class="u.verified ? 'verified' : 'unverified'">
                {{ u.verified ? 'Verified' : 'Pending Verification' }}
              </span>
            </td>
            <td>
              <span class="tier-pill" :class="'tier-' + (u.tier || 'none')">{{ u.tier || 'None' }}</span>
            </td>
            <td>
              <div class="roles-chips">
                <span v-for="(r, idx) in u.roles" :key="idx" class="role-badge">{{ r }}</span>
                <span v-if="u.roles.length === 0" class="empty-text-sub">No roles</span>
              </div>
            </td>
            <td>
              <span class="perms-badge" v-if="u.permissions_count > 0">{{ u.permissions_count }} direct</span>
              <span class="empty-text-sub" v-else>None</span>
            </td>
            <td>{{ u.created_at }}</td>
            <td>
              <div class="actions-cell">
                <a :href="'/admin/users/' + u.id + '/edit'" class="btn btn-outline btn-xs">Edit Access</a>
              </div>
            </td>
          </tr>
          <tr v-if="users.data.length === 0">
            <td colspan="8" class="empty-table-text">No users found matching filters.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- PAGINATION -->
    <div class="pagination-row" v-if="users.links && users.links.length > 3">
      <button
        v-for="(link, index) in users.links"
        :key="index"
        class="page-link-btn"
        :class="{ active: link.active, disabled: !link.url }"
        :disabled="!link.url"
        @click="navigateLink(link.url)"
        v-html="link.label"
      ></button>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Components/AppLayout.vue';

const props = defineProps({
  user: Object,
  users: Object,
  filters: Object,
  allRoles: Array,
});

const search = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type !== undefined ? String(props.filters.type) : '');

let debounceTimeout = null;
function debounceSearch() {
  clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => {
    applyFilters();
  }, 400);
}

function applyFilters() {
  const query = {};
  if (search.value.trim()) {
    query.search = search.value;
  }
  if (typeFilter.value !== '') {
    query.type = typeFilter.value;
  }
  router.get('/admin/users', query, {
    preserveState: true,
    replace: true,
  });
}

function resetFilters() {
  search.value = '';
  typeFilter.value = '';
  applyFilters();
}

function navigateLink(url) {
  if (url) {
    router.get(url, {}, { preserveState: true });
  }
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

/* Filter row */
.filters-row {
  display: flex; gap: 14px; margin-bottom: 22px; align-items: flex-end; flex-wrap: wrap;
}
.search-box {
  position: relative; flex: 1; min-width: 240px;
}
.search-icon {
  position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
  width: 15px; height: 15px; color: var(--text-4); pointer-events: none;
}
.filter-input {
  display: block; width: 100%; padding: 10px 13px; font-size: 13px; color: var(--text);
  background: var(--surface); border: 1.5px solid var(--border); border-radius: var(--radius-sm, 8px);
  outline: none; transition: border-color var(--transition);
}
.search-input { padding-left: 36px; }
.filter-input:focus { border-color: var(--gold); }
.select-box { display: flex; flex-direction: column; gap: 5px; min-width: 180px; }
.filter-label { font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--text-4); letter-spacing: 0.5px; }
.select-input { appearance: auto; cursor: pointer; }
.reset-btn { height: 38px; padding: 0 16px; display: inline-flex; align-items: center; }

/* Table styles */
.table-wrap { overflow-x: auto; border-radius: var(--radius-lg); border: 1px solid var(--border); margin-bottom: 24px; }
.table { width: 100%; border-collapse: collapse; }
.table th { font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--text-4); padding: 12px 16px; text-align: left; background: var(--surface-2); border-bottom: 1px solid var(--border); }
.table td { font-size: 13px; color: var(--text-2); padding: 12px 16px; border-bottom: 1px solid var(--border); }
.table tr:last-child td { border-bottom: none; }
.table tr:hover td { background: var(--surface-2); }

.user-details-cell { display: flex; flex-direction: column; }
.user-name { font-weight: 600; color: var(--text); font-size: 13.5px; }
.user-email { font-size: 11px; color: var(--text-4); }

.user-type-pill {
  display: inline-flex; padding: 2px 8px; font-size: 10.5px; font-weight: 600; border-radius: 4px;
}
.type-0 { background: #efebe9; color: #4e342e; }
.type-1 { background: #e8f5e9; color: #2e7d32; }
.type-2 { background: #e3f2fd; color: #1565c0; }
.type-3 { background: #f3e5f5; color: #6a1b9a; }
.type-4 { background: #fff3e0; color: #e65100; }

.status-indicator-pill {
  display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 500;
}
.status-indicator-pill::before {
  content: ""; width: 5px; height: 5px; border-radius: 50%;
}
.status-indicator-pill.verified { color: var(--green-dark); }
.status-indicator-pill.verified::before { background: var(--green); }
.status-indicator-pill.unverified { color: var(--text-4); }
.status-indicator-pill.unverified::before { background: var(--border-dark); }

.tier-pill {
  display: inline-flex; padding: 2px 6px; font-size: 10px; font-weight: 700; border-radius: 4px; text-transform: uppercase;
}
.tier-none { background: #eceff1; color: #455a64; }
.tier-free { background: #e0f2f1; color: #00796b; }
.tier-basic { background: #e8f5e9; color: #2e7d32; }
.tier-pro { background: #e3f2fd; color: #1565c0; }
.tier-enterprise { background: #f3e5f5; color: #6a1b9a; }

.roles-chips { display: flex; flex-wrap: wrap; gap: 4px; max-width: 160px; }
.role-badge {
  background: var(--surface-3, #eae5dc); color: var(--text-3);
  font-size: 10.5px; font-weight: 600; padding: 1px 6px; border-radius: 4px;
}
.perms-badge {
  background: rgba(196,169,106,0.12); color: var(--gold-dark);
  font-size: 11px; font-weight: 600; padding: 2px 6px; border-radius: 4px;
}
.empty-text-sub { font-size: 12px; color: var(--text-4); font-style: italic; }

.actions-cell { display: flex; gap: 6px; }
.btn-xs { padding: 4px 10px; font-size: 11px; }
.empty-table-text { text-align: center; color: var(--text-4); padding: 32px 16px; }

/* Pagination style */
.pagination-row {
  display: flex; gap: 6px; justify-content: center; margin-top: 10px; margin-bottom: 24px;
}
.page-link-btn {
  border: 1px solid var(--border); background: var(--surface); padding: 6px 12px;
  font-size: 12.5px; font-weight: 600; border-radius: var(--radius-sm); color: var(--text-2);
  cursor: pointer; transition: all var(--transition);
}
.page-link-btn:hover { background: var(--surface-2); border-color: var(--gold-light); }
.page-link-btn.active { background: var(--gold-dark); border-color: var(--gold-dark); color: #fff; }
.page-link-btn.disabled { opacity: 0.4; cursor: not-allowed; }

.alert {
  padding: 12px 16px; border-radius: var(--radius-sm); font-size: 13px;
  display: flex; align-items: center; gap: 8px; margin-bottom: 20px;
}
.alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid rgba(46,125,50,0.2); }
.alert svg { flex-shrink: 0; }
</style>
