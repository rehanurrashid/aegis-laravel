<!--
  BpInvoiceRow.vue — BP invoice table row (sic-row pattern).
  Emits @open(invoice) — parent (BpFinanceTable) handles all modals centrally.
-->
<template>
  <tr
    class="bpir-row"
    :class="`bpir-row--${sv(invoice.status)}`"
  >
    <!-- Col 1: Avatar + name + contract + invoice # -->
    <td class="bpir-td bpir-td--party" @click="$emit('open', invoice)">
      <div class="bpir-party">
        <div class="bpir-avatar">{{ initials(invoice.bp_name) }}</div>
        <div class="bpir-party-info">
          <a
            v-if="invoice.bp_slug"
            :href="`/public/business/${invoice.bp_slug}`"
            class="bpir-party-name bpir-party-name--link"
            @click.stop
          >{{ invoice.bp_name }}</a>
          <span v-else class="bpir-party-name">{{ invoice.bp_name }}</span>
          <span class="bpir-service-name">{{ invoice.contract_title }}</span>
          <span class="bpir-date-sub">
            #{{ invoice.invoice_number }}
            <template v-if="invoice.issued_month"> · {{ invoice.issued_month }}</template>
          </span>
        </div>
      </div>
    </td>

    <!-- Col 2: Status badges -->
    <td class="bpir-td bpir-td--status" @click="$emit('open', invoice)">
      <div class="bpir-badges">
        <AegisBadge :label="statusLabel(invoice.status)" :variant="statusVariant(invoice.status)" />
        <AegisBadge v-if="invoice.active_dispute_id" label="Disputed" variant="red" />
      </div>
    </td>

    <!-- Col 3: Chevron -->
    <td class="bpir-td bpir-td--actions" @click="$emit('open', invoice)">
      <button type="button" class="btn-icon" data-tooltip="View details & actions">
        <AegisIcon name="chevron-right" :size="15" />
      </button>
    </td>
  </tr>
</template>

<script setup>
const props = defineProps({
  invoice: { type: Object, required: true },
})

defineEmits(['open'])

const sv = (v) => (v && typeof v === 'object' && 'value' in v) ? v.value : (v ?? '')

function initials(name) {
  if (!name) return 'BP'
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}
function statusLabel(s) {
  return ({
    sent:     'Awaiting Approval',
    overdue:  'Overdue',
    disputed: 'Disputed',
    paid:     'Paid',
    void:     'Void',
    draft:    'Draft',
  })[sv(s)] ?? sv(s)
}
function statusVariant(s) {
  return ({
    sent:     'gold',
    overdue:  'red',
    disputed: 'red',
    paid:     'green',
    void:     'neutral',
    draft:    'neutral',
  })[sv(s)] ?? 'neutral'
}
</script>

<style scoped>
.bpir-row {
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  transition: background var(--transition);
  cursor: pointer;
}
.bpir-row:last-child { border-bottom: none; }
.bpir-row:hover { background: var(--surface-2); }

.bpir-row--sent     td:first-child { border-left: 3px solid var(--gold); }
.bpir-row--overdue  td:first-child { border-left: 3px solid var(--red); }
.bpir-row--disputed td:first-child { border-left: 3px solid var(--red); }
.bpir-row--paid     td:first-child { border-left: 3px solid var(--green); }
.bpir-row--void     td:first-child { border-left: 3px solid var(--border-dark); opacity: 0.7; }

.bpir-td { padding: 10px 12px; vertical-align: middle; font-family: var(--font-sans); border-bottom: 1px solid var(--border); }
.bpir-td--party   { width: 58%; }
.bpir-td--status  { width: 34%; }
.bpir-td--actions { width: 8%; text-align: right; }

.bpir-party { display: flex; align-items: center; gap: 9px; }
.bpir-avatar {
  width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
  background: var(--badge-bg-gold); color: var(--gold-dark);
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; font-weight: 700;
}
.bpir-party-info  { min-width: 0; display: flex; flex-direction: column; gap: 1px; }
.bpir-party-name  { font-size: 13px; font-weight: 700; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bpir-party-name--link { color: var(--gold-dark); text-decoration: none; }
.bpir-party-name--link:hover { text-decoration: underline; color: var(--gold); }
.bpir-service-name { font-size: 11px; color: var(--text-4); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bpir-date-sub    { font-size: 11px; color: var(--text-4); margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bpir-badges      { display: flex; flex-wrap: wrap; gap: 4px; }
</style>
