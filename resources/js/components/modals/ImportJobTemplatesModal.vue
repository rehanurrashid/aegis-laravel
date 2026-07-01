<!--
  ImportJobTemplatesModal.vue — Provider "Job Templates" picker.
  Replaces job-postings.php #importJobModal. Emits a template selection so the
  parent page can pre-fill and open PostJobModal (client-side convenience only —
  no backend write happens here).
-->
<template>
  <AegisModal v-model="isOpen" title="Job Templates" size="md" @update:model-value="onUpdateOpen">
    <div class="modal-sub" style="margin-bottom:14px">Start with a pre-built template and customize</div>
    <div style="display:flex;flex-direction:column;gap:10px">
      <div v-for="t in templates" :key="t.type" class="jp-template-item" @click="use(t)">
        <div class="jp-template-icon">
          <AegisIcon :name="t.icon" :size="18" />
        </div>
        <div>
          <div class="jp-template-title">{{ t.title }}</div>
          <div class="jp-template-sub">{{ t.sub }}</div>
        </div>
        <div class="btn btn-xs btn-outline" style="margin-left:auto">Use →</div>
      </div>
    </div>

    <template #footer>
      <button type="button" class="btn btn-outline" @click="close">Close</button>
    </template>
  </AegisModal>
</template>

<script setup>
import { computed } from 'vue'
import AegisIcon from '@/components/ui/AegisIcon.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'use'])

const isOpen = computed(() => props.modelValue)
function onUpdateOpen(v) { emit('update:modelValue', v) }
function close() { emit('update:modelValue', false) }

const templates = [
  { type: 'billing',       icon: 'credit-card',     title: 'Medical Billing Specialist',        sub: 'Full-cycle billing, denial management, EHR specialist',
    category: 'billing',      description: 'We are seeking an experienced Medical Billing Specialist to handle full-cycle billing for our psychiatric practice. Must have prior experience with behavioral health billing codes, insurance follow-up, denial management, and EHR systems (Athena preferred). HIPAA compliance required.' },
  { type: 'it',            icon: 'shield-check',    title: 'HIPAA IT Support Engineer',          sub: 'Network security, HIPAA compliance, EHR integration',
    category: 'it',           description: 'Looking for a HIPAA-certified IT engineer to manage our practice network security, support EHR infrastructure, and ensure HIPAA compliance for our clinical systems. Remote support with on-site visits as needed.' },
  { type: 'marketing',     icon: 'megaphone',       title: 'Practice Marketing Consultant',      sub: 'SEO, client acquisition, social media, reputation mgmt',
    category: 'marketing',    description: 'Seeking a digital marketing specialist experienced with practice growth. Responsibilities include SEO optimization, Google Ads management, social media strategy, reputation management, and patient acquisition campaigns.' },
  { type: 'va',            icon: 'user-check',      title: 'Virtual Administrative Assistant',   sub: 'Scheduling, client follow-up, data entry, insurance auth',
    category: 'admin',        description: 'HIPAA-trained virtual assistant needed for appointment scheduling, client reminders, insurance authorizations, and general administrative support.' },
  { type: 'credentialing', icon: 'clipboard-check', title: 'Insurance Credentialing Specialist', sub: 'CAQH, PECOS, NPI, panel enrollment, re-credentialing',
    category: 'credentialing', description: 'Need an experienced credentialing specialist to manage insurance panel enrollments, CAQH profile maintenance, NPI registrations, and payer follow-up for our practice.' },
]

function use(t) {
  emit('use', { title: t.title, category: t.category, description: t.description })
  close()
}
</script>

<style scoped>
.jp-template-item { border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 14px; cursor: pointer; transition: all var(--transition); display: flex; align-items: center; gap: 12px; background: var(--surface); }
.jp-template-item:hover { border-color: var(--gold-dark); background: var(--badge-bg-gold); }
.jp-template-icon { width: 38px; height: 38px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: var(--badge-bg-gold); color: var(--gold-dark); }
.jp-template-title { font-size: 13px; font-weight: 700; color: var(--text); }
.jp-template-sub { font-size: 11px; color: var(--text-3); }
</style>
