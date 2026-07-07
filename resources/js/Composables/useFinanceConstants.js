// Konstanta simulasi saving (bukan data kontrak nyata siapa pun — dipakai
// murni sebagai nilai contoh default form/simulator). Data cicilan/BEP yang
// sebenarnya HARUS selalu berasal dari KontrakCicilanEmas milik user yang
// login (lihat aktifKontrak di Dashboard/Target/Info/Grafik/Catat) — jangan
// pernah dijadikan fallback seolah-olah itu data user.
export const DEFAULT_BUDGET = 3000000
export const DEFAULT_TENOR_BULAN = 12
export const DEFAULT_BIAYA_ADMIN = 25000

// Split rata budget (setelah dikurangi cicilan) ke N jenis investasi ber-Rupiah —
// tidak ada lagi persentase tetap per nama kategori (Dana Darurat/Reksa Dana/SBN),
// karena jenis investasi sekarang sepenuhnya custom per user.
export function hitungAlokasiBulanan(budget = DEFAULT_BUDGET, cicilan = 0, rupiahTypeCount = 0) {
    const sisa = Math.max(0, budget - cicilan)
    return {
        sisa,
        perType: rupiahTypeCount > 0 ? Math.round(sisa / rupiahTypeCount) : 0,
    }
}
