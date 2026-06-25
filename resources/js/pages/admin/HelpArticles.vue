<!--
  pages/admin/HelpArticles.vue — knowledge-base authoring.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Admin"
      title="Help Articles"
      :subtitle="`${articles.length} article${articles.length === 1 ? '' : 's'} published.`"
    >
      <template #actions>
        <a :href="route('admin.help-articles.create')" class="btn btn-primary">
          <AegisIcon name="plus" :size="14" />
          <span>New article</span>
        </a>
      </template>
    </AegisHeroBanner>

    <div class="bp-filters">
      <input v-model="filters.q" type="text" class="form-input bp-filters-q" placeholder="Search articles…" @keyup.enter="apply" />
      <select v-model="filters.category" class="form-input" @change="apply">
        <option value="">All categories</option>
        <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
      </select>
    </div>

    <AegisCard v-if="articles.length">
      <table class="data-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Category</th>
            <th>Audience</th>
            <th>Status</th>
            <th>Updated</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="a in articles" :key="a.id">
            <td class="data-table-primary">
              <a :href="route('admin.help-articles.edit', { article: a.id })">{{ a.title }}</a>
            </td>
            <td>{{ a.category }}</td>
            <td>{{ a.audience }}</td>
            <td><AegisBadge :label="a.published_at ? 'Published' : 'Draft'" :variant="a.published_at ? 'green' : 'neutral'" /></td>
            <td>{{ activity.timeAgo(a.updated_at) }}</td>
            <td>
              <a :href="route('admin.help-articles.edit', { article: a.id })" class="btn btn-sm btn-outline">Edit</a>
              <button type="button" class="btn btn-sm btn-ghost btn-danger-ghost" @click="del(a)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </AegisCard>

    <AegisEmptyState v-else icon="book-open" title="No articles yet" description="Author your first help article." />
  </AppLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import { useActivity } from '@/composables/useActivity'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  articles:   { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  filters:    { type: Object, default: () => ({ q: '', category: '' }) },
})

const activity = useActivity()
const toast = useToast()
const filters = reactive({ ...props.filters })

function apply() {
  router.get(route('admin.help-articles'), filters, { preserveScroll: true, preserveState: true, replace: true, only: ['articles', 'filters'] })
}
function del(a) {
  if (!window.confirm(`Delete "${a.title}"?`)) return
  router.delete(route('admin.help-articles.destroy', { article: a.id }), { onSuccess: () => toast.success('Article deleted.') })
}
</script>
