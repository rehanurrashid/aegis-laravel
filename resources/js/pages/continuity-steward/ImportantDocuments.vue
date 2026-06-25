<!--
  pages/continuity-steward/ImportantDocuments.vue — documents held in trust for practitioners.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="My Work"
      title="Important Documents"
      subtitle="Practice documents practitioners have shared with you."
    />

    <div class="stat-chips-row">
      <AegisStatChip icon="file-text" :value="documents.length" label="Documents" />
      <AegisStatChip icon="users"     :value="providerCount"    label="Across practitioners" bg-color="var(--icon-bg-blue)" icon-color="var(--blue-dark)" />
    </div>

    <AegisCard v-if="documents.length">
      <table class="data-table">
        <thead>
          <tr>
            <th>Document</th>
            <th>Practitioner</th>
            <th>Category</th>
            <th>Updated</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in documents" :key="d.id">
            <td class="data-table-primary">
              <AegisIcon name="file-text" :size="13" />
              {{ d.title }}
            </td>
            <td>{{ d.provider_name }}</td>
            <td>{{ d.category ?? '—' }}</td>
            <td>{{ activity.timeAgo(d.updated_at) }}</td>
            <td>
              <a :href="d.download_url" class="btn btn-ghost btn-icon btn-sm" data-tip="Download">
                <AegisIcon name="download" :size="13" />
              </a>
            </td>
          </tr>
        </tbody>
      </table>
    </AegisCard>

    <AegisEmptyState v-else icon="file-text" title="No documents shared with you yet" />
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import { useActivity } from '@/composables/useActivity'

const props = defineProps({ documents: { type: Array, default: () => [] } })
const activity = useActivity()
const providerCount = computed(() => new Set(props.documents.map((d) => d.provider_id)).size)
</script>
