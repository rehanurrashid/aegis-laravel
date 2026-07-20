<!--
  EndStewardRetainerModal.vue — shared remove/end-retainer modal.
  Used by both ContinuityStewards and SupportStewards pages.

  Props:
    modelValue  — v-model open/close
    steward     — the PlanSteward record (active)
    kind        — 'cs' | 'ss'  (controls title, warning copy, reason list, routes)

  Emits:
    update:modelValue
    success   — after successful action, parent reloads
-->
<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  steward:    { type: Object,  default: null },
  kind:       { type: String,  default: 'cs', validator: v => ['cs', 'ss'].includes(v) },
})

const emit = defineEmits(['update:modelValue', 'success'])

const toast   = useToast()
const busy    = ref(false)
const form    = useForm({ action: '', reason: '', details: '' })

function close() {
  emit('update:modelValue', false)
  form.reset()
}

// ── Computed labels based on kind ────────────────────────────────────────────
const title = computed(() =>
  props.kind === 'cs' ? 'End Retainer Agreement' : 'Remove Support Steward'
)

const stewardName = computed(() => {
  const s = props.steward
  return s?.steward?.display_name ?? s?.display_name ?? s?.email ?? '—'
})

const warningNote = computed(() =>
  props.kind === 'cs'
    ? 'Your Continuity Plan requires at least one active CS. Consider designating a replacement before ending this retainer.'
    : 'Removing a Support Steward revokes all access. The record is preserved for audit purposes.'
)

const pauseLabel = computed(() =>
  props.kind === 'cs' ? 'Pause Temporarily' : 'Suspend Temporarily'
)
const pauseDesc = computed(() =>
  props.kind === 'cs'
    ? 'Access revoked but retainer preserved. You can reinstate anytime from the Suspended tab.'
    : 'Access revoked — reinstate anytime from the Suspended tab.'
)
const terminateLabel = computed(() =>
  props.kind === 'cs' ? 'Terminate Permanently' : 'Remove Permanently'
)
const terminateDesc = computed(() =>
  props.kind === 'cs'
    ? 'Retainer archived. Historical invoices preserved. Cannot be reinstated — you would need to sign a new retainer.'
    : 'Record archived. Cannot be reinstated without a new invitation.'
)

const reasons = computed(() =>
  props.kind === 'cs'
    ? [
        { value: 'steward_resigned',  label: 'CS resigned' },
        { value: 'mutual',            label: 'Mutual termination' },
        { value: 'practice_closing',  label: 'Practice closing' },
        { value: 'temporary_leave',   label: 'Temporary leave' },
        { value: 'replacing',         label: 'Replacing with another CS' },
        { value: 'other',             label: 'Other' },
      ]
    : [
        { value: 'temporary_leave',          label: 'Temporary leave' },
        { value: 'role_no_longer_needed',     label: 'Role no longer needed' },
        { value: 'replacing',                 label: 'Replacing with a different SS' },
        { value: 'practice_restructuring',    label: 'Practice restructuring' },
        { value: 'ss_requested',              label: 'SS requested removal' },
        { value: 'other',                     label: 'Other' },
      ]
)

const ctaLabel = computed(() => {
  if (busy.value) return 'Processing…'
  if (form.action === 'suspend') return props.kind === 'cs' ? 'Pause Retainer' : 'Suspend Access'
  if (form.action === 'terminate') return props.kind === 'cs' ? 'Terminate Retainer' : 'Remove Support Steward'
  return 'Confirm'
})

const disabled = computed(() => !form.action || !form.reason || busy.value)

