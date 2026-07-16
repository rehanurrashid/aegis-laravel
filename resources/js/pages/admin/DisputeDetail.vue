<!--
  pages/admin/DisputeDetail.vue — admin review + resolution of a single dispute.

  Props:
    dispute    { id, disputer, respondent, subject_type, subject_id, ..., is_active }
    messages   list of thread messages
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Admin Dispute"
      :title="`Dispute ${dispute.id.slice(0,8)}…`"
      :subtitle="`${dispute.disputer.name} v. ${dispute.respondent.name} · ${dispute.reason_label}`"
    />

    <div class="dispute-header-grid">
      <AegisCard title="Summary">
        <dl class="def-list">
          <dt>Status</dt><dd><AegisBadge :label="dispute.status_label" :variant="statusVariant(dispute.status)" /></dd>
          <dt>Reason</dt><dd>{{ dispute.reason_label }}</dd>
          <dt>Amount disputed</dt><dd>{{ formatCents(dispute.amount_disputed_cents) }}</dd>
          <dt>Subject</dt><dd class="mono">{{ dispute.subject_type }} · {{ dispute.subject_id.slice(0,8) }}…</dd>
          <dt>Opened</dt><dd>{{ dispute.opened_at }}</dd>
          <dt v-if="dispute.resolved_at">Resolved</dt><dd v-if="dispute.resolved_at">{{ dispute.resolved_at }} by {{ dispute.resolved_by }}</dd>
        </dl>
      </AegisCard>

      <AegisCard title="Disputer's statement">
        <p class="dispute-desc">{{ dispute.description }}</p>
        <div class="dispute-parties">
          <div class="party">
            <div class="party-label">Disputer</div>
            <div class="party-name">{{ dispute.disputer.name }}</div>
            <div class="party-email">{{ dispute.disputer.email }}</div>
          </div>
          <div class="party">
            <div class="party-label">Respondent</div>
            <div class="party-name">{{ dispute.respondent.name }}</div>
            <div class="party-email">{{ dispute.respondent.email }}</div>
          </div>
        </div>
      </AegisCard>
    </div>

    <AegisCard title="Message thread">
      <div v-if="messages.length" class="thread">
        <div v-for="m in messages" :key="m.id" class="msg" :class="`msg-${m.author_role}`">
          <div class="msg-head">
            <span class="msg-author">{{ m.author_name }}</span>
            <span class="msg-role">{{ m.author_role }}</span>
            <span class="msg-date">{{ m.created_at }}</span>
          </div>
          <div class="msg-body">{{ m.body }}</div>
        </div>
      </div>
      <p v-else class="thread-empty">No messages yet.</p>

      <div v-if="dispute.is_active" class="admin-reply">
        <textarea v-model="replyBody" class="form-input" rows="3" maxlength="5000" placeholder="Post a message as admin (both parties will see it)…"></textarea>
        <div class="reply-actions">
          <button type="button" class="btn btn-outline btn-sm" :disabled="!replyBody.trim() || replying" @click="postReply">
            {{ replying ? 'Posting…' : 'Post reply' }}
          </button>
        </div>
      </div>
    </AegisCard>

    <AegisCard v-if="dispute.is_active" title="Resolve">
      <div class="form-stack">
        <div class="form-group">
          <label class="form-label">Resolution</label>
          <select v-model="resForm.resolution" class="form-input">
            <option value="">— Select outcome —</option>
            <option value="refund_full">Full refund to disputer</option>
            <option value="refund_partial">Partial refund to disputer</option>
            <option value="pay_full">Dismiss — full payment to respondent stands</option>
            <option value="pay_partial">Partial payment to respondent</option>
            <option value="no_action">No action (dispute dismissed)</option>
            <option value="stripe_dispute_escalated">Escalate to Stripe chargeback process</option>
          </select>
        </div>
        <div v-if="isPartial" class="form-group">
          <label class="form-label">Cents amount</label>
          <input v-model.number="resDollars" type="number" min="0.01" :max="(dispute.amount_disputed_cents / 100).toFixed(2)" step="0.01" class="form-input" />
          <div class="form-hint">Up to {{ formatCents(dispute.amount_disputed_cents) }}.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Resolution summary (visible to both parties)</label>
          <textarea v-model="resForm.summary" class="form-input" rows="4" maxlength="2000" minlength="10"></textarea>
        </div>
        <div class="dispute-warning">
          <AegisIcon name="alert-triangle" :size="14" />
          <div>
            <strong>Refund resolutions</strong> fire <code>Stripe refunds->create</code> against the subject's PaymentIntent immediately. This is irreversible.
            <strong>Dismiss / pay resolutions</strong> unfreeze the invoice so the payer can complete payment.
          </div>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn-danger btn-sm" :disabled="!canResolve || resolving" @click="submitResolution">
          {{ resolving ? 'Resolving…' : 'Resolve dispute' }}
        </button>
      </template>
    </AegisCard>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisHeroBanner from '@/components/ui/AegisHeroBanner.vue'
