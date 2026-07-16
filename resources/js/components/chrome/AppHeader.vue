<!--
  AppHeader.vue — Aegis topbar.
  Props wired from AppLayout via usePage() shared props.
  No demo-switcher. No raw <svg>. All icons via AegisIcon.
  All tooltips via data-tooltip. All links via route().
-->
<template>
  <header class="topbar" role="banner">

    <!-- Left: mobile hamburger + date + page title -->
    <div class="topbar-left">
      <button class="topbar-hamburger" @click="emit('toggleMobileSidebar')" aria-label="Toggle navigation menu" aria-haspopup="true">
        <AegisIcon name="panel-left" :size="20" />
      </button>
      <div class="topbar-text-wrapper">
        <div class="topbar-breadcrumb">{{ formattedDate }}</div>
        <div class="topbar-title">{{ pageTitle }}</div>
      </div>
    </div>

    <!-- Right: emergency CTA + messages + notifications + profile -->
    <div class="topbar-right">

      <!-- Emergency CTA — only when incident active -->
      <Link v-if="hasEmergency" :href="criticalIncidentUrl" class="btn btn-danger btn-sm topbar-emergency" aria-label="Active Critical Incident — view details">
        <AegisIcon name="alert-triangle" :size="14" />
        Active Critical Incident
      </Link>

      <!-- Messages dropdown -->
      <div class="ep-drop-wrap" ref="msgWrapRef">
        <button
          class="topbar-icon-btn"
          :data-tooltip="unreadMessages > 0 ? 'Messages · ' + unreadMessages + ' unread' : 'Messages'"
          data-tooltip-pos="bottom"
          @click.stop="toggleDrop('msg')"
          :aria-label="'Messages' + (unreadMessages > 0 ? ', ' + unreadMessages + ' unread' : '')"
          aria-haspopup="true"
          :aria-expanded="activeDrop === 'msg'"
        >
          <AegisIcon name="message" :size="18" />
          <span v-if="unreadMessages > 0" class="notif-dot" aria-hidden="true"></span>
        </button>

        <div class="ep-drop-panel msg-drop" :class="{ open: activeDrop === 'msg' }" role="menu" aria-label="Messages">
          <div class="ep-drop-head">
            <div>
              <div class="ep-drop-head-title">Messages</div>
              <div class="ep-drop-head-sub">{{ unreadMessages > 0 ? unreadMessages + ' unread' : 'No unread messages' }}</div>
            </div>
            <Link class="ep-drop-head-action" :href="messagesUrl + '?compose=1'" data-tooltip="New message" @click="closeAllDrops">
              <AegisIcon name="plus" :size="14" />
            </Link>
          </div>
          <div class="ep-drop-scroll">
            <div v-if="!threads.length" class="notif-empty">
              <AegisIcon name="message" :size="22" />
              <div class="notif-empty-title">No conversations yet</div>
              <div class="notif-empty-sub">Start a thread to see it here.</div>
            </div>
            <Link
              v-for="th in threads"
              :key="th.id"
              class="msg-row"
              :class="{ 'is-unread': th.unread }"
              :href="messagesUrl + '?thread=' + th.id"
              @click="closeAllDrops"
            >
              <div class="msg-avatar">{{ th.initials }}</div>
              <div class="msg-body">
                <div class="msg-head-row">
                  <div class="msg-name">{{ th.name }}</div>
                  <div class="msg-time">{{ th.time }}</div>
                </div>
                <div class="msg-preview">
                  <span v-if="th.own" class="msg-you">You:</span>
                  {{ th.preview || 'No messages yet' }}
                </div>
              </div>
              <span v-if="th.unread" class="notif-unread-bullet" aria-hidden="true"></span>
            </Link>
          </div>
          <div class="ep-drop-foot">
            <Link class="ep-drop-foot-btn" :href="messagesUrl" @click="closeAllDrops">
              View all messages <AegisIcon name="chevron-right" :size="12" />
            </Link>
          </div>
        </div>
      </div>

      <!-- Notifications dropdown -->
      <div class="ep-drop-wrap" ref="notifWrapRef">
        <button
          class="topbar-icon-btn"
          :data-tooltip="unreadNotifs > 0 ? 'Notifications · ' + unreadNotifs + ' new' : 'Notifications'"
          data-tooltip-pos="bottom"
          @click.stop="toggleDrop('notif')"
          :aria-label="'Notifications' + (unreadNotifs > 0 ? ', ' + unreadNotifs + ' unread' : '')"
          aria-haspopup="true"
          :aria-expanded="activeDrop === 'notif'"
        >
          <AegisIcon name="bell" :size="18" />
          <span v-if="unreadNotifs > 0 || hasEmergency" class="notif-dot" :class="{ emergency: hasEmergency }" aria-hidden="true"></span>
        </button>

        <div class="ep-drop-panel notif-drop" :class="{ open: activeDrop === 'notif' }" role="menu" aria-label="Notifications">
          <div class="ep-drop-head">
            <div>
              <div class="ep-drop-head-title">Notifications</div>
              <div class="ep-drop-head-sub">{{ unreadNotifs > 0 ? unreadNotifs + ' unread' : 'All caught up' }}</div>
            </div>
            <button v-if="unreadNotifs > 0" class="ep-drop-head-action" data-tooltip="Mark all as read" @click="markAllRead">
              <AegisIcon name="check" :size="14" />
            </button>
          </div>
          <div class="ep-drop-scroll">
            <!-- Emergency pinned row -->
            <Link v-if="hasEmergency" class="notif-row notif-row-emergency" :href="criticalIncidentUrl" @click="closeAllDrops">
              <span class="notif-dot-mark emergency" aria-hidden="true"></span>
              <div class="notif-body">
                <div class="notif-title">
                  <AegisIcon name="alert-triangle" :size="12" /> Active Critical Incident
                </div>
                <div class="notif-desc">View details &amp; task checklist</div>
                <div class="notif-time">recently</div>
              </div>
            </Link>
            <!-- Empty state -->
            <div v-if="!notifications.length && !hasEmergency" class="notif-empty">
              <AegisIcon name="bell" :size="22" />
              <div class="notif-empty-title">No notifications yet</div>
              <div class="notif-empty-sub">When something happens, you'll see it here.</div>
            </div>
            <!-- Notification rows -->
            <Link
              v-for="n in notifications"
              :key="n.id"
              class="notif-row"
              :class="{ 'is-unread': !n.read_at }"
              :href="n.module ? activityUrl + '?module=' + n.module : activityUrl"
              @click="closeAllDrops"
            >
              <span class="notif-dot-mark" :class="severityColor(n.severity)" aria-hidden="true"></span>
              <div class="notif-body">
                <div class="notif-title">{{ n.title }}</div>
                <div v-if="n.description" class="notif-desc">{{ n.description }}</div>
                <div class="notif-meta">
                  <span v-if="n.module" class="notif-chip">{{ n.module }}</span>
                  <span class="notif-time">{{ timeAgo(n.created_at) }}</span>
                </div>
              </div>
              <span v-if="!n.read_at" class="notif-unread-bullet" aria-hidden="true"></span>
            </Link>
          </div>
          <div class="ep-drop-foot">
            <Link class="ep-drop-foot-btn" :href="activityUrl" @click="closeAllDrops">
              View all activity <AegisIcon name="chevron-right" :size="12" />
            </Link>
          </div>
        </div>
      </div>

      <!-- Profile dropdown -->
      <div class="ep-profile-wrap" ref="profileWrapRef">
        <button
          class="ep-profile-btn"
          :class="{ open: activeDrop === 'profile' }"
          @click.stop="toggleDrop('profile')"
          aria-label="Your profile"
          aria-haspopup="true"
          :aria-expanded="activeDrop === 'profile'"
        >
          <div class="ep-profile-avatar" aria-hidden="true" :style="user?.avatar_url ? { backgroundImage: `url(${user.avatar_url})`, backgroundSize: 'cover', backgroundPosition: 'center' } : {}">
            <template v-if="!user?.avatar_url">{{ initials }}</template>
          </div>
          <div class="ep-profile-info">
            <div class="ep-profile-name">{{ cleanName }}</div>
            <div class="ep-profile-role">
              <span
                class="avail-status-dot avail-status-dot--sm"
                :class="hasEmergency ? 'avail-status-dot--emergency' : `avail-status-dot--${messagingStatus}`"
                aria-hidden="true"
              ></span>
              {{ roleShort }}
            </div>
          </div>
          <span class="ep-profile-caret" aria-hidden="true">
            <AegisIcon name="chevron-down" :size="12" />
          </span>
        </button>

        <div class="ep-profile-drop" :class="{ open: activeDrop === 'profile' }" role="menu" aria-label="Profile menu">

          <!-- Quick links -->
          <div class="epd-section">
            <Link class="epd-item" :href="profileUrl" @click="closeAllDrops" role="menuitem">
              <span class="epd-item-icon"><AegisIcon name="user" :size="16" /></span>
              <span class="epd-item-label">My Profile</span>
            </Link>
            <Link v-if="portal !== 'provider'" class="epd-item" :href="assignmentsUrl" @click="closeAllDrops" role="menuitem">
              <span class="epd-item-icon"><AegisIcon name="clipboard-check" :size="16" /></span>
              <span class="epd-item-label">{{ portal === 'business_partner' ? 'Contracts' : 'My Tasks' }}</span>
            </Link>
            <Link class="epd-item" :href="paymentsUrl" @click="closeAllDrops" role="menuitem">
              <span class="epd-item-icon"><AegisIcon name="credit-card" :size="16" /></span>
              <span class="epd-item-label">Payments</span>
            </Link>
            <Link class="epd-item" :href="settingsUrl" @click="closeAllDrops" role="menuitem">
              <span class="epd-item-icon"><AegisIcon name="settings" :size="16" /></span>
              <span class="epd-item-label">Settings</span>
            </Link>
          </div>

          <!-- Portal switcher (BP users skip) -->
          <div v-if="portal !== 'business_partner'" class="epd-section">
            <div class="epd-section-label">Switch Portal</div>
            <template v-for="entry in portalSwitchEntries" :key="entry.key">
              <button v-if="entry.current" class="epd-item epd-item-current" @click="closeAllDrops" data-tooltip="Currently active" role="menuitem">
                <span class="epd-item-icon"><AegisIcon :name="entry.icon" :size="16" /></span>
                <span class="epd-item-label">{{ entry.label }}</span>
                <span class="epd-current-badge">Current</span>
              </button>
              <Link v-else class="epd-item" :href="entry.href" @click="closeAllDrops" :data-tooltip="'Open ' + entry.label + ' Portal'" role="menuitem">
                <span class="epd-item-icon"><AegisIcon :name="entry.icon" :size="16" /></span>
                <span class="epd-item-label">{{ entry.label }}</span>
                <span class="epd-item-arrow"><AegisIcon name="chevron-right" :size="12" /></span>
              </Link>
            </template>
            <div class="epd-section-note">Same login. Visible only if you hold multiple roles on Aegis.</div>
          </div>

          <!-- Sign out -->
          <div class="epd-section">
            <button class="epd-item epd-item-danger" @click="logout" role="menuitem">
              <span class="epd-item-icon"><AegisIcon name="log-out" :size="16" /></span>
              <span class="epd-item-label">Sign Out</span>
            </button>
          </div>

        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AegisIcon from '@/components/ui/AegisIcon.vue'
