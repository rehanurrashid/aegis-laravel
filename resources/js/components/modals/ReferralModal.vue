<!--
  ReferralModal.vue — shared 4-step "Send Client Referral" modal.

  Reused across Provider Dashboard, Network, Referrals pages and any
  other surface that initiates a referral. Steps:

    1. Client       — pull from vault roster (zone=roster, not discharged)
                      or enter manually with HIPAA reminder
    2. Practitioner — pick from clinical network connections with live search
    3. Notes        — service format, urgency, cash pay, clinical notes,
                      attachment dropzone
    4. Review       — final HIPAA acknowledgement before send

  Roster items: provider's vault zone=roster, non-discharged, priority first
  Network:      clinical network_connections (connection_type=practitioner)

  Submits to provider.referrals.store; server stores extras in referral_meta
  and fans out activity to recipient + CS + SS feeds.

  Usage:
    <ReferralModal v-model="modals.newReferral" :roster="roster" :network="network" />
-->
<template>
  <AegisModal
    :model-value="modelValue"
    title="Send Client Referral"
    size="lg"
    @update:model-value="onUpdateOpen"
  >
    <!-- Step indicator -->
    <ol class="modal-steps">
      <li
        v-for="(label, i) in stepLabels"
        :key="label"
        class="modal-step"
        :class="{ 'is-active': step === i + 1, 'is-done': step > i + 1 }"
        @click="goToStep(i + 1)"
      >
        <div class="modal-step-num">{{ i + 1 }}</div>
        <span>{{ label }}</span>
      </li>
    </ol>

    <!-- ── STEP 1 — CLIENT ─────────────────────────────────────────── -->
    <section v-if="step === 1" class="referral-step">
      <div class="referral-source-chooser">
        <button
          type="button"
          class="referral-source-option"
          :class="{ 'is-selected': source === 'roster' }"
          :disabled="!roster.length"
          @click="source = 'roster'"
        >
          <div class="referral-source-icon"><AegisIcon name="lock" :size="20" /></div>
          <div class="referral-source-title">Pull from Client Roster</div>
          <div class="referral-source-sub">
            {{ roster.length
              ? `${roster.length} client${roster.length === 1 ? '' : 's'} in your practice records`
              : 'No roster entries found' }}
          </div>
        </button>

        <button
          type="button"
          class="referral-source-option"
          :class="{ 'is-selected': source === 'manual' }"
          @click="source = 'manual'"
        >
          <div class="referral-source-icon"><AegisIcon name="pencil" :size="20" /></div>
          <div class="referral-source-title">Enter Manually</div>
          <div class="referral-source-sub">
            Client not in roster — enter anonymized identifier
          </div>
        </button>
      </div>

      <!-- Roster picker -->
      <div v-if="source === 'roster' && roster.length" class="referral-roster">
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

        <div class="referral-roster-list">
          <button
            v-for="r in filteredRoster"
            :key="r.id"
            type="button"
            class="referral-roster-row"
            :class="{ 'is-selected': form.roster_item_id === r.id }"
            @click="selectRoster(r)"
          >
            <div class="referral-pt-avatar">{{ initials(r.client_name) }}</div>
            <div class="referral-pt-info">
              <div class="referral-pt-name">{{ r.client_name }}</div>
              <div class="referral-pt-meta">
                {{ [r.client_service, r.client_location].filter(Boolean).join(' · ') }}
              </div>
              <div v-if="r.client_notes" class="referral-pt-notes">
                {{ truncate(r.client_notes, 80) }}
              </div>
            </div>
            <span
              class="referral-pt-tag"
              :class="r.client_status === 'priority' ? 'referral-pt-tag--priority' : 'referral-pt-tag--active'"
            >
              <AegisIcon name="circle-dot" :size="9" />
              {{ r.client_status === 'priority' ? 'Priority' : 'Active' }}
            </span>
          </button>

          <div v-if="!filteredRoster.length" class="referral-empty">
            No matching clients. Try a different search, or
            <button type="button" class="referral-link" @click="source = 'manual'">use manual entry</button>.
          </div>
        </div>

        <div v-if="form.roster_item_id" class="referral-selected">
          <div class="referral-selected-title">
            <AegisIcon name="check" :size="13" />
            Client selected — fields pre-filled from roster
          </div>
          <div class="referral-selected-body">
            <strong>{{ form.client_name }}</strong>
            <template v-if="form.diagnosis"> · {{ form.diagnosis }}</template>
          </div>
          <button type="button" class="referral-selected-clear" @click="clearRoster">× Clear selection</button>
        </div>
      </div>

      <!-- Manual entry -->
      <div v-if="source === 'manual'" class="referral-manual">
        <div class="referral-hipaa-note">
          <strong>HIPAA reminder:</strong> use anonymized identifiers only
          (e.g., initials and age range). Do not include full names or DOBs.
        </div>
        <div class="form-group">
          <label class="form-label" for="ref-client">Client Identifier <span class="req">*</span></label>
          <input
            id="ref-client"
            v-model="form.client_name"
            type="text"
            class="form-input"
            placeholder="e.g. A.M. — Generalized Anxiety"
          />
          <div v-if="form.errors.client_name" class="form-error">{{ form.errors.client_name }}</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="ref-dx">Diagnosis / Reason for Care</label>
          <input
            id="ref-dx"
            v-model="form.diagnosis"
            type="text"
            class="form-input"
            placeholder="e.g. PTSD, Depression, Medication management"
          />
        </div>
      </div>
    </section>

    <!-- ── STEP 2 — PRACTITIONER ──────────────────────────────────── -->
    <section v-if="step === 2" class="referral-step">
      <div class="form-group">
        <label class="form-label">Referring to <span class="req">*</span></label>
        <input
          v-model="networkFilter"
          type="text"
          class="form-input"
          :placeholder="network.length
            ? `Search ${network.length} provider${network.length === 1 ? '' : 's'} in your network…`
            : 'No network connections — type a name to refer off-platform'"
          autocomplete="off"
        />
        <div v-if="!network.length" class="form-hint" style="color:var(--orange-dark)">
          No clinical connections found. You can still enter a name to send an off-platform referral.
        </div>
      </div>

      <div v-if="network.length" class="referral-network-list">
        <button
          v-for="n in filteredNetwork"
          :key="n.slug"
          type="button"
          class="referral-network-row"
          :class="{ 'is-selected': form.provider_slug === n.slug }"
          :disabled="!n.accepting"
          @click="selectNetwork(n)"
        >
          <div class="referral-pt-avatar">{{ n.initials }}</div>
          <div class="referral-pt-info">
            <div class="referral-pt-name">
              {{ n.display_name }}<span v-if="n.credentials" class="referral-pt-creds">, {{ n.credentials }}</span>
            </div>
            <div class="referral-pt-meta">
              {{ [n.specialty, n.location].filter(Boolean).join(' · ') }}
            </div>
          </div>
          <span class="referral-pt-tag" :class="n.accepting ? 'referral-pt-tag--active' : 'referral-pt-tag--closed'">
            {{ n.accepting ? 'Accepting' : 'Closed' }}
          </span>
        </button>

        <div v-if="!filteredNetwork.length" class="referral-empty">
          No matches in your network. Try a broader search.
        </div>
      </div>

      <div class="form-row" style="margin-top:14px">
        <div class="form-group">
          <label class="form-label">Specialty Needed</label>
          <select v-model="form.specialty" class="form-select">
            <option value="">Any specialty…</option>
            <option>Psychiatrist</option>
            <option>Psychologist</option>
            <option>Therapist / Counselor</option>
            <option>Neurologist</option>
            <option>Primary Care</option>
            <option>Addiction Specialist</option>
            <option>Other</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Coverage</label>
          <select v-model="form.coverage" class="form-select" @change="onCoverageChange">
            <option value="">Select coverage…</option>
            <option>Self-pay</option>
            <option>Blue Cross Blue Shield</option>
            <option>Aetna</option>
            <option>Cigna</option>
            <option>UnitedHealthcare</option>
            <option>Medicare</option>
            <option>Medicaid</option>
            <option value="other">Other (specify)</option>
          </select>
        </div>
      </div>
      <div v-if="form.coverage === 'other'" class="form-group">
        <input
          v-model="form.coverage_other"
          type="text"
          class="form-input"
          placeholder="Specify coverage or plan…"
        />
      </div>
    </section>

    <!-- ── STEP 3 — NOTES ─────────────────────────────────────────── -->
    <section v-if="step === 3" class="referral-step">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Reason for Referral <span class="req">*</span></label>
          <select v-model="form.reason" class="form-select" @change="onReasonChange">
            <option value="">Select reason…</option>
            <option>Specialist consultation</option>
            <option>Medication management</option>
            <option>Ongoing care continuity</option>
            <option>Crisis intervention needed</option>
            <option>Critical incident practice closure</option>
            <option>Client preference</option>
            <option value="other">Other (specify)</option>
          </select>
          <div v-if="form.errors.reason" class="form-error">{{ form.errors.reason }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Urgency Level</label>
          <select v-model="form.urgency" class="form-select">
            <option value="routine">Routine (within 30 days)</option>
            <option value="soon">Soon (48–72 hours)</option>
            <option value="urgent">Urgent (within 24 hours)</option>
            <option value="critical">Critical (immediate)</option>
          </select>
        </div>
      </div>
      <div v-if="form.reason === 'other'" class="form-group">
        <input
          v-model="form.reason_other"
          type="text"
          class="form-input"
          placeholder="Specify reason…"
        />
      </div>

      <div class="form-group">
        <label class="form-label">Clinical Notes <span style="color:var(--text-4);font-weight:500">(optional)</span></label>
        <textarea
          v-model="form.notes"
          class="form-textarea"
          rows="3"
          placeholder="Relevant clinical context — diagnosis, medications, safety plan, next-step coordination…"
        ></textarea>
      </div>

      <div class="form-group">
        <label class="form-label">Attach Documents <span style="color:var(--text-4);font-weight:500">(optional)</span></label>
        <AegisDropzone
          accept=".pdf,.jpg,.png,.doc,.docx"
          :multiple="true"
          hint="Clinical notes, safety plan, or release authorization · PDF, JPG, PNG, DOC up to 10 MB"
          @files="form.attachments = $event"
        />
        <div v-if="form.attachments && form.attachments.length" class="referral-attach-list">
          <span v-for="(f, i) in form.attachments" :key="i" class="referral-attach-item">
            <AegisIcon name="file-text" :size="12" /> {{ f.name }}
          </span>
        </div>
      </div>
    </section>

    <!-- ── STEP 4 — REVIEW ─────────────────────────────────────────── -->
    <section v-if="step === 4" class="referral-step referral-review">
      <h3 class="referral-review-title">Review your referral</h3>
      <dl class="referral-review-list">
        <div>
          <dt>Client</dt>
          <dd>
            {{ form.client_name || '—' }}<template v-if="form.diagnosis"> · {{ form.diagnosis }}</template>
          </dd>
        </div>
        <div>
          <dt>Referring To</dt>
          <dd>{{ selectedNetworkLabel || form.provider_name_manual || '—' }}</dd>
        </div>
        <div>
          <dt>Specialty</dt>
          <dd>{{ form.specialty || '—' }}</dd>
        </div>
        <div>
          <dt>Coverage</dt>
          <dd>{{ resolvedCoverage || '—' }}</dd>
        </div>
        <div>
          <dt>Reason</dt>
          <dd>{{ resolvedReason || '—' }}</dd>
        </div>
        <div>
          <dt>Urgency</dt>
          <dd>{{ urgencyLabel || '—' }}</dd>
        </div>
        <div class="is-full">
          <dt>Clinical Notes</dt>
          <dd>{{ form.notes || '—' }}</dd>
        </div>
      </dl>

      <label class="referral-hipaa-ack">
        <input v-model="form.hipaa_ack" type="checkbox" class="form-check-input" />
        <span>
          I confirm this referral is HIPAA-compliant and the client has been notified per the Continuity Plan.
        </span>
      </label>
      <div v-if="form.errors.hipaa_ack" class="form-error">{{ form.errors.hipaa_ack }}</div>
    </section>

    <template #footer>
      <button
        type="button"
        class="btn btn-outline"
        :disabled="form.processing"
        @click="step === 1 ? onClose() : (step--)"
      >{{ step === 1 ? 'Cancel' : 'Back' }}</button>

      <button
        v-if="step < 4"
        type="button"
        class="btn btn-primary"
        :disabled="!canAdvance"
        @click="step++"
      >
        Continue <AegisIcon name="chevron-right" :size="12" />
      </button>

      <button
        v-else
        type="button"
        class="btn btn-primary"
        :disabled="form.processing || !form.hipaa_ack"
        @click="submit"
      >
        <AegisIcon name="send" :size="13" />
        {{ form.processing ? 'Sending…' : 'Send Referral' }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useToast } from '@/composables/useToast'
import AegisDropzone from '@/components/ui/AegisDropzone.vue'
import { scanAndEnhance, syncFormEnhancements } from '@/plugins/FormEnhancerPlugin'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  roster:     { type: Array,   default: () => [] },
  network:    { type: Array,   default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

const toast = useToast()

const stepLabels = ['Client', 'Practitioner', 'Notes', 'Review']
const step          = ref(1)
watch(step, async () => { await nextTick(); scanAndEnhance(); syncFormEnhancements() })
const source        = ref(props.roster.length ? 'roster' : 'manual')
const rosterFilter  = ref('')
const networkFilter = ref('')

const form = useForm({
  // Step 1 — client
  roster_item_id:  null,
  client_name:     '',
  diagnosis:       '',
  // Step 2 — practitioner
  provider_slug:   null,
  provider_id:     null,
  provider_name_manual: '',
  specialty:       '',
  coverage:        '',
  coverage_other:  '',
  // Step 3 — notes
  reason:          '',
  reason_other:    '',
  urgency:         'routine',
  notes:           '',
  attachments:     [],
  // Step 4 — review
  hipaa_ack:       false,
})

// If user types in network filter without selecting a row, treat the
// raw text as an off-platform referral name. If their text matches a
// real network row exactly, auto-bind to its slug.
watch(networkFilter, (q) => {
  if (!q) {
    form.provider_slug = null
    form.provider_name_manual = ''
    return
  }
  const exact = props.network.find(
    (n) => n.display_name.toLowerCase() === q.toLowerCase() ||
           `${n.display_name}, ${n.credentials}`.toLowerCase() === q.toLowerCase(),
  )
  if (exact) {
    form.provider_slug = exact.slug
    form.provider_id   = exact.id ?? null
    form.provider_name_manual = ''
  } else {
    form.provider_slug = null
    form.provider_name_manual = q
  }
})

// ── Computed ─────────────────────────────────────────────────────────
const filteredRoster = computed(() => {
  const q = rosterFilter.value.trim().toLowerCase()
  if (!q) return props.roster
  return props.roster.filter((r) =>
    `${r.client_name} ${r.client_service ?? ''} ${r.client_location ?? ''}`
      .toLowerCase()
      .includes(q),
  )
})

const filteredNetwork = computed(() => {
  const q = networkFilter.value.trim().toLowerCase()
  if (!q) return props.network
  return props.network.filter((n) =>
    `${n.display_name} ${n.credentials ?? ''} ${n.specialty ?? ''} ${n.location ?? ''}`
      .toLowerCase()
      .includes(q),
  )
})

const selectedNetworkLabel = computed(() => {
  const n = props.network.find((x) => x.slug === form.provider_slug)
  return n ? `${n.display_name}${n.credentials ? ', ' + n.credentials : ''}` : ''
})

const resolvedReason = computed(() =>
  form.reason === 'other' ? (form.reason_other || 'Other') : form.reason
)
const resolvedCoverage = computed(() =>
  form.coverage === 'other' ? (form.coverage_other || 'Other') : form.coverage
)
const urgencyLabel = computed(() => ({
  routine:  'Routine (within 30 days)',
  soon:     'Soon (48–72 hours)',
  urgent:   'Urgent (within 24 hours)',
  critical: 'Critical (immediate)',
}[form.urgency] ?? form.urgency))

const canAdvance = computed(() => {
  if (step.value === 1) return form.client_name.trim().length > 0
  if (step.value === 2) return !!form.provider_slug || form.provider_name_manual.trim().length > 0
  if (step.value === 3) {
    const reasonOk = form.reason && (form.reason !== 'other' || form.reason_other.trim().length > 0)
    return !!reasonOk
  }
  return true
})

// ── Handlers ─────────────────────────────────────────────────────────
function initials(name) {
  if (!name) return '·'
  const upper = name.match(/[A-Z]/g) ?? []
  return upper.slice(0, 2).join('') || name.slice(0, 2).toUpperCase()
}
function truncate(s, n) { return s && s.length > n ? s.slice(0, n) + '…' : s }

function selectRoster(r) {
  form.roster_item_id = r.id
  form.client_name    = r.client_name
  form.diagnosis      = r.client_service ?? ''
}
function clearRoster() {
  form.roster_item_id = null
  form.client_name    = ''
  form.diagnosis      = ''
}
function selectNetwork(n) {
  form.provider_slug = n.slug
  form.provider_id   = n.id ?? null
  form.provider_name_manual = ''
  networkFilter.value = `${n.display_name}${n.credentials ? ', ' + n.credentials : ''}`
}
function goToStep(n) { if (n < step.value) step.value = n }
function onReasonChange()   { if (form.reason !== 'other')   form.reason_other = '' }
function onCoverageChange() { if (form.coverage !== 'other') form.coverage_other = '' }

function onUpdateOpen(v) { if (!v) onClose() }
function onClose() {
  emit('update:modelValue', false)
  setTimeout(() => {
    step.value = 1
    form.reset()
    source.value = props.roster.length ? 'roster' : 'manual'
    rosterFilter.value = ''
    networkFilter.value = ''
  }, 220)
}

function submit() {
  form.transform((data) => ({
    ...data,
    reason:   data.reason === 'other'   ? data.reason_other   : data.reason,
    coverage: data.coverage === 'other' ? data.coverage_other : data.coverage,
  })).post(route('provider.referrals.store'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Referral sent.')
      onClose()
    },
    onError: () => toast.error('Could not send referral — please review the form.'),
  })
}
</script>

<style scoped>
.modal-steps {
  display: flex;
  align-items: center;
  gap: 6px;
  list-style: none;
  padding: 0;
  margin: 0 0 14px 0;
  flex-wrap: wrap;
}
.modal-step {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-4);
  cursor: pointer;
  padding: 4px 8px;
  border-radius: var(--radius-sm);
  transition: color 180ms, background 180ms;
}
.modal-step.is-active { color: var(--gold-dark);  background: var(--badge-bg-gold); }
.modal-step.is-done   { color: var(--green-dark); }
.modal-step-num {
  width: 22px; height: 22px;
  border-radius: var(--radius-full);
  background: var(--surface-2);
  color: var(--text-3);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 11px;
}
.modal-step.is-active .modal-step-num { background: var(--gold-dark);  color: var(--text-inverted); }
.modal-step.is-done   .modal-step-num { background: var(--green-dark); color: var(--text-inverted); }

