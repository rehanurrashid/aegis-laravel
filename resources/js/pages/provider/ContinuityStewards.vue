<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { useVuelidate } from '@vuelidate/core'
import { required, email, minLength, minValue, helpers, integer } from '@vuelidate/validators'
import AppLayout from '@/layouts/AppLayout.vue'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const props = defineProps({
  stewards:           { type: Array,  default: () => [] },
  pendingInvitations: { type: Array,  default: () => [] },
  servingAsCSFor:     { type: Array,  default: () => [] },
  tierLimits:         { type: Object, default: () => ({}) },
  tier:               { type: String, default: 'access' },
  csMax:              { type: Number, default: 2 },
  csCount:            { type: Number, default: 0 },
  incidentConfigs:    { type: Array,  default: () => [] },
})

const toast = useToast()
const { confirmAction } = useConfirm()

// ── Tab state ─────────────────────────────────────────────────────────────────
const activeTab = ref('mine')

// ── Active record refs ─────────────────────────────────────────────────────────
const activeId     = ref(null)
const activeSteward = computed(() => props.stewards.find(s => s.id === activeId.value) ?? null)
const activePendingId = ref(null)
const activePending  = computed(() => props.pendingInvitations.find(s => s.id === activePendingId.value) ?? null)

// ── Modal open state ───────────────────────────────────────────────────────────
const modals = ref({
  designate:  false,
  amendFee:   false,
  authSummary: false,
  remove:     false,
  resend:     false,
  cancelInvite: false,
  upgrade:    false,
})

function openModal(key, steward = null) {
  if (steward) activeId.value = steward.id
  modals.value[key] = true
}
function closeModal(key) {
  modals.value[key] = false
  activeId.value    = null
  activePendingId.value = null
}

// ── Busy refs ──────────────────────────────────────────────────────────────────
const busyDesignate  = ref(false)
const busyAmendFee   = ref(false)
const busyRemove     = ref(false)
const busyResend     = ref(false)
const busyCancel     = ref(false)

// ── Tier gate ─────────────────────────────────────────────────────────────────
const atLimit = computed(() => props.csCount >= props.csMax)