import { useUiStore } from '@/stores/ui'

const props = defineProps({
  pageTitle:      { type: String,  default: 'Dashboard' },
  hasEmergency:   { type: Boolean, default: false },
  unreadMessages: { type: Number,  default: 0 },
  unreadNotifs:   { type: Number,  default: 0 },
  threads:        { type: Array,   default: () => [] },
  notifications:  { type: Array,   default: () => [] },
})

const emit = defineEmits(['toggleMobileSidebar'])
const ui   = useUiStore()
const page = usePage()

// ── Auth from shared props ─────────────────────────────────────────────
const user            = computed(() => page.props.auth?.user   ?? null)
const portal          = computed(() => page.props.auth?.portal ?? 'provider')
const messagingStatus = computed(() => page.props.auth?.user?.messaging_status ?? 'available')

// ── Name / initials ────────────────────────────────────────────────────
const cleanName = computed(() => {
  const name = user.value?.display_name ?? 'User'
  return name.replace(/^(Dr|Mr|Mrs|Ms|Prof|Rev|Sr|Jr)\.?\s+/i, '').trim() || 'User'
})

const initials = computed(() => {
  const parts = cleanName.value.split(/\s+/).filter(Boolean)
  if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase()
  return 'U'
})

// ── Role short label ───────────────────────────────────────────────────
const roleShort = computed(() => {
  if (props.hasEmergency && (portal.value === 'continuity_steward' || portal.value === 'support_steward')) {
    return 'Active Critical Incident'
  }
  const map = {
    provider:            'Practitioner',
    continuity_steward:  'Continuity Steward',
    support_steward:     'Support Steward',
    business_partner:    user.value?.bp_type === 'freelancer' ? 'Freelancer Account' : 'Agency Account',
    admin:               'Administrator',
  }
  return map[portal.value] ?? 'User'
})

