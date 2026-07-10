<!--
  AppSidebar.vue — Aegis universal sidebar.
  Role-aware: renders correct nav for provider / cs / ss / bp / admin.
  No demo-switcher. No raw <svg>. All icons via AegisIcon.
-->
<template>
  <aside class="sidebar" :class="{ collapsed: ui.sidebarCollapsed, 'mobile-open': ui.mobileOpen }" id="sidebar">

    <!-- ── Brand ──────────────────────────────────────────────────── -->
    <div class="sidebar-brand">
      <div class="sidebar-brand-top">
        <Link :href="dashboardUrl" class="sidebar-brand-link">
          <span class="sidebar-brand-name">Aegis</span>
        </Link>
        <button
          class="sidebar-toggle-btn"
          @click="ui.sidebarCollapsed = !ui.sidebarCollapsed"
          data-tooltip="Collapse sidebar"
          data-tooltip-pos="bottom"
          aria-label="Toggle sidebar"
          :aria-pressed="ui.sidebarCollapsed"
        >
          <div class="sidebar-toggle-icon">
            <!-- Brand panel-left icon — fill="currentColor", not a Lucide stroke icon -->
            <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M16.5 4A1.5 1.5 0 0 1 18 5.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 2 14.5v-9A1.5 1.5 0 0 1 3.5 4zM7 15h9.5a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5H7zM3.5 5a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5H6V5z"/>
            </svg>
          </div>
        </button>
      </div>

      <div class="sidebar-brand-meta">
        <span v-if="portal === 'business_partner'" class="sidebar-portal-badge">BP Portal</span>
        <span v-if="portal === 'provider' && servicesMode" class="sidebar-portal-badge sidebar-portal-badge--services">Clinical Services</span>
        <span class="sidebar-role-badge" :class="{ emergency: hasEmergency }">
          <span
            class="avail-status-dot avail-status-dot--sm"
            :class="`avail-status-dot--${messagingStatus}`"
            aria-hidden="true"
          ></span>
          {{ roleLabel }}
        </span>
        <div v-if="portal === 'provider' && isAccessTier" class="sidebar-access-pill">
          <span>Continuity Access</span>
          <button type="button" class="sidebar-access-upgrade" @click="goToUpgrade()">Upgrade ↗</button>
        </div>
      </div>
    </div>

    <!-- ── Navigation ─────────────────────────────────────────────── -->
    <nav class="sidebar-nav" id="sidebarNav" role="navigation" aria-label="Main navigation">
      <template v-for="(items, section) in navSections" :key="section">
        <div class="nav-section-label">{{ section }}</div>
        <template v-for="item in items" :key="item.key">
          <!-- Locked items: render as button to block all navigation -->
          <button
            v-if="item.locked"
            type="button"
            class="nav-item is-locked"
            :data-label="item.label"
            @click="openUpgradeModal(item.label)"
          >
            <span class="nav-icon"><AegisIcon :name="item.icon" :size="14" /></span>
            <span class="nav-label">{{ item.label }}</span>
            <span class="nav-lock" data-tooltip="Upgrade to unlock">
              <AegisIcon name="lock" :size="12" />
            </span>
          </button>
          <!-- Unlocked items: normal Inertia Link -->
          <Link
            v-else
            class="nav-item"
            :class="{ active: activePage === item.key || (item.key === 'profile' && isOnOwnProfile) }"
            :href="item.href"
            :aria-current="(activePage === item.key || (item.key === 'profile' && isOnOwnProfile)) ? 'page' : 'false'"
            :data-label="item.label"
          >
            <span class="nav-icon"><AegisIcon :name="item.icon" :size="14" /></span>
            <span class="nav-label">{{ item.label }}</span>
            <span v-if="item.badge" class="nav-badge" :class="item.badgeType">{{ item.badge }}</span>
          </Link>
        </template>
      </template>
    </nav>

    <!-- Scroll indicator — centred chevron -->
    <div class="sidebar-scroll-indicator" :class="{ visible: showScrollIndicator }" aria-hidden="true">
      <div class="sidebar-scroll-indicator-inner">
        <AegisIcon name="chevron-down" :size="14" />
      </div>
    </div>

    <!-- Footer -->
    <div class="sidebar-footer">
      <p class="sidebar-version">Aegis © 2026 · {{ portalVersion }}</p>
    </div>

  </aside>

  <!-- Mobile overlay -->
  <div class="sidebar-overlay" :class="{ visible: ui.mobileOpen }" @click="ui.mobileOpen = false" aria-hidden="true"></div>
  <!-- Tier upgrade modal for locked nav items -->
  <SettingsTierUpgradeModal
    v-model:show="showUpgradeModal"
    :locked-feature-note="upgradeFeature"
    @upgrade="goToUpgrade"
  />
