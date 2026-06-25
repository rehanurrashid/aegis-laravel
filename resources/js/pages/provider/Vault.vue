<!--
  pages/provider/Vault.vue — Document Vault, 4-zone layout.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Continuity"
      title="Document Vault"
      subtitle="Sealed client records and continuity-critical documents."
    >
      <template #actions>
        <button type="button" class="btn btn-outline" @click="openModal('addVaultModal')">
          <AegisIcon name="plus" :size="14" />
          <span>Add item</span>
        </button>
      </template>
    </AegisHeroBanner>

    <div class="stat-chips-row">
      <AegisStatChip icon="vault"    :value="counts.sealed"   label="Sealed items" />
      <AegisStatChip icon="unlock"   :value="counts.unsealed" label="Unsealed"     bg-color="var(--icon-bg-green)"  icon-color="var(--green-dark)" />
      <AegisStatChip icon="archive"  :value="counts.archive"  label="Archived"     bg-color="var(--icon-bg-blue)"   icon-color="var(--blue-dark)" />
      <AegisStatChip icon="check-badge" :value="counts.attested" label="Attested"  bg-color="var(--icon-bg-gold)"   icon-color="var(--gold-dark)" />
    </div>

    <div class="vault-zones">
      <VaultZone
        variant="sealed"
        title="Sealed"
        description="Encrypted client records. Unsealed only during a critical incident."
        icon="vault"
        :sealed="!unsealedNow"
        :count="counts.sealed"
        @unseal="openModal('unsealCeremonyModal')"
      >
        <ul v-if="unsealedNow && items.sealed.length" class="vault-item-list">
          <li v-for="item in items.sealed" :key="item.id" class="vault-item-row">
            <AegisIcon name="file-text" :size="14" />
            <span>{{ item.label }}</span>
            <span class="vault-item-row-meta">{{ activity.timeAgo(item.created_at) }}</span>
          </li>
        </ul>
      </VaultZone>

      <VaultZone variant="unsealed" title="Unsealed" icon="unlock" :count="items.unsealed.length">
        <ul class="vault-item-list">
          <li v-for="item in items.unsealed" :key="item.id" class="vault-item-row">
            <AegisIcon name="file-text" :size="14" />
            <span>{{ item.label }}</span>
            <span class="vault-item-row-meta">{{ activity.timeAgo(item.created_at) }}</span>
          </li>
        </ul>
      </VaultZone>

      <VaultZone variant="plan" title="Plan items" icon="shield-check" :count="items.plan.length">
        <ul class="vault-item-list">
          <li v-for="item in items.plan" :key="item.id" class="vault-item-row">
            <AegisIcon name="file-text" :size="14" />
            <span>{{ item.label }}</span>
            <AegisBadge v-if="item.attested_at" label="Attested" variant="green" />
          </li>
        </ul>
      </VaultZone>

      <VaultZone variant="archive" title="Archive" icon="archive" :count="items.archive.length">
        <ul class="vault-item-list">
          <li v-for="item in items.archive" :key="item.id" class="vault-item-row">
            <AegisIcon name="file-text" :size="14" />
            <span>{{ item.label }}</span>
            <span class="vault-item-row-meta">Archived {{ activity.timeAgo(item.archived_at) }}</span>
          </li>
        </ul>
      </VaultZone>
    </div>

    <!-- Unseal ceremony modal -->
    <AegisConfirm
      :model-value="isOpen('unsealCeremonyModal').value"
      title="Begin unseal ceremony"
      primary-label="I understand, begin unseal"
      destructive
      @update:model-value="(v) => !v && closeModal('unsealCeremonyModal')"
      @confirm="unseal"
    >
      <p>
        Unsealing exposes the vault to your active session and notifies all
        designated stewards. The action is logged to your activity feed and
        the network audit trail.
      </p>
      <p><strong>Only proceed if you've activated continuity support.</strong></p>
    </AegisConfirm>
  </AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisConfirm from '@/components/ui/AegisConfirm.vue'
import VaultZone from '@/components/features/VaultZone.vue'
import { useModal } from '@/composables/useModal'
import { useToast } from '@/composables/useToast'
import { useActivity } from '@/composables/useActivity'

defineProps({
  counts: { type: Object, default: () => ({ sealed: 0, unsealed: 0, archive: 0, attested: 0 }) },
  items:  { type: Object, default: () => ({ sealed: [], unsealed: [], plan: [], archive: [] }) },
  unsealedNow: { type: Boolean, default: false },
})

const { openModal, closeModal, isOpen } = useModal()
const toast = useToast()
const activity = useActivity()

function unseal() {
  router.post(route('provider.vault.unseal'), {}, {
    onSuccess: () => { toast.success('Vault unsealed for this session.'); closeModal('unsealCeremonyModal') },
  })
}
</script>
