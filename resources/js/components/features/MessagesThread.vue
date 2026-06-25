<!--
  MessagesThread.vue — direct-message thread view.

  Replaces the thread render in _shared/templates/messages.php. Used on
  the Messages page in all portals (Provider, CS, SS, BP). Renders a
  scrollable message list + composer; submits via useForm() POST to
  the portal's messages.send route.

  Props:
    thread     — { id, counterpart: { id, display_name, slug, kind } }
    messages   — array of { id, body, sender_id, created_at, read_at? }
    canReply   — bool; pause composer when false (archived thread, etc.)
-->
<template>
  <div class="messages-thread">
    <!-- Header -->
    <header class="messages-thread-header">
      <button
        v-if="$slots.back"
        type="button"
        class="btn btn-ghost btn-sm messages-back-btn"
        @click="$emit('back')"
      >
        <AegisIcon name="chevron-left" :size="14" />
        <slot name="back" />
      </button>
      <div class="messages-thread-party">
        <div class="messages-thread-avatar">{{ initials(counterpart?.display_name) }}</div>
        <div>
          <div class="messages-thread-name">{{ counterpart?.display_name }}</div>
          <a v-if="profileHref !== '#'" :href="profileHref" class="messages-thread-link">
            View profile
            <AegisIcon name="external-link" :size="11" />
          </a>
        </div>
      </div>
    </header>

    <!-- Scrollable list -->
    <div class="messages-list" ref="listEl">
      <div
        v-for="msg in messages"
        :key="msg.id"
        class="message-bubble"
        :class="msg.sender_id === auth.user?.id ? 'message-bubble--mine' : 'message-bubble--theirs'"
      >
        <div class="message-bubble-body">{{ msg.body }}</div>
        <div class="message-bubble-meta">
          <span>{{ activity.timeAgo(msg.created_at) }}</span>
          <AegisIcon
            v-if="msg.sender_id === auth.user?.id && msg.read_at"
            name="check-circle"
            :size="11"
            class="message-bubble-read"
          />
        </div>
      </div>

      <AegisEmptyState
        v-if="!messages.length"
        icon="message"
        title="No messages yet"
        description="Send the first message below."
      />
    </div>

    <!-- Composer -->
    <form class="messages-composer" @submit.prevent="submit">
      <textarea
        v-model="form.body"
        class="messages-composer-input"
        :placeholder="canReply ? 'Write a message…' : 'This thread is read-only.'"
        :disabled="!canReply || form.processing"
        rows="2"
        @keydown.enter.exact.prevent="submit"
      ></textarea>
      <button
        type="submit"
        class="btn btn-primary messages-composer-send"
        :disabled="!canReply || !form.body.trim() || form.processing"
        aria-label="Send"
      >
        <AegisIcon name="send" :size="14" />
        <span>Send</span>
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, watch, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useAuthStore } from '@/stores/auth'
import { useActivity } from '@/composables/useActivity'
import { useProfileRoute } from '@/composables/useProfileRoute'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'

const props = defineProps({
  thread:   { type: Object, required: true },
  messages: { type: Array,  default: () => [] },
  canReply: { type: Boolean, default: true },
})

defineEmits(['back', 'sent'])

const auth     = useAuthStore()
const activity = useActivity()
const { profileHref: profileHrefFor } = useProfileRoute()

const counterpart = computed(() => props.thread?.counterpart)
const profileHref = computed(() =>
  counterpart.value?.slug ? profileHrefFor(counterpart.value.slug, counterpart.value.kind) : '#',
)

const form = useForm({ thread_id: props.thread?.id, body: '' })
const listEl = ref(null)

function initials(name) {
  if (!name) return '·'
  return name.split(/\s+/).map((n) => n[0]).slice(0, 2).join('').toUpperCase()
}

function scrollToBottom() {
  nextTick(() => {
    if (listEl.value) listEl.value.scrollTop = listEl.value.scrollHeight
  })
}

function submit() {
  if (!form.body.trim() || form.processing) return
  form.post(route('messages.send'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('body')
      scrollToBottom()
    },
  })
}

onMounted(scrollToBottom)
watch(() => props.messages.length, scrollToBottom)
</script>
