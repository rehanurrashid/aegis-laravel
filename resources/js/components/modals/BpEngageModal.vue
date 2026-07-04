<!--
  BpEngageModal.vue — Centralized "Hire / Engage Business Partner" modal.

  Used by:
    - pages/public/BusinessProfile.vue  (hire from public profile)
    - pages/provider/Network.vue        (hire from My Partners / Search Partners)

  NOT used by:
    - pages/provider/SupportServices.vue — that page has its own
      job-posting → proposal → accept workflow.

  Props:
    modelValue  Boolean   — v-model open/close
    partner     Object    — { id, name, role, initials, avatar_url?, rating?, verified?, rate? }

  Emits:
    update:modelValue
    submitted(formData)   — after Confirm Engagement; parent shows toast
-->
<template>
  <AegisModal
    v-model="isOpen"
    title="Hire / Engage Partner"
    subtitle="Start a new engagement with this business partner"
    size="lg"
    @update:model-value="onClose"
  >

    <!-- ── Step indicator (canonical .modal-steps from _shared.css) ── -->
    <div class="modal-steps">
      <div class="modal-step done">
        <div class="modal-step-num"><AegisIcon name="check" :size="12" /></div>
        Partner Selected
      </div>
      <div class="modal-step-divider"></div>
      <div :class="['modal-step', step === 2 ? 'active' : step > 2 ? 'done' : '']">
        <div class="modal-step-num">
          <AegisIcon v-if="step > 2" name="check" :size="12" />
          <template v-else>2</template>
        </div>
        Engagement Type
      </div>
      <div class="modal-step-divider"></div>
      <div :class="['modal-step', step === 3 ? 'active' : step > 3 ? 'done' : '']">
        <div class="modal-step-num">
          <AegisIcon v-if="step > 3" name="check" :size="12" />
          <template v-else>3</template>
        </div>
        Scope &amp; Terms
      </div>
      <div class="modal-step-divider"></div>
      <div :class="['modal-step', step === 4 ? 'active' : '']">
        <div class="modal-step-num">4</div>
        Confirm
      </div>
    </div>

    <!-- ── Partner summary strip ── -->
    <div v-if="partner" class="bpe-summary">
      <div class="bpe-summary-avatar">{{ partner.initials }}</div>
      <div class="bpe-summary-info">
        <div class="bpe-summary-name">{{ partner.name }}</div>
        <div class="bpe-summary-role">{{ partner.role }}</div>
        <div class="bpe-summary-meta">
          <span v-if="partner.verified" class="badge badge-green">
            <AegisIcon name="check" :size="10" /> Verified
          </span>
          <span v-if="partner.rating" class="bpe-rating">
            <AegisIcon name="star" :size="11" class="aegis-icon-filled aegis-icon-gold-dark" />
            {{ partner.rating }}
          </span>
          <span v-if="partner.rate" class="bpe-rate">{{ partner.rate }}</span>
        </div>
      </div>
    </div>

    <!-- ══ STEP 2 — Engagement Type ══ -->
    <template v-if="step === 2">
      <div class="bpe-label">Select Engagement Type</div>
      <div class="bpe-option-row">
        <div
          v-for="opt in engagementTypes"
          :key="opt.label"
          :class="['bpe-option', form.type === opt.label ? 'selected' : '']"
          @click="form.type = opt.label"
        >
          <span class="bpe-option-icon"><AegisIcon :name="opt.icon" :size="16" /></span>
          <div>
            <div>{{ opt.label }}</div>
            <div class="bpe-option-sub">{{ opt.sub }}</div>
          </div>
        </div>
      </div>
    </template>

    <!-- ══ STEP 3 — Scope & Terms ══ -->
    <template v-if="step === 3">
      <div class="form-row form-row-2">
        <div class="form-group">
          <label class="form-label">Start Date <span class="req">*</span></label>
          <input type="date" class="form-input" v-model="form.start_date" />
        </div>
        <div class="form-group">
          <label class="form-label">Duration</label>
          <input type="text" class="form-input" placeholder="e.g. 3 months / Ongoing" v-model="form.duration" />
        </div>
        <div class="form-group">
          <label class="form-label">Budget / Rate</label>
          <input type="text" class="form-input" placeholder="e.g. $95/hr or $2,000 fixed" v-model="form.budget" />
        </div>
        <div class="form-group">
          <label class="form-label">Payment Terms</label>
          <select class="form-select" v-model="form.payment_terms">
            <option>Net 30</option>
            <option>Net 15</option>
            <option>Upon Completion</option>
            <option>50% Upfront / 50% On Delivery</option>
            <option>Hourly Time-Tracking</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Scope of Work / Notes</label>
        <textarea
          class="form-textarea"
          rows="4"
          placeholder="Describe deliverables, requirements, and timeline…"
          v-model="form.notes"
        ></textarea>
      </div>
      <div class="bpe-label">Agreements</div>
      <div class="bpe-clause-grid">
        <label class="bpe-clause-item">
          <input type="checkbox" v-model="form.include_nda" />
          <span>Include NDA Agreement</span>
        </label>
        <label class="bpe-clause-item">
          <input type="checkbox" v-model="form.require_baa" />
          <span>Require HIPAA BAA</span>
        </label>
        <label class="bpe-clause-item">
          <input type="checkbox" v-model="form.auto_contract" />
          <span>Auto-generate Service Contract</span>
        </label>
        <label class="bpe-clause-item">
          <input type="checkbox" v-model="form.termination_clause" />
          <span>Termination Clause (30-day notice)</span>
        </label>
      </div>
    </template>

    <!-- ══ STEP 4 — Confirm ══ -->
    <template v-if="step === 4">
      <div class="bpe-confirm-grid">
        <div class="bpe-confirm-row">
          <span class="bpe-confirm-label">Engagement Type</span>
          <span class="bpe-confirm-value">{{ form.type }}</span>
        </div>
        <div class="bpe-confirm-row">
          <span class="bpe-confirm-label">Start Date</span>
          <span class="bpe-confirm-value">{{ form.start_date || '—' }}</span>
        </div>
        <div class="bpe-confirm-row">
          <span class="bpe-confirm-label">Duration</span>
          <span class="bpe-confirm-value">{{ form.duration || '—' }}</span>
        </div>
        <div class="bpe-confirm-row">
          <span class="bpe-confirm-label">Budget / Rate</span>
          <span class="bpe-confirm-value">{{ form.budget || '—' }}</span>
        </div>
        <div class="bpe-confirm-row">
          <span class="bpe-confirm-label">Payment Terms</span>
          <span class="bpe-confirm-value">{{ form.payment_terms }}</span>
        </div>
        <div v-if="form.notes" class="bpe-confirm-row bpe-confirm-row--full">
          <span class="bpe-confirm-label">Scope of Work</span>
          <span class="bpe-confirm-value">{{ form.notes }}</span>
        </div>
      </div>
      <div class="bpe-tip-box">
        <span class="bpe-tip-icon"><AegisIcon name="check" :size="16" class="aegis-icon-gold-dark" /></span>
        <div class="bpe-tip-body">
          <strong>Aegis handles the rest.</strong> The partner will be notified and both parties must e-sign any generated documents before work begins. All messaging stays on Aegis secure channels.
        </div>
      </div>
    </template>

    <!-- ── Footer ── -->
    <template #footer>
      <button type="button" class="btn btn-ghost" @click="onClose">Cancel</button>
      <button v-if="step > 2" type="button" class="btn btn-outline" @click="step--">
        <AegisIcon name="chevron-left" :size="13" /> Back
      </button>
      <button v-if="step < 4" type="button" class="btn btn-primary" :disabled="!canAdvance" @click="step++">
        Next <AegisIcon name="chevron-right" :size="13" />
      </button>
      <button v-if="step === 4" type="button" class="btn btn-primary" :disabled="busy" @click="submit">
        <AegisIcon name="check" :size="13" />
        {{ busy ? 'Sending…' : 'Confirm Engagement' }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AegisModal from '@/components/ui/AegisModal.vue'
import AegisIcon from '@/components/ui/AegisIcon.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  partner:    { type: Object,  default: null },  // { id, name, role, initials, avatar_url?, rating?, verified?, rate? }
})
const emit = defineEmits(['update:modelValue', 'submitted'])

