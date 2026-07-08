<template>
  <div class="card">
    <div class="card-header">
      <div class="card-title-group">
        <div class="stat-chip-icon" style="width:36px;height:36px;border-radius:var(--radius);background:var(--icon-bg-gold);color:var(--gold-dark)"><AegisIcon name="message-square" :size="16" /></div>
        <div><div class="card-title">Messaging Preferences</div><div class="card-subtitle">{{ subtitle }}</div></div>
      </div>
      <a :href="route(messagesRoute)" class="btn btn-outline btn-sm"><AegisIcon name="message-square" :size="13" /> Open Chat</a>
    </div>
    <div class="card-body">
      <div class="form-row form-row-2" style="margin-bottom:4px">
        <div class="form-group">
          <label class="form-label">Who Can Message Me</label>
          <select class="form-select" v-model="msg.who">
            <option value="assigned">{{ whoOptions[0] }}</option>
            <option value="any">Any verified provider</option>
            <option value="none">No one — pause messages</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">My Status</label>
          <select class="form-select" v-model="msg.status">
            <option value="available">Available</option>
            <option value="busy">Busy</option>
            <option value="away">Away</option>
            <option value="off">Offline</option>
          </select>
        </div>
      </div>
      <div class="toggle-row">
        <div class="toggle-info"><div class="toggle-label">Read Receipts</div><div class="toggle-desc">Show senders when you've read their messages</div></div>
        <button type="button" class="toggle" :class="{ on: msg.readReceipts }" @click="msg.readReceipts = !msg.readReceipts" :aria-pressed="msg.readReceipts"></button>
      </div>
      <div class="toggle-row">
        <div class="toggle-info"><div class="toggle-label">Online Status</div><div class="toggle-desc">Show a presence indicator when you're active on Aegis</div></div>
        <button type="button" class="toggle" :class="{ on: msg.onlineStatus }" @click="msg.onlineStatus = !msg.onlineStatus" :aria-pressed="msg.onlineStatus"></button>
      </div>
      <!-- Extra toggles for specific portals -->
      <slot name="extra-toggles" />
      <div class="form-group" style="margin-top:4px">
        <label class="form-label">Away Message</label>
        <textarea class="form-textarea" rows="3" v-model="msg.awayText" placeholder="e.g. 'I'm currently on leave. For urgent matters please contact…'"></textarea>
      </div>
      <div class="btn-group" style="justify-content:flex-end;margin-top:16px">
        <button type="button" class="btn btn-primary" @click="save"><AegisIcon name="check" :size="13" /> Save</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

const props = defineProps({
  updateRoute:   { type: String, required: true },
  messagesRoute: { type: String, required: true },
  subtitle:      { type: String, default: 'Control who can reach you and how you appear to assigned practitioners' },
  whoOptions:    { type: Array,  default: () => ['Assigned practitioners only'] },
  meta:          { type: Object, default: () => ({}) },
});

const toast = useToast();

const msg = reactive({
  who:          props.meta?.msg_who    || 'assigned',
  status:       props.meta?.msg_status || 'available',
  readReceipts: props.meta?.msg_read_receipts !== '0',
  onlineStatus: props.meta?.msg_online_status !== '0',
  awayText:     props.meta?.msg_away_text     || '',
});

function save() {
  router.put(route(props.updateRoute), msg, {
    preserveScroll: true,
    onSuccess: () => toast.success('Messaging settings saved.'),
    onError:   () => toast.error('Could not save messaging settings.'),
  });
}
</script>
