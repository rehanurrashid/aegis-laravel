<template>
  <aside class="sidebar" :class="{ collapsed: isCollapsed, open: isMobileOpen }" id="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
      <div class="sidebar-brand-top">
        <a :href="brandHref" class="sidebar-brand-link">
          <span class="sidebar-brand-name">Aegis</span>
        </a>
        <button class="sidebar-toggle-btn" @click="toggleSidebar" aria-label="Toggle sidebar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="5" width="18" height="14" rx="2" ry="2"></rect>
            <line x1="9" y1="10" x2="9" y2="14"></line>
            <line x1="15" y1="10" x2="15" y2="14"></line>
          </svg>
        </button>
      </div>
      <div class="sidebar-brand-meta">
        <span v-if="portal === 'business_partner'" class="sidebar-portal-badge">BP Portal</span>
        <span v-if="portal === 'practitioner'" class="sidebar-clinical-badge">Clinical Services</span>
        <span class="sidebar-role-badge">{{ roleLabel }}</span>
        <span v-if="hasEmergency && (portal === 'continuity_steward' || portal === 'support_steward')" class="sidebar-emergency-badge">
          <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          Active Critical Incident
        </span>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav" id="sidebarNav" ref="navRef" role="navigation" aria-label="Main navigation">
      <template v-for="(items, section) in navItems" :key="section">
        <div class="nav-section-label">{{ section }}</div>
        <Link
          v-for="item in items"
          :key="item.key"
          class="nav-item"
          :class="{ active: activePage === item.key, 'is-locked': item.locked }"
          :href="resolveHref(item.href)"
          :data-label="item.label"
          :aria-current="activePage === item.key ? 'page' : 'false'"
          @click="item.locked ? $event.preventDefault() : closeMobile()"
        >
          <span class="nav-icon" v-html="getIcon(item.icon)"></span>
          <span class="nav-label">{{ item.label }}</span>
          <span v-if="item.badge" class="nav-badge" :class="item.badge_type">{{ item.badge }}</span>
          <span v-if="item.locked" class="nav-lock" aria-hidden="true">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </span>
        </Link>
      </template>
    </nav>



    <!-- Footer -->
    <div class="sidebar-footer">
      <p class="sidebar-version">{{ versionText }}</p>
    </div>
  </aside>

  <!-- Mobile overlay -->
  <div class="sidebar-overlay" :class="{ open: isMobileOpen }" @click="toggleSidebar" aria-hidden="true"></div>
</template>

<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  user: Object,
  portal: { type: String, default: 'practitioner' },
  activePage: { type: String, default: 'dashboard' },
  hasEmergency: { type: Boolean, default: false },
  bpType: { type: String, default: 'agency' },
  mobileOpen: { type: Boolean, default: false },
  collapsed: { type: Boolean, default: false },
});

const emit = defineEmits(['toggle', 'mobileToggle']);

const isCollapsed = ref(props.collapsed);
const isMobileOpen = computed({
  get: () => props.mobileOpen,
  set: (val) => emit('mobileToggle', val)
});

watch(() => props.collapsed, (newVal) => {
  isCollapsed.value = newVal;
});

/* Lock / unlock body scroll when mobile drawer opens / closes */
watch(isMobileOpen, (open) => {
  if (typeof document === 'undefined') return;
  document.body.style.overflow = open ? 'hidden' : '';
});

onBeforeUnmount(() => {
  if (typeof document !== 'undefined') document.body.style.overflow = '';
});

/* Close the mobile drawer after navigating (no-op on desktop). */
function closeMobile() {
  if (isMobileOpen.value) isMobileOpen.value = false;
}

function toggleSidebar() {
  if (window.innerWidth <= 768) {
    isMobileOpen.value = !isMobileOpen.value;
  } else {
    isCollapsed.value = !isCollapsed.value;
    emit('toggle', isCollapsed.value);
  }
}

