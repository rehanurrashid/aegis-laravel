<!--
  pages/business-partner/PaymentSetup.vue — payout method configuration.
-->
<template>
  <AppLayout>
    <AegisHeroBanner
      eyebrow="Money"
      title="Payment Setup"
      subtitle="Where your earnings should be sent."
    />

    <AegisCard title="Payout method">
      <div v-if="method" class="pm-row">
        <AegisIcon :name="method.kind === 'bank' ? 'bank' : 'credit-card'" :size="20" />
        <div>
          <div class="pm-brand">{{ method.label }}</div>
          <div class="pm-exp">{{ method.detail }}</div>
        </div>
        <AegisBadge v-if="method.verified" label="Verified" variant="green" />
        <AegisBadge v-else label="Unverified" variant="gold" />
      </div>
      <AegisEmptyState v-else icon="bank" title="No payout method on file" description="Add a method to receive earnings." />
    </AegisCard>

    <AegisCard title="Add or update payout method">
      <form @submit.prevent="save">
        <div class="form-group">
          <label class="form-label">Method</label>
          <select v-model="form.kind" class="form-input">
            <option value="bank">Bank account (ACH)</option>
            <option value="card">Debit card</option>
            <option value="paypal">PayPal</option>
          </select>
        </div>

        <template v-if="form.kind === 'bank'">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Routing number</label>
              <input v-model="form.routing" class="form-input" />
            </div>
            <div class="form-group">
              <label class="form-label">Account number</label>
              <input v-model="form.account" class="form-input" />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Account holder name</label>
            <input v-model="form.holder" class="form-input" />
          </div>
        </template>

        <template v-else-if="form.kind === 'card'">
          <div class="form-group">
            <label class="form-label">Card number</label>
            <input v-model="form.card_number" class="form-input" />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Expiry</label>
              <input v-model="form.exp" class="form-input" placeholder="MM/YY" />
            </div>
            <div class="form-group">
              <label class="form-label">CVC</label>
              <input v-model="form.cvc" class="form-input" />
            </div>
          </div>
        </template>

        <template v-else>
          <div class="form-group">
            <label class="form-label">PayPal email</label>
            <input v-model="form.paypal_email" type="email" class="form-input" />
          </div>
        </template>

        <div class="form-actions-bar">
          <button type="submit" class="btn btn-primary" :disabled="form.processing">
            {{ form.processing ? 'Saving…' : 'Save method' }}
          </button>
        </div>
      </form>
    </AegisCard>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import { useToast } from '@/composables/useToast'

defineProps({ method: { type: Object, default: null } })
const toast = useToast()

const form = useForm({
  kind: 'bank',
  routing: '', account: '', holder: '',
  card_number: '', exp: '', cvc: '',
  paypal_email: '',
})

function save() {
  form.post(route('bp.payment-setup.store'), {
    preserveScroll: true,
    onSuccess: () => toast.success('Payout method saved.'),
  })
}
</script>
