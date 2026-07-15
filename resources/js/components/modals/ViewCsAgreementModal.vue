<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  steward:    { type: Object,  default: null },
})
const emit = defineEmits(['update:modelValue'])

function close() { emit('update:modelValue', false) }

// ── Helpers ────────────────────────────────────────────────────────────────────
function formatMoney(cents) {
  if (!cents) return '$0.00'
  return '$' + (cents / 100).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

function fmtDate(val) {
  if (!val) return ''
  try { return new Date(val).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }
  catch { return '' }
}

function initials(name) {
  if (!name) return '??'
  return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()
}

function stewardName(s) {
  return s?.steward?.display_name ?? s?.display_name ?? s?.email ?? '—'
}

function csRoleLabel(role) {
  if (role === 'primary')   return 'Primary Continuity Steward'
  if (role === 'alternate') return 'Alternate Continuity Steward'
  return 'Continuity Steward'
}

const paymentTermsLabel = computed(() => {
  const map = { on_close: 'Upon incident close', net_30: 'Net 30', net_60: 'Net 60', per_incident: 'Per incident' }
  return map[props.steward?.payment_terms] ?? props.steward?.payment_terms ?? 'per incident'
})

const isActive  = computed(() => props.steward?.status === 'active')
const isPending = computed(() => ['invited', 'pending'].includes(props.steward?.status))

function downloadPdf() {
  if (!props.steward) return
  window.open(route('provider.stewards.agreement.download', { steward: props.steward.id }), '_blank')
}
</script>

<template>
  <AegisModal
    :model-value="modelValue"
    :title="'Continuity Agreement' + (steward ? ' — ' + stewardName(steward) : '')"
    size="lg"
    @update:model-value="emit('update:modelValue', $event)"
    @close="close"
  >
    <template v-if="steward">

      <!-- Status banner -->
      <div
        :class="isActive ? 'alert alert-success' : 'alert alert-warning'"
        style="display:flex;align-items:center;gap:8px;margin-bottom:16px;"
      >
        <AegisIcon :name="isActive ? 'check-circle' : 'clock'" :size="14" />
        <div>{{ isActive ? 'Agreement active · Both parties signed' : 'Pending countersignature' }}</div>
      </div>

      <!-- HEADER: avatar + name + role + date -->
      <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px;">
        <div style="width:48px;height:48px;border-radius:var(--radius);background:var(--gold-dark);color:var(--text-inverted);font-family:var(--font-serif);font-weight:700;font-size:18px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          {{ initials(stewardName(steward)) }}
        </div>
        <div style="flex:1;min-width:0;">
          <component
            :is="steward.steward?.slug || steward.slug ? 'a' : 'span'"
            :href="(steward.steward?.slug || steward.slug) ? '/cs/' + (steward.steward?.slug ?? steward.slug) : undefined"
            style="font-size:15px;font-weight:700;color:var(--gold-dark);text-decoration:none;font-family:var(--font-serif);"
          >{{ stewardName(steward) }}</component>
          <div style="display:flex;gap:6px;margin-top:4px;flex-wrap:wrap;">
            <AegisBadge :label="csRoleLabel(steward.role)" variant="gold" icon="shield" />
          </div>
        </div>
        <div v-if="steward.signed_at || steward.created_at" style="font-size:12px;color:var(--text-3);text-align:right;flex-shrink:0;">
          <div style="font-weight:600;">Agreement Date</div>
          <div>{{ fmtDate(steward.signed_at ?? steward.created_at) }}</div>
        </div>
      </div>

      <!-- SUMMARY CARDS: 2-col grid -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">

        <!-- Compensation -->
        <div style="background:var(--surface-2);border-radius:var(--radius);padding:12px;">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-3);margin-bottom:8px;">Compensation</div>
          <div v-if="steward.fee_cents" style="font-size:13px;color:var(--text);">
            <strong>{{ formatMoney(steward.fee_cents) }}</strong> per incident
            <div style="font-size:12px;color:var(--text-3);margin-top:4px;">
              {{ paymentTermsLabel }}
              &nbsp;·&nbsp;
              Auto-charge: {{ steward.auto_charge ? 'Yes' : 'No' }}
            </div>
          </div>
          <div v-else style="font-size:13px;color:var(--text-3);">No compensation agreed</div>
        </div>

        <!-- Vault Access -->
        <div style="background:var(--surface-2);border-radius:var(--radius);padding:12px;">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-3);margin-bottom:8px;">Vault Access</div>
          <span
            :class="{
              'badge': true,
              'badge-grey':  !steward.vault_access || steward.vault_access === 'none',
              'badge-gold':  steward.vault_access === 'metadata',
              'badge-green': steward.vault_access === 'scoped' || steward.vault_access === 'full',
            }"
          >
            <AegisIcon name="lock" :size="10" style="margin-right:3px;" />
            <template v-if="!steward.vault_access || steward.vault_access === 'none'">No Vault Access</template>
            <template v-else-if="steward.vault_access === 'metadata'">Metadata Only</template>
            <template v-else-if="steward.vault_access === 'scoped'">Scoped Access</template>
            <template v-else-if="steward.vault_access === 'full'">Full Access</template>
          </span>
        </div>

      </div>

      <!-- Incident Authorization -->
      <div style="margin-bottom:16px;">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-3);margin-bottom:8px;">Incident Authorization</div>
        <div v-if="steward._incidentLabels && steward._incidentLabels.length" style="display:flex;flex-wrap:wrap;gap:6px;">
          <span v-for="label in steward._incidentLabels" :key="label" class="badge badge-gold" style="text-transform:capitalize;">{{ label }}</span>
        </div>
        <span v-else style="font-size:12px;color:var(--text-3);">No specific incident types configured.</span>
      </div>

      <!-- LEGAL DOCUMENT -->
      <div style="background:var(--surface-2);border-radius:var(--radius);padding:18px 22px;font-size:13px;line-height:1.8;color:var(--text-2);">
        <div style="font-family:var(--font-serif);font-size:17px;font-weight:700;color:var(--text);text-align:center;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid var(--border);">Aegis CONTINUITY PLAN</div>

        <p><strong>Section 1. Purpose.</strong> This agreement establishes the designated Continuity Steward for the professional practice in the event of death, incapacitation, disability, voluntary retirement, license suspension, or any other event preventing the Provider from practicing.</p>

        <p v-if="steward.fee_cents">
          <strong>Section 2. Compensation.</strong>
          Provider agrees to pay Continuity Steward {{ formatMoney(steward.fee_cents) }} upon {{ paymentTermsLabel }} following closure of each verified critical incident.
        </p>

        <p>
          <strong>Section 3. Vault Access.</strong>
          Continuity Steward is granted
          <template v-if="steward.vault_access === 'scoped'">Emergency-Only (Scoped)</template>
          <template v-else-if="steward.vault_access === 'full'">Full Read</template>
          <template v-else-if="steward.vault_access === 'metadata'">Metadata-Only</template>
          <template v-else>No</template>
          access to the Provider's document vault.
        </p>

        <p v-if="steward._incidentLabels && steward._incidentLabels.length">
          <strong>Section 4. Authorized Incidents.</strong>
          Continuity Steward is authorized to act on the following verified critical incident types:
          {{ steward._incidentLabels.join(', ') }}.
        </p>
      </div>

    </template>

    <template #footer>
      <button type="button" class="btn btn-outline" @click="close">Close</button>
      <button type="button" class="btn btn-outline" style="display:inline-flex;align-items:center;gap:6px;" @click="downloadPdf">
        <AegisIcon name="download" :size="14" /> Download PDF
      </button>
    </template>
  </AegisModal>
</template>
