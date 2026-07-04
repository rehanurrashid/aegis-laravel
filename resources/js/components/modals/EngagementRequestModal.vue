<!--
  EngagementRequestModal.vue — detailed view of a single BpEngagementRequest.

  Used by:
    - pages/provider/SupportServices.vue  (Requests tab — provider viewing their own requests)
    - pages/public/BusinessProfile.vue    (activity tracker — same provider, from profile page)

  Props:
    modelValue  Boolean
    request     Object  — serialised BpEngagementRequest row
-->
<template>
  <AegisModal
    v-model="isOpen"
    :title="modalTitle"
    size="lg"
    @update:model-value="(v) => emit('update:modelValue', v)"
  >
    <template v-if="request">

      <!-- Partner strip -->
      <div class="erm-partner-strip">
        <div class="avatar avatar-md avatar-gold" style="border-radius:var(--radius-sm);font-size:14px;font-weight:700;flex-shrink:0">
          {{ request.bp?.avatar_initials ?? '??' }}
        </div>
        <div class="erm-partner-info">
          <div class="erm-partner-name">{{ request.bp?.display_name ?? 'Business Partner' }}</div>
          <div class="erm-partner-meta">
            <span v-if="request.bp?.bp_type" class="badge badge-blue">{{ bpTypeLabel(request.bp.bp_type) }}</span>
            <span :class="['badge', statusBadge]">{{ statusLabel }}</span>
            <span v-if="request.urgent" class="badge badge-orange">Urgent</span>
          </div>
        </div>
        <a v-if="request.bp?.slug" :href="route('public.bp', request.bp.slug)" target="_blank" class="btn btn-outline btn-sm">
          <AegisIcon name="eye" :size="13" /> View Profile
        </a>
      </div>

      <!-- Type-specific details -->
      <!-- HIRE -->
      <template v-if="request.type === 'hire'">
        <div class="erm-section-label">Engagement Details</div>
        <div class="erm-detail-grid">
          <div class="erm-detail-row">
            <span class="erm-detail-label">Engagement Type</span>
            <span class="erm-detail-val">{{ request.engagement_type || '—' }}</span>
          </div>
          <div class="erm-detail-row">
            <span class="erm-detail-label">Start Date</span>
            <span class="erm-detail-val">{{ request.start_date || '—' }}</span>
          </div>
          <div class="erm-detail-row">
            <span class="erm-detail-label">Duration</span>
            <span class="erm-detail-val">{{ request.duration || '—' }}</span>
          </div>
          <div class="erm-detail-row">
            <span class="erm-detail-label">Budget / Rate</span>
            <span class="erm-detail-val">{{ request.budget || '—' }}</span>
          </div>
          <div class="erm-detail-row">
            <span class="erm-detail-label">Payment Terms</span>
            <span class="erm-detail-val">{{ request.payment_terms || '—' }}</span>
          </div>
          <div v-if="request.notes" class="erm-detail-row erm-detail-row--full">
            <span class="erm-detail-label">Scope of Work</span>
            <span class="erm-detail-val">{{ request.notes }}</span>
          </div>
        </div>
        <div class="erm-agreements">
          <span v-if="request.include_nda" class="badge badge-blue"><AegisIcon name="shield" :size="10" /> NDA Requested</span>
          <span v-if="request.require_baa" class="badge badge-blue"><AegisIcon name="shield" :size="10" /> HIPAA BAA Requested</span>
          <span v-if="request.auto_contract" class="badge badge-blue"><AegisIcon name="file-text" :size="10" /> Auto-generate Contract</span>
        </div>
      </template>

      <!-- QUOTE -->
      <template v-else-if="request.type === 'quote'">
        <div class="erm-section-label">Quote Request Details</div>
        <div class="erm-detail-grid">
          <div class="erm-detail-row">
            <span class="erm-detail-label">Service</span>
            <span class="erm-detail-val">{{ request.service || '—' }}</span>
          </div>
          <div class="erm-detail-row">
            <span class="erm-detail-label">Practice Size</span>
            <span class="erm-detail-val">{{ request.size || '—' }}</span>
          </div>
          <div class="erm-detail-row">
            <span class="erm-detail-label">Budget</span>
            <span class="erm-detail-val">{{ request.budget || '—' }}</span>
          </div>
          <div class="erm-detail-row">
            <span class="erm-detail-label">Timeline</span>
            <span class="erm-detail-val">{{ request.timeline || '—' }}</span>
          </div>
          <div v-if="request.notes" class="erm-detail-row erm-detail-row--full">
            <span class="erm-detail-label">Additional Notes</span>
            <span class="erm-detail-val">{{ request.notes }}</span>
          </div>
        </div>
      </template>

      <!-- CONSULTATION -->
      <template v-else-if="request.type === 'consultation'">
        <div class="erm-section-label">Consultation Details</div>
        <div class="erm-detail-grid">
          <div class="erm-detail-row">
            <span class="erm-detail-label">Meeting Type</span>
            <span class="erm-detail-val">{{ request.meeting_type || '—' }}</span>
          </div>
          <div class="erm-detail-row">
            <span class="erm-detail-label">Date</span>
            <span class="erm-detail-val">{{ request.start_date || '—' }}</span>
          </div>
          <div class="erm-detail-row">
            <span class="erm-detail-label">Time</span>
            <span class="erm-detail-val">{{ request.preferred_time || '—' }}</span>
          </div>
          <div class="erm-detail-row">
            <span class="erm-detail-label">Duration</span>
            <span class="erm-detail-val">{{ request.meeting_duration || '—' }}</span>
          </div>
          <div class="erm-detail-row">
            <span class="erm-detail-label">Timezone</span>
            <span class="erm-detail-val">{{ request.timezone || '—' }}</span>
          </div>
          <div v-if="request.agenda" class="erm-detail-row erm-detail-row--full">
            <span class="erm-detail-label">Agenda</span>
            <span class="erm-detail-val">{{ request.agenda }}</span>
          </div>
        </div>
      </template>

      <!-- Metadata footer -->
      <div class="erm-meta-row">
        <span class="erm-meta-item"><AegisIcon name="clock" :size="12" /> Submitted {{ request.created_at }}</span>
        <span v-if="request.response_note" class="erm-meta-item">
          <AegisIcon name="message-square" :size="12" /> {{ request.response_note }}
        </span>
      </div>

    </template>

    <template #footer>
      <button class="btn btn-outline" @click="emit('update:modelValue', false)">Close</button>
      <button v-if="request?.bp?.id" class="btn btn-ghost" :disabled="msgLoading === request.bp.id" @click="openConversation(request.bp.id)">
        <AegisIcon name="message-square" :size="13" /> Message
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { computed } from 'vue'
import AegisModal from '@/components/ui/AegisModal.vue'
import AegisIcon from '@/components/ui/AegisIcon.vue'
import { useMessageButton } from '@/composables/useMessageButton'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  request:    { type: Object,  default: null },
})
const emit = defineEmits(['update:modelValue'])

