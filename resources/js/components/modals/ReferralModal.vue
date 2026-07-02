<!--
  ReferralModal.vue — universal "Send Client Referral" 4-step modal.

  Steps:
    1. Client     — pull from vault roster or enter manually
    2. Practitioner — pick from clinical network connections
    3. Notes      — service format, cash pay, urgency, note
    4. Review     — final confirmation

  Open state: ui.openModal('referralModal') → isOpen cached as referralModalOpen.
  Submits to provider.referrals.store.
-->
<template>
  <AegisModal
    :model-value="referralModalOpen"
    title="Send Client Referral"
    size="lg"
    @update:model-value="onUpdateOpen"
  >
    <!-- ── Step indicator ── -->
    <div class="modal-steps">
      <div
        v-for="(label, i) in stepLabels"
        :key="label"
        class="modal-step"
        :class="{ active: step === i + 1, done: step > i + 1 }"
        @click="goToStep(i + 1)"
      >
        <div class="modal-step-num">
          <AegisIcon v-if="step > i + 1" name="check" :size="11" />
          <template v-else>{{ i + 1 }}</template>
        </div>
        <span class="modal-step-label">{{ label }}</span>
        <div v-if="i < stepLabels.length - 1" class="modal-step-divider" />
      </div>
    </div>

    <!-- ── STEP 1 — CLIENT ── -->
    <section v-if="step === 1" class="rfm-step">
      <div class="rfm-source-chooser">
        <button
          type="button"
          class="rfm-source-option"
          :class="{ 'is-selected': source === 'roster' }"
          :disabled="!roster.length"
          @click="source = 'roster'"
        >
          <div class="rfm-source-icon"><AegisIcon name="lock" :size="20" /></div>
          <div class="rfm-source-title">Pull from Client Roster</div>
          <div class="rfm-source-sub">
            {{ roster.length
              ? `${roster.length} client${roster.length === 1 ? '' : 's'} in your practice records`
              : 'No roster entries found' }}
          </div>
        </button>

        <button
          type="button"
          class="rfm-source-option"
          :class="{ 'is-selected': source === 'manual' }"
          @click="source = 'manual'"
        >
          <div class="rfm-source-icon"><AegisIcon name="pencil" :size="20" /></div>
          <div class="rfm-source-title">Enter Manually</div>
          <div class="rfm-source-sub">Client not in roster — enter anonymized identifier</div>
        </button>
      </div>

      <!-- Roster picker -->
      <div v-if="source === 'roster'" class="rfm-roster">
        <div class="form-group">
          <label class="form-label">Search Roster</label>
          <input
            v-model="rosterFilter"
            type="text"
            class="form-input"
            placeholder="Type name, service, or location…"
            autocomplete="off"
          />
        </div>

        <div class="rfm-list">
          <button
            v-for="r in filteredRoster"
            :key="r.id"
            type="button"
            class="rfm-row"
            :class="{ 'is-selected': form.roster_item_id === r.id }"
            @click="selectRoster(r)"
          >
            <div class="rfm-avatar">{{ initials(r.client_name || r.title) }}</div>
            <div class="rfm-info">
              <div class="rfm-name">{{ r.client_name || r.title }}</div>
              <div class="rfm-meta">
                {{ [r.client_service || r.category, r.client_location || r.sub_label].filter(Boolean).join(' · ') }}
              </div>
            </div>
            <span class="rfm-tag" :class="r.status === 'priority' ? 'rfm-tag--priority' : 'rfm-tag--active'">
              {{ r.status === 'priority' ? 'Priority' : 'Active' }}
            </span>
          </button>

          <div v-if="!filteredRoster.length" class="rfm-empty">
            No matching clients.
            <button type="button" class="rfm-link" @click="source = 'manual'">Use manual entry</button>
          </div>
        </div>
      </div>

      <!-- Manual entry -->
      <div v-if="source === 'manual'" class="rfm-manual">
        <div class="rfm-hipaa">
          <AegisIcon name="shield" :size="14" />
          <span><strong>HIPAA reminder:</strong> use anonymized identifiers only (e.g. initials and age range). Do not include full names or DOBs.</span>
        </div>
        <div class="form-group">
          <label class="form-label" for="rfm-client">Client Identifier <span class="req">*</span></label>
          <input
            id="rfm-client"
            v-model="form.client_name"
            type="text"
            class="form-input"
            placeholder="e.g. A.M. — Generalized Anxiety"
          />
          <div v-if="showErrors && !form.client_name.trim()" class="form-error">Client identifier is required.</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="rfm-dx">Diagnosis / Reason for Care</label>
          <input
            id="rfm-dx"
            v-model="form.diagnosis"
            type="text"
            class="form-input"
            placeholder="e.g. PTSD, Depression, Medication management"
          />
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="rfm-city">City</label>
            <input id="rfm-city" v-model="form.city" type="text" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label" for="rfm-state">State</label>
            <input id="rfm-state" v-model="form.state" type="text" class="form-input" />
          </div>
        </div>
      </div>
    </section>

    <!-- ── STEP 2 — PRACTITIONER ── -->
    <section v-if="step === 2" class="rfm-step">
      <div class="form-group">
        <label class="form-label">Search your clinical network</label>
        <input
          v-model="networkFilter"
          type="text"
          class="form-input"
          placeholder="Name, specialty, or location…"
          autocomplete="off"
        />
      </div>

      <div class="rfm-list">
        <button
          v-for="n in filteredNetwork"
          :key="n.slug"
          type="button"
          class="rfm-row"
          :class="{ 'is-selected': form.provider_slug === n.slug, 'is-disabled': !n.accepting }"
          :disabled="!n.accepting"
          @click="selectNetwork(n)"
        >
          <div class="rfm-avatar" :style="n.avatar_url ? { backgroundImage: `url(${n.avatar_url})`, backgroundSize: 'cover', backgroundPosition: 'center' } : {}">
            <template v-if="!n.avatar_url">{{ n.initials || initials(n.display_name) }}</template>
          </div>
          <div class="rfm-info">
            <div class="rfm-name">
              {{ n.display_name }}<span v-if="n.credentials" class="rfm-creds">, {{ n.credentials }}</span>
            </div>
            <div class="rfm-meta">{{ [n.specialty, n.location].filter(Boolean).join(' · ') }}</div>
          </div>
          <AegisBadge
            :label="n.accepting ? 'Accepting' : 'Not accepting'"
            :variant="n.accepting ? 'green' : 'neutral'"
          />
        </button>

        <div v-if="!filteredNetwork.length" class="rfm-empty">
          No matches in your network. Try a broader search.
        </div>
      </div>

      <div v-if="showErrors && !form.provider_slug" class="form-error" style="margin-top:8px">Please select a provider to refer to.</div>
    </section>

    <!-- ── STEP 3 — NOTES ── -->
    <section v-if="step === 3" class="rfm-step">
      <div class="form-group">
        <label class="form-label">Service format</label>
        <div class="rfm-seg">
          <button
            v-for="f in serviceFormats"
            :key="f.value"
            type="button"
            class="rfm-seg-btn"
            :class="{ 'is-active': form.service_format === f.value }"
            @click="form.service_format = f.value"
          >{{ f.label }}</button>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Urgency</label>
        <div class="rfm-seg">
          <button
            v-for="u in urgencies"
            :key="u.value"
            type="button"
            class="rfm-seg-btn"
            :class="{ 'is-active': form.urgency === u.value }"
            @click="form.urgency = u.value"
          >{{ u.label }}</button>
        </div>
      </div>

      <div class="setting-row">
        <div class="setting-info">
          <strong>Cash pay accepted</strong>
          <span>Client is willing to self-pay if insurance isn't accepted.</span>
        </div>
        <AegisToggle v-model="form.accepts_cash" />
      </div>

      <div class="form-group" style="margin-top:14px">
        <label class="form-label" for="rfm-note">Note to receiving provider</label>
        <textarea
          id="rfm-note"
          v-model="form.note"
          class="form-textarea"
          rows="4"
          placeholder="Relevant history, treatment preferences, scheduling notes…"
        ></textarea>
      </div>
    </section>

    <!-- ── STEP 4 — REVIEW ── -->
    <section v-if="step === 4" class="rfm-step">
      <div class="rfm-review-title">Review your referral</div>

      <dl class="rfm-review-list">
        <div class="rfm-review-row">
          <dt>Client</dt>
          <dd>{{ form.client_name || '—' }}</dd>
        </div>
        <div v-if="form.diagnosis" class="rfm-review-row">
          <dt>Reason / Diagnosis</dt>
          <dd>{{ form.diagnosis }}</dd>
        </div>
        <div class="rfm-review-row">
          <dt>Referring to</dt>
          <dd>{{ selectedNetworkLabel || '—' }}</dd>
        </div>
        <div class="rfm-review-row">
          <dt>Format</dt>
          <dd>{{ serviceFormats.find(f => f.value === form.service_format)?.label ?? '—' }}</dd>
        </div>
        <div class="rfm-review-row">
          <dt>Urgency</dt>
          <dd :class="form.urgency === 'urgent' ? 'rfm-urgent' : form.urgency === 'soon' ? 'rfm-soon' : ''">
            {{ urgencies.find(u => u.value === form.urgency)?.label ?? '—' }}
          </dd>
        </div>
        <div class="rfm-review-row">
          <dt>Cash pay</dt>
          <dd>{{ form.accepts_cash ? 'Accepted' : 'Not noted' }}</dd>
        </div>
        <div v-if="form.note" class="rfm-review-row rfm-review-note-row">
          <dt>Note</dt>
          <dd class="rfm-review-note">{{ form.note }}</dd>
        </div>
      </dl>

      <div class="setting-row" style="margin-top:16px">
        <div class="setting-info">
          <strong>Client consents to record sharing</strong>
          <span>Required before any clinical records are released.</span>
        </div>
        <AegisToggle v-model="form.client_consents" />
      </div>

      <div v-if="form.errors.submit" class="form-error" style="margin-top:10px">{{ form.errors.submit }}</div>
    </section>

    <!-- ── Footer ── -->
    <template #footer>
      <button
        type="button"
        class="btn btn-outline"
        :disabled="form.processing"
        @click="step === 1 ? onClose() : prev()"
      >{{ step === 1 ? 'Cancel' : 'Back' }}</button>

      <button
        v-if="step < 4"
        type="button"
        class="btn btn-primary"
        @click="advance"
      >Continue</button>

      <button
        v-else
        type="button"
        class="btn btn-primary"
        :disabled="form.processing || !form.client_consents"
        @click="submit"
      >{{ form.processing ? 'Sending…' : 'Send Referral' }}</button>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisToggle from '@/components/ui/AegisToggle.vue'