</template>

<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import AegisIcon from '@/components/ui/AegisIcon.vue'
import SettingsTierUpgradeModal from '@/components/settings/SettingsTierUpgradeModal.vue'
import { useUiStore } from '@/stores/ui'

const ui   = useUiStore()
const page = usePage()

// ── Shared props ──────────────────────────────────────────────────────
const portal       = computed(() => page.props.auth?.portal   ?? 'provider')
const user         = computed(() => page.props.auth?.user     ?? null)
const activePage = computed(() => {
  const full = page.props.activePage ?? ''
  if (!full) return ''
  const parts = full.split('.')
  // Route names like 'provider.dashboard' → 'dashboard'
  // Route names like 'provider.plan.index' → 'plan'
  // Route names like 'provider.messages' → 'messages'
  // Route names like 'messages.index' (shared) → 'messages'
  // Strip portal prefix (first segment), then use remaining minus trailing .index/.show/.store
  const withoutPortal = parts.slice(1) // drop 'provider'/'cs'/'ss'/'bp'/'admin'
  // If slicing the portal prefix left nothing (e.g. 'messages.index' has no portal prefix),
  // treat the full parts as the domain path instead
  const segments = withoutPortal.length ? withoutPortal : parts
  const last = segments[segments.length - 1]
  // Drop generic suffixes — use the domain segment before them
  if (['index', 'show', 'store', 'update', 'destroy'].includes(last) && segments.length > 1) {
    return segments[segments.length - 2]
  }
  return last ?? ''
})
const hasEmergency = computed(() => page.props.hasEmergency   ?? false)
// True when the current URL is the user's own public profile page
const isOnOwnProfile = computed(() => {
  const slug = user.value?.slug
  if (!slug) return false
  return page.url.startsWith('/public/') && page.url.includes(`/${slug}`)
})
const unreadMsgs      = computed(() => page.props.unreadMessages ?? 0)
const openJobs        = computed(() => page.props.openJobPostings  ?? 0)
const pendingReferrals = computed(() => page.props.pendingReferrals ?? 0)

const isAccessTier     = computed(() => page.props.auth?.tier === 'access')
const messagingStatus  = computed(() => page.props.auth?.user?.messaging_status ?? 'available')
const servicesMode = computed(() => !!user.value?.services_mode)
const bpType       = computed(() => user.value?.bp_type ?? 'agency')

// ── Public profile URL — routes to the user's own public-facing profile ──
const publicProfileUrl = computed(() => {
  const slug = user.value?.slug
  if (!slug) return '#'
  const map = {
    practitioner:       route('public.provider', { slug }),
    continuity_steward: route('public.cs',       { slug }),
    support_steward:    route('public.ss',        { slug }),
    business_partner:   route('public.bp',        { slug }),
  }
  return map[user.value?.role] ?? '#'
})

// ── Role label ─────────────────────────────────────────────────────────
const roleLabel = computed(() => {
  if (hasEmergency.value && (portal.value === 'continuity_steward' || portal.value === 'support_steward')) {
    return 'Active Critical Incident'
  }
  return {
    provider:           'Practitioner',
    continuity_steward: 'Continuity Steward',
    support_steward:    'Support Steward',
    business_partner:   bpType.value === 'freelancer' ? 'Freelancer Account' : 'Agency Account',
    admin:              'Administrator',
  }[portal.value] ?? 'User'
})

const portalVersion = computed(() => ({
  provider:           'Practitioner Portal v2.4.1',
  continuity_steward: 'Continuity Steward v1.0.0',
  support_steward:    'Support Steward v1.0.0',
  business_partner:   'BP Portal v1.0.0',
  admin:              'Admin Portal v1.0.0',
}[portal.value] ?? 'v1.0.0'))

