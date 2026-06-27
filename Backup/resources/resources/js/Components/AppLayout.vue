<template>
  <div class="layout">
    <Sidebar
      :user="user"
      :portal="portal"
      :activePage="activePage"
      :hasEmergency="hasEmergency"
      :bpType="bpType"
      :mobileOpen="isMobileSidebarOpen"
      :collapsed="isSidebarCollapsed"
      @toggle="handleSidebarToggle"
      @mobileToggle="isMobileSidebarOpen = $event"
    />
    <div class="main-content" :class="{ collapsed: isSidebarCollapsed }">
      <Header
        :user="user"
        :pageTitle="pageTitle"
        :portal="portal"
        :hasEmergency="hasEmergency"
        :unreadMessages="unreadMessages"
        :unreadNotifs="unreadNotifs"
        :threads="threads"
        :notifications="notifications"
        @toggleMobileSidebar="handleMobileSidebarToggle"
      />
      <div class="page-body">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import Sidebar from './Sidebar.vue';
import Header from './Header.vue';

defineProps({
  user: Object,
  portal: { type: String, default: 'practitioner' },
  activePage: { type: String, default: 'dashboard' },
  pageTitle: { type: String, default: 'Dashboard' },
  hasEmergency: { type: Boolean, default: false },
  bpType: { type: String, default: 'agency' },
  unreadMessages: { type: Number, default: 0 },
  unreadNotifs: { type: Number, default: 0 },
  threads: { type: Array, default: () => [] },
  notifications: { type: Array, default: () => [] },
});

const isSidebarCollapsed = ref(false);
const isMobileSidebarOpen = ref(false);

function handleSidebarToggle(collapsed) {
  isSidebarCollapsed.value = collapsed;
}

function handleMobileSidebarToggle() {
  if (window.innerWidth <= 768) {
    isMobileSidebarOpen.value = !isMobileSidebarOpen.value;
  } else {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
  }
}
</script>

<style scoped>
.layout {
  display: flex;
  min-height: 100vh;
}

.main-content {
  margin-left: var(--sidebar-width, 260px);
  flex: 1;
  min-width: 0;
  transition: margin-left var(--transition-slow, 0.3s ease);
}

.main-content.collapsed {
  margin-left: 72px;
}

.page-body {
  padding: 28px 32px;
}

@media (max-width: 1024px) {
  .page-body {
    padding: 24px 24px;
  }
}

@media (max-width: 768px) {
  .page-body {
    padding: 20px 16px;
  }
  
  .main-content {
    margin-left: 0;
  }
  
  .main-content.collapsed {
    margin-left: 0;
  }
}

@media (max-width: 480px) {
  .page-body {
    padding: 16px 12px;
  }
}


</style>