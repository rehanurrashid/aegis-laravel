import { defineStore } from 'pinia'
import { ref } from 'vue'

/*
 * useUiStore — global UI state that should not be ergonomic to thread
 * through props: sidebar collapse, mobile drawer open, modal stack, toasts.
 *
 * Modal management is single-active by design (matches the PHP prototype
 * where openModal() / closeModal() target one overlay at a time).
 */
export const useUiStore = defineStore('ui', () => {
    const sidebarCollapsed = ref(false)
    const mobileOpen       = ref(false)
    const activeModal      = ref(null)
    const toastQueue       = ref([])

    // Single-active confirm dialog — imperative confirmAction() backs into this,
    // rendered once by a global <AegisConfirm> host in AppLayout. Mirrors the
    // PHP prototype's confirmAction(message, fn) from _shared.js.
    const confirmState = ref({
        open:         false,
        title:        'Confirm',
        message:      '',
        confirmLabel: 'Confirm',
        cancelLabel:  'Cancel',
        destructive:  false,
        onConfirm:    null,
    })

    let toastCounter = 0

    function showToast(message, level = 'success', duration = 3000) {
        const id = ++toastCounter
        toastQueue.value.push({ id, message, level })

        if (duration > 0) {
            setTimeout(() => dismissToast(id), duration)
        }
        return id
    }

    function dismissToast(id) {
        toastQueue.value = toastQueue.value.filter((t) => t.id !== id)
    }

    function openModal(id) {
        activeModal.value = id
    }

    function closeModal(id = null) {
        if (id === null || activeModal.value === id) {
            activeModal.value = null
        }
    }

    function toggleSidebar() {
        sidebarCollapsed.value = !sidebarCollapsed.value
    }

    function requestConfirm(opts = {}) {
        confirmState.value = {
            open:         true,
            title:        opts.title        ?? 'Confirm',
            message:      opts.message      ?? '',
            confirmLabel: opts.confirmLabel ?? 'Confirm',
            cancelLabel:  opts.cancelLabel  ?? 'Cancel',
            destructive:  opts.destructive  ?? false,
            onConfirm:    opts.onConfirm    ?? null,
        }
    }

    // Confirm path: close first, then run the stored callback exactly once.
    function resolveConfirm() {
        const cb = confirmState.value.onConfirm
        confirmState.value.open = false
        confirmState.value.onConfirm = null
        if (typeof cb === 'function') cb()
    }

    // Cancel / backdrop / X path: close without running the callback.
    function cancelConfirm() {
        confirmState.value.open = false
        confirmState.value.onConfirm = null
    }

    function setMobileOpen(value) {
        mobileOpen.value = !!value
    }

    return {
        sidebarCollapsed,
        mobileOpen,
        activeModal,
        toastQueue,
        confirmState,
        showToast,
        dismissToast,
        openModal,
        closeModal,
        toggleSidebar,
        requestConfirm,
        resolveConfirm,
        cancelConfirm,
        setMobileOpen,
    }
})
