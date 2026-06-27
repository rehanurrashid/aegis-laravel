import { useUiStore } from '@/stores/ui'

/*
 * useToast() — replaces showToast() from _shared.js.
 *
 * Usage:
 *   const toast = useToast()
 *   toast.success('Saved.')
 *   toast.error('Could not save changes.')
 */
export function useToast() {
    const ui = useUiStore()

    return {
        showToast: ui.showToast,
        success: (msg, duration) => ui.showToast(msg, 'success', duration),
        error:   (msg, duration) => ui.showToast(msg, 'error',   duration ?? 5000),
        info:    (msg, duration) => ui.showToast(msg, 'info',    duration),
        warning: (msg, duration) => ui.showToast(msg, 'warning', duration),
        dismiss: (id) => ui.dismissToast(id),
    }
}