.referral-source-chooser { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
.referral-source-option {
  border: 1.5px solid var(--border);
  background: var(--surface);
  border-radius: var(--radius-sm);
  padding: 12px 14px;
  cursor: pointer;
  text-align: left;
  transition: box-shadow 180ms, border-color 180ms, background 180ms;
}
.referral-source-option:hover:not(:disabled) { box-shadow: var(--shadow-sm); }
.referral-source-option.is-selected { border-color: var(--gold-dark); background: var(--badge-bg-gold); }
.referral-source-option:disabled { opacity: 0.55; cursor: not-allowed; }
.referral-source-icon  { margin-bottom: 6px; color: var(--text-3); display: inline-flex; align-items: center; }
.referral-source-option.is-selected .referral-source-icon { color: var(--gold-dark); }
.referral-source-title { font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 3px; }
.referral-source-sub   { font-size: 12px; color: var(--text-3); line-height: 1.4; }

.referral-roster-list  { display: flex; flex-direction: column; gap: 6px; margin-top: 4px; }
.referral-roster-row,
.referral-network-row  {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--surface);
  cursor: pointer;
  text-align: left;
  transition: box-shadow 180ms, border-color 180ms, background 180ms;
}
.referral-roster-row:hover:not(:disabled),
.referral-network-row:hover:not(:disabled) { box-shadow: var(--shadow-sm); }
.referral-roster-row.is-selected,
.referral-network-row.is-selected { border-color: var(--gold-dark); background: var(--badge-bg-gold); }
.referral-roster-row:disabled,
.referral-network-row:disabled    { opacity: 0.55; cursor: not-allowed; }