const props = defineProps({
  modelValue:    { type: Boolean, default: undefined }, // v-model pattern (Dashboard)
  roster:        { type: Array,   default: () => [] },
  network:       { type: Array,   default: () => [] },
  preselectSlug: { type: String,  default: '' },        // pre-select a practitioner on open
})

const emit = defineEmits(['update:modelValue'])

const { isOpen, closeModal } = useModal()
const toast = useToast()

// Unified open state: v-model takes precedence when provided, otherwise store-driven.
const referralModalOpen = computed(() =>
  props.modelValue !== undefined ? props.modelValue : isOpen('referralModal').value
)

const stepLabels = ['Client', 'Practitioner', 'Notes', 'Review']
const step       = ref(1)
const showErrors = ref(false)

const source        = ref(props.roster.length ? 'roster' : 'manual')
const rosterFilter  = ref('')
const networkFilter = ref('')

const serviceFormats = [
  { value: 'in_person', label: 'In-person' },
  { value: 'online',    label: 'Online' },
  { value: 'any',       label: 'No preference' },
]
const urgencies = [
  { value: 'routine', label: 'Routine' },
  { value: 'soon',    label: 'Soon' },
  { value: 'urgent',  label: 'Urgent' },
]

const form = useForm({
  roster_item_id:  null,
  client_name:     '',
  diagnosis:       '',
  city:            '',
  state:           '',
  provider_slug:   null,
  provider_id:     null,
  service_format:  'any',
  urgency:         'routine',
  accepts_cash:    false,
  note:            '',
  client_consents: false,
})

