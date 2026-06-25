<!--
  pages/admin/Users.vue — user directory and management.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Admin"
      title="Users"
      :subtitle="`${users.total} user${users.total === 1 ? '' : 's'} total.`"
    />

    <div class="bp-filters">
      <input v-model="filters.q" type="text" class="form-input bp-filters-q" placeholder="Search by name or email…" @keyup.enter="apply" />
      <select v-model="filters.portal" class="form-input" @change="apply">
        <option value="">All portals</option>
        <option value="provider">Practitioner</option>
        <option value="cs">Continuity Steward</option>
        <option value="ss">Support Steward</option>
        <option value="bp">Business Partner</option>
        <option value="admin">Admin</option>
      </select>
      <select v-model="filters.status" class="form-input" @change="apply">
        <option value="">Any status</option>
        <option value="active">Active</option>
        <option value="suspended">Suspended</option>
      </select>
    </div>

    <AegisCard v-if="users.data?.length">
      <table class="data-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Portal</th>
            <th>Status</th>
            <th>Joined</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users.data" :key="u.id">
            <td class="data-table-primary">{{ u.display_name }}</td>
            <td>{{ u.email }}</td>
            <td><AegisBadge :label="portalLabel(u.portal)" variant="blue" /></td>
            <td><AegisBadge :label="u.status" :variant="u.status === 'active' ? 'green' : 'red'" /></td>
            <td>{{ activity.timeAgo(u.created_at) }}</td>
            <td>
              <a :href="route('admin.users.show', { user: u.id })" class="btn btn-sm btn-outline">Open</a>
              <button
                type="button"
                class="btn btn-sm btn-ghost btn-danger-ghost"
                @click="toggleSuspend(u)"
              >
                {{ u.status === 'active' ? 'Suspend' : 'Reinstate' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <AegisPagination :links="users.links" />
    </AegisCard>

    <AegisEmptyState v-else icon="users" title="No users match" />
  </AppLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import AegisPagination from '@/components/ui/AegisPagination.vue'
import { useActivity } from '@/composables/useActivity'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  users:   { type: Object, default: () => ({ data: [], links: [], total: 0 }) },
  filters: { type: Object, default: () => ({ q: '', portal: '', status: '' }) },
})

const activity = useActivity()
const toast = useToast()
const filters = reactive({ ...props.filters })

function portalLabel(p) { return { provider: 'Practitioner', cs: 'Continuity Steward', ss: 'Support Steward', bp: 'Business Partner', admin: 'Admin' }[p] ?? p }
function apply() {
  router.get(route('admin.users'), filters, { preserveScroll: true, preserveState: true, replace: true, only: ['users', 'filters'] })
}

function toggleSuspend(u) {
  const action = u.status === 'active' ? 'suspend' : 'reinstate'
  if (!window.confirm(`${action.charAt(0).toUpperCase() + action.slice(1)} ${u.display_name}?`)) return
  router.post(route(`admin.users.${action}`, { user: u.id }), {}, {
    preserveScroll: true,
    onSuccess: () => toast.success(`User ${action}d.`),
  })
}
</script>
