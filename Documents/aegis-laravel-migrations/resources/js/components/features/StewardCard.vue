<!--
  StewardCard.vue — designation card for a Continuity or Support Steward.

  Used on the Provider's Continuity Stewards / Support Stewards pages and
  on dashboard summaries. Renders avatar, name, role, status pill, plus
  a row of secondary actions (Message, View profile, Remove).

  Pending invitations render with a softer treatment and resend/cancel
  actions instead.
-->
<template>
  <article class="steward-card" :class="{ 'steward-card--invited': isInvited }">
    <div class="steward-card-avatar">
      <img v-if="steward.avatar_url" :src="steward.avatar_url" :alt="steward.display_name" />
      <span v-else>{{ initials }}</span>
    </div>

    <div class="steward-card-body">
      <div class="steward-card-name-row">
        <span class="steward-card-name">{{ steward.display_name }}</span>
        <AegisBadge
          :label="statusLabel"
          :variant="statusVariant"
        />
      </div>

      <div v-if="steward.headline" class="steward-card-headline">
        {{ steward.headline }}
      </div>

      <div class="steward-card-meta">
        <span class="steward-card-role">{{ roleLabel }}</span>
        <span v-if="steward.email" class="steward-card-email">{{ steward.email }}</span>
      </div>
    </div>

    <div class="steward-card-actions">
      <template v-if="isInvited">
        <button type="button" class="btn btn-sm btn-outline" @click="$emit('resend')">
          <AegisIcon name="send" :size="12" />
          <span>Resend</span>
        </button>
        <button type="button" class="btn btn-sm btn-ghost btn-danger-ghost" @click="$emit('cancel')">
          <AegisIcon name="x" :size="12" />
          <span>Cancel</span>
        </button>
      </template>
      <template v-else>
        <button type="button" class="btn btn-sm btn-outline" @click="$emit('message')">
          <AegisIcon name="message" :size="12" />
          <span>Message</span>
        </button>
        <a v-if="profileHref !== '#'" :href="profileHref" class="btn btn-sm btn-ghost">
          <AegisIcon name="external-link" :size="12" />
          <span>Profile</span>
        </a>
        <button
          v-if="canRemove"
          type="button"
          class="btn btn-sm btn-ghost btn-danger-ghost"
          @click="$emit('remove')"
        >
          <AegisIcon name="trash" :size="12" />
        </button>
      </template>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import { useProfileRoute } from '@/composables/useProfileRoute'

const props = defineProps({
  steward:   { type: Object, required: true },
  kind:      { type: String, default: 'cs', validator: (v) => ['cs','ss'].includes(v) },
  canRemove: { type: Boolean, default: true },
})

defineEmits(['message', 'remove', 'resend', 'cancel'])

const { profileHref: hrefFor } = useProfileRoute()

const isInvited = computed(() => props.steward?.status === 'invited' || props.steward?.invited_at && !props.steward?.accepted_at)

const STATUS_META = {
  active:    { label: 'Active',    variant: 'green' },
  invited:   { label: 'Invited',   variant: 'gold' },
  primary:   { label: 'Primary',   variant: 'gold' },
  pending:   { label: 'Pending',   variant: 'orange' },
  inactive:  { label: 'Inactive',  variant: 'neutral' },
}

const statusLabel = computed(() => {
  const key = props.steward.is_primary ? 'primary' : (props.steward.status ?? 'active')
  return STATUS_META[key]?.label ?? key
})
const statusVariant = computed(() => {
  const key = props.steward.is_primary ? 'primary' : (props.steward.status ?? 'active')
  return STATUS_META[key]?.variant ?? 'neutral'
})

const roleLabel = computed(() =>
  props.kind === 'cs' ? 'Continuity Steward' : 'Support Steward',
)

const initials = computed(() => {
  const n = props.steward?.display_name ?? ''
  return n.split(/\s+/).map((s) => s[0]).slice(0, 2).join('').toUpperCase() || '·'
})

const profileHref = computed(() =>
  props.steward.slug ? hrefFor(props.steward.slug, props.kind) : '#',
)
</script>
