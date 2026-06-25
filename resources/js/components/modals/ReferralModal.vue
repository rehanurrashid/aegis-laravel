<!--
  ReferralModal.vue — universal "Send Client Referral" 4-step modal.

  Replaces _shared/modals/referral_modal.php. Steps:
    1. Client     — pull from roster (vault) or enter manually
    2. Practitioner — pick from clinical network connections
    3. Notes      — service format, cash pay, urgency, note
    4. Review     — final confirmation

  Roster items come from the practitioner's vault (zone=roster, not
  discharged). Network connections come from clinical network_connections.

  Submits to provider.referrals.store; server handles aegis_log_activity
  fan-out (sender → recipient → CS → SS feeds).

  HIPAA reminder is rendered in manual-entry mode to discourage PHI.
-->
<template>
  <AegisModal
    :model-value="isOpen('referralModal').value"
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
            </div>
            <span
              class="referral-pt-tag"
              :class="r.client_status === 'priority' ? 'referral-pt-tag--priority' : 'referral-pt-tag--active'"
            >
              <AegisIcon name="circle-dot" :size="9" :filled="true" />
              {{ r.client_status === 'priority' ? 'Priority' : 'Active' }}
            </span>
          </button>

          <div v-if="!filteredRoster.length" class="referral-empty">
            No matching clients. Try a different search, or
            <button type="button" class="referral-link" @click="source = 'manual'">use manual entry</button>.
          </div>
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
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="ref-city">City</label>
            <input id="ref-city" v-model="form.city" type="text" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label" for="ref-state">State</label>
            <input id="ref-state" v-model="form.state" type="text" class="form-input" />
          </div>
        </div>
      </div>
    </section>

    <!-- ── STEP 2 — PRACTITIONER ──────────────────────────────────── -->
    <section v-if="step === 2" class="referral-step">
      <div class="form-group">
        <label class="form-label">Referring to</label>
        <input
          v-model="networkFilter"
          type="text"
          class="form-input"
          placeholder="Search your clinical network…"
          autocomplete="off"
        />
      </div>

      <div class="referral-network-list">
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
              {{ n.display_name }}
              <span v-if="n.credentials" class="referral-pt-creds">, {{ n.credentials }}</span>
            </div>
            <div class="referral-pt-meta">
              {{ [n.specialty, n.location].filter(Boolean).join(' · ') }}
            </div>
          </div>
          <AegisBadge
            :label="n.accepting ? 'Accepting' : 'Not accepting'"
            :variant="n.accepting ? 'green' : 'neutral'"
          />
        </button>

        <div v-if="!filteredNetwork.length" class="referral-empty">
          No matches in your network. Try a broader search.
        </div>
      </div>
    </section>

    <!-- ── STEP 3 — NOTES ─────────────────────────────────────────── -->
    <section v-if="step === 3" class="referral-step">
      <div class="form-group">
        <label class="form-label">Service format</label>
        <div class="seg-toggle">
          <button
            v-for="f in serviceFormats"
            :key="f.value"
            type="button"
            class="seg-toggle-btn"
            :class="{ 'is-active': form.service_format === f.value }"
            @click="form.service_format = f.value"
          >{{ f.label }}</button>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Urgency</label>
        <div class="seg-toggle">
          <button
            v-for="u in urgencies"
            :key="u.value"
            type="button"
            class="seg-toggle-btn"
            :class="{ 'is-active': form.urgency === u.value }"
            @click="form.urgency = u.value"
          >{{ u.label }}</button>
        </div>
      </div>

      <AegisToggle
        v-model="form.accepts_cash"
        label="Cash pay accepted"
        description="Client is willing to self-pay if insurance isn't accepted."
      />

      <div class="form-group">
        <label class="form-label" for="ref-note">Note</label>
        <textarea
          id="ref-note"
          v-model="form.note"
          class="form-input"
          rows="4"
          placeholder="Context for the receiving practitioner — relevant history, treatment preferences, scheduling notes."
        ></textarea>
      </div>

      <AegisToggle
        v-model="form.client_consents"
        label="Client consents to record sharing"
        description="Required before any clinical records are released."
      />
    </section>

    <!-- ── STEP 4 — REVIEW ─────────────────────────────────────────── -->
    <section v-if="step === 4" class="referral-step referral-review">
      <h3 class="referral-review-title">Review your referral</h3>

      <dl class="referral-review-list">
        <div>
          <dt>Client</dt>
          <dd>{{ form.client_name || '—' }}</dd>
        </div>
        <div v-if="form.diagnosis">
          <dt>Reason</dt>
          <dd>{{ form.diagnosis }}</dd>
        </div>
        <div>
          <dt>Referring to</dt>
          <dd>{{ selectedNetworkLabel || '—' }}</dd>
        </div>
        <div>
          <dt>Format</dt>
          <dd>{{ serviceFormats.find(f => f.value === form.service_format)?.label ?? '—' }}</dd>
        </div>
        <div>
          <dt>Urgency</dt>
          <dd>{{ urgencies.find(u => u.value === form.urgency)?.label ?? '—' }}</dd>
        </div>
        <div>
          <dt>Cash pay</dt>
          <dd>{{ form.accepts_cash ? 'Accepted' : 'Not noted' }}</dd>
        </div>
        <div v-if="form.note">
          <dt>Note</dt>
          <dd class="referral-review-note">{{ form.note }}</dd>
        </div>
        <div>
          <dt>Records release</dt>
          <dd>{{ form.client_consents ? 'Consent recorded' : 'Not yet consented' }}</dd>
        </div>
      </dl>

      <div v-if="form.errors.submit" class="form-error">{{ form.errors.submit }}</div>
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
      >Continue</button>

      <button
        v-else
        type="button"
        class="btn btn-primary"
        :disabled="form.processing || !form.client_consents"
        @click="submit"
      >{{ form.processing ? 'Sending…' : 'Send referral' }}</button>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisToggle from '@/components/ui/AegisToggle.vue'

const props = defineProps({
  roster:  { type: Array, default: () => [] },
  network: { type: Array, default: () => [] },
})

const { isOpen, closeModal } = useModal()
const toast = useToast()

const stepLabels = ['Client', 'Practitioner', 'Notes', 'Review']
const step = ref(1)

const source = ref(props.roster.length ? 'roster' : 'manual')
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
  phone:           '',
  email:           '',
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
    `${n.display_name} ${n.specialty ?? ''} ${n.location ?? ''}`
      .toLowerCase()
      .includes(q),
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
  form.roster_item_id  = r.id
  form.client_name     = r.client_name
  form.diagnosis       = r.client_service ?? ''
  form.city            = r.client_city  ?? ''
  form.state           = r.client_state ?? ''
}

function selectNetwork(n) {
  form.provider_slug = n.slug
  form.provider_id   = n.id ?? null
}

function goToStep(n) {
  if (n < step.value) step.value = n // allow rewind only
}

function initials(name) {
  if (!name) return '·'
  const upper = name.match(/[A-Z]/g) ?? []
  return upper.slice(0, 2).join('') || name.slice(0, 2).toUpperCase()
}

function onUpdateOpen(v) { if (!v) onClose() }

function onClose() {
  closeModal('referralModal')
  setTimeout(() => {
    step.value = 1
    form.reset()
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
    onError: () => toast.error('Could not send referral.'),
  })
}
</script>
