<!--
  PaymentTermsInline.vue — payment terms selection for service request flow.

  Used inside ServiceRequestModal (and BP's proposal modal in sibling plan).
  Client sees provider's default terms and can either accept them or propose different terms.

  Props:
    providerDefaults  — { structure, upfrontPercentage, termsNote, allowCompletionOnly }
    modelValue        — { structure, upfrontPercentage, termsNote, termsSource }
    allowedStructures — Array of allowed structure values (default: all three)

  Emits:
    update:modelValue — updated terms object

  Requires local import — NOT globally registered.
-->
<template>
  <div class="pt-wrap">

    <!-- Provider default summary chip ────────────────────────────── -->
    <div class="pt-defaults-row">
      <div class="pt-defaults-label">
        <AegisIcon name="credit-card" :size="14" />
        Payment terms
      </div>
      <div class="pt-defaults-chip">
        <AegisBadge :label="defaultChipLabel" variant="gold" />
        <button v-if="providerDefaults.termsNote" type="button" class="pt-see-more"
                @click="showNote = !showNote">
          {{ showNote ? 'Hide note' : 'See note' }}
        </button>
      </div>
    </div>
    <div v-if="showNote && providerDefaults.termsNote" class="pt-note-preview">
      {{ providerDefaults.termsNote }}
    </div>

    <!-- Radio: accept vs propose ──────────────────────────────────── -->
    <div class="pt-choice-row">
      <label class="pt-radio-label">
        <input type="radio" name="terms_choice" :value="false" v-model="isProposing"
               @change="onAccept" />
        <span>Accept provider's terms</span>
      </label>
      <label class="pt-radio-label">
        <input type="radio" name="terms_choice" :value="true" v-model="isProposing"
               @change="onStartPropose" />
        <span>Propose different terms</span>
      </label>
    </div>

    <!-- Terms editor (when proposing) ─────────────────────────────── -->
    <div v-if="isProposing" class="pt-editor">

      <!-- Structure picker -->
      <div class="form-group">
        <label class="form-label">Payment structure</label>
        <div class="pt-structure-list">
          <label v-for="opt in visibleStructures" :key="opt.value" class="pt-structure-option"
                 :class="{ 'pt-structure-option--active': localValue.structure === opt.value }">
            <input type="radio" :value="opt.value" v-model="localValue.structure"
                   @change="onStructureChange" />
            <div class="pt-structure-body">
              <div class="pt-structure-name">{{ opt.label }}</div>
              <div class="pt-structure-desc">{{ opt.desc }}</div>
            </div>
          </label>
        </div>
      </div>

      <!-- Upfront percentage (split only) -->
      <div v-if="localValue.structure === 'split'" class="form-group">
        <label class="form-label">Upfront percentage (%)</label>
        <div class="pt-pct-row">
          <input
            v-model.number="localValue.upfrontPercentage"
            type="number"
            min="1"
            max="99"
            class="form-input pt-pct-input"
            :class="{ 'is-error': pctError }"
            @input="onPctInput"
          />
          <span class="pt-pct-suffix">% upfront / {{ completionPct }}% at completion</span>
        </div>
        <div v-if="pctError" class="form-error">Enter a value between 1 and 99.</div>
      </div>

      <!-- Terms note -->
      <div class="form-group">
        <label class="form-label">Note to provider <span class="form-label-optional">(optional)</span></label>
        <textarea
          v-model="localValue.termsNote"
          class="form-input"
          rows="2"
          maxlength="2000"
          placeholder="Explain your proposed terms…"
          @input="emit"
        />
      </div>

    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  providerDefaults: {
    type: Object,
    default: () => ({
      structure: 'split',
      upfrontPercentage: 30,
      termsNote: null,
      allowCompletionOnly: false,
    }),
  },
  modelValue: {
    type: Object,
    default: () => ({
      structure: 'split',
      upfrontPercentage: 30,
      termsNote: null,
      termsSource: 'provider_default',
    }),
  },
  allowedStructures: {
    type: Array,
    default: () => ['full_upfront', 'split', 'full_on_completion'],
  },
})

const emits = defineEmits(['update:modelValue'])

