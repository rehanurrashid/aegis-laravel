<!--
  pages/provider/DisputeDetail.vue — Provider's view of one dispute.
  Shows thread, allows replies while active.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Dispute"
      :title="`Dispute ${dispute.id.slice(0,8)}…`"
      :subtitle="`${dispute.reason_label} · ${dispute.status_label}`"
    />

    <AegisCard title="Summary">
      <dl class="def-list">
        <dt>Your role</dt>
        <dd><span :class="`role-pill role-${dispute.role}`">{{ dispute.role }}</span></dd>
        <dt>Reason</dt><dd>{{ dispute.reason_label }}</dd>
        <dt>Amount disputed</dt><dd>{{ formatCents(dispute.amount_disputed_cents) }}</dd>
        <dt>Subject</dt><dd class="mono">{{ dispute.subject_type }} · {{ dispute.subject_id }}</dd>
        <dt>Opened</dt><dd>{{ dispute.opened_at }}</dd>
        <dt v-if="dispute.resolved_at">Resolved</dt><dd v-if="dispute.resolved_at">{{ dispute.resolved_at }}</dd>
        <dt v-if="dispute.resolution_label">Outcome</dt><dd v-if="dispute.resolution_label">{{ dispute.resolution_label }}</dd>
        <dt v-if="dispute.resolution_summary">Resolution notes</dt>
        <dd v-if="dispute.resolution_summary">{{ dispute.resolution_summary }}</dd>
      </dl>
    </AegisCard>

    <AegisCard :title="dispute.role === 'disputer' ? 'Your statement' : 'The disputer\'s statement'">
      <p class="dispute-desc">{{ dispute.description }}</p>
    </AegisCard>

    <AegisCard title="Conversation">
      <div v-if="messages.length" class="thread">
        <div v-for="m in messages" :key="m.id" class="msg" :class="[`msg-${m.author_role}`, { 'is-mine': m.is_mine }]">
          <div class="msg-head">
            <span class="msg-author">{{ m.author_name }}</span>
            <span class="msg-role">{{ m.author_role }}</span>
            <span class="msg-date">{{ m.created_at }}</span>
          </div>
          <div class="msg-body">{{ m.body }}</div>
        </div>
      </div>
      <p v-else class="thread-empty">No messages yet.</p>

      <div v-if="isActive" class="reply-area">
        <textarea v-model="body" class="form-input" rows="3" maxlength="5000" placeholder="Add your response…"></textarea>
        <div class="reply-actions">
          <button type="button" class="btn btn-primary btn-sm" :disabled="!body.trim() || busy" @click="submit">
            {{ busy ? 'Sending…' : 'Send reply' }}
          </button>
        </div>
      </div>
    </AegisCard>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisHeroBanner from '@/components/ui/AegisHeroBanner.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  dispute:  { type: Object, required: true },
  messages: { type: Array,  default: () => [] },
})

const toast = useToast()
const body  = ref('')
const busy  = ref(false)

const isActive = computed(() => ['open', 'awaiting_response', 'under_review'].includes(props.dispute.status))

function formatCents(c) { return '$' + (Number(c) / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }

function submit() {
  if (!body.value.trim()) return
  busy.value = true
  router.post(route('provider.disputes.reply', { dispute: props.dispute.id }), { body: body.value }, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Reply posted.'); body.value = '' },
    onFinish:  () => { busy.value = false },
  })
}
</script>

<style scoped>
.def-list       { display: grid; grid-template-columns: max-content 1fr; column-gap: 14px; row-gap: 6px; margin: 0; }
.def-list dt    { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-3); font-weight: 600; }
.def-list dd    { font-size: 13px; color: var(--text); margin: 0; }
.mono           { font-family: monospace; font-size: 12px; }
.role-pill      { font-size: 10px; text-transform: uppercase; letter-spacing: .5px; padding: 2px 8px; border-radius: 10px; font-weight: 600; }
.role-disputer  { background: rgba(220,38,38,.1); color: var(--red-dark); }
.role-respondent { background: rgba(59,130,246,.1); color: var(--blue-dark); }
.dispute-desc   { font-size: 13px; color: var(--text-2); line-height: 1.55; margin: 0; padding: 12px; background: var(--surface-2); border-radius: var(--radius-sm); }
.thread         { display: flex; flex-direction: column; gap: 10px; max-height: 400px; overflow-y: auto; padding: 4px; }
.msg            { padding: 10px 12px; border-radius: var(--radius-sm); background: var(--surface); border: 1px solid var(--border); }
.msg.is-mine    { background: rgba(196,169,106,.06); border-color: var(--gold); }
.msg-disputer   { border-left: 3px solid var(--red); }
.msg-respondent { border-left: 3px solid var(--blue-dark); }
.msg-admin      { border-left: 3px solid var(--gold-dark); background: var(--surface-2); }
.msg-head       { display: flex; gap: 8px; align-items: center; margin-bottom: 4px; font-size: 11px; color: var(--text-3); }
.msg-author     { font-weight: 700; color: var(--text-2); }
.msg-role       { text-transform: uppercase; font-size: 9px; letter-spacing: .5px; padding: 2px 6px; background: var(--surface-2); border-radius: 3px; }
.msg-body       { font-size: 13px; color: var(--text); white-space: pre-wrap; }
.thread-empty   { font-size: 13px; color: var(--text-4); text-align: center; padding: 20px; }
.reply-area     { margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border); }
.reply-actions  { display: flex; justify-content: flex-end; margin-top: 8px; }
.form-input     { width: 100%; padding: 10px 12px; font-size: 13.5px; color: var(--text); background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); font-family: inherit; }
.form-input:focus { border-color: var(--gold); outline: none; box-shadow: 0 0 0 3px rgba(196,169,106,.18); }
</style>
