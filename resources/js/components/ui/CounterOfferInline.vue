<!--
  CounterOfferInline.vue — optional price negotiation within Accept Request modal.

  Renders a toggle row: "Use listing price" ↔ "Offer different price".
  When toggled on, shows a dollar input that emits the negotiated_amount_cents value.

  Props:
    listingPriceCents  — original service listing price in cents
    modelValue         — negotiated_amount_cents (null = use listing price)

  Emits:
    update:modelValue — null (use listing) | integer cents (negotiated)

  Used inside the AcceptRequest modal in Services.vue.
  AegisIcon, AegisToggle must be imported locally (not globally registered).
-->
<template>
  <div class="co-wrap">

    <!-- Toggle row ──────────────────────────────────────────────── -->
    <div class="setting-row" style="padding:8px 0">
      <div class="setting-info">
        <div class="setting-label" style="display:flex;align-items:center;gap:6px;">
          <AegisIcon name="tag" :size="14" />
          Offer a different price
        </div>
        <div class="setting-desc">
          Listing price: <strong>{{ listingPriceLabel }}</strong>.
          Toggle on to negotiate a custom rate for this session.
        </div>
      </div>
      <AegisToggle :model-value="isNegotiating" @update:model-value="toggleNegotiate" />
    </div>

    <!-- Dollar input (shown when negotiating) ───────────────────── -->
    <div v-if="isNegotiating" class="co-input-wrap">
      <div class="form-group" style="margin:0">
        <label class="form-label">Negotiated Rate ($)</label>
        <div class="co-input-row">
          <span class="co-dollar">$</span>
          <input
            v-model.number="dollars"
            type="number"
            min="0"
            step="1"
            class="form-input co-input"
            :class="{ 'is-error': dollars < 0 }"
            placeholder="0"
            @input="onDollarInput"
            @blur="onBlur"
          />
          <span class="co-per">/ session</span>
        </div>
        <div v-if="dollars < 0" class="form-error">Amount cannot be negative.</div>
        <div v-else-if="dollars > 0" class="co-note">
          This price applies to this session only.
          Deposit will be {{ depositLabel }}, balance {{ balanceLabel }}.
        </div>
        <div v-else class="co-note co-note--free">
          Setting rate to $0 makes this session free (no deposit or balance charge).
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import AegisToggle from '@/components/ui/AegisToggle.vue'

const props = defineProps({
  listingPriceCents: { type: Number, default: 0 },
  modelValue:        { type: Number, default: null }, // null = use listing
})

const emit = defineEmits(['update:modelValue'])

const isNegotiating = ref(props.modelValue !== null && props.modelValue !== props.listingPriceCents)
const dollars       = ref(props.modelValue !== null ? props.modelValue / 100 : props.listingPriceCents / 100)

const listingPriceLabel = computed(() =>
  props.listingPriceCents
    ? '$' + (props.listingPriceCents / 100).toLocaleString('en-US', { minimumFractionDigits: 0 })
    : 'Contact for pricing'
)

const depositLabel = computed(() => {
  const cents = Math.round((dollars.value || 0) * 100)
  const dep   = Math.floor(cents * 0.30)
  return '$' + (dep / 100).toFixed(2)
})

const balanceLabel = computed(() => {
  const cents = Math.round((dollars.value || 0) * 100)
  const dep   = Math.floor(cents * 0.30)
  return '$' + ((cents - dep) / 100).toFixed(2)
})

function toggleNegotiate(val) {
  isNegotiating.value = val
  if (!val) {
    // Revert to listing price → emit null
    dollars.value = props.listingPriceCents / 100
    emit('update:modelValue', null)
  } else {
    // Start from listing price as default negotiated value
    if (!dollars.value) dollars.value = props.listingPriceCents / 100
    emitCents()
  }
}

function onDollarInput() {
  if (isNegotiating.value) emitCents()
}

function onBlur() {
  // Clamp negative
  if (dollars.value < 0) dollars.value = 0
  if (isNegotiating.value) emitCents()
}

function emitCents() {
  const cents = Math.round((dollars.value || 0) * 100)
  emit('update:modelValue', cents)
}

// Sync from parent
watch(() => props.modelValue, (v) => {
  if (v === null) {
    isNegotiating.value = false
    dollars.value = props.listingPriceCents / 100
  } else {
    isNegotiating.value = true
    dollars.value = v / 100
  }
})
</script>

<style scoped>
.co-wrap { border-top: 1px solid var(--border); padding-top: 8px; margin-top: 4px; }

.co-input-wrap {
  background: var(--badge-bg-gold);
  border: 1px solid var(--gold);
  border-radius: var(--radius);
  padding: 12px 14px;
  margin-top: 6px;
}
.co-input-row {
  display: flex; align-items: center; gap: 8px;
}
.co-dollar {
  font-size: 18px; font-weight: 700; color: var(--text-3);
  flex-shrink: 0;
}
.co-input {
  flex: 1; min-width: 0;
  font-size: 18px; font-weight: 700;
  text-align: right;
}
.co-per {
  font-size: 12px; color: var(--text-4); font-weight: 600;
  flex-shrink: 0;
}
.co-note {
  font-size: 11px; color: var(--text-3); margin-top: 6px; line-height: 1.5;
}
.co-note--free { color: var(--green-dark, #2e7d32); }
</style>
