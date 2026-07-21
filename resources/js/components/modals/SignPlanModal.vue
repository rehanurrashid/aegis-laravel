<!--
  modals/SignPlanModal.vue — Option B single confirmation sign ceremony.
  Usage: <SignPlanModal v-model="showSignModal" :plan="plan" ... />
-->
<template>
  <AegisModal :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" size="lg" title="Sign Your Continuity Plan" @close="closeModal">
    <!-- Plan readiness summary -->
    <div class="m-section" style="margin-bottom:16px">
      <div class="m-section-title">Plan Readiness</div>
      <div class="list-group" style="margin-bottom:0">
        <div v-for="sec in sectionSummary" :key="sec.key" class="list-group-item" style="gap:12px">
          <span :style="`width:18px;height:18px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;background:${sec.complete ? 'var(--green-dark)' : 'var(--surface-3)'};color:${sec.complete ? '#fff' : 'var(--text-4)'}`">
            <AegisIcon :name="sec.complete ? 'check' : 'minus'" :size="10" />
          </span>
          <span style="flex:1;font-size:13px;font-weight:500;color:var(--text-2)">{{ sec.title }}</span>
          <span v-if="!sec.complete && sec.blocks_signing" style="font-size:11px;font-weight:600;color:var(--red-dark)">Required</span>
          <span v-else-if="!sec.complete" style="font-size:11px;font-weight:600;color:var(--text-4)">Recommended</span>
        </div>
      </div>
    </div>

    <!-- Payment authorization (CS with fee_cents > 0) -->
    <div v-if="paidStewards.length" class="m-section" style="margin-bottom:16px">
      <div class="m-section-title">Payment Authorization</div>
      <div class="alert alert-warning" style="margin-bottom:10px">
        <AegisIcon name="alert-triangle" :size="16" />
        <div>By signing, you authorize payment to each Continuity Steward listed below when a critical incident is closed. Charges are drawn from your default payment method on file.</div>
      </div>
      <div class="list-group" style="margin-bottom:0">
        <div v-for="cs in paidStewards" :key="cs.steward_id" class="list-group-item" style="gap:12px">
          <span class="avatar avatar-sm avatar-gold">{{ cs.avatar_initials }}</span>
          <span style="flex:1;font-size:13px;font-weight:600;color:var(--text)">{{ cs.display_name }}</span>
          <span style="font-size:13px;font-weight:700;font-family:var(--font-serif);color:var(--gold-dark)">{{ formatMoney(cs.fee_cents) }}</span>
          <span style="font-size:11px;color:var(--text-3);font-weight:500">{{ paymentTermLabel(cs.payment_terms) }}</span>
        </div>
      </div>
    </div>

    <!-- Signature block -->
    <div class="m-section">
      <div class="m-section-title">Your Signature</div>
      <div class="form-group" style="margin-top:14px;margin-bottom:10px">
        <label class="form-label">Legal Name</label>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 14px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;font-weight:500;color:var(--text-3)">
          <span>{{ auth?.user?.display_name ?? '—' }}</span>
          <small style="display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:var(--text-4)">
            <AegisIcon name="lock" :size="11" /> Verified
          </small>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Title <span class="form-required">*</span></label>
          <input v-model="form.title" class="form-input" :class="{ 'is-error': fieldError('title') }"
            type="text" placeholder="Your professional title" @blur="v$.title.$touch()" />
          <div v-if="fieldError('title')" class="form-error">{{ fieldError('title') }}</div>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Date</label>
          <input class="form-input" type="date" :value="today" readonly style="background:var(--surface-2);color:var(--text-3)" />
        </div>
      </div>
    </div>

    <!-- Consent toggle -->
    <div class="setting-row" style="margin-top:14px">
      <div class="setting-info">
        <div class="setting-label">I confirm this plan is accurate and complete</div>
        <div class="setting-desc">By signing, I authorize my designated Stewards to act as described during any verified critical incident{{ paidStewards.length ? ', and I authorize the compensation payments listed above' : '' }}. This plan supersedes any prior plan I have signed on Aegis.</div>
      </div>
      <AegisToggle v-model="form.confirmed" />
    </div>
    <div v-if="fieldError('confirmed')" class="form-error" style="margin-top:4px">{{ fieldError('confirmed') }}</div>

    <template #footer>
      <button type="button" class="btn btn-outline" @click="closeModal">Cancel</button>
      <button type="button" class="btn btn-primary" 
        :disabled="submitting || !form.confirmed || !canSign"
        :data-tooltip="!canSign ? 'Complete all required sections first' : undefined"
        @click="submit">
        <span v-if="submitting" class="spinner spinner-sm" />
        <AegisIcon v-else name="edit" :size="14" />
        {{ submitting ? 'Signing plan…' : 'Sign & Activate Plan' }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useVuelidate } from '@vuelidate/core'
import { required, helpers } from '@vuelidate/validators'
import AegisToggle from '@/components/ui/AegisToggle.vue'

const props = defineProps({
  modelValue:    { type: Boolean, required: true },
  plan:          { type: Object,  default: null },
  stewards:      { type: Array,   default: () => [] },
  canSign:       { type: Boolean, default: false },
  sectionSummary:{ type: Array,   default: () => [] },
  auth:          { type: Object,  default: null },
})

const emit = defineEmits(['update:modelValue'])

function closeModal() {
  form.reset()
  v$.value.$reset()
  emit('update:modelValue', false)
}

const today = new Date().toISOString().split('T')[0]

const paidStewards = computed(() =>
  props.stewards.filter(s => s.steward_type === 'continuity_steward' && (s.fee_cents ?? 0) > 0)
)

const form = useForm({ title: '', confirmed: false })

const rules = computed(() => ({
  title:     { required: helpers.withMessage('Professional title is required.', required) },
  confirmed: { accepted: helpers.withMessage('You must confirm before signing.', v => v === true) },
}))
const v$ = useVuelidate(rules, form, { $scope: false })
const submitting = ref(false)

function fieldError(field) {
  if (v$.value[field]?.$error) return v$.value[field].$errors[0]?.$message
  if (form.errors[field]) return form.errors[field]
  return null
}

function formatMoney(cents) {
  return '$' + ((cents ?? 0) / 100).toLocaleString('en-US', { minimumFractionDigits: 2 })
}
function paymentTermLabel(term) {
  return { on_close: 'On incident close', net_30: 'Net 30', net_60: 'Net 60' }[term] ?? term ?? '—'
}

async function submit() {
  const valid = await v$.value.$validate()
  v$.value.$touch()
  if (!valid) return
  submitting.value = true
  form.post(route('provider.plan.sign'), {
    onSuccess: () => { submitting.value = false; emit('update:modelValue', false) },
    onError:   () => { submitting.value = false },
  })
}
</script>

<style scoped>
.m-section-title { display:flex;align-items:center;justify-content:space-between;font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--text-3);padding-bottom:6px;border-bottom:1px solid var(--border); }
</style>
