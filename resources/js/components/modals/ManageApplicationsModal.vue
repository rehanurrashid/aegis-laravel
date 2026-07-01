<!--
  ManageApplicationsModal.vue — replaces job-postings.php #manageAppsModal.
  Per-job applicant list with stage filter chips; clicking a row opens
  ApplicantProfileModal (emitted up to the page, which owns that modal).
-->
<template>
  <AegisModal v-model="isOpen" :title="title" size="xl" @update:model-value="onUpdateOpen">
    <div v-if="job" class="modal-sub" style="margin-bottom:14px">{{ counts.total }} total · {{ counts.new }} new · {{ counts.shortlisted }} shortlisted · {{ counts.rejected }} rejected</div>

    <div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap">
      <button class="btn btn-sm" :class="filter === 'all' ? 'btn-primary' : 'btn-outline'" @click="filter = 'all'">All ({{ counts.total }})</button>
      <button class="btn btn-sm" :class="filter === 'new' ? 'btn-primary' : 'btn-outline'" @click="filter = 'new'">New ({{ counts.new }})</button>
      <button class="btn btn-sm" :class="filter === 'reviewed' ? 'btn-primary' : 'btn-outline'" @click="filter = 'reviewed'">Reviewed ({{ counts.reviewed }})</button>
      <button class="btn btn-sm" :class="filter === 'shortlisted' ? 'btn-primary' : 'btn-outline'" @click="filter = 'shortlisted'">Shortlisted ({{ counts.shortlisted }})</button>
      <button class="btn btn-sm" :class="filter === 'rejected' ? 'btn-primary' : 'btn-outline'" @click="filter = 'rejected'">Rejected ({{ counts.rejected }})</button>
    </div>

    <AegisEmptyState v-if="!filteredProposals.length" icon="users" title="No applicants in this view" description="Try a different filter." />

    <div v-else style="display:flex;flex-direction:column;gap:8px">
      <div v-for="p in filteredProposals" :key="p.id" class="ma-row" @click="$emit('open-profile', p)">
        <div class="ma-avatar" :style="avatarStyle(p.bp)">
          <template v-if="!p.bp?.avatar_url">{{ initials(p.bp?.display_name) }}</template>
        </div>
        <div style="flex:1;min-width:0">
          <div class="ma-name">{{ p.bp?.display_name ?? 'Business Partner' }}</div>
          <div class="ma-role">{{ bpTypeLabel(p.bp?.bp_type) }} · Applied {{ formatDate(p.submitted_at) }}</div>
        </div>
        <div style="font-size:13px;font-weight:700;color:var(--green)">{{ formatCents(p.proposed_rate_cents) }}</div>
        <span class="badge" :class="statusBadgeClass(p)">{{ statusLabel(p) }}</span>
        <AegisIcon name="chevron-right" :size="14" style="color:var(--text-3)" />
      </div>
    </div>

    <template #footer>
      <button type="button" class="btn btn-outline" @click="close">Close</button>
      <button type="button" class="btn btn-primary" @click="$emit('view-pipeline')">
        <AegisIcon name="grid" :size="13" />
        View in Pipeline
      </button>
    </template>
  </AegisModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import AegisIcon from '@/components/ui/AegisIcon.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  job:        { type: Object, default: null },
  proposals:  { type: Array, default: () => [] },
})
const emit = defineEmits(['update:modelValue', 'open-profile', 'view-pipeline'])

const filter = ref('all')
const isOpen = computed(() => props.modelValue)
function onUpdateOpen(v) { emit('update:modelValue', v) }
function close() { emit('update:modelValue', false) }

watch(() => props.modelValue, (v) => { if (v) filter.value = 'all' })

const title = computed(() => `Applications — ${props.job?.title ?? ''}`)

const counts = computed(() => {
  const list = props.proposals
  return {
    total: list.length,
    new: list.filter(p => (p.pipeline_stage || 'new') === 'new').length,
    reviewed: list.filter(p => p.pipeline_stage === 'reviewed').length,
    shortlisted: list.filter(p => p.pipeline_stage === 'shortlisted').length,
    rejected: list.filter(p => p.status === 'declined').length,
  }
})

const filteredProposals = computed(() => {
  if (filter.value === 'all') return props.proposals
  if (filter.value === 'rejected') return props.proposals.filter(p => p.status === 'declined')
  return props.proposals.filter(p => (p.pipeline_stage || 'new') === filter.value)
})

function bpTypeLabel(t) { return t ? t.charAt(0).toUpperCase() + t.slice(1) : 'Business Partner' }
function formatDate(d) { return d ? new Date(d).toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) : '—' }
function formatCents(c) { return c ? '$' + (c / 100).toLocaleString() : '—' }
function initials(name) {
  if (!name) return 'BP'
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}
const palette = ['var(--gold-dark)', 'var(--blue-dark)', 'var(--green-dark)', 'var(--orange)']
function avatarStyle(bp) {
  if (bp?.avatar_url) {
    return { backgroundImage: `url(${bp.avatar_url})`, backgroundSize: 'cover', backgroundPosition: 'center' }
  }
  const i = (bp?.id?.charCodeAt(2) ?? 0) % palette.length
  return { background: palette[i] }
}
function statusLabel(p) {
  if (p.status === 'accepted') return 'Hired'
  if (p.status === 'declined') return 'Rejected'
  return ({ new: 'New', reviewed: 'Reviewed', shortlisted: 'Shortlisted', interview: 'Interview' }[p.pipeline_stage] || 'New')
}
function statusBadgeClass(p) {
  if (p.status === 'accepted') return 'badge-green'
  if (p.status === 'declined') return 'badge-red'
  return ({ new: 'badge-gray', reviewed: 'badge-orange', shortlisted: 'badge-blue', interview: 'badge-gold' }[p.pipeline_stage] || 'badge-gray')
}
</script>

<style scoped>
.ma-row { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border: 1px solid var(--border); border-radius: var(--radius-sm); cursor: pointer; transition: background var(--transition); }
.ma-row:hover { background: var(--surface-2); }
.ma-avatar { width: 38px; height: 38px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; color: var(--text-inverted); flex-shrink: 0; }
.ma-name { font-size: 13px; font-weight: 700; color: var(--text); }
.ma-role { font-size: 11px; color: var(--text-3); margin-top: 1px; }
</style>
