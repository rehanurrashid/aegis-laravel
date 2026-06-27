<template>
  <AppLayout :user="user" portal="admin" activePage="dashboard" pageTitle="Admin Dashboard">
    <div class="page-body-inner">

      <!-- GREETING -->
      <div class="dh-greet">
        <div>
          <div class="dh-greet-eyebrow">Aegis Operations</div>
          <div class="dh-greet-title">System <em>Overview,</em></div>
          <div class="dh-greet-sub">Welcome back to the Aegis administration console. Here is the real-time status of user accounts, revenue, packages, and complaints.</div>
        </div>
        <div class="dh-greet-meta">
          <div class="dh-greet-mcell">
            <div class="dh-greet-mlabel">Revenue Today</div>
            <div class="dh-greet-mval ok">${{ stats.monthly_revenue }}</div>
          </div>
          <div class="dh-greet-mcell">
            <div class="dh-greet-mlabel">Open Complaints</div>
            <div class="dh-greet-mval" :class="{ 'error-text': stats.open_complaints > 0 }">
              {{ stats.open_complaints }}
            </div>
          </div>
          <div class="dh-greet-mcell">
            <div class="dh-greet-mlabel">Total Users</div>
            <div class="dh-greet-mval">{{ stats.total_users }}</div>
          </div>
        </div>
      </div>

      <!-- STATS GRID -->
      <div class="dh-glance">
        <div class="dh-gl-card">
          <div class="dh-gl-head">
            <div class="dh-gl-label">Total Revenue</div>
            <div class="dh-gl-icon">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
          </div>
          <div class="dh-gl-val">${{ stats.total_revenue }}</div>
          <div class="dh-gl-sub">Cumulative gross subscription fees</div>
        </div>

        <div class="dh-gl-card">
          <div class="dh-gl-head">
            <div class="dh-gl-label">Active Packages</div>
            <div class="dh-gl-icon">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            </div>
          </div>
          <div class="dh-gl-val">{{ stats.active_packages }}<small> / {{ stats.total_packages }}</small></div>
          <div class="dh-gl-sub">Subscription plans currently offered</div>
        </div>

        <div class="dh-gl-card">
          <div class="dh-gl-head">
            <div class="dh-gl-label">Critical Complaints</div>
            <div class="dh-gl-icon" :class="{ 'warn': stats.critical_complaints > 0 }">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
          </div>
          <div class="dh-gl-val" :class="{ 'error-text': stats.critical_complaints > 0 }">{{ stats.critical_complaints }}</div>
          <div class="dh-gl-sub">Urgent support requests open</div>
        </div>

        <div class="dh-gl-card">
          <div class="dh-gl-head">
            <div class="dh-gl-label">Subscribers</div>
            <div class="dh-gl-icon">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
          </div>
          <div class="dh-gl-val">{{ stats.provider_count }}</div>
          <div class="dh-gl-sub">Providers / Practitioners active</div>
        </div>
      </div>

      <!-- MAIN CONTENT COLS -->
      <div class="dh-cols">

        <!-- LEFT COLUMN: Charts & Recent Payments -->
        <div class="col-left">

          <!-- USER DISTRIBUTION CHART -->
          <div class="admin-card mb-22">
            <div class="admin-card-header">
              <h3 class="admin-card-title">User Distribution</h3>
              <span class="admin-card-sub">Account breakdown by user role type</span>
            </div>
            <div class="chart-container">
              <div class="bar-chart">
                <div v-for="dist in typeDistribution" :key="dist.type" class="bar-item">
                  <div class="bar-info">
                    <span class="bar-label">{{ dist.type }}</span>
                    <span class="bar-count">{{ dist.count }} ({{ Math.round((dist.count / (stats.total_users || 1)) * 100) }}%)</span>
                  </div>
                  <div class="bar-track">
                    <div class="bar-fill" :style="{ width: ((dist.count / (stats.total_users || 1)) * 100) + '%', backgroundColor: dist.color }"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- RECENT PAYMENTS -->
          <div class="admin-card">
            <div class="admin-card-header">
              <h3 class="admin-card-title">Recent Transactions</h3>
              <a href="/admin/payments" class="admin-card-action">View all</a>
            </div>
            <div class="table-wrap">
              <table class="table">
                <thead>
                  <tr>
                    <th>User</th>
                    <th>Package</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="p in recentPayments" :key="p.id">
                    <td>
                      <div class="user-cell">
                        <span class="user-cell-name">{{ p.user_name }}</span>
                        <span class="user-cell-email">{{ p.user_email }}</span>
                      </div>
                    </td>
                    <td>{{ p.package_name }}</td>
                    <td><strong>${{ p.amount }}</strong></td>
                    <td>
                      <span class="badge" :class="'badge-' + p.status">{{ p.status }}</span>
                    </td>
                    <td>{{ p.created_at }}</td>
                  </tr>
                  <tr v-if="recentPayments.length === 0">
                    <td colspan="5" class="empty-text">No payments recorded.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

        </div>

        <!-- RIGHT COLUMN: Recent Complaints -->
        <div class="col-right">

          <div class="admin-card">
            <div class="admin-card-header">
              <h3 class="admin-card-title">Open Support Tickets</h3>
              <a href="/admin/complaints" class="admin-card-action">View all</a>
            </div>
            <div class="complaints-list">
              <div v-for="c in recentComplaints" :key="c.id" class="complaint-card-item">
                <div class="complaint-head">
                  <span class="complaint-user">{{ c.user_name }}</span>
                  <span class="badge badge-priority" :class="'badge-' + c.priority">{{ c.priority }}</span>
                </div>
                <h4 class="complaint-subject">{{ c.subject }}</h4>
                <div class="complaint-meta">
                  <span class="meta-tag">{{ c.category }}</span>
                  <span class="meta-date">{{ c.created_at }}</span>
                </div>
                <div class="complaint-footer">
                  <span class="status-indicator" :class="c.status">{{ c.status }}</span>
                  <a :href="'/admin/complaints/' + c.id" class="btn btn-outline btn-xs">Manage</a>
                </div>
              </div>
              <div v-if="recentComplaints.length === 0" class="empty-state">
                <div class="empty-state-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <p>No complaints reported. Great job!</p>
              </div>
            </div>
          </div>

        </div>

      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '../../Components/AppLayout.vue';

