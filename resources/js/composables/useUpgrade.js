import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'

/*
 * useUpgrade() — replaces _showUpgradeModal() from _shared.js.
 *
 * `requiresPractice(fn)` invokes `fn` only when the user is already on the
 * Practice tier; otherwise it opens the upgrade modal. Always pair upgrade
 * CTAs with the modal — NEVER navigate via href (Aegis convention).
 */
export function useUpgrade() {
    const ui   = useUiStore()
    const auth = useAuthStore()

    return {
        openUpgradeModal: () => ui.openModal('upgradeModal'),

        requiresPractice(fn) {
            if (auth.isAccessTier) {
                ui.openModal('upgradeModal')
                return false
            }
            if (typeof fn === 'function') fn()
            return true
        },

        isLocked: () => auth.isAccessTier,
    }
}