function handleAddCS() {
  if (atLimit.value) {
    if (props.tier === 'access') {
      modals.value.upgrade = true
    } else {
      toast.info(`Your ${props.tier === 'practice' ? 'Continuity Practice' : ''} plan allows up to ${props.csMax} Continuity Stewards.`)
    }
    return
  }
  modals.value.designate = true
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function formatMoney(cents) {
  if (!cents) return '$0.00'
  return '$' + (cents / 100).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

function initials(name) {
  if (!name) return '??'
  return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()
}

function avatarColor(name) {
  const colors = ['var(--gold-dark)', 'var(--blue-dark)', 'var(--green-dark)', 'var(--text-2)']
  const idx = (name || '').split('').reduce((a, c) => a + c.charCodeAt(0), 0) % colors.length
  return colors[idx]
}

function stewardName(s) {
  return s.user?.display_name ?? s.display_name ?? s.email ?? '—'
}

function stewardOrg(s) {
  return s.user?.organization ?? '—'
}

function authIncidentLabels(steward) {
  if (!props.incidentConfigs.length) return []
  return props.incidentConfigs
    .filter(c => c.is_active && (c.authorized_cs_ids ?? []).includes(steward.id))
    .map(c => c.incident_type.replace(/_/g, ' '))
}

// ── Designate form ─────────────────────────────────────────────────────────────
const designateStep = ref(1) // 1=Find, 2=Role+Fee, 3=Review
const designateForm = useForm({
  user_id:       null,
  email:         '',
  display_name:  '',
  role:          'primary',
  fee_cents:     0,
  payment_terms: 'per_incident',
  auto_charge:   false,
})
const designateFeeInput = ref('0.00') // dollars display

watch(designateFeeInput, val => {
  designateForm.fee_cents = Math.round(parseFloat(val || '0') * 100)
})

const designateRules = computed(() => ({
  email:        designateForm.user_id ? {} : { required: helpers.withMessage('Email is required', required), email: helpers.withMessage('Must be a valid email', email) },
  display_name: designateForm.user_id ? {} : { required: helpers.withMessage('Name is required', required), minLength: helpers.withMessage('Min 2 characters', minLength(2)) },
  role:         { required: helpers.withMessage('Role is required', required) },
  fee_cents:    { integer: helpers.withMessage('Must be a whole number of cents', integer), minValue: helpers.withMessage('Cannot be negative', minValue(0)) },
  payment_terms:{ required: helpers.withMessage('Payment terms required', required) },
}))

const v$designate = useVuelidate(designateRules, designateForm)

function fieldError(v$, field) {
  const f = v$.value[field]
  if (!f || !f.$dirty) return null
  const msg = f.$errors[0]?.$message
  if (msg) return msg
  if (designateForm.errors[field]) return designateForm.errors[field]
  return null
}

function fieldClass(v$, field) {
  return fieldError(v$, field) ? 'is-error' : ''
}

async function submitDesignate() {
  const ok = await v$designate.value.$validate()
  if (!ok) return
  busyDesignate.value = true
  designateForm.post(route('provider.stewards.invite'), {
    preserveScroll: true,
    onSuccess: () => {
      closeModal('designate')
      designateStep.value = 1
      designateForm.reset()
      v$designate.value.$reset()
      toast.success('Continuity Steward invited.')
    },
    onError: () => toast.error('Failed to send invitation.'),
    onFinish: () => { busyDesignate.value = false },
  })
}

// ── Amend fee form ────────────────────────────────────────────────────────────
const amendFeeForm = useForm({
  fee_cents:     0,
  payment_terms: 'per_incident',
})
const amendFeeInput = ref('0.00')

watch(amendFeeInput, val => {
  amendFeeForm.fee_cents = Math.round(parseFloat(val || '0') * 100)
})

watch(() => modals.value.amendFee, open => {
  if (open && activeSteward.value) {
    amendFeeForm.fee_cents     = activeSteward.value.fee_cents ?? 0
    amendFeeForm.payment_terms = activeSteward.value.payment_terms ?? 'per_incident'
    amendFeeInput.value = ((activeSteward.value.fee_cents ?? 0) / 100).toFixed(2)
  }
})

const amendFeeRules = computed(() => ({
  fee_cents:    { integer: helpers.withMessage('Must be a whole cent amount', integer), minValue: helpers.withMessage('Cannot be negative', minValue(0)) },
  payment_terms:{ required: helpers.withMessage('Payment terms required', required) },
}))
const v$amendFee = useVuelidate(amendFeeRules, amendFeeForm)

async function submitAmendFee() {
  const ok = await v$amendFee.value.$validate()
  if (!ok) return
  busyAmendFee.value = true
  amendFeeForm.post(route('provider.stewards.update-fee', { steward: activeId.value }), {
    preserveScroll: true,
    onSuccess: () => {
      closeModal('amendFee')
      amendFeeForm.reset()
      v$amendFee.value.$reset()
      toast.success('Fee amendment created. Awaiting CS countersignature.')
    },
    onError: () => toast.error('Failed to create amendment.'),
    onFinish: () => { busyAmendFee.value = false },
  })
}

// ── Remove form ───────────────────────────────────────────────────────────────
const removeForm = useForm({ reason: '', confirm: '' })
const removeRules = computed(() => ({
  reason:  { required: helpers.withMessage('Reason is required', required) },
  confirm: { required: helpers.withMessage('Type REMOVE to confirm', required) },
}))
const v$remove = useVuelidate(removeRules, removeForm)

function openRemoveModal(steward) {
  if (steward.has_outstanding_invoices) {
    toast.error('Cannot remove this CS — outstanding invoices exist. Resolve them in Finances first.')
    return
  }
  activeId.value = steward.id
  modals.value.remove = true
}

async function submitRemove() {
  const ok = await v$remove.value.$validate()
  if (!ok) return
  if (removeForm.confirm !== 'REMOVE') {
    toast.error('Type REMOVE exactly to confirm.')
    return
  }
  busyRemove.value = true
  removeForm.delete(route('provider.stewards.remove', { steward: activeId.value }), {
    preserveScroll: true,
    onSuccess: () => {
      closeModal('remove')
      removeForm.reset()
      v$remove.value.$reset()
      toast.success('Continuity Steward removed.')
    },
    onError: (errors) => {
      if (errors.remove) toast.error(errors.remove)
      else toast.error('Could not remove steward.')
    },
    onFinish: () => { busyRemove.value = false },
  })
}

// ── Resend invite ──────────────────────────────────────────────────────────────
function openResend(pending) {
  activePendingId.value = pending.id
  activeId.value = pending.id
  modals.value.resend = true
}

function submitResend() {
  busyResend.value = true
  router.post(route('provider.stewards.resend-invite', { steward: activePendingId.value }), {}, {
    preserveScroll: true,
    onSuccess: () => {
      closeModal('resend')
      toast.success('Invitation resent.')
    },
    onError: () => toast.error('Failed to resend invitation.'),
    onFinish: () => { busyResend.value = false },
  })
}

// ── Cancel invite ──────────────────────────────────────────────────────────────
function openCancelInvite(pending) {
  activePendingId.value = pending.id
  activeId.value = pending.id
  modals.value.cancelInvite = true
}

function submitCancelInvite() {
  busyCancel.value = true
  router.delete(route('provider.stewards.cancel-invite', { steward: activePendingId.value }), {
    preserveScroll: true,
    onSuccess: () => {
      closeModal('cancelInvite')
      toast.success('Invitation cancelled.')
    },
    onError: () => toast.error('Failed to cancel invitation.'),
    onFinish: () => { busyCancel.value = false },
  })
}

// ── Auth modal ─────────────────────────────────────────────────────────────────
function openAuthSummary(steward) {
  activeId.value = steward.id
  modals.value.authSummary = true
}
</script>

<template>
  <AppLayout portal="provider" activePage="continuity-stewards" pageTitle="Continuity Stewards">

    <!-- HERO BANNER -->
    <AegisHeroBanner
      eyebrow="Continuity Planning"
      title="Continuity Stewards"
      subtitle="Identify and empower the trusted individuals who will carry out your Continuity Plan. Designate, configure, and maintain your steward roster here."
      quiet
    >
      <template #actions>
        <a :href="route('provider.activity')" class="btn btn-outline" style="display:inline-flex;align-items:center;gap:6px;">
          <AegisIcon name="activity" :size="13" />
          Activity
        </a>
        <button
          type="button"
          class="btn btn-primary"
          :disabled="atLimit && tier !== 'access'"
          :data-tooltip="atLimit && tier !== 'access' ? `Plan limit: ${csMax} CS` : undefined"
          @click="handleAddCS"
          style="display:inline-flex;align-items:center;gap:6px;"
        >
          <AegisIcon name="user-plus" :size="13" />
          Add Continuity Steward
        </button>
      </template>
    </AegisHeroBanner>

    <!-- STAT CHIPS — sibling of hero, never inside -->
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px;">
      <AegisStatChip icon="users" :value="csCount" label="Active CS" />
      <AegisStatChip icon="clock" :value="pendingInvitations.length" label="Pending" />
      <AegisStatChip v-if="servingAsCSFor.length" icon="shield-check" :value="servingAsCSFor.length" label="Serving As CS" />
      <AegisStatChip icon="user-cog" :value="`${csCount} / ${csMax}`" label="Tier Capacity" />
    </div>

    <!-- TIER LIMIT NOTICE -->
    <div v-if="atLimit" class="info-banner info-banner--blue" style="margin-bottom:12px;">
      <div style="display:flex;align-items:flex-start;gap:9px;">
        <AegisIcon name="alert-circle" :size="14" style="flex-shrink:0;margin-top:2px;color:var(--blue-dark);" />
        <span>
          Your <strong>{{ tier === 'practice' ? 'Continuity Practice' : 'Continuity Access' }}</strong> plan includes up to <strong>{{ csMax }} Continuity Steward{{ csMax !== 1 ? 's' : '' }}</strong> — limit reached.
          <template v-if="tier === 'access'">
            <a :href="route('provider.settings.billing.portal')" style="color:var(--gold-dark);font-weight:600;margin-left:4px;">Upgrade for more</a>
          </template>
        </span>
      </div>
    </div>

    <!-- STRIPE CONNECT WARNING (any active CS with fee and no connect) -->
    <div v-if="stewards.some(s => s.fee_cents > 0 && !s.stripe_connected)" class="info-banner info-banner--gold" style="margin-bottom:12px;">
      <div style="display:flex;align-items:flex-start;gap:9px;">
        <AegisIcon name="alert-triangle" :size="14" style="flex-shrink:0;margin-top:2px;color:var(--gold-dark);" />
        <span>One or more Continuity Stewards have a fee set but have not connected a Stripe account. Payments cannot be processed until they complete Stripe Connect onboarding.</span>
      </div>
    </div>

    <!-- TABS -->
    <div class="tabs-segmented" style="margin-bottom:14px;">
      <button
        class="tab-pill"
        :class="{ active: activeTab === 'mine' }"
        @click="activeTab = 'mine'"
      >
        <AegisIcon name="users" :size="12" />
        My CS
        <AegisBadge v-if="stewards.length" :count="stewards.length" />
      </button>
      <button
        class="tab-pill"
        :class="{ active: activeTab === 'pending' }"
        @click="activeTab = 'pending'"
      >
        <AegisIcon name="clock" :size="12" />
        Pending
        <AegisBadge v-if="pendingInvitations.length" :count="pendingInvitations.length" variant="gold" />
      </button>
      <button
        v-if="servingAsCSFor.length"
        class="tab-pill"
        :class="{ active: activeTab === 'for' }"
        @click="activeTab = 'for'"
      >
        <AegisIcon name="shield-check" :size="12" />
        I'm CS For
        <AegisBadge :count="servingAsCSFor.length" />
      </button>
    </div>

    <!-- TAB: MY CONTINUITY STEWARDS -->
    <div v-show="activeTab === 'mine'">
      <AegisEmptyState
        v-if="!stewards.length"
        icon="users"
        title="No Continuity Stewards Yet"
        description="Add a Continuity Steward to your plan. They'll carry out your continuity tasks if a critical incident occurs."
      >
        <button type="button" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;" @click="handleAddCS">
          <AegisIcon name="user-plus" :size="13" />
          Add Continuity Steward
        </button>
      </AegisEmptyState>

      <div v-for="s in stewards" :key="s.id" class="aegis-card" style="margin-bottom:12px;padding:20px 22px;">
        <!-- Card header -->
        <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:14px;">
          <div
            style="width:48px;height:48px;border-radius:10px;color:#fff;font-family:var(--font-sans);font-size:15px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;"
            :style="{ background: avatarColor(stewardName(s)) }"
          >{{ initials(stewardName(s)) }}</div>
          <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:3px;">
              <span style="font-family:var(--font-sans);font-size:14.5px;font-weight:700;color:var(--text);">{{ stewardName(s) }}</span>
              <AegisBadge :label="s.role" variant="gold" />
              <AegisBadge label="Active" variant="green" />
              <AegisBadge v-if="s.signed_at" label="Countersigned" variant="green" />
              <!-- Stripe Connect dot -->
              <span
                v-if="s.fee_cents > 0"
                :data-tooltip="s.stripe_connected ? 'Stripe Connect active — payments ready' : 'CS has not connected Stripe — payments blocked'"
                style="display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px;"
                :style="s.stripe_connected
                  ? 'color:var(--green-dark);background:var(--green-light);'
                  : 'color:var(--gold-dark);background:rgba(160,129,62,.1);border:1px solid var(--fade-gold);'"
              >
                <AegisIcon :name="s.stripe_connected ? 'check-circle' : 'alert-triangle'" :size="10" />
                {{ s.stripe_connected ? 'Payments Ready' : 'Connect Required' }}
              </span>
            </div>
            <div style="font-size:12px;color:var(--text-4);margin-bottom:6px;">{{ stewardOrg(s) }}</div>
            <div style="display:flex;flex-wrap:wrap;gap:12px;">
              <span v-if="s.user?.email" style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:var(--text-4);">
                <AegisIcon name="mail" :size="11" />{{ s.user.email }}
              </span>
              <span v-if="s.signed_at" style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:var(--text-4);">
                <AegisIcon name="signature" :size="11" />Signed {{ s.signed_at }}
              </span>
            </div>
          </div>
        </div>

        <!-- Fee block -->
        <div v-if="s.fee_cents > 0" style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:var(--surface-2);border:1px solid var(--border);border-radius:8px;margin-bottom:12px;">
          <AegisIcon name="dollar-sign" :size="14" style="color:var(--gold-dark);flex-shrink:0;" />
          <div style="flex:1;">
            <span style="font-size:13px;font-weight:700;color:var(--text);">{{ formatMoney(s.fee_cents) }}</span>
            <span style="font-size:12px;color:var(--text-4);margin-left:6px;">{{ s.payment_terms?.replace(/_/g, ' ') }}</span>
            <span v-if="s.auto_charge" style="font-size:10px;font-weight:700;color:var(--green-dark);background:var(--green-light);border-radius:99px;padding:1px 7px;margin-left:8px;">Auto-charge</span>
          </div>
          <button
            type="button"
            class="btn btn-outline"
            style="font-size:11px;padding:4px 10px;display:inline-flex;align-items:center;gap:5px;"
            :data-tooltip="'Amend fee — creates amendment doc for CS countersignature'"
            @click="openModal('amendFee', s)"
          >
            <AegisIcon name="edit" :size="11" />
            Amend
          </button>
        </div>

        <!-- Authorization chips -->
        <div v-if="authIncidentLabels(s).length" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px;">
          <span style="font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--text-4);align-self:center;">Authorized for:</span>
          <AegisBadge v-for="label in authIncidentLabels(s)" :key="label" :label="label" variant="default" />
        </div>

        <!-- Card actions -->
        <div style="display:flex;gap:6px;flex-wrap:wrap;padding-top:10px;border-top:1px solid var(--border);">
          <button type="button" class="btn btn-outline" style="font-size:11.5px;padding:5px 10px;display:inline-flex;align-items:center;gap:5px;" :data-tooltip="'View authorization summary'" @click="openAuthSummary(s)">
            <AegisIcon name="shield-check" :size="12" />
            Auth
          </button>
          <button v-if="s.fee_cents === 0" type="button" class="btn btn-outline" style="font-size:11.5px;padding:5px 10px;display:inline-flex;align-items:center;gap:5px;" :data-tooltip="'Set compensation fee'" @click="openModal('amendFee', s)">
            <AegisIcon name="dollar-sign" :size="12" />
            Set Fee
          </button>
          <button
            type="button"
            class="btn btn-outline"
            style="font-size:11.5px;padding:5px 10px;display:inline-flex;align-items:center;gap:5px;margin-left:auto;"
            :data-tooltip="s.has_outstanding_invoices ? 'Outstanding invoices exist — resolve in Finances first' : 'Remove this Continuity Steward'"
            @click="openRemoveModal(s)"
          >
            <AegisIcon name="trash" :size="12" style="color:var(--red);" />
            Remove
          </button>
        </div>
      </div>
    </div>

    <!-- TAB: PENDING INVITATIONS -->
    <div v-show="activeTab === 'pending'">
      <AegisEmptyState
        v-if="!pendingInvitations.length"
        icon="mail"
        title="No Pending Invitations"
        description="Invitations you send to Continuity Stewards will appear here until accepted."
      />

      <div v-for="inv in pendingInvitations" :key="inv.id" class="aegis-card" style="margin-bottom:10px;padding:16px 20px;">
        <div style="display:flex;align-items:flex-start;gap:12px;">
          <div
            style="width:42px;height:42px;border-radius:10px;color:#fff;font-family:var(--font-sans);font-size:14px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;"
            :style="{ background: avatarColor(inv.display_name ?? inv.email ?? '') }"
          >{{ initials(inv.display_name ?? inv.email ?? '') }}</div>
          <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:3px;">
              <span style="font-size:14px;font-weight:700;color:var(--text);">{{ inv.display_name ?? inv.email }}</span>
              <AegisBadge
                :label="inv.status === 'invited' ? 'Invited' : 'Pending Response'"
                :variant="inv.status === 'declined' ? 'red' : 'gold'"
              />
              <AegisBadge v-if="inv.role" :label="inv.role" variant="default" />
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:10px;">
              <span v-if="inv.email" style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:var(--text-4);">
                <AegisIcon name="mail" :size="11" />{{ inv.email }}
              </span>
              <span v-if="inv.invited_at" style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:var(--text-4);">
                <AegisIcon name="send" :size="11" />Sent {{ inv.invited_at }}
              </span>
              <span v-if="inv.expires_at" style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:var(--text-4);">
                <AegisIcon name="clock" :size="11" />Expires {{ inv.expires_at }}
              </span>
            </div>
          </div>
          <div style="display:flex;gap:6px;flex-shrink:0;">
            <button type="button" class="btn btn-outline" style="font-size:11.5px;padding:5px 10px;display:inline-flex;align-items:center;gap:5px;" :data-tooltip="'Resend invitation'" @click="openResend(inv)">
              <AegisIcon name="send" :size="12" />
              Resend
            </button>
            <button type="button" class="btn btn-outline" style="font-size:11.5px;padding:5px 10px;display:inline-flex;align-items:center;gap:5px;color:var(--red);" :data-tooltip="'Cancel invitation'" @click="openCancelInvite(inv)">
              <AegisIcon name="x" :size="12" />
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB: I'M CS FOR -->
    <div v-show="activeTab === 'for'">
      <div class="info-banner info-banner--blue" style="margin-bottom:14px;">
        <div style="display:flex;align-items:flex-start;gap:9px;">
          <AegisIcon name="shield-check" :size="15" style="flex-shrink:0;margin-top:2px;color:var(--blue-dark);" />
          <div>
            <div style="font-size:13.5px;font-weight:600;color:var(--blue-dark);margin-bottom:3px;">
              You are a Continuity Steward for <strong>{{ servingAsCSFor.length }}</strong> provider{{ servingAsCSFor.length !== 1 ? 's' : '' }}.
            </div>
            <div style="font-size:12px;color:var(--blue-dark);opacity:.85;line-height:1.5;">
              Run annual reviews and complete CS tasks from the full CS Portal.
            </div>
          </div>
        </div>
      </div>

      <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:10px;">Providers I'm Stewarding</div>
      <div v-for="p in servingAsCSFor" :key="p.id" class="aegis-card" style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 18px;margin-bottom:8px;cursor:pointer;" @click="router.visit(route('cs.dashboard'))">
        <div style="display:flex;align-items:center;gap:12px;min-width:0;">
          <div
            style="width:40px;height:40px;border-radius:8px;color:#fff;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;"
            :style="{ background: avatarColor(p.display_name) }"
          >{{ initials(p.display_name) }}</div>
          <div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:2px;">
              <span style="font-size:13.5px;font-weight:700;color:var(--text);">{{ p.display_name }}</span>
              <AegisBadge :label="p.role" variant="gold" />
              <AegisBadge label="Active" variant="green" />
            </div>
            <div style="font-size:12px;color:var(--text-4);">{{ p.organization }} · {{ p.location }}</div>
          </div>
        </div>
        <AegisIcon name="arrow-right" :size="14" style="color:var(--text-3);flex-shrink:0;" />
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ MODALS -->

    <!-- DESIGNATE CS MODAL (3-step wizard) -->
    <AegisModal
      v-model="modals.designate"
      title="Add Continuity Steward"
      size="xl"
      @close="closeModal('designate'); designateStep = 1; designateForm.reset(); v$designate.$reset()"
    >
      <!-- Step indicators -->
      <div style="display:flex;align-items:center;gap:4px;padding:10px 22px 0;flex-wrap:wrap;">
        <span
          v-for="(label, i) in ['Find Person', 'Role & Fee', 'Review & Send']"
          :key="i"
          style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:500;padding:4px 8px;border-radius:99px;"
          :style="designateStep === i + 1
            ? 'color:var(--text);font-weight:700;background:rgba(160,129,62,.12);'
            : designateStep > i + 1
              ? 'color:var(--green-dark);background:var(--green-light);'
              : 'color:var(--text-4);'"
        >
          <span style="width:18px;height:18px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;"
            :style="designateStep === i+1 ? 'background:var(--gold-dark);color:#fff;' : designateStep > i+1 ? 'background:var(--green-dark);color:#fff;' : 'background:var(--surface-3);'"
          >{{ designateStep > i + 1 ? '✓' : i + 1 }}</span>
          {{ label }}
        </span>
      </div>

      <!-- Step 1: Find Person -->
      <div v-show="designateStep === 1" style="padding:18px 0 0;">
        <p style="font-size:13px;color:var(--text-3);margin:0 0 14px;">Invite by email or search your network for an existing CS.</p>
        <div style="display:flex;flex-direction:column;gap:12px;">
          <div class="form-group">
            <label class="form-label">Email Address <span style="color:var(--red)">*</span></label>
            <input
              v-model="designateForm.email"
              type="email"
              class="form-input"
              :class="fieldClass(v$designate, 'email')"
              placeholder="cs@example.com"
              @blur="v$designate.email.$touch()"
            />
            <div v-if="fieldError(v$designate, 'email')" class="form-error">{{ fieldError(v$designate, 'email') }}</div>
          </div>
          <div class="form-group">
            <label class="form-label">Full Name <span style="color:var(--red)">*</span></label>
            <input
              v-model="designateForm.display_name"
              type="text"
              class="form-input"
              :class="fieldClass(v$designate, 'display_name')"
              placeholder="Dr. Jane Smith"
              @blur="v$designate.display_name.$touch()"
            />
            <div v-if="fieldError(v$designate, 'display_name')" class="form-error">{{ fieldError(v$designate, 'display_name') }}</div>
          </div>
        </div>
      </div>

      <!-- Step 2: Role & Fee -->
      <div v-show="designateStep === 2" style="padding:18px 0 0;">
        <div style="display:flex;flex-direction:column;gap:14px;">
          <div class="form-group">
            <label class="form-label">Role <span style="color:var(--red)">*</span></label>
            <div style="display:flex;flex-direction:column;gap:8px;">
              <label
                v-for="opt in [{ value: 'primary', label: 'Primary CS', desc: 'First responder — activated immediately on a critical incident.' }, { value: 'alternate', label: 'Alternate CS', desc: 'Steps in if Primary CS is unavailable.' }]"
                :key="opt.value"
                class="role-option-card"
                :class="{ selected: designateForm.role === opt.value }"
                style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border:1.5px solid var(--border);border-radius:8px;cursor:pointer;transition:border-color .15s,background .15s;"
                :style="designateForm.role === opt.value ? 'border-color:var(--gold-dark);background:rgba(160,129,62,.04);' : ''"
              >
                <input type="radio" :value="opt.value" v-model="designateForm.role" style="margin-top:3px;" />
                <div>
                  <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:2px;">{{ opt.label }}</div>
                  <div style="font-size:12px;color:var(--text-4);">{{ opt.desc }}</div>
                </div>
              </label>
            </div>
            <div v-if="fieldError(v$designate, 'role')" class="form-error">{{ fieldError(v$designate, 'role') }}</div>
          </div>

          <div class="form-group">
            <label class="form-label">Compensation Fee (optional)</label>
            <div style="position:relative;">
              <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:13px;">$</span>
              <input
                v-model="designateFeeInput"
                type="number"
                min="0"
                step="0.01"
                class="form-input"
                :class="fieldClass(v$designate, 'fee_cents')"
                style="padding-left:22px;"
                placeholder="0.00"
                @blur="v$designate.fee_cents.$touch()"
              />
            </div>
            <div v-if="fieldError(v$designate, 'fee_cents')" class="form-error">{{ fieldError(v$designate, 'fee_cents') }}</div>
          </div>

          <div class="form-group">
            <label class="form-label">Payment Terms <span style="color:var(--red)">*</span></label>
            <select v-model="designateForm.payment_terms" class="form-input" :class="fieldClass(v$designate, 'payment_terms')" @blur="v$designate.payment_terms.$touch()">
              <option value="per_incident">Per Incident</option>
              <option value="monthly">Monthly</option>
              <option value="flat_rate">Flat Rate</option>
            </select>
            <div v-if="fieldError(v$designate, 'payment_terms')" class="form-error">{{ fieldError(v$designate, 'payment_terms') }}</div>
          </div>

          <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;background:var(--surface-2);border:1px solid var(--border);border-radius:8px;">
            <div>
              <div style="font-size:13px;font-weight:500;color:var(--text);margin-bottom:2px;">Auto-charge on incident close</div>
              <div style="font-size:11.5px;color:var(--text-4);">Automatically charge your payment method when the incident closes.</div>
            </div>
            <label style="position:relative;display:inline-block;width:40px;height:22px;flex-shrink:0;">
              <input type="checkbox" v-model="designateForm.auto_charge" style="opacity:0;width:0;height:0;" />
              <span
                style="position:absolute;inset:0;border-radius:99px;cursor:pointer;transition:background .2s;"
                :style="designateForm.auto_charge ? 'background:var(--gold-dark)' : 'background:var(--border-dark)'"
              >
                <span style="position:absolute;width:18px;height:18px;left:2px;top:2px;background:#fff;border-radius:50%;transition:transform .2s;" :style="designateForm.auto_charge ? 'transform:translateX(18px)' : ''"></span>
              </span>
            </label>
          </div>

          <!-- Stripe Connect warning -->
          <div v-if="designateForm.fee_cents > 0" class="info-banner info-banner--gold">
            <div style="display:flex;align-items:flex-start;gap:8px;">
              <AegisIcon name="alert-triangle" :size="13" style="flex-shrink:0;margin-top:2px;color:var(--gold-dark);" />
              <span style="font-size:12px;color:var(--orange-dark);">The CS must complete Stripe Connect onboarding before payments can be processed.</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 3: Review & Send -->
      <div v-show="designateStep === 3" style="padding:18px 0 0;">
        <div style="border:1px solid var(--border);border-radius:8px;padding:16px 18px;background:var(--surface-2);margin-bottom:14px;">
          <div style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--text-4);margin-bottom:10px;">Invitation Summary</div>
          <div style="display:flex;flex-direction:column;gap:7px;font-size:13px;color:var(--text-2);">
            <div><strong>To:</strong> {{ designateForm.display_name || designateForm.email }}</div>
            <div><strong>Email:</strong> {{ designateForm.email }}</div>
            <div><strong>Role:</strong> {{ designateForm.role === 'primary' ? 'Primary CS' : 'Alternate CS' }}</div>
            <div v-if="designateForm.fee_cents > 0"><strong>Fee:</strong> {{ formatMoney(designateForm.fee_cents) }} ({{ designateForm.payment_terms?.replace(/_/g, ' ') }})</div>
            <div v-if="designateForm.auto_charge"><strong>Auto-charge:</strong> Yes</div>
          </div>
        </div>
        <div class="info-banner info-banner--blue">
          <div style="display:flex;align-items:flex-start;gap:8px;">
            <AegisIcon name="alert-circle" :size="13" style="flex-shrink:0;margin-top:2px;color:var(--blue-dark);" />
            <span style="font-size:12px;color:var(--blue-dark);">An engagement agreement will be created automatically when the CS accepts. If a fee is set, they must also complete Stripe Connect onboarding.</span>
          </div>
        </div>
      </div>

      <template #footer>
        <button type="button" class="btn btn-outline" @click="designateStep > 1 ? designateStep-- : closeModal('designate')">
          {{ designateStep > 1 ? 'Back' : 'Cancel' }}
        </button>
        <button
          v-if="designateStep < 3"
          type="button"
          class="btn btn-primary"
          @click="designateStep++"
          style="display:inline-flex;align-items:center;gap:6px;"
        >
          Continue
          <AegisIcon name="arrow-right" :size="13" />
        </button>
        <button
          v-else
          type="button"
          class="btn btn-primary"
          :disabled="busyDesignate || designateForm.processing"
          @click="submitDesignate"
          style="display:inline-flex;align-items:center;gap:6px;"
        >
          <AegisIcon v-if="busyDesignate" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="send" :size="13" />
          {{ busyDesignate ? 'Sending…' : 'Send Invitation' }}
        </button>
      </template>
    </AegisModal>

    <!-- AMEND FEE MODAL -->
    <AegisModal
      v-model="modals.amendFee"
      title="Amend CS Compensation"
      size="md"
      @close="closeModal('amendFee'); amendFeeForm.reset(); v$amendFee.$reset()"
    >
      <div style="display:flex;flex-direction:column;gap:14px;padding-top:4px;">
        <div class="info-banner info-banner--gold">
          <div style="display:flex;align-items:flex-start;gap:8px;">
            <AegisIcon name="alert-triangle" :size="13" style="flex-shrink:0;margin-top:2px;color:var(--gold-dark);" />
            <span style="font-size:12px;color:var(--orange-dark);">A fee change creates a signed amendment. The new fee does not take effect until the CS countersigns the amendment document.</span>
          </div>
        </div>

        <div v-if="activeSteward" style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--surface-2);border:1px solid var(--border);border-radius:8px;">
          <div style="width:36px;height:36px;border-radius:8px;color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;"
            :style="{ background: avatarColor(stewardName(activeSteward)) }">
            {{ initials(stewardName(activeSteward)) }}
          </div>
          <div>
            <div style="font-size:13.5px;font-weight:700;color:var(--text);">{{ stewardName(activeSteward) }}</div>
            <div style="font-size:12px;color:var(--text-4);">Current fee: {{ formatMoney(activeSteward.fee_cents) }}</div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">New Fee Amount <span style="color:var(--red)">*</span></label>
          <div style="position:relative;">
            <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:13px;">$</span>
            <input
              v-model="amendFeeInput"
              type="number"
              min="0"
              step="0.01"
              class="form-input"
              :class="fieldClass(v$amendFee, 'fee_cents')"
              style="padding-left:22px;"
              @blur="v$amendFee.fee_cents.$touch()"
            />
          </div>
          <div v-if="fieldError(v$amendFee, 'fee_cents')" class="form-error">{{ fieldError(v$amendFee, 'fee_cents') }}</div>
        </div>

        <div class="form-group">
          <label class="form-label">Payment Terms <span style="color:var(--red)">*</span></label>
          <select v-model="amendFeeForm.payment_terms" class="form-input" :class="fieldClass(v$amendFee, 'payment_terms')" @blur="v$amendFee.payment_terms.$touch()">
            <option value="per_incident">Per Incident</option>
            <option value="monthly">Monthly</option>
            <option value="flat_rate">Flat Rate</option>
          </select>
          <div v-if="fieldError(v$amendFee, 'payment_terms')" class="form-error">{{ fieldError(v$amendFee, 'payment_terms') }}</div>
        </div>
      </div>

      <template #footer>
        <button type="button" class="btn btn-outline" @click="closeModal('amendFee')">Cancel</button>
        <button
          type="button"
          class="btn btn-primary"
          :disabled="busyAmendFee"
          @click="submitAmendFee"
          style="display:inline-flex;align-items:center;gap:6px;"
        >
          <AegisIcon v-if="busyAmendFee" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="file-pen" :size="13" />
          {{ busyAmendFee ? 'Creating…' : 'Create Amendment' }}
        </button>
      </template>
    </AegisModal>

    <!-- AUTHORIZATION SUMMARY MODAL -->
    <AegisModal
      v-model="modals.authSummary"
      title="Authorization Summary"
      size="md"
      @close="closeModal('authSummary')"
    >
      <div v-if="activeSteward" style="padding-top:4px;">
        <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--surface-2);border:1px solid var(--border);border-radius:8px;margin-bottom:14px;">
          <div style="width:36px;height:36px;border-radius:8px;color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;"
            :style="{ background: avatarColor(stewardName(activeSteward)) }">
            {{ initials(stewardName(activeSteward)) }}
          </div>
          <div>
            <div style="font-size:13.5px;font-weight:700;color:var(--text);">{{ stewardName(activeSteward) }}</div>
            <div style="font-size:12px;color:var(--text-4);">Role: {{ activeSteward.role }}</div>
          </div>
        </div>
        <div style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--text-4);margin-bottom:10px;">Authorized Incident Types</div>
        <div v-if="authIncidentLabels(activeSteward).length" style="display:flex;flex-wrap:wrap;gap:6px;">
          <AegisBadge v-for="label in authIncidentLabels(activeSteward)" :key="label" :label="label" variant="default" />
        </div>
        <div v-else style="font-size:13px;color:var(--text-4);padding:14px 0;">
          No incident types configured yet. Set up incident authorizations in your Continuity Plan.
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="closeModal('authSummary')">Close</button>
        <a :href="route('provider.plan.index')" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;">
          <AegisIcon name="edit" :size="13" />
          Edit in Plan
        </a>
      </template>
    </AegisModal>

    <!-- REMOVE MODAL -->
    <AegisModal
      v-model="modals.remove"
      title="Remove Continuity Steward"
      size="md"
      @close="closeModal('remove'); removeForm.reset(); v$remove.$reset()"
    >
      <div style="display:flex;flex-direction:column;gap:12px;padding-top:4px;">
        <div style="display:flex;align-items:flex-start;gap:8px;padding:10px 13px;background:var(--red-light);border:1px solid rgba(160,45,34,.3);border-radius:8px;">
          <AegisIcon name="alert-triangle" :size="13" style="flex-shrink:0;margin-top:2px;color:var(--red);" />
          <span style="font-size:12.5px;color:var(--red);line-height:1.5;">Removing this Continuity Steward will revoke their access to your plan and vault. This action cannot be undone.</span>
        </div>
        <div class="form-group">
          <label class="form-label">Reason <span style="color:var(--red)">*</span></label>
          <textarea
            v-model="removeForm.reason"
            class="form-input"
            :class="fieldClass(v$remove, 'reason')"
            rows="3"
            placeholder="Briefly explain why this steward is being removed…"
            @blur="v$remove.reason.$touch()"
          />
          <div v-if="fieldError(v$remove, 'reason')" class="form-error">{{ fieldError(v$remove, 'reason') }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Type <strong>REMOVE</strong> to confirm</label>
          <input
            v-model="removeForm.confirm"
            type="text"
            class="form-input"
            :class="fieldClass(v$remove, 'confirm')"
            placeholder="REMOVE"
            @blur="v$remove.confirm.$touch()"
          />
          <div v-if="fieldError(v$remove, 'confirm')" class="form-error">{{ fieldError(v$remove, 'confirm') }}</div>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="closeModal('remove')">Cancel</button>
        <button
          type="button"
          class="btn btn-danger"
          :disabled="busyRemove || removeForm.confirm !== 'REMOVE'"
          @click="submitRemove"
          style="display:inline-flex;align-items:center;gap:6px;"
        >
          <AegisIcon v-if="busyRemove" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="trash" :size="13" />
          {{ busyRemove ? 'Removing…' : 'Remove Steward' }}
        </button>
      </template>
    </AegisModal>

    <!-- RESEND INVITE MODAL -->
    <AegisModal
      v-model="modals.resend"
      title="Resend Invitation"
      size="sm"
      @close="closeModal('resend')"
    >
      <p style="font-size:13px;color:var(--text-2);margin:0;">Resend the invitation to <strong>{{ activePending?.display_name ?? activePending?.email }}</strong>. The expiry will be reset to 30 days.</p>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="closeModal('resend')">Cancel</button>
        <button
          type="button"
          class="btn btn-primary"
          :disabled="busyResend"
          @click="submitResend"
          style="display:inline-flex;align-items:center;gap:6px;"
        >
          <AegisIcon v-if="busyResend" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="send" :size="13" />
          {{ busyResend ? 'Sending…' : 'Resend' }}
        </button>
      </template>
    </AegisModal>

    <!-- CANCEL INVITE MODAL -->
    <AegisModal
      v-model="modals.cancelInvite"
      title="Cancel Invitation"
      size="sm"
      @close="closeModal('cancelInvite')"
    >
      <p style="font-size:13px;color:var(--text-2);margin:0;">Cancel the invitation sent to <strong>{{ activePending?.display_name ?? activePending?.email }}</strong>? This cannot be undone.</p>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="closeModal('cancelInvite')">Keep</button>
        <button
          type="button"
          class="btn btn-danger"
          :disabled="busyCancel"
          @click="submitCancelInvite"
          style="display:inline-flex;align-items:center;gap:6px;"
        >
          <AegisIcon v-if="busyCancel" name="refresh-cw" :size="13" class="btn-spin" />
          <AegisIcon v-else name="x" :size="13" />
          {{ busyCancel ? 'Cancelling…' : 'Cancel Invitation' }}
        </button>
      </template>
    </AegisModal>

    <!-- UPGRADE MODAL -->
    <AegisModal
      v-model="modals.upgrade"
      title="Upgrade to Add More CS"
      size="md"
      @close="closeModal('upgrade')"
    >
      <div style="display:flex;flex-direction:column;gap:12px;padding-top:4px;">
        <div style="display:flex;align-items:flex-start;gap:9px;padding:10px 13px;background:rgba(160,129,62,.06);border:1px solid var(--fade-gold);border-radius:8px;">
          <AegisIcon name="award" :size="14" style="flex-shrink:0;margin-top:2px;color:var(--gold-dark);" />
          <span style="font-size:12.5px;color:var(--orange-dark);line-height:1.5;">Your <strong>Continuity Access</strong> plan supports up to <strong>{{ csMax }} Continuity Steward{{ csMax !== 1 ? 's' : '' }}</strong>. Upgrade to <strong>Continuity Practice</strong> to add more.</span>
        </div>
        <div style="padding:14px;background:var(--surface-2);border:1px solid var(--border);border-radius:8px;">
          <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:6px;">Continuity Practice — $49/mo</div>
          <div style="font-size:12px;color:var(--text-3);">Up to 2 Continuity Stewards + 4 Support Stewards, full vault access, and more.</div>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-outline" @click="closeModal('upgrade')">Not Now</button>
        <a :href="route('provider.settings.billing.portal')" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;">
          <AegisIcon name="arrow-right" :size="13" />
          Upgrade Plan
        </a>
      </template>
    </AegisModal>

  </AppLayout>
</template>
