<!--
  CredentialModal.vue — unified credential & insurance modal.

  Replaces all inline credential/insurance AegisModal blocks in Dashboard.vue
  and EditProfile.vue with a single shared component.

  Modes:
    'add-credential'    — add new license/certification
    'edit-credential'   — update/renew existing credential
    'detail-credential' — read-only detail with Update shortcut
    'add-insurance'     — add new insurance policy
    'edit-insurance'    — update/renew existing insurance policy
    'detail-insurance'  — read-only detail with Update shortcut
    'reminder'          — set expiry reminder (UI-only)

  Props:
    v-model          — Boolean open/close
    mode             — one of the 7 strings above
    credential       — existing record for edit/detail/reminder (null for add)
    all-credentials  — full list for reminder item dropdown

  Emits:
    saved            — after successful store/update (parent calls router.reload)
    edit(mode)       — when detail "Update" clicked (parent switches mode)
-->
<template>
  <AegisModal
    :model-value="modelValue"
    :title="modalTitle"
    :size="isDetail || mode === 'reminder' ? 'sm' : 'lg'"
    @update:model-value="$emit('update:modelValue', $event)"
  >

    <!-- ── ADD / EDIT CREDENTIAL ───────────────────────────────── -->
    <template v-if="mode === 'add-credential' || mode === 'edit-credential'">

      <div v-if="mode === 'edit-credential' && isCritical" class="alert alert-warning" style="margin-bottom:16px">
        <div class="alert-icon"><AegisIcon name="alert-triangle" :size="14" /></div>
        <div class="alert-content">
          {{ credential?.cred_type ?? 'Credential' }}
          {{ credential?.expires_on ? 'expires ' + fmtDate(credential.expires_on) : 'needs updating' }}
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Credential Type <span style="color:var(--red)">*</span></label>
          <select v-model="form.cred_type" class="form-select" @change="form.custom_type = ''">
            <option value="">Select a credential...</option>
            <optgroup label="Medical &amp; Prescribing">
              <option>MD</option><option>DO</option><option>ND</option><option>NP</option><option>PA</option>
            </optgroup>
            <optgroup label="Mental Health">
              <option>LPC / LPCC</option><option>LCSW / LICSW</option><option>LMFT</option><option>ABPP</option><option>PhD — Psychology</option><option>PsyD — Psychology</option><option>PMHNP-BC</option>
            </optgroup>
            <optgroup label="Therapy &amp; Specialty">
              <option>EMDR Certified</option><option>DBT Certified</option><option>CSE</option><option>CSC</option><option>CST</option><option>ATR</option><option>MT-BC</option><option>RDT</option>
            </optgroup>
            <optgroup label="Addiction &amp; Health">
              <option>CADC / ICADC</option><option>LAc</option><option>RD / RDN</option><option>NBC-HWC</option><option>CNM</option><option>CGC</option><option>CDCES / CDE</option>
            </optgroup>
            <optgroup label="Fitness &amp; Physical">
              <option>CPT (NSCA)</option><option>CPT (NASM)</option><option>CPT (ACE)</option><option>EP-C (ACSM)</option>
            </optgroup>
            <optgroup label="Other">
              <option value="Licensed">Licensed</option>
              <option value="custom">Other (enter manually)</option>
            </optgroup>
          </select>
          <div v-if="form.errors.cred_type" class="form-error">{{ form.errors.cred_type }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Issuing State / Body</label>
          <select v-model="form.issuer" class="form-select">
            <option value="">Select…</option>
            <option>Alabama</option><option>Alaska</option><option>Arizona</option><option>Arkansas</option>
            <option>California</option><option>Colorado</option><option>Connecticut</option><option>Delaware</option>
            <option>Florida</option><option>Georgia</option><option>Hawaii</option><option>Idaho</option>
            <option>Illinois</option><option>Indiana</option><option>Iowa</option><option>Kansas</option>
            <option>Kentucky</option><option>Louisiana</option><option>Maine</option><option>Maryland</option>
            <option>Massachusetts</option><option>Michigan</option><option>Minnesota</option><option>Mississippi</option>
            <option>Missouri</option><option>Montana</option><option>Nebraska</option><option>Nevada</option>
            <option>New Hampshire</option><option>New Jersey</option><option>New Mexico</option><option>New York</option>
            <option>North Carolina</option><option>North Dakota</option><option>Ohio</option><option>Oklahoma</option>
            <option>Oregon</option><option>Pennsylvania</option><option>Rhode Island</option><option>South Carolina</option>
            <option>South Dakota</option><option>Tennessee</option><option>Texas</option><option>Utah</option>
            <option>Vermont</option><option>Virginia</option><option>Washington</option><option>West Virginia</option>
            <option>Wisconsin</option><option>Wyoming</option>
            <option>Federal</option><option>National / No State</option><option>Other</option>
          </select>
        </div>
      </div>

      <div v-if="form.cred_type === 'custom'" class="form-group">
        <label class="form-label">Custom Credential Name <span style="color:var(--red)">*</span></label>
        <input v-model="form.custom_type" class="form-input" type="text" placeholder="e.g. Board Certified Coach" />
        <div v-if="form.errors.custom_type" class="form-error">{{ form.errors.custom_type }}</div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">License / Credential #</label>
          <input v-model="form.number" type="text" class="form-input" placeholder="e.g. NY-12345 (optional)" />
        </div>
        <div class="form-group">
          <label class="form-label">Display Name</label>
          <input v-model="form.name" type="text" class="form-input" placeholder="e.g. NY Medical License (optional)" />
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Date Issued</label>
          <input v-model="form.issued_on" type="date" class="form-input" />
        </div>
        <div class="form-group">
          <label class="form-label">Expiration Date</label>
          <input v-model="form.expires_on" type="date" class="form-input" />
          <div v-if="form.errors.expires_on" class="form-error">{{ form.errors.expires_on }}</div>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Upload Document</label>
        <div v-if="credential?.document_paths?.length && mode === 'edit-credential'" style="display:flex;flex-direction:column;gap:5px;margin-bottom:8px">
          <div v-for="p in credential.document_paths" :key="p" class="cm-file-existing">
            <AegisIcon name="file-text" :size="14" />
            <span>{{ docName(p) }}</span>
            <span class="cm-file-existing-sub">· on file</span>
            <button type="button" class="btn-icon btn-icon-sm btn-icon-danger" style="margin-left:auto" data-tooltip="Remove this file" @click="removeExistingDoc(p)"><AegisIcon name="x" :size="11" /></button>
          </div>
        </div>
        <AegisDropzone accept=".pdf,.jpg,.jpeg,.png" hint="Drop files or browse — add as many as needed" :multiple="true" @files="form.document = $event" />
        <div v-if="form.errors.document || form.errors['document.*']" class="form-error">{{ form.errors.document || form.errors['document.*'] }}</div>
      </div>
    </template>

    <!-- ── DETAIL — CREDENTIAL ────────────────────────────────── -->
    <template v-else-if="mode === 'detail-credential'">
      <div class="cm-status-strip" :class="isCritical ? 'is-critical' : 'is-ok'">
        <AegisIcon :name="isCritical ? 'alert-triangle' : 'shield-check'" :size="13" />
        <span>{{ daysLabel }}</span>
      </div>
      <div class="cm-detail-rows">
        <div class="cm-detail-row"><span>Type</span><strong>{{ credential?.cred_type ?? '—' }}</strong></div>
        <div class="cm-detail-row"><span>Display Name</span><strong>{{ credential?.name ?? '—' }}</strong></div>
        <div class="cm-detail-row"><span>License #</span><strong>{{ credential?.number ?? '—' }}</strong></div>
        <div class="cm-detail-row"><span>Issued By</span><strong>{{ credential?.issuer ?? '—' }}</strong></div>
        <div class="cm-detail-row"><span>Issued</span><strong>{{ credential?.issued_on ? fmtDate(credential.issued_on) : '—' }}</strong></div>
        <div class="cm-detail-row"><span>Expires</span><strong>{{ credential?.expires_on ? fmtDate(credential.expires_on) : 'No expiry' }}</strong></div>
      </div>
      <div v-if="credential?.document_paths?.length" style="margin-top:14px;display:flex;flex-direction:column;gap:6px">
        <div v-for="(p, i) in credential.document_paths" :key="p" class="cm-file-existing">
          <AegisIcon name="file-text" :size="14" />
          <span>{{ docName(p) }}</span>
          <span class="cm-file-existing-sub">· on file</span>
          <div style="display:flex;gap:6px;margin-left:auto">
            <a :href="`/storage/${p}`" target="_blank" rel="noopener" class="btn-icon btn-icon-sm" data-tooltip="Preview"><AegisIcon name="eye" :size="13" /></a>
            <a :href="route('provider.credentials.download', credential.id) + '?path=' + encodeURIComponent(p)" class="btn-icon btn-icon-sm" data-tooltip="Download"><AegisIcon name="download" :size="13" /></a>
          </div>
        </div>
      </div>
    </template>

    <!-- ── ADD / EDIT INSURANCE ───────────────────────────────── -->
    <template v-else-if="mode === 'add-insurance' || mode === 'edit-insurance'">

      <div v-if="mode === 'edit-insurance' && isCritical" class="alert alert-warning" style="margin-bottom:16px">
        <div class="alert-icon"><AegisIcon name="alert-triangle" :size="14" /></div>
        <div class="alert-content">
          {{ credential?.cred_type ?? 'Insurance policy' }}
          {{ credential?.expires_on ? 'expires ' + fmtDate(credential.expires_on) : 'needs updating' }}
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Policy Type <span style="color:var(--red)">*</span></label>
          <select v-model="form.cred_type" class="form-select">
            <option value="">Select type</option>
            <option>Professional Liability (Malpractice)</option>
            <option>General Business Insurance</option>
            <option>Workers Compensation</option>
            <option>Cyber Liability</option>
            <option>Life Insurance</option>
            <option>Other</option>
          </select>
          <div v-if="form.errors.cred_type" class="form-error">{{ form.errors.cred_type }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Insurance Provider <span style="color:var(--red)">*</span></label>
          <input v-model="form.issuer" type="text" class="form-input" placeholder="e.g. Medical Protective" />
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Policy Number</label>
          <input v-model="form.number" type="text" class="form-input" placeholder="e.g. POL-2024-XXXXX" />
        </div>
        <div class="form-group">
          <label class="form-label">Coverage / Display Name</label>
          <input v-model="form.name" type="text" class="form-input" placeholder="e.g. $2M / $4M Liability" />
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Effective Date</label>
          <input v-model="form.issued_on" type="date" class="form-input" />
        </div>
        <div class="form-group">
          <label class="form-label">Expiration Date <span style="color:var(--red)">*</span></label>
          <input v-model="form.expires_on" type="date" class="form-input" />
          <div v-if="form.errors.expires_on" class="form-error">{{ form.errors.expires_on }}</div>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Upload Policy Document</label>
        <div v-if="credential?.document_paths?.length && mode === 'edit-insurance'" style="display:flex;flex-direction:column;gap:5px;margin-bottom:8px">
          <div v-for="p in credential.document_paths" :key="p" class="cm-file-existing">
            <AegisIcon name="file-text" :size="14" />
            <span>{{ docName(p) }}</span>
            <span class="cm-file-existing-sub">· on file</span>
            <button type="button" class="btn-icon btn-icon-sm btn-icon-danger" style="margin-left:auto" data-tooltip="Remove this file" @click="removeExistingDoc(p)"><AegisIcon name="x" :size="11" /></button>
          </div>
        </div>
        <AegisDropzone accept=".pdf,.jpg,.jpeg,.png" hint="Drop files or browse — add as many as needed" :multiple="true" @files="form.document = $event" />
        <div v-if="form.errors.document || form.errors['document.*']" class="form-error">{{ form.errors.document || form.errors['document.*'] }}</div>
      </div>
    </template>

    <!-- ── DETAIL — INSURANCE ─────────────────────────────────── -->
    <template v-else-if="mode === 'detail-insurance'">
      <div class="cm-status-strip" :class="isCritical ? 'is-critical' : 'is-ok'">
        <AegisIcon :name="isCritical ? 'alert-triangle' : 'shield-check'" :size="13" />
        <span>{{ daysLabel }}</span>
      </div>
      <div class="cm-detail-rows">
        <div class="cm-detail-row"><span>Policy Type</span><strong>{{ credential?.cred_type ?? '—' }}</strong></div>
        <div class="cm-detail-row"><span>Carrier</span><strong>{{ credential?.issuer ?? '—' }}</strong></div>
        <div class="cm-detail-row"><span>Policy #</span><strong>{{ credential?.number ?? '—' }}</strong></div>
        <div class="cm-detail-row"><span>Coverage</span><strong>{{ credential?.name ?? credential?.subtitle ?? '—' }}</strong></div>
        <div class="cm-detail-row"><span>Effective</span><strong>{{ credential?.issued_on ? fmtDate(credential.issued_on) : '—' }}</strong></div>
        <div class="cm-detail-row"><span>Expires</span><strong>{{ credential?.expires_on ? fmtDate(credential.expires_on) : '—' }}</strong></div>
      </div>
      <div v-if="credential?.document_paths?.length" style="margin-top:14px;display:flex;flex-direction:column;gap:6px">
        <div v-for="(p, i) in credential.document_paths" :key="p" class="cm-file-existing">
          <AegisIcon name="file-text" :size="14" />
          <span>{{ docName(p) }}</span>
          <span class="cm-file-existing-sub">· on file</span>
          <div style="display:flex;gap:6px;margin-left:auto">
            <a :href="`/storage/${p}`" target="_blank" rel="noopener" class="btn-icon btn-icon-sm" data-tooltip="Preview"><AegisIcon name="eye" :size="13" /></a>
            <a :href="route('provider.credentials.download', credential.id) + '?path=' + encodeURIComponent(p)" class="btn-icon btn-icon-sm" data-tooltip="Download"><AegisIcon name="download" :size="13" /></a>
          </div>
        </div>
      </div>
    </template>

    <!-- ── SET REMINDER ───────────────────────────────────────── -->
    <template v-else-if="mode === 'reminder'">
      <div class="form-group">
        <label class="form-label">Credential / Policy</label>
        <select v-model="reminderCredId" class="form-select">
          <option value="">Select item…</option>
          <option v-for="c in allCredentials" :key="c.id" :value="c.id">
            {{ c.name || c.cred_type }}{{ c.expires_on ? ' — Expires ' + fmtDate(c.expires_on) : '' }}
          </option>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Remind Me</label>
          <select v-model="reminderDays" class="form-select">
            <option value="30">30 days before</option>
            <option value="60">60 days before</option>
            <option value="90">90 days before</option>
            <option value="120">120 days before</option>
            <option value="180">180 days before</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Repeat</label>
          <select v-model="reminderRepeat" class="form-select">
            <option value="once">One-time</option>
            <option value="weekly">Weekly until acted on</option>
            <option value="daily_final">Daily in final 7 days</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Delivery</label>
        <div style="display:flex;flex-direction:column;gap:8px;margin-top:4px">
          <label class="form-check"><input type="checkbox" v-model="reminderEmail" class="form-check-input" /><span class="form-check-label">Email reminder</span></label>
          <label class="form-check"><input type="checkbox" v-model="reminderInApp" class="form-check-input" /><span class="form-check-label">In-app notification</span></label>
        </div>
      </div>
    </template>

    <!-- ── FOOTER ─────────────────────────────────────────────── -->
    <template #footer>
      <template v-if="isDetail">
        <button class="btn btn-outline" @click="$emit('update:modelValue', false)">Close</button>
        <button class="btn btn-primary" @click="$emit('edit', mode === 'detail-credential' ? 'edit-credential' : 'edit-insurance')">
          {{ mode === 'detail-credential' ? 'Update Credential' : 'Update Policy' }}
        </button>
      </template>
      <template v-else-if="mode === 'reminder'">
        <button class="btn btn-outline" @click="$emit('update:modelValue', false)">Cancel</button>
        <button class="btn btn-primary" @click="submitReminder">Set Reminder</button>
      </template>
      <template v-else>
        <button class="btn btn-outline" @click="$emit('update:modelValue', false)">Cancel</button>
        <button class="btn btn-primary" :disabled="form.processing" @click="submit">
          {{ form.processing ? 'Saving…' : submitLabel }}
        </button>
      </template>
    </template>

  </AegisModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { useToast } from '@/composables/useToast'
import AegisDropzone from '@/components/ui/AegisDropzone.vue'

const props = defineProps({
  modelValue:     { type: Boolean, required: true },
  mode:           { type: String,  required: true },
  credential:     { type: Object,  default: null },
  allCredentials: { type: Array,   default: () => [] },
})

const emit  = defineEmits(['update:modelValue', 'saved', 'edit'])
const toast = useToast()

const isDetail    = computed(() => props.mode.startsWith('detail'))
const isInsurance = computed(() => props.mode.includes('insurance'))
const isEdit      = computed(() => props.mode.startsWith('edit'))

const isCritical = computed(() => {
  const d = props.credential?.days_remaining
  return d !== null && d !== undefined && d < 30
})
const daysLabel = computed(() => {
  const d = props.credential?.days_remaining
  if (d === null || d === undefined) return 'No expiry set'
  if (d < 1)  return 'Expired'
  if (d < 30) return `${d} days remaining — update now`
  return `${d} days remaining`
})
const modalTitle = computed(() => ({
  'add-credential':    'Add Credential',
  'edit-credential':   'Update Credential',
  'detail-credential': 'License Details',
  'add-insurance':     'Add Insurance Policy',
  'edit-insurance':    'Update Insurance Policy',
  'detail-insurance':  'Insurance Policy Details',
  'reminder':          'Set Reminder',
}[props.mode] ?? 'Credential'))

const submitLabel = computed(() => ({
  'add-credential': 'Add Credential',
  'edit-credential': 'Save Update',
  'add-insurance': 'Add Policy',
  'edit-insurance': 'Save Update',
}[props.mode] ?? 'Save'))

// ── Form ──────────────────────────────────────────────────────────────
const form = useForm({
  cred_type: '', custom_type: '', name: '', number: '',
  issuer: '', issued_on: '', expires_on: '', document: [],
})

watch(() => [props.modelValue, props.mode, props.credential], ([open]) => {
  if (!open) return
  if (isEdit.value && props.credential) {
    form.cred_type   = props.credential.cred_type  ?? ''
    form.custom_type = ''
    form.name        = props.credential.name        ?? ''
    form.number      = props.credential.number      ?? ''
    form.issuer      = props.credential.issuer      ?? ''
    form.issued_on   = props.credential.issued_on   ?? ''
    form.expires_on  = props.credential.expires_on  ?? ''
    form.document    = []
  } else if (!isEdit.value && !isDetail.value && props.mode !== 'reminder') {
    form.reset()
  }
  if (props.mode === 'reminder' && props.credential?.id) {
    reminderCredId.value = props.credential.id
  }
})

function submit() {
  const resolvedType = form.cred_type === 'custom'
    ? (form.custom_type || 'Other')
    : (form.cred_type || (isInsurance.value ? 'Liability Insurance' : ''))

  const url = isEdit.value && props.credential?.id
    ? route('provider.credentials.update', props.credential.id)
    : route('provider.credentials.store')

  form.transform((d) => ({
    ...d,
    cred_type: resolvedType,
    ...(isEdit.value ? { _method: 'PUT' } : {}),
  })).post(url, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      emit('update:modelValue', false)
      emit('saved')
      form.reset()
    },
  })
}

