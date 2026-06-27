<!--
  pages/admin/Packages.vue — subscription package management.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Admin"
      title="Packages"
      subtitle="Subscription tiers offered to practitioners and stewards."
    >
      <template #actions>
        <button type="button" class="btn btn-primary" @click="openCreate">
          <AegisIcon name="plus" :size="14" />
          <span>New package</span>
        </button>
      </template>
    </AegisHeroBanner>

    <AegisCard v-if="packages.length">
      <table class="data-table">
        <thead>
          <tr>
            <th>Package</th>
            <th>Audience</th>
            <th>Monthly</th>
            <th>Annual</th>
            <th>Active subscribers</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in packages" :key="p.id">
            <td class="data-table-primary">{{ p.name }}</td>
            <td>{{ p.audience }}</td>
            <td>{{ pricing.formatCents(p.monthly_cents) }}</td>
            <td>{{ pricing.formatCents(p.annual_cents) }}</td>
            <td>{{ p.subscriber_count }}</td>
            <td><AegisBadge :label="p.active ? 'Active' : 'Disabled'" :variant="p.active ? 'green' : 'neutral'" /></td>
            <td>
              <button type="button" class="btn btn-ghost btn-icon btn-sm" data-tip="Edit" @click="edit(p)">
                <AegisIcon name="pencil" :size="13" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </AegisCard>

    <AegisEmptyState v-else icon="package" title="No packages defined" />

    <!-- Edit / create modal -->
    <AegisModal
      :model-value="isOpen('packageModal').value"
      :title="form.id ? 'Edit package' : 'New package'"
      @update:model-value="(v) => !v && close()"
    >
      <form @submit.prevent="save">
        <div class="form-group">
          <label class="form-label">Name <span class="req">*</span></label>
          <input v-model="form.name" required class="form-input" />
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Audience</label>
            <select v-model="form.audience" class="form-input">
              <option value="provider">Practitioner</option>
              <option value="cs">Continuity Steward</option>
              <option value="ss">Support Steward</option>
              <option value="bp">Business Partner</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select v-model="form.active" class="form-input">
              <option :value="true">Active</option>
              <option :value="false">Disabled</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Monthly ($)</label>
            <input v-model.number="form.monthly_input" type="number" min="0" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label">Annual ($)</label>
            <input v-model.number="form.annual_input" type="number" min="0" class="form-input" />
          </div>
        </div>
      </form>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="close">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="form.processing" @click="save">
          {{ form.processing ? 'Saving…' : 'Save package' }}
        </button>
      </template>
    </AegisModal>
  </AppLayout>
</template>

<script setup>
import { watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'
import { usePricingStore } from '@/stores/pricing'

defineProps({ packages: { type: Array, default: () => [] } })

const { openModal, closeModal, isOpen } = useModal()
const toast = useToast()
const pricing = usePricingStore()

const form = useForm({
  id: null, name: '', audience: 'provider', active: true,
  monthly_input: null, monthly_cents: null, annual_input: null, annual_cents: null,
})

watch(() => form.monthly_input, (v) => { form.monthly_cents = v ? Math.round(v * 100) : null })
watch(() => form.annual_input,  (v) => { form.annual_cents  = v ? Math.round(v * 100) : null })

function openCreate() { form.reset(); openModal('packageModal') }
function edit(p) {
  form.id            = p.id
  form.name          = p.name
  form.audience      = p.audience
  form.active        = p.active
  form.monthly_input = p.monthly_cents / 100
  form.annual_input  = p.annual_cents / 100
  openModal('packageModal')
}
function close() { closeModal('packageModal'); setTimeout(() => form.reset(), 200) }

function save() {
  const url    = form.id ? route('admin.packages.update', { package: form.id }) : route('admin.packages.store')
  const method = form.id ? 'put' : 'post'
  form[method](url, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Package saved.'); close() },
  })
}
</script>
