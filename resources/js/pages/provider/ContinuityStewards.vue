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

const props = defineProps({
  stewards:           { type: Array,  default: () => [] },
  pendingInvitations: { type: Array,  default: () => [] },
  servingAsCSFor:     { type: Array,  default: () => [] },
  tierLimits:         { type: Object, default: () => ({}) },
  tier:               { type: String, default: 'access' },
  csMax:              { type: Number, default: 2 },
  csCount:            { type: Number, default: 0 },
  incidentConfigs:    { type: Array,  default: () => [] },
  annualReviewDue:    { type: String, default: null },
  notifyPrefs:        { type: Object, default: () => ({}) },
})

const toast = useToast()
const { confirmAction } = useConfirm()
const { openConversation, loading: msgLoading } = useMessageButton()

// ── Tab state ──────────────────────────────────────────────────────────────────
const activeTab = ref('myexec')

// ── Active record refs ──────────────────────────────────────────────────────────
const activeId      = ref(null)
const activeSteward = computed(() => props.stewards.find(s => s.id === activeId.value) ?? null)
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
  changeRole:     false,
  grantVault:     false,
  annualReview:   false,
  viewAgreement:  false,
  remove:         false,
  resend:         false,
  cancelInvite:   false,
  snoozeReview:   false,
  upgrade:        false,
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
function openChangeRole(s) {
  activeId.value = s.id
  changeRoleForm.role   = s.role ?? 'primary'
  changeRoleForm.reason = ''
  modals.value.changeRole = true
}
function openVaultModal(s) {
  activeId.value = s.id
  vaultForm.vault_access_level = 'emergency'
  modals.value.grantVault = true
}
function openRemoveModal(s) {
  activeId.value = s.id
  removeForm.reason  = ''
  removeForm.confirm = ''
  modals.value.remove = true
}
function openResend(inv) {
  activePendingId.value   = inv.id
  resendForm.expires_days = 30
  resendForm.message      = ''
  modals.value.resend = true
}
function openCancelInvite(inv) {
  activePendingId.value = inv.id
  modals.value.cancelInvite = true
}

// ── Edit CS form ─────────────────────────────────────────────────────────────
const editForm = useForm({
  display_name:   '',
  email:          '',
  phone:          '',
  organization:   '',
  title:          '',
  emergency_phone:'',
  notes:          '',
})
const busyEdit = ref(false)

watch(() => modals.value.editCS, (open) => {
  if (open && activeSteward.value) {
    const s = activeSteward.value
    editForm.display_name    = s.steward?.display_name ?? s.display_name ?? ''
    editForm.email           = s.steward?.email ?? s.email ?? ''
    editForm.phone           = s.steward?.phone ?? ''
    editForm.organization    = s.steward?.organization ?? ''
    editForm.title           = s.steward?.title ?? ''
    editForm.emergency_phone = ''
    editForm.notes           = ''
  }
})

function submitEdit() {
  if (!activeSteward.value) return
  busyEdit.value = true
  editForm.put(route('provider.stewards.invite', { steward: activeSteward.value.steward_id ?? activeSteward.value.id }), {
    preserveScroll: true,
    onSuccess: () => { modals.value.editCS = false; toast.success('Steward details updated.') },
    onError:   () => toast.error('Could not update steward details.'),
    onFinish:  () => { busyEdit.value = false },
  })
}

// ── Change Role form ──────────────────────────────────────────────────────────
const changeRoleForm = useForm({ role: 'primary', reason: '' })
const busyChangeRole = ref(false)

function submitChangeRole() {
  if (!activeSteward.value) return
  busyChangeRole.value = true
  router.post(route('provider.ss.update-role', { steward: activeSteward.value.id }), {
    role:   changeRoleForm.role,
    reason: changeRoleForm.reason,
  }, {
    preserveScroll: true,
    onSuccess: () => { modals.value.changeRole = false; toast.success('Role updated.'); router.reload({ only: ['stewards'] }) },
    onError:   () => toast.error('Could not update role.'),
    onFinish:  () => { busyChangeRole.value = false },
  })
}