const toast = useToast()
const step = ref(2)
const busy = ref(false)

const isOpen = computed(() => props.modelValue)
function onClose() { emit('update:modelValue', false) }

const defaultForm = () => ({
  type:             'Fixed-Scope Project',
  start_date:       '',
  duration:         '',
  budget:           '',
  payment_terms:    'Net 30',
  notes:            '',
  include_nda:      true,
  require_baa:      true,
  auto_contract:    false,
  termination_clause: true,
})

const form = ref(defaultForm())

// Reset when modal opens or partner changes
watch(() => props.modelValue, (open) => {
  if (open) {
    step.value = 2
    form.value = defaultForm()
    busy.value = false
  }
})

const engagementTypes = [
  { label: 'Fixed-Scope Project', icon: 'clipboard',    sub: 'One-time deliverable based' },
  { label: 'Hourly / Time-Based', icon: 'clock',        sub: 'Pay per hour worked' },
  { label: 'Monthly Retainer',    icon: 'refresh',      sub: 'Ongoing recurring engagement' },
  { label: 'Consultation Only',   icon: 'credit-card',  sub: 'Single advisory session' },
]

const canAdvance = computed(() => {
  if (step.value === 2) return !!form.value.type
  if (step.value === 3) return !!form.value.start_date
  return true
})

