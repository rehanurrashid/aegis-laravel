<template>
  <header class="topbar" role="banner">
    <!-- Left: mobile hamburger + date eyebrow + page title -->
    <div class="topbar-left">

      <!-- Mobile sidebar toggle — only visible ≤768px -->
      <button
        class="topbar-hamburger"
        @click="emit('toggleMobileSidebar')"
        aria-label="Toggle navigation menu"
        aria-haspopup="true"
      >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="5" width="18" height="14" rx="2" ry="2"></rect>
          <line x1="9" y1="10" x2="9" y2="14"></line>
          <line x1="15" y1="10" x2="15" y2="14"></line>
        </svg>
      </button>

      <div class="topbar-text-wrapper">
        <div class="topbar-breadcrumb">{{ formattedDate }}</div>
        <div class="topbar-title">{{ pageTitle }}</div>
      </div>
    </div>

    <!-- Right: icon actions + profile -->
    <div class="topbar-right">
      <!-- Emergency CTA -->
      <a v-if="hasEmergency" :href="criticalIncidentHref" class="btn btn-emergency btn-sm" style="gap:6px;" aria-label="Active Critical Incident — view details">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        Active Critical Incident
      </a>

      <!-- Messages dropdown -->
      <div class="ep-drop-wrap" ref="msgWrapRef">
        <button class="topbar-icon-btn" @click.stop="toggleDrop('msg')" :aria-label="'Messages' + (unreadMessages > 0 ? ', ' + unreadMessages + ' unread' : '')" aria-haspopup="true" :aria-expanded="activeDrop === 'msg' ? 'true' : 'false'">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          <span v-if="unreadMessages > 0" class="notif-dot" aria-hidden="true"></span>
        </button>

        <div class="ep-drop-panel msg-drop" :class="{ open: activeDrop === 'msg' }" role="menu" aria-label="Messages">
          <div class="ep-drop-head">
            <div>
              <div class="ep-drop-head-title">Messages</div>
              <div class="ep-drop-head-sub">{{ unreadMessages > 0 ? unreadMessages + ' unread' : 'No unread messages' }}</div>
            </div>
            <a class="ep-drop-head-action" :href="messagesHref + '?compose=1'" title="New message">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </a>
          </div>
          <div class="ep-drop-scroll">
            <div v-if="!threads.length" class="notif-empty">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
              <div class="notif-empty-title">No conversations yet</div>
              <div class="notif-empty-sub">Start a thread to see it here.</div>
            </div>
            <template v-for="th in threads" :key="th.id">
              <a class="msg-row" :class="{ 'is-unread': th.unread }" :href="messagesHref + '?thread=' + th.id" @click="closeAllDrops">
                <div class="msg-avatar">{{ th.initials }}</div>
                <div class="msg-body">
                  <div class="msg-head-row">
                    <div class="msg-name">{{ th.name }}</div>
                    <div class="msg-time">{{ th.time }}</div>
                  </div>
                  <div class="msg-preview">{{ th.preview || 'No messages yet' }}</div>
                </div>
                <span v-if="th.unread" class="notif-unread-bullet" aria-hidden="true"></span>
              </a>
            </template>
          </div>
          <div class="ep-drop-foot">
            <a class="ep-drop-foot-btn" :href="messagesHref" @click="closeAllDrops">
              View all messages
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
          </div>
        </div>
      </div>

      <!-- Notifications dropdown -->
      <div class="ep-drop-wrap" ref="notifWrapRef">
        <button class="topbar-icon-btn" @click.stop="toggleDrop('notif')" :aria-label="'Notifications' + (unreadNotifs > 0 ? ', ' + unreadNotifs + ' unread' : '')" aria-haspopup="true" :aria-expanded="activeDrop === 'notif' ? 'true' : 'false'">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <span v-if="unreadNotifs > 0 || hasEmergency" class="notif-dot" :class="{ emergency: hasEmergency }" aria-hidden="true"></span>
        </button>

        <div class="ep-drop-panel notif-drop" :class="{ open: activeDrop === 'notif' }" role="menu" aria-label="Notifications">
          <div class="ep-drop-head">
            <div>
              <div class="ep-drop-head-title">Notifications</div>
              <div class="ep-drop-head-sub">{{ unreadNotifs > 0 ? unreadNotifs + ' unread' : 'All caught up' }}</div>
            </div>
            <button v-if="unreadNotifs > 0" class="ep-drop-head-action" title="Mark all as read">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </button>
          </div>
          <div class="ep-drop-scroll">
            <a v-if="hasEmergency" class="notif-row notif-row-emergency" :href="criticalIncidentHref" @click="closeAllDrops">
              <span class="notif-dot-mark emergency" aria-hidden="true"></span>
              <div class="notif-body">
                <div class="notif-title">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                  Active Critical Incident
                </div>
                <div class="notif-desc">View details &amp; task checklist</div>
                <div class="notif-time">recently</div>
              </div>
            </a>
            <div v-if="!notifications.length" class="notif-empty">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
              <div class="notif-empty-title">No notifications yet</div>
              <div class="notif-empty-sub">When something happens, you'll see it here.</div>
            </div>
            <template v-for="n in notifications" :key="n.id">
              <a class="notif-row" :class="{ 'is-unread': !n.read }" href="#" @click.prevent="closeAllDrops">
                <span class="notif-dot-mark" :class="n.severity || 'gray'" aria-hidden="true"></span>
                <div class="notif-body">
                  <div class="notif-title">{{ n.title }}</div>
                  <div v-if="n.description" class="notif-desc">{{ n.description }}</div>
                  <div class="notif-meta">
                    <span v-if="n.module" class="notif-chip">{{ n.module }}</span>
                    <span class="notif-time">{{ n.time }}</span>
                  </div>
                </div>
                <span v-if="!n.read" class="notif-unread-bullet" aria-hidden="true"></span>
              </a>
            </template>
          </div>
          <div class="ep-drop-foot">
            <a class="ep-drop-foot-btn" :href="activityHref" @click="closeAllDrops">
              View all activity
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
          </div>
        </div>
      </div>

      <!-- Profile dropdown -->
      <div class="ep-profile-wrap" ref="profileWrapRef">
        <button class="ep-profile-btn" :class="{ open: activeDrop === 'profile' }" @click.stop="toggleDrop('profile')" aria-label="Your profile" aria-haspopup="true" :aria-expanded="activeDrop === 'profile' ? 'true' : 'false'">
          <div class="ep-profile-avatar" aria-hidden="true">{{ initials }}</div>
          <div class="ep-profile-info">
            <div class="ep-profile-name">{{ cleanName }}</div>
            <div class="ep-profile-role">
              <span class="ep-role-dot" :class="{ emergency: hasEmergency }" aria-hidden="true"></span>
              {{ roleShort }}
            </div>
          </div>
          <span class="ep-profile-caret" aria-hidden="true">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
          </span>
        </button>

        <div class="ep-profile-drop" :class="{ open: activeDrop === 'profile' }" role="menu" aria-label="Profile menu">
          <!-- Quick links -->
          <div class="epd-section">
            <a class="epd-item" :href="profileHref" @click="closeAllDrops" role="menuitem">
              <span class="epd-item-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
              <span class="epd-item-label">My Profile</span>
            </a>
            <a v-if="portal !== 'practitioner'" class="epd-item" :href="assignmentsHref" @click="closeAllDrops" role="menuitem">
              <span class="epd-item-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M9 14l2 2 4-4"/></svg></span>
              <span class="epd-item-label">{{ portal === 'business_partner' ? 'Contracts' : 'My Tasks' }}</span>
            </a>
            <a class="epd-item" :href="paymentsHref" @click="closeAllDrops" role="menuitem">
              <span class="epd-item-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></span>
              <span class="epd-item-label">Payments</span>
            </a>
            <a class="epd-item" :href="settingsHref" @click="closeAllDrops" role="menuitem">
              <span class="epd-item-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>
              <span class="epd-item-label">Settings</span>
            </a>
          </div>

          <!-- Portal switcher (BP users skip) -->
          <div v-if="portal !== 'business_partner'" class="epd-section">
            <div class="epd-section-label">Switch Portal</div>
            <template v-for="entry in portalSwitchEntries" :key="entry.key">
              <button v-if="entry.current" class="epd-item epd-item-current" @click="closeAllDrops" title="Currently active" role="menuitem">
                <span class="epd-item-icon" v-html="getSwitchIcon(entry.key)"></span>
                <span class="epd-item-label">{{ entry.label }}</span>
                <span class="epd-current-badge">Current</span>
              </button>
              <a v-else class="epd-item" :href="entry.href" @click="closeAllDrops" :title="'Open ' + entry.label + ' Portal'" role="menuitem">
                <span class="epd-item-icon" v-html="getSwitchIcon(entry.key)"></span>
                <span class="epd-item-label">{{ entry.label }}</span>
                <span class="epd-item-arrow"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
              </a>
            </template>
          </div>

          <!-- Sign out -->
          <div class="epd-section">
            <button class="epd-item epd-item-danger" @click="logout" role="menuitem">
              <span class="epd-item-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span>
              <span class="epd-item-label">Sign Out</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  user: Object,
  pageTitle: { type: String, default: 'Dashboard' },
  portal: { type: String, default: 'practitioner' },
  hasEmergency: { type: Boolean, default: false },
  unreadMessages: { type: Number, default: 0 },
  unreadNotifs: { type: Number, default: 0 },
  threads: { type: Array, default: () => [] },
  notifications: { type: Array, default: () => [] },
});