defineProps({
  user: Object,
  stats: Object,
  recentPayments: Array,
  recentComplaints: Array,
  typeDistribution: Array,
});
</script>

<style scoped>
.page-body-inner {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* Greetings formatting */
.dh-greet {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 26px;
  align-items: end;
  padding: 6px 4px 26px;
  border-bottom: 1px solid var(--border);
}
.dh-greet-eyebrow {
  font-size: 11px; font-weight: 600;
  letter-spacing: 1.6px; text-transform: uppercase;
  color: var(--gold-dark);
  margin-bottom: 10px;
  display: flex; align-items: center; gap: 9px;
}
.dh-greet-eyebrow::before {
  content: ""; width: 18px; height: 1px;
  background: var(--gold-dark);
}
.dh-greet-title {
  font-family: var(--font-serif);
  font-size: 38px; font-weight: 600;
  letter-spacing: -0.5px;
  line-height: 1.05;
  color: var(--text);
}
.dh-greet-title em { font-style: italic; color: var(--gold-dark); font-weight: 600; }
.dh-greet-sub {
  margin-top: 12px;
  font-size: 14px;
  color: var(--text-3);
  max-width: 560px;
  line-height: 1.55;
}
.dh-greet-meta {
  display: flex; align-items: center; gap: 0;
  padding: 14px 22px;
  border-radius: var(--radius-lg);
  background: var(--surface);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-xs);
}
.dh-greet-mcell {
  display: flex; flex-direction: column; gap: 2px;
  padding: 0 22px;
}
.dh-greet-mcell + .dh-greet-mcell { border-left: 1px solid var(--border); }
.dh-greet-mlabel {
  font-size: 10px; letter-spacing: 1px; text-transform: uppercase;
  color: var(--text-4); font-weight: 600;
}
.dh-greet-mval {
  font-family: var(--font-serif);
  font-size: 18px; font-weight: 600;
  color: var(--text);
}
.dh-greet-mval.ok { color: var(--green-dark); }
.error-text { color: var(--red) !important; }

/* Stats grid */
.dh-glance {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}
.dh-gl-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  box-shadow: var(--shadow-xs);
  transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.dh-gl-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-sm); border-color: var(--gold-light); }
.dh-gl-head { display: flex; align-items: center; justify-content: space-between; }
.dh-gl-label { font-size: 11px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; color: var(--text-4); }
.dh-gl-icon {
  width: 30px; height: 30px;
  border-radius: var(--radius-sm);
  background: rgba(196,169,106,0.1);
  color: var(--gold-dark);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.dh-gl-icon.warn {
  background: rgba(224,92,92,0.1);
  color: var(--red);
}
.dh-gl-val {
  font-family: var(--font-serif);
  font-size: 32px; font-weight: 600;
  line-height: 1; letter-spacing: -1px;
  color: var(--text);
}
.dh-gl-val small { font-family: var(--font-sans); font-size: 15px; color: var(--text-4); font-weight: 500; }
.dh-gl-sub { font-size: 12px; color: var(--text-3); }

/* Main columns */
.dh-cols {
  display: grid;
  grid-template-columns: 1.6fr 1fr;
  gap: 20px;
  align-items: start;
}
.mb-22 { margin-bottom: 22px; }

/* Admin card style */
.admin-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-xl);
  padding: 24px;
  box-shadow: var(--shadow-xs);
}
.admin-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 18px;
}
.admin-card-title {
  font-family: var(--font-serif);
  font-size: 18px;
  font-weight: 600;
  color: var(--text);
  margin: 0;
}
.admin-card-sub {
  font-size: 12px;
  color: var(--text-4);
  display: block;
}
.admin-card-action {
  font-size: 12px;
  font-weight: 600;
  color: var(--gold-dark);
  text-decoration: none;
}
.admin-card-action:hover { text-decoration: underline; }

