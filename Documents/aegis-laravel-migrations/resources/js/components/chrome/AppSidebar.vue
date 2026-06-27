<!--
  AppSidebar.vue — universal portal sidebar.

  Replaces _shared/sidebar.php. Decides which menu set to render from the
  auth store's `portal` value (provider, continuity_steward, support_steward,
  business_partner, admin). Highlights the active item from the Inertia page
  prop `activePage` set by every controller.

  Tier gating: on Access tier, locked items (referrals, services) render
  with .is-locked and intercept clicks to open the upgrade modal — NEVER
  navigating to a fallback URL. Mirrors AegisTier.onLockedClick().

  Active-incident badge: when `incident.hasEmergency` is true, the CS portal's
  "Continuity Management" item gets a '!' danger badge and the SS portal's
  "Critical Incident Log" gets a '1' danger badge.
-->
<template>
  <aside
    class="sidebar"
    :class="{ 'sidebar--collapsed': ui.sidebarCollapsed, 'sidebar--mobile-open': ui.mobileOpen }"
    role="complementary"
    aria-label="Portal navigation"
  >
    <!-- ── Brand ──────────────────────────────────────────────────── -->
    <div class="sidebar-brand">
      <a :href="brandHref" class="sidebar-brand-link">
        <img src="/aegis-favicon.svg" alt="" class="sidebar-brand-mark" />
        <div class="sidebar-brand-text">
          <span class="sidebar-brand-name">Aegis</span>
          <span class="sidebar-brand-portal">{{ portalLabel }}</span>
        </div>
      </a>

      <button
        type="button"
        class="sidebar-collapse-btn"
        :aria-label="ui.sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
        @click="ui.toggleSidebar"
      >
        <AegisIcon :name="ui.sidebarCollapsed ? 'chevron-right' : 'chevron-left'" :size="14" />
      </button>

      <button
        type="button"
        class="sidebar-mobile-close"
        aria-label="Close menu"
        @click="ui.setMobileOpen(false)"
      >
        <AegisIcon name="x" :size="16" />
      </button>
    </div>

    <!-- ── Tier badge (Provider portal only) ─────────────────────── -->
    <div
      v-if="auth.isPractitioner"
      class="sidebar-tier-badge"
      :class="auth.isAccessTier ? 'is-access' : 'is-practice'"
    >
      <AegisIcon :name="auth.isAccessTier ? 'shield' : 'shield-check'" :size="13" />
      <span>{{ auth.isAccessTier ? 'Continuity Access' : 'Continuity Practice' }}</span>
      <button
        v-if="auth.isAccessTier"
        type="button"
        class="sidebar-tier-upgrade"
        @click="upgrade.openUpgradeModal"
      >Upgrade ↗</button>
    </div>

    <!-- ── Active incident strip (CS / SS portals only) ──────────── -->
    <div
      v-if="incident.hasEmergency && (auth.isContinuitySteward || auth.isSupportSteward)"
      class="sidebar-incident-badge"
    >
      <AegisIcon name="alert-triangle" :size="13" />
      <span>Active Critical Incident</span>
    </div>

    <!-- ── Nav ────────────────────────────────────────────────────── -->
    <nav class="sidebar-nav" role="navigation" aria-label="Main navigation">
      <template v-for="(items, section) in navItems" :key="section">
        <div class="nav-section-label">{{ section }}</div>
        <ul class="nav-section">
          <li v-for="item in items" :key="item.key">
            <a
              :href="item.locked ? '#' : item.href"
              class="nav-item"
              :class="{
                'is-active': isActive(item.key),
                'is-locked': item.locked,
              }"
              @click="onItemClick(item, $event)"
            >
              <AegisIcon :name="item.icon" :size="16" />
              <span class="nav-item-label">{{ item.label }}</span>
              <span
                v-if="item.badge"
                class="nav-badge"
                :class="badgeClass(item.badge_type)"
              >{{ item.badge }}</span>
              <AegisIcon v-if="item.locked" name="lock" :size="11" class="nav-lock" />
            </a>
          </li>
        </ul>
      </template>
    </nav>

    <!-- ── Footer ─────────────────────────────────────────────────── -->
    <div class="sidebar-footer">
      <div class="sidebar-version">{{ versionLine }}</div>
    </div>
  </aside>

  <!-- Mobile backdrop -->
  <div
    v-if="ui.mobileOpen"
    class="sidebar-backdrop"
    @click="ui.setMobileOpen(false)"
  ></div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { useIncidentStore } from '@/stores/incident'
