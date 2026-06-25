<!--
  pages/continuity-steward/Vault.vue — CS view of practitioner vaults.
  Only viewable during active incidents (per-practitioner gating).
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Critical Incident"
      title="Document Vault"
      subtitle="Sealed client records you may unseal during an active incident."
    />

    <div class="stat-chips-row">
      <AegisStatChip icon="vault"  :value="totalSealed"   label="Sealed (all practitioners)" />
      <AegisStatChip icon="unlock" :value="totalUnsealed" label="Unsealed for active incidents" bg-color="var(--icon-bg-green)" icon-color="var(--green-dark)" />
    </div>

    <div class="vault-zones">
      <VaultZone
        v-for="bucket in buckets"
        :key="bucket.provider_id"
        :variant="bucket.unsealed ? 'unsealed' : 'sealed'"
        :title="bucket.provider_name"
        :description="bucket.unsealed ? 'Vault unsealed for active incident.' : 'Sealed until incident is activated.'"
        :sealed="!bucket.unsealed"
        :count="bucket.item_count"
        @unseal="openModal('csUnsealModal'); selectBucket(bucket)"
      >
        <ul v-if="bucket.unsealed && bucket.items?.length" class="vault-item-list">
          <li v-for="item in bucket.items" :key="item.id" class="vault-item-row">
            <AegisIcon name="file-text" :size="14" />
            <span>{{ item.label }}</span>
          </li>
        </ul>
      </VaultZone>
    </div>

    <AegisConfirm
      :model-value="isOpen('csUnsealModal').value"
      title="Unseal vault?"
      destructive
      primary-label="Begin unseal ceremony"
      @update:model-value="(v) => !v && closeModal('csUnsealModal')"
      @confirm="unseal"
    >
      <p>
        Unsealing is a logged action that notifies the practitioner and all
        Support Stewards. Only proceed if you are responding to an active
        critical incident.
      </p>
    </AegisConfirm>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisConfirm from '@/components/ui/AegisConfirm.vue'
import VaultZone from '@/components/features/VaultZone.vue'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'

const props = defineProps({ buckets: { type: Array, default: () => [] } })

const { openModal, closeModal, isOpen } = useModal()
const toast = useToast()

const selected = ref(null)
function selectBucket(b) { selected.value = b }

const totalSealed   = computed(() => props.buckets.filter((b) => !b.unsealed).reduce((s, b) => s + b.item_count, 0))
const totalUnsealed = computed(() => props.buckets.filter((b) =>  b.unsealed).reduce((s, b) => s + b.item_count, 0))

function unseal() {
  if (!selected.value) return
  router.post(route('cs.vault.unseal', { provider: selected.value.provider_id }), {}, {
    onSuccess: () => { toast.success('Vault unsealed for this incident.'); closeModal('csUnsealModal') },
  })
}
</script>
