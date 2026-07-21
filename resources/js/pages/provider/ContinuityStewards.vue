<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { useVuelidate } from '@vuelidate/core'
import { required, email, minLength, minValue, helpers, integer } from '@vuelidate/validators'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisToggle from '@/components/ui/AegisToggle.vue'
import DesignateCsModal from '@/components/modals/DesignateCsModal.vue'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { useMessageButton } from '@/composables/useMessageButton'
import PlanReviewAlert from '@/components/PlanReviewAlert.vue'
import ViewCsAgreementModal from '@/components/modals/ViewCsAgreementModal.vue'
import EndStewardRetainerModal from '@/components/modals/EndStewardRetainerModal.vue'

const props = defineProps({
  stewards:           { type: Array,  default: () => [] },
  suspended:          { type: Array,  default: () => [] },
  pendingInvitations: { type: Array,  default: () => [] },
  servingAsCSFor:     { type: Array,  default: () => [] },
  tierLimits:         { type: Object, default: () => ({}) },
  tier:               { type: String, default: 'access' },
  csMax:              { type: Number, default: 2 },
  csCount:            { type: Number, default: 0 },
  incidentConfigs:    { type: Array,  default: () => [] },
  annualReviewDue:    { type: String,  default: null },
  planStatus:         { type: String,  default: null },
  annualReviewDate:   { type: String,  default: null },
  hasDraftInProgress: { type: Boolean, default: false },
  draftPlanVersion:   { type: Number,  default: null },
  notifyPrefs:        { type: Object,  default: () => ({}) },
  hasCsAddon:         { type: Boolean, default: false },
  providerAsCsCount:  { type: Number,  default: 0 },
  providerAsCsCap:    { type: Number,  default: 1 },
  userTier:           { type: String,  default: 'access' },
  availableAsCs:      { type: Boolean, default: false },
})

const toast = useToast()
const { confirmAction } = useConfirm()
const { openConversation, loading: msgLoading } = useMessageButton()

// ── Tab state ──────────────────────────────────────────────────────────────────
const activeTab = ref('myexec')
const showRemoveModal = ref(false)

// ── Active record refs ──────────────────────────────────────────────────────────
const activeId      = ref(null)
const activeSteward = computed(() =>
  [...(props.stewards ?? []), ...(props.suspended ?? [])].find(s => s.id === activeId.value) ?? null
)
const viewAgreementSteward = ref(null)
const showViewAgreement = ref(false)
const activePendingId = ref(null)
const activePending   = computed(() => props.pendingInvitations.find(s => s.id === activePendingId.value) ?? null)

// ── Modal open state ────────────────────────────────────────────────────────────
const modals = ref({
  addStep1:       false,
  addStep2:       false,
  addStep3:       false,
  addStep4:       false,
  addStep5:       false,
  editCS:         false,
  amendFee:       false,
  resend:         false,
  endRetainer:    false,
  cancelInvite:   false,
  upgrade:        false,
  reinstateCs:    false,
  designateCs:    false,
})

function openModal(key, steward = null) {
  if (steward) activeId.value = steward.id
  modals.value[key] = true
}
function closeModal(key) {
  modals.value[key] = false
}
function closeAllAdd() {
  modals.value.designateCs = false
}

// ── Steward action openers ─────────────────────────────────────────────────────
function openEditModal(s) {
  activeId.value = s.id
  modals.value.editCS = true
}


function openRemoveModal(s) {
  activeId.value = s.id
  endRetainerForm.reset()
  showRemoveModal.value = true
}
function openEndRetainer(s) { openRemoveModal(s) }
function openCsReinstate(s) {
  activeId.value = s.id
  modals.value.reinstateCs = true
}
function submitCsReinstate() {
  if (!activeSteward.value || busyReinstate.value) return
  busyReinstate.value = true
  router.post(route('provider.stewards.reinstate', { steward: activeSteward.value.id }), {}, {
    preserveScroll: true,
    onSuccess: () => {
      modals.value.reinstateCs = false
      toast.success('Continuity Steward reinstated.')
      router.reload({ only: ['stewards', 'suspended'] })
    },
    onError:  () => toast.error('Could not reinstate steward.'),
    onFinish: () => { busyReinstate.value = false },
  })
}
function openResend(inv) {
  activePendingId.value   = inv.id
  resendForm.expiry_days = 14
  resendForm.message      = ''
  modals.value.resend = true
}
function openCancelInvite(inv) {
  activePendingId.value = inv.id
  modals.value.cancelInvite = true
}

// ── Edit CS form ─────────────────────────────────────────────────────────────
const editForm = useForm({
  role:         '',
  vault_access: 'scoped',
  notes:        '',
})
const busyEdit = ref(false)

// ── Amend Fee form ─────────────────────────────────────────────────────────────
const amendFeeForm = useForm({ fee_cents_display: '' })
const busyAmendFee = ref(false)

watch(() => modals.value.amendFee, (open) => {
  if (open && activeSteward.value) {
    amendFeeForm.fee_cents_display = activeSteward.value.fee_cents
      ? (activeSteward.value.fee_cents / 100).toFixed(2)
      : ''
  }
})

function submitAmendFee() {
  if (!activeSteward.value) return
  busyAmendFee.value = true
  const feeCents = Math.round(parseFloat(amendFeeForm.fee_cents_display || '0') * 100)
  router.post(route('provider.stewards.update-fee', { steward: activeSteward.value.id }), {
    fee_cents:     feeCents,
  }, {
    preserveScroll: true,
    onSuccess: () => { modals.value.amendFee = false; toast.success('Fee amendment created. Awaiting CS countersignature.'); router.reload({ only: ['stewards'] }) },
    onError:   (errors) => toast.error(errors.fee_cents ?? 'Could not create fee amendment.'),
    onFinish:  () => { busyAmendFee.value = false },
  })
}

watch(() => modals.value.editCS, (open) => {
  if (open && activeSteward.value) {
    const s = activeSteward.value
    editForm.role         = s.role ?? 'primary'
    editForm.vault_access = s.vault_access ?? 'scoped'
    editForm.notes        = ''
  }
})

const hasPendingFeeAmendment = computed(() => activeSteward.value?.has_pending_fee_amendment ?? false)

function submitEdit() {
  if (!activeSteward.value) return
  busyEdit.value = true
  editForm.post(route('provider.stewards.update', { steward: activeSteward.value.id }), {
    preserveScroll: true,
    onSuccess: () => { modals.value.editCS = false; toast.success('Steward details updated.'); router.reload({ only: ['stewards'] }) },
    onError:   () => toast.error('Could not update steward details.'),
    onFinish:  () => { busyEdit.value = false },
  })
}

// ── Change Role form ──────────────────────────────────────────────────────────


// ── Vault Access form ─────────────────────────────────────────────────────────


// ── Annual Review state ───────────────────────────────────────────────────────
const annualReviewOverdue = computed(() =>
    props.planStatus === 'annual_review_due' ||
    (props.annualReviewDate && new Date(props.annualReviewDate) < new Date())
)
const reviewInProgress = computed(() => props.planStatus === 'draft')

// ── Annual Review form ────────────────────────────────────────────────────────


// ── End Retainer form ─────────────────────────────────────────────────────────
const endRetainerForm = useForm({ action: '', reason: '', details: '' })
const busyEndRetainer = ref(false)
const busyReinstate   = ref(false)

