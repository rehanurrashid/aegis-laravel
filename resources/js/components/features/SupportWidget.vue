<!--
  SupportWidget.vue — floating feedback / support FAB (bottom-right).

  Replaces the floating FAB from _shared/templates/support.php. Opens a
  popover with three actions: Send feedback, Report a bug, Open ticket.
  Each routes to the portal's support page with a preselected tab, or
  opens a quick-compose modal in place.

  Hidden on the Support page itself (the parent layout omits this widget
  when the current page is the shared Support page).
-->
<template>
  <div class="support-fab" v-click-outside="close">
    <button
      type="button"
      class="support-fab-button"
      :class="{ 'is-open': open }"
      aria-label="Help and feedback"
      @click="open = !open"
    >
      <AegisIcon :name="open ? 'x' : 'help-circle'" :size="20" />
    </button>

    <Transition name="support-fab-fade">
      <div v-if="open" class="support-fab-menu">
        <div class="support-fab-header">
          <div class="support-fab-title">How can we help?</div>
          <div class="support-fab-sub">Your message reaches the team directly.</div>
        </div>

        <button
          type="button"
          class="support-fab-item"
          @click="go('feedback')"
        >
          <AegisIcon name="message-square" :size="14" />
          <div>
            <div class="support-fab-item-title">Send feedback</div>
            <div class="support-fab-item-desc">Suggest an improvement</div>
          </div>
        </button>

        <button
          type="button"
          class="support-fab-item"
          @click="go('bug')"
        >
          <AegisIcon name="alert-circle" :size="14" />
          <div>
            <div class="support-fab-item-title">Report a bug</div>
            <div class="support-fab-item-desc">Something looks wrong</div>
          </div>
        </button>

        <button
          type="button"
          class="support-fab-item"
          @click="go('tickets')"
        >
          <AegisIcon name="inbox" :size="14" />
          <div>
            <div class="support-fab-item-title">Open a ticket</div>
            <div class="support-fab-item-desc">Get help from support</div>
          </div>
        </button>

        <a :href="helpCenterHref" class="support-fab-footer">
          <AegisIcon name="book-open" :size="12" />
          <span>Help center</span>
          <AegisIcon name="external-link" :size="11" />
        </a>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const open = ref(false)

const portalPrefix = computed(() => ({
  provider:           'provider',
  continuity_steward: 'cs',
  support_steward:    'ss',
  business_partner:   'bp',
  admin:              'admin',
})[auth.portal] ?? 'provider')

const helpCenterHref = computed(() => {
  try { return route(`${portalPrefix.value}.support`, { tab: 'help' }) }
  catch { return '#' }
})

function go(tab) {
  open.value = false
  try {
    router.visit(route(`${portalPrefix.value}.support`, { tab }))
  } catch {
    // route undefined — no-op
  }
}

function close() { open.value = false }

const vClickOutside = {
  mounted(el, binding) {
    el._clickOutside = (e) => { if (!el.contains(e.target)) binding.value(e) }
    document.addEventListener('click', el._clickOutside)
  },
  unmounted(el) {
    document.removeEventListener('click', el._clickOutside)
  },
}
</script>

<style scoped>
.support-fab-fade-enter-active,
.support-fab-fade-leave-active {
  transition: opacity var(--transition-fast), transform var(--transition-fast);
}
.support-fab-fade-enter-from,
.support-fab-fade-leave-to {
  opacity: 0;
  transform: translateY(8px) scale(0.96);
}
</style>