/* ── Role label ── */
const roleLabel = computed(() => {
  const map = {
    practitioner: 'PRACTITIONER',
    continuity_steward: 'Continuity Steward',
    support_steward: 'Support Steward',
    business_partner: props.bpType === 'freelancer' ? 'Freelancer Account' : 'Agency Account',
    admin: 'Administrator',
  };
  return map[props.portal] || 'User';
});

const brandHref = computed(() => {
  const bases = {
    practitioner: '/provider/dashboard',
    continuity_steward: '/continuity-steward/dashboard',
    support_steward: '/support-steward/dashboard',
    business_partner: '/business/dashboard',
    admin: '/admin/dashboard',
  };
  return bases[props.portal] || '/';
});

const versionText = computed(() => {
  const map = {
    practitioner: 'Aegis \u00A9 2026 \u00B7 Practitioner Portal v2.4.1',
    continuity_steward: 'Aegis \u00A9 2026 \u00B7 Continuity Steward Portal v1.0.0',
    support_steward: 'Aegis \u00A9 2026 \u00B7 Support Steward v1.0.0',
    business_partner: 'Aegis \u00A9 2026 \u00B7 BP Portal v1.0.0',
    admin: 'Aegis \u00A9 2026 \u00B7 Admin Portal v1.0.0',
  };
  return map[props.portal] || 'Aegis \u00A9 2026';
});

function resolveHref(href) {
  if (!href) return '#';
  if (href.startsWith('/') || href.startsWith('http')) return href;
  const bases = {
    practitioner: '/provider/',
    continuity_steward: '/continuity-steward/',
    support_steward: '/support-steward/',
    business_partner: '/business/',
    admin: '/admin/',
  };
  return (bases[props.portal] || '/') + href;
}

