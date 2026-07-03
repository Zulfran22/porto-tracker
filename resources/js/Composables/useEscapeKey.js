import { onBeforeUnmount, watch } from 'vue'

// Calls `onEscape` when Escape is pressed while `isActive` (a ref/computed) is true.
// Used by every hand-rolled modal/sheet so Escape-to-close works consistently —
// previously only closable by clicking the backdrop.
export function useEscapeKey(isActive, onEscape) {
    function handler(e) {
        if (e.key === 'Escape') onEscape()
    }

    watch(isActive, (active) => {
        if (active) {
            window.addEventListener('keydown', handler)
        } else {
            window.removeEventListener('keydown', handler)
        }
    })

    onBeforeUnmount(() => window.removeEventListener('keydown', handler))
}
