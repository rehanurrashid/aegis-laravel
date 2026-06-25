<!--
  pages/admin/Roles.vue — RBAC role management.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Admin"
      title="Roles &amp; Permissions"
      subtitle="Role-based access control for staff and admins."
    >
      <template #actions>
        <button type="button" class="btn btn-primary" @click="openCreate">
          <AegisIcon name="plus" :size="14" />
          <span>New role</span>
        </button>
      </template>
    </AegisHeroBanner>

    <AegisCard v-if="roles.length">
      <table class="data-table">
        <thead>
          <tr>
            <th>Role</th>
            <th>Members</th>
            <th>Permissions</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in roles" :key="r.id">
            <td class="data-table-primary">{{ r.name }}</td>
            <td>{{ r.member_count }}</td>
            <td>{{ r.permission_count }} permission{{ r.permission_count === 1 ? '' : 's' }}</td>
            <td>
              <button type="button" class="btn btn-sm btn-outline" @click="edit(r)">Edit</button>
              <button v-if="!r.is_system" type="button" class="btn btn-sm btn-ghost btn-danger-ghost" @click="del(r)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </AegisCard>

    <AegisEmptyState v-else icon="shield" title="No roles defined" />

    <AegisModal
      :model-value="isOpen('roleModal').value"
      :title="form.id ? 'Edit role' : 'New role'"
      size="lg"
      @update:model-value="(v) => !v && close()"
    >
      <form @submit.prevent="save">
        <div class="form-group">
          <label class="form-label">Name <span class="req">*</span></label>
          <input v-model="form.name" required class="form-input" />
        </div>
        <div class="form-group">
          <label class="form-label">Permissions</label>
          <div class="perm-grid">
            <label v-for="p in allPermissions" :key="p" class="perm-chip">
              <input type="checkbox" :value="p" v-model="form.permissions" />
              <span>{{ p }}</span>
            </label>
          </div>
        </div>
      </form>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="close">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="form.processing" @click="save">
          {{ form.processing ? 'Saving…' : 'Save role' }}
        </button>
      </template>
    </AegisModal>
  </AppLayout>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'

defineProps({
  roles:          { type: Array, default: () => [] },
  allPermissions: { type: Array, default: () => [] },
})

const { openModal, closeModal, isOpen } = useModal()
const toast = useToast()

const form = useForm({ id: null, name: '', permissions: [] })

function openCreate() { form.reset(); openModal('roleModal') }
function edit(r) {
  form.id = r.id
  form.name = r.name
  form.permissions = [...r.permissions]
  openModal('roleModal')
}
function close() { closeModal('roleModal'); setTimeout(() => form.reset(), 200) }

function save() {
  const url    = form.id ? route('admin.roles.update', { role: form.id }) : route('admin.roles.store')
  const method = form.id ? 'put' : 'post'
  form[method](url, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Role saved.'); close() },
  })
}

function del(r) {
  if (!window.confirm(`Delete role "${r.name}"?`)) return
  router.delete(route('admin.roles.destroy', { role: r.id }), { onSuccess: () => toast.success('Role deleted.') })
}
</script>