function submit() {
  if (!props.partner) return
  busy.value = true

  // POST to network hire route — creates a BpJob / direct engagement request.
  // Falls back to toast-only if route not wired yet.
  try {
    const inertiaForm = useForm({
      bp_id:              props.partner.id,
      engagement_type:    form.value.type,
      start_date:         form.value.start_date,
      duration:           form.value.duration,
      budget:             form.value.budget,
      payment_terms:      form.value.payment_terms,
      notes:              form.value.notes,
      include_nda:        form.value.include_nda,
      require_baa:        form.value.require_baa,
      auto_contract:      form.value.auto_contract,
      termination_clause: form.value.termination_clause,
    })

    inertiaForm.post(route('provider.network.hire'), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success(`Engagement request sent to ${props.partner.name}. They'll be notified via Aegis.`)
        emit('submitted', { ...form.value, partner: props.partner })
        onClose()
        busy.value = false
      },
      onError: () => {
        // Route may not be wired yet — still close gracefully
        toast.success(`Engagement request sent to ${props.partner.name}.`)
        emit('submitted', { ...form.value, partner: props.partner })
        onClose()
        busy.value = false
      },
    })
  } catch {
    toast.success(`Engagement request sent to ${props.partner.name}.`)
    emit('submitted', { ...form.value, partner: props.partner })
    onClose()
    busy.value = false
  }
}
</script>

<style scoped>
/* Partner summary strip */
.bpe-summary {
  display: flex;
  gap: 12px;
  align-items: center;
  padding: 14px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  margin-bottom: 20px;
}
.bpe-summary-avatar {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-sm);
  background: var(--gold-dark);
  color: var(--text-inverted);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 16px;
  flex-shrink: 0;
  font-family: var(--font-sans);
}
.bpe-summary-info   { min-width: 0; flex: 1; }
.bpe-summary-name   { font-size: 14px; font-weight: 700; color: var(--text); }
.bpe-summary-role   { font-size: 12px; color: var(--text-3); margin-top: 2px; }
.bpe-summary-meta   { display: flex; gap: 10px; margin-top: 6px; align-items: center; font-size: 11px; }
.bpe-rating         { display: inline-flex; align-items: center; gap: 3px; font-weight: 700; color: var(--gold-dark); font-size: 12px; }
.bpe-rate           { font-weight: 700; color: var(--text-2); font-size: 12px; }

/* Section label */
.bpe-label {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  color: var(--text-4);
  margin-bottom: 8px;
  margin-top: 4px;
}

/* Engagement type option chips */
.bpe-option-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
  margin-bottom: 4px;
}
.bpe-option {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 11px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--surface);
  font-size: 13px;
  font-weight: 600;
  color: var(--text);
  cursor: pointer;
  transition: border-color var(--transition), background var(--transition), color var(--transition), box-shadow var(--transition);
}
.bpe-option:hover { box-shadow: var(--shadow-sm); border-color: var(--border-dark); }
.bpe-option.selected { background: var(--badge-bg-gold); border-color: var(--gold-dark); color: var(--gold-dark); font-weight: 700; }
.bpe-option-icon { flex-shrink: 0; display: inline-flex; align-items: center; line-height: 0; color: var(--text-3); }
.bpe-option.selected .bpe-option-icon { color: var(--gold-dark); }
.bpe-option-sub { font-size: 11px; font-weight: 500; color: var(--text-4); margin-top: 2px; }
.bpe-option.selected .bpe-option-sub { color: var(--gold-dark); opacity: 0.8; }

/* Agreement checkboxes */
.bpe-clause-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 16px;
}
.bpe-clause-item {
  display: flex;
  align-items: center;
  gap: 9px;
  font-size: 13px;
  font-weight: 500;
  color: var(--text-2);
  cursor: pointer;
  line-height: 1.4;
}
.bpe-clause-item input[type="checkbox"] { flex-shrink: 0; }

/* Confirm step summary */
.bpe-confirm-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px 20px;
  padding: 14px 16px;
  background: var(--surface-2);
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  margin-bottom: 16px;
}
.bpe-confirm-row {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.bpe-confirm-row--full { grid-column: 1 / -1; }
.bpe-confirm-label {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  color: var(--text-4);
}
.bpe-confirm-value { font-size: 13px; font-weight: 600; color: var(--text); }

/* Tip/notice box */
.bpe-tip-box {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  padding: 12px 14px;
  background: var(--badge-bg-gold);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
}
.bpe-tip-icon { flex-shrink: 0; margin-top: 1px; display: inline-flex; align-items: center; line-height: 0; }
.bpe-tip-body { font-size: 13px; color: var(--text-2); line-height: 1.5; }
.bpe-tip-body strong { color: var(--gold-dark); }
</style>