const emit = defineEmits(['toggleMobileSidebar']);

const activeDrop = ref(null);
const msgWrapRef = ref(null);
const notifWrapRef = ref(null);
const profileWrapRef = ref(null);

const formattedDate = computed(() => {
  return new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
});

const cleanName = computed(() => {
  const name = props.user?.display_name || props.user?.name || 'User';
  return name.replace(/^(Dr|Mr|Mrs|Ms|Prof|Rev|Sr|Jr)\.?\s+/i, '').trim() || 'User';
});

const initials = computed(() => {
  const parts = cleanName.value.split(/\s+/).filter(Boolean);
  if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
  return 'U';
});

const roleShort = computed(() => {
  if (props.hasEmergency && (props.portal === 'continuity_steward' || props.portal === 'support_steward')) {
    return 'Active Critical Incident';
  }
  const map = {
    practitioner: 'Practitioner',
    continuity_steward: 'Continuity Steward',
    support_steward: 'Support Steward',
    business_partner: 'Agency Account',
  };
  return map[props.portal] || 'User';
});

/* ── Portal-aware URL helpers ── */
const portalBase = computed(() => {
  const bases = { practitioner: '/provider/', continuity_steward: '/continuity-steward/', support_steward: '/support-steward/', business_partner: '/business/' };
  return bases[props.portal] || '/';
});

