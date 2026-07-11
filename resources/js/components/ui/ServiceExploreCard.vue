<!--
  ServiceExploreCard.vue — grid card for the Explore Services tab.

  Displays a single public service from another practitioner.
  Used inside the explore grid in Services.vue.

  Props:
    service — shaped object from shapeForExplore()

  Emits:
    request — user clicks "Request" button (parent opens ServiceRequestModal)

  No Inertia, no axios — pure display + emit.
  AegisBadge, AegisIcon globally registered.
-->
<template>
  <div class="explore-card" @click="$emit('request')">

    <!-- Top row: type icon + category badge + availability -->
    <div class="ec-top">
      <div class="ec-type-icon">
        <AegisIcon :name="typeIcon" :size="18" />
      </div>
      <AegisBadge :label="service.service_type" variant="gold" />
      <AegisBadge
        :label="availabilityLabel"
        :variant="service.availability === 'limited' ? 'gold' : 'green'"
      />
    </div>

    <!-- Title -->
    <div class="ec-title">{{ service.title }}</div>

    <!-- Description -->
    <div v-if="service.description" class="ec-desc">{{ service.description }}</div>

    <!-- Format + duration pills -->
    <div class="ec-pills">
      <span v-if="service.duration_min" class="ec-pill">
        <AegisIcon name="clock" :size="11" />
        {{ service.duration_min }} min
      </span>
      <span v-if="service.format" class="ec-pill">
        <AegisIcon name="monitor" :size="11" />
        {{ formatLabel }}
      </span>
    </div>

    <!-- Divider -->
    <div class="ec-divider"></div>

    <!-- Provider row -->
    <div class="ec-provider">
      <div class="ec-provider-avatar">{{ service.practitioner_avatar || '?' }}</div>
      <div class="ec-provider-info">
        <a
          v-if="service.practitioner_slug"
          :href="`/public/provider/${service.practitioner_slug}`"
          class="ec-provider-name link-name"
          @click.stop
        >{{ service.practitioner_name }}</a>
        <div v-else class="ec-provider-name">{{ service.practitioner_name }}</div>
        <div v-if="service.practitioner_credentials" class="ec-provider-cred">
          {{ service.practitioner_credentials }}
        </div>
      </div>
      <span
        class="ec-connect-dot"
        :class="service.practitioner_connected ? 'is-connected' : ''"
        :data-tooltip="service.practitioner_connected ? 'Stripe Connected — instant payout' : 'Payout queued until provider connects Stripe'"
      ></span>
    </div>

    <!-- Footer: price + request button -->
    <div class="ec-footer" @click.stop>
      <div class="ec-price">
        <span class="ec-price-amount">{{ service.price }}</span>
        <span v-if="service.price_unit" class="ec-price-unit">{{ service.price_unit }}</span>
      </div>
      <button type="button" class="btn btn-primary" @click.stop="$emit('request')">
        <AegisIcon name="send" :size="12" />
        Request
      </button>
    </div>

  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  service: { type: Object, required: true },
})

defineEmits(['request'])

const typeIcon = computed(() => {
  const map = {
    supervision:          'graduation-cap',
    consultation:         'message',
    training:             'book-open',
    coaching:             'leaf',
    practice_continuity:  'shield',
  }
  return map[props.service.category] ?? 'briefcase'
})

const availabilityLabel = computed(() => {
  if (props.service.availability_label) return props.service.availability_label
  return props.service.availability === 'limited' ? 'Limited Spots' : 'Available'
})

const formatLabel = computed(() => {
  return {
    telehealth: 'Virtual',
    in_person:  'In-Person',
    both:       'Virtual or In-Person',
  }[props.service.format] ?? props.service.format
})
</script>

<style scoped>
.explore-card {
  display: flex;
  flex-direction: column;
  gap: 10px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 16px;
  cursor: pointer;
  transition: box-shadow var(--transition), border-color var(--transition);
}
.explore-card:hover {
  box-shadow: var(--shadow-md);
  border-color: var(--border-dark);
}

.ec-top { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.ec-type-icon {
  width: 32px; height: 32px; border-radius: var(--radius-sm);
  display: flex; align-items: center; justify-content: center;
  background: var(--badge-bg-gold); color: var(--gold-dark);
  flex-shrink: 0;
}

.ec-title {
  font-family: var(--font-serif, serif);
  font-size: 15px; font-weight: 700; color: var(--text);
  line-height: 1.3;
}

.ec-desc {
  font-size: 12.5px; color: var(--text-2); line-height: 1.55;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.ec-pills { display: flex; flex-wrap: wrap; gap: 6px; }
.ec-pill {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 11px; font-weight: 600; color: var(--text-3);
  background: var(--surface-2); border: 1px solid var(--border);
  padding: 2px 8px; border-radius: 100px;
}

.ec-divider { height: 1px; background: var(--border); }

.ec-provider { display: flex; align-items: center; gap: 8px; }
.ec-provider-avatar {
  width: 28px; height: 28px; border-radius: 50%;
  background: var(--surface-3); color: var(--text-3);
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700; flex-shrink: 0;
}
.ec-provider-info { flex: 1; min-width: 0; }
.ec-provider-name { font-size: 12.5px; font-weight: 700; color: var(--text); }
.link-name { color: var(--gold-dark); text-decoration: none; }
.link-name:hover { text-decoration: underline; }
.ec-provider-cred { font-size: 11px; color: var(--text-4); font-weight: 600; }
.ec-connect-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: var(--border-dark); flex-shrink: 0;
  transition: background var(--transition);
}
.ec-connect-dot.is-connected { background: var(--green); }

.ec-footer { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.ec-price { display: flex; align-items: baseline; gap: 4px; }
.ec-price-amount { font-family: var(--font-serif, serif); font-size: 20px; font-weight: 700; color: var(--text); }
.ec-price-unit { font-size: 11px; color: var(--text-3); font-weight: 600; }
</style>