// ── Date ───────────────────────────────────────────────────────────────
const formattedDate = computed(() =>
  new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
)

// ── Portal-aware route helpers ─────────────────────────────────────────
const portalPrefix = computed(() => {
  const map = {
    provider:           'provider',
    continuity_steward: 'cs',
    support_steward:    'ss',
    business_partner:   'bp',
    admin:              'admin',
  }
  return map[portal.value] ?? 'provider'
})

function r(name) {
  try { return route(portalPrefix.value + '.' + name) } catch { return '#' }
}

const messagesUrl         = computed(() => r('messages'))
const activityUrl         = computed(() => r('activity'))
const profileUrl          = computed(() => r('profile.index'))
const settingsUrl         = computed(() => r('settings.index'))
const paymentsUrl         = computed(() => r('finances.index'))
const assignmentsUrl      = computed(() => portal.value === 'business_partner' ? r('contracts.index') : r('tasks.index'))
const criticalIncidentUrl = computed(() => {
  if (portal.value === 'support_steward')    return r('incident.index')
  if (portal.value === 'continuity_steward') return r('continuity.index')
  return r('dashboard')
})

// ── Portal switcher ────────────────────────────────────────────────────
const portalSwitchEntries = computed(() => {
  const user = page.props.auth?.user ?? {}
  const all = [
    { key: 'provider',           label: 'Practitioner',       icon: 'user',         href: route('provider.dashboard'), always: true },
    { key: 'continuity_steward', label: 'Continuity Steward', icon: 'shield-check',  href: '#', gate: user.has_cs_portal },
    { key: 'support_steward',    label: 'Support Steward',    icon: 'users',         href: '#', gate: user.has_ss_portal },
  ]
  const current = portal.value
  const eligible = all.filter(e => e.always || e.gate)
  const ordered = []
  const cur = eligible.find(e => e.key === current)
  if (cur) ordered.push({ ...cur, current: true })
  for (const e of eligible) {
    if (e.key !== current) ordered.push({ ...e, current: false })
  }
  return ordered
})