import { useUpgrade } from '@/composables/useUpgrade'

const auth     = useAuthStore()
const ui       = useUiStore()
const incident = useIncidentStore()
const upgrade  = useUpgrade()
const page     = usePage()

const activePage = computed(() => page.props.activePage ?? '')

// ── Portal label + version + brand href ────────────────────────────
const PORTAL_META = {
  provider:           { label: 'Practitioner Portal',     version: 'Aegis © 2026 · Practitioner Portal v2.4.1',  home: 'provider.dashboard' },
  continuity_steward: { label: 'Continuity Steward Portal', version: 'Aegis © 2026 · Continuity Steward Portal v1.0.0', home: 'cs.dashboard' },
  support_steward:    { label: 'Support Steward Portal',   version: 'Aegis © 2026 · Support Steward v1.0.0',     home: 'ss.dashboard' },
  business_partner:   { label: 'Business Partner Portal',  version: 'Aegis © 2026 · BP Portal v1.0.0',           home: 'bp.dashboard' },
  admin:              { label: 'Admin Portal',             version: 'Aegis © 2026 · Admin Portal v1.0.0',        home: 'admin.dashboard' },
}

const portalMeta  = computed(() => PORTAL_META[auth.portal] ?? PORTAL_META.provider)
const portalLabel = computed(() => portalMeta.value.label)
const versionLine = computed(() => portalMeta.value.version)
const brandHref   = computed(() => {
  try { return route(portalMeta.value.home) } catch { return '/' }
})

// ── Nav builders, one per portal ────────────────────────────────────
const providerNav = computed(() => {
  const isAccess  = auth.isAccessTier
  const services  = !isAccess // services mode: Practice tier only

  const main = [
    { key: 'overview',  href: r('provider.overview'),  icon: 'overview',  label: 'Overview — Start Here' },
    { key: 'dashboard', href: r('provider.dashboard'), icon: 'home',      label: 'Dashboard' },
    { key: 'profile',   href: r('provider.profile'),   icon: 'user',      label: 'My Profile' },
  ]
  if (!isAccess) {
    main.push({ key: 'job-postings', href: r('provider.job-postings'), icon: 'briefcase', label: 'Support & Services', badge: '12' })
  }

  const practice = [
    { key: 'referrals', href: r('provider.referrals'), icon: 'share-tree', label: 'Referrals', badge: isAccess ? '' : '3', locked: isAccess },
    { key: 'network',   href: r('provider.network'),   icon: 'network',    label: 'Network' },
  ]
  if (services) {
    practice.push({ key: 'services', href: r('provider.services'), icon: 'briefcase-rx', label: 'My Services', badge: '3' })
  }

  return {
    'Main': main,
    'My Practice': practice,
    'Continuity': [
      { key: 'continuity-plan',     href: r('provider.plan'),                icon: 'shield-check',  label: 'Continuity Plan' },
      { key: 'continuity-stewards', href: r('provider.continuity-stewards'), icon: 'users-network', label: 'Continuity Stewards', badge: '1' },
      { key: 'support-stewards',    href: r('provider.support-stewards'),    icon: 'users-line',    label: 'Support Stewards' },
      { key: 'important-documents', href: r('provider.documents'),           icon: 'file-text',     label: 'Important Documents' },
      { key: 'vault',               href: r('provider.vault'),               icon: 'vault',         label: 'Document Vault' },
      { key: 'finances',            href: r('provider.finances'),            icon: 'dollar',        label: 'Finances', badge: '2' },
    ],
    'Communication': [
      { key: 'messages', href: r('provider.messages'), icon: 'message', label: 'Messages', badge: badgeFromCount(unreadMessages.value) },
      { key: 'activity', href: r('provider.activity'), icon: 'activity', label: 'Activity Log' },
    ],
    'Explore': [
      { key: 'news',   href: r('provider.news'),   icon: 'megaphone', label: 'News & Resources' },
      { key: 'events', href: r('provider.events'), icon: 'calendar',  label: 'Events' },
    ],
    'Account': [
      { key: 'settings', href: r('provider.settings'), icon: 'settings',    label: 'Settings' },
      { key: 'support',  href: r('provider.support'),  icon: 'help-circle', label: 'Support' },
    ],
  }
})