const filteredRoster = computed(() => {
  const q = rosterFilter.value.trim().toLowerCase()
  if (!q) return props.roster
  return props.roster.filter((r) => {
    const name = r.client_name || r.title || ''
    const svc  = r.client_service || r.category || ''
    const loc  = r.client_location || r.sub_label || ''
    return `${name} ${svc} ${loc}`.toLowerCase().includes(q)
  })
})

const filteredNetwork = computed(() => {
  const q = networkFilter.value.trim().toLowerCase()
  if (!q) return props.network
  return props.network.filter((n) =>
    `${n.display_name} ${n.specialty ?? ''} ${n.location ?? ''}`.toLowerCase().includes(q),
  )
})

const selectedNetworkLabel = computed(() => {
  const n = props.network.find((x) => x.slug === form.provider_slug)
  return n ? `${n.display_name}${n.credentials ? ', ' + n.credentials : ''}` : ''
})

const canAdvance = computed(() => {
  if (step.value === 1) return form.client_name.trim().length > 0
  if (step.value === 2) return !!form.provider_slug
  if (step.value === 3) return !!form.service_format && !!form.urgency
  return true
})

function selectRoster(r) {
  form.roster_item_id = r.id
  form.client_name    = r.client_name || r.title || ''
  form.diagnosis      = r.client_service || r.category || ''
  form.city           = r.client_city  || ''
  form.state          = r.client_state || ''
}

