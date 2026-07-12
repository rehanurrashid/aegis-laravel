<!--
  ReviewContractModal.vue — post-completion contract review.
  Used by both Provider and BP portals on completed contracts.

  Props:
    modelValue   Boolean v-model open/close
    contract     { id, title, counterparty_name } — the completed contract
    postRoute    Ziggy route name  (provider: 'provider.jobs.contract.review', bp: 'bp.contracts.review')
    dismissRoute Ziggy route name  (provider: 'provider.jobs.contract.review.dismiss', bp: 'bp.contracts.review.dismiss')

  Local import required — not globally registered.
-->
<template>
  <AegisModal
    :model-value="modelValue"
    title="Leave a review"
    size="md"
    @update:model-value="(v) => $emit('update:modelValue', v)"
  >
    <!-- Contract context -->
    <div class="review-contract-context">
      <div class="review-contract-label">Reviewing contract</div>
      <div class="review-contract-title">{{ contract?.title ?? 'Completed contract' }}</div>
      <div v-if="contract?.counterparty_name" class="review-contract-party">
        with {{ contract.counterparty_name }}
      </div>
    </div>

    <div class="form-stack" style="margin-top:20px">

      <!-- Overall rating -->
      <div class="form-group">
        <label class="form-label">Overall rating <span class="form-required">*</span></label>
        <div class="star-row" role="group" aria-label="Overall rating">
          <button
            v-for="n in 5"
            :key="n"
            type="button"
            class="star-btn"
            :class="{ filled: n <= form.rating }"
            :aria-label="`${n} star${n > 1 ? 's' : ''}`"
            @click="form.rating = n"
          >
            <AegisIcon name="star" :size="22" :filled="n <= form.rating" />
          </button>
        </div>
        <div v-if="form.errors.rating" class="form-error">{{ form.errors.rating }}</div>
      </div>

      <!-- Sub-dimension ratings -->
      <div class="review-dimensions">
        <div v-for="dim in dimensions" :key="dim.key" class="review-dim-row">
          <div class="review-dim-label">{{ dim.label }}</div>
          <div class="star-row star-row--sm" :role="'group'" :aria-label="dim.label">
            <button
              v-for="n in 5"
              :key="n"
              type="button"
              class="star-btn"
              :class="{ filled: n <= form[dim.key] }"
              @click="form[dim.key] = n"
            >
              <AegisIcon name="star" :size="16" :filled="n <= form[dim.key]" />
            </button>
          </div>
        </div>
      </div>

      <!-- Written review -->
      <div class="form-group" style="margin-top:8px">
        <label class="form-label">Your review <span class="review-optional">(optional)</span></label>
        <textarea
          v-model="form.review_text"
          class="form-input"
          rows="4"
          maxlength="1000"
          placeholder="Share your experience with this contract — what went well, what could be improved."
        ></textarea>
        <div class="form-hint">{{ (form.review_text || '').length }} / 1000 characters</div>
      </div>

      <!-- Visibility toggle -->
      <div class="review-visibility-row">
        <div class="review-visibility-info">
          <strong>Public review</strong>
          <span>Visible on their public Aegis profile</span>
        </div>
        <label class="toggle-switch" :aria-label="form.is_public ? 'Make private' : 'Make public'">
          <input type="checkbox" v-model="form.is_public" />
          <span class="toggle-track"></span>
        </label>
      </div>

      <div v-if="form.errors.review" class="form-error">{{ form.errors.review }}</div>
    </div>

    <template #footer>
      <button type="button" class="btn btn-ghost" :disabled="form.processing" @click="skip">
        Skip for now
      </button>
      <button
        type="button"
        class="btn btn-primary"
        :class="{ 'btn-spin': form.processing }"
        :disabled="!canSubmit || form.processing"
        @click="submit"
      >
        <AegisIcon v-if="form.processing" name="refresh-cw" :size="14" class="spin" />
        Submit review
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  modelValue:   { type: Boolean, default: false },
  contract:     { type: Object, default: null },   // { id, title, counterparty_name }
  postRoute:    { type: String, required: true },
  dismissRoute: { type: String, required: true },
})

const emit = defineEmits(['update:modelValue'])
const toast = useToast()

const dimensions = [
  { key: 'communication', label: 'Communication' },
  { key: 'quality',       label: 'Quality of work' },
  { key: 'timeliness',    label: 'Timeliness' },
]

const form = useForm({
  rating:        0,
  communication: 0,
  quality:       0,
  timeliness:    0,
  review_text:   '',
  is_public:     true,
})

const canSubmit = computed(() =>
  form.rating >= 1 &&
  form.communication >= 1 &&
  form.quality >= 1 &&
  form.timeliness >= 1
)

function submit() {
  form.post(route(props.postRoute, { contract: props.contract?.id }), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Review submitted. Thank you!')
      emit('update:modelValue', false)
    },
  })
}

function skip() {
  form.post(route(props.dismissRoute, { contract: props.contract?.id }), {
    preserveScroll: true,
    onSuccess: () => {
      emit('update:modelValue', false)
    },
  })
}
</script>

<style scoped>
/* ── Contract context header ─────────────────────────────────────── */
.review-contract-context {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 14px 16px;
}
.review-contract-label {
  font-family: var(--font-sans);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
  color: var(--text-3);
  margin-bottom: 4px;
}
.review-contract-title {
  font-family: var(--font-sans);
  font-size: 14px;
  font-weight: 700;
  color: var(--text);
}
.review-contract-party {
  font-family: var(--font-sans);
  font-size: 12px;
  color: var(--text-3);
  margin-top: 2px;
}

/* ── Star rating ─────────────────────────────────────────────────── */
.star-row {
  display: flex;
  gap: 4px;
  margin-top: 8px;
}
.star-row--sm { gap: 3px; margin-top: 0; }

.star-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 2px;
  color: var(--border-dark);
  transition: color var(--transition), transform 0.1s;
  line-height: 1;
}
.star-btn:hover { color: var(--gold-dark); }
.star-btn.filled { color: var(--gold-dark); }
.star-btn:active { transform: scale(0.9); }

/* ── Sub-dimension rows ──────────────────────────────────────────── */
.review-dimensions {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 14px 0;
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
}
.review-dim-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.review-dim-label {
  font-family: var(--font-sans);
  font-size: 13px;
  color: var(--text-2);
}

/* ── Visibility toggle row ───────────────────────────────────────── */
.review-visibility-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 0;
  border-top: 1px solid var(--border);
}
.review-visibility-info strong {
  display: block;
  font-family: var(--font-sans);
  font-size: 13px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 2px;
}
.review-visibility-info span {
  font-family: var(--font-sans);
  font-size: 12px;
  color: var(--text-3);
}

/* ── Toggle switch ───────────────────────────────────────────────── */
.toggle-switch {
  position: relative;
  display: inline-block;
  width: 40px;
  height: 22px;
  flex-shrink: 0;
  cursor: pointer;
}
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-track {
  position: absolute;
  inset: 0;
  background: var(--border);
  border-radius: 11px;
  transition: background var(--transition);
}
.toggle-track::before {
  content: '';
  position: absolute;
  left: 3px;
  top: 3px;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #fff;
  transition: transform var(--transition);
}
.toggle-switch input:checked + .toggle-track { background: var(--gold-dark); }
.toggle-switch input:checked + .toggle-track::before { transform: translateX(18px); }

.review-optional {
  font-weight: 400;
  color: var(--text-3);
  font-size: 12px;
}
</style>