/* ── Nav configs per portal (matches sidebar.php lines 128-318) ── */
const navItems = computed(() => {
  const p = props.portal;

  if (p === 'admin') {
    const count = props.user?.open_complaints_count || 0;
    return {
      'Main': [
        { key: 'dashboard', href: 'dashboard', icon: 'dashboard', label: 'Dashboard' },
      ],
      'Management': [
        { key: 'packages', href: 'packages', icon: 'packages', label: 'Packages' },
        { key: 'users', href: 'users', icon: 'users', label: 'Users' },
        { key: 'roles', href: 'roles', icon: 'roles', label: 'Roles & Permissions' },
      ],
      'Finance': [
        { key: 'payments', href: 'payments', icon: 'financials', label: 'Payments' },
      ],
      'Support': [
        { key: 'complaints', href: 'complaints', icon: 'complaints', label: 'Complaints', badge: count },
      ],
    };
  }

  if (p === 'practitioner') return {
    'Main': [
      { key: 'overview', href: 'overview', icon: 'overview', label: 'Overview \u2014 Start Here' },
      { key: 'dashboard', href: 'dashboard', icon: 'dashboard', label: 'Dashboard' },
      { key: 'profile', href: 'profile', icon: 'profile', label: 'My Profile' },
      { key: 'job-postings', href: 'support-services', icon: 'jobs', label: 'Support & Services', badge: '12' },
    ],
    'My Practice': [
      { key: 'referrals', href: 'referrals', icon: 'referrals', label: 'Referrals', badge: '3' },
      { key: 'network', href: 'network', icon: 'network', label: 'Integrative Network' },
      { key: 'my-services', href: 'my-services', icon: 'my-services', label: 'My Services', badge: '3' },
    ],
    'Continuity': [
      { key: 'continuity-plan', href: 'continuity-plan', icon: 'continuity-plan', label: 'Continuity Plan' },
      { key: 'continuity-stewards', href: 'continuity-stewards', icon: 'continuity-stewards', label: 'Continuity Stewards', badge: '1' },
      { key: 'support-stewards', href: 'support-stewards', icon: 'support-stewards', label: 'Support Stewards' },
      { key: 'important-documents', href: 'important-documents', icon: 'important-documents', label: 'Important Documents' },
      { key: 'vault', href: 'vault', icon: 'vault', label: 'Document Vault' },
      { key: 'finances', href: 'finances', icon: 'financials', label: 'Finances', badge: '2' },
    ],
    'Communication': [
      { key: 'messages', href: 'messages', icon: 'messages', label: 'Messages' },
      { key: 'activity', href: 'activity', icon: 'activity', label: 'Activity Log' },
    ],
    'Explore': [
      { key: 'news', href: 'news', icon: 'news', label: 'News & Resources' },
      { key: 'events', href: 'events', icon: 'events', label: 'Events' },
    ],
    'Account': [
      { key: 'settings', href: 'settings', icon: 'settings', label: 'Settings' },
      { key: 'support', href: 'support', icon: 'support', label: 'Support' },
    ],
  };

  if (p === 'support_steward') return {
    'Main': [
      { key: 'overview', href: 'overview', icon: 'overview', label: 'Overview \u2014 Start Here' },
      { key: 'dashboard', href: 'dashboard', icon: 'dashboard', label: 'Dashboard' },
      { key: 'profile', href: 'profile', icon: 'profile', label: 'My Profile' },
    ],
    'Critical Moment Plans': [
      { key: 'providers', href: 'providers', icon: 'providers', label: 'My Practitioners', badge: '8' },
    ],
    'Activation': [
      { key: 'my-tasks', href: 'my-tasks', icon: 'tasks', label: 'My Tasks', badge: '5' },
      { key: 'important-documents', href: 'important-documents', icon: 'important-documents', label: 'Important Documents' },
      { key: 'continuity-stewards', href: 'continuity-stewards', icon: 'continuity-stewards', label: 'Continuity Stewards' },
      { key: 'critical-incident-log', href: 'critical-incident-log', icon: 'emergency', label: 'Critical Incident Log', badge: props.hasEmergency ? '1' : '', badge_type: props.hasEmergency ? 'danger' : '' },
    ],
    'Communication': [
      { key: 'messages', href: 'messages', icon: 'messages', label: 'Messages' },
      { key: 'activity', href: 'activity', icon: 'activity', label: 'Activity Log' },
    ],
    'Account': [
      { key: 'settings', href: 'settings', icon: 'settings', label: 'Settings' },
      { key: 'support', href: 'support', icon: 'support', label: 'Support' },
    ],
  };

  if (p === 'business_partner') {
    const qs = '?type=' + props.bpType;
    const items = {
      'Main': [
        { key: 'overview', href: 'overview' + qs, icon: 'overview', label: 'Overview \u2014 Start Here' },
        { key: 'dashboard', href: 'dashboard' + qs, icon: 'dashboard', label: 'Dashboard' },
        { key: 'profile', href: 'profile' + qs, icon: 'profile', label: 'My Profile' },
      ],
      'Work': [
        { key: 'find-jobs', href: 'find-jobs' + qs, icon: 'find-jobs', label: 'Find Jobs', badge: '3', badge_type: 'blue' },
        { key: 'contracts', href: 'contracts' + qs, icon: 'agreement', label: 'Contracts', badge: '2', badge_type: 'green' },
        { key: 'proposals', href: 'proposals' + qs, icon: 'proposals', label: 'Proposals', badge: '1', badge_type: 'warning' },
        { key: 'milestones', href: 'milestones' + qs, icon: 'milestones', label: 'Milestones' },
      ],
      'Financial': [
        { key: 'finances', href: 'finances' + qs, icon: 'finances-coin', label: 'Finances' },
        { key: 'invoices', href: 'invoices' + qs, icon: 'invoices', label: 'Invoices', badge_type: 'warning' },
        { key: 'payment-setup', href: 'payment-setup' + qs, icon: 'financials', label: 'Payment Setup' },
      ],
      'Communication': [
        { key: 'messages', href: 'messages' + qs, icon: 'messages', label: 'Messages' },
        { key: 'activity', href: 'activity' + qs, icon: 'activity', label: 'Activity Log' },
      ],
      'Account': [
        { key: 'settings', href: 'settings' + qs, icon: 'settings', label: 'Settings' },
        { key: 'support', href: 'support' + qs, icon: 'support', label: 'Support' },
      ],
    };
    if (props.bpType === 'agency') {
      items['Team'] = [
        { key: 'team', href: 'team' + qs, icon: 'team', label: 'Team Management' },
      ];
    }
    return items;
  }

  /* Continuity Steward (default) */
  return {
    'Main': [
      { key: 'overview', href: 'overview', icon: 'overview', label: 'Overview \u2014 Start Here' },
      { key: 'dashboard', href: 'dashboard', icon: 'dashboard', label: 'Dashboard' },
      { key: 'profile', href: 'profile', icon: 'profile', label: 'My Profile' },
    ],
    'My Work': [
      { key: 'my-tasks', href: 'my-tasks', icon: 'tasks', label: 'My Tasks' },
      { key: 'providers', href: 'providers', icon: 'providers', label: 'My Practitioners' },
      { key: 'important-documents', href: 'important-documents', icon: 'important-documents', label: 'Important Documents' },
      { key: 'finances', href: 'finances', icon: 'financials', label: 'Finances' },
    ],
    'Critical Incident': [
      { key: 'continuity-management', href: 'continuity-management', icon: 'emergency', label: 'Continuity Management', badge: props.hasEmergency ? '!' : '', badge_type: props.hasEmergency ? 'danger' : '' },
      { key: 'vault', href: 'vault', icon: 'vault', label: 'Document Vault' },
    ],
    'Communication': [
      { key: 'messages', href: 'messages', icon: 'messages', label: 'Messages' },
      { key: 'activity', href: 'activity', icon: 'activity', label: 'Activity Log' },
    ],
    'Account': [
      { key: 'settings', href: 'settings', icon: 'settings', label: 'Settings' },
      { key: 'support', href: 'support', icon: 'support', label: 'Support' },
    ],
  };
});