// ── Dropdown state ─────────────────────────────────────────────────────
const activeDrop    = ref(null)
const msgWrapRef    = ref(null)
const notifWrapRef  = ref(null)
const profileWrapRef = ref(null)

function toggleDrop(name) { activeDrop.value = activeDrop.value === name ? null : name }
function closeAllDrops()  { activeDrop.value = null }

function markAllRead() {
  router.post(route('activity.mark-all-read'), {}, { preserveState: true, preserveScroll: true })
  closeAllDrops()
}

function logout() {
  closeAllDrops()
  router.post(route('logout'))
}

// ── Helpers ────────────────────────────────────────────────────────────
function severityColor(sev) {
  return { critical: 'red', warning: 'orange', success: 'green', info: 'blue' }[sev] ?? 'gray'
}

function timeAgo(iso) {
  if (!iso) return ''
  const diff = Math.floor((Date.now() - new Date(iso).getTime()) / 1000)
  if (diff < 60)        return diff + 's ago'
  if (diff < 3600)      return Math.floor(diff / 60) + 'm ago'
  if (diff < 86400)     return Math.floor(diff / 3600) + 'h ago'
  if (diff < 604800)    return Math.floor(diff / 86400) + 'd ago'
  return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

// ── Outside click / Escape ─────────────────────────────────────────────
function handleOutsideClick(e) {
  if (!activeDrop.value) return
  const wraps = [msgWrapRef.value, notifWrapRef.value, profileWrapRef.value].filter(Boolean)
  if (wraps.some(w => w.contains(e.target))) return
  closeAllDrops()
}
function handleEscape(e) { if (e.key === 'Escape') closeAllDrops() }

onMounted(() => {
  document.addEventListener('click', handleOutsideClick)
  document.addEventListener('keydown', handleEscape)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', handleOutsideClick)
  document.removeEventListener('keydown', handleEscape)
})
</script>

<style scoped>
.topbar {
  height: var(--topbar-height, 64px);
  background: var(--bg);
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 32px;
  position: sticky; top: 0; z-index: 100; gap: 20px;
  border-bottom: 1px solid transparent;
  transition: border-color .18s ease, background .18s ease;
}
.topbar-left {
  display: flex; align-items: center; gap: 12px;
  flex: 1; min-width: 0; text-align: left;
}
.topbar-text-wrapper { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.topbar-title {
  font-family: var(--font-serif); font-size: 18px; font-weight: 700;
  color: var(--text); line-height: 1.1; letter-spacing: 0.2px; white-space: nowrap;
}
.topbar-breadcrumb {
  font-size: 11px; color: var(--text-4); letter-spacing: 0.4px; white-space: nowrap;
}
.topbar-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

/* Emergency CTA */
.topbar-emergency { gap: 6px; display: inline-flex; align-items: center; }

/* Icon buttons */
.topbar-icon-btn {
  width: 38px; height: 38px; border-radius: var(--radius-full);
  border: 1px solid var(--border); background: var(--surface); color: var(--text-3);
  display: flex; align-items: center; justify-content: center; cursor: pointer;
  transition: background .18s ease, border-color .18s ease, color .18s ease;
  position: relative; text-decoration: none; flex-shrink: 0;
}
.topbar-icon-btn:hover { background: var(--surface-3); border-color: rgba(196,169,106,0.4); color: var(--gold-dark); }
.topbar-icon-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--badge-bg-gold); }

