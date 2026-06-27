import { computed } from 'vue'
import { useUiStore } from '@/stores/ui'

/*
 * useModal() — replaces openModal() / closeModal() from _shared.js.
 *
 * Usage:
 *   const { openModal, closeModal, isOpen } = useModal()
 *   openModal('referralModal')
 *   <AegisModal v-model="isOpen('referralModal').value" ... />
 *
 * Or pair with v-model on AegisModal directly via a local ref.
 */
export function useModal() {
    const ui = useUiStore()

    return {
        openModal:   (id) => ui.openModal(id),
        closeModal:  (id = null) => ui.closeModal(id),
        activeModal: computed(() => ui.activeModal),
        isOpen:      (id) => computed(() => ui.activeModal === id),
    }
}