// ── Submit ────────────────────────────────────────────────────────────────────
function submit() {
  if (!props.steward || disabled.value) return
  busy.value = true

  const id = props.steward.id

  if (form.action === 'suspend') {
    const suspendRoute = props.kind === 'cs'
      ? route('provider.stewards.suspend',   { steward: id })
      : route('provider.ss.suspend',         { steward: id })

    form.post(suspendRoute, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success(props.kind === 'cs'
          ? 'Retainer paused. Reinstate anytime from the Suspended tab.'
          : 'Support Steward access suspended.')
        close()
        emit('success')
      },
      onError:  () => toast.error('Could not suspend access.'),
      onFinish: () => { busy.value = false },
    })
  } else {
    // terminate / remove
    const terminateRoute = props.kind === 'cs'
      ? route('provider.stewards.terminate', { steward: id })
      : route('provider.ss.archive',         { steward: id })

    form.post(terminateRoute, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success(props.kind === 'cs'
          ? 'Retainer terminated.'
          : 'Support Steward removed.')
        close()
        emit('success')
      },
      onError:  () => toast.error('Could not complete this action.'),
      onFinish: () => { busy.value = false },
    })
  }
}
</script>

<template>
  <AegisModal
    :model-value="modelValue"
    :title="title"
    size="md"
    @update:model-value="v => !v && close()"
    @close="close"
  >
    <template v-if="steward">

      <!-- Warning alert -->
      <div class="alert alert-warning" style="margin-bottom:16px;">
        <div class="alert-icon"><AegisIcon name="alert-triangle" :size="16" /></div>
        <div class="alert-content">
          <div class="alert-title">Ending {{ kind === 'cs' ? 'retainer' : 'designation' }} with {{ stewardName }}</div>
          <div style="font-size:12px;">{{ warningNote }}</div>
        </div>
      </div>

      <!-- Action picker -->
      <div class="form-group">
        <label class="form-label">How do you want to proceed?</label>
        <div style="display:flex;flex-direction:column;gap:8px;margin-top:8px;">

          <!-- Pause / Suspend -->
          <label
            style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border:1px solid var(--border);border-radius:var(--radius);cursor:pointer;background:var(--surface);"
            :style="form.action === 'suspend' ? 'border-color:var(--gold-dark);background:var(--badge-bg-gold);' : ''"
          >
            <input type="radio" v-model="form.action" value="suspend" style="margin-top:2px;accent-color:var(--gold-dark);" />
            <div>
              <div style="font-weight:600;font-size:13px;">{{ pauseLabel }}</div>
              <div style="font-size:11px;color:var(--text-3);margin-top:2px;">{{ pauseDesc }}</div>
            </div>
          </label>

          <!-- Terminate / Remove -->
          <label
            style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border:1px solid var(--border);border-radius:var(--radius);cursor:pointer;background:var(--surface);"
            :style="form.action === 'terminate' ? 'border-color:var(--red-dark);background:var(--red-light);' : ''"
          >
            <input type="radio" v-model="form.action" value="terminate" style="margin-top:2px;accent-color:var(--red-dark);" />
            <div>
              <div style="font-weight:600;font-size:13px;">{{ terminateLabel }}</div>
              <div style="font-size:11px;color:var(--text-3);margin-top:2px;">{{ terminateDesc }}</div>
            </div>
          </label>
        </div>
      </div>

      <!-- Reason dropdown -->
      <div class="form-group" style="margin-top:14px;">
        <label class="form-label">Reason <span style="color:var(--red-dark);">*</span></label>
        <select v-model="form.reason" class="form-control form-select" :class="{ 'is-error': !form.reason && form.action }">
          <option value="">Select a reason…</option>
          <option v-for="r in reasons" :key="r.value" :value="r.value">{{ r.label }}</option>
        </select>
      </div>

      <!-- Details (always shown, optional) -->
      <div class="form-group">
        <label class="form-label">Details <span style="font-size:11px;color:var(--text-4);">(optional)</span></label>
        <textarea v-model="form.details" class="form-control" rows="3" placeholder="Provide additional context…"></textarea>
      </div>

    </template>

    <template #footer>
      <button type="button" class="btn btn-outline" @click="close">Cancel</button>
      <button
        type="button"
        class="btn btn-danger"
        :disabled="disabled"
        style="display:inline-flex;align-items:center;gap:6px;"
        @click="submit"
      >
        <AegisIcon v-if="busy" name="refresh-cw" :size="13" class="btn-spin" />
        <AegisIcon v-else name="x" :size="13" />
        {{ ctaLabel }}
      </button>
    </template>
  </AegisModal>
</template>
