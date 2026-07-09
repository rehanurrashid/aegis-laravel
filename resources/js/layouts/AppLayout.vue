<!--
  AppLayout.vue — authenticated portal shell.

  Replaces the PHP chain: page_head.php → sidebar.php → header.php →
  layout.php → page_foot.php.

  Provides:
    • Sidebar (5 portals + emergency badge)
    • Header (search, bell, user menu)
    • Incident banner (when hasEmergency=true)
    • Slot for the page body
    • Global modals (upgrade) + toast stack + floating support widget

  All pages render through this layout unless explicitly using AuthLayout
  (login/register) or PublicLayout (public profile pages).
-->
<template>
  <div class="app-shell" :class="{ 'app-shell--collapsed': ui.sidebarCollapsed }">
    <AppSidebar />

    <div class="main-content">
      <AppHeader />

      <IncidentBanner />

      <!-- Paused Account Banner -->
      <!-- Paused Account Banner -->
      <div v-if="page.props.auth?.user?.is_paused"
        style="background:var(--orange-light);border-bottom:1px solid var(--orange);padding:10px 24px;display:flex;align-items:center;justify-content:space-between;gap:12px;font-size:13px">
        <div style="display:inline-flex;align-items:center;gap:8px;color:var(--orange-dark)">
          <AegisIcon name="activity" :size="15" />
          <strong>Account Paused</strong> — You are not visible in search results or receiving referrals.
        </div>
        <a :href="`/${page.props.auth?.portal ?? 'provider'}/settings?tab=changes`"
          style="font-size:12px;font-weight:600;color:var(--orange-dark);text-decoration:underline;white-space:nowrap">
          Reactivate →
        </a>
      </div>

      <main class="page-body">
        <slot />
      </main>
    </div>

    <!-- Global modals: available on every authenticated page. -->
    <AegisUpgradeModal />

    <!-- Global confirm host — backs useConfirm().confirmAction(). -->
    <AegisConfirm
      :model-value="ui.confirmState.open"
      :title="ui.confirmState.title"
      :message="ui.confirmState.message"
      :confirm-label="ui.confirmState.confirmLabel"
      :cancel-label="ui.confirmState.cancelLabel"
      :destructive="ui.confirmState.destructive"
      @confirm="ui.resolveConfirm"
      @update:model-value="(v) => { if (!v) ui.cancelConfirm() }"
    />

    <!-- Floating feedback / support FAB (skipped on Support page itself). -->
    <SupportWidget v-if="$page.component !== 'shared/Support'" />

    <!-- Toast stack — always last so it layers above modals. -->
    <div class="toast-container">
      <AegisToast
        v-for="toast in ui.toastQueue"
        :key="toast.id"
        :message="toast.message"
        :level="toast.level"
        @dismiss="ui.dismissToast(toast.id)"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { useNotificationStore } from '@/stores/notifications'

import AppSidebar from '@/components/chrome/AppSidebar.vue'
import AppHeader from '@/components/chrome/AppHeader.vue'

import IncidentBanner from '@/components/features/IncidentBanner.vue'
import SupportWidget from '@/components/features/SupportWidget.vue'

import AegisUpgradeModal from '@/components/ui/AegisUpgradeModal.vue'
import AegisConfirm from '@/components/ui/AegisConfirm.vue'



const auth  = useAuthStore()
const ui    = useUiStore()
const notifications = useNotificationStore()
const page  = usePage()

// Flash → toast is handled globally in app.js via router.on('navigate').
// Do NOT duplicate it here — that causes every toast to appear twice.

onMounted(() => {
  if (auth.user?.id) {
    notifications.listenForIncident(auth.user.id)
  }
})

onBeforeUnmount(() => {
  if (auth.user?.id) {
    notifications.stopListening(auth.user.id)
  }
})
</script>