/* ── Icon map (matches sidebar.php lines 326-358) ── */
function getIcon(key) {
  const s = 'width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"';
  const icons = {
    'overview':              `<svg ${s}><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`,
    'support':               `<svg ${s}><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
    'dashboard':             `<svg ${s}><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>`,
    'profile':               `<svg ${s}><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`,
    'tasks':                 `<svg ${s}><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M9 14l2 2 4-4"/></svg>`,
    'providers':             `<svg ${s}><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`,
    'agreement':             `<svg ${s}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>`,
    'continuity-plan':       `<svg ${s}><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>`,
    'financials':            `<svg ${s}><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>`,
    'emergency':             `<svg ${s}><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
    'vault':                 `<svg ${s}><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>`,
    'messages':              `<svg ${s}><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>`,
    'activity':              `<svg ${s}><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>`,
    'settings':              `<svg ${s}><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>`,
    'jobs':                  `<svg ${s}><path d="M3 21h18"/><path d="M5 21V7l8-4 8 4v14"/><path d="M9 21v-4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v4"/></svg>`,
    'packages':              `<svg ${s}><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>`,
    'users':                 `<svg ${s}><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`,
    'roles':                 `<svg ${s}><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/><circle cx="12" cy="16" r="1"/></svg>`,
    'complaints':            `<svg ${s}><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
    'referrals':             `<svg ${s}><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>`,
    'network':               `<svg ${s}><circle cx="12" cy="5" r="3"/><circle cx="5" cy="19" r="3"/><circle cx="19" cy="19" r="3"/><line x1="12" y1="8" x2="5" y2="16"/><line x1="12" y1="8" x2="19" y2="16"/></svg>`,
    'my-services':           `<svg ${s}><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>`,
    'continuity-stewards':   `<svg ${s}><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/></svg>`,
    'support-stewards':      `<svg ${s}><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`,
    'important-documents':   `<svg ${s}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>`,
    'news':                  `<svg ${s}><path d="M3 11l18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>`,
    'events':                `<svg ${s}><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>`,
    'find-jobs':             `<svg ${s}><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>`,
    'proposals':             `<svg ${s}><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>`,
    'milestones':            `<svg ${s}><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>`,
    'finances-coin':         `<svg ${s}><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>`,
    'invoices':              `<svg ${s}><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1z"/><line x1="8" y1="8" x2="16" y2="8"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="8" y1="16" x2="12" y2="16"/></svg>`,
    'team':                  `<svg ${s}><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`,
  };
  return icons[key] || `<svg ${s}><circle cx="12" cy="12" r="1"/></svg>`;
}