const { openConversation, loading: msgLoading } = useMessageButton()

const isOpen = computed(() => props.modelValue)

const modalTitle = computed(() => {
  if (!props.request) return 'Engagement Request'
  return {
    hire:         'Engagement Request',
    quote:        'Quote Request',
    consultation: 'Consultation Request',
  }[props.request.type] ?? 'Request Details'
})

const statusLabel = computed(() => {
  const s = props.request?.status ?? 'pending'
  return s.charAt(0).toUpperCase() + s.slice(1)
})

const statusBadge = computed(() => ({
  pending:  'badge-gold',
  accepted: 'badge-green',
  declined: 'badge-red',
  expired:  'badge-grey',
}[props.request?.status ?? 'pending'] ?? 'badge-gold'))

function bpTypeLabel(t) {
  if (!t) return ''
  return t.charAt(0).toUpperCase() + t.slice(1)
}
</script>

<style scoped>
.erm-partner-strip {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  margin-bottom: 20px;
}
.erm-partner-info  { flex: 1; min-width: 0; }
.erm-partner-name  { font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 5px; }
.erm-partner-meta  { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }

.erm-section-label {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  color: var(--text-4);
  margin-bottom: 10px;
  margin-top: 2px;
}

.erm-detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px 20px;
  padding: 14px 16px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  margin-bottom: 14px;
}
.erm-detail-row         { display: flex; flex-direction: column; gap: 2px; }
.erm-detail-row--full   { grid-column: 1 / -1; }
.erm-detail-label       { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text-4); }
.erm-detail-val         { font-size: 13px; font-weight: 500; color: var(--text); line-height: 1.5; }

.erm-agreements {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  margin-bottom: 16px;
}

.erm-meta-row {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
  padding-top: 12px;
  border-top: 1px solid var(--border);
  margin-top: 4px;
}
.erm-meta-item {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  color: var(--text-4);
  font-weight: 500;
}
</style>
