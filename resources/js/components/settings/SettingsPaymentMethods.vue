<!--
  SettingsPaymentMethods.vue — shared across Provider, CS (Business), BP portals.

  Props:
    paymentMethods      Array   — list of card objects from SettingsController::fetchPaymentMethods()
    setupIntentRoute    String  — e.g. 'provider.settings.payment.setup-intent'
    storeRoute          String  — e.g. 'provider.settings.payment.store'
    defaultRoute        String  — e.g. 'provider.settings.payment.default'
    removeRoute         String  — e.g. 'provider.settings.payment.remove'
    infoText            String  — portal-specific description in the info alert

  Emits: (none — all actions POST via Inertia and reload page props)
-->
<template>
  <div class="st-card">
    <div class="st-card-head">
      <div class="st-card-head-l">
        <span class="st-card-ico"><AegisIcon name="credit-card" :size="17" /></span>
        <div>
          <div class="st-card-title">Saved Payment Methods</div>
          <div class="st-card-sub">Cards used to fund your Aegis charges</div>
        </div>
      </div>
      <button type="button" class="btn btn-dark" @click="showAddCard = true">
        <AegisIcon name="plus" :size="12" /> Add Method
      </button>
    </div>

    <div class="st-card-body">
      <div class="alert alert-info" style="margin-bottom:16px;">
        <div class="alert-icon"><AegisIcon name="shield" :size="18" /></div>
        <div class="alert-content">
          <div class="alert-title">One Card, All Payments</div>
          <div>{{ infoText }}</div>
        </div>
      </div>

      <AegisEmptyState
        v-if="!paymentMethods.length"
        icon="credit-card"
        title="No payment methods"
        description="Add a card to fund your Aegis subscription."
        style="padding:24px 0;"
      />
      <div v-else>
        <div
          v-for="pm in paymentMethods"
          :key="pm.id"
          class="spm-card"
          :class="{ 'spm-card--default': pm.is_default }"
        >
          <div class="spm-logo">
            <AegisIcon :name="pm.method_type === 'bank' ? 'building' : 'credit-card'" :size="20" />
          </div>
          <div class="spm-info">
            <div class="spm-name">
              {{ (pm.brand || 'card').toUpperCase() }} ···· {{ pm.last4 }}
              <AegisBadge v-if="pm.is_default" label="Default · funds all payments" variant="gold" style="margin-left:6px;" />
            </div>
            <div class="spm-meta">
              {{ pm.method_type === 'bank' ? 'ACH / Bank Transfer' : (pm.exp_month ? 'Expires ' + pm.exp_month + '/' + pm.exp_year : 'On file') }}
            </div>
          </div>
          <div class="spm-btns">
            <template v-if="!pm.is_default">
              <button type="button" class="btn-icon btn-icon-sm" data-tooltip="Set as default" @click="setDefault(pm)">
                <AegisIcon name="check" :size="12" />
              </button>
              <button type="button" class="btn-icon btn-icon-sm btn-icon-danger" data-tooltip="Remove" @click="openRemove(pm)">
                <AegisIcon name="trash" :size="12" />
              </button>
            </template>
            <AegisIcon v-else name="shield-check" :size="16" class="spm-default-icon" data-tooltip="Default payment method" />
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Set Default Confirm Modal -->
  <AegisModal v-model="showDefault" title="Set Default Payment Method" size="sm">
    <p style="font-size:13px;color:var(--text-2);">
      Set <strong>{{ defaultPm ? (defaultPm.brand || 'card').toUpperCase() + ' ···· ' + defaultPm.last4 : '' }}</strong> as your default payment method? It will fund all Aegis charges going forward.
    </p>
    <template #footer>
      <button type="button" class="btn btn-outline" :disabled="settingDefault" @click="showDefault = false">Cancel</button>
      <button type="button" class="btn btn-primary" :disabled="settingDefault" @click="doSetDefault">
        <span v-if="settingDefault" class="spinner spinner-sm" />
        <AegisIcon v-else name="check" :size="13" />
        {{ settingDefault ? 'Saving…' : 'Confirm' }}
      </button>
    </template>
  </AegisModal>

  <!-- Add Card Modal -->
  <AddCardModal
    v-model="showAddCard"
    :setup-intent-route="setupIntentRoute"
    :store-route="storeRoute"
  />

  <!-- Remove Confirm Modal -->
  <AegisModal v-model="showRemove" title="Remove Payment Method" size="sm">
    <p style="font-size:13px;color:var(--text-2);">
      Remove this payment method? If it's the only card on file, subscription renewal will fail until a new card is added.
    </p>
    <template #footer>
      <button type="button" class="btn btn-outline" :disabled="removing" @click="showRemove = false">Cancel</button>
      <button type="button" class="btn btn-danger" :disabled="removing" @click="doRemove">
        <span v-if="removing" class="spinner spinner-sm" />
        <AegisIcon v-else name="trash" :size="13" />
        {{ removing ? 'Removing…' : 'Remove' }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast }   from '@/composables/useToast'