.referral-network-list { display: flex; flex-direction: column; gap: 6px; }

.referral-pt-avatar {
  width: 34px; height: 34px;
  border-radius: var(--radius-full);
  background: var(--gold-dark);
  color: var(--text-inverted);
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700;
  flex-shrink: 0;
}
.referral-pt-info  { flex: 1; min-width: 0; }
.referral-pt-name  { font-size: 13px; font-weight: 700; color: var(--text); }
.referral-pt-meta  { font-size: 12px; color: var(--text-3); }
.referral-pt-notes { font-size: 11px; color: var(--text-4); margin-top: 2px; line-height: 1.4; }
.referral-pt-creds { font-weight: 500; color: var(--text-3); }
.referral-pt-tag {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 10px; font-weight: 700;
  padding: 2px 8px;
  border-radius: var(--radius-full);
  text-transform: uppercase;
  letter-spacing: 0.4px;
  white-space: nowrap;
}
.referral-pt-tag--priority { background: var(--red-light);   color: var(--red-dark); }
.referral-pt-tag--active   { background: var(--green-light); color: var(--green-dark); }
.referral-pt-tag--closed   { background: var(--surface-3);   color: var(--text-3); }

.referral-empty {
  padding: 18px;
  text-align: center;
  background: var(--surface-2);
  border: 1px dashed var(--border);
  border-radius: var(--radius-sm);
  font-size: 13px;
  color: var(--text-3);
  margin-top: 6px;
}
.referral-link { color: var(--gold-dark); font-weight: 700; background: none; border: none; cursor: pointer; padding: 0; }
.referral-link:hover { text-decoration: underline; }

