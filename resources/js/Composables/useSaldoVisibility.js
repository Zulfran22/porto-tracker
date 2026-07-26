import { ref } from 'vue'

const isHidden = ref(localStorage.getItem('saldoHidden') === 'true')

export function useSaldoVisibility() {
    const toggle = () => {
        isHidden.value = !isHidden.value
        localStorage.setItem('saldoHidden', String(isHidden.value))
    }
    return { isHidden, toggle }
}