// ── Route helper — try/catch so missing routes show # not crash ────────
function r(name) {
  try { return route(name) } catch { return '#' }
}

// ── Dashboard URL ──────────────────────────────────────────────────────
const dashboardUrl = computed(() => r({
  provider:           'provider.dashboard',
  continuity_steward: 'cs.dashboard',
  support_steward:    'ss.dashboard',
  business_partner:   'bp.dashboard',
  admin:              'admin.dashboard',
}[portal.value] ?? 'provider.dashboard'))

// ── Badge helper ───────────────────────────────────────────────────────
function badgeFromCount(n) { return n > 0 ? String(n) : '' }
const msgs = computed(() => unreadMsgs.value > 0 ? String(unreadMsgs.value) : '')
const pendingEngagements = computed(() => {
  const n = page.props.pendingEngagementRequests ?? 0
  return n > 0 ? String(n) : ''
})

// Active disputes badge — driven by disputes prop on Finances page (page-level, not shared)
// Falls back to 0 when on any other page
const activeDisputeCount = computed(() => {
  const disputes = page.props.disputes ?? []
  return disputes.filter(d => ['open', 'awaiting_response', 'under_review'].includes(d.status)).length
})
const financesBadge = computed(() => activeDisputeCount.value > 0 ? String(activeDisputeCount.value) : '')

// ── Nav sections ───────────────────────────────────────────────────────
const showUpgradeModal = ref(false)
const upgradeFeature   = ref('')

function openUpgradeModal(label) {
  upgradeFeature.value   = label
  showUpgradeModal.value = true
}

function goToUpgrade() {
  showUpgradeModal.value = false
  router.visit(route('provider.settings.index', { section: 'billing', upgrade: '1' }))
}

