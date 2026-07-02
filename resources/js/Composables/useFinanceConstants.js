// Konstanta bisnis cicilan emas & simulasi saving, dipakai bersama oleh
// Dashboard, Target, dan Info agar tidak ada angka ganda yang bisa saling berbeda.
export const CICILAN = 1032662
export const CICILAN_GRAM = 5
export const BEP = 2861639
export const DUE_DATE_DAY = 4
export const CICILAN_TENOR_END = '2027-06-04' // jatuh tempo kontrak Mulia Tabungan Emas
export const DEFAULT_BUDGET = 3000000
export const DEFAULT_ALLOC = { darurat: 25, emas: 40, reksa: 20, sbn: 15 }

export function hitungAlokasiBulanan(budget = DEFAULT_BUDGET, alloc = DEFAULT_ALLOC) {
    const sisa = Math.max(0, budget - CICILAN)
    return {
        sisa,
        darurat: Math.round(sisa * alloc.darurat / 100),
        emas:    Math.round(sisa * alloc.emas / 100),
        reksa:   Math.round(sisa * alloc.reksa / 100),
        sbn:     Math.round(sisa * alloc.sbn / 100),
    }
}