const csNav = computed(() => ({
  'Main': [
    { key: 'overview',  href: r('cs.overview'),  icon: 'overview', label: 'Overview — Start Here' },
    { key: 'dashboard', href: r('cs.dashboard'), icon: 'home',     label: 'Dashboard' },
    { key: 'profile',   href: r('cs.profile'),   icon: 'user',     label: 'My Profile' },
  ],
  'My Work': [
    { key: 'my-tasks',            href: r('cs.tasks'),     icon: 'tasks',     label: 'My Tasks' },
    { key: 'providers',           href: r('cs.providers'), icon: 'users',     label: 'My Practitioners' },
    { key: 'important-documents', href: r('cs.documents'), icon: 'file-text', label: 'Important Documents' },
    { key: 'finances',            href: r('cs.finances'),  icon: 'dollar',    label: 'Finances' },
  ],
  'Critical Incident': [
    { key: 'continuity-management', href: r('cs.continuity-management'), icon: 'alert-triangle', label: 'Continuity Management', badge: incident.hasEmergency ? '!' : '', badge_type: incident.hasEmergency ? 'danger' : '' },
    { key: 'vault',                 href: r('cs.vault'),                 icon: 'vault',          label: 'Document Vault' },
  ],
  'Communication': [
    { key: 'messages', href: r('cs.messages'), icon: 'message',  label: 'Messages', badge: badgeFromCount(unreadMessages.value) },
    { key: 'activity', href: r('cs.activity'), icon: 'activity', label: 'Activity Log' },
  ],
  'Account': [
    { key: 'settings', href: r('cs.settings'), icon: 'settings',    label: 'Settings' },
    { key: 'support',  href: r('cs.support'),  icon: 'help-circle', label: 'Support' },
  ],
}))

const ssNav = computed(() => ({
  'Main': [
    { key: 'overview',  href: r('ss.overview'),  icon: 'overview', label: 'Overview — Start Here' },
    { key: 'dashboard', href: r('ss.dashboard'), icon: 'home',     label: 'Dashboard' },
    { key: 'profile',   href: r('ss.profile'),   icon: 'user',     label: 'My Profile' },
  ],
  'Critical Moment Plans': [
    { key: 'providers', href: r('ss.providers'), icon: 'users', label: 'My Practitioners', badge: '8' },
  ],
  'Activation': [
    { key: 'my-tasks',                href: r('ss.tasks'),                icon: 'tasks',          label: 'My Tasks', badge: '5' },
    { key: 'important-documents',     href: r('ss.documents'),            icon: 'file-text',      label: 'Important Documents' },
    { key: 'continuity-stewards',     href: r('ss.continuity-stewards'),  icon: 'users-network',  label: 'Continuity Stewards' },
    { key: 'critical-incident-log',   href: r('ss.incident-log'),         icon: 'alert-triangle', label: 'Critical Incident Log', badge: incident.hasEmergency ? '1' : '', badge_type: incident.hasEmergency ? 'danger' : '' },
  ],
  'Communication': [
    { key: 'messages', href: r('ss.messages'), icon: 'message',  label: 'Messages', badge: badgeFromCount(unreadMessages.value) },
    { key: 'activity', href: r('ss.activity'), icon: 'activity', label: 'Activity Log' },
  ],
  'Account': [
    { key: 'settings', href: r('ss.settings'), icon: 'settings',    label: 'Settings' },
    { key: 'support',  href: r('ss.support'),  icon: 'help-circle', label: 'Support' },
  ],
}))