/* Notification dot */
.notif-dot {
  position: absolute; top: 8px; right: 9px;
  width: 7px; height: 7px; background: var(--red);
  border-radius: var(--radius-full); border: 1px solid var(--surface);
}
.notif-dot.emergency { background: var(--emergency); animation: dot-pulse 1.5s ease infinite; }

/* Profile pill */
.ep-profile-wrap { position: relative; margin-left: 4px; }
.ep-profile-btn {
  display: flex; align-items: center; gap: 10px; padding: 5px 14px 5px 5px;
  border-radius: var(--radius-full); border: 1px solid var(--border);
  cursor: pointer; transition: border-color .18s ease, box-shadow .18s ease;
  background: var(--surface);
}
.ep-profile-btn:hover { border-color: var(--gold); box-shadow: 0 2px 8px rgba(30,28,26,0.06); }
.ep-profile-btn.open  { border-color: var(--gold); box-shadow: 0 2px 8px rgba(160,129,62,0.18); }

.ep-profile-avatar {
  width: 30px; height: 30px; border-radius: var(--radius-full);
  background: linear-gradient(140deg, var(--gold), var(--gold-dark));
  color: var(--text-inverted); display: flex; align-items: center; justify-content: center;
  font-family: var(--font-serif); font-weight: 700; font-size: 12px; flex-shrink: 0;
}
.ep-profile-name { font-size: 13px; font-weight: 600; color: var(--text); white-space: nowrap; line-height: 1.2; }
.ep-profile-role { font-size: 11px; color: var(--text-3); white-space: nowrap; display: flex; align-items: center; gap: 4px; }
/* ep-role-dot replaced by avail-status-dot--sm — see Messages.vue for the shared class definitions */
.ep-profile-caret { display: inline-flex; align-items: center; color: var(--text-4); transition: transform var(--transition), color .18s ease; flex-shrink: 0; }
.ep-profile-btn:hover .ep-profile-caret { color: var(--text-2); }
.ep-profile-btn.open .ep-profile-caret  { transform: rotate(180deg); color: var(--gold-dark); }

