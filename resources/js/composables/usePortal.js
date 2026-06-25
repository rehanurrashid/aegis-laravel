import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'

/*
 * usePortal() — portal/role-aware helpers.
 *
 * `requiresTier(tier, fn?)` is the tier gate: if the user is on a lower tier,
 * the upgrade modal opens and the gated function is NOT invoked. Returns
 * truthy on pass so callers can also branch directly:
 *
 *   if (!portal.requiresTier('practice')) return
 *   doThing()
 *
 * Or with the callback form:
 *
 *   portal.requiresTier('practice', () => doThing())
 */
export function usePortal() {
    const auth = useAuthStore()
    const ui   = useUiStore()

    function requiresTier(tier, fn = null) {
        if (tier === 'practice' && auth.isAccessTier) {
            ui.openModal('upgradeModal')
            return false
        }
        if (typeof fn === 'function') fn()
        return true
    }

    return {
        portal: auth.portal,
        isPractitioner: auth.isPractitioner,
        isCS:           auth.isContinuitySteward,
        isSS:           auth.isSupportSteward,
        isBP:           auth.isBusinessPartner,
        isAdmin:        auth.isAdmin,
        isAccessTier:   auth.isAccessTier,
        isPracticeTier: auth.isPracticeTier,
        bpType:    auth.bpType,
        isAgency:  auth.isAgency,
        isFreelancer: auth.isFreelancer,
        requiresTier,
    }
}
