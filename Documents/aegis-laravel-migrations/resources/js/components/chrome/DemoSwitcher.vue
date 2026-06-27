<!--
  DemoSwitcher.vue — bottom-right demo user switcher.

  Replaces demo_switcher.php. Visible only when import.meta.env.DEV is true
  (production builds strip the entire block). Switches the current session
  user via the demo.switch route — server swaps in a different seeded user
  and redirects to their portal home.
-->
<template>
  <div v-if="demo.isDev" class="demo-switcher" :class="{ 'is-collapsed': collapsed }">
    <button
      type="button"
      class="demo-switcher-toggle"
      :aria-label="collapsed ? 'Expand demo switcher' : 'Collapse demo switcher'"
      @click="collapsed = !collapsed"
    >
      <AegisIcon :name="collapsed ? 'chevron-left' : 'chevron-right'" :size="12" />
    </button>

    <div v-show="!collapsed" class="demo-switcher-body">
      <div class="demo-switcher-header">
        <span class="demo-switcher-label">Demo</span>
        <button
          type="button"
          class="demo-switcher-reset"
          title="Reset demo data"
          @click="demo.resetDemo"
        >
          <AegisIcon name="refresh" :size="11" />
          <span>Reset</span>
        </button>
      </div>

      <div class="demo-switcher-users">
        <button
          v-for="user in demo.demoUsers"
          :key="user.id"
          type="button"
          class="demo-user-btn"
          :class="{ 'is-active': auth.user?.id === user.id }"
          :title="`Switch to ${user.name} (${user.role})`"
          @click="demo.switchUser(user.id)"
        >
          {{ user.name.split(' ')[0] }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useDemo } from '@/composables/useDemo'
import { useAuthStore } from '@/stores/auth'

const demo = useDemo()
const auth = useAuthStore()
const collapsed = ref(false)
</script>