</script>

<style scoped>
.sidebar {
  width: var(--sidebar-width);
  background: #f8f5ee;
  min-height: 100vh;
  position: fixed;
  left: 0; top: 0; bottom: 0;
  display: flex;
  flex-direction: column;
  z-index: 200;
  transition: transform var(--transition-slow), width var(--transition-slow);
  overflow-y: auto;
  overflow-x: hidden;
  scrollbar-width: none;
  border-right: 1px solid #e8e4db;
}
.sidebar::-webkit-scrollbar { display: none; }

/* ── Collapsed state ── */
.sidebar.collapsed {
  width: 80px;
}
.sidebar.collapsed .sidebar-brand {
  padding: 20px 12px 24px;
}
.sidebar.collapsed .sidebar-brand-top {
  justify-content: center;
}
.sidebar.collapsed .sidebar-brand-link {
  display: none;
}
.sidebar.collapsed .sidebar-brand-name { display: none; }
.sidebar.collapsed .sidebar-brand-meta { display: none; }
.sidebar.collapsed .nav-section-label { display: none; }
.sidebar.collapsed .nav-item {
  justify-content: center;
  padding: 14px 0;
  gap: 0;
}
.sidebar.collapsed .nav-label { display: none; }
.sidebar.collapsed .nav-badge { display: none; }
.sidebar.collapsed .nav-lock { display: none; }
.sidebar.collapsed .sidebar-footer {
  padding: 12px 0;
  text-align: center;
}
.sidebar.collapsed .sidebar-version { display: none; }

/* ── Brand ── */
.sidebar-brand {
  padding: 24px 20px 28px;
  flex-shrink: 0;
}
.sidebar-brand-top {
  display: flex; align-items: center; justify-content: space-between;
  gap: 8px;
}
.sidebar-brand-link {
  display: flex; align-items: center; gap: 8px;
  text-decoration: none;
}
.sidebar-brand-name {
  font-family: var(--font-serif);
  font-size: 22px; font-weight: 700;
  color: #2d2a26;
  letter-spacing: -0.3px;
}

.sidebar-toggle-btn {
  border: none;
  background: #fff;
  border-radius: 10px;
  padding: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  flex-shrink: 0;
}
.sidebar-toggle-btn svg {
  width: 20px;
  height: 20px;
  color: #2d2a26;
}

/* ── Brand meta strip ── */
.sidebar-brand-meta {
  margin-top: 10px;
  display: flex; flex-direction: column;
  gap: 8px;
}
.sidebar-clinical-badge {
  font-size: 10px; font-weight: 700;
  letter-spacing: 1px; text-transform: uppercase;
  color: #4a4741;
  background: #e8e4db;
  padding: 4px 10px;
  border-radius: 12px;
  display: inline-block;
  width: fit-content;
}
.sidebar-brand {
  position: relative;
}
.sidebar-brand::after {
  content: '';
  position: absolute;
  left: 20px; right: 20px;
  bottom: 0;
  height: 1px;
  background: #e8e4db;
}
.sidebar-brand-meta .sidebar-portal-badge,
.sidebar-brand-meta .sidebar-role-badge {
  border-radius: var(--radius-sm) !important;
}
.sidebar-portal-badge {
  font-size: 9.5px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
  color: var(--gold-dark); background: rgba(196,169,106,0.12);
  padding: 3px 8px; display: inline-block; width: fit-content;
}
.sidebar-role-badge {
  font-size: 10px; font-weight: 700;
  letter-spacing: 1px;
  color: var(--gold-dark);
  background: rgba(196, 169, 106, 0.15);
  padding: 4px 10px;
  border-radius: 12px;
  display: inline-flex; align-items: center; gap: 6px;
}
.sidebar-role-badge::before {
  content: ''; width: 8px; height: 8px;
  border-radius: 50%;
  background: #2f7d54;
  flex-shrink: 0;
}
.sidebar-emergency-badge {
  font-size: 9px; font-weight: 700; letter-spacing: 0.5px;
  color: var(--red); background: var(--red-light);
  padding: 3px 8px; display: inline-flex; align-items: center; gap: 4px;
  border-radius: var(--radius-sm); width: fit-content;
}