import AegisCard  from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisIcon  from '@/components/ui/AegisIcon.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  dispute:  { type: Object, required: true },
  messages: { type: Array,  default: () => [] },
})

const toast = useToast()

const replyBody = ref('')
const replying  = ref(false)
const resolving = ref(false)
const resDollars = ref(null)

const resForm = useForm({
  resolution:        '',
  resolution_cents:  null,
  summary:           '',
})

const isPartial = computed(() => ['refund_partial', 'pay_partial'].includes(resForm.resolution))
const canResolve = computed(() =>
  !!resForm.resolution &&
  resForm.summary.length >= 10 &&
  (!isPartial.value || (resForm.resolution_cents > 0 && resForm.resolution_cents <= props.dispute.amount_disputed_cents))
)

watch(() => resDollars.value, (v) => {
  resForm.resolution_cents = v ? Math.round(Number(v) * 100) : null
})

function formatCents(c) {
  const n = Number(c ?? 0) / 100
  return '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function statusVariant(s) {
  return { open: 'gold', awaiting_response: 'gold', under_review: 'blue', resolved: 'green', closed_no_action: 'neutral' }[s] ?? 'neutral'
}

function postReply() {
  if (!replyBody.value.trim()) return
  replying.value = true
  router.post(route('admin.disputes.reply', { dispute: props.dispute.id }), { body: replyBody.value }, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Message posted.'); replyBody.value = '' },
    onFinish:  () => { replying.value = false },
  })
}

function submitResolution() {
  if (!canResolve.value) return
  resolving.value = true
  resForm.post(route('admin.disputes.resolve', { dispute: props.dispute.id }), {
    preserveScroll: true,
    onSuccess: () => toast.success('Dispute resolved.'),
    onFinish:  () => { resolving.value = false },
  })
}
</script>

<style scoped>
.dispute-header-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 14px; margin-bottom: 14px; }
.def-list            { display: grid; grid-template-columns: max-content 1fr; column-gap: 14px; row-gap: 6px; margin: 0; }
.def-list dt         { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-3); font-weight: 600; }
.def-list dd         { font-size: 13px; color: var(--text); margin: 0; }
.mono                { font-family: monospace; font-size: 12px; }
.dispute-desc        { font-size: 13px; color: var(--text-2); line-height: 1.55; margin: 0 0 12px; padding: 10px 12px; background: var(--surface-2); border-radius: var(--radius-sm); }
.dispute-parties     { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.party               { padding: 10px 12px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); }
.party-label         { font-size: 10px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-3); font-weight: 600; }
.party-name          { font-size: 13px; font-weight: 600; color: var(--text); }
.party-email         { font-size: 11px; color: var(--text-4); }
.thread              { display: flex; flex-direction: column; gap: 10px; max-height: 400px; overflow-y: auto; padding: 4px; }
.msg                 { padding: 10px 12px; border-radius: var(--radius-sm); background: var(--surface); border: 1px solid var(--border); }
.msg-disputer        { border-left: 3px solid var(--red); }
.msg-respondent      { border-left: 3px solid var(--blue-dark); }
.msg-admin           { border-left: 3px solid var(--gold-dark); background: var(--surface-2); }
.msg-head            { display: flex; gap: 8px; align-items: center; margin-bottom: 4px; font-size: 11px; color: var(--text-3); }
.msg-author          { font-weight: 700; color: var(--text-2); }
.msg-role            { text-transform: uppercase; font-size: 9px; letter-spacing: .5px; padding: 2px 6px; background: var(--surface-2); border-radius: 3px; }
.msg-body            { font-size: 13px; color: var(--text); white-space: pre-wrap; }
.thread-empty        { font-size: 13px; color: var(--text-4); text-align: center; padding: 20px; }
.admin-reply         { margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border); }
.reply-actions       { display: flex; justify-content: flex-end; margin-top: 8px; }
.form-stack          { display: flex; flex-direction: column; gap: 14px; }
.form-group          { display: flex; flex-direction: column; gap: 6px; }
.form-label          { font-size: 11px; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; color: var(--text-2); }
.form-input          { padding: 10px 12px; font-size: 13.5px; color: var(--text); background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); font-family: inherit; }
.form-hint           { font-size: 11px; color: var(--text-4); }
.dispute-warning     { display: flex; gap: 8px; padding: 12px; background: rgba(220,38,38,.05); border-radius: var(--radius-sm); font-size: 12px; color: var(--text-2); line-height: 1.5; }
</style>
