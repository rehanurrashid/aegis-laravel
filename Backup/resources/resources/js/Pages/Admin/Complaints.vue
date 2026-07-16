<template>
  <AppLayout :user="user" portal="admin" activePage="complaints" pageTitle="Support Tickets">
    <div class="hero-banner is-quiet">
      <div class="page-hero-inner">
        <div class="page-hero-text">
          <div class="page-hero-eyebrow">Support</div>
          <h1 class="page-hero-title">Complaints &amp; Feedback</h1>
          <p class="page-hero-sub">Resolve user issues, review account complaints, and log resolution notes.</p>
        </div>
      </div>
    </div>

    <!-- METRICS SUMMARY -->
    <div class="stat-chip-row" v-if="summary">
      <div class="stat-chip">
        <span class="stat-chip-label">Total Tickets</span>
        <span class="stat-chip-value">{{ summary.total }}</span>
      </div>
      <div class="stat-chip">
        <span class="stat-chip-label">Open Tickets</span>
        <span class="stat-chip-value error-text">{{ summary.open }}</span>
      </div>
      <div class="stat-chip">
        <span class="stat-chip-label">In Progress</span>
        <span class="stat-chip-value warn-text">{{ summary.in_progress }}</span>
      </div>
      <div class="stat-chip">
        <span class="stat-chip-label">Critical Alerts</span>
        <span class="stat-chip-value" :class="{ 'error-text': summary.critical > 0 }">{{ summary.critical }}</span>
      </div>
    </div>

    <!-- FILTER FIELDS -->
    <div class="filters-row">
      <div class="select-box">
        <label class="filter-label">Status</label>
        <select v-model="statusFilter" class="filter-input select-input" @change="applyFilters">
          <option value="">All Statuses</option>
          <option value="open">Open</option>
          <option value="in_progress">In Progress</option>
          <option value="resolved">Resolved</option>
          <option value="closed">Closed</option>
        </select>
      </div>

      <div class="select-box">
        <label class="filter-label">Priority</label>
        <select v-model="priorityFilter" class="filter-input select-input" @change="applyFilters">
          <option value="">All Priorities</option>
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
          <option value="critical">Critical</option>
        </select>
      </div>

      <div class="select-box">
        <label class="filter-label">Category</label>
        <select v-model="categoryFilter" class="filter-input select-input" @change="applyFilters">
          <option value="">All Categories</option>
          <option value="billing">Billing</option>
          <option value="technical">Technical</option>
          <option value="account">Account</option>
          <option value="other">Other</option>
        </select>
      </div>

      <button v-if="statusFilter || priorityFilter || categoryFilter" @click="resetFilters" class="btn btn-outline btn-sm reset-btn">Reset</button>
    </div>

    <!-- TICKETS TABLE -->
    <div class="table-wrap" v-if="complaints">
      <table class="table">
        <thead>
          <tr>
            <th>User</th>
            <th>Subject</th>
            <th>Category</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Reported Date</th>
            <th>Resolved Date</th>
            <th class="actions-col">Details</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in complaints.data" :key="c.id">
            <td>
              <div class="user-cell">
                <span class="user-name">{{ c.user_name }}</span>
                <span class="user-email">{{ c.user_email }}</span>
              </div>
            </td>
            <td>
              <div class="subject-cell">
                <strong>{{ c.subject }}</strong>
                <span class="description-preview">{{ c.description }}</span>
              </div>
            </td>
            <td>
              <span class="category-badge">{{ c.category }}</span>
            </td>
            <td>
              <span class="badge" :class="'badge-' + c.priority">{{ c.priority }}</span>
            </td>
            <td>
              <span class="status-indicator" :class="c.status">{{ c.status }}</span>
            </td>
            <td>{{ c.created_at }}</td>
            <td>{{ c.resolved_at || '—' }}</td>
            <td>
              <div class="actions-cell">
                <button @click="showComplaintDetails(c.id)" class="btn btn-outline btn-xs">Manage Ticket</button>
              </div>
            </td>
          </tr>
          <tr v-if="complaints.data.length === 0">
            <td colspan="8" class="empty-table-text">No support tickets found matching filters.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- PAGINATION -->
    <div class="pagination-row" v-if="complaints && complaints.links && complaints.links.length > 3">
      <button
        v-for="(link, index) in complaints.links"
        :key="index"
        class="page-link-btn"
        :class="{ active: link.active, disabled: !link.url }"
        :disabled="!link.url"
        @click="navigateLink(link.url)"
        v-html="link.label"
      ></button>
    </div>

    <!-- COMPLAINT RESOLUTION FORM MODAL OVERLAY -->
    <div class="modal-overlay" v-if="complaint" @click.self="closeComplaintDetails">
      <div class="modal-box">
        <div class="modal-header">
          <h3 class="modal-title">Resolve Support Request</h3>
          <button class="close-modal-btn" @click="closeComplaintDetails">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>

        <div class="modal-body text-left">
          <div class="complaint-meta-strip">
            <span class="category-badge">{{ complaint.category }}</span>
            <span class="badge" :class="'badge-' + complaint.priority">{{ complaint.priority }}</span>
            <span class="status-indicator" :class="complaint.status">{{ complaint.status }}</span>
          </div>

          <h2 class="complaint-details-subject">{{ complaint.subject }}</h2>
          <div class="complaint-user-card">
            <strong>Reporter:</strong> {{ complaint.user_name }} ({{ complaint.user_email }})<br>
            <strong>Created:</strong> {{ complaint.created_at }}
          </div>

          <div class="desc-box">
            <h4 class="box-title">Problem Description</h4>
            <p class="box-content">{{ complaint.description }}</p>
          </div>

          <form @submit.prevent="submitResolution" class="resolve-form">
            <div class="pg-form-row">
              <div class="pg-form-group">
                <label class="pg-form-label">Update Status</label>
                <select v-model="form.status" class="pg-form-input select-input" required>
                  <option value="open">Open</option>
                  <option value="in_progress">In Progress</option>
                  <option value="resolved">Resolved</option>
                  <option value="closed">Closed</option>
                </select>
              </div>

              <div class="pg-form-group">
                <label class="pg-form-label">Adjust Priority</label>
                <select v-model="form.priority" class="pg-form-input select-input" required>
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="critical">Critical</option>
                </select>
              </div>
            </div>

            <div class="pg-form-group">
              <label class="pg-form-label">Administrator Action Notes</label>
              <textarea
                v-model="form.admin_notes"
                class="pg-form-input text-area"
                placeholder="Log internal resolution comments, action details, or customer follow-ups..."
                rows="4"
              ></textarea>
            </div>

            <div class="resolved-by-info" v-if="complaint.resolved_by">
              <strong>Resolved by:</strong> {{ complaint.resolved_by }} on {{ complaint.resolved_at }}
            </div>

            <div class="modal-footer-actions">
              <button type="button" @click="closeComplaintDetails" class="btn btn-outline">Cancel</button>
              <button type="submit" class="btn btn-primary" :disabled="form.processing">
                {{ form.processing ? 'Updating...' : 'Update Ticket' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '../../Components/AppLayout.vue';

const props = defineProps({
  user: Object,
  complaints: Object,
  summary: Object,
  filters: Object,
  complaint: Object,
});

const statusFilter = ref(props.filters?.status || '');
const priorityFilter = ref(props.filters?.priority || '');
const categoryFilter = ref(props.filters?.category || '');

const form = useForm({
  status: 'open',
  admin_notes: '',
  priority: 'medium',
});

watch(() => props.complaint, (newVal) => {
  if (newVal) {
    form.status = newVal.status;
    form.admin_notes = newVal.admin_notes || '';
    form.priority = newVal.priority;
  }
}, { immediate: true });

function applyFilters() {
  const query = {};
  if (statusFilter.value) query.status = statusFilter.value;
  if (priorityFilter.value) query.priority = priorityFilter.value;
  if (categoryFilter.value) query.category = categoryFilter.value;

  router.get('/admin/complaints', query, {
    preserveState: true,
    replace: true,
  });
}

function resetFilters() {
  statusFilter.value = '';
  priorityFilter.value = '';
  categoryFilter.value = '';
  applyFilters();
}

function showComplaintDetails(id) {
  router.get('/admin/complaints/' + id, {}, {
    preserveState: true,
  });
}

function closeComplaintDetails() {
  router.get('/admin/complaints', {
    status: statusFilter.value || undefined,
    priority: priorityFilter.value || undefined,
    category: categoryFilter.value || undefined,
  }, {
    preserveState: true,
  });
}

function submitResolution() {
  form.put('/admin/complaints/' + props.complaint.id + '/resolve', {
    onSuccess: () => {
      closeComplaintDetails();
    },
  });
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

/* Stat chips */
.stat-chip-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; }
.stat-chip { padding: 14px 18px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg, 14px); min-width: 140px; flex: 1; }
.stat-chip-label { font-size: 10px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: var(--text-4); display: block; margin-bottom: 6px; }
.stat-chip-value { font-family: var(--font-serif); font-size: 24px; font-weight: 700; color: var(--text); display: block; }
.error-text { color: var(--red) !important; }
.warn-text { color: var(--orange-dark) !important; }

/* Filters row */
.filters-row {
  display: flex; gap: 14px; margin-bottom: 22px; align-items: flex-end; flex-wrap: wrap;
}
.select-box { display: flex; flex-direction: column; gap: 5px; min-width: 160px; }
.filter-label { font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--text-4); letter-spacing: 0.5px; }
.filter-input {
  display: block; width: 100%; padding: 10px 13px; font-size: 13px; color: var(--text);
  background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm, 8px);
  outline: none; transition: border-color var(--transition);
}
.filter-input:focus { border-color: var(--gold); }
.select-input { appearance: auto; cursor: pointer; }
.reset-btn { height: 38px; padding: 0 16px; display: inline-flex; align-items: center; }

