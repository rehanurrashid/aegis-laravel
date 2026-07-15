<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  planStatus:         { type: String,  default: null },
  annualReviewDate:   { type: String,  default: null },
  hasDraftInProgress: { type: Boolean, default: false },
  draftPlanVersion:   { type: Number,  default: null },
  context:            { type: String,  default: 'cs' }, // 'cs' | 'ss' | 'vault' | 'documents'
})

const annualReviewOverdue = computed(() =>
  props.planStatus === 'annual_review_due' ||
  (props.annualReviewDate && new Date(props.annualReviewDate) < new Date())
)

const show = computed(() => annualReviewOverdue.value || props.hasDraftInProgress)

// Per-context body copy
const bodyState1 = computed(() => {
  if (props.context === 'vault')      return 'Verify your vault contents are current then begin your annual review.'
  if (props.context === 'documents')  return 'Ensure all agreements are signed then begin your annual review.'
  return 'Confirm all steward details are current and begin your annual review.'
})
const bodyState2 = computed(() => {
  if (props.context === 'vault')      return 'Attest your vault contents then go to your plan to finalize and sign.'
  if (props.context === 'documents')  return 'Ensure all agreements are signed then go to your plan to finalize and sign.'
  return 'Your review draft is ready. Finalize and sign it to complete your review.'
})
const bodyState3 = computed(() => {
  if (props.context === 'vault')      return 'Attest your vault then go to your plan to finalize and sign.'
  if (props.context === 'documents')  return 'Ensure agreements are signed then go to your plan to finalize and sign.'
  return 'Your review draft is in progress. Complete and sign it when ready.'
})

function formatDate(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>

<template>
  <template v-if="show">

    <!-- State 1: overdue, no draft -->
    <div v-if="annualReviewOverdue && !hasDraftInProgress" class="alert alert-warning" style="margin-bottom:14px;">
      <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
      <div class="alert-content">
        <div class="alert-title">Annual Review Due — {{ formatDate(annualReviewDate) }}</div>
        <div>{{ bodyState1 }}</div>
        <div style="margin-top:10px;">
          <button
            type="button"
            class="btn btn-primary"
            style="display:inline-flex;align-items:center;gap:6px;"
            @click="router.visit(route('provider.plan.index') + '?action=begin_review')"
          >
            <AegisIcon name="arrow-right" :size="14" /> Begin Annual Review
          </button>
        </div>
      </div>
    </div>

    <!-- State 2: overdue, draft in progress -->
    <div v-else-if="annualReviewOverdue && hasDraftInProgress" class="alert alert-warning" style="margin-bottom:14px;">
      <div class="alert-icon"><AegisIcon name="alert-triangle" :size="18" /></div>
      <div class="alert-content">
        <div class="alert-title">Review Draft v{{ draftPlanVersion ?? '' }} In Progress</div>
        <div>{{ bodyState2 }}</div>
        <div style="margin-top:10px;">
          <button
            type="button"
            class="btn btn-primary"
            style="display:inline-flex;align-items:center;gap:6px;"
            @click="router.visit(route('provider.plan.index') + '?action=sign')"
          >
            <AegisIcon name="check" :size="14" /> Complete &amp; Sign
          </button>
        </div>
      </div>
    </div>

    <!-- State 3: not overdue, draft in progress -->
    <div v-else-if="!annualReviewOverdue && hasDraftInProgress" class="alert alert-info" style="margin-bottom:14px;">
      <div class="alert-icon"><AegisIcon name="info" :size="18" /></div>
      <div class="alert-content">
        <div class="alert-title">Review Draft v{{ draftPlanVersion ?? '' }} In Progress</div>
        <div>{{ bodyState3 }}</div>
        <div style="margin-top:10px;">
          <button
            type="button"
            class="btn btn-primary"
            style="display:inline-flex;align-items:center;gap:6px;"
            @click="router.visit(route('provider.plan.index'))"
          >
            <AegisIcon name="arrow-right" :size="14" /> Go to Plan
          </button>
        </div>
      </div>
    </div>

  </template>
</template>