.referral-selected {
  margin-top: 12px;
  background: var(--badge-bg-gold);
  border: 1.5px solid var(--gold-dark);
  border-radius: var(--radius-sm);
  padding: 11px 14px;
}
.referral-selected-title {
  font-size: 12px; font-weight: 700;
  color: var(--gold-dark);
  margin-bottom: 5px;
  display: flex; align-items: center; gap: 5px;
}
.referral-selected-body  { font-size: 13px; color: var(--text-2); line-height: 1.55; }
.referral-selected-clear {
  background: none; border: none;
  font-size: 11px; color: var(--text-4);
  cursor: pointer;
  margin-top: 6px;
  padding: 0;
  text-decoration: underline;
}

.referral-hipaa-note {
  background: var(--blue-light);
  border-left: 3px solid var(--blue-dark);
  border-radius: var(--radius-sm);
  padding: 10px 14px;
  margin-bottom: 14px;
  font-size: 12px;
  color: var(--blue-dark);
  line-height: 1.5;
}

.referral-review-title {
  font-size: 13px; font-weight: 700;
  color: var(--text);
  margin: 0 0 12px 0;
}
.referral-review-list {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px 24px;
  margin: 0 0 14px 0;
}
.referral-review-list > div { display: flex; flex-direction: column; }
.referral-review-list > div.is-full { grid-column: 1 / -1; }
.referral-review-list dt {
  font-size: 11px; font-weight: 700;
  color: var(--text-3);
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-bottom: 3px;
}
.referral-review-list dd {
  font-size: 13px; font-weight: 600;
  color: var(--text);
  line-height: 1.4;
  padding-bottom: 8px;
  border-bottom: 1px solid var(--border);
  margin: 0;
}
.referral-review-list > div.is-full dd { font-weight: 500; color: var(--text-2); line-height: 1.55; }

.referral-hipaa-ack {
  display: flex; align-items: flex-start; gap: 9px;
  padding-top: 6px;
  font-size: 13px;
  color: var(--text-2);
  cursor: pointer;
  line-height: 1.5;
}
.referral-hipaa-ack input { margin-top: 2px; }

.referral-attach-list { margin-top: 8px; display: flex; flex-wrap: wrap; gap: 6px; }
.referral-attach-item {
  display: inline-flex; align-items: center; gap: 5px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 4px 8px;
  font-size: 12px;
  color: var(--text-2);
}
</style>