const bpNav = computed(() => {
  const nav = {
    'Main': [
      { key: 'overview',  href: r('bp.overview'),  icon: 'overview', label: 'Overview — Start Here' },
      { key: 'dashboard', href: r('bp.dashboard'), icon: 'home',     label: 'Dashboard' },
      { key: 'profile',   href: r('bp.profile'),   icon: 'user',     label: 'My Profile' },
    ],
    'Work': [
      { key: 'find-jobs',  href: r('bp.find-jobs'),  icon: 'search-lg', label: 'Find Jobs',  badge: badgeFromCount(bpBadges.value.new_jobs),          badge_type: 'blue' },
      { key: 'contracts',  href: r('bp.contracts'),  icon: 'agreement', label: 'Contracts',  badge: badgeFromCount(bpBadges.value.active_contracts), badge_type: 'green' },
      { key: 'proposals',  href: r('bp.proposals'),  icon: 'file-pen',  label: 'Proposals',  badge: badgeFromCount(bpBadges.value.pending_proposals),badge_type: 'warning' },
      { key: 'milestones', href: r('bp.milestones'), icon: 'flag-2',    label: 'Milestones', badge: badgeFromCount(bpBadges.value.overdue_milestones), badge_type: 'danger' },
    ],
    'Financial': [
      { key: 'finances',      href: r('bp.finances'),      icon: 'dollar',       label: 'Finances' },
      { key: 'invoices',      href: r('bp.invoices'),      icon: 'receipt',      label: 'Invoices', badge: badgeFromCount(bpBadges.value.pending_invoices), badge_type: 'warning' },
      { key: 'payment-setup', href: r('bp.payment-setup'), icon: 'credit-card',  label: 'Payment Setup' },
    ],
    'Communication': [
      { key: 'messages', href: r('bp.messages'), icon: 'message',  label: 'Messages', badge: badgeFromCount(unreadMessages.value) },
      { key: 'activity', href: r('bp.activity'), icon: 'activity', label: 'Activity Log' },
    ],
    'Account': [
      { key: 'settings', href: r('bp.settings'), icon: 'settings',    label: 'Settings' },
      { key: 'support',  href: r('bp.support'),  icon: 'help-circle', label: 'Support' },
    ],
  }
  if (auth.isAgency) {
    nav.Team = [
      { key: 'team', href: r('bp.team'), icon: 'users-2', label: 'Team Management' },
    ]
  }
  return nav
})

const adminNav = computed(() => ({
  'Main': [
    { key: 'dashboard', href: r('admin.dashboard'), icon: 'home', label: 'Dashboard' },
  ],
  'Management': [
    { key: 'users',    href: r('admin.users'),    icon: 'users',      label: 'Users' },
    { key: 'roles',    href: r('admin.roles'),    icon: 'shield',     label: 'Roles & Permissions' },
    { key: 'packages', href: r('admin.packages'), icon: 'briefcase',  label: 'Packages & Pricing' },
  ],
  'Finance': [
    { key: 'payments', href: r('admin.payments'), icon: 'credit-card', label: 'Payments & Payouts' },
  ],
  'Support': [
    { key: 'complaints',    href: r('admin.complaints'),    icon: 'help-circle',    label: 'Complaints & Tickets' },
    { key: 'help-articles', href: r('admin.help-articles'), icon: 'book',           label: 'Help Articles' },
    { key: 'incidents',     href: r('admin.incidents'),     icon: 'alert-triangle', label: 'Incident Oversight' },
  ],
}))

const navItems = computed(() => {
  if (auth.isPractitioner)      return providerNav.value
  if (auth.isContinuitySteward) return csNav.value
  if (auth.isSupportSteward)    return ssNav.value
  if (auth.isBusinessPartner)   return bpNav.value
  if (auth.isAdmin)             return adminNav.value
  return {}
})

// ── Badges from shared Inertia props ────────────────────────────────
const unreadMessages = computed(() => page.props.unreadMessages ?? 0)
const bpBadges       = computed(() => page.props.bpBadges ?? {
  new_jobs: 0, active_contracts: 0, pending_proposals: 0,
  overdue_milestones: 0, pending_invoices: 0,
})

// ── Helpers ─────────────────────────────────────────────────────────
function isActive(key) {
  const norm = activePage.value === 'tasks' ? 'my-tasks' : activePage.value
  return norm === key
}

function badgeClass(type) {
  if (!type) return ''
  return `nav-badge--${type}`
}

function badgeFromCount(n) {
  return n && n > 0 ? String(n) : ''
}

function r(name, params = {}) {
  try { return route(name, params) }
  catch { return '#' }
}

function onItemClick(item, e) {
  if (item.locked) {
    e.preventDefault()
    upgrade.openUpgradeModal()
    return
  }
  // Inertia handles regular hrefs via @inertiajs/vue3 link interception
  // when using <Link>, but for plain <a> we route through router.visit
  // to preserve SPA navigation and keep Pinia state alive.
  if (item.href && item.href !== '#') {
    e.preventDefault()
    router.visit(item.href)
    ui.setMobileOpen(false)
  }
}
</script>
