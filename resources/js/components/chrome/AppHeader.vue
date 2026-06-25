<!--
  AppHeader.vue — top bar across all authenticated pages.

  Replaces _shared/header.php. Contains:
    • mobile hamburger (opens sidebar drawer)
    • search input (Cmd-K stub)
    • notification bell with unread count + dropdown of recent events
    • user avatar/menu (profile, settings, sign out)

  Notification badge sources from useNotificationStore which reads
  Inertia's shared `unreadCount` prop.
-->
<template>
  <header class="page-header">
    <!-- Mobile menu trigger -->
    <button
      type="button"
      class="header-menu-btn"
      aria-label="Open menu"
      @click="ui.setMobileOpen(true)"
    >
      <AegisIcon name="menu" :size="20" />
    </button>

    <!-- Search -->
    <div class="header-search">
      <AegisIcon name="search" :size="16" />
      <input
        v-model="searchQuery"
        type="text"
        class="header-search-input"
        placeholder="Search…"
        @keyup.enter="onSearch"
      />
      <kbd class="header-search-kbd">⌘K</kbd>
    </div>

    <div class="header-spacer"></div>

    <!-- Bell -->
    <div class="header-bell-wrap" v-click-outside="closeBell">
      <button
        type="button"
        class="header-bell"
        :class="{ 'is-active': bellOpen }"
        aria-label="Notifications"
        @click="bellOpen = !bellOpen"
      >
        <AegisIcon name="bell" :size="18" />
        <span v-if="notifications.unreadCount > 0" class="header-bell-badge">
          {{ notifications.unreadCount > 99 ? '99+' : notifications.unreadCount }}
        </span>
      </button>

      <div v-if="bellOpen" class="header-bell-dropdown">
        <div class="header-bell-header">
          <span class="header-bell-title">Notifications</span>
          <a :href="activityHref" class="header-bell-view-all">View all</a>
        </div>

        <div class="header-bell-list">
          <div v-if="!notifications.events.length" class="header-bell-empty">
            <AegisIcon name="inbox" :size="20" />
            <span>You're all caught up.</span>
          </div>
          <a
            v-for="event in notifications.events.slice(0, 6)"
            :key="event.id"
            :href="event.href || '#'"
            class="header-bell-item"
            :class="{ 'is-unread': !event.read_at }"
          >
            <AegisIcon :name="iconFor(event)" :size="14" />
            <div class="header-bell-item-body">
              <div class="header-bell-item-title">{{ event.title }}</div>
              <div class="header-bell-item-time">{{ activity.timeAgo(event.created_at) }}</div>
            </div>
          </a>
        </div>
      </div>
    </div>

    <!-- User menu -->
    <div class="header-user-wrap" v-click-outside="closeUserMenu">
      <button
        type="button"
        class="header-user"
        :class="{ 'is-active': userMenuOpen }"
        @click="userMenuOpen = !userMenuOpen"
      >
        <div class="header-avatar">{{ initials }}</div>
        <span class="header-user-name">{{ displayName }}</span>
        <AegisIcon name="chevron-down" :size="13" />
      </button>

      <div v-if="userMenuOpen" class="header-user-dropdown">
        <div class="header-user-info">
          <div class="header-user-info-name">{{ displayName }}</div>
          <div class="header-user-info-role">{{ roleLabel }}</div>
        </div>
        <a :href="profileHref" class="dropdown-item">
          <AegisIcon name="user" :size="14" />
          <span>My Profile</span>
        </a>
        <a :href="settingsHref" class="dropdown-item">
          <AegisIcon name="settings" :size="14" />
          <span>Settings</span>
        </a>
        <div class="dropdown-divider"></div>
        <button type="button" class="dropdown-item dropdown-item--danger" @click="logout">
          <AegisIcon name="log-out" :size="14" />
          <span>Sign out</span>
        </button>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { useNotificationStore } from '@/stores/notifications'
import { useActivity } from '@/composables/useActivity'

const auth          = useAuthStore()
const ui            = useUiStore()
const notifications = useNotificationStore()
const activity      = useActivity()

const bellOpen      = ref(false)
const userMenuOpen  = ref(false)
const searchQuery   = ref('')

const displayName = computed(() => auth.user?.display_name ?? auth.user?.name ?? '')
const initials = computed(() => {
  const name = displayName.value || ''
  return name.split(/\s+/).map((n) => n[0]).slice(0, 2).join('').toUpperCase() || '·'
})

const ROLE_LABELS = {
  provider:           'Practitioner',
  continuity_steward: 'Continuity Steward',
  support_steward:    'Support Steward',
  business_partner:   'Business Partner',
  admin:              'Administrator',
}
const roleLabel = computed(() => ROLE_LABELS[auth.portal] ?? '')

const portalPrefix = computed(() => ({
  provider:           'provider',
  continuity_steward: 'cs',
  support_steward:    'ss',
  business_partner:   'bp',
  admin:              'admin',
})[auth.portal] ?? 'provider')

const profileHref  = computed(() => r(`${portalPrefix.value}.profile`))
const settingsHref = computed(() => r(`${portalPrefix.value}.settings`))
const activityHref = computed(() => r(`${portalPrefix.value}.activity`))

function r(name) {
  try { return route(name) } catch { return '#' }
}

function iconFor(event) {
  if (event.severity === 'critical') return 'alert-triangle'
  if (event.module) return activity.iconForEventType(event.module)
  return 'dot'
}

function onSearch() {
  if (!searchQuery.value.trim()) return
  // Routed to portal search endpoint; controller handles dispatch.
  try {
    router.visit(route(`${portalPrefix.value}.search`, { q: searchQuery.value.trim() }))
  } catch {
    // No search route in this portal — silently ignore.
  }
}

function logout() {
  router.post(route('logout'))
}

function closeBell()     { bellOpen.value = false }
function closeUserMenu() { userMenuOpen.value = false }

// Minimal click-outside directive (registered inline for this component only).
const vClickOutside = {
  mounted(el, binding) {
    el._clickOutside = (event) => {
      if (!el.contains(event.target)) binding.value(event)
    }
    document.addEventListener('click', el._clickOutside)
  },
  unmounted(el) {
    document.removeEventListener('click', el._clickOutside)
  },
}
</script>
