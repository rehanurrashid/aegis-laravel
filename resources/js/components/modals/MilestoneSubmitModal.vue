<!--
  MilestoneSubmitModal.vue — BP submits milestone work for provider review.

  Handles both first submission and resubmit (after revision_requested).
  Required: work summary notes (min 10 chars).
  Optional: hours logged (if hourly contract), revision context when resubmitting.

  Posts to: bp.milestones.submit

  Props:
    milestone  Object | null  — from milestones prop (includes revision_notes, revision_count)
-->
<template>
  <AegisModal
    :model-value="!!milestone"
    :title="isResubmit ? 'Resubmit milestone' : 'Submit milestone work'"
    size="lg"
    @update:model-value="onClose"
  >
    <div v-if="milestone" class="ms-submit-modal">

      <!-- Milestone summary -->
      <div class="ms-submit-summary">
        <div class="ms-submit-summary-title">{{ milestone.title }}</div>
        <div class="ms-submit-summary-meta">
          <span>{{ pricing.formatCents(milestone.amount_cents) }} in escrow</span>
          <span v-if="milestone.due_at">· Due {{ milestone.due_at }}</span>
          <AegisBadge
            v-if="isResubmit"
            :label="`Revision #${milestone.revision_count}`"
            variant="gold"
          />
        </div>
      </div>

      <!-- Provider revision feedback (resubmit context) -->
      <div v-if="isResubmit && milestone.revision_notes" class="ms-submit-revision-context">
        <div class="ms-submit-revision-label">
          <AegisIcon name="message-circle" :size="13" />
          Provider feedback to address:
        </div>
        <div class="ms-submit-revision-text">{{ milestone.revision_notes }}</div>
      </div>

      <!-- Work summary -->
      <div class="form-group">
        <label class="form-label" for="ms-submit-notes">
          {{ isResubmit ? 'What changed / what was revised' : 'Work summary' }}
          <span class="req">*</span>
        </label>
        <textarea
          id="ms-submit-notes"
          v-model="form.notes"
          class="form-textarea"
          :class="{ 'is-error': fieldError('notes') }"
          rows="6"
          :placeholder="isResubmit
            ? 'Describe what you changed based on the provider\'s feedback. Be specific.'
            : 'Describe what was completed. Include relevant details, links to deliverables, or any context the provider needs to review your work.'"
          @blur="v$.notes.$touch()"
        />
        <div class="form-hint">{{ form.notes.length }} / 2000 characters</div>
        <div v-if="fieldError('notes')" class="form-error">{{ fieldError('notes') }}</div>
      </div>

      <!-- Hours logged (optional) -->
      <div class="form-group">
        <label class="form-label" for="ms-submit-hours">
          Hours logged <span class="form-label-hint">(optional)</span>
        </label>
        <div class="input-affix">
          <input
            id="ms-submit-hours"
            v-model.number="form.hours_logged"
            type="number"
            min="0"
            step="0.5"
            max="9999"
            class="form-input"
            :class="{ 'is-error': fieldError('hours_logged') }"
            placeholder="0"
            @blur="v$.hours_logged.$touch()"
          />
          <span class="input-affix-suffix">hrs</span>
        </div>
        <div v-if="fieldError('hours_logged')" class="form-error">{{ fieldError('hours_logged') }}</div>
      </div>

      <!-- Auto-release notice -->
      <div class="ms-submit-auto-release-notice">
        <AegisIcon name="shield-check" :size="13" />
        <span>
          After submitting, the provider has <strong>{{ autoReleaseDays }} days</strong> to review.
          If they don't respond, funds auto-release to you.
        </span>
      </div>
    </div>

    <template #footer>
      <button type="button" class="btn btn-outline" :disabled="form.processing" @click="onClose">
        Cancel
      </button>
      <button
        type="button"
        class="btn btn-primary"
        :disabled="form.processing || !canSubmit"
        @click="submit"
      >
        <span v-if="form.processing" class="spinner spinner-sm" />
        {{ form.processing ? 'Submitting…' : isResubmit ? 'Resubmit for review' : 'Submit for review' }}
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { computed, watch } from 'vue'
import { useForm }    from '@inertiajs/vue3'
import useVuelidate   from '@vuelidate/core'
import { required, minLength, maxLength, between } from '@vuelidate/validators'
import { useToast }   from '@/composables/useToast'
import { usePricingStore } from '@/stores/pricing'

const props = defineProps({
  milestone: { type: Object, default: null },
})

const emit    = defineEmits(['update:modelValue'])
const toast   = useToast()
const pricing = usePricingStore()

const autoReleaseDays = 7

// useForm at top-level
const form = useForm({
  notes:        '',
  hours_logged: null,
})

// Reset form when milestone changes
watch(() => props.milestone?.id, () => {
  form.reset()
  v$.value.$reset()
})

const isResubmit = computed(() => props.milestone?.status === 'revision_requested')

// ── Vuelidate ─────────────────────────────────────────────────────────────────
const rules = {
  notes:        { required, minLength: minLength(10), maxLength: maxLength(2000) },
  hours_logged: {},
}
const v$ = useVuelidate(rules, form, { $scope: false })

function fieldError(f) {
  const e = v$.value[f]?.$errors
  return e?.length ? e[0].$message : null
}

const canSubmit = computed(() =>
  form.notes.trim().length >= 10 &&
  form.notes.length <= 2000 &&
  !form.processing,
)

// ── Actions ───────────────────────────────────────────────────────────────────
function onClose() {
  emit('update:modelValue', null)
  setTimeout(() => { form.reset(); v$.value.$reset() }, 200)
}

async function submit() {
  const valid = await v$.value.$validate()
  if (!valid) return

  form.post(route('bp.milestones.submit', { milestone: props.milestone.id }), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(
        isResubmit.value
          ? 'Milestone resubmitted. Awaiting provider review.'
          : 'Milestone submitted for review.',
      )
      onClose()
    },
    onError: (e) => toast.error(e.milestone ?? 'Could not submit milestone.'),
  })
}
</script>