/* Table wrap styles */
.table-wrap { overflow-x: auto; border-radius: var(--radius-lg); border: 1px solid var(--border); margin-bottom: 24px; }
.table { width: 100%; border-collapse: collapse; }
.table th { font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--text-4); padding: 12px 16px; text-align: left; background: var(--surface-2); border-bottom: 1px solid var(--border); }
.table td { font-size: 13px; color: var(--text-2); padding: 12px 16px; border-bottom: 1px solid var(--border); }
.table tr:last-child td { border-bottom: none; }
.table tr:hover td { background: var(--surface-2); }

.user-cell { display: flex; flex-direction: column; }
.user-name { font-weight: 600; color: var(--text); }
.user-email { font-size: 11.5px; color: var(--text-4); }

.subject-cell { display: flex; flex-direction: column; max-width: 300px; }
.subject-cell strong { font-size: 13.5px; color: var(--text); }
.description-preview { font-size: 11.5px; color: var(--text-4); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }

.category-badge {
  background: rgba(196,169,106,0.08); color: var(--gold-dark);
  font-size: 10.5px; font-weight: 700; padding: 2px 6px; border-radius: 4px;
  text-transform: uppercase; display: inline-block; width: fit-content;
}

/* Badges */
.badge { display: inline-flex; padding: 2px 8px; font-size: 10px; font-weight: 700; border-radius: var(--radius-full); text-transform: uppercase; }
.badge-low { background: #e8f5e9; color: #2e7d32; }
.badge-medium { background: #e3f2fd; color: #1565c0; }
.badge-high { background: #fff3e0; color: #e65100; }
.badge-critical { background: #ffebee; color: #c62828; }

/* Status indicator colors */
.status-indicator {
  font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; text-transform: capitalize;
}
.status-indicator::before {
  content: ""; width: 6px; height: 6px; border-radius: 50%;
}
.status-indicator.open::before { background: var(--red); }
.status-indicator.in_progress::before { background: var(--orange); }
.status-indicator.resolved::before { background: var(--green); }
.status-indicator.closed::before { background: var(--text-4); }

.actions-cell { display: flex; gap: 6px; }
.btn-xs { padding: 4px 10px; font-size: 11px; }
.empty-table-text { text-align: center; color: var(--text-4); padding: 32px 16px; }

/* Pagination style */
.pagination-row { display: flex; gap: 6px; justify-content: center; margin-top: 10px; margin-bottom: 24px; }
.page-link-btn { border: 1px solid var(--border); background: var(--surface); padding: 6px 12px; font-size: 12.5px; font-weight: 600; border-radius: var(--radius-sm); color: var(--text-2); cursor: pointer; transition: all var(--transition); }
.page-link-btn:hover { background: var(--surface-2); border-color: var(--gold-light); }
.page-link-btn.active { background: var(--gold-dark); border-color: var(--gold-dark); color: #fff; }
.page-link-btn.disabled { opacity: 0.4; cursor: not-allowed; }

/* Modal overlay styling */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(30,28,26,0.5);
  backdrop-filter: blur(2px); z-index: 1000; display: flex;
  align-items: center; justify-content: center; padding: 20px;
}
.modal-box {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-xl, 18px); width: 100%; max-width: 540px;
  box-shadow: 0 12px 36px rgba(30,28,26,0.25); overflow: hidden;
  animation: modalFadeIn 0.25s ease both;
}
.modal-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 16px 22px; border-bottom: 1px solid var(--border);
}
.modal-title { font-family: var(--font-serif); font-size: 16px; font-weight: 700; color: var(--text); margin: 0; }
.close-modal-btn { background: none; border: none; padding: 4px; cursor: pointer; color: var(--text-4); display: flex; align-items: center; }
.close-modal-btn:hover { color: var(--text); }

.modal-body { padding: 24px 22px; }
.text-left { text-align: left; }

.complaint-meta-strip { display: flex; gap: 8px; align-items: center; margin-bottom: 14px; }
.complaint-details-subject { font-family: var(--font-serif); font-size: 22px; font-weight: 600; color: var(--text); margin: 0 0 10px; line-height: 1.25; }
.complaint-user-card { font-size: 12.5px; color: var(--text-3); line-height: 1.5; background: var(--surface-2); padding: 10px 14px; border-radius: var(--radius-sm); border: 1px solid var(--border); margin-bottom: 18px; }

.desc-box { margin-bottom: 22px; }
.box-title { font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--text-4); letter-spacing: 0.5px; margin-bottom: 6px; }
.box-content { font-size: 13.5px; color: var(--text-2); line-height: 1.6; margin: 0; background: var(--surface); border: 1px solid var(--border); padding: 12px; border-radius: var(--radius-sm); white-space: pre-line; }

.resolve-form { border-top: 1px solid var(--border); padding-top: 18px; }
.pg-form-group { margin-bottom: 16px; }
.pg-form-label { display: block; font-size: 10.5px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; color: var(--text-2); margin-bottom: 6px; }
.pg-form-input { display: block; width: 100%; padding: 10px 13px; font-size: 13px; color: var(--text); background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm, 8px); transition: border-color var(--transition), box-shadow var(--transition); outline: none; }
.pg-form-input:focus { border-color: var(--gold); box-shadow: 0 0 0 3px rgba(196,169,106,0.18); }
.pg-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

.select-input { appearance: auto; cursor: pointer; }
.text-area { resize: vertical; font-family: var(--font-sans); line-height: 1.5; }

.resolved-by-info { font-size: 12px; color: var(--text-4); margin-bottom: 18px; border-top: 1px dashed var(--border); padding-top: 10px; }

.modal-footer-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px; border-top: 1px solid var(--border); padding-top: 16px; }

@media (max-width: 900px) {
  .page-hero-inner { flex-direction: column; align-items: flex-start; gap: 14px; }
  .stat-chip-row { flex-direction: column; }
  .filters-row { flex-direction: column; align-items: stretch; }
  .select-box { min-width: 0; }
  .reset-btn { align-self: flex-start; }
  .pg-form-row { grid-template-columns: 1fr; }
}
</style>