function submitEndRetainer() {
  if (!activeSteward.value) return
  busyEndRetainer.value = true
  const routeName = endRetainerForm.action === 'suspend'
    ? 'provider.stewards.suspend'
    : 'provider.stewards.terminate'
  endRetainerForm.post(route(routeName, { steward: activeSteward.value.id }), {
    preserveScroll: true,
    onSuccess: () => {
      modals.value.endRetainer = false
      toast.success(endRetainerForm.action === 'suspend'
        ? 'Retainer paused. Reinstate anytime from the Suspended tab.'
        : 'Retainer terminated.')
      router.reload({ only: ['stewards', 'pendingInvitations', 'suspended', 'csCount'] })
    },
    onError:  () => toast.error('Could not end retainer.'),
    onFinish: () => { busyEndRetainer.value = false },
  })
}

// ── Resend Invitation form ────────────────────────────────────────────────────
const resendForm = useForm({ expiry_days: 14, message: '' })
const busyResend = ref(false)

function submitResend() {
  if (!activePending.value) return
  busyResend.value = true
  resendForm.post(route('provider.stewards.resend-invite', { steward: activePending.value.id }), {
    preserveScroll: true,
    onSuccess: () => { modals.value.resend = false; toast.success('Invitation resent.') },
    onError:   () => toast.error('Could not resend invitation.'),
    onFinish:  () => { busyResend.value = false },
  })
}

// ── Cancel Invitation form ────────────────────────────────────────────────────
const busyCancel = ref(false)

function submitCancelInvite() {
  if (!activePending.value) return
  busyCancel.value = true
  router.delete(route('provider.stewards.cancel-invite', { steward: activePending.value.id }), {
    preserveScroll: true,
    onSuccess: () => {
      modals.value.cancelInvite = false
      toast.success('Invitation cancelled.')
      router.reload({ only: ['pendingInvitations', 'csCount'] })
    },
    onError:   () => toast.error('Could not cancel invitation.'),
    onFinish:  () => { busyCancel.value = false },
  })
}