const navSections = computed(() => {
  switch (portal.value) {

    // ── PROVIDER ─────────────────────────────────────────────────────
    case 'provider': {
      const main = [
        { key: 'overview',  href: r('provider.overview'),      icon: 'clock',     label: 'Overview — Start Here' },
        { key: 'dashboard', href: r('provider.dashboard'),     icon: 'grid',      label: 'Dashboard' },
        { key: 'profile',   href: publicProfileUrl.value,       icon: 'user',      label: 'My Profile' },
      ]
      if (!isAccessTier.value) {
        main.push({ key: 'jobs', href: r('provider.jobs.index'), icon: 'briefcase', label: 'Support & Services', badge: badgeFromCount(openJobs.value) })
      }
      const practice = [
        { key: 'referrals', href: r('provider.referrals.index'), icon: 'share-tree',    label: 'Referrals',   badge: isAccessTier.value ? '' : badgeFromCount(pendingReferrals.value), locked: isAccessTier.value },
        { key: 'network',   href: r('provider.network.index'),   icon: 'users-network', label: 'Network' },
      ]
      if (servicesMode.value) {
        practice.push({ key: 'services', href: r('provider.services.index'), icon: 'layers-2', label: 'My Services', badge: isAccessTier.value ? '' : '3', locked: isAccessTier.value })
      }
      return {
        'Main': main,
        'My Practice': practice,
        'Continuity': [
          { key: 'plan',     href: r('provider.plan.index'),     icon: 'shield-check',    label: 'Continuity Plan' },
          { key: 'stewards', href: r('provider.stewards.index'), icon: 'user-check',      label: 'Continuity Stewards', badge: '1' },
          { key: 'ss',    href: r('provider.ss.index'),       icon: 'users-2',         label: 'Support Stewards' },
          { key: 'documents', href: r('provider.documents.index'), icon: 'file-pen',       label: 'Important Documents' },
          { key: 'vault',               href: r('provider.vault.index'),    icon: 'lock-3',          label: 'Document Vault' },
          { key: 'finances',            href: r('provider.finances.index'), icon: 'credit-card',     label: 'Finances', badge: financesBadge.value },
        ],
        'Communication': [
          { key: 'messages', href: r('provider.messages'), icon: 'message',   label: 'Messages',    badge: msgs.value },
          { key: 'activity', href: r('provider.activity'), icon: 'activity',  label: 'Activity Log' },
        ],
        'Explore': [
          { key: 'news',   href: r('provider.news.index'),   icon: 'megaphone', label: 'News & Resources' },
          { key: 'events', href: r('provider.events.index'), icon: 'calendar',  label: 'Events' },
        ],
        'Account': [
          { key: 'settings', href: r('provider.settings.index'), icon: 'settings',    label: 'Settings' },
          { key: 'support',  href: r('provider.support'),        icon: 'help-circle', label: 'Support' },
        ],
      }
    }

    // ── CONTINUITY STEWARD ────────────────────────────────────────────
    case 'continuity_steward':
      return {
        'Main': [
          { key: 'overview',  href: r('cs.overview'),      icon: 'clock', label: 'Overview — Start Here' },
          { key: 'dashboard', href: r('cs.dashboard'),     icon: 'grid',  label: 'Dashboard' },
          { key: 'profile',   href: publicProfileUrl.value,       icon: 'user',  label: 'My Profile' },
        ],
        'My Work': [
          { key: 'tasks',            href: r('cs.tasks.index'),     icon: 'clipboard-check', label: 'My Tasks' },
          { key: 'providers',           href: r('cs.providers.index'), icon: 'users',           label: 'My Practitioners' },
          { key: 'documents', href: r('cs.documents.index'), icon: 'file-pen',        label: 'Important Documents' },
          { key: 'finances',            href: r('cs.finances.index'),  icon: 'credit-card',     label: 'Finances' },
        ],
        'Critical Incident': [
          { key: 'continuity', href: r('cs.continuity.index'), icon: 'alert-triangle', label: 'Continuity Management', badge: hasEmergency.value ? '!' : '', badgeType: hasEmergency.value ? 'danger' : '' },
          { key: 'vault',                 href: '#',                       icon: 'lock-3',          label: 'Document Vault' },
        ],
        'Communication': [
          { key: 'messages', href: r('cs.messages'), icon: 'message',  label: 'Messages',    badge: msgs.value },
          { key: 'activity', href: r('cs.activity'), icon: 'activity', label: 'Activity Log' },
        ],
        'Account': [
          { key: 'settings', href: r('cs.settings.index'), icon: 'settings',    label: 'Settings' },
          { key: 'support',  href: r('cs.support'),         icon: 'help-circle', label: 'Support' },
        ],
      }

    // ── SUPPORT STEWARD ───────────────────────────────────────────────
    case 'support_steward':
      return {
        'Main': [
          { key: 'overview',  href: r('ss.overview'),      icon: 'clock', label: 'Overview — Start Here' },
          { key: 'dashboard', href: r('ss.dashboard'),     icon: 'grid',  label: 'Dashboard' },
          { key: 'profile',   href: publicProfileUrl.value,       icon: 'user',  label: 'My Profile' },
        ],
        'Critical Moment Plans': [
          { key: 'providers', href: r('ss.providers.index'), icon: 'users', label: 'My Practitioners', badge: '8' },
        ],
        'Activation': [
          { key: 'tasks',              href: r('ss.tasks.index'),     icon: 'clipboard-check', label: 'My Tasks',              badge: '5' },
          { key: 'documents',   href: r('ss.documents.index'), icon: 'file-pen',        label: 'Important Documents' },
          { key: 'stewards',   href: r('ss.cs.index'),        icon: 'user-check',      label: 'Continuity Stewards' },
          { key: 'incidents', href: r('ss.incidents.index'), icon: 'alert-triangle',  label: 'Critical Incident Log', badge: hasEmergency.value ? '1' : '', badgeType: hasEmergency.value ? 'danger' : '' },
        ],
        'Communication': [
          { key: 'messages', href: r('ss.messages'), icon: 'message',  label: 'Messages',    badge: msgs.value },
          { key: 'activity', href: r('ss.activity'), icon: 'activity', label: 'Activity Log' },
        ],
        'Account': [
          { key: 'settings', href: r('ss.settings.index'), icon: 'settings',    label: 'Settings' },
          { key: 'support',  href: r('ss.support'),         icon: 'help-circle', label: 'Support' },
        ],
      }

    // ── BUSINESS PARTNER ──────────────────────────────────────────────
    case 'business_partner': {
      const sections = {
        'Main': [
          { key: 'overview',  href: r('bp.overview'),      icon: 'clock', label: 'Overview — Start Here' },
          { key: 'dashboard', href: r('bp.dashboard'),     icon: 'grid',  label: 'Dashboard' },
          { key: 'profile',   href: publicProfileUrl.value,       icon: 'user',  label: 'My Profile' },
        ],
        'Work': [
          { key: 'jobs',  href: r('bp.jobs.index'),       icon: 'search',         label: 'Find Jobs' },
          { key: 'engagement-requests', href: r('bp.engagement-requests.index'), icon: 'briefcase', label: 'Engagement Requests', badge: pendingEngagements.value, badge_type: 'warning' },
          { key: 'contracts',  href: r('bp.contracts.index'),  icon: 'file-text',      label: 'Contracts' },
          { key: 'proposals',  href: r('bp.proposals.index'),  icon: 'message-square', label: 'Proposals' },
          { key: 'milestones', href: r('bp.milestones.index'), icon: 'target-2',       label: 'Milestones' },
        ],
        'Financial': [
          { key: 'finances',      href: r('bp.finances.index'), icon: 'dollar',       label: 'Finances' },
          { key: 'invoices',      href: r('bp.invoices.index'), icon: 'receipt',      label: 'Invoices' },
          { key: 'payment', href: r('bp.payment.index'), icon: 'credit-card',  label: 'Payment Setup' },
        ],
        'Communication': [
          { key: 'messages', href: r('bp.messages'), icon: 'message',  label: 'Messages',    badge: msgs.value },
          { key: 'activity', href: r('bp.activity'), icon: 'activity', label: 'Activity Log' },
        ],
        'Account': [
          { key: 'settings', href: r('bp.settings.index'), icon: 'settings',    label: 'Settings' },
          { key: 'support',  href: r('bp.support'),         icon: 'help-circle', label: 'Support' },
        ],
      }
      if (bpType.value === 'agency') {
        sections['Team'] = [
          { key: 'team', href: r('bp.team.index'), icon: 'users-2', label: 'Team Management' },
        ]
      }
      return sections
    }

    // ── ADMIN ─────────────────────────────────────────────────────────
    case 'admin':
      return {
        'Main': [
          { key: 'dashboard', href: r('admin.dashboard'), icon: 'grid', label: 'Dashboard' },
        ],
        'Management': [
          { key: 'users',    href: r('admin.users.index'),    icon: 'users',        label: 'Users' },
          { key: 'roles',    href: r('admin.roles.index'),    icon: 'shield-check', label: 'Roles & Permissions' },
          { key: 'packages', href: r('admin.packages.index'), icon: 'layers-2',     label: 'Packages & Pricing' },
        ],
        'Finance': [
          { key: 'payouts',  href: r('admin.payouts.index'),  icon: 'dollar',       label: 'Payments & Payouts' },
        ],
        'Support': [
          { key: 'complaints',    href: r('admin.complaints.index'), icon: 'help-circle',    label: 'Complaints & Tickets' },
          { key: 'help', href: r('admin.help.index'),       icon: 'book-open',      label: 'Help Articles' },
          { key: 'incidents',     href: r('admin.incidents.index'),  icon: 'alert-triangle', label: 'Incident Oversight' },
        ],
      }

    default:
      return {}
  }
})

