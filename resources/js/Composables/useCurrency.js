export function fmt(n) {
    return 'Rp' + Math.round(n ?? 0).toLocaleString('id-ID')
}

export function fmtJt(n) {
    return 'Rp' + ((n ?? 0) / 1000000).toFixed(2) + 'jt'
}
