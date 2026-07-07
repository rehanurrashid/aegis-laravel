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
import { onMounted, onBeforeUnmount, watch } from 'vue'
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

import { useToast } from '@/composables/useToast'

const auth  = useAuthStore()
const ui    = useUiStore()
const toast = useToast()
const notifications = useNotificationStore()
const page  = usePage()

// Fire flash toasts on every Inertia page load (covers post-login redirect).
// onMounted handles initial load; watch handles SPA navigations where layout stays mounted.
const fireFlashToasts = (flash) => {
  if (flash?.success) toast.success(flash.success)
  if (flash?.error)   toast.error(flash.error)
  if (flash?.info)    toast.info(flash.info)
  if (flash?.warning) toast.warning(flash.warning)
}

watch(() => page.props.flash, fireFlashToasts, { deep: true })

onMounted(() => {
  fireFlashToasts(page.props.flash)

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
