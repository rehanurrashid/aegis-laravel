<template>
  <AppLayout :user="user" portal="admin" activePage="packages" pageTitle="Manage Packages">
    <div class="hero-banner is-quiet">
      <div class="page-hero-inner">
        <div class="page-hero-text">
          <div class="page-hero-eyebrow">Subscriptions</div>
          <h1 class="page-hero-title">Package Management</h1>
          <p class="page-hero-sub">Define tiers, prices, subscriber limits, and feature lists for practitioner plans.</p>
        </div>
        <div class="page-hero-actions">
          <a href="/admin/packages/create" class="btn btn-primary btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Create Package
          </a>
        </div>
      </div>
    </div>

    <!-- FLASH MESSAGES -->
    <div v-if="$page.props.flash?.success" class="alert alert-success">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
      <span>{{ $page.props.flash.success }}</span>
    </div>

    <!-- STAT CHIPS -->
    <div class="stat-chip-row">
      <div class="stat-chip">
        <span class="stat-chip-label">Total Packages</span>
        <span class="stat-chip-value">{{ packages.length }}</span>
      </div>
      <div class="stat-chip">
        <span class="stat-chip-label">Active Plans</span>
        <span class="stat-chip-value">{{ packages.filter(p => p.is_active).length }}</span>
      </div>
      <div class="stat-chip">
        <span class="stat-chip-label">Deactivated Plans</span>
        <span class="stat-chip-value">{{ packages.filter(p => !p.is_active).length }}</span>
      </div>
    </div>

    <!-- TABLE LIST -->
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Plan Name</th>
            <th>Tier</th>
            <th>Monthly Price</th>
            <th>Annual Price</th>
            <th>Max Users</th>
            <th>Features Included</th>
            <th>Status</th>
            <th class="actions-col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="pkg in packages" :key="pkg.id">
            <td>
              <div class="pkg-name-cell">
                <strong>{{ pkg.name }}</strong>
                <small class="pkg-slug">{{ pkg.slug }}</small>
              </div>
            </td>
            <td>
              <span class="tier-pill" :class="'tier-' + pkg.tier">{{ pkg.tier }}</span>
            </td>
            <td><strong>${{ pkg.price_monthly }}</strong><small class="price-sub">/mo</small></td>
            <td><strong>${{ pkg.price_annual }}</strong><small class="price-sub">/yr</small></td>
            <td>{{ pkg.max_users }}</td>
            <td>
              <div class="features-summary">
                <span v-for="(feat, idx) in pkg.features" :key="idx" class="feature-chip">{{ feat }}</span>
                <span v-if="!pkg.features || pkg.features.length === 0" class="empty-features">No features listed</span>
              </div>
            </td>
            <td>
              <span class="status-pill" :class="pkg.is_active ? 'active' : 'inactive'">
                {{ pkg.is_active ? 'Active' : 'Deactivated' }}
              </span>
            </td>
            <td>
              <div class="actions-cell">
                <a :href="'/admin/packages/' + pkg.id + '/edit'" class="btn btn-outline btn-xs">Edit</a>
                <button
                  v-if="pkg.is_active"
                  @click="deactivatePackage(pkg.id)"
                  class="btn btn-outline btn-xs btn-danger"
                >
                  Deactivate
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="packages.length === 0">
            <td colspan="8" class="empty-text">No packages available. Create one to get started.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Components/AppLayout.vue';

defineProps({
  user: Object,
  packages: Array,
});

function deactivatePackage(id) {
  if (confirm("Are you sure you want to deactivate this package? Users will no longer be able to subscribe to it.")) {
    router.delete('/admin/packages/' + id);
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
.page-hero-actions { flex-shrink: 0; }

.stat-chip-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; }
.stat-chip { padding: 14px 18px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg, 14px); min-width: 140px; flex: 1; }
.stat-chip-label { font-size: 10px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: var(--text-4); display: block; margin-bottom: 6px; }
.stat-chip-value { font-family: var(--font-serif); font-size: 24px; font-weight: 700; color: var(--text); display: block; }

.table-wrap { overflow-x: auto; border-radius: var(--radius-lg); border: 1px solid var(--border); margin-bottom: 24px; }
.table { width: 100%; border-collapse: collapse; }
.table th { font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--text-4); padding: 12px 16px; text-align: left; background: var(--surface-2); border-bottom: 1px solid var(--border); }
.table td { font-size: 13px; color: var(--text-2); padding: 12px 16px; border-bottom: 1px solid var(--border); }
.table tr:last-child td { border-bottom: none; }
.table tr:hover td { background: var(--surface-2); }

.pkg-name-cell { display: flex; flex-direction: column; }
.pkg-name-cell strong { font-size: 14px; color: var(--text); }
.pkg-slug { font-size: 11px; color: var(--text-4); }

.tier-pill {
  display: inline-flex; padding: 2px 8px; font-size: 10px; font-weight: 700;
  text-transform: uppercase; border-radius: var(--radius-sm);
}
.tier-free { background: #e0f2f1; color: #00796b; }
.tier-basic { background: #e8f5e9; color: #2e7d32; }
.tier-pro { background: #e3f2fd; color: #1565c0; }
.tier-enterprise { background: #f3e5f5; color: #6a1b9a; }

.price-sub { font-size: 11px; color: var(--text-4); font-weight: normal; margin-left: 2px; }

.features-summary { display: flex; flex-wrap: wrap; gap: 4px; max-width: 320px; }
.feature-chip {
  background: rgba(196,169,106,0.08); color: var(--gold-dark);
  font-size: 11px; font-weight: 600; padding: 1px 6px; border-radius: 4px;
}
.empty-features { font-size: 12px; color: var(--text-4); font-style: italic; }

.status-pill {
  display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600;
}
.status-pill::before {
  content: ""; width: 6px; height: 6px; border-radius: 50%;
}
.status-pill.active { color: var(--green-dark); }
.status-pill.active::before { background: var(--green); }
.status-pill.inactive { color: var(--red); }
.status-pill.inactive::before { background: var(--red); }

.actions-col { text-align: right !important; }
.actions-cell { display: flex; gap: 6px; justify-content: flex-end; }
.btn-xs { padding: 4px 10px; font-size: 11px; }
.btn-danger { color: var(--red); border-color: rgba(224,92,92,0.4); }
.btn-danger:hover { background: var(--red-light); border-color: var(--red); }

.alert {
  padding: 12px 16px; border-radius: var(--radius-sm); font-size: 13px;
  display: flex; align-items: center; gap: 8px; margin-bottom: 20px;
}
.alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid rgba(46,125,50,0.2); }
.alert svg { flex-shrink: 0; }
.empty-text { text-align: center; color: var(--text-4); padding: 32px 16px; }

@media (max-width: 900px) {
  .page-hero-inner { flex-direction: column; align-items: flex-start; gap: 14px; }
  .stat-chip-row { flex-direction: column; }
}
</style>
