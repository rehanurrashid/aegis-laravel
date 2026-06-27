import { useUiStore } from '@/stores/ui'

/*
 * useConfirm() — replaces confirmAction(message, fn) from _shared.js.
 *
 * Imperative confirm dialog, called inline from event handlers. Backs into a
 * single-active confirm state on the ui store, rendered once by the global
 * <AegisConfirm> host in AppLayout — so pages never wire their own modal
 * state for a simple "are you sure?".
 *
 * Two call shapes:
 *
 *   const { confirmAction } = useConfirm()
 *
 *   // 1. terse — matches the PHP prototype exactly
 *   confirmAction('Remove this steward?', () => form.delete(route('...')))
 *
 *   // 2. full — title, button label, destructive styling
 *   confirmAction(
 *     {
 *       title:        'Remove Continuity Steward?',
 *       message:      'Marcus will lose access immediately. This cannot be undone.',
 *       confirmLabel: 'Remove',
 *       destructive:  true,
 *     },
 *     () => form.delete(route('provider.stewards.destroy', id)),
 *   )
 */
export function useConfirm() {
    const ui = useUiStore()

    function confirmAction(arg, onConfirm) {
        const opts = typeof arg === 'string' ? { message: arg } : { ...(arg ?? {}) }
        if (onConfirm) opts.onConfirm = onConfirm
        ui.requestConfirm(opts)
    }

    return { confirmAction }
}