/* Table wrap styles */
.table-wrap {
  overflow-x: auto;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border);
}
.table {
  width: 100%;
  border-collapse: collapse;
}
.table th {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
  color: var(--text-4);
  padding: 12px 16px;
  text-align: left;
  background: var(--surface-2);
  border-bottom: 1px solid var(--border);
}
.table td {
  font-size: 13px;
  color: var(--text-2);
  padding: 12px 16px;
  border-bottom: 1px solid var(--border);
}
.table tr:last-child td { border-bottom: none; }
.table tr:hover td { background: var(--surface-2); }

/* User cell info */
.user-cell {
  display: flex;
  flex-direction: column;
}
.user-cell-name { font-weight: 600; color: var(--text); }
.user-cell-email { font-size: 11px; color: var(--text-4); }

/* Badges */
.badge {
  display: inline-flex;
  padding: 2px 8px;
  font-size: 10.5px;
  font-weight: 600;
  border-radius: var(--radius-full);
  text-transform: capitalize;
}
.badge-completed { background: rgba(76,175,125,0.12); color: var(--green-dark); }
.badge-pending { background: rgba(232,169,74,0.12); color: var(--orange-dark); }
.badge-failed { background: rgba(224,92,92,0.12); color: var(--red); }
.badge-refunded { background: var(--surface-3); color: var(--text-3); }

/* Priority Badges */
.badge-priority {
  font-size: 10px;
  text-transform: uppercase;
}
.badge-low { background: #e8f5e9; color: #2e7d32; }
.badge-medium { background: #e3f2fd; color: #1565c0; }
.badge-high { background: #fff3e0; color: #e65100; }
.badge-critical { background: #ffebee; color: #c62828; }

/* Status indicator colors */
.status-indicator {
  font-size: 11px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  text-transform: capitalize;
}
.status-indicator::before {
  content: ""; width: 6px; height: 6px; border-radius: 50%;
}
.status-indicator.open::before { background: var(--red); }
.status-indicator.in_progress::before { background: var(--orange); }
.status-indicator.resolved::before { background: var(--green); }
.status-indicator.closed::before { background: var(--text-4); }

/* Bar chart styles */
.bar-chart {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.bar-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.bar-info {
  display: flex;
  justify-content: space-between;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--text-2);
}
.bar-track {
  height: 8px;
  background: var(--surface-2);
  border-radius: var(--radius-full);
  overflow: hidden;
}
.bar-fill {
  height: 100%;
  border-radius: var(--radius-full);
  transition: width 0.6s ease;
}

/* Complaints list */
.complaints-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.complaint-card-item {
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 16px;
  background: var(--surface-2);
  display: flex;
  flex-direction: column;
  gap: 8px;
  transition: border-color 0.2s;
}
.complaint-card-item:hover {
  border-color: var(--gold);
}
.complaint-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.complaint-user {
  font-size: 12px;
  font-weight: 700;
  color: var(--text-2);
}
.complaint-subject {
  font-size: 14px;
  font-weight: 600;
  color: var(--text);
  margin: 0;
  line-height: 1.35;
}
.complaint-meta {
  display: flex;
  gap: 8px;
  font-size: 11px;
  color: var(--text-4);
  align-items: center;
}
.meta-tag {
  background: rgba(196,169,106,0.1);
  color: var(--gold-dark);
  padding: 1px 6px;
  border-radius: 4px;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 9.5px;
}
.complaint-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 6px;
  padding-top: 8px;
  border-top: 1px solid rgba(0,0,0,0.04);
}

/* Empty states */
.empty-text {
  text-align: center;
  color: var(--text-4);
  padding: 16px;
}
.empty-state {
  text-align: center;
  padding: 32px 16px;
  color: var(--text-4);
}
.empty-state-icon {
  width: 44px; height: 44px;
  border-radius: 50%;
  background: var(--surface-3);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 12px;
  color: var(--text-4);
}

.btn-xs {
  padding: 4px 10px;
  font-size: 11px;
}

@media (max-width: 900px) {
  .dh-greet { grid-template-columns: 1fr; }
  .dh-glance { grid-template-columns: 1fr 1fr; }
  .dh-cols { grid-template-columns: 1fr; }
}
</style>
