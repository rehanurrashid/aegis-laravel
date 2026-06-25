import { defineStore } from 'pinia'
import { usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

/*
 * useIncidentStore — surfaces the live critical-incident state.
 *
 * `hasEmergency` is shared from the server (HandleInertiaRequests) so the
 * IncidentBanner and sidebar emergency badge stay in sync across navigations.
 *
 * `activeIncident` is hydrated by pages that fetch it (Continuity Management,
 * Provider Continuity Plan) and consumed by IncidentBanner / Activate flow.
 */
export const useIncidentStore = defineStore('incident', () => {
    const page = usePage()

    const hasEmergency = computed(() => page.props.hasEmergency ?? false)
    const activeIncident = ref(null)

    function setActiveIncident(incident) {
        activeIncident.value = incident
    }

    function clearActiveIncident() {
        activeIncident.value = null
    }

    return {
        hasEmergency,
        activeIncident,
        setActiveIncident,
        clearActiveIncident,
    }
})