/* Profile dropdown */
.ep-profile-drop {
  position: absolute; top: calc(100% + 10px); right: 0; width: 280px;
  background: var(--surface); border-radius: var(--radius);
  box-shadow: 0 16px 48px rgba(30,28,26,0.14), 0 4px 12px rgba(30,28,26,0.06), 0 1px 0 rgba(255,255,255,0.6) inset;
  z-index: 9999; display: none; overflow: hidden; padding: 8px; transform-origin: top right;
}
.ep-profile-drop.open {
  display: block;
  animation: epd-drop-in 0.32s cubic-bezier(0.16, 1.05, 0.32, 1) both;
}
/* Stagger each section inside profile dropdown */
.ep-profile-drop.open .epd-section {
  animation: epd-item-rise 0.36s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.ep-profile-drop.open .epd-section:nth-of-type(1) { animation-delay: 0.08s; }
.ep-profile-drop.open .epd-section:nth-of-type(2) { animation-delay: 0.12s; }
.ep-profile-drop.open .epd-section:nth-of-type(3) { animation-delay: 0.16s; }
.ep-profile-drop.open .epd-section:nth-of-type(4) { animation-delay: 0.20s; }

/* Notification/message drop panels */
.ep-drop-wrap { position: relative; }
.ep-drop-panel {
  position: absolute; top: calc(100% + 10px); right: 0;
  background: var(--surface); border-radius: var(--radius);
  box-shadow: 0 16px 48px rgba(30,28,26,0.14), 0 4px 12px rgba(30,28,26,0.06), 0 1px 0 rgba(255,255,255,0.6) inset;
  z-index: 9999; overflow: hidden; max-height: 540px; display: none; flex-direction: column;
  transform-origin: top right;
}
.ep-drop-panel.open {
  display: flex;
  animation: epd-drop-in 0.32s cubic-bezier(0.16, 1.05, 0.32, 1) both;
}
/* Stagger head → scroll → foot inside msg/notif dropdowns */
.ep-drop-panel.open .ep-drop-head,
.ep-drop-panel.open .ep-drop-scroll,
.ep-drop-panel.open .ep-drop-foot {
  animation: epd-item-rise 0.36s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.ep-drop-panel.open .ep-drop-head   { animation-delay: 0.08s; }
.ep-drop-panel.open .ep-drop-scroll { animation-delay: 0.12s; }
.ep-drop-panel.open .ep-drop-foot   { animation-delay: 0.16s; }
.notif-drop { width: 380px; }
.msg-drop   { width: 360px; }

.ep-drop-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 14px 16px 10px; border-bottom: 1px solid rgba(30,28,26,0.06); }
.ep-drop-head-title { font-family: var(--font-serif); font-size: 15px; font-weight: 700; color: var(--text); line-height: 1.2; }
.ep-drop-head-sub   { font-size: 11px; font-weight: 600; color: var(--text-3); margin-top: 2px; }
.ep-drop-head-action {
  display: inline-flex; align-items: center; justify-content: center;
  width: 28px; height: 28px; background: var(--surface-2);
  border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text-3);
  cursor: pointer; text-decoration: none; transition: background .18s ease, color .18s ease;
}
.ep-drop-head-action:hover { background: var(--badge-bg-gold); border-color: var(--soft-gold); color: var(--gold-dark); }