function selectNetwork(n) {
  form.provider_slug = n.slug
  form.provider_id   = n.id ?? null
}

function goToStep(n) {
  if (n < step.value) { showErrors.value = false; step.value = n }
}

function advance() {
  if (!canAdvance.value) { showErrors.value = true; return }
  showErrors.value = false
  step.value++
}

function prev() {
  showErrors.value = false
  step.value--
}

function initials(name) {
  if (!name) return '?'
  const upper = name.match(/[A-Z]/g) ?? []
  return upper.slice(0, 2).join('') || name.slice(0, 2).toUpperCase()
}

function onUpdateOpen(v) { if (!v) onClose() }

// When modal opens with a preselectSlug, pre-select the practitioner in the
// background but stay on step 1 (Client) — the user must still fill client
// info before advancing. Step 2 will show the practitioner already highlighted.
watch(referralModalOpen, (open) => {
  if (open && props.preselectSlug) {
    const match = props.network.find((n) => n.slug === props.preselectSlug)
    if (match) selectNetwork(match)
  }
})

function onClose() {
  if (props.modelValue !== undefined) {
    emit('update:modelValue', false)
  } else {
    closeModal('referralModal')
  }
  setTimeout(() => {
    step.value       = 1
    showErrors.value = false
    form.reset()
    rosterFilter.value  = ''
    networkFilter.value = ''
    source.value = props.roster.length ? 'roster' : 'manual'
  }, 200)
}

function submit() {
  form.post(route('provider.referrals.store'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Referral sent.')
      onClose()
    },
    onError: () => toast.error('Could not send referral. Please check all fields.'),
  })
}
</script>

<style scoped>
/* ── Step ── */
.rfm-step { display: flex; flex-direction: column; gap: 14px; }

/* ── Source chooser (step 1) ── */
.rfm-source-chooser {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 4px;
}
.rfm-source-option {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  padding: 14px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  cursor: pointer;
  text-align: left;
  transition: border-color var(--transition), background var(--transition);
}
.rfm-source-option:hover:not(:disabled) {
  border-color: var(--gold-dark);
  background: var(--surface-2);
}
.rfm-source-option.is-selected {
  border-color: var(--gold-dark);
  background: var(--badge-bg-gold);
}
.rfm-source-option:disabled { opacity: 0.45; cursor: not-allowed; }
.rfm-source-icon {
  width: 34px; height: 34px;
  border-radius: var(--radius-sm);
  background: var(--badge-bg-gold);
  color: var(--gold-dark);
  display: flex;
  align-items: center;
  justify-content: center;
}
.rfm-source-title { font-size: 13px; font-weight: 700; color: var(--text); }
.rfm-source-sub   { font-size: 11.5px; color: var(--text-3); line-height: 1.4; }