// ── Vault Access form ─────────────────────────────────────────────────────────
const vaultForm = useForm({ vault_access_level: 'emergency' })
const busyVault = ref(false)

function submitVault() {
  if (!activeSteward.value) return
  busyVault.value = true
  router.post(route('provider.stewards.authorize', { steward: activeSteward.value.id }), {
    vault_access: vaultForm.vault_access_level,
  }, {
    preserveScroll: true,
    onSuccess: () => { modals.value.grantVault = false; toast.success('Vault access updated.') },
    onError:   () => toast.error('Could not update vault access.'),
    onFinish:  () => { busyVault.value = false },
  })
}

// ── Annual Review form ────────────────────────────────────────────────────────
const reviewForm = useForm({ notes: '', confirmed: true })
const busyReview = ref(false)

function submitReview() {
  busyReview.value = true
  reviewForm.post(route('provider.stewards.invite'), {
    preserveScroll: true,
    onSuccess: () => { modals.value.annualReview = false; toast.success('Annual review completed.') },
    onError:   () => toast.error('Could not complete review.'),
    onFinish:  () => { busyReview.value = false },
  })
}

// ── Remove form ───────────────────────────────────────────────────────────────
const removeForm = useForm({ reason: '', confirm: '', notes: '' })
const busyRemove = ref(false)

function submitRemove() {
  if (!activeSteward.value || removeForm.confirm !== 'REMOVE') return
  busyRemove.value = true
  router.delete(route('provider.stewards.remove', { steward: activeSteward.value.id }), {
    data: { reason: removeForm.reason, notes: removeForm.notes },
    preserveScroll: true,
    onSuccess: () => {
      modals.value.remove = false
      toast.success('Continuity Steward removed.')
      router.reload({ only: ['stewards', 'pendingInvitations', 'csCount'] })
    },
    onError:   () => toast.error('Could not remove steward.'),
    onFinish:  () => { busyRemove.value = false },
  })
}

// ── Resend Invitation form ────────────────────────────────────────────────────
const resendForm = useForm({ expires_days: 30, message: '' })
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
  if (!props.incidentConfigs.length) return []
  return props.incidentConfigs
    .filter(c => c.is_active && (c.authorized_cs_ids ?? []).includes(steward.id))
    .map(c => c.incident_type.replace(/_/g, ' '))
}

// ── Tier gate ──────────────────────────────────────────────────────────────────
const atLimit = computed(() => props.csCount >= props.csMax)
const hasAlternate = computed(() => props.stewards.some(s => s.role === 'alternate'))