.ep-drop-scroll { overflow-y: auto; flex: 1 1 auto; min-height: 100px; max-height: 420px; padding: 4px 0; }
.ep-drop-scroll::-webkit-scrollbar { width: 6px; }
.ep-drop-scroll::-webkit-scrollbar-thumb { background: rgba(30,28,26,0.16); border-radius: 3px; }

.ep-drop-foot { border-top: 1px solid rgba(30,28,26,0.06); padding: 8px; }
.ep-drop-foot-btn {
  display: flex; align-items: center; justify-content: center; gap: 6px; width: 100%;
  padding: 9px 12px; background: transparent; border: 0; border-radius: var(--radius-sm);
  color: var(--gold-dark); font-size: 12px; font-weight: 700; text-decoration: none;
  cursor: pointer; transition: background .18s ease;
}
.ep-drop-foot-btn:hover { background: var(--badge-bg-gold); }

/* Notification rows */
.notif-row {
  display: flex; align-items: flex-start; gap: 10px; padding: 10px 16px;
  text-decoration: none; color: inherit; position: relative;
  transition: background .14s ease; border-bottom: 1px solid rgba(30,28,26,0.04);
}
.notif-row:last-child { border-bottom: 0; }
.notif-row:hover        { background: var(--surface-2); }
.notif-row.is-unread    { background: rgba(192,154,82,0.04); }
.notif-row.is-unread:hover { background: var(--badge-bg-gold); }
.notif-row-emergency { background: var(--emergency-light); border-bottom: 1px solid var(--emergency); }
.notif-row-emergency:hover { background: rgba(160,45,34,0.10); }

.notif-dot-mark { width: 8px; height: 8px; border-radius: 50%; margin-top: 6px; flex-shrink: 0; }
.notif-dot-mark.red      { background: var(--red); }
.notif-dot-mark.orange   { background: var(--orange); }
.notif-dot-mark.green    { background: var(--green); }
.notif-dot-mark.blue     { background: var(--blue); }
.notif-dot-mark.gray     { background: var(--text-4); }
.notif-dot-mark.emergency { background: var(--emergency); animation: notif-pulse 1.5s ease infinite; }

.notif-body { flex: 1; min-width: 0; }
.notif-title { font-size: 13px; font-weight: 700; color: var(--text); line-height: 1.35; display: flex; align-items: center; gap: 4px; }
.notif-desc  { font-size: 12px; font-weight: 500; color: var(--text-3); line-height: 1.45; margin-top: 2px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.notif-meta  { display: flex; align-items: center; gap: 6px; margin-top: 5px; }
.notif-chip  { font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-3); background: var(--surface-3); padding: 2px 6px; border-radius: var(--radius-full); }
.notif-time  { font-size: 11px; font-weight: 600; color: var(--text-4); }
.notif-unread-bullet { width: 7px; height: 7px; border-radius: 50%; background: var(--gold-dark); flex-shrink: 0; margin-top: 7px; }

.notif-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 36px 20px; text-align: center; color: var(--text-4); }
.notif-empty-title { font-size: 13px; font-weight: 700; color: var(--text-3); margin-top: 8px; }
.notif-empty-sub   { font-size: 12px; color: var(--text-4); margin-top: 3px; }

/* Message rows */
.msg-row { display: flex; align-items: center; gap: 10px; padding: 10px 16px; text-decoration: none; color: inherit; position: relative; transition: background .14s ease; border-bottom: 1px solid rgba(30,28,26,0.04); }
.msg-row:last-child { border-bottom: 0; }
.msg-row:hover      { background: var(--surface-2); }
.msg-row.is-unread  { background: rgba(192,154,82,0.04); }
.msg-row.is-unread:hover { background: var(--badge-bg-gold); }

