<!--
  pages/provider/Services.vue — services offered (Practice tier only).
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="My Practice"
      title="My Services"
      subtitle="Services you offer to clients and the network."
    >
      <template #actions>
        <button type="button" class="btn btn-primary" @click="openModal('serviceModal')">
          <AegisIcon name="plus" :size="14" />
          <span>Add service</span>
        </button>
      </template>
    </AegisHeroBanner>

    <div class="stat-chips-row">
      <AegisStatChip icon="briefcase-rx" :value="services.length" label="Services offered" />
      <AegisStatChip icon="users" :value="totalActiveClients" label="Active clients" bg-color="var(--icon-bg-blue)" icon-color="var(--blue-dark)" />
    </div>

    <div v-if="services.length" class="services-grid">
      <AegisCard v-for="s in services" :key="s.id">
        <template #actions>
          <button type="button" class="btn btn-icon btn-ghost btn-sm" data-tip="Edit" @click="edit(s)">
            <AegisIcon name="pencil" :size="13" />
          </button>
        </template>
        <div class="service-card-head">
          <div class="service-card-title">{{ s.title }}</div>
          <div class="service-card-rate">{{ rateLabel(s) }}</div>
        </div>
        <p class="service-card-desc">{{ s.description }}</p>
        <div class="service-card-meta">
          <AegisBadge :label="s.format === 'in_person' ? 'In-person' : s.format === 'online' ? 'Online' : 'Hybrid'" variant="blue" />
          <AegisBadge v-if="s.accepts_cash" label="Cash pay" variant="green" />
          <AegisBadge v-if="!s.discoverable" label="Hidden" variant="neutral" />
        </div>
      </AegisCard>
    </div>

    <AegisEmptyState v-else icon="briefcase-rx" title="No services yet" description="Add a service so clients and your network can find what you offer.">
      <template #action>
        <button type="button" class="btn btn-primary" @click="openModal('serviceModal')">Add service</button>
      </template>
    </AegisEmptyState>

    <AegisModal
      :model-value="isOpen('serviceModal').value"
      :title="form.id ? 'Edit service' : 'Add service'"
      size="md"
      @update:model-value="(v) => !v && close()"
    >
      <form @submit.prevent="save">
        <div class="form-group">
          <label class="form-label">Title <span class="req">*</span></label>
          <input v-model="form.title" required class="form-input" />
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea v-model="form.description" class="form-input" rows="4"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Rate (dollars)</label>
            <input v-model.number="form.rate_input" type="number" min="0" step="1" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label">Per</label>
            <select v-model="form.unit" class="form-input">
              <option value="session">Session</option>
              <option value="hour">Hour</option>
              <option value="package">Package</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Format</label>
          <select v-model="form.format" class="form-input">
            <option value="in_person">In-person</option>
            <option value="online">Online</option>
            <option value="hybrid">Hybrid</option>
          </select>
        </div>
        <AegisToggle v-model="form.accepts_cash" label="Accept cash pay" />
        <AegisToggle v-model="form.discoverable" label="Discoverable in network search" />
      </form>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="close">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="form.processing" @click="save">
          {{ form.processing ? 'Saving…' : 'Save' }}
        </button>
      </template>
    </AegisModal>
  </AppLayout>
</template>

<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import AegisToggle from '@/components/ui/AegisToggle.vue'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({
  services: { type: Array, default: () => [] },
  totalActiveClients: { type: Number, default: 0 },
})

const { openModal, closeModal, isOpen } = useModal()
const toast = useToast()
const pricing = usePricingStore()

const form = useForm({
  id: null, title: '', description: '', rate_input: null, unit: 'session',
  format: 'in_person', accepts_cash: false, discoverable: true,
})

// Keep cents on the model in sync with dollar input.
watch(() => form.rate_input, (v) => { form.rate_cents = v != null ? Math.round(Number(v) * 100) : null })

function rateLabel(s) {
  if (!s.rate_cents) return '—'
  return `${pricing.formatCents(s.rate_cents)} / ${s.unit}`
}

function edit(s) {
  form.id           = s.id
  form.title        = s.title
  form.description  = s.description
  form.rate_input   = s.rate_cents ? s.rate_cents / 100 : null
  form.unit         = s.unit
  form.format       = s.format
  form.accepts_cash = s.accepts_cash
  form.discoverable = s.discoverable
  openModal('serviceModal')
}

function close() { closeModal('serviceModal'); setTimeout(() => form.reset(), 200) }

function save() {
  const url = form.id
    ? route('provider.services.update', { service: form.id })
    : route('provider.services.store')
  const method = form.id ? 'put' : 'post'
  form[method](url, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Service saved.'); close() },
  })
}
</script>