/* ── Shared list (roster + network) ── */
.rfm-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
  max-height: 280px;
  overflow-y: auto;
  padding-right: 2px;
}
.rfm-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  cursor: pointer;
  text-align: left;
  transition: border-color var(--transition), background var(--transition);
}
.rfm-row:hover:not(:disabled) {
  border-color: var(--gold-dark);
  background: var(--surface-2);
}
.rfm-row.is-selected {
  border-color: var(--gold-dark);
  background: var(--badge-bg-gold);
}
.rfm-row.is-disabled { opacity: 0.5; cursor: not-allowed; }

/* Avatar */
.rfm-avatar {
  width: 36px; height: 36px;
  border-radius: var(--radius-sm);
  background: var(--gold-dark);
  color: var(--text-inverted);
  font-family: var(--font-serif);
  font-weight: 700;
  font-size: 13px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* Info block */
.rfm-info  { flex: 1; min-width: 0; }
.rfm-name  { font-size: 13px; font-weight: 600; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rfm-creds { font-weight: 400; color: var(--text-3); }
.rfm-meta  { font-size: 11.5px; color: var(--text-3); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Tag (roster priority/active) */
.rfm-tag {
  font-size: 10px;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: var(--radius-full);
  flex-shrink: 0;
}
.rfm-tag--active   { background: var(--badge-bg-gold); color: var(--gold-dark); }
.rfm-tag--priority { background: var(--red-light); color: var(--red-dark); }

/* Empty state */
.rfm-empty {
  padding: 24px;
  text-align: center;
  font-size: 13px;
  color: var(--text-3);
}
.rfm-link {
  background: none;
  border: none;
  color: var(--gold-dark);
  font-weight: 600;
  cursor: pointer;
  padding: 0;
  text-decoration: underline;
}

/* HIPAA note */
.rfm-hipaa {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 10px 12px;
  border-radius: var(--radius-sm);
  background: var(--badge-bg-gold);
  border-left: 3px solid var(--gold-dark);
  font-size: 12px;
  color: var(--text-2);
  line-height: 1.5;
  margin-bottom: 16px;
}
.rfm-hipaa svg { flex-shrink: 0; color: var(--gold-dark); margin-top: 1px; }

/* Manual entry */
.rfm-manual { display: flex; flex-direction: column; gap: 0; }
.rfm-manual .form-group { margin-bottom: 14px; }
.rfm-manual .form-group:last-child { margin-bottom: 0; }

/* Roster */
.rfm-roster { display: flex; flex-direction: column; gap: 12px; }

/* Segmented toggle (format / urgency) */
.rfm-seg {
  display: inline-flex;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  overflow: hidden;
  gap: 0;
}
.rfm-seg-btn {
  padding: 7px 18px;
  font-size: 12.5px;
  font-weight: 500;
  color: var(--text-3);
  background: var(--surface);
  border: none;
  border-right: 1px solid var(--border);
  cursor: pointer;
  transition: background var(--transition), color var(--transition);
  white-space: nowrap;
}
.rfm-seg-btn:last-child  { border-right: none; }
.rfm-seg-btn:hover:not(.is-active) { background: var(--surface-2); color: var(--text); }
.rfm-seg-btn.is-active {
  background: var(--gold-dark);
  color: var(--text-inverted);
  font-weight: 700;
}

/* Review (step 4) */
.rfm-review-title {
  font-family: var(--font-serif);
  font-size: 15px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 12px;
}
.rfm-review-list {
  display: flex;
  flex-direction: column;
  gap: 0;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
}
.rfm-review-row {
  display: flex;
  align-items: baseline;
  gap: 12px;
  padding: 9px 14px;
  border-bottom: 1px solid var(--border);
  font-size: 13px;
}
.rfm-review-row:last-child { border-bottom: none; }
.rfm-review-row dt {
  width: 140px;
  flex-shrink: 0;
  font-weight: 600;
  color: var(--text-3);
  font-size: 12px;
}
.rfm-review-row dd { color: var(--text); flex: 1; }
.rfm-review-note-row { align-items: flex-start; }
.rfm-review-note {
  font-size: 12.5px;
  color: var(--text-2);
  line-height: 1.5;
  white-space: pre-wrap;
}
.rfm-urgent { color: var(--red-dark); font-weight: 700; }
.rfm-soon   { color: var(--orange-dark); font-weight: 700; }
</style>
