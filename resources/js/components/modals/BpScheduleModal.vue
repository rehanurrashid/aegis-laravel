<!--
  BpScheduleModal.vue — centralized consultation scheduling modal for Business Partners.

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
    title="Schedule a Consultation"
    subtitle="Book a discovery call, strategy session, or consultation meeting"
    size="md"
    @update:model-value="(v) => emit('update:modelValue', v)"
  >
    <div class="form-group">
      <label class="form-label">Partner</label>
      <input type="text" class="form-input" :value="partnerName" readonly>
    </div>

    <div class="form-group">
      <div class="form-label">Meeting Type</div>
      <div class="bsc-type-grid">
        <div
          v-for="mt in meetingTypes"
          :key="mt.label"
          :class="['bpe-option', form.type === mt.label ? 'selected' : '']"
          @click="form.type = mt.label"
        >
          <span class="bpe-option-icon"><AegisIcon :name="mt.icon" :size="15" /></span>
          <div class="bsc-type-label">{{ mt.label }}</div>
        </div>
      </div>
    </div>

    <div class="form-row form-row-2">
      <div class="form-group">
        <label class="form-label">Preferred Date <span class="req">*</span></label>
        <input type="date" class="form-input" v-model="form.date">
      </div>
      <div class="form-group">
        <label class="form-label">Preferred Time</label>
        <select class="form-select" v-model="form.time">
          <option>9:00 AM</option>
          <option>10:00 AM</option>
          <option>11:00 AM</option>
          <option>12:00 PM</option>
          <option>1:00 PM</option>
          <option>2:00 PM</option>
          <option>3:00 PM</option>
          <option>4:00 PM</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Duration</label>
        <select class="form-select" v-model="form.duration">
          <option>15 minutes</option>
          <option>30 minutes</option>
          <option>45 minutes</option>
          <option>1 hour</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Your Timezone</label>
        <select class="form-select" v-model="form.tz">
          <option>EST (New York)</option>
          <option>CST</option>
          <option>MST</option>
          <option>PST</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Agenda / Topics to Discuss <span class="form-label-opt">(optional)</span></label>
      <textarea class="form-textarea" rows="2" placeholder="What do you want to cover?" v-model="form.agenda"></textarea>
    </div>

    <!-- Video link — own row, shown only for Video Call type -->
    <div v-if="form.type === 'Video Call'" class="form-group">
      <label class="form-label">
        <AegisIcon name="video" :size="13" style="margin-right:5px;vertical-align:-2px" />
        Video Link
        <span class="form-label-opt">(optional)</span>
      </label>
      <input
        type="url"
        class="form-input"
        v-model="form.video_link"
        placeholder="Zoom, Google Meet, Teams, or any meeting URL…"
      />
      <div class="bsc-hint">Paste the join link — it will be included in the consultation invite.</div>
    </div>

    <div v-if="form.errors.date" class="alert alert-danger">{{ form.errors.date }}</div>

    <template #footer>
      <button class="btn btn-outline" :disabled="form.processing" @click="close">Cancel</button>
      <button class="btn btn-primary" :disabled="form.processing || !form.date" @click="submit">
        <span class="btn-ico"><AegisIcon name="calendar" :size="13" /></span>
        <span v-if="form.processing" class="spinner spinner-sm" />
        {{ form.processing ? 'Sending…' : 'Book Consultation' }}
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

const meetingTypes = [
  { label: 'Video Call', icon: 'user' },
  { label: 'Phone Call', icon: 'phone' },
  { label: 'In-Person',  icon: 'map-pin' },
  { label: 'Aegis Chat', icon: 'message' },
]

const form = useForm({
  type:       'Video Call',
  date:       '',
  time:       '10:00 AM',
  duration:   '30 minutes',
  tz:         'EST (New York)',
  agenda:     '',
  video_link: '',
})

watch(() => props.modelValue, (val) => { if (val) form.reset() })

function close() { emit('update:modelValue', false) }

function submit() {
  if (!props.partner?.id) return
  form.post(route('public.profile.consultation', props.partner.id), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Consultation request sent to ' + partnerName.value + '.')
      emit('submitted')
      close()
    },
    onError: () => toast.error('Please check the form and try again.'),
  })
}
</script>

<style scoped>
.form-label-opt { color: var(--text-4); font-weight: 500; }
.bsc-type-grid  {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
  margin-bottom: 4px;
}
.bsc-type-label { font-size: 11px; font-weight: 600; margin-top: 2px; }
:deep(.bpe-option) { border-width: 1px; }
.bsc-hint {
  font-family: var(--font-sans);
  font-size: 11px;
  color: var(--text-4);
  margin-top: 4px;
  line-height: 1.4;
}
</style>