import AddCardModal   from '@/components/modals/AddCardModal.vue'

const props = defineProps({
  paymentMethods:   { type: Array,  default: () => [] },
  setupIntentRoute: { type: String, required: true },
  storeRoute:       { type: String, required: true },
  defaultRoute:     { type: String, required: true },
  removeRoute:      { type: String, required: true },
  infoText:         { type: String, default: 'Your active payment method funds your Aegis subscription. Aegis never sees or stores your full card number.' },
})

const toast          = useToast()
const showAddCard    = ref(false)
const showRemove     = ref(false)
const removing       = ref(false)
const activePm       = ref(null)
const showDefault    = ref(false)
const defaultPm      = ref(null)
const settingDefault = ref(false)

function setDefault(pm) {
  defaultPm.value   = pm
  showDefault.value = true
}

function doSetDefault() {
  if (!defaultPm.value || settingDefault.value) return
  settingDefault.value = true
  router.post(route(props.defaultRoute), { payment_method_id: defaultPm.value.id }, {
    preserveScroll: true,
    onSuccess: () => { showDefault.value = false; toast.success('Default payment method updated.') },
    onError:   () => toast.error('Could not update default payment method.'),
    onFinish:  () => { settingDefault.value = false },
  })
}

function openRemove(pm) {
  activePm.value  = pm
  showRemove.value = true
}

function doRemove() {
  if (!activePm.value || removing.value) return
  removing.value = true
  router.delete(route(props.removeRoute), {
    data: { payment_method_id: activePm.value.id },
    preserveScroll: true,
    onSuccess: () => { showRemove.value = false; toast.info('Payment method removed.') },
    onError:   () => toast.error('Could not remove payment method.'),
    onFinish:  () => { removing.value = false },
  })
}
</script>

<style scoped>
.spm-card         { display: flex; align-items: center; gap: 14px; padding: 14px 18px; border: 1px solid var(--border); border-radius: var(--radius-lg); margin-bottom: 10px; background: var(--surface); box-shadow: var(--shadow-sm); }
.spm-card--default { border-color: var(--gold-dark); background: var(--badge-bg-gold); }
.spm-logo         { width: 48px; height: 32px; border-radius: var(--radius-sm); background: var(--surface-2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.spm-info         { flex: 1; min-width: 0; }
.spm-name         { font-size: 13px; font-weight: 700; color: var(--text); }
.spm-meta         { font-size: 12px; color: var(--text-3); margin-top: 2px; }
.spm-btns         { display: flex; gap: 6px; align-items: center; flex-shrink: 0; }
.spm-default-icon { color: var(--gold-dark); }
.spm-spin         { animation: spm-spin-kf 0.7s linear infinite; display: inline-block; }
@keyframes spm-spin-kf { to { transform: rotate(360deg); } }

/* ── st-card design (scoped copy from Settings.vue — not inherited from parent) ── */
.st-card       { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); }
.st-card-head  { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 18px 20px; border-bottom: 1px solid var(--border); }
.st-card-head-l { display: flex; align-items: center; gap: 10px; min-width: 0; }
.st-card-ico   { width: 34px; height: 34px; border-radius: var(--radius-sm); background: var(--icon-bg-gold); color: var(--gold-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.st-card-title { font-size: 15px; font-weight: 700; color: var(--text); font-family: var(--font-serif); }
.st-card-sub   { font-size: 12px; color: var(--text-3); margin-top: 1px; }
.st-card-body  { padding: 18px 20px; }
</style>