const messagesHref = computed(() => portalBase.value + 'messages');
const activityHref = computed(() => portalBase.value + 'activity');
const profileHref = computed(() => portalBase.value + 'profile');
const settingsHref = computed(() => portalBase.value + 'settings');
const paymentsHref = computed(() => portalBase.value + 'finances');
const assignmentsHref = computed(() => {
  if (props.portal === 'business_partner') return portalBase.value + 'contracts';
  return portalBase.value + 'my-tasks';
});
const criticalIncidentHref = computed(() => {
  if (props.portal === 'support_steward') return portalBase.value + 'critical-incident-log';
  if (props.portal === 'continuity_steward') return portalBase.value + 'continuity-management';
  return portalBase.value + 'dashboard';
});

/* ── Portal switcher entries ── */
const portalSwitchEntries = computed(() => {
  const all = [
    { key: 'practitioner', label: 'Practitioner', href: '/provider/dashboard', alwaysShow: true },
    { key: 'continuity_steward', label: 'Continuity Steward', href: '/continuity-steward/dashboard', alwaysShow: false },
    { key: 'support_steward', label: 'Support Steward', href: '/support-steward/dashboard', alwaysShow: false },
  ];
  const current = props.portal;
  const ordered = [];
  const currentEntry = all.find(e => e.key === current);
  if (currentEntry) ordered.push({ ...currentEntry, current: true });
  for (const e of all) {
    if (e.key === current) continue;
    if (e.alwaysShow) ordered.push({ ...e, current: false });
  }
  return ordered;
});

function getSwitchIcon(key) {
  const s = 'width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"';
  const map = {
    practitioner: `<svg ${s}><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`,
    continuity_steward: `<svg ${s}><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>`,
    support_steward: `<svg ${s}><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`,
  };
  return map[key] || '';
}

