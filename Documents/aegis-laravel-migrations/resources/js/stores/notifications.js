import { defineStore } from 'pinia'
import { usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

/*
 * useNotificationStore — bell badge count + recent activity feed.
 *
 * `unreadCount` is a shared Inertia prop; pages and the header subscribe
 * to it reactively without needing to refetch.
 *
 * `events` holds the rolling activity feed for the header dropdown.
 * Real-time updates arrive via Reverb private channel `incident.{userId}`.
 */
export const useNotificationStore = defineStore('notifications', () => {
    const page = usePage()

    const unreadCount = computed(() => page.props.unreadCount ?? 0)
    const events = ref([])

    function setEvents(newEvents) {
        events.value = Array.isArray(newEvents) ? newEvents : []
    }

    function pushEvent(event) {
        events.value.unshift(event)
        // Cap to 50 in memory; pagination handled server-side.
        if (events.value.length > 50) events.value.length = 50
    }

    function listenForIncident(userId) {
        if (!window.Echo || !userId) return

        window.Echo.private(`incident.${userId}`)
            .listen('IncidentActivated', (e) => {
                pushEvent({
                    id: e.id,
                    title: e.title,
                    description: e.description,
                    severity: 'critical',
                    module: 'incident',
                    created_at: e.created_at,
                    read_at: null,
                })
            })
    }

    function stopListening(userId) {
        if (!window.Echo || !userId) return
        window.Echo.leave(`incident.${userId}`)
    }

    return {
        unreadCount,
        events,
        setEvents,
        pushEvent,
        listenForIncident,
        stopListening,
    }
})