/* ── Nav ── */
.sidebar-nav {
  flex: 1;
  padding: 8px 12px;
  overflow-y: auto;
  overflow-x: visible;
  scrollbar-width: none;
}
.sidebar-nav::-webkit-scrollbar { display: none; }

.nav-section-label {
  font-size: 9px; font-weight: 700;
  letter-spacing: 1px; text-transform: uppercase;
  color: #8a8378;
  padding: 16px 12px 8px;
}

.sidebar .nav-item {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 12px;
  border-radius: 10px;
  font-size: 13px; font-weight: 500;
  color: #4a4741;
  cursor: pointer;
  background: none; border: none;
  width: 100%; text-align: left;
  white-space: nowrap; text-decoration: none;
  position: relative;
  transition: background .18s ease, color .18s ease;
}
.sidebar .nav-item .nav-icon {
  width: 22px; height: 22px;
  background: transparent; border-radius: 0;
  color: #6b655d;
  display: inline-flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  transition: color .18s ease, transform .18s ease;
  box-shadow: none;
}
.sidebar .nav-item .nav-icon :deep(svg) {
  width: 22px; height: 22px;
}
.sidebar .nav-item:hover {
  background: rgba(196, 169, 106, 0.10);
  color: #2d2a26;
}
.sidebar .nav-item:hover .nav-icon {
  color: #a0813e;
  background: transparent;
  transform: none;
  box-shadow: none;
}
.sidebar .nav-item {
  position: relative;
}
.sidebar .nav-item.active {
  background: #ffffff;
  color: var(--text);
  font-weight: 600;
  border-radius: 10px;
}
.sidebar .nav-item.active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 4px;
  height: 60%;
  background: #c0392b;
  border-top-right-radius: 4px;
  border-bottom-right-radius: 4px;
}
.sidebar.collapsed .nav-item.active::before {
  width: 3px;
  height: 80%;
}
.sidebar .nav-item.active .nav-icon {
  color: #a0813e;
  background: transparent;
  box-shadow: none;
}

.nav-label {
  flex: 1; min-width: 0;
  overflow: hidden; text-overflow: ellipsis;
  transition: color .18s ease;
}

/* Nav badge */
.nav-badge {
  margin-left: auto;
  font-size: 10px; font-weight: 700;
  padding: 1px 7px;
  border-radius: var(--radius-full);
  background: var(--surface-3); color: var(--text-3);
  flex-shrink: 0; line-height: 1.6;
}
.nav-badge.danger  { background: var(--red-light);    color: var(--red); }
.nav-badge.warning,
.nav-badge.warn    { background: var(--orange-light); color: var(--orange-dark); }
.nav-badge.blue    { background: var(--blue-light);   color: var(--blue-dark); }
.nav-badge.green   { background: var(--green-light);  color: var(--green-dark); }