.msg-avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--gold-dark); color: var(--text-inverted); display: flex; align-items: center; justify-content: center; font-family: var(--font-serif); font-size: 13px; font-weight: 700; flex-shrink: 0; }
.msg-body   { flex: 1; min-width: 0; }
.msg-head-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.msg-name    { font-size: 13px; font-weight: 700; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; }
.msg-time    { font-size: 11px; font-weight: 600; color: var(--text-4); flex-shrink: 0; }
.msg-preview { font-size: 12px; color: var(--text-3); margin-top: 2px; line-height: 1.4; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.msg-row.is-unread .msg-preview { color: var(--text-2); font-weight: 600; }
.msg-you     { font-weight: 700; color: var(--text-4); }

/* Dropdown items */
.epd-section { display: flex; flex-direction: column; gap: 1px; padding: 4px 0; }
.epd-section + .epd-section { margin-top: 4px; position: relative; }
.epd-section + .epd-section::before { content: ''; position: absolute; top: -2px; left: 8px; right: 8px; height: 1px; background: linear-gradient(to right, transparent, rgba(30,28,26,0.06), transparent); }
.epd-section-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; color: var(--text-4); padding: 8px 12px 6px; }
.epd-section-note  { font-size: 11px; font-weight: 500; font-style: italic; color: var(--text-4); padding: 4px 12px 8px; line-height: 1.4; }

.epd-item { display: flex; align-items: center; gap: 11px; padding: 7px 10px; cursor: pointer; text-decoration: none; background: transparent; border: none; width: 100%; text-align: left; border-radius: var(--radius-sm); color: var(--text-2); transition: background .18s ease, color .18s ease; }
.epd-item:hover { background: rgba(196,169,106,0.08); color: var(--text); }
.epd-item-icon  { display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; flex-shrink: 0; color: var(--text-4); transition: color .18s ease; }
.epd-item:hover .epd-item-icon { color: var(--gold-dark); }
.epd-item-label { flex: 1; font-size: 13px; font-weight: 400; color: inherit; line-height: 1.3; }
.epd-item-arrow { display: inline-flex; align-items: center; color: var(--text-4); transition: color .18s ease, transform .18s ease; }
.epd-item:hover .epd-item-arrow { color: var(--gold-dark); transform: translateX(2px); }

.epd-item-current { cursor: default; background: rgba(196,169,106,0.08); color: var(--text); font-weight: 600; }
.epd-item-current:hover { background: rgba(196,169,106,0.08); }
.epd-item-current .epd-item-icon { color: var(--gold-dark); }
.epd-current-badge { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; background: var(--gold-dark); color: var(--text-inverted); padding: 2px 7px; border-radius: var(--radius-sm); flex-shrink: 0; }

.epd-item-danger .epd-item-label,
.epd-item-danger .epd-item-icon  { color: var(--red); }
.epd-item-danger:hover { background: var(--red-light); }
.epd-item-danger:hover .epd-item-icon,
.epd-item-danger:hover .epd-item-label { color: var(--red-dark); }

/* Mobile hamburger */
.topbar-hamburger { display: none; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: var(--radius-sm); border: 1px solid var(--border); background: var(--surface); color: var(--text-3); cursor: pointer; flex-shrink: 0; transition: background .18s ease, border-color .18s ease; }
.topbar-hamburger:hover { background: var(--surface-3); border-color: rgba(196,169,106,0.4); color: var(--gold-dark); }

/* Responsive */
@media (max-width: 900px)  { .topbar { padding: 0 16px; } .ep-profile-info { display: none; } .ep-profile-caret { display: none; } .ep-profile-drop { right: -8px; width: 264px; } .ep-profile-btn { padding: 4px 6px; } }
@media (max-width: 768px)  { .topbar-hamburger { display: inline-flex; } .topbar-title { font-size: 17px; } }
@media (max-width: 480px)  { .notif-drop, .msg-drop { width: calc(100vw - 24px); right: -8px; } }

@keyframes epd-drop-in {
  0%   { opacity: 0; transform: translateY(-10px) scale(0.94); }
  60%  { opacity: 1; }
  100% { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes epd-item-rise {
  from { opacity: 0; transform: translateY(6px); }
  to   { opacity: 1; transform: translateY(0); }
}
@keyframes dot-pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(220,38,38,0.4); }
  50%      { box-shadow: 0 0 0 4px rgba(220,38,38,0); }
}
@keyframes notif-pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(160,45,34,0.5); }
  50%      { box-shadow: 0 0 0 6px rgba(160,45,34,0); }
}
</style>