// ── Reminder (UI-only) ────────────────────────────────────────────────
const reminderCredId = ref(props.credential?.id ?? '')
const reminderDays   = ref('90')
const reminderRepeat = ref('once')
const reminderEmail  = ref(true)
const reminderInApp  = ref(true)

function removeExistingDoc(filePath) {
  router.delete(route('provider.credentials.document.destroy', props.credential.id), {
    data: { path: filePath },
    preserveScroll: true,
    onSuccess: () => emit('saved'),
  })
}

function submitReminder() {
  toast.success("Reminder set. We'll notify you before this credential expires.")
  emit('update:modelValue', false)
  reminderCredId.value = ''
}

function fmtDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
function docName(path) {
  if (!path) return ''
  const file = path.split('/').pop()
  // Laravel stores files as <hash>.<ext> — strip the hash, keep extension readable
  const ext = file.includes('.') ? file.split('.').pop() : ''
  return ext ? `Document.${ext}` : file
}
</script>

<style scoped>
.cm-status-strip {
  display: flex; align-items: center; gap: 7px;
  padding: 9px 12px; border-radius: var(--radius-sm);
  font-size: 12px; font-weight: 600; margin-bottom: 16px;
}
.cm-status-strip.is-ok       { background: var(--green-light); color: var(--green-dark); }
.cm-status-strip.is-critical { background: var(--red-light);   color: var(--red-dark); }

.cm-detail-rows { display: flex; flex-direction: column; }
.cm-detail-row {
  display: flex; justify-content: space-between; align-items: baseline;
  gap: 12px; padding: 9px 0; border-bottom: 1px solid var(--border); font-size: 13px;
}
.cm-detail-row:last-child { border-bottom: none; }
.cm-detail-row > span   { color: var(--text-3); flex-shrink: 0; }
.cm-detail-row > strong { color: var(--text); text-align: right; }

.cm-file-existing {
  display: flex; align-items: center; gap: 7px;
  font-size: 12px; font-weight: 600; color: var(--text-2);
  background: var(--surface-2); border: 1px solid var(--border);
  border-radius: var(--radius-sm); padding: 8px 10px; margin-bottom: 8px;
  min-width: 0; overflow: hidden;
}
.cm-file-existing > .aegis-icon { flex-shrink: 0; }
.cm-file-existing > span:first-of-type {
  flex: 1; min-width: 0;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.cm-file-existing-sub { font-size: 11px; color: var(--text-4); font-weight: 400; flex-shrink: 0; }
.cm-file-existing > div:last-child { flex-shrink: 0; margin-left: auto; }
</style>
