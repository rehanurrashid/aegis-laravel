import { defineStore } from 'pinia'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

/*
 * useAuthStore — wraps Inertia's shared `auth.*` props.
 *
 * Source: HandleInertiaRequests middleware shares `auth.user`, `auth.portal`,
 * `auth.tier`, `auth.roles` on every response. We expose computed accessors
 * plus role/tier predicates so callers never read page.props directly.
 */
export const useAuthStore = defineStore('auth', () => {
    const page = usePage()

    const user               = computed(() => page.props.auth?.user ?? null)
    const portal             = computed(() => page.props.auth?.portal ?? null)
    const tier               = computed(() => page.props.auth?.tier ?? null)
    const roles              = computed(() => page.props.auth?.roles ?? [])
    const availabilityStatus = computed(() => user.value?.messaging_status ?? 'available')

    // Portal predicates — mirror Provider/CS/SS/BP/Admin enum
    const isPractitioner      = computed(() => portal.value === 'provider')
    const isContinuitySteward = computed(() => portal.value === 'continuity_steward')
    const isSupportSteward    = computed(() => portal.value === 'support_steward')
    const isBusinessPartner   = computed(() => portal.value === 'business_partner')
    const isAdmin             = computed(() => portal.value === 'admin')

    // Tier predicates
    const isAccessTier   = computed(() => tier.value === 'access')
    const isPracticeTier = computed(() => tier.value === 'practice')

    // BP sub-type (agency | freelancer)
    const bpType       = computed(() => user.value?.bp_type ?? null)
    const isAgency     = computed(() => bpType.value === 'agency')
    const isFreelancer = computed(() => bpType.value === 'freelancer')

    // Role checks
    function hasRole(role) {
        return roles.value.includes(role)
    }

    return {
        user, portal, tier, roles, bpType,
        isPractitioner, isContinuitySteward, isSupportSteward,
        isBusinessPartner, isAdmin,
        isAccessTier, isPracticeTier,
        isAgency, isFreelancer,
        availabilityStatus,
        hasRole,
    }
})