const isProposing = ref(false)
const showNote    = ref(false)
const localValue  = ref({ ...props.modelValue })

const structureOptions = [
  { value: 'full_upfront',      label: 'Full payment upfront',   desc: '100% due before the session begins.' },
  { value: 'split',             label: 'Split payment',           desc: 'Upfront % now, remainder at completion.' },
  { value: 'full_on_completion', label: 'Pay after session',      desc: 'Full payment collected after session is confirmed complete.' },
]

const visibleStructures = computed(() => {
  return structureOptions.filter(o => {
    if (!props.allowedStructures.includes(o.value)) return false
    // full_on_completion only shown if provider allows it
    if (o.value === 'full_on_completion' && !props.providerDefaults.allowCompletionOnly) return false
    return true
  })
})

const defaultChipLabel = computed(() => {
  const s   = props.providerDefaults.structure ?? 'split'
  const pct = props.providerDefaults.upfrontPercentage ?? 30
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

function onAccept() {
  isProposing.value = false
  localValue.value = {
    structure:         props.providerDefaults.structure ?? 'split',
    upfrontPercentage: props.providerDefaults.upfrontPercentage ?? 30,
    termsNote:         props.providerDefaults.termsNote ?? null,
    termsSource:       'provider_default',
  }
  emits('update:modelValue', { ...localValue.value })
}

function onStartPropose() {
  isProposing.value = true
  localValue.value.termsSource = 'client_proposed'
  emits('update:modelValue', { ...localValue.value })
}

function onStructureChange() {
  if (localValue.value.structure !== 'split') {
    localValue.value.upfrontPercentage = localValue.value.structure === 'full_upfront' ? 100 : 0
  } else {
    localValue.value.upfrontPercentage = props.providerDefaults.upfrontPercentage ?? 30
  }
  emits('update:modelValue', { ...localValue.value })
}

function onPctInput() {
  emits('update:modelValue', { ...localValue.value })
}

function emit() {
  emits('update:modelValue', { ...localValue.value })
}

watch(() => props.modelValue, (v) => {
  localValue.value = { ...v }
}, { deep: true })
</script>

<style scoped>
.pt-wrap { border-top: 1px solid var(--border); padding-top: 12px; margin-top: 8px; }

.pt-defaults-row {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 6px;
}
.pt-defaults-label {
  display: flex; align-items: center; gap: 6px;
  font-size: 13px; font-weight: 600; color: var(--text-2);
}
.pt-defaults-chip { display: flex; align-items: center; gap: 8px; }

.pt-see-more {
  background: none; border: none; cursor: pointer;
  font-size: 11px; color: var(--gold-dark); text-decoration: underline; padding: 0;
}
.pt-note-preview {
  font-size: 12px; color: var(--text-3); background: var(--badge-bg-gold);
  border: 1px solid var(--gold); border-radius: var(--radius);
  padding: 8px 10px; margin-bottom: 8px; line-height: 1.5;
}

.pt-choice-row {
  display: flex; gap: 16px; margin: 10px 0 0;
}
.pt-radio-label {
  display: flex; align-items: center; gap: 6px;
  font-size: 13px; color: var(--text-2); cursor: pointer;
}

.pt-editor {
  background: var(--surface-2, #faf9f7);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 14px;
  margin-top: 10px;
}

.pt-structure-list { display: flex; flex-direction: column; gap: 6px; }
.pt-structure-option {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  cursor: pointer;
  transition: border-color 0.15s;
}
.pt-structure-option--active {
  border-color: var(--gold);
  background: var(--badge-bg-gold);
}
.pt-structure-option input[type="radio"] { margin-top: 2px; flex-shrink: 0; }
.pt-structure-name { font-size: 13px; font-weight: 600; color: var(--text-1); }
.pt-structure-desc { font-size: 11px; color: var(--text-3); margin-top: 2px; line-height: 1.4; }

.pt-pct-row { display: flex; align-items: center; gap: 10px; }
.pt-pct-input { width: 80px; text-align: right; }
.pt-pct-suffix { font-size: 12px; color: var(--text-3); }
</style>