/* Locked nav item */
.nav-item.is-locked { opacity: 0.55; cursor: pointer; }
.nav-item.is-locked:hover { opacity: 0.78; background: var(--surface-2); }
.nav-item.is-locked .nav-icon,
.nav-item.is-locked .nav-label { color: var(--text-4); }
.nav-item.is-locked .nav-badge { display: none; }
.nav-lock {
  margin-left: auto;
  display: inline-flex; align-items: center; justify-content: center;
  width: 18px; height: 18px;
  border-radius: var(--radius-full);
  color: var(--text-4);
  flex-shrink: 0;
}
.nav-item.is-locked:hover .nav-lock { color: var(--gold-dark); }

/* ── Scroll indicator ── */
.sidebar-scroll-indicator {
  display: none;
  align-items: center; justify-content: center;
  flex-shrink: 0; pointer-events: none;
  padding: 6px 0;
  width: 100%;
  text-align: center;
}
.sidebar-scroll-indicator.visible { display: flex; }
.sidebar-scroll-indicator-inner {
  display: inline-flex; align-items: center; justify-content: center;
  width: 26px; height: 26px;
  background: rgba(196,169,106,0.18);
  border: 1px solid rgba(196,169,106,0.42);
  border-radius: var(--radius-sm);
  color: var(--gold-dark);
  animation: sidebar-bounce 1.8s ease-in-out infinite;
  box-shadow: 0 1px 3px rgba(160,129,62,0.10);
}
.sidebar-scroll-indicator-inner svg {
  width: 14px !important; height: 14px !important;
  color: var(--gold-dark) !important;
  stroke: currentColor !important;
  stroke-width: 2 !important;
  display: block;
}

/* ── Footer ── */
.sidebar-footer {
  padding: 20px 20px 24px;
  flex-shrink: 0;
}
.sidebar-version {
  font-size: 10px;
  color: #8a8378;
  letter-spacing: 0.4px;
  text-align: left;
  margin: 0;
}

/* ── Mobile overlay ── */
.sidebar-overlay {
  display: none;
  position: fixed; inset: 0;
  background: rgba(30,28,26,0.4);
  z-index: 199;
}
@media (max-width: 768px) {
  .sidebar { transform: translateX(-100%); }
  .sidebar.open { transform: translateX(0); }
  .sidebar-overlay.open { display: block; }
}

/* ── Collapsed state ── */
.sidebar.collapsed .sidebar-brand-name { display: none; }
.sidebar.collapsed .sidebar-brand-link { display: none; }
.sidebar.collapsed .sidebar-brand-top { justify-content: center; }
.sidebar.collapsed .sidebar-footer { padding: 12px 0; text-align: center; }
.sidebar.collapsed .sidebar-version { display: none; }
.sidebar.collapsed .nav-item {
  justify-content: center;
  padding: 6px 0;
  gap: 0;
}
.sidebar.collapsed .nav-item .nav-icon {
  width: 36px; height: 36px;
  background: transparent;
  border-radius: var(--radius-sm);
  color: var(--text-3);
}
.sidebar.collapsed .nav-item .nav-icon :deep(svg) {
  width: 20px; height: 20px;
}
.sidebar.collapsed .nav-item:hover .nav-icon {
  background: rgba(196,169,106,0.10);
  color: var(--gold-dark);
}
.sidebar.collapsed .nav-item.active {
  background: transparent;
  box-shadow: none;
}
.sidebar.collapsed .nav-item.active .nav-icon {
  background: var(--surface);
  color: var(--gold-dark);
  box-shadow: var(--shadow-xs);
}
.sidebar.collapsed .nav-item.active::before {
  left: 0; top: 50%; bottom: auto;
  transform: translateY(-50%);
  height: 22px; width: 3px;
}
.sidebar.collapsed { width: 72px; }

/* ── Floating tooltip ── */
.sidebar-floating-tooltip {
  position: fixed;
  background: var(--text);
  color: var(--surface);
  font-size: 12px;
  font-weight: 600;
  padding: 5px 10px;
  border-radius: var(--radius-sm);
  white-space: nowrap;
  z-index: 10000;
  pointer-events: none;
  box-shadow: 0 4px 12px rgba(30,28,26,0.2);
}

@keyframes sidebar-bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(3px); }
}
</style>