<!--
  VaultZone.vue — one of the four zones in the Document Vault layout.

  Zones (per Aegis vault spec):
    1. Sealed — encrypted, requires unseal ceremony
    2. Unsealed — currently viewable items
    3. Plan items — attestable items
    4. Archive — historical items

  This component renders one zone: header (icon, title, status pill, count)
  + body slot for the item list. Locked zones show a gold padlock and an
  "Unseal" CTA that emits to the parent (parent owns the modal/ceremony).
-->
<template>
  <section
    class="vault-zone"
    :class="[`vault-zone--${variant}`, { 'is-sealed': sealed, 'is-empty': !count }]"
  >
    <header class="vault-zone-header">
      <div class="vault-zone-icon" :style="iconStyle">
        <AegisIcon :name="sealed ? 'lock' : icon" :size="18" />
      </div>
      <div class="vault-zone-text">
        <div class="vault-zone-title">{{ title }}</div>
        <div v-if="description" class="vault-zone-desc">{{ description }}</div>
      </div>
      <div class="vault-zone-meta">
        <AegisBadge
          :label="sealed ? 'Sealed' : 'Unsealed'"
          :variant="sealed ? 'gold' : 'green'"
          :icon="sealed ? 'lock' : 'unlock'"
        />
        <span class="vault-zone-count">{{ count }} {{ count === 1 ? 'item' : 'items' }}</span>
      </div>
    </header>

    <div v-if="sealed" class="vault-zone-sealed-body">
      <p class="vault-zone-sealed-msg">
        Items in this zone are sealed with AES-256-GCM. Unsealing is logged
        to the activity feed and notifies all designated stewards.
      </p>
      <button type="button" class="btn btn-primary" @click="$emit('unseal')">
        <AegisIcon name="key" :size="14" />
        <span>Begin unseal ceremony</span>
      </button>
    </div>

    <div v-else class="vault-zone-body">
      <slot />
      <AegisEmptyState
        v-if="!count && $slots.default === undefined"
        :icon="icon"
        title="Nothing here yet"
        :description="emptyHint"
      />
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'

const props = defineProps({
  variant:     { type: String, default: 'sealed', validator: (v) => ['sealed','unsealed','plan','archive'].includes(v) },
  title:       { type: String, required: true },
  description: { type: String, default: '' },
  icon:        { type: String, default: 'vault' },
  sealed:      { type: Boolean, default: false },
  count:       { type: Number,  default: 0 },
  emptyHint:   { type: String,  default: 'Items added here will appear in this zone.' },
})

defineEmits(['unseal'])

// Zone tint via inline style — variant maps to CSS var background tokens.
const VARIANT_BG = {
  sealed:   { background: 'var(--icon-bg-gold)',  color: 'var(--gold-dark)' },
  unsealed: { background: 'var(--icon-bg-green)', color: 'var(--green-dark)' },
  plan:     { background: 'var(--icon-bg-blue)',  color: 'var(--blue-dark)' },
  archive:  { background: 'var(--surface-3)',     color: 'var(--text-3)' },
}
const iconStyle = computed(() => VARIANT_BG[props.variant] ?? VARIANT_BG.sealed)
</script>
