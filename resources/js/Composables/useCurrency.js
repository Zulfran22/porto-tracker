import { useSaldoVisibility } from './useSaldoVisibility'

const { isHidden } = useSaldoVisibility()

export function fmt(n) {
    if (isHidden.value) return 'Rp••••••'
    return 'Rp' + Math.round(n ?? 0).toLocaleString('id-ID')
}

export function fmtJt(n) {
    if (isHidden.value) return 'Rp••••'
    return 'Rp' + ((n ?? 0) / 1000000).toFixed(2) + 'jt'
}