function handleAddCS() {
  if (atLimit.value) {
    if (props.tier === 'access') { modals.value.upgrade = true }
    else { toast.info(`You've reached the maximum of ${props.csMax} Continuity Stewards on the Continuity Practice plan. Contact support to discuss enterprise options.`) }
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
      subtitle="Identify and support the trusted individuals who will carry out your Continuity Plan. Keeping agreements current helps maintain alignment and readiness over time."
      quiet
    >
      <template #actions>
        <a :href="route('provider.activity') + '?module=continuity_stewards'" class="btn-hero-ghost is-on-light" style="display:inline-flex;align-items:center;gap:6px;">
          <AegisIcon name="activity" :size="14" /> Activity
        </a>
        <button type="button" class="btn-hero-solid is-on-light" style="display:inline-flex;align-items:center;gap:6px;" @click="handleAddCS">
          <AegisIcon name="plus" :size="14" /> Add Continuity Steward
        </button>
      </template>
    </AegisHeroBanner>

    <!-- ANNUAL REVIEW ALERT -->
    <div v-if="annualReviewDue" class="alert alert-warning" style="margin-bottom:14px;">
      <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
      <div class="alert-content">
        <div class="alert-title">Annual Review Due — {{ fmtDate(annualReviewDue) }}</div>
        <div>Your Continuity Plan requires an annual attestation. Please confirm all Continuity Steward details and procedures are current.</div>
        <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;">
          <button type="button" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;" @click="modals.annualReview = true">
            <AegisIcon name="check" :size="14" /> Complete Review Now
          </button>
          <button type="button" class="btn btn-outline" style="display:inline-flex;align-items:center;gap:6px;" @click="modals.snoozeReview = true">
            <AegisIcon name="clock" :size="14" /> Remind Me Later
          </button>
        </div>
      </div>
    </div>

    <!-- TIER INFO ALERT — always shown -->
    <div class="alert alert-info" style="margin-bottom:14px;">
      <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
      <div class="alert-content" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <span>Your Continuity <strong>{{ tier === 'access' ? 'Access' : 'Practice' }}</strong> plan includes up to <strong>{{ csMax }} Continuity Steward{{ csMax !== 1 ? 's' : '' }}</strong> &middot; <strong>{{ csCount }} of {{ csMax }}</strong> in use{{ atLimit ? ' (limit reached)' : '' }}.</span>
        <a v-if="tier === 'access'" href="#" style="font-weight:700;color:var(--gold-dark);text-decoration:none;" @click.prevent="modals.upgrade = true">Upgrade for more &rarr;</a>
      </div>
    </div>

    <!-- STAT CHIPS — sibling of hero, never inside -->
    <div class="stat-chips-row">
      <AegisStatChip icon="users" :value="stewards.filter(s => s.status === 'active').length" label="Active Continuity Stewards" />
      <AegisStatChip icon="mail"  :value="pendingInvitations.length" label="Pending Invitation" />
      <AegisStatChip v-if="servingAsCSFor.length" icon="check-circle" :value="servingAsCSFor.length" label="I'm Continuity Steward For" />
      <AegisStatChip icon="calendar" :value="stewards.some(s => s.review_overdue) ? (stewards.filter(s => s.review_overdue).length + ' Overdue') : 'On Track'" label="Annual Review" />
    </div>

    <!-- TABS -->
    <div class="tabs-segmented" style="margin-bottom:14px;">
      <button class="tab-pill" :class="{ active: activeTab === 'myexec' }" @click="activeTab = 'myexec'">
        <AegisIcon name="users" :size="13" />
        My Continuity Stewards <span class="badge-pill">{{ stewards.length }}</span>
      </button>
      <button class="tab-pill" :class="{ active: activeTab === 'pending' }" @click="activeTab = 'pending'">
        <AegisIcon name="mail" :size="13" />
        Pending <span class="badge-pill">{{ pendingInvitations.length }}</span>
      </button>
      <button v-if="servingAsCSFor.length" class="tab-pill" :class="{ active: activeTab === 'for' }" @click="activeTab = 'for'">
        <AegisIcon name="check-circle" :size="13" />
        I'm Continuity Steward For <span class="badge-pill">{{ servingAsCSFor.length }}</span>
      </button>
      <button class="tab-pill" :class="{ active: activeTab === 'notifications' }" @click="activeTab = 'notifications'">
        <AegisIcon name="bell" :size="13" />
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
        <button type="button" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;" @click="handleAddCS">
          <AegisIcon name="user-plus" :size="14" /> Add Continuity Steward
        </button>
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
            <span style="font-family:var(--font-serif);font-size:17px;font-weight:700;color:var(--text);cursor:pointer;" class="exec-name is-link">{{ stewardName(s) }}</span>
            <AegisBadge :label="csRoleLabel(s.role)" variant="gold" icon="shield" />
            <span class="badge badge-green"><span class="status-dot green"></span> Active</span>
            <span v-if="s.countersigned_at" class="badge badge-green" :data-tooltip="'Countersigned ' + fmtDate(s.countersigned_at)"><AegisIcon name="check" :size="11" /> Countersigned</span>
            <span v-else class="badge badge-amber" data-tooltip="Agreement sent — awaiting countersignature"><AegisIcon name="clock" :size="11" /> Awaiting Countersignature</span>
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
            <button type="button" class="btn-icon" data-tooltip="View Agreement" @click="openModal('viewAgreement', s)"><AegisIcon name="file-text" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="Edit Details"   @click="openEditModal(s)"><AegisIcon name="pencil" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="Message this CS" :disabled="msgLoading === s.steward_id" @click="openConversation(s.steward_id)"><AegisIcon name="message-square" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="Annual Review"  @click="modals.annualReview = true"><AegisIcon name="calendar" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="Change Role"    @click="openChangeRole(s)"><AegisIcon name="refresh-cw" :size="14" /></button>
            <button type="button" class="btn-icon" data-tooltip="Vault Access"   @click="openVaultModal(s)"><AegisIcon name="lock" :size="14" /></button>

            <button type="button" class="btn-icon" data-tooltip="Remove" @click="openRemoveModal(s)"><AegisIcon name="trash" :size="14" /></button>
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
              <span style="font-family:var(--font-serif);font-size:15px;font-weight:700;color:var(--text);">{{ inv.display_name ?? inv.email }}</span>
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
              <button type="button" class="btn-icon" data-tooltip="Preview Agreement"  @click="openModal('viewAgreement')"><AegisIcon name="eye" :size="14" /></button>
              <button type="button" class="btn-icon" data-tooltip="Cancel Invitation"  @click="openCancelInvite(inv)"><AegisIcon name="x" :size="14" /></button>
            </div>
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
          label="Active Agreements"
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

      <!-- Provider list -->
      <div class="section-header" style="margin-bottom:10px;">
        <div class="section-title"><AegisIcon name="users" :size="16" /> Providers I'm Stewarding</div>
      </div>

      <div class="list-group">
        <AegisEmptyState
          v-if="!servingAsCSFor.length"
          icon="users"
          title="Not serving as CS yet"
          description="You are not currently serving as CS for any providers."
        />
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
              <div class="toggle-desc">Alert when any Continuity Steward is added, removed, or has their agreement updated</div>
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
              <AegisIcon v-if="notifySaving" name="refresh-cw" :size="13" class="btn-spin" />
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
      <div class="alert alert-info"><div class="alert-icon"><AegisIcon name="info" :size="14" /></div><div class="alert-content">Changes to name, email, or role will generate an amendment notice sent to the Continuity Steward for acknowledgment.</div></div>
      <div class="form-group" style="margin-top:14px;">
        <label class="form-label">Notes / Instructions</label>
        <textarea v-model="editForm.notes" class="form-control" style="min-height:80px;" placeholder="Any updates or context…"></textarea>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.editCS=false">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="busyEdit" style="display:inline-flex;align-items:center;gap:6px;" @click="submitEdit">
          <AegisIcon v-if="busyEdit" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="check" :size="13" />
          {{ busyEdit ? 'Saving…' : 'Save Changes' }}
        </button>
      </template>
    </AegisModal>

    <!-- CHANGE ROLE MODAL -->
    <AegisModal v-model="modals.changeRole" title="Change Continuity Steward Role" size="md" @close="modals.changeRole=false">
      <div v-if="activeSteward" style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
        <div style="width:42px;height:42px;border-radius:var(--radius);background:var(--gold-dark);color:var(--text-inverted);font-family:var(--font-serif);font-weight:700;font-size:15px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">{{ initials(stewardName(activeSteward)) }}</div>
        <div><div style="font-size:13px;font-weight:700;">{{ stewardName(activeSteward) }}</div><div style="font-size:11px;color:var(--text-3);">Currently: {{ csRoleLabel(activeSteward.role) }}</div></div>
      </div>
      <div class="alert alert-warning"><div class="alert-icon"><AegisIcon name="alert-triangle" :size="14" /></div><div class="alert-content">Changing the role will update the agreement and send an amendment to both parties for acknowledgment.</div></div>
      <div class="form-group" style="margin-top:14px;">
        <label class="form-label">New Role</label>
        <select v-model="changeRoleForm.role" class="form-control form-select">
          <option value="primary">Primary Continuity Steward</option>
          <option value="secondary">Support Continuity Steward</option>
          <option value="alternate">Alternate Continuity Steward</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Reason for Change (Optional)</label>
        <textarea v-model="changeRoleForm.reason" class="form-control" style="min-height:60px;" placeholder="e.g., Swapping roles for scheduling reasons"></textarea>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.changeRole=false">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="busyChangeRole" @click="submitChangeRole">{{ busyChangeRole ? 'Saving…' : 'Confirm Role Change' }}</button>
      </template>
    </AegisModal>

    <!-- GRANT VAULT ACCESS MODAL -->
    <AegisModal v-model="modals.grantVault" :title="'Manage Document Vault Access' + (activeSteward ? ' — ' + stewardName(activeSteward) : '')" size="lg" @close="modals.grantVault=false">
      <div class="alert alert-info"><div class="alert-icon"><AegisIcon name="info" :size="14" /></div><div class="alert-content">Control what documents this Continuity Steward can access and when. Vault access is unlocked automatically when continuity support is activated.</div></div>
      <div style="margin:16px 0;">
        <div class="form-label" style="margin-bottom:10px;">Access Level</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
          <label v-for="opt in [
            { value:'emergency_only', title:'Emergency Only (Recommended)', desc:'Vault unlocks automatically when continuity support is activated.' },
            { value:'full_read',      title:'Full Read Access (Now)',        desc:'Continuity Steward can view documents at any time.' },
            { value:'no_access',      title:'No Access',                    desc:'Emergency credentials must be shared separately.' },
          ]" :key="opt.value"
            style="display:flex;align-items:flex-start;gap:10px;padding:12px;border:2px solid var(--border);border-radius:var(--radius);cursor:pointer;"
            :style="vaultForm.vault_access_level===opt.value ? 'border-color:var(--gold-dark);' : ''"
            @click="vaultForm.vault_access_level = opt.value"
          >
            <input type="radio" :value="opt.value" v-model="vaultForm.vault_access_level" style="margin-top:3px;" />
            <div><strong style="font-size:13px;">{{ opt.title }}</strong><div style="font-size:12px;color:var(--text-3);">{{ opt.desc }}</div></div>
          </label>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.grantVault=false">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="busyVault" @click="submitVault">{{ busyVault ? 'Saving…' : 'Save Access Settings' }}</button>
      </template>
    </AegisModal>

    <!-- VIEW AGREEMENT MODAL -->
    <AegisModal v-model="modals.viewAgreement" :title="'Continuity Plan' + (activeSteward ? ' — ' + stewardName(activeSteward) : '')" size="lg" @close="modals.viewAgreement=false">
      <div class="alert alert-success"><AegisIcon name="check" :size="14" /><div>Agreement active · Both parties signed</div></div>
      <div style="background:var(--surface-2);border-radius:var(--radius);padding:22px;font-size:13px;line-height:1.8;color:var(--text-2);margin-top:12px;">
        <div style="font-family:var(--font-serif);font-size:17px;font-weight:700;color:var(--text);text-align:center;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid var(--border);">Aegis CONTINUITY PLAN</div>
        <p v-if="activeSteward"><strong>Continuity Steward:</strong> {{ stewardName(activeSteward) }} — {{ csRoleLabel(activeSteward?.role) }}</p>
        <p v-if="activeSteward?.signed_at"><strong>Agreement Date:</strong> {{ fmtDate(activeSteward.signed_at) }}</p>
        <br />
        <p><strong>Section 1. Purpose.</strong> This agreement establishes the designated Continuity Steward for the professional practice in the event of death, incapacitation, disability, voluntary retirement, license suspension, or any other event preventing the Provider from practicing.</p>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.viewAgreement=false">Close</button>
        <button type="button" class="btn btn-outline" style="display:inline-flex;align-items:center;gap:6px;"><AegisIcon name="download" :size="14" /> Download PDF</button>
        <button type="button" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;" @click="modals.viewAgreement=false;modals.annualReview=true"><AegisIcon name="calendar" :size="14" /> Start Annual Review</button>
      </template>
    </AegisModal>

    <!-- ANNUAL REVIEW MODAL -->
    <AegisModal v-model="modals.annualReview" title="Annual Continuity Steward Review" size="lg" @close="modals.annualReview=false">
      <div class="alert alert-warning"><div class="alert-icon"><AegisIcon name="alert-triangle" :size="14" /></div><div class="alert-content">Annual review{{ annualReviewDue ? ' due ' + fmtDate(annualReviewDue) : '' }}. Confirm all Continuity Steward details and procedures are current.</div></div>
      <div style="font-size:13px;font-weight:700;margin:14px 0 10px;color:var(--text);">Continuity Steward Availability Confirmation</div>
      <div v-for="item in stewards.filter(s=>s.status==='active')" :key="item.id" class="setting-row">
        <div class="setting-info"><div class="setting-label">{{ stewardName(item) }} ({{ csRoleLabel(item.role) }}) still agrees to serve</div></div>
        <button type="button" class="toggle on" aria-pressed="true"></button>
      </div>
      <div style="font-size:13px;font-weight:700;margin:16px 0 10px;color:var(--text);">Practice Information</div>
      <div v-for="item in [
        'Document Vault contains current client roster',
        'Malpractice insurance policy is current and on file',
        'Practice billing info and payer contacts documented',
        'Client transition protocol is documented and accessible',
        'Continuity Steward Vault access credentials have been updated',
      ]" :key="item" class="setting-row">
        <div class="setting-info"><div class="setting-label">{{ item }}</div></div>
        <button type="button" class="toggle" aria-pressed="false"></button>
      </div>
      <div class="form-group" style="margin-top:16px;">
        <label class="form-label">Review Notes / Changes Since Last Year</label>
        <textarea v-model="reviewForm.notes" class="form-control" placeholder="Document any personnel changes, updated responsibilities, or other relevant changes…"></textarea>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.annualReview=false">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="busyReview" style="display:inline-flex;align-items:center;gap:6px;" @click="submitReview">
          <AegisIcon v-if="busyReview" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="check" :size="13" />
          {{ busyReview ? 'Saving…' : 'Attest & Complete Review' }}
        </button>
      </template>
    </AegisModal>

    <!-- REMOVE MODAL -->
    <AegisModal v-model="modals.remove" title="Remove Continuity Steward" size="md" @close="modals.remove=false;removeForm.reset();v$remove.$reset()">
      <div class="alert alert-danger"><div class="alert-icon"><AegisIcon name="alert-triangle" :size="14" /></div><div class="alert-content"><strong>Warning:</strong> Removing a Continuity Steward voids the existing agreement. This action is permanent. The Continuity Steward will be notified by email.</div></div>
      <div v-if="activeSteward" style="display:flex;align-items:center;gap:12px;margin:14px 0;">
        <div style="width:42px;height:42px;border-radius:var(--radius);background:var(--gold-dark);color:var(--text-inverted);font-family:var(--font-serif);font-weight:700;font-size:15px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">{{ initials(stewardName(activeSteward)) }}</div>
        <div><div style="font-size:13px;font-weight:700;">{{ stewardName(activeSteward) }}</div><div style="font-size:11px;color:var(--text-3);">{{ csRoleLabel(activeSteward.role) }}</div></div>
      </div>
      <div class="form-group">
        <label class="form-label">Reason for Removal <span style="color:var(--red-dark);">*</span></label>
        <select v-model="removeForm.reason" class="form-control form-select" :class="fieldClass(v$remove,'reason')" @blur="v$remove.reason.$touch()">
          <option value="">— Select Reason —</option>
          <option>Continuity Steward is no longer available or willing</option>
          <option>Continuity Steward has relocated or left the organization</option>
          <option>Replacing with another Continuity Steward</option>
          <option>Mutual agreement to end arrangement</option>
          <option>Continuity Steward requested to be released</option>
          <option>Other</option>
        </select>
        <div v-if="fieldError(v$remove,'reason')" class="form-error">{{ fieldError(v$remove,'reason') }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm by typing "REMOVE"</label>
        <input v-model="removeForm.confirm" type="text" class="form-control" :class="fieldClass(v$remove,'confirm')" placeholder="Type REMOVE to confirm" style="border-color:var(--red);" @blur="v$remove.confirm.$touch()" />
        <div v-if="fieldError(v$remove,'confirm')" class="form-error">{{ fieldError(v$remove,'confirm') }}</div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.remove=false">Cancel</button>
        <button type="button" class="btn btn-danger" :disabled="busyRemove || removeForm.confirm!=='REMOVE'" style="display:inline-flex;align-items:center;gap:6px;" @click="submitRemove">
          <AegisIcon v-if="busyRemove" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="trash" :size="13" />
          {{ busyRemove ? 'Removing…' : 'Remove Continuity Steward' }}
        </button>
      </template>
    </AegisModal>

    <!-- RESEND INVITE MODAL -->
    <AegisModal v-model="modals.resend" title="Resend Invitation" size="sm" @close="modals.resend=false">
      <div v-if="activePending" style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
        <div style="width:42px;height:42px;border-radius:var(--radius);background:var(--gold-dark);color:var(--text-inverted);font-family:var(--font-serif);font-weight:700;font-size:15px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">{{ initials(activePending.display_name ?? activePending.email ?? '') }}</div>
        <div><div style="font-size:13px;font-weight:700;">{{ activePending.display_name ?? activePending.email }}</div><div style="font-size:11px;color:var(--text-3);">Originally invited {{ fmtDate(activePending.invited_at) }}</div></div>
      </div>
      <div class="form-group">
        <label class="form-label">New Expiration Date</label>
        <select class="form-control form-select"><option>30 days from today</option><option>14 days from today</option><option>7 days from today</option></select>
      </div>
      <div class="form-group">
        <label class="form-label">Optional Follow-up Message</label>
        <textarea class="form-control" style="min-height:70px;" placeholder="Hi, just following up on my Continuity Steward invitation…"></textarea>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.resend=false">Cancel</button>
        <button type="button" class="btn btn-primary" :disabled="busyResend" style="display:inline-flex;align-items:center;gap:6px;" @click="submitResend">
          <AegisIcon v-if="busyResend" name="refresh-cw" :size="13" class="btn-spin" />
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
          <AegisIcon v-if="busyCancel" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="x" :size="13" />
          {{ busyCancel ? 'Cancelling…' : 'Cancel Invitation' }}
        </button>
      </template>
    </AegisModal>

    <!-- SNOOZE REVIEW MODAL -->
    <AegisModal v-model="modals.snoozeReview" title="Remind Me Later" size="sm" @close="modals.snoozeReview=false">
      <div class="alert alert-warning"><div class="alert-icon"><AegisIcon name="alert-triangle" :size="14" /></div><div class="alert-content">Annual reviews are required for your continuity plan to remain compliant.</div></div>
      <div class="form-group" style="margin-top:14px;">
        <label class="form-label">Remind me in</label>
        <select class="form-control form-select"><option>3 days</option><option>1 week</option><option>2 weeks</option></select>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.snoozeReview=false">Cancel</button>
        <button type="button" class="btn btn-primary" @click="modals.snoozeReview=false">Set Reminder</button>
      </template>
    </AegisModal>


    <!-- UPGRADE MODAL -->
    <AegisModal v-model="modals.upgrade" title="Upgrade to Add More CS" size="md" @close="modals.upgrade=false">
      <div style="display:flex;flex-direction:column;gap:12px;padding-top:4px;">
        <div style="display:flex;align-items:flex-start;gap:9px;padding:10px 13px;background:var(--badge-bg-gold);border:1px solid var(--fade-gold);border-radius:var(--radius);">
          <AegisIcon name="award" :size="14" style="flex-shrink:0;margin-top:2px;color:var(--gold-dark);" />
          <span style="font-size:12.5px;color:var(--text-2);line-height:1.5;">Your <strong>Continuity Access</strong> plan supports up to <strong>{{ csMax }} Continuity Steward{{ csMax!==1?'s':'' }}</strong>. Upgrade to <strong>Continuity Practice</strong> to add more.</span>
        </div>
        <div style="padding:14px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius);">
          <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:6px;">Continuity Practice — $49/mo</div>
          <div style="font-size:12px;color:var(--text-3);">Up to 2 Continuity Stewards + 4 Support Stewards, full vault access, and more.</div>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="modals.upgrade=false">Not Now</button>
        <a :href="route('provider.settings.billing.portal')" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;"><AegisIcon name="arrow-right" :size="13" /> Upgrade Plan</a>
      </template>
    </AegisModal>

  </AppLayout>
</template>
