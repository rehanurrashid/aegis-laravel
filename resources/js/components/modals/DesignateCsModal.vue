<!-- resources/js/components/modals/DesignateCsModal.vue
     Centralized Designate CS modal.
     MODE A (preselectedUser=null): 5-step wizard — find person → role → incidents → responsibilities → send
     MODE B (preselectedUser=Object): 1-step — show card + role/fee/terms → designate & send
-->
<template>
  <AegisModal
    v-model="innerOpen"
    :title="modalTitle"
    size="lg"
    @close="handleClose"
  >
    <!-- ── MODE B: Preselected user card ── -->
    <div v-if="preselectedUser" class="list-group-item" style="background:var(--surface-2);border-radius:var(--radius);margin-bottom:20px;gap:14px;">
      <div class="spc-avatar" style="width:44px;height:44px;font-size:15px;flex-shrink:0;">{{ preselectedUser.avatar_initials }}</div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:14px;font-weight:700;">{{ preselectedUser.display_name }}<span v-if="preselectedUser.credentials" style="font-size:12px;color:var(--text-3);font-weight:400;">, {{ preselectedUser.credentials }}</span></div>
        <div style="font-size:12px;color:var(--text-3);">{{ [preselectedUser.license_state, preselectedUser.location].filter(Boolean).join(' · ') }}</div>
        <div v-if="preselectedUser.specialties?.length" style="display:flex;flex-wrap:wrap;gap:4px;margin-top:6px;">
          <span v-for="tag in preselectedUser.specialties.slice(0,3)" :key="tag" class="badge badge-neutral" style="font-size:10px;">{{ tag }}</span>
        </div>
      </div>
    </div>

    <!-- ── Step indicator ── -->
    <div class="modal-steps" style="margin-bottom:22px;">
      <div v-for="(s, i) in steps" :key="s.key" style="display:contents;">
        <div class="modal-step" :class="{ done: step > i + 1 || (preselectedUser && i === 0), active: step === i + 1 && !(preselectedUser && i === 0) }">
          <div class="modal-step-num">
            <AegisIcon v-if="step > i + 1" name="check" :size="12" />
            <span v-else>{{ i + 1 }}</span>
          </div>
          {{ s.label }}
        </div>
        <div v-if="i < steps.length - 1" class="modal-step-divider"></div>
      </div>
    </div>

    <!-- ══ STEP 1 / MODE A: Find Person ══ -->
    <div v-if="!preselectedUser && step === 1" style="margin-top:4px;">

      <!-- Tab switcher -->
      <div style="display:flex;justify-content:center;margin-bottom:20px;">
        <div class="tabs-segmented" style="margin-bottom:0;" role="tablist">
          <button type="button" class="tab-pill" :class="{ active: addCsFlow === 'existing' }" @click="addCsFlow = 'existing'; clearSelection()">
            <AegisIcon name="user" :size="13" /> Existing Aegis User
          </button>
          <button type="button" class="tab-pill" :class="{ active: addCsFlow === 'external' }" @click="addCsFlow = 'external'; clearSelection()">
            <AegisIcon name="mail" :size="13" /> External Invite
          </button>
        </div>
      </div>

      <!-- ── TAB A: existing Aegis user ── -->
      <div v-show="addCsFlow === 'existing'">
        <!-- Selected chip -->
        <div v-if="searchSelected" style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:var(--badge-bg-gold);border:1px solid var(--gold-dark);border-radius:var(--radius);margin-bottom:16px;">
          <div style="width:36px;height:36px;border-radius:var(--radius-sm);background:var(--gold-dark);color:var(--text-inverted);font-family:var(--font-serif);font-weight:700;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            {{ searchSelected.initials }}
          </div>
          <div style="flex:1 1 0;min-width:0;">
            <div style="font-size:13px;font-weight:700;color:var(--text);">{{ searchSelected.display_name }}{{ searchSelected.credentials ? ', ' + searchSelected.credentials : '' }}</div>
            <div style="font-size:11px;color:var(--text-3);">{{ searchSelected.email }} &middot; <span style="color:var(--gold-dark);font-weight:600;">{{ searchSelected.role_label }}</span></div>
          </div>
          <button type="button" data-tooltip="Clear selection" style="background:none;border:none;cursor:pointer;color:var(--text-3);padding:4px;" @click="clearSelection">
            <AegisIcon name="x" :size="14" />
          </button>
        </div>

        <!-- Search input -->
        <div v-else class="form-group" style="position:relative;">
          <label class="form-label">Search by Name or Email <span style="color:var(--red-dark);">*</span></label>
          <div style="position:relative;">
            <input
              :value="searchQuery"
              type="text"
              class="form-input"
              :class="{ 'is-error': showSearchError }"
              placeholder="Start typing a name or email…"
              autocomplete="off"
              @input="onSearchInput($event.target.value)"
              @blur="hideDropdown"
              @focus="showDropdown = searchResults.length > 0"
            />
            <div v-if="searchLoading" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);">
              <span class="spinner spinner-sm" />
            </div>
          </div>
          <div v-if="showSearchError" class="form-error">Please search and select a Continuity Steward.</div>

          <!-- Dropdown -->
          <div v-if="showDropdown && searchResults.length"
            style="position:absolute;top:100%;left:0;right:0;z-index:200;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-md);margin-top:2px;max-height:220px;overflow-y:auto;">
            <div v-for="user in searchResults" :key="user.id"
              :style="designatedMap[user.id]
                ? 'display:flex;align-items:center;gap:10px;padding:10px 12px;border-bottom:1px solid var(--border);cursor:not-allowed;opacity:0.55;'
                : 'display:flex;align-items:center;gap:10px;padding:10px 12px;border-bottom:1px solid var(--border);cursor:pointer;'"
              @mousedown.prevent="selectUser(user)"
              @mouseover="!designatedMap[user.id] && ($event.currentTarget.style.background='var(--surface-2)')"
              @mouseleave="$event.currentTarget.style.background=''">
              <div style="width:32px;height:32px;border-radius:var(--radius-sm);background:var(--gold-dark);color:var(--text-inverted);font-family:var(--font-serif);font-weight:700;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                {{ user.initials }}
              </div>
              <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:600;color:var(--text);">{{ user.display_name }}{{ user.credentials ? ', ' + user.credentials : '' }}</div>
                <div style="font-size:11px;color:var(--text-3);">{{ user.email }}</div>
              </div>
              <span v-if="designatedMap[user.id]" style="font-size:10px;font-weight:600;letter-spacing:0.2px;color:var(--text-3);background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:2px 7px;flex-shrink:0;">Already designated</span>
              <span v-else style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.3px;color:var(--gold-dark);flex-shrink:0;">{{ user.role_label }}</span>
            </div>
          </div>
          <div v-if="showDropdown && !searchResults.length && !searchLoading && searchQuery.length >= 2"
            style="position:absolute;top:100%;left:0;right:0;z-index:200;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-md);margin-top:2px;padding:14px 12px;font-size:13px;color:var(--text-3);text-align:center;">
            No Continuity Stewards found for "{{ searchQuery }}".<br>
            <span style="font-size:12px;">Try the <strong>External Invite</strong> tab to invite someone new.</span>
          </div>
        </div>

        <div style="font-size:12px;color:var(--text-3);margin-top:8px;">Search finds users with the Continuity Steward role, or Practitioners who have made themselves available as CS.</div>
      </div>

      <!-- ── TAB B: external invite ── -->
      <div v-show="addCsFlow === 'external'">
        <div class="alert alert-info" style="margin-bottom:16px;">
          <div class="alert-icon"><AegisIcon name="info" :size="16" /></div>
          <div class="alert-content">
            <div class="alert-title">Continuity Steward Agreement</div>
            <div style="font-size:12px;">This is a persistent agreement — active from signing until cancelled by either party. No charges occur until a critical incident is verified and CS tasks are completed. Requires annual re-attestation to remain in effect.</div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address <span style="color:var(--red-dark);">*</span></label>
          <input
            v-model="form.email"
            type="email"
            class="form-input"
            :class="{ 'is-error': fieldError('email') }"
            placeholder="cs@example.com"
            @blur="v$.email.$touch()"
          />
          <div v-if="fieldError('email')" class="form-error">{{ fieldError('email') }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Full Name <span style="color:var(--red-dark);">*</span></label>
          <input
            v-model="form.display_name"
            type="text"
            class="form-input"
            :class="{ 'is-error': fieldError('display_name') }"
            placeholder="Dr. Jane Smith"
            @blur="v$.display_name.$touch()"
          />
          <div v-if="fieldError('display_name')" class="form-error">{{ fieldError('display_name') }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Relationship to You</label>
          <select v-model="form.relationship" class="form-input form-select">
            <option value="">— Select Relationship —</option>
            <option>Designated Continuity Steward</option>
            <option>Office Manager / Practice Administrator</option>
            <option>Colleague Provider (Aegis User)</option>
            <option>Colleague Provider (External)</option>
            <option>Attorney / Legal Representative</option>
            <option>Spouse / Domestic Partner</option>
            <option>Family Member</option>
            <option>Other</option>
          </select>
        </div>
      </div>
    </div>

    <!-- ══ STEP 2: Role + Fee ══ -->
    <div v-if="step === 2" style="margin-top:16px;">
      <div v-if="preselectedUser" class="alert alert-info" style="margin-bottom:16px;">
        <div class="alert-icon"><AegisIcon name="info" :size="16" /></div>
        <div class="alert-content">
          <div class="alert-title">Continuity Steward Agreement</div>
          <div style="font-size:12px;">This is a persistent agreement — active from signing until cancelled by either party. No charges occur until a critical incident is verified and CS tasks are completed. Requires annual re-attestation to remain in effect.</div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Continuity Steward Role <span style="color:var(--red-dark);">*</span></label>
        <div style="display:flex;flex-direction:column;gap:10px;margin-top:8px;">
          <label
            v-for="opt in roleOptions" :key="opt.value"
            :style="form.role === opt.value ? 'border-color:var(--gold-dark);background:var(--badge-bg-gold);' : ''"
            style="display:flex;align-items:center;gap:14px;padding:14px 16px;border:1px solid var(--border);border-radius:var(--radius);background:var(--surface);cursor:pointer;"
            @click="form.role = opt.value"
          >
            <input type="radio" :value="opt.value" v-model="form.role" style="width:16px;height:16px;flex-shrink:0;accent-color:var(--gold-dark);" />
            <div style="flex:1;">
              <div style="font-size:13px;font-weight:700;color:var(--text);">{{ opt.title }}</div>
              <div style="font-size:12px;color:var(--text-3);margin-top:4px;">{{ opt.desc }}</div>
            </div>
          </label>
        </div>
        <div v-if="fieldError('role')" class="form-error" style="margin-top:6px;">{{ fieldError('role') }}</div>
      </div>

      <div class="form-group" style="margin-top:16px;">
        <label class="form-label">Agreed Fee per Incident (Optional)</label>
        <div style="display:flex;align-items:center;gap:8px;">
          <span style="font-size:16px;font-weight:700;color:var(--text-3);">$</span>
          <input
            v-model.number="feeInput"
            type="number"
            min="0"
            step="5"
            class="form-input"
            placeholder="0 for no fee / reciprocal agreement"
            style="max-width:220px;"
            @input="form.fee_cents = feeInput ? Math.round(feeInput * 100) : 0"
          />
          <span style="font-size:12px;color:var(--text-3);">per incident</span>
        </div>
        <div style="display:flex;align-items:flex-start;gap:5px;font-size:11px;color:var(--text-3);margin-top:6px;line-height:1.5;">
          <AegisIcon name="info" :size="11" style="flex-shrink:0;margin-top:1px;" />
          <span>Invoiced automatically when the critical incident closes and CS tasks are marked complete. You have 7 days to pay manually before your default payment method is auto-charged.</span>
        </div>
      </div>

      <div v-if="!preselectedUser" class="form-group" style="margin-top:14px;">
        <label class="form-label">Compensation Notes (Optional)</label>
        <textarea v-model="form.message" class="form-input" style="min-height:60px;" placeholder="e.g., Reciprocal Continuity Plan, hourly compensation…"></textarea>
      </div>
    </div>

    <!-- ══ STEP 3: Approved Critical Incidents ══ -->
    <div v-if="step === 3" style="margin-top:16px;">
      <div class="alert alert-info"><div class="alert-icon"><AegisIcon name="info" :size="14" /></div><div class="alert-content">Select which critical incidents authorize this Continuity Steward to act on your behalf.</div></div>
      <div class="modal-section-label" style="margin-top:14px;">Always-Active Incidents</div>
      <div class="list-group" style="margin-bottom:16px;">
        <div v-for="inc in alwaysActiveIncidents" :key="inc.key" class="list-group-item" style="gap:14px;">
          <span style="flex:1;font-size:13px;font-weight:600;color:var(--text);">{{ inc.label }}</span>
          <span style="font-size:12px;color:var(--text-4);">Always active</span>
          <button type="button" class="toggle on" disabled aria-pressed="true" style="opacity:0.45;cursor:not-allowed;"></button>
        </div>
      </div>
      <div class="modal-section-label">Opt-In Incidents</div>
      <div class="list-group">
        <div v-for="inc in optInIncidents" :key="inc.key" class="list-group-item" style="gap:14px;">
          <div style="flex:1;min-width:0;">
            <div style="font-size:13px;" :style="form.incidentActive[inc.key] ? 'font-weight:600;color:var(--text);' : 'color:var(--text-3);'">{{ inc.label }}</div>
            <div v-if="form.incidentActive[inc.key]" style="display:flex;align-items:center;gap:6px;margin-top:4px;">
              <input type="checkbox" :id="'dcm_verify_' + inc.key" v-model="form.incidentVerify[inc.key]" style="accent-color:var(--gold-dark);width:14px;height:14px;" />
              <label :for="'dcm_verify_' + inc.key" style="font-size:12px;color:var(--text-3);cursor:pointer;margin:0;">Require verification</label>
            </div>
          </div>
          <button type="button" class="toggle" :class="{ on: form.incidentActive[inc.key] }" :aria-pressed="String(!!form.incidentActive[inc.key])" @click="form.incidentActive[inc.key] = !form.incidentActive[inc.key]; if (!form.incidentActive[inc.key]) form.incidentVerify[inc.key] = false" style="flex-shrink:0;"></button>
        </div>
      </div>
    </div>

    <!-- ══ STEP 4: Responsibilities ══ -->
    <div v-if="step === 4" style="margin-top:16px;">
      <div class="alert alert-info"><div class="alert-icon"><AegisIcon name="info" :size="14" /></div><div class="alert-content">Select all responsibilities this Continuity Steward is authorized and expected to carry out.</div></div>
      <div v-for="section in responsibilityGroups" :key="section.title" style="margin-top:18px;">
        <div style="font-size:13px;font-weight:700;margin-bottom:10px;color:var(--text);">{{ section.title }}</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
          <label v-for="item in section.items" :key="item" style="display:flex;align-items:flex-start;gap:10px;font-size:13px;cursor:pointer;">
            <input type="checkbox" checked style="margin-top:2px;accent-color:var(--gold-dark);" />
            <div>{{ item }}</div>
          </label>
        </div>
      </div>
      <div class="form-group" style="margin-top:20px;">
        <label class="form-label">Special Instructions / Notes</label>
        <textarea v-model="form.notes" class="form-input" style="min-height:70px;" placeholder="Any specific instructions, conditions, or context…"></textarea>
      </div>
    </div>

    <!-- ══ STEP 5: Review & Send ══ -->
    <div v-if="step === 5" style="margin-top:16px;">
      <div class="alert alert-success"><AegisIcon name="check" :size="14" /><div>Review the agreement terms below, apply your digital signature, then send.</div></div>
      <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius);padding:20px;font-size:13px;line-height:1.75;color:var(--text-2);margin:14px 0;">
        <div style="font-family:var(--font-serif);font-size:17px;font-weight:700;color:var(--text);text-align:center;margin-bottom:14px;border-bottom:1px solid var(--border);padding-bottom:10px;">Aegis Continuity Steward Agreement</div>
        <h4 style="font-size:13px;font-weight:700;margin-bottom:10px;color:var(--text);">Agreement Terms</h4>
        <p><strong>Continuity Steward:</strong> {{ preselectedUser?.display_name || searchSelected?.display_name || form.display_name || '—' }}<span v-if="preselectedUser?.credentials || searchSelected?.credentials" style="color:var(--text-3);font-weight:400;">, {{ preselectedUser?.credentials || searchSelected?.credentials }}</span></p>
        <p v-if="!preselectedUser && !searchSelected"><strong>Email:</strong> <span :style="!form.email ? 'color:var(--text-4);font-style:italic;' : ''">{{ form.email || 'Not entered' }}</span></p>
        <p v-if="preselectedUser?.location"><strong>Location:</strong> {{ preselectedUser.location }}</p>
        <p><strong>Role:</strong> {{ roleLabel(form.role) }}</p>
        <p v-if="form.relationship"><strong>Relationship:</strong> {{ form.relationship }}</p>
        <p v-if="form.fee_cents > 0"><strong>Fee per incident:</strong> ${{ (form.fee_cents / 100).toFixed(2) }}</p>
        <p v-else><strong>Fee per incident:</strong> $0 (Reciprocal / No Payment)</p>
        <p><strong>Signing Date:</strong> {{ new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }}</p>
        <p style="display:flex;align-items:flex-start;gap:5px;font-size:11px;color:var(--text-3);margin-top:12px;line-height:1.5;">
          <AegisIcon name="info" :size="11" style="flex-shrink:0;margin-top:1px;" />
          <span>Retainer active indefinitely from date of signing. Invoiced only when a critical incident closes and CS tasks are marked complete. Annual re-attestation required. Either party may cancel at any time.</span>
        </p>
      </div>
      <div
        class="upload-zone"
        style="cursor:pointer;margin-bottom:14px;"
        :style="signed ? 'border-color:var(--green);background:var(--green-light,#f0fdf4);' : ''"
        @click="signed = !signed"
      >
        <div class="upload-zone-icon" :style="signed ? 'background:var(--green);border-radius:var(--radius-full);padding:6px;' : ''">
          <AegisIcon v-if="signed" name="check-circle" :size="20" style="color:#fff;" />
          <AegisIcon v-else name="pencil" :size="20" />
        </div>
        <div class="upload-zone-title" :style="signed ? 'color:var(--green);' : ''">
          {{ signed ? 'Signature applied — click to remove' : 'Click to apply your digital signature' }}
        </div>
        <div class="upload-zone-sub">By signing, you confirm all details above are accurate</div>
      </div>
      <div class="form-group">
        <label class="form-label">Invitation Expiry</label>
        <select v-model="form.expires_days" class="form-input form-select">
          <option :value="30">30 days</option>
          <option :value="14">14 days</option>
          <option :value="7">7 days</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Personal Message (Optional)</label>
        <textarea v-model="form.message" class="form-input" style="min-height:60px;" placeholder="Hi, I would like to formally designate you as my Continuity Steward on Aegis…"></textarea>
      </div>
    </div>

    <!-- ── FOOTER ── -->
    <template #footer>
      <button type="button" class="btn btn-outline" style="display:inline-flex;align-items:center;gap:6px;" @click="stepBack">
        <AegisIcon v-if="step > (preselectedUser ? 2 : 1)" name="chevron-left" :size="14" />
        {{ step === (preselectedUser ? 2 : 1) ? 'Cancel' : 'Back' }}
      </button>
      <button v-if="step < 5" type="button" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;" @click="stepNext">
        {{ stepNextLabel }} <AegisIcon name="chevron-right" :size="14" />
      </button>
      <span v-else :data-tooltip="!signed ? 'Apply your digital signature first' : null" style="display:inline-flex;">
        <button type="button" class="btn btn-primary" :disabled="busy || !signed" style="display:inline-flex;align-items:center;gap:6px;" @click="submitDesignate">
          <span v-if="busy" class="spinner spinner-sm" />
          <AegisIcon v-else name="send" :size="14" />
          {{ busy ? 'Sending…' : 'Designate Continuity Steward' }}
        </button>
      </span>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { useVuelidate } from '@vuelidate/core'
import { required, email as emailRule, minLength, helpers } from '@vuelidate/validators'
import { useToast } from '@/composables/useToast'

// ── Props & emits ────────────────────────────────────────────────────────────
const props = defineProps({
  modelValue:         { type: Boolean, default: false },
  preselectedUser:    { type: Object,  default: null  },
  context:            { type: String,  default: 'plan' }, // 'plan' | 'network'
  existingStewards:   { type: Array,   default: () => [] }, // active stewards
  pendingInvitations: { type: Array,   default: () => [] }, // pending/invited
})
const emit = defineEmits(['update:modelValue', 'success'])

const toast = useToast()

// ── Internal open state ───────────────────────────────────────────────────────
const innerOpen = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

// ── designatedMap — block re-designation ─────────────────────────────────────
const designatedMap = computed(() => {
  const map = {}
  const roleLabel = (s) => {
    const r = typeof s.role === 'object' ? s.role?.value : s.role
    if (r === 'alternate') return 'Alternate CS'
    if (r === 'secondary') return 'Support CS'
    return 'Primary CS'
  }
  ;[...props.existingStewards, ...props.pendingInvitations].forEach(s => {
    if (s.steward_id) map[s.steward_id] = roleLabel(s)
  })
  return map
})

// ── Step state ────────────────────────────────────────────────────────────────
const step   = ref(1)
const signed = ref(false)
const busy   = ref(false)

const steps = [
  { key: 'find',            label: 'Find Person' },
  { key: 'role',            label: 'Role' },
  { key: 'incidents',       label: 'Critical Incidents' },
  { key: 'responsibilities', label: 'Responsibilities' },
  { key: 'send',            label: 'Send' },
]

const stepNextLabel = computed(() => {
  const labels = { 1: 'Next', 2: 'Next', 3: 'Next', 4: 'Next: Review & Send' }
  return labels[step.value] ?? 'Next'
})

const modalTitle = computed(() => {
  const titles = {
    1: 'Add Continuity Steward — Step 1 of 5',
    2: 'Add Continuity Steward — Step 2 of 5',
    3: 'Add Continuity Steward — Step 3 of 5',
    4: 'Add Continuity Steward — Step 4 of 5',
    5: 'Add Continuity Steward — Step 5 of 5',
  }
  return titles[step.value] ?? 'Add Continuity Steward'
})

// ── Search state (Step 1 / Tab A) ────────────────────────────────────────────
const addCsFlow     = ref('existing')
const searchQuery   = ref('')
const searchResults = ref([])
const searchLoading = ref(false)
const searchSelected = ref(null)
const showDropdown  = ref(false)
const showSearchError = ref(false)
let searchTimer = null

function onSearchInput(val) {
  searchQuery.value   = val
  searchSelected.value = null
  form.user_id        = null
  form.display_name   = val
  form.email          = ''
  showSearchError.value = false
  if (val.length < 2) { searchResults.value = []; showDropdown.value = false; return }
  clearTimeout(searchTimer)
  searchLoading.value = true
  searchTimer = setTimeout(async () => {
    try {
      const res = await window.axios.get(route('provider.stewards.search-users'), { params: { q: val } })
      searchResults.value = res.data
      showDropdown.value  = res.data.length > 0
    } catch { searchResults.value = [] }
    finally { searchLoading.value = false }
  }, 280)
}

function selectUser(user) {
  const alreadyRole = designatedMap.value[user.id]
  if (alreadyRole) {
    toast.error(`${user.display_name} is already designated as your ${alreadyRole}. Remove them first to re-designate.`)
    showDropdown.value = false
    return
  }
  searchSelected.value = user
  form.user_id         = user.id
  form.display_name    = user.display_name
  form.email           = user.email
  searchQuery.value    = user.display_name
  searchResults.value  = []
  showDropdown.value   = false
  showSearchError.value = false
}

function hideDropdown() { setTimeout(() => { showDropdown.value = false }, 200) }

function clearSelection() {
  searchSelected.value = null
  form.user_id         = null
  form.display_name    = ''
  form.email           = ''
  searchQuery.value    = ''
  showSearchError.value = false
}

// ── Form ─────────────────────────────────────────────────────────────────────
const form = useForm({
  user_id:        null,
  email:          '',
  display_name:   '',
  role:           'primary',
  fee_cents:      0,
  relationship:   '',
  message:        '',
  notes:          '',
  expires_days:   30,
  incidentActive: { death: true, short_term_incapacitation: true, long_term_incapacitation: true, missing_person: false, detainment: false, natural_disaster: false, geopolitical: false },
  incidentVerify: { death: false, short_term_incapacitation: false, long_term_incapacitation: false, missing_person: true, detainment: true, natural_disaster: true, geopolitical: true },
})

const feeInput = ref(0)
watch(feeInput, (v) => { form.fee_cents = v ? Math.round(Number(v) * 100) : 0 })

// ── Vuelidate ────────────────────────────────────────────────────────────────
const isExternal = computed(() => addCsFlow.value === 'external')
const rules = computed(() => ({
  email: isExternal.value
    ? { required: helpers.withMessage('Email is required', required), email: helpers.withMessage('Must be a valid email', emailRule) }
    : {},
  display_name: isExternal.value
    ? { required: helpers.withMessage('Name is required', required), minLength: helpers.withMessage('Min 2 characters', minLength(2)) }
    : {},
  role: { required: helpers.withMessage('Please select a role', required) },
}))

const v$ = useVuelidate(rules, form, { $scope: false })

function fieldError(field) {
  const f = v$.value[field]
  if (!f || !f.$dirty) return null
  return f.$errors[0]?.$message ?? null
}

// ── Reset on open ─────────────────────────────────────────────────────────────
watch(() => props.modelValue, (open) => {
  if (!open) return
  step.value        = props.preselectedUser ? 2 : 1
  signed.value      = false
  busy.value        = false
  feeInput.value    = 0
  addCsFlow.value   = 'existing'
  searchQuery.value = ''
  searchResults.value = []
  searchSelected.value = null
  showDropdown.value = false
  showSearchError.value = false
  form.reset()
  v$.value.$reset()
  if (props.preselectedUser) {
    form.user_id      = props.preselectedUser.id
    form.display_name = props.preselectedUser.display_name ?? ''
  }
})

// ── Navigation ────────────────────────────────────────────────────────────────
async function stepNext() {
  if (step.value === 1) {
    if (addCsFlow.value === 'existing') {
      if (!form.user_id) {
        showSearchError.value = true
        return
      }
    } else {
      v$.value.email.$touch()
      v$.value.display_name.$touch()
      if (v$.value.email.$error || v$.value.display_name.$error) return
    }
  }
  if (step.value === 2) {
    v$.value.role.$touch()
    if (v$.value.role.$error || !form.role) return
  }
  step.value = Math.min(step.value + 1, 5)
}

function stepBack() {
  const firstStep = props.preselectedUser ? 2 : 1
  if (step.value <= firstStep) { handleClose(); return }
  step.value = Math.max(step.value - 1, firstStep)
}

function handleClose() {
  innerOpen.value = false
}

// ── Submit ────────────────────────────────────────────────────────────────────
async function submitDesignate() {
  v$.value.$touch()
  const valid = await v$.value.$validate()
  if (!valid) return

  busy.value = true

  const payload = {
    role:         form.role,
    fee_cents:    form.fee_cents,
    message:      form.message,
    expires_days: form.expires_days,
  }

  if (props.preselectedUser) {
    payload.preselected_user_id = props.preselectedUser.id
  } else if (form.user_id) {
    payload.user_id = form.user_id
  } else {
    payload.email        = form.email
    payload.display_name = form.display_name
    payload.relationship = form.relationship
  }

  router.post(route('provider.stewards.invite'), payload, {
    preserveScroll: true,
    onSuccess: () => {
      const name = props.preselectedUser?.display_name ?? searchSelected.value?.display_name ?? form.display_name
      toast.success((name || 'Continuity Steward') + (props.preselectedUser ? ' designated as your CS.' : ' invitation sent.'))
      emit('success')
      innerOpen.value = false
      busy.value = false
    },
    onError: (errors) => {
      const msg = errors && typeof errors === 'object' ? (Object.values(errors)[0] ?? '') : ''
      toast.error(msg || 'Could not complete. Please try again.')
      busy.value = false
    },
  })
}

// ── Static data ───────────────────────────────────────────────────────────────
const roleOptions = [
  { value: 'primary',   title: 'Primary Continuity Steward',   desc: 'First in line. Full authority to act. Best for a trusted colleague who is highly available.' },
  { value: 'secondary', title: 'Support Continuity Steward',   desc: 'Works alongside the Primary, sharing responsibilities during a critical moment.' },
  { value: 'alternate', title: 'Alternate Continuity Steward', desc: 'Steps in if the Primary is unreachable. Good for a clinically-licensed colleague.' },
]

function roleLabel(r) {
  return roleOptions.find(o => o.value === r)?.title ?? r
}

const alwaysActiveIncidents = [
  { key: 'death',                     label: 'Death' },
  { key: 'short_term_incapacitation', label: 'Short-Term Incapacitation' },
  { key: 'long_term_incapacitation',  label: 'Long-Term Incapacitation' },
]
const optInIncidents = [
  { key: 'missing_person', label: 'Missing Person' },
  { key: 'detainment',     label: 'Detainment' },
  { key: 'natural_disaster', label: 'Natural Disaster' },
  { key: 'geopolitical',   label: 'Geopolitical or Conflict-Related Events' },
]

const responsibilityGroups = [
  { title: 'Immediate Practice Stabilization', items: ['Confirm the practitioner\'s status and that a critical incident has been verified', 'Notify active clients of the change in practice continuity', 'Pause or adjust the schedule and communicate with practice staff'] },
  { title: 'Professional & Regulatory Alignment', items: ['Notify the relevant state licensing board(s)', 'Notify the malpractice carrier and arrange tail coverage if needed', 'Coordinate with the attorney or legal representative'] },
  { title: 'Records & Information Stewardship', items: ['Access the Document Vault using the credentials stored in the agreement', 'Secure and transfer client records in a confidential, compliant manner', 'Maintain the confidentiality of all sensitive information'] },
  { title: 'Financial & Administrative Stewardship', items: ['Transition or close out insurance billing and outstanding claims', 'Coordinate practice banking and outstanding payments', 'Manage payroll and vendor obligations as needed'] },
  { title: 'Practice Presence & Relationships', items: ['Notify the professional network and referral partners via Aegis', 'Coordinate client referrals to appropriate providers', 'Update or pause public-facing practice information'] },
]
</script>