// ── Helpers ────────────────────────────────────────────────────────────────────
function formatMoney(cents) {
  if (!cents) return '$0.00'
  return '$' + (cents / 100).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

function initials(name) {
  if (!name) return '??'
  return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()
}

function stewardName(s) {
  return s.steward?.display_name ?? s.display_name ?? s.email ?? '—'
}

function stewardOrg(s) {
  const parts = [s.steward?.title ?? s.title, s.steward?.organization ?? s.organization, 'Aegis Certified Continuity Steward']
  return parts.filter(Boolean).join(' · ')
}

function stewardPhone(s) { return s.steward?.phone ?? s.phone ?? null }
function stewardEmail(s) { return s.steward?.email ?? s.email ?? null }

function csRoleLabel(role) {
  if (role === 'primary')   return 'Primary Continuity Steward'
  if (role === 'alternate') return 'Alternate Continuity Steward'
  return 'Support Continuity Steward'
}

function fmtDate(val) {
  if (!val) return ''
  try { return new Date(val).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }
  catch { return '' }
}

function authIncidentLabels(steward) {
  // Use pre-computed authorized_incidents from controller when available
  if (steward?.authorized_incidents) {
    return steward.authorized_incidents.map(t => t.replace(/_/g, ' '))
  }
  // Fallback: compute from incidentConfigs prop
  if (!props.incidentConfigs.length) return []
  return props.incidentConfigs
    .filter(c => c.is_active && (c.authorized_cs_ids ?? []).includes(steward.id))
    .map(c => c.incident_type.replace(/_/g, ' '))
}

// ── Available as CS toggle ─────────────────────────────────────────────────────
const availableAsCsLocal = ref(props.availableAsCs ?? false)
const csAvailSaving = ref(false)
function saveAvailableAsCs(val) {
  availableAsCsLocal.value = val
  csAvailSaving.value = true
  router.post(route('provider.settings.cs-availability'), { available_as_cs: val }, {
    preserveScroll: true,
    onError:   () => { availableAsCsLocal.value = !val; toast.error('Could not update CS availability.') },
    onFinish:  () => { csAvailSaving.value = false },
  })
}

// ── Tier gate ──────────────────────────────────────────────────────────────────
const atLimit = computed(() => props.csCount >= props.csMax)
const hasAlternate = computed(() => props.stewards.some(s => s.role === 'alternate'))

// atProviderCap: this user is at their provider-as-CS cap (serving as CS on other plans)
const atProviderCap = computed(() => props.providerAsCsCount >= props.providerAsCsCap)

function handleAddCS() {
  if (atLimit.value) {
    modals.value.upgrade = true
    return
  }
  modals.value.designateCs = true
}

// ── Notify prefs ───────────────────────────────────────────────────────────────
const notifyToggles = ref({
  re_attestation_complete:   props.notifyPrefs?.re_attestation_complete   ?? true,
  steward_requests_changes:  props.notifyPrefs?.steward_requests_changes  ?? true,
  steward_updates_info:      props.notifyPrefs?.steward_updates_info      ?? true,
  roles_permissions_change:  props.notifyPrefs?.roles_permissions_change  ?? true,
  documents_accessed:        props.notifyPrefs?.documents_accessed        ?? true,
  steward_added_removed:     props.notifyPrefs?.steward_added_removed     ?? true,
  critical_incident_reported:props.notifyPrefs?.critical_incident_reported?? true,
  continuity_response:       props.notifyPrefs?.continuity_response       ?? true,
})
const notifySaving = ref(false)

function saveNotifyPrefs() {
  notifySaving.value = true
  router.put(route('provider.settings.notifications'), { cs_notify: notifyToggles.value }, {
    preserveScroll: true,
    onSuccess: () => toast.success('Notification preferences saved.'),
    onError:   () => toast.error('Could not save preferences.'),
    onFinish:  () => { notifySaving.value = false },
  })
}
</script>

<template>
  <AppLayout portal="provider" activePage="continuity-stewards" pageTitle="Continuity Stewards">

    <!-- HERO BANNER — quiet variant -->
    <AegisHeroBanner
      eyebrow="Continuity Planning"
      title="Continuity Stewards"
      subtitle="Designate trusted stewards to execute your practice continuity plan. Retainer agreements remain active until cancelled — invoices are generated only when a critical incident closes and steward tasks are completed."
      quiet
    >
      <template #actions>
        <a :href="route('provider.activity') + '?module=continuity_stewards'" class="btn-hero-ghost is-on-light" style="display:inline-flex;align-items:center;gap:6px;">
          <AegisIcon name="activity" :size="14" /> Activity
        </a>
        <a :href="route('provider.network.index') + '?tab=cs'" class="btn-hero-ghost is-on-light" style="display:inline-flex;align-items:center;gap:6px;">
          <AegisIcon name="search" :size="14" /> Browse CS Directory
        </a>
        <button type="button" class="btn-hero-solid is-on-light" style="display:inline-flex;align-items:center;gap:6px;" @click="handleAddCS">
          <AegisIcon name="plus" :size="14" /> Sign Retainer Agreement
        </button>
      </template>
    </AegisHeroBanner>

    <!-- ANNUAL REVIEW ALERT -->
    <PlanReviewAlert
      :plan-status="planStatus"
      :annual-review-date="annualReviewDate"
      :has-draft-in-progress="hasDraftInProgress"
      :draft-plan-version="draftPlanVersion"
      context="cs"
    />

    <!-- TIER INFO ALERT — always shown -->
    <div class="alert alert-info" style="margin-bottom:14px;">
      <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
      <div class="alert-content" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <span>Your Continuity <strong>{{ tier === 'access' ? 'Access' : 'Practice' }}</strong> plan includes up to <strong>{{ csMax }} Continuity Steward{{ csMax !== 1 ? 's' : '' }}</strong> &middot; <strong>{{ csCount }} of {{ csMax }}</strong> in use{{ atLimit ? ' (limit reached)' : '' }}.</span>
        <a v-if="atLimit" href="#" style="font-weight:700;color:var(--gold-dark);text-decoration:none;" @click.prevent="modals.upgrade = true">
          {{ tier === 'access' ? 'Upgrade for more →' : (hasCsAddon ? 'Contact support' : 'Add CS Add-On →') }}
        </a>
      </div>
    </div>

    <!-- STAT CHIPS — sibling of hero, never inside -->
    <div class="stat-chips-row">
      <AegisStatChip icon="users" :value="stewards.filter(s => s.status === 'active').length" label="Active Continuity Stewards" />
      <AegisStatChip icon="mail"  :value="pendingInvitations.length" label="Pending Invitation" />
      <AegisStatChip v-if="servingAsCSFor.length" icon="check-circle" :value="servingAsCSFor.length" label="I'm Continuity Steward For" />
      <AegisStatChip
        icon="calendar"
        :value="annualReviewOverdue ? 'Review Overdue' : reviewInProgress ? 'In Progress' : 'On Track'"
        label="Annual Review"
        :icon-bg="annualReviewOverdue ? 'var(--icon-bg-red)' : reviewInProgress ? 'var(--icon-bg-blue)' : 'var(--icon-bg-green)'"
        :color="annualReviewOverdue ? 'var(--red-dark)' : reviewInProgress ? 'var(--blue-dark)' : 'var(--green-dark)'"
      />
    </div>

    <!-- TABS -->
    <div class="tabs-segmented" style="margin-bottom:14px;">
      <button class="tab-pill" :class="{ active: activeTab === 'myexec' }" @click="activeTab = 'myexec'">
        My Continuity Stewards <span class="badge-pill">{{ stewards.length }}</span>
      </button>
      <button class="tab-pill" :class="{ active: activeTab === 'pending' }" @click="activeTab = 'pending'">
        Pending <span class="badge-pill">{{ pendingInvitations.length }}</span>
      </button>
      <button v-if="suspended?.length" class="tab-pill" :class="{ active: activeTab === 'suspended' }" @click="activeTab = 'suspended'">
        Suspended <span class="badge-pill">{{ suspended.length }}</span>
      </button>
      <button class="tab-pill" :class="{ active: activeTab === 'for' }" @click="activeTab = 'for'">
        I'm Continuity Steward For <span class="badge-pill">{{ servingAsCSFor.length }}</span>
      </button>
      <button class="tab-pill" :class="{ active: activeTab === 'notifications' }" @click="activeTab = 'notifications'">
        Notifications
      </button>
    </div>

    <!-- ═══════════════════ TAB: MY CONTINUITY STEWARDS ═══════════════════ -->
    <div v-show="activeTab === 'myexec'">
      <div style="font-size:13px;color:var(--text-2);font-weight:600;margin-bottom:16px;line-height:1.5;">Trusted individuals who will manage your practice in an emergency or upon your incapacity.</div>

      <AegisEmptyState
        v-if="!stewards.length"
        icon="shield"
        title="No Active Continuity Stewards"
        description="Designate a Continuity Steward to manage your practice if you become unavailable."
      >
        <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center;">
          <button type="button" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;" @click="handleAddCS">
            <AegisIcon name="user-plus" :size="14" /> Add Continuity Steward
          </button>
          <a :href="route('provider.network.index') + '?tab=cs'" class="btn btn-outline" style="display:inline-flex;align-items:center;gap:6px;">
            <AegisIcon name="search" :size="14" /> Browse CS Directory
          </a>
        </div>
      </AegisEmptyState>

      <!-- STEWARD CARDS -->
      <div
        v-for="s in stewards"
        :key="s.id"
        class="card exec-card"
        :class="s.role === 'primary' ? 'primary' : 'secondary'"
        style="display:flex;align-items:flex-start;gap:18px;padding:22px;margin-bottom:14px;position:relative;overflow:hidden;"
      >
        <!-- Left rail via inline style (mirrors PHP ::before) -->
        <span style="position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--gold-dark);"></span>

        <!-- Avatar -->
        <div style="width:58px;height:58px;border-radius:var(--radius);background:var(--gold-dark);color:var(--text-inverted);font-family:var(--font-serif);font-weight:700;font-size:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          {{ initials(stewardName(s)) }}
        </div>

        <!-- Content -->
        <div style="flex:1;min-width:0;">
          <!-- Name + badges row -->
          <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:4px;">
            <span style="font-family:var(--font-serif);font-size:17px;font-weight:700;color:var(--gold-dark);cursor:pointer;" class="exec-name is-link">{{ stewardName(s) }}</span>
            <AegisBadge :label="csRoleLabel(s.role)" variant="gold" icon="shield" />
            <span class="badge badge-green"><span class="status-dot green"></span> Active</span>
            <span v-if="s.signed_at" style="font-size:11px;color:var(--text-3);">Retainer since {{ fmtDate(s.signed_at) }}</span>
            <span v-if="s.engagement_document?.status === 'fully_executed'" class="badge badge-green" :data-tooltip="'Retainer active since ' + fmtDate(s.signed_at)"><AegisIcon name="check" :size="11" /> Retainer Active</span>
            <span v-else-if="s.engagement_document?.status === 'countersign_pending'" class="badge badge-amber" data-tooltip="Agreement sent — awaiting countersignature"><AegisIcon name="clock" :size="11" /> Awaiting Countersignature</span>
            <span
              :class="{
                'badge': true,
                'badge-grey':  s.vault_access === 'none' || !s.vault_access,
                'badge-gold':  s.vault_access === 'metadata',
                'badge-green': s.vault_access === 'scoped' || s.vault_access === 'full',
              }"
            >
              <AegisIcon name="lock" :size="10" style="margin-right:3px;" />
              <template v-if="s.vault_access === 'none' || !s.vault_access">No Vault Access</template>
              <template v-else-if="s.vault_access === 'metadata'">Metadata Only</template>
              <template v-else-if="s.vault_access === 'scoped'">Scoped Access</template>
              <template v-else-if="s.vault_access === 'full'">Full Access</template>
            </span>
          </div>

          <!-- Sub line -->
          <div style="font-size:12px;color:var(--text-3);margin-top:2px;">{{ stewardOrg(s) }}</div>

          <!-- Meta row -->
          <div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:10px;font-size:12px;color:var(--text-3);">
            <span v-if="stewardPhone(s)" style="display:flex;align-items:center;gap:5px;"><AegisIcon name="phone" :size="13" />{{ stewardPhone(s) }}</span>
            <span v-if="stewardEmail(s)" style="display:flex;align-items:center;gap:5px;"><AegisIcon name="mail" :size="13" />{{ stewardEmail(s) }}</span>
            <span v-else style="display:flex;align-items:center;gap:5px;"><AegisIcon name="message-square" :size="13" />Via Aegis Messaging</span>
            <span v-if="s.signed_at" style="display:flex;align-items:center;gap:5px;"><AegisIcon name="pencil" :size="13" />Signed {{ fmtDate(s.signed_at) }}</span>
            <span v-if="s.review_due_at" style="display:flex;align-items:center;gap:5px;"><AegisIcon name="calendar" :size="13" />Review Due: {{ fmtDate(s.review_due_at) }}</span>
          </div>

          <!-- Responsibilities list -->
          <div v-if="s.responsibilities && s.responsibilities.length" style="background:var(--surface-2);border-radius:var(--radius);padding:6px 16px;margin-top:14px;border:1px solid var(--border);">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-3);margin-bottom:8px;">
              {{ s.role === 'primary' ? 'Authorized Responsibilities' : 'Authorized Responsibilities (Backup)' }}
            </div>
            <div v-for="r in s.responsibilities" :key="r.text" style="display:flex;align-items:flex-start;gap:10px;padding:9px 0;font-size:13px;color:var(--text-2);border-bottom:1px solid var(--border);">
              <div style="width:28px;height:28px;border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;flex-shrink:0;background:var(--badge-bg-gold);color:var(--gold-dark);">
                <AegisIcon :name="r.icon || 'check'" :size="14" />
              </div>
              <span>{{ r.text }}</span>
            </div>
          </div>

          <!-- Action buttons -->
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;">
            <button type="button" class="btn-icon" data-tooltip="View Retainer" @click="viewAgreementSteward = {...s, _incidentLabels: authIncidentLabels(s)}; showViewAgreement = true"><AegisIcon name="file-text" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="Edit Details"   @click="openEditModal(s)"><AegisIcon name="pencil" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="Message this CS" :disabled="msgLoading === s.steward_id" @click="openConversation(s.steward_id)"><AegisIcon name="message-square" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="End retainer" @click="openEndRetainer(s)">
              <AegisIcon name="x" :size="14" style="color:var(--red-dark);" />
            </button>
          </div>
        </div>
      </div>

      <!-- ADD ALTERNATE CTA -->
      <div v-if="stewards.length && !hasAlternate" class="upload-zone" style="cursor:pointer;margin-bottom:14px;" @click="handleAddCS">
        <div class="upload-zone-icon"><AegisIcon name="user-plus" :size="22" /></div>
        <div class="upload-zone-title">Add Alternate Continuity Steward (Optional)</div>
        <div class="upload-zone-sub">Recommended for solo practitioners. An Alternate steps in if your Primary is unavailable, keeping your coverage uninterrupted.</div>
      </div>

    </div>

    <!-- ═══════════════════ TAB: PENDING ═══════════════════ -->
    <div v-show="activeTab === 'pending'">

      <!-- OUTGOING INVITATIONS -->
      <div>
        <div style="font-family:var(--font-serif);font-size:15px;font-weight:700;color:var(--text);margin-bottom:4px;">Outgoing Invitations</div>
        <div style="font-size:12px;color:var(--text-3);margin-bottom:12px;">Invitations you've sent that are awaiting a response.</div>

        <AegisEmptyState
          v-if="!pendingInvitations.length"
          icon="mail"
          title="No Outstanding Invitations"
          description="Invitations you send to prospective Continuity Stewards will appear here."
        />

        <div
          v-for="inv in pendingInvitations"
          :key="inv.id"
          class="card exec-card pending"
          style="display:flex;align-items:flex-start;gap:18px;padding:20px 22px;margin-bottom:8px;position:relative;overflow:hidden;"
        >
          <span style="position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--orange-dark);"></span>
          <div style="width:48px;height:48px;border-radius:var(--radius);background:var(--gold-dark);color:var(--text-inverted);font-family:var(--font-serif);font-weight:700;font-size:17px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            {{ initials(inv.display_name ?? inv.email ?? '') }}
          </div>
          <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:4px;">
              <component
                :is="inv.slug ? 'a' : 'span'"
                :href="inv.slug ? '/continuity-steward/' + inv.slug : undefined"
                style="font-family:var(--font-serif);font-size:15px;font-weight:700;color:var(--gold-dark);text-decoration:none;"
              >{{ inv.display_name ?? inv.email }}</component>
              <span class="badge badge-orange"><AegisIcon name="clock" :size="12" /> Pending Response</span>
              <span v-if="inv.role" class="badge badge-gold"><AegisIcon name="shield" :size="10" /> {{ csRoleLabel(inv.role) }}</span>
            </div>
            <div style="display:flex;gap:14px;flex-wrap:wrap;font-size:12px;color:var(--text-3);">
              <span v-if="inv.email" style="display:flex;align-items:center;gap:5px;"><AegisIcon name="mail" :size="13" />{{ inv.email }}</span>
              <span v-if="inv.invited_at" style="display:flex;align-items:center;gap:5px;"><AegisIcon name="calendar" :size="13" />Invited: {{ fmtDate(inv.invited_at) }}</span>
              <span v-if="inv.expires_at" style="display:flex;align-items:center;gap:5px;"><AegisIcon name="clock" :size="13" />Expires: {{ fmtDate(inv.expires_at) }}</span>
            </div>
            <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
              <button type="button" class="btn-icon" data-tooltip="Resend Invitation"  @click="openResend(inv)"><AegisIcon name="send" :size="14" /></button>
              <button type="button" class="btn-icon" data-tooltip="Preview Retainer" @click="viewAgreementSteward = {...inv, _incidentLabels: []}; showViewAgreement = true"><AegisIcon name="eye" :size="14" /></button>
              <button type="button" class="btn-icon" data-tooltip="Cancel Invitation"  @click="openCancelInvite(inv)"><AegisIcon name="x" :size="14" /></button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════ TAB: SUSPENDED ═══════════════════ -->
    <div v-show="activeTab === 'suspended'">
      <p style="font-size:13px;color:var(--text-3);margin-bottom:16px">Continuity Stewards with temporarily suspended access.</p>
      <AegisEmptyState v-if="!suspended?.length" icon="pause-circle" title="No Suspended Stewards" description="Stewards with suspended access will appear here." />
      <div
          v-for="s in (suspended ?? [])"
          :key="s.id"
          class="card exec-card suspended"
          style="display:flex;align-items:flex-start;gap:18px;padding:20px 22px;margin-bottom:8px;position:relative;overflow:hidden;"
        >
          <span style="position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--red-dark);"></span>
          <div style="width:48px;height:48px;border-radius:var(--radius);background:var(--gold-dark);color:var(--text-inverted);font-family:var(--font-serif);font-weight:700;font-size:17px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            {{ initials(s.display_name ?? '') }}
          </div>
          <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:4px;">
              <a
                v-if="s.slug"
                :href="'/continuity-steward/' + s.slug"
                style="font-family:var(--font-serif);font-size:15px;font-weight:700;color:var(--gold-dark);text-decoration:none;"
              >{{ s.display_name ?? '—' }}{{ s.credentials ? ', ' + s.credentials : '' }}</a>
              <span
                v-else
                style="font-family:var(--font-serif);font-size:15px;font-weight:700;color:var(--gold-dark);"
              >{{ s.display_name ?? '—' }}{{ s.credentials ? ', ' + s.credentials : '' }}</span>
              <span class="badge badge-red"><AegisIcon name="lock" :size="12" /> Suspended</span>
              <span v-if="s.role" class="badge badge-gold"><AegisIcon name="shield" :size="10" /> {{ csRoleLabel(s.role) }}</span>
            </div>
            <div style="display:flex;gap:14px;flex-wrap:wrap;font-size:12px;color:var(--text-3);">
              <span v-if="s.email" style="display:flex;align-items:center;gap:5px;"><AegisIcon name="mail" :size="13" />{{ s.email }}</span>
              <span v-if="s.signed_at" style="display:flex;align-items:center;gap:5px;"><AegisIcon name="file-text" :size="13" />Retainer since {{ fmtDate(s.signed_at) }}</span>
              <span v-if="s.fee_cents > 0" style="display:flex;align-items:center;gap:5px;"><AegisIcon name="dollar-sign" :size="13" />{{ formatMoney(s.fee_cents) }} — on incident close</span>
              <span v-else-if="s.fee_cents === 0" style="display:flex;align-items:center;gap:5px;"><AegisIcon name="dollar-sign" :size="13" />Reciprocal (no payment)</span>
            </div>
            <div v-if="s.declined_reason" style="display:flex;align-items:center;gap:6px;margin-top:10px;font-size:12px;color:var(--red-dark);">
              <AegisIcon name="alert-circle" :size="13" />
              <span>{{ s.declined_reason }}</span>
            </div>
            <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
              <button type="button" class="btn" @click="openCsReinstate(s)" style="display:inline-flex;align-items:center;gap:6px;background:var(--black,#000);color:var(--white,#fff);border-color:var(--black,#000);"><AegisIcon name="check" :size="13" /> Reinstate</button>
            </div>
          </div>
        </div>
    </div>

    <!-- ═══════════════════ TAB: I'M CS FOR ═══════════════════ -->
    <div v-show="activeTab === 'for'">
      <div class="alert alert-info" style="margin-bottom:20px;">
        <div class="alert-icon"><AegisIcon name="shield" :size="18" /></div>
        <div class="alert-content">
          <div class="alert-title">You are a Continuity Steward for {{ servingAsCSFor.length }} provider{{ servingAsCSFor.length !== 1 ? 's' : '' }}.</div>
          <div>Run annual reviews, open the provider's vault, and complete your CS tasks directly from this tab. Open the full CS Portal for the unified caseload dashboard.</div>
          <div style="margin-top:10px;">
            <a :href="route('cs.dashboard')" class="btn btn-outline" style="display:inline-flex;align-items:center;gap:6px;"><AegisIcon name="shield" :size="13" /> Open CS Portal</a>
          </div>
        </div>
      </div>

      <!-- Summary stat chips -->
      <div v-if="servingAsCSFor.length" class="stat-chips-row" style="margin-bottom:20px;">
        <AegisStatChip
          icon="users"
          :value="servingAsCSFor.filter(p => p.status === 'active').length"
          label="Active Retainers"
        />
        <AegisStatChip
          icon="calendar"
          :value="servingAsCSFor.filter(p => p.review_due_at).length ? fmtDate(servingAsCSFor.filter(p => p.review_due_at).sort((a,b) => new Date(a.review_due_at) - new Date(b.review_due_at))[0].review_due_at) : 'None'"
          label="Next Review Due"
        />
        <AegisStatChip
          icon="check-circle"
          :value="servingAsCSFor.reduce((n, p) => n + (p.active_incidents ?? 0), 0)"
          label="Active Incidents"
        />
        <AegisStatChip
          icon="lock"
          :value="servingAsCSFor.filter(p => p.vault_access && p.vault_access !== 'none').length"
          label="Vaults Accessible"
        />
      </div>

      <!-- Empty state: tier-aware card when not serving as CS for anyone -->
      <div v-if="!servingAsCSFor.length" style="margin-bottom:20px">

        <!-- Practice + CS Add-On active: success alert + toggle only -->
        <div v-if="userTier === 'practice' && hasCsAddon">
          <div class="alert alert-success" style="margin-bottom:16px">
            <div class="alert-icon"><AegisIcon name="check-circle" :size="18" /></div>
            <div class="alert-content">
              <div class="alert-title">Your CS Add-On is active</div>
              <div>You can serve as Continuity Steward for up to <strong>{{ providerAsCsCap }} practitioners</strong>. Enable the toggle below to appear in the CS directory.</div>
            </div>
          </div>
          <div class="card" style="padding:18px 20px">
            <div class="toggle-row">
              <div class="toggle-info">
                <div class="toggle-label">Available as Continuity Steward</div>
                <div class="toggle-desc">Show in the CS directory so practitioners can invite you</div>
              </div>
              <div style="display:flex;align-items:center;gap:8px;">
                <span v-if="csAvailSaving" style="font-size:11px;color:var(--text-3);white-space:nowrap;">Saving…</span>
                <button
                  type="button"
                  class="toggle"
                  :class="{ on: availableAsCsLocal }"
                  :disabled="csAvailSaving"
                  :style="csAvailSaving ? 'opacity:0.45;cursor:not-allowed;pointer-events:none;' : ''"
                  @click="saveAvailableAsCs(!availableAsCsLocal)"
                  :aria-pressed="availableAsCsLocal"
                ></button>
              </div>
            </div>
          </div>
        </div>

        <!-- CS Business tier -->
        <div v-else-if="userTier === 'cs_business'">
          <div class="alert alert-info" style="margin-bottom:16px">
            <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
            <div class="alert-content">
              <div class="alert-title">Business CS account</div>
              <div>You can serve as Continuity Steward for up to <strong>40 practitioners</strong>. You have <strong>{{ providerAsCsCount }} of 40</strong> slots filled.</div>
            </div>
          </div>
          <div class="card" style="padding:18px 20px">
            <div class="toggle-row" style="margin-bottom:14px">
              <div class="toggle-info">
                <div class="toggle-label">Available as Continuity Steward</div>
                <div class="toggle-desc">Show in the CS directory so practitioners can invite you</div>
              </div>
              <div style="display:flex;align-items:center;gap:8px;">
                <span v-if="csAvailSaving" style="font-size:11px;color:var(--text-3);white-space:nowrap;">Saving…</span>
                <button
                  type="button"
                  class="toggle"
                  :class="{ on: availableAsCsLocal }"
                  :disabled="csAvailSaving"
                  :style="csAvailSaving ? 'opacity:0.45;cursor:not-allowed;pointer-events:none;' : ''"
                  @click="saveAvailableAsCs(!availableAsCsLocal)"
                  :aria-pressed="availableAsCsLocal"
                ></button>
              </div>
            </div>
            <a :href="route('cs.dashboard')" class="btn btn-outline" style="display:inline-flex;align-items:center;gap:6px;">
              <AegisIcon name="arrow-right" :size="13" /> Open CS Portal
            </a>
          </div>
        </div>

        <!-- Access, Practice no-addon, or fallback: full add-on card -->
        <div v-else style="border:1px solid var(--badge-border-gold);border-radius:var(--radius-lg);background:var(--icon-bg-gold);padding:18px;display:flex;align-items:flex-start;gap:16px;">
          <span style="width:36px;height:36px;border-radius:var(--radius);background:var(--badge-border-gold);color:var(--gold-dark);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <AegisIcon name="users" :size="17" />
          </span>
          <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:baseline;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
              <div style="font-family:var(--font-serif);font-size:16px;font-weight:700;color:var(--text);">Serve as Continuity Steward</div>
              <div style="font-size:13px;font-weight:700;color:var(--gold-dark);">
                <template v-if="userTier === 'access'"><strong>Free</strong><span style="font-size:11px;font-weight:400;color:var(--text-3);display:block;">Included with Access</span></template>
                <template v-else-if="userTier === 'practice'">+<strong>$25</strong>/mo<span style="font-size:11px;font-weight:400;color:var(--text-3);display:block;">CS Add-On</span></template>
              </div>
            </div>

            <!-- Tier-specific alert -->
            <div v-if="userTier === 'access'" class="alert alert-info" style="margin-bottom:14px;">
              <div class="alert-icon"><AegisIcon name="info" :size="14" /></div>
              <div class="alert-content">Access tier can serve as Continuity Steward for <strong>1 practitioner</strong> at no extra cost. To serve more, upgrade to Practice + CS Add-On ($25/mo) or Business CS ($49/mo).</div>
            </div>
            <div v-else-if="userTier === 'practice'" class="alert alert-info" style="margin-bottom:14px;">
              <div class="alert-icon"><AegisIcon name="info" :size="14" /></div>
              <div class="alert-content">Practice tier can serve as CS for up to <strong>3 practitioners</strong>. Add CS Add-On (+$25/mo) to serve up to <strong>43</strong>.</div>
            </div>

            <!-- Available as CS toggle -->
            <div class="toggle-row" style="margin-bottom:14px;">
              <div class="toggle-info">
                <div class="toggle-label">Available as Continuity Steward</div>
                <div class="toggle-desc">Show in the CS directory so practitioners can invite you</div>
              </div>
              <div style="display:flex;align-items:center;gap:8px;">
                <span v-if="csAvailSaving" style="font-size:11px;color:var(--text-3);white-space:nowrap;">Saving…</span>
                <button
                  type="button"
                  class="toggle"
                  :class="{ on: availableAsCsLocal }"
                  :disabled="csAvailSaving"
                  :style="csAvailSaving ? 'opacity:0.45;cursor:not-allowed;pointer-events:none;' : ''"
                  @click="saveAvailableAsCs(!availableAsCsLocal)"
                  :aria-pressed="availableAsCsLocal"
                ></button>
              </div>
            </div>

            <!-- Action buttons -->
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
              <a v-if="userTier === 'access'" :href="route('provider.settings.index') + '?section=billing'" class="btn btn-outline" style="font-size:12px;display:inline-flex;align-items:center;gap:6px;">
                <AegisIcon name="trending-up" :size="13" /> Upgrade to Practice — unlock CS Add-On
              </a>
              <a v-else-if="userTier === 'practice'" :href="route('provider.settings.index') + '?section=billing&highlight=cs_addon'" class="btn btn-gold" style="font-size:12px;display:inline-flex;align-items:center;gap:6px;">
                <AegisIcon name="plus" :size="13" /> Add CS Add-On (+$25/mo)
              </a>
              <a :href="route('provider.settings.index') + '?section=billing'" style="font-size:12px;color:var(--gold-dark);text-decoration:none;">Manage in Settings →</a>
            </div>
          </div>
        </div>

      </div>

      <!-- Serving status line when has active assignments -->
      <div v-if="servingAsCSFor.length" style="font-size:12px;color:var(--text-3);margin-bottom:12px;display:flex;align-items:center;gap:6px;">
        <AegisIcon name="check-circle" :size="13" style="color:var(--green-dark);" />
        Serving as CS for <strong style="color:var(--text);margin:0 2px;">{{ servingAsCSFor.length }}</strong> of <strong style="color:var(--text);margin:0 2px;">{{ providerAsCsCap }}</strong> practitioner{{ providerAsCsCap !== 1 ? 's' : '' }} (based on your plan)
      </div>

      <div class="list-group">
        <div v-for="prov in servingAsCSFor" :key="prov.id" class="list-group-item" style="background:var(--surface-white,#fff);">
          <div style="width:38px;height:38px;border-radius:var(--radius-sm);background:var(--gold-dark);color:var(--text-inverted);font-family:var(--font-serif);font-weight:700;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            {{ prov.avatar_initials || initials(prov.display_name ?? '') }}
          </div>
          <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
              <span style="font-size:13px;font-weight:700;color:var(--text);">{{ prov.display_name }}</span>
              <span v-if="prov.role === 'primary'" class="badge badge-gold"><AegisIcon name="shield" :size="10" /> Primary CS</span>
              <span v-else-if="prov.role === 'alternate'" class="badge badge-blue"><AegisIcon name="shield" :size="10" /> Alternate CS</span>
              <span v-else class="badge badge-neutral"><AegisIcon name="shield" :size="10" /> Support CS</span>
              <span v-if="prov.status === 'active'" class="badge badge-green"><span class="status-dot green"></span> Active</span>
              <span v-else-if="prov.status === 'pending'" class="badge badge-yellow"><span class="status-dot yellow"></span> Pending</span>
              <span v-else class="badge badge-neutral"><span class="status-dot"></span> {{ prov.status }}</span>
            </div>
            <div style="font-size:12px;color:var(--text-3);margin-top:2px;">
              {{ [prov.organization, prov.location].filter(Boolean).join(', ') }}{{ prov.review_due_at ? ' · Review due ' + fmtDate(prov.review_due_at) : '' }}
            </div>
          </div>
          <a v-if="prov.slug" :href="'/provider/' + prov.slug" class="btn-icon" data-tooltip="View Provider Profile"><AegisIcon name="external-link" :size="14" /></a>
          <a :href="route('cs.dashboard')" class="btn-icon" data-tooltip="Manage in CS Portal"><AegisIcon name="arrow-right" :size="14" /></a>
        </div>
      </div>
    </div>


    <!-- ═══════════════════ TAB: NOTIFICATIONS ═══════════════════ -->
    <div v-show="activeTab === 'notifications'">
      <div class="card">
        <div class="card-header">
          <div class="card-title-group">
            <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark);"><AegisIcon name="bell" :size="16" /></div>
            <div>
              <div class="card-title">CS Activity Notifications</div>
              <div class="card-subtitle">Choose when Aegis should alert you about activity involving your Continuity Stewards.</div>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">Annual Re-Attestation is complete</div>
              <div class="toggle-desc">Get notified when your Continuity Steward completes their annual re-attestation</div>
            </div>
            <AegisToggle v-model="notifyToggles.re_attestation_complete" />
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">CS requests changes</div>
              <div class="toggle-desc">Alert when a Continuity Steward submits a change request to your plan</div>
            </div>
            <AegisToggle v-model="notifyToggles.steward_requests_changes" />
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">CS updates their information</div>
              <div class="toggle-desc">Alert when a Continuity Steward updates their contact details or credentials</div>
            </div>
            <AegisToggle v-model="notifyToggles.steward_updates_info" />
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">Roles or permissions change</div>
              <div class="toggle-desc">Alert when a steward role or vault permission is modified</div>
            </div>
            <AegisToggle v-model="notifyToggles.roles_permissions_change" />
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">Important Documents are accessed</div>
              <div class="toggle-desc">Alert when a steward views or downloads a document from your vault</div>
            </div>
            <AegisToggle v-model="notifyToggles.documents_accessed" />
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">CS added, removed, or updated</div>
              <div class="toggle-desc">Alert when any Continuity Steward is added, removed, or has their retainer updated</div>
            </div>
            <AegisToggle v-model="notifyToggles.steward_added_removed" />
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">Critical incident reported</div>
              <div class="toggle-desc">Alert when a Support Steward reports a critical incident affecting your practice</div>
            </div>
            <AegisToggle v-model="notifyToggles.critical_incident_reported" />
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">Continuity Response activated</div>
              <div class="toggle-desc">Alert when your Continuity Response plan is formally activated</div>
            </div>
            <AegisToggle v-model="notifyToggles.continuity_response" />
          </div>
          <div class="btn-group" style="justify-content:flex-end;margin-top:20px;">
            <button type="button" class="btn btn-primary" :disabled="notifySaving" style="display:inline-flex;align-items:center;gap:6px;" @click="saveNotifyPrefs">
              <span v-if="notifySaving" class="spinner spinner-sm" />
              <AegisIcon v-else name="check" :size="13" />
              {{ notifySaving ? 'Saving…' : 'Save Preferences' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════ MODALS ═══ -->

    <!-- DESIGNATE CS MODAL — centralized, handles both Mode A and Mode B -->
    <DesignateCsModal
      v-model="modals.designateCs"
      :preselected-user="null"
      context="plan"
      @success="router.reload({ only: ['stewards', 'csCount', 'pendingInvitations'] })"
    />

    <!-- EDIT CS MODAL -->
    <AegisModal v-model="modals.editCS" title="Edit Continuity Steward" size="md" @close="modals.editCS=false">
      <div class="alert alert-info"><div class="alert-icon"><AegisIcon name="info" :size="14" /></div><div class="alert-content">Role changes will notify your Continuity Steward by email. No countersignature required.</div></div>

      <!-- Role -->
      <div class="form-group" style="margin-top:14px;">
        <label class="form-label">Role</label>
        <select v-model="editForm.role" class="form-control form-select">
          <option value="primary">Primary Continuity Steward</option>
          <option value="alternate">Alternate Continuity Steward</option>
        </select>
      </div>

      <!-- Fee (read-only) -->
      <div class="form-group">
        <label class="form-label">Agreed Fee</label>
        <div style="display:flex;align-items:center;gap:10px;">
          <div style="flex:1;">
            <span class="form-control" style="background:var(--surface-2);color:var(--text-2);cursor:default;display:block;">
              {{ activeSteward ? formatMoney(activeSteward.fee_cents) : '—' }}
            </span>
            <div style="font-size:11px;color:var(--text-3);margin-top:6px;line-height:1.4;display:flex;align-items:flex-start;gap:4px;">
              <AegisIcon name="info" :size="11" style="flex-shrink:0;margin-top:1px;" /><span>Invoiced when the critical incident closes and CS tasks are marked complete. Auto-charged 7 days after invoice if not paid manually.</span>
            </div>
          </div>
          <button
            v-if="!hasPendingFeeAmendment"
            type="button"
            class="btn btn-outline"
            style="white-space:nowrap;align-self:flex-start;"
            @click="modals.editCS=false;modals.amendFee=true"
          >Amend Fee →</button>
          <span v-else class="badge badge-amber" style="white-space:nowrap;flex-shrink:0;align-self:flex-start;"><AegisIcon name="clock" :size="11" /> Pending Signature</span>
        </div>
        <div v-if="hasPendingFeeAmendment" class="alert alert-warning" style="margin-top:8px;padding:8px 12px;">
          <div class="alert-icon"><AegisIcon name="clock" :size="13" /></div>
          <div class="alert-content" style="font-size:12px;">A fee amendment is awaiting your Continuity Steward's countersignature. You can send a new amendment once they sign or decline.</div>
        </div>
      </div>

      <!-- Vault Access -->
      <div class="form-group">
        <label class="form-label">Vault Access</label>
        <select v-model="editForm.vault_access" class="form-control form-select">
          <option value="scoped">Emergency Only (Recommended)</option>
          <option value="full">Full Read Access (Anytime)</option>
          <option value="none">No Access</option>
        </select>
      </div>

      <!-- Notes -->
      <div class="form-group">
        <label class="form-label">Notes / Instructions</label>
        <textarea v-model="editForm.notes" class="form-control" style="min-height:80px;" placeholder="Any updates or context…"></textarea>
      </div>

      <!-- Authorized Incidents (read-only) -->
      <div class="form-group">
        <label class="form-label">Authorized Incident Types</label>
        <div v-if="activeSteward && authIncidentLabels(activeSteward).length" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:6px;">
          <span v-for="label in authIncidentLabels(activeSteward)" :key="label" class="badge badge-gold" style="text-transform:capitalize;">{{ label }}</span>
        </div>
        <div v-else-if="props.incidentConfigs.some(c => c.is_active)" style="font-size:12px;color:var(--text-3);margin-bottom:6px;">Not authorized for any incidents yet.</div>
        <div v-else style="font-size:12px;color:var(--text-3);margin-bottom:6px;">No incident types configured.</div>
        <a :href="route('provider.plan.index') + '#incident-grid'" style="font-size:12px;color:var(--gold-dark);text-decoration:none;" @click.prevent="modals.editCS=false;router.visit(route('provider.plan.index') + '#incident-grid')">Configure on Plan →</a>
      </div>

      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.editCS=false">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="busyEdit" style="display:inline-flex;align-items:center;gap:6px;" @click="submitEdit">
          <span v-if="busyEdit" class="spinner spinner-sm" />
          <AegisIcon v-else name="check" :size="13" />
          {{ busyEdit ? 'Saving…' : 'Save Changes' }}
        </button>
      </template>
    </AegisModal>





    <!-- AMEND FEE MODAL -->
    <AegisModal v-model="modals.amendFee" title="Amend CS Fee" size="sm" @close="modals.amendFee=false">
      <!-- Pending guard -->
      <div v-if="hasPendingFeeAmendment" class="alert alert-warning">
        <div class="alert-icon"><AegisIcon name="clock" :size="14" /></div>
        <div class="alert-content">A fee amendment is already awaiting your Continuity Steward's countersignature. You can send a new amendment once they sign or decline the current one.</div>
      </div>
      <template v-if="!hasPendingFeeAmendment">
        <div class="alert alert-info"><div class="alert-icon"><AegisIcon name="info" :size="14" /></div><div class="alert-content">A fee amendment document will be sent to your Continuity Steward for countersignature. The fee updates once signed.</div></div>
        <div v-if="activeSteward" style="display:flex;align-items:center;gap:12px;margin:14px 0 4px;">
          <div style="width:38px;height:38px;border-radius:var(--radius);background:var(--gold-dark);color:var(--text-inverted);font-family:var(--font-serif);font-weight:700;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">{{ initials(stewardName(activeSteward)) }}</div>
          <div><div style="font-size:13px;font-weight:700;">{{ stewardName(activeSteward) }}</div><div style="font-size:11px;color:var(--text-3);">Current fee: {{ formatMoney(activeSteward.fee_cents) }}</div></div>
        </div>
        <div class="form-group" style="margin-top:14px;">
          <label class="form-label">New Fee (USD)</label>
          <div style="display:flex;align-items:center;gap:6px;">
            <span style="font-size:14px;color:var(--text-3);">$</span>
            <input v-model="amendFeeForm.fee_cents_display" type="number" min="0" step="0.01" class="form-control" placeholder="0.00" />
          </div>
        </div>
        <p style="font-size:11px;color:var(--text-3);margin-top:6px;line-height:1.4;display:flex;align-items:flex-start;gap:4px;"><AegisIcon name="info" :size="11" style="flex-shrink:0;margin-top:1px;" /><span>Invoiced when the critical incident closes and CS tasks are marked complete. Auto-charged 7 days after invoice if not paid manually.</span></p>
      </template>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.amendFee=false">{{ hasPendingFeeAmendment ? 'Close' : 'Cancel' }}</button>
        <button v-if="!hasPendingFeeAmendment" type="button" class="btn btn-primary" :disabled="busyAmendFee" style="display:inline-flex;align-items:center;gap:6px;" @click="submitAmendFee">
          <span v-if="busyAmendFee" class="spinner spinner-sm" />
          <AegisIcon v-else name="send" :size="13" />
          {{ busyAmendFee ? 'Sending…' : 'Send Amendment' }}
        </button>
      </template>
    </AegisModal>

    <!-- VIEW AGREEMENT MODAL -->
    <ViewCsAgreementModal
      v-model="showViewAgreement"
      :steward="viewAgreementSteward"
      @update:model-value="v => { showViewAgreement = v; if (!v) viewAgreementSteward = null }"
    />





    <!-- END RETAINER — centralized EndStewardRetainerModal -->
    <EndStewardRetainerModal
      v-model="showRemoveModal"
      :steward="activeSteward"
      kind="cs"
      @success="router.reload({ only: ['stewards', 'pendingInvitations', 'suspended', 'csCount'] })"
    />

    <!-- RESEND INVITE MODAL -->
    <AegisModal v-model="modals.resend" title="Resend Invitation" size="sm" @close="modals.resend=false">
      <div v-if="activePending" style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
        <div style="width:42px;height:42px;border-radius:var(--radius);background:var(--gold-dark);color:var(--text-inverted);font-family:var(--font-serif);font-weight:700;font-size:15px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">{{ initials(activePending.display_name ?? activePending.email ?? '') }}</div>
        <div><div style="font-size:13px;font-weight:700;">{{ activePending.display_name ?? activePending.email }}</div><div style="font-size:11px;color:var(--text-3);">Originally invited {{ fmtDate(activePending.invited_at) }}</div></div>
      </div>
      <div class="form-group">
        <label class="form-label">New Expiration</label>
        <select v-model="resendForm.expiry_days" class="form-control form-select">
          <option :value="30">30 days from today</option>
          <option :value="14">14 days from today</option>
          <option :value="7">7 days from today</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Optional Follow-up Message</label>
        <textarea v-model="resendForm.message" class="form-control" style="min-height:70px;" placeholder="Hi, just following up on my Continuity Steward invitation…"></textarea>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.resend=false">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="busyResend" style="display:inline-flex;align-items:center;gap:6px;" @click="submitResend">
          <span v-if="busyResend" class="spinner spinner-sm" />
          <AegisIcon v-else name="send" :size="13" />
          {{ busyResend ? 'Sending…' : 'Resend Invitation' }}
        </button>
      </template>
    </AegisModal>

    <!-- CANCEL INVITE MODAL -->
    <AegisModal v-model="modals.cancelInvite" title="Cancel Invitation" size="sm" @close="modals.cancelInvite=false">
      <div style="text-align:center;padding:12px 0;">
        <div style="width:52px;height:52px;border-radius:var(--radius);background:var(--red-light);color:var(--red-dark);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;"><AegisIcon name="trash" :size="22" /></div>
        <div style="font-family:var(--font-serif);font-size:15px;font-weight:700;margin-bottom:8px;">Cancel invitation to {{ activePending?.display_name ?? activePending?.email }}?</div>
        <div style="font-size:13px;color:var(--text-2);">The invitation link will be deactivated. They will be notified by email that the invitation was withdrawn.</div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.cancelInvite=false">Keep Invitation</button>
        <button type="button" class="btn btn-danger" :disabled="busyCancel" style="display:inline-flex;align-items:center;gap:6px;" @click="submitCancelInvite">
          <span v-if="busyCancel" class="spinner spinner-sm" />
          <AegisIcon v-else name="x" :size="13" />
          {{ busyCancel ? 'Cancelling…' : 'Cancel Invitation' }}
        </button>
      </template>
    </AegisModal>




    <!-- UPGRADE MODAL -->
    <AegisModal v-model="modals.upgrade" title="CS Slot Limit Reached" size="md" @close="modals.upgrade=false">
      <div style="display:flex;flex-direction:column;gap:12px;padding-top:4px;">
        <div class="alert alert-warning">
          <div class="alert-icon"><AegisIcon name="lock" :size="16" /></div>
          <div class="alert-content">
            <div class="alert-title">You've reached your CS capacity</div>
            <!-- Access tier: upgrade to Practice -->
            <div v-if="tier === 'access'">
              Your Access plan supports up to <strong>{{ csMax }} Continuity Steward{{ csMax!==1?'s':'' }}</strong>.
              Upgrade to Continuity Practice to invite up to 2 CS — or upgrade to Business CS ($49/mo) to serve more practitioners.
            </div>
            <!-- Practice tier without addon: add CS addon -->
            <div v-else-if="tier === 'practice' && !hasCsAddon">
              Your Practice plan supports up to <strong>{{ csMax }} Continuity Stewards</strong>.
              Add the <strong>Practice CS Add-On (+$25/mo)</strong> to expand your capacity to 2 CS slots on your plan.
            </div>
            <!-- Practice + addon or other -->
            <div v-else>
              You've reached the maximum CS slots for your current plan. Contact support to discuss enterprise options.
            </div>
          </div>
        </div>
        <!-- Upgrade CTA -->
        <div v-if="tier === 'access'" style="padding:14px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius);">
          <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:6px;">Continuity Practice — $79/mo</div>
          <div style="font-size:12px;color:var(--text-3);">Up to 2 Continuity Steward invitations, 4 Support Stewards, full vault, referrals, and more.</div>
        </div>
        <div v-else-if="tier === 'practice' && !hasCsAddon" style="padding:14px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius);">
          <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:6px;">Practice CS Add-On — +$25/mo</div>
          <div style="font-size:12px;color:var(--text-3);">Adds an additional CS slot and increases your provider-as-CS capacity to 43.</div>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.upgrade=false">Not Now</button>
        <a :href="route('provider.settings.index') + '?section=billing'" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;">
          <AegisIcon name="arrow-right" :size="13" />
          {{ tier === 'access' ? 'Upgrade to Practice' : 'Add CS Add-On' }}
        </a>
      </template>
    </AegisModal>

    <!-- REINSTATE CS -->
    <AegisModal v-model="modals.reinstateCs" title="Reinstate Continuity Steward" size="sm" @close="modals.reinstateCs=false">
      <p>Reinstate <strong>{{ activeSteward?.display_name ?? activeSteward?.steward?.display_name ?? '' }}</strong> as your Continuity Steward?</p>
      <p style="font-size:12px;color:var(--text-3);margin-top:8px;">Their access will be restored. They will be notified by email.</p>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.reinstateCs=false">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="busyReinstate" style="display:inline-flex;align-items:center;gap:6px;" @click="submitCsReinstate">
          <span v-if="busyReinstate" class="spinner spinner-sm" />
          <AegisIcon v-else name="check" :size="13" />
          {{ busyReinstate ? 'Reinstating…' : 'Reinstate' }}
        </button>
      </template>
    </AegisModal>

  </AppLayout>
</template>