// ── Scroll indicator ───────────────────────────────────────────────────
const showScrollIndicator = ref(false)
let navEl = null

function checkScroll() {
  if (!navEl) return
  showScrollIndicator.value = navEl.scrollHeight > navEl.clientHeight + navEl.scrollTop + 40
}

onMounted(() => {
  navEl = document.getElementById('sidebarNav')
  if (navEl) navEl.addEventListener('scroll', checkScroll)
  setTimeout(checkScroll, 100)
})
onBeforeUnmount(() => {
  if (navEl) navEl.removeEventListener('scroll', checkScroll)
})
</script>

<style scoped>
/* ── Sidebar shell ───────────────────────────────────────────────────── */
.sidebar {
  width: var(--sidebar-width);
  background: var(--surface-2);
  min-height: 100vh;
  position: sticky;
  top: 0;
  height: 100vh;
  display: flex;
  flex-direction: column;
  z-index: 200;
  transition: width var(--transition-slow);
  overflow-y: auto;
  overflow-x: hidden;
  scrollbar-width: none;
  border-right: 1px solid var(--border);
  flex-shrink: 0;
}
.sidebar::-webkit-scrollbar { display: none; }

/* ── Brand ──────────────────────────────────────────────────────────── */
.sidebar-brand {
  padding: 22px 22px 18px;
  flex-shrink: 0;
  border-bottom: 1px solid var(--border);
}
.sidebar-brand-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}
.sidebar-brand-link {
  display: flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
}
.sidebar-brand-name {
  font-family: var(--font-serif);
  font-size: 20px;
  font-weight: 700;
  color: var(--text);
  letter-spacing: 0.3px;
}

