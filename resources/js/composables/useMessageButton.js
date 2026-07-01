import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

/**
 * Shared composable for every "message this person" button across the app.
 * Calls the findOrCreate endpoint, which finds or creates a direct thread
 * with the given recipient and redirects to /messages?thread=<id>.
 *
 * Usage:
 *   const { openConversation, loading } = useMessageButton()
 *
 *   <button class="btn-icon" data-tooltip="Message"
 *           :disabled="loading === person.id"
 *           @click="openConversation(person.id)">
 *     <AegisIcon name="message" :size="14" />
 *   </button>
 */
export function useMessageButton() {
    const loading = ref(null) // holds recipient_id while request is in-flight

    function openConversation(recipientId) {
        if (!recipientId) return
        loading.value = recipientId

        router.post(
            route('messages.find-or-create'),
            { recipient_id: recipientId },
            {
                preserveState: false,
                onFinish: () => { loading.value = null },
            }
        )
    }

    return { openConversation, loading }
}