/* ── Dropdown controller ── */
function toggleDrop(name) {
  activeDrop.value = activeDrop.value === name ? null : name;
}

function closeAllDrops() {
  activeDrop.value = null;
}

function logout() {
  closeAllDrops();
  router.post('/logout');
}

function handleOutsideClick(e) {
  if (!activeDrop.value) return;
  const wraps = [msgWrapRef.value, notifWrapRef.value, profileWrapRef.value].filter(Boolean);
  for (const w of wraps) {
    if (w.contains(e.target)) return;
  }
  closeAllDrops();
}

function handleEscape(e) {
  if (e.key === 'Escape') closeAllDrops();
}

onMounted(() => {
  document.addEventListener('click', handleOutsideClick);
  document.addEventListener('keydown', handleEscape);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleOutsideClick);
  document.removeEventListener('keydown', handleEscape);
});
</script>

<style scoped>
/* ── Topbar ── */
.topbar {
  height: var(--topbar-height, 64px);
  background: var(--bg, #f4f1ea);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 32px;
  position: sticky;
  top: 0;
  z-index: 100;
  gap: 20px;
  border-bottom: 1px solid transparent;
  transition: border-color .18s ease, background .18s ease;
}
.topbar-left {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 12px;
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-align: left;
}

.topbar-text-wrapper {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  justify-content: center;
  gap: 2px;
  min-width: 0;
  overflow: hidden;
}

.topbar-title {
  font-family: var(--font-serif, 'Spectral', Georgia, serif);
  font-size: 18px;
  font-weight: 700;
  color: var(--text, #1e1c1a);
  line-height: 1.1;
  letter-spacing: 0.2px;
  white-space: nowrap;
}
.topbar-breadcrumb {
  font-family: var(--font-sans, 'Inter', Arial, sans-serif);
  font-size: 11px;
  color: var(--text-4, #a89f94);
  letter-spacing: 0.4px;
  white-space: nowrap;
}
.topbar-right {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

/* Icon buttons */
.topbar-icon-btn {
  width: 38px; height: 38px;
  border-radius: var(--radius-full);
  border: 1px solid var(--border, #e4dfd7);
  background: var(--surface, #ffffff);
  color: var(--text-3, #6b6660);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  transition: background .18s ease, border-color .18s ease, color .18s ease;
  position: relative;
  text-decoration: none;
  flex-shrink: 0;
}
.topbar-icon-btn:hover {
  background: var(--surface-3);
  border-color: rgba(196,169,106,0.4);
  color: var(--gold-dark);
}
.topbar-icon-btn:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(196,169,106,0.30);
}

/* Notification dot */
.notif-dot {
  position: absolute;
  top: 8px; right: 9px;
  width: 7px; height: 7px;
  background: var(--red, #e05c5c);
  border-radius: var(--radius-full);
  border: 1px solid var(--surface, #ffffff);
}
.notif-dot.emergency {
  background: var(--emergency, #dc2626);
  animation: dot-pulse 1.5s ease infinite;
}

/* ── Profile Pill ── */
.ep-profile-wrap { position: relative; margin-left: 4px; }
.ep-profile-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 5px 14px 5px 5px;
  border-radius: var(--radius-full);
  border: 1px solid var(--border, #e4dfd7);
  cursor: pointer;
  transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
  background: var(--surface, #ffffff);
  font-family: var(--font-sans, 'Inter', Arial, sans-serif);
}
.ep-profile-btn:hover {
  border-color: var(--gold);
  box-shadow: 0 2px 8px rgba(30,28,26,0.06);
}
.ep-profile-btn:active { transform: translateY(0.5px); }
.ep-profile-btn.open {
  border-color: var(--gold);
  box-shadow: 0 2px 8px rgba(160,129,62,0.18);
}
.ep-profile-btn:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(196,169,106,0.30);
}

.ep-profile-avatar {
  width: 30px; height: 30px;
  border-radius: var(--radius-full);
  background: linear-gradient(140deg, var(--gold), var(--gold-dark));
  color: var(--text-inverted, #ffffff);
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-serif, 'Spectral', Georgia, serif);
  font-weight: 700;
  font-size: 12px;
  flex-shrink: 0;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.25);
}
.ep-profile-name {
  font-family: var(--font-sans, 'Inter', Arial, sans-serif);
  font-size: 13px;
  font-weight: 600;
  color: var(--text, #1e1c1a);
  white-space: nowrap;
  line-height: 1.2;
}
.ep-profile-role {
  font-family: var(--font-sans, 'Inter', Arial, sans-serif);
  font-size: 11px;
  color: var(--text-3, #22201d);
  white-space: nowrap;
  display: flex;
  align-items: center;
  gap: 4px;
}
.ep-role-dot {
  display: inline-block;
  width: 6px; height: 6px;
  border-radius: 50%;
  background: var(--green, #4caf7d);
  flex-shrink: 0;
}
.ep-role-dot.emergency { background: var(--emergency, #dc2626); animation: dot-pulse 1.5s ease infinite; }
.ep-profile-caret {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: var(--text-4, #a89f94);
  transition: transform var(--transition, 0.18s ease), color .18s ease;
  flex-shrink: 0;
}
.ep-profile-btn:hover .ep-profile-caret { color: var(--text-2); }
.ep-profile-btn.open .ep-profile-caret { transform: rotate(180deg); color: var(--gold-dark); }

/* ── Profile Dropdown ── */
.ep-profile-drop {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 280px;
  background: var(--surface, #ffffff);
  border-radius: var(--radius, 12px);
  box-shadow: 0 16px 48px rgba(30,28,26,0.14), 0 4px 12px rgba(30,28,26,0.06), 0 1px 0 rgba(255,255,255,0.6) inset;
  z-index: 9999;
  display: none;
  overflow: hidden;
  padding: 8px;
  transform-origin: top right;
}
.ep-profile-drop.open {
  display: block;
  animation: epd-drop-in 0.32s cubic-bezier(0.16, 1.05, 0.32, 1) both;
}

@keyframes epd-drop-in {
  0%   { opacity: 0; transform: translateY(-10px) scale(0.94); }
  60%  { opacity: 1; }
  100% { opacity: 1; transform: translateY(0)     scale(1); }
}

/* Dropdown panels */
.ep-drop-wrap { position: relative; }
.ep-drop-panel {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  background: var(--surface, #ffffff);
  border-radius: var(--radius, 12px);
  box-shadow: 0 16px 48px rgba(30,28,26,0.14), 0 4px 12px rgba(30,28,26,0.06), 0 1px 0 rgba(255,255,255,0.6) inset;
  z-index: 9999;
  overflow: hidden;
  transform-origin: top right;
  max-height: 540px;
  display: none;
  flex-direction: column;
}
.ep-drop-panel.open {
  display: flex;
  animation: epd-drop-in 0.32s cubic-bezier(0.16, 1.05, 0.32, 1) both;
}
.notif-drop { width: 380px; }
.msg-drop   { width: 360px; }

/* Head */
.ep-drop-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 14px 16px 10px;
  border-bottom: 1px solid rgba(30,28,26,0.06);
}
.ep-drop-head-title {
  font-family: var(--font-serif, 'Spectral', Georgia, serif);
  font-size: 15px;
  font-weight: 700;
  color: var(--text, #1e1c1a);
  line-height: 1.2;
}
.ep-drop-head-sub {
  font-family: var(--font-sans, 'Inter', Arial, sans-serif);
  font-size: 11px;
  font-weight: 600;
  color: var(--text-3, #6b6660);
  margin-top: 2px;
  letter-spacing: 0.2px;
}
.ep-drop-head-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px; height: 28px;
  background: var(--surface-2, #faf7f1);
  border: 1px solid var(--border, #e4dfd7);
  border-radius: var(--radius-sm, 8px);
  color: var(--text-3, #6b6660);
  cursor: pointer;
  text-decoration: none;
  transition: background 0.18s ease, color 0.18s ease, border-color 0.18s ease;
}
.ep-drop-head-action:hover {
  background: rgba(192,154,82,0.10);
  border-color: rgba(160,129,62,0.5);
  color: var(--gold-dark, #a0813e);
}

/* Scroll region */
.ep-drop-scroll {
  overflow-y: auto;
  flex: 1 1 auto;
  min-height: 100px;
  max-height: 420px;
  padding: 4px 0;
}
.ep-drop-scroll::-webkit-scrollbar { width: 6px; }
.ep-drop-scroll::-webkit-scrollbar-thumb { background: rgba(30,28,26,0.16); border-radius: 3px; }

/* Footer */
.ep-drop-foot { border-top: 1px solid rgba(30,28,26,0.06); padding: 8px; }
.ep-drop-foot-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  width: 100%;
  padding: 9px 12px;
  background: transparent;
  border: 0;
  border-radius: var(--radius-sm, 8px);
  color: var(--gold-dark, #a0813e);
  font-family: var(--font-sans, 'Inter', Arial, sans-serif);
  font-size: 12px;
  font-weight: 700;
  text-decoration: none;
  cursor: pointer;
  transition: background 0.18s ease;
}
.ep-drop-foot-btn:hover { background: rgba(192,154,82,0.10); }

/* Notification row */
.notif-row {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 10px 16px;
  text-decoration: none;
  color: inherit;
  position: relative;
  transition: background 0.14s ease;
  border-bottom: 1px solid rgba(30,28,26,0.04);
}
.notif-row:last-child { border-bottom: 0; }
.notif-row:hover { background: var(--surface-2, #faf7f1); }
.notif-row.is-unread { background: rgba(192,154,82,0.04); }
.notif-row.is-unread:hover { background: rgba(192,154,82,0.10); }
.notif-row-emergency { background: var(--emergency-light, #fdf0f0); border-bottom: 1px solid var(--emergency, #a02d22); }
.notif-row-emergency:hover { background: rgba(160,45,34,0.10); }

.notif-dot-mark { width: 8px; height: 8px; border-radius: 50%; margin-top: 6px; flex-shrink: 0; }
.notif-dot-mark.red    { background: var(--red, #a02d22); }
.notif-dot-mark.orange { background: var(--orange, #e8a94a); }
.notif-dot-mark.green  { background: var(--green, #4caf7d); }
.notif-dot-mark.blue   { background: var(--blue, #4a90c4); }
.notif-dot-mark.gray   { background: var(--text-4, #a89f94); }
.notif-dot-mark.emergency { background: var(--emergency, #a02d22); animation: notif-pulse 1.5s ease infinite; }

@keyframes notif-pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(160,45,34,0.5); }
  50%      { box-shadow: 0 0 0 6px rgba(160,45,34,0); }
}

.notif-body { flex: 1; min-width: 0; }
.notif-title {
  font-family: var(--font-sans, 'Inter', Arial, sans-serif);
  font-size: 13px; font-weight: 700; color: var(--text, #1e1c1a);
  line-height: 1.35; display: flex; align-items: center; gap: 4px;
}
.notif-desc {
  font-family: var(--font-sans, 'Inter', Arial, sans-serif);
  font-size: 12px; font-weight: 500; color: var(--text-3, #6b6660);
  line-height: 1.45; margin-top: 2px;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.notif-meta { display: flex; align-items: center; gap: 6px; margin-top: 5px; }
.notif-chip {
  font-family: var(--font-sans, 'Inter', Arial, sans-serif);
  font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
  color: var(--text-3, #6b6660); background: var(--surface-3, #f0ece2);
  padding: 2px 6px; border-radius: var(--radius-full, 9999px);
}
.notif-time { font-family: var(--font-sans, 'Inter', Arial, sans-serif); font-size: 11px; font-weight: 600; color: var(--text-4, #a89f94); }
.notif-unread-bullet { width: 7px; height: 7px; border-radius: 50%; background: var(--gold-dark, #a0813e); flex-shrink: 0; margin-top: 7px; }

.notif-empty {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 36px 20px; text-align: center; color: var(--text-4, #a89f94);
}
.notif-empty-title { font-family: var(--font-sans); font-size: 13px; font-weight: 700; color: var(--text-3, #6b6660); }
.notif-empty-sub { font-family: var(--font-sans); font-size: 12px; color: var(--text-4, #a89f94); margin-top: 3px; }

/* Message row */
.msg-row {
  display: flex; align-items: center; gap: 10px; padding: 10px 16px;
  text-decoration: none; color: inherit; position: relative;
  transition: background 0.14s ease; border-bottom: 1px solid rgba(30,28,26,0.04);
}
.msg-row:last-child { border-bottom: 0; }
.msg-row:hover { background: var(--surface-2, #faf7f1); }
.msg-row.is-unread { background: rgba(192,154,82,0.04); }
.msg-row.is-unread:hover { background: rgba(192,154,82,0.10); }

.msg-avatar {
  width: 36px; height: 36px; border-radius: 50%;
  background: var(--gold-dark, #a0813e); color: #ffffff;
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-serif, 'Spectral', Georgia, serif);
  font-size: 13px; font-weight: 700; flex-shrink: 0; letter-spacing: 0.3px;
}
.msg-body { flex: 1; min-width: 0; }
.msg-head-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.msg-name {
  font-family: var(--font-sans, 'Inter', Arial, sans-serif); font-size: 13px; font-weight: 700;
  color: var(--text, #1e1c1a); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0;
}
.msg-time { font-family: var(--font-sans, 'Inter', Arial, sans-serif); font-size: 11px; font-weight: 600; color: var(--text-4, #a89f94); flex-shrink: 0; }
.msg-preview {
  font-family: var(--font-sans, 'Inter', Arial, sans-serif); font-size: 12px; color: var(--text-3, #6b6660);
  margin-top: 2px; line-height: 1.4; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.msg-row.is-unread .msg-preview { color: var(--text-2, #3d3a36); font-weight: 600; }

/* Mobile */
@media (max-width: 480px) {
  .notif-drop, .msg-drop { width: calc(100vw - 24px); right: -8px; }
}

/* Section grouping */
.epd-section { display: flex; flex-direction: column; gap: 1px; padding: 4px 0; }
.epd-section + .epd-section { margin-top: 4px; position: relative; }
.epd-section + .epd-section::before {
  content: ''; position: absolute; top: -2px; left: 8px; right: 8px; height: 1px;
  background: linear-gradient(to right, transparent, rgba(30,28,26,0.06), transparent);
}
.epd-section-label {
  font-family: var(--font-sans, 'Inter', Arial, sans-serif); font-size: 10px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 1.2px; color: var(--text-4, #a89f94); padding: 8px 12px 6px;
}

.epd-item {
  display: flex; align-items: center; gap: 11px; padding: 7px 10px;
  cursor: pointer; text-decoration: none; background: transparent; border: none;
  width: 100%; text-align: left; font-family: var(--font-sans, 'Inter', Arial, sans-serif);
  border-radius: var(--radius-sm, 8px); color: var(--text-2);
  transition: background .18s ease, color .18s ease;
}
.epd-item:hover { background: rgba(196,169,106,0.08); color: var(--text); }
.epd-item-icon {
  display: inline-flex; align-items: center; justify-content: center;
  width: 16px; height: 16px; flex-shrink: 0; color: var(--text-4, #a89f94);
  background: transparent; border-radius: 0; transition: color .18s ease;
}
.epd-item-icon :deep(svg) { width: 16px; height: 16px; }
.epd-item:hover .epd-item-icon { color: var(--gold-dark, #a0813e); }
.epd-item-label { flex: 1; font-size: 13px; font-weight: 400; color: inherit; line-height: 1.3; letter-spacing: 0.1px; }
.epd-item-arrow {
  display: inline-flex; align-items: center; justify-content: center;
  color: var(--text-4, #a89f94); flex-shrink: 0; transition: color .18s ease, transform .18s ease;
}
.epd-item:hover .epd-item-arrow { color: var(--gold-dark, #a0813e); transform: translateX(2px); }

.epd-item-current { cursor: default; background: rgba(196,169,106,0.08); color: var(--text); font-weight: 600; }
.epd-item-current:hover { background: rgba(196,169,106,0.08); }
.epd-item-current .epd-item-icon { color: var(--gold-dark, #a0813e); }
.epd-current-badge {
  font-family: var(--font-sans, 'Inter', Arial, sans-serif); font-size: 9px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.8px; background: var(--gold-dark, #a0813e);
  color: #ffffff; padding: 2px 7px; border-radius: var(--radius-sm, 8px); flex-shrink: 0;
}

.epd-item-danger .epd-item-label { color: var(--red, #e05c5c); }
.epd-item-danger .epd-item-icon  { color: var(--red, #e05c5c); }
.epd-item-danger:hover { background: var(--red-light, #fdf0f0); }
.epd-item-danger:hover .epd-item-icon  { color: var(--red-dark, #c04848); }
.epd-item-danger:hover .epd-item-label { color: var(--red-dark, #c04848); }

/* ── Mobile hamburger toggle ── */
.topbar-hamburger {
  display: none; /* hidden on desktop */
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: 1px solid var(--border, #e4dfd7);
  background: var(--surface, #ffffff);
  color: var(--text-3, #6b6660);
  cursor: pointer;
  flex-shrink: 0;
  transition: background .18s ease, border-color .18s ease, color .18s ease;
}
.topbar-hamburger:hover {
  background: var(--surface-3);
  border-color: rgba(196,169,106,0.4);
  color: var(--gold-dark);
}
.topbar-hamburger:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(196,169,106,0.30);
}

/* ── Responsive ── */
@media (max-width: 1024px) {
  .topbar { padding: 0 24px; }
}

@media (max-width: 768px) {
  .topbar { padding: 0 16px; }
  .topbar-title { font-size: 17px; }
  .topbar-breadcrumb { font-size: 10px; }
  .ep-profile-info { display: none; }
  .ep-profile-caret { display: none; }
  .ep-profile-drop { right: -8px; width: 264px; }
  .ep-profile-btn { padding: 4px 6px; }
  /* show the hamburger on mobile */
  .topbar-hamburger { display: inline-flex; }
}

@media (max-width: 600px) {
  .topbar { padding: 0 14px; }
  .topbar-title { font-size: 16px; }
  .topbar-right { gap: 8px; }
  .topbar-icon-btn { width: 36px; height: 36px; }
}

@media (max-width: 480px) {
  .topbar { padding: 0 12px; }
  .topbar-title { font-size: 15px; }
  .topbar-breadcrumb { font-size: 9px; }
  .ep-profile-btn { padding: 3px 5px; }
  .ep-profile-avatar { width: 28px; height: 28px; font-size: 11px; }
}

@media (max-width: 425px) {
  .topbar { padding: 0 10px; gap: 8px; }

  /* left side: let it shrink and clip long titles */
  .topbar-left { flex: 1; min-width: 0; overflow: hidden; gap: 8px; }
  .topbar-text-wrapper { min-width: 0; overflow: hidden; }
  .topbar-title { font-size: 14px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .topbar-breadcrumb { display: none; }

  /* right side: shrink icon buttons and tighten gap */
  .topbar-right { gap: 4px; flex-shrink: 0; }
  .topbar-icon-btn { width: 32px; height: 32px; }
  .ep-profile-btn { padding: 3px 4px; }
  .ep-profile-avatar { width: 26px; height: 26px; font-size: 10px; }

  /* hamburger */
  .topbar-hamburger { width: 32px; height: 32px; flex-shrink: 0; }
}

@media (max-width: 320px) {
  .topbar { padding: 0 8px; gap: 4px; }

  /* hamburger — smallest it can go */
  .topbar-hamburger { width: 28px; height: 28px; flex-shrink: 0; border-radius: 6px; }
  .topbar-hamburger svg { width: 16px; height: 16px; }

  /* left: title clips with ellipsis, no breadcrumb */
  .topbar-left { gap: 6px; }
  .topbar-title { font-size: 13px; }
  .topbar-breadcrumb { display: none; }

  /* right: tightest possible, no extra border on profile */
  .topbar-right { gap: 3px; flex-shrink: 0; }
  .topbar-icon-btn { width: 28px; height: 28px; border: none; background: transparent; }
  .topbar-icon-btn svg { width: 16px; height: 16px; }

  /* profile button — avatar only, no border, no padding */
  .ep-profile-wrap { margin-left: 0; }
  .ep-profile-btn { padding: 0; border: none; background: transparent; box-shadow: none; gap: 0; }
  .ep-profile-avatar { width: 26px; height: 26px; font-size: 10px; }
  .ep-profile-info { display: none; }
  .ep-profile-caret { display: none; }
}

@keyframes dot-pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(220,38,38,0.4); }
  50%      { box-shadow: 0 0 0 4px rgba(220,38,38,0); }
}
</style>