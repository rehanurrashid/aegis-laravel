<!--
  CounterTermsInline.vue — payment terms counter-offer for provider Accept Request modal.

  Provider sees what the client proposed and can accept or counter with different terms.
  full_on_completion always allowed here (no provider-side gate).

  Props:
    requestedTerms    — { structure, upfrontPercentage, termsNote, termsSource }
    modelValue        — { countered, structure, upfrontPercentage, termsNote }
    allowedStructures — Array (default: all three)

  Emits:
    update:modelValue — updated counter object

  Requires local import — NOT globally registered.
-->
<template>
  <div class="ct-wrap">

    <!-- Requested terms summary ────────────────────────────────────── -->
    <div class="ct-requested">
      <div class="ct-requested-label">
        <AegisIcon name="credit-card" :size="14" />
        Client's proposed terms
      </div>
      <AegisBadge :label="requestedChipLabel" :variant="requestedTerms.termsSource === 'client_proposed' ? 'blue' : 'neutral'" />
    </div>
    <div v-if="requestedTerms.termsNote" class="ct-note-preview">
      {{ requestedTerms.termsNote }}
    </div>

    <!-- Toggle: accept as-is vs counter ───────────────────────────── -->
    <div class="setting-row" style="padding:8px 0">
      <div class="setting-info">
        <div class="setting-label">Counter with different terms</div>
        <div class="setting-desc">Toggle on to propose a different payment structure.</div>
      </div>
      <AegisToggle :model-value="localValue.countered" @update:model-value="onToggleCounter" />
    </div>

    <!-- Counter editor (when countering) ──────────────────────────── -->
    <div v-if="localValue.countered" class="ct-editor">

      <!-- Structure picker -->
      <div class="form-group">
        <label class="form-label">Proposed structure</label>
        <div class="ct-structure-list">
          <label v-for="opt in structureOptions" :key="opt.value" class="ct-structure-option"
                 :class="{ 'ct-structure-option--active': localValue.structure === opt.value }">
            <input type="radio" :value="opt.value" v-model="localValue.structure"
                   @change="onStructureChange" />
            <div class="ct-structure-body">
              <div class="ct-structure-name">{{ opt.label }}</div>
              <div class="ct-structure-desc">{{ opt.desc }}</div>
            </div>
          </label>
        </div>
      </div>

      <!-- Upfront percentage (split only) -->
      <div v-if="localValue.structure === 'split'" class="form-group">
        <label class="form-label">Upfront percentage (%)</label>
        <div class="ct-pct-row">
          <input
            v-model.number="localValue.upfrontPercentage"
            type="number"
            min="1"
            max="99"
            class="form-input ct-pct-input"
            :class="{ 'is-error': pctError }"
            @input="emit"
          />
          <span class="ct-pct-suffix">% upfront / {{ completionPct }}% at completion</span>
        </div>
        <div v-if="pctError" class="form-error">Enter a value between 1 and 99.</div>
      </div>

      <!-- Counter note -->
      <div class="form-group">
        <label class="form-label">Note to client <span class="form-label-optional">(optional)</span></label>
        <textarea
          v-model="localValue.termsNote"
          class="form-input"
          rows="2"
          maxlength="2000"
          placeholder="Explain your counter-terms…"
          @input="emit"
        />
      </div>

    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import AegisToggle from '@/components/ui/AegisToggle.vue'

const props = defineProps({
  requestedTerms: {
    type: Object,
    default: () => ({
      structure: 'split',
      upfrontPercentage: 30,
      termsNote: null,
      termsSource: 'provider_default',
    }),
  },
  modelValue: {
    type: Object,
    default: () => ({
      countered: false,
      structure: 'split',
      upfrontPercentage: 30,
      termsNote: null,
    }),
  },
})

const emits = defineEmits(['update:modelValue'])

const localValue = ref({ ...props.modelValue })

const structureOptions = [
  { value: 'full_upfront',       label: 'Full payment upfront',  desc: '100% due before the session begins.' },
  { value: 'split',              label: 'Split payment',          desc: 'Upfront % now, remainder at completion.' },
  { value: 'full_on_completion', label: 'Pay after session',      desc: 'Full payment collected after session is confirmed complete.' },
]

const requestedChipLabel = computed(() => {
  const s   = props.requestedTerms.structure ?? 'split'
  const pct = props.requestedTerms.upfrontPercentage ?? 30
  if (s === 'full_upfront')       return '100% upfront'
  if (s === 'full_on_completion') return 'Pay after session'
  return `${pct}% upfront + ${100 - pct}% completion`
})

const completionPct = computed(() => {
  const p = localValue.value.upfrontPercentage ?? 30
  return Math.max(1, 100 - Math.min(p, 99))
})

const pctError = computed(() => {
  const p = localValue.value.upfrontPercentage
  return localValue.value.structure === 'split' && (p < 1 || p > 99 || !Number.isInteger(p))
})

function onToggleCounter(val) {
  localValue.value.countered = val
  if (!val) {
    // Revert to requested terms
    localValue.value.structure         = props.requestedTerms.structure ?? 'split'
    localValue.value.upfrontPercentage = props.requestedTerms.upfrontPercentage ?? 30
    localValue.value.termsNote         = props.requestedTerms.termsNote ?? null
  }
  emit()
}

function onStructureChange() {
  if (localValue.value.structure === 'full_upfront') {
    localValue.value.upfrontPercentage = 100
  } else if (localValue.value.structure === 'full_on_completion') {
    localValue.value.upfrontPercentage = 0
  } else {
    localValue.value.upfrontPercentage = props.requestedTerms.upfrontPercentage ?? 30
  }
  emit()
}

function emit() {
  emits('update:modelValue', { ...localValue.value })
}

watch(() => props.modelValue, (v) => {
  localValue.value = { ...v }
}, { deep: true })
</script>

<style scoped>
.ct-wrap { border-top: 1px solid var(--border); padding-top: 12px; margin-top: 8px; }

.ct-requested {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 4px;
}
.ct-requested-label {
  display: flex; align-items: center; gap: 6px;
  font-size: 13px; font-weight: 600; color: var(--text-2);
}
.ct-note-preview {
  font-size: 12px; color: var(--text-3); background: var(--badge-bg-blue, #eff6ff);
  border: 1px solid var(--blue, #3b82f6); border-radius: var(--radius);
  padding: 8px 10px; margin-bottom: 6px; line-height: 1.5;
}

.ct-editor {
  background: var(--surface-2, #faf9f7);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 14px;
  margin-top: 6px;
}

.ct-structure-list { display: flex; flex-direction: column; gap: 6px; }
.ct-structure-option {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  cursor: pointer;
  transition: border-color 0.15s;
}
.ct-structure-option--active {
  border-color: var(--gold);
  background: var(--badge-bg-gold);
}
.ct-structure-option input[type="radio"] { margin-top: 2px; flex-shrink: 0; }
.ct-structure-name { font-size: 13px; font-weight: 600; color: var(--text-1); }
.ct-structure-desc { font-size: 11px; color: var(--text-3); margin-top: 2px; line-height: 1.4; }

.ct-pct-row { display: flex; align-items: center; gap: 10px; }
.ct-pct-input { width: 80px; text-align: right; }
.ct-pct-suffix { font-size: 12px; color: var(--text-3); }
</style>
