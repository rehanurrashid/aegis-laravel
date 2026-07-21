<!--
  BpQuoteModal.vue — centralized quote request modal for Business Partners.

  Used by:
    - pages/provider/Network.vue
    - pages/public/BusinessProfile.vue

  Props:
    modelValue  Boolean
    partner     Object  — { id, name, display_name }

  Emits:
    update:modelValue
    submitted
-->
<template>
  <AegisModal
    v-model="isOpen"
    title="Request a Quote"
    subtitle="Describe your needs and request a formal quote from this partner"
    size="md"
    @update:model-value="(v) => emit('update:modelValue', v)"
  >
    <div class="form-group">
      <label class="form-label">Partner</label>
      <input type="text" class="form-input" :value="partnerName" readonly>
    </div>
    <div class="form-group">
      <label class="form-label">Service Needed <span class="req">*</span></label>
      <select class="form-select" v-model="form.service">
        <option value="">Select a service…</option>
        <option>Tax Preparation (one-time)</option>
        <option>Bookkeeping Setup &amp; Cleanup</option>
        <option>Payroll Setup</option>
        <option>Practice Valuation</option>
        <option>Financial Audit</option>
        <option>Medical Billing</option>
        <option>Compliance / HIPAA Consulting</option>
        <option>IT / EHR Support</option>
        <option>Legal / Contracting</option>
        <option>Hourly Consultation</option>
        <option>Other / Custom</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Practice Size</label>
      <select class="form-select" v-model="form.size">
        <option>Solo Practitioner</option>
        <option>2-5 Providers</option>
        <option>6-15 Providers</option>
        <option>16+ Providers / Group Practice</option>
      </select>
    </div>
    <div class="form-row form-row-2">
      <div class="form-group">
        <label class="form-label">Estimated Budget</label>
        <input type="text" class="form-input" placeholder="e.g. $1,500-$3,000 fixed" v-model="form.budget">
      </div>
      <div class="form-group">
        <label class="form-label">Timeline</label>
        <input type="text" class="form-input" placeholder="e.g. ASAP / within 2 weeks" v-model="form.timeline">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Additional Details <span class="form-label-opt">(optional)</span></label>
      <textarea class="form-textarea" rows="3" placeholder="Describe specific needs, pain points, or context…" v-model="form.notes"></textarea>
    </div>
    <label class="form-check">
      <input type="checkbox" v-model="form.urgent">
      <span class="form-check-label">Urgent — request response within 48 hours</span>
    </label>
    <div v-if="form.errors.service" class="alert alert-danger">{{ form.errors.service }}</div>

    <template #footer>
      <button class="btn btn-outline" :disabled="form.processing" @click="close">Cancel</button>
      <button class="btn btn-primary" :disabled="form.processing || !form.service" @click="submit">
        <span class="btn-ico"><AegisIcon name="send" :size="13" /></span>
        <span v-if="form.processing" class="spinner spinner-sm" />
        {{ form.processing ? 'Sending…' : 'Send Request' }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AegisModal from '@/components/ui/AegisModal.vue'
import AegisIcon from '@/components/ui/AegisIcon.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  partner:    { type: Object,  default: null },
})
const emit = defineEmits(['update:modelValue', 'submitted'])

const toast = useToast()
const isOpen = computed(() => props.modelValue)
const partnerName = computed(() => props.partner?.display_name ?? props.partner?.name ?? '')

const form = useForm({
  service:  '',
  size:     'Solo Practitioner',
  budget:   '',
  timeline: '',
  notes:    '',
  urgent:   false,
})

// Reset form each time modal opens
watch(() => props.modelValue, (val) => { if (val) form.reset() })

function close() { emit('update:modelValue', false) }

function submit() {
  if (!props.partner?.id) return
  form.post(route('public.profile.quote-request', props.partner.id), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Quote request sent to ' + partnerName.value + '.')
      emit('submitted')
      close()
    },
    onError: () => toast.error('Please check the form and try again.'),
  })
}
</script>

<style scoped>
.form-label-opt { color: var(--text-4); font-weight: 500; }
</style>