/* Toggle button — matches PHP .sidebar-toggle-btn exactly */
.sidebar-toggle-btn {
  width: 30px;
  height: 30px;
  border-radius: var(--radius-sm);
  border: none;
  background: transparent;
  color: var(--text-4);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: color .18s ease, background .18s ease;
  flex-shrink: 0;
  padding: 0;
}
.sidebar-toggle-btn:hover { color: var(--text); background: var(--surface-3); }
.sidebar-toggle-icon {
  width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* ── Brand meta strip ───────────────────────────────────────────────── */
.sidebar-brand-meta {
  margin-top: 10px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.sidebar-brand-meta .sidebar-portal-badge,
.sidebar-brand-meta .sidebar-access-pill,
.sidebar-brand-meta .sidebar-role-badge {
  border-radius: var(--radius-sm) !important;
}

/* Portal badge — e.g. "CLINICAL SERVICES", "BP PORTAL" */
.sidebar-portal-badge {
  font-family: var(--font-sans);
  font-size: 9.5px;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
  padding: 3px 8px;
  display: inline-block;
  width: fit-content;
  line-height: 1.4;
  border: 1px solid rgba(0,0,0,0.07);
  color: var(--gold-dark);
  background: rgba(196,169,106,0.12);
}
.sidebar-portal-badge--services { color: var(--blue-dark); background: rgba(74,144,196,0.12); }

/* Role badge — e.g. "PRACTITIONER", "CONTINUITY STEWARD" */
.sidebar-role-badge {
  display: inline-flex !important;
  align-items: center !important;
  gap: 5px;
  width: fit-content !important;
  font-family: var(--font-sans) !important;
  font-size: 9px !important;
  font-weight: 700 !important;
  letter-spacing: 0.8px !important;
  text-transform: uppercase !important;
  color: var(--gold-dark) !important;
  background: var(--badge-bg-gold) !important;
  border: 1px solid var(--badge-border-gold, rgba(196,169,106,0.35)) !important;
  padding: 3px 7px !important;
  line-height: 1.4 !important;
}
/* Status dot is now an avail-status-dot--sm span injected by the template */
.sidebar-access-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 10px;
  font-weight: 600;
  color: var(--text-2);
  background: var(--surface-3);
  padding: 4px 9px;
  border-radius: var(--radius-sm);
  width: fit-content;
}
.sidebar-access-upgrade {
  background: none;
  border: none;
  border-left: 1px solid var(--border);
  padding-left: 6px;
  cursor: pointer;
  font-family: inherit;
  font-size: inherit;
  color: var(--gold-dark);
  font-weight: 700;
}
.sidebar-access-upgrade:hover { color: var(--text); }

/* ── Navigation ─────────────────────────────────────────────────────── */
.sidebar-nav {
  flex: 1;
  padding: 8px 12px;
  overflow-y: auto;
  overflow-x: visible;
  scrollbar-width: none;
}
.sidebar-nav::-webkit-scrollbar { display: none; }

.nav-section-label {
  font-size: 9.5px;
  font-weight: 700;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  color: var(--text-4);
  padding: 14px 10px 6px;
}

.sidebar .nav-item {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 7px 10px;
  border-radius: var(--radius-sm);
  font-size: 13px;
  font-weight: 400;
  color: var(--text-2);
  cursor: pointer;
  background: none;
  border: none;
  width: 100%;
  text-align: left;
  white-space: nowrap;
  text-decoration: none;
  position: relative;
  transition: background .18s ease, color .18s ease;
}
.sidebar .nav-item .nav-icon {
  width: 16px;
  height: 16px;
  background: transparent;
  border-radius: 0;
  color: var(--text-3);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: color .18s ease;
}
.sidebar .nav-item:hover { background: rgba(196,169,106,0.08); color: var(--text); }
.sidebar .nav-item:hover .nav-icon { color: var(--gold-dark); }
.sidebar .nav-item.active {
  background: var(--surface);
  color: var(--text);
  font-weight: 600;
  box-shadow: var(--shadow-xs);
}
.sidebar .nav-item.active .nav-icon { color: var(--gold-dark); }
.sidebar .nav-item.active::before {
  content: '';
  position: absolute;
  left: -12px;
  top: 8px;
  bottom: 8px;
  width: 3px;
  border-radius: 0 3px 3px 0;
  background: var(--gold-dark);
}
.nav-label { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; }
.nav-badge {
  margin-left: auto;
  font-size: 10px;
  font-weight: 700;
  padding: 1px 7px;
  border-radius: var(--radius-full);
  background: var(--surface-3);
  color: var(--text-3);
  flex-shrink: 0;
  line-height: 1.6;
}
.nav-badge.danger  { background: var(--red-light);    color: var(--red); }
.nav-badge.warning { background: var(--orange-light); color: var(--orange-dark); }
.nav-badge.blue    { background: var(--blue-light);   color: var(--blue-dark); }
.nav-badge.green   { background: var(--green-light);  color: var(--green-dark); }

/* Locked items */
.nav-item.is-locked { opacity: 0.55; }
.nav-item.is-locked:hover { opacity: 0.78; background: var(--surface-2); }
.nav-lock {
  margin-left: auto;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  border-radius: var(--radius-full);
  color: var(--text-4);
  flex-shrink: 0;
}

/* ── Scroll indicator — centred ─────────────────────────────────────── */
.sidebar-scroll-indicator {
  display: none;
  flex-shrink: 0;
  pointer-events: none;
  padding: 6px 0;
  width: 100%;
  text-align: center;
}
.sidebar-scroll-indicator.visible {
  display: flex;
  align-items: center;
  justify-content: center;
}
.sidebar-scroll-indicator-inner {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 26px;
  margin: 0 auto;
  background: rgba(196,169,106,0.18);
  border: 1px solid rgba(196,169,106,0.42);
  border-radius: var(--radius-sm);
  color: var(--gold-dark);
  animation: sidebar-bounce 1.8s ease-in-out infinite;
  box-shadow: 0 1px 3px rgba(160,129,62,0.10);
}

/* ── Footer ──────────────────────────────────────────────────────────── */
.sidebar-footer {
  padding: 14px 22px;
  flex-shrink: 0;
  border-top: 1px solid var(--border);
}
.sidebar-version { font-size: 10px; color: var(--text-4); line-height: 1.5; }

/* ── Collapsed state ──────────────────────────────────────────────────── */
.sidebar.collapsed { width: 60px; }
.sidebar.collapsed .sidebar-brand-name,
.sidebar.collapsed .sidebar-brand-meta,
.sidebar.collapsed .nav-label,
.sidebar.collapsed .nav-badge,
.sidebar.collapsed .nav-section-label,
.sidebar.collapsed .nav-lock,
.sidebar.collapsed .sidebar-footer { display: none; }
.sidebar.collapsed .sidebar-brand { padding: 18px 12px; }
.sidebar.collapsed .nav-item { justify-content: center; padding: 8px; }
.sidebar.collapsed .nav-item .nav-icon { margin: 0; }
.sidebar.collapsed .sidebar-scroll-indicator { display: none !important; }

/* ── Mobile overlay ───────────────────────────────────────────────────── */
.sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 199; }
.sidebar-overlay.visible { display: block; }

@media (max-width: 768px) {
  .sidebar { position: fixed; left: 0; top: 0; bottom: 0; transform: translateX(-100%); z-index: 200; }
  .sidebar.mobile-open { transform: translateX(0); }
}

/* ── Keyframes ─────────────────────────────────────────────────────────── */
@keyframes sidebar-bounce {
  0%, 100% { transform: translateY(0); }
  50%       { transform: translateY(3px); }
}
@keyframes dot-pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(220,38,38,0.4); }
  50%       { box-shadow: 0 0 0 4px rgba(220,38,38,0); }
}
</style>
