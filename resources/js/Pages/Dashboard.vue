<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import { Progress } from '@/Components/ui/progress'
import { Separator } from '@/Components/ui/separator'
import {
    Bell, AlertTriangle, AlertCircle, CheckCircle2,
    Lock, Coins, TrendingUp, StickyNote,
    Pencil, Trash2, ChevronRight, Wallet,
    ArrowDownCircle, ArrowUpCircle, Download, X
} from 'lucide-vue-next'
import { exportCSV } from '@/Composables/useExport'
import { fmt, fmtJt } from '@/Composables/useCurrency'
import { useEscapeKey } from '@/Composables/useEscapeKey'

const props = defineProps({
    portofolios: Array,
    investmentTypes: { type: Array, default: () => [] },
    aktifKontrak: { type: Object, default: null },
    cicilanPaid: { type: Boolean, default: false },
    cashflow: { type: Object, default: () => ({ income: 0, expense: 0, net: 0 }) },
})

const last = computed(() => props.portofolios?.at(-1) ?? null)
const prev = computed(() => props.portofolios?.at(-2) ?? null)

function findItem(items, name) {
    return items?.find(i => i.type_name === name) ?? null
}
function gramItemOf(items) {
    return items?.find(i => i.unit === 'gram') ?? null
}
const rupiahTypes = computed(() => props.investmentTypes.filter(t => t.unit === 'rupiah'))
const gramType     = computed(() => props.investmentTypes.find(t => t.unit === 'gram') ?? null)

// Semua angka cicilan/BEP hanya berarti kalau user benar-benar punya kontrak
// aktif tercatat — tanpa itu jangan menebak pakai angka siapa pun, tampilkan
// 0 / sembunyikan section terkait (lihat hasKontrak di template).
const hasKontrak      = computed(() => !!props.aktifKontrak)
const cicilanGram     = computed(() => hasKontrak.value ? Number(props.aktifKontrak.total_gram) : 0)
const cicilanBulanan  = computed(() => hasKontrak.value ? Number(props.aktifKontrak.angsuran_bulan) : 0)
const bepTarget       = computed(() => hasKontrak.value ? Number(props.aktifKontrak.bep_per_gram) : 0)

// Total per bulan dihitung di backend (Portofolio::getTotalAttribute) agar satu sumber
// kebenaran dengan gram cicilan dari kontrak aktif — lihat app/Models/Portofolio.php.
const totalLast = computed(() => Number(last.value?.total ?? 0))
const totalPrev = computed(() => Number(prev.value?.total ?? 0))
const diff      = computed(() => totalLast.value - totalPrev.value)

const cashIncome = computed(() => Number(props.cashflow?.income ?? 0))
const cashExpense = computed(() => Number(props.cashflow?.expense ?? 0))
const cashNet = computed(() => Number(props.cashflow?.net ?? 0))
const cashBurnPct = computed(() => cashIncome.value > 0 ? Math.min(100, Math.round(cashExpense.value / cashIncome.value * 100)) : 0)
const cashSavePct = computed(() => cashIncome.value > 0 ? Math.round(cashNet.value / cashIncome.value * 100) : 0)

// Reminder pembayaran cicilan HANYA relevan kalau user benar-benar punya kontrak
// aktif — tanpa itu tidak ada tagihan apa pun yang perlu diingatkan. Begitu
// pembayaran bulan ini tercatat (cicilanPaid dari backend), semua notif hilang.
const today        = new Date()
const todayDate    = today.getDate()
// Bukan selalu 30 hari — Februari/bulan 31-hari bikin hitungan "N hari lagi" meleset kalau dihardcode.
const daysInMonth  = new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate()
const dueDateDay   = computed(() => hasKontrak.value ? new Date(props.aktifKontrak.tanggal_mulai).getDate() : null)
const daysUntilDue = computed(() => dueDateDay.value === null ? null
    : todayDate <= dueDateDay.value ? dueDateDay.value - todayDate : daysInMonth - todayDate + dueDateDay.value)
const belumBayar   = computed(() => hasKontrak.value && !props.cicilanPaid)
const showReminder = computed(() => belumBayar.value && todayDate >= 1 && todayDate <= dueDateDay.value + 2)
const isUrgent     = computed(() => belumBayar.value && todayDate === dueDateDay.value)
const isLate       = computed(() => belumBayar.value && todayDate > dueDateDay.value && todayDate <= dueDateDay.value + 2)

// BEP — hanya berarti kalau ada kontrak aktif dengan bep_per_gram > 0.
const hargaNow = computed(() => last.value ? Number(last.value.harga_emas) : 0)
const bepPct   = computed(() => bepTarget.value > 0 ? Math.min(100, Math.round(hargaNow.value / bepTarget.value * 100)) : 0)

// Ref ke AuthenticatedLayout — dipakai buat memicu modal "Catat" (dalam mode
// edit) dari tombol pensil di riwayat, karena modal sekarang tinggal di layout.
const layoutRef = ref(null)

// Riwayat default hanya bulan berjalan biar dashboard pendek; bulan-bulan lain
// tetap bisa dibuka lewat toggle. Fallback ke entri terakhir kalau bulan
// kalender ini belum dicatat, supaya section tidak kosong.
const bulanBerjalan = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`
const showAllRiwayat = ref(false)
const riwayatTampil = computed(() => {
    const semua = [...(props.portofolios ?? [])].reverse()
    if (showAllRiwayat.value) return semua
    const bulanIni = semua.filter(p => p.bulan === bulanBerjalan)
    return bulanIni.length ? bulanIni : semua.slice(0, 1)
})

// Delete modal
const deleteTarget = ref(null)
const confirmHapus = (item) => { deleteTarget.value = item }
const batalHapus   = () => { deleteTarget.value = null }
useEscapeKey(deleteTarget, batalHapus)
const hapus = () => {
    if (!deleteTarget.value) return
    router.delete(route('portofolio.destroy', deleteTarget.value.id), {
        onFinish: () => { deleteTarget.value = null }
    })
}

// Export CSV — kolom dibangun dinamis dari investmentTypes, bukan daftar tetap.
const exportPortofolio = () => {
    const headers = ['Bulan', `${gramType.value?.name ?? 'Emas'} (gram)`, 'Harga Emas',
        ...rupiahTypes.value.map(t => t.name), 'Total', 'Catatan']

    exportCSV('portofolio.csv', headers, props.portofolios.map(p => [
        p.bulan,
        gramItemOf(p.items)?.gram ?? 0,
        p.harga_emas ?? '',
        ...rupiahTypes.value.map(t => findItem(p.items, t.name)?.jumlah ?? 0),
        Math.round(p.total),
        p.catatan ?? '',
    ]))
}
</script>

<template>
    <AuthenticatedLayout ref="layoutRef">
        <div class="max-w-lg mx-auto lg:max-w-3xl px-4 py-5 lg:py-8 space-y-3">

            <!-- NOTIFIKASI -->
<Card v-if="isLate" class="border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/40">
    <CardContent class="p-4 flex gap-3 items-start">
        <AlertCircle :size="22" class="text-red-600 dark:text-red-400 shrink-0 mt-0.5"/>
        <div class="flex-1">
            <p class="text-sm font-semibold text-red-600 dark:text-red-400">Telat bayar cicilan!</p>
            <p class="text-xs text-red-500 dark:text-red-300/70 mt-0.5">Segera bayar <strong>{{ fmt(cicilanBulanan) }}</strong> — hindari denda!</p>
            <a :href="route('portofolio.create')"
                class="mt-2.5 inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-500 text-white transition-colors">
                <CheckCircle2 :size="13"/> Catat pembayaran
            </a>
        </div>
    </CardContent>
</Card>
<Card v-else-if="isUrgent" class="border-orange-200 dark:border-orange-700 bg-orange-50 dark:bg-orange-950/40">
    <CardContent class="p-4 flex gap-3 items-start">
        <AlertTriangle :size="22" class="text-orange-600 dark:text-orange-400 shrink-0 mt-0.5"/>
        <div class="flex-1">
            <p class="text-sm font-semibold text-orange-600 dark:text-orange-400">HARI INI jatuh tempo!</p>
            <p class="text-xs text-orange-500 dark:text-orange-300/70 mt-0.5">Bayar <strong>{{ fmt(cicilanBulanan) }}</strong> sekarang!</p>
            <a :href="route('portofolio.create')"
                class="mt-2.5 inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg bg-orange-600 hover:bg-orange-500 text-white transition-colors">
                <CheckCircle2 :size="13"/> Catat pembayaran
            </a>
        </div>
    </CardContent>
</Card>
<Card v-else-if="showReminder" class="border-yellow-200 dark:border-yellow-700/50 bg-yellow-50 dark:bg-yellow-950/30">
    <CardContent class="p-4 flex gap-3 items-start">
        <Bell :size="22" class="text-yellow-600 dark:text-yellow-400 shrink-0 mt-0.5"/>
        <div>
            <p class="text-sm font-semibold text-yellow-700 dark:text-yellow-400">Reminder cicilan emas</p>
            <p class="text-xs text-yellow-600 dark:text-yellow-300/70 mt-0.5">Tanggal {{ String(dueDateDay).padStart(2, '0') }} — <strong>{{ daysUntilDue }} hari lagi</strong>. Siapkan {{ fmt(cicilanBulanan) }}</p>
        </div>
    </CardContent>
</Card>

            <!-- EMPTY STATE -->
            <div v-if="!last" class="text-center py-16">
                <div class="text-6xl mb-4">🏦</div>
                <p class="text-lg font-semibold text-zinc-700 dark:text-zinc-200 mb-2">Belum ada data</p>
                <p class="text-sm text-zinc-500 mb-6">Catat bulan pertama untuk mulai tracking</p>
                <a :href="route('portofolio.create')"
                   class="bg-indigo-500 text-white px-6 py-2.5 rounded-xl font-medium text-sm hover:bg-indigo-400 active:bg-indigo-600 transition-colors">
                    Catat sekarang
                </a>
            </div>

            <!-- TOTAL PORTOFOLIO -->
            <Card v-if="last" class="border-indigo-300/60 dark:border-indigo-700/40 bg-gradient-to-br from-indigo-50 to-white dark:from-indigo-950/40 dark:to-zinc-900">
                <CardContent class="p-5">
                    <p class="text-xs text-indigo-600 dark:text-indigo-400 uppercase tracking-widest font-medium mb-1">Total portofolio</p>
                    <p class="text-4xl font-bold text-zinc-900 dark:text-white tracking-tight mb-1">{{ fmtJt(totalLast) }}</p>
                    <div v-if="prev" class="flex items-center gap-1.5 mt-1">
                        <Badge :class="diff >= 0
                            ? 'bg-green-100 text-green-700 border-green-300 dark:bg-green-900 dark:text-green-400 dark:border-green-700'
                            : 'bg-red-100 text-red-700 border-red-300 dark:bg-red-900 dark:text-red-400 dark:border-red-700'"
                               class="text-xs border">
                            {{ diff >= 0 ? '▲' : '▼' }} {{ fmt(Math.abs(diff)) }}
                        </Badge>
                        <span class="text-xs text-zinc-500">dari bulan lalu</span>
                    </div>
                    <p v-else class="text-xs text-zinc-500 mt-1">Bulan pertama — terus semangat! 💪</p>
                </CardContent>
            </Card>

            <!-- CASHFLOW SUMMARY -->
            <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                <CardHeader class="pb-2 pt-4 px-4">
                    <div class="flex items-center justify-between gap-3">
                        <CardTitle class="text-xs text-zinc-500 uppercase tracking-widest flex items-center gap-1.5">
                            <Wallet :size="12"/> Cashflow bulan ini
                        </CardTitle>
                        <div class="flex items-center gap-2">
                            <Badge class="border text-xs"
                                :class="cashBurnPct > 90
                                    ? 'bg-red-100 text-red-700 border-red-300 dark:bg-red-900 dark:text-red-400 dark:border-red-700'
                                    : cashBurnPct > 70
                                    ? 'bg-orange-100 text-orange-700 border-orange-300 dark:bg-orange-900 dark:text-orange-400 dark:border-orange-700'
                                    : 'bg-green-100 text-green-700 border-green-300 dark:bg-green-900 dark:text-green-400 dark:border-green-700'">
                                {{ cashBurnPct > 90 ? 'Kritis' : cashBurnPct > 70 ? 'Waspada' : 'Sehat' }}
                            </Badge>
                            <Badge class="border text-xs"
                                :class="cashNet >= 0
                                    ? 'bg-green-100 text-green-700 border-green-300 dark:bg-green-900 dark:text-green-400 dark:border-green-700'
                                    : 'bg-red-100 text-red-700 border-red-300 dark:bg-red-900 dark:text-red-400 dark:border-red-700'">
                                {{ cashNet >= 0 ? '+' : '' }}{{ fmt(cashNet) }}
                            </Badge>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="px-4 pb-4 space-y-3">
                    <div class="grid grid-cols-2 gap-2">
                        <div class="rounded-xl bg-green-50 dark:bg-green-950/30 border border-green-100 dark:border-green-900/50 p-3">
                            <p class="text-xs text-green-700 dark:text-green-400 mb-1 flex items-center gap-1.5">
                                <ArrowUpCircle :size="12"/> Pemasukan
                            </p>
                            <p class="text-base font-semibold text-green-700 dark:text-green-400">{{ fmt(cashIncome) }}</p>
                        </div>
                        <div class="rounded-xl bg-red-50 dark:bg-red-950/30 border border-red-100 dark:border-red-900/50 p-3">
                            <p class="text-xs text-red-700 dark:text-red-400 mb-1 flex items-center gap-1.5">
                                <ArrowDownCircle :size="12"/> Pengeluaran
                            </p>
                            <p class="text-base font-semibold text-red-700 dark:text-red-400">{{ fmt(cashExpense) }}</p>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between text-xs text-zinc-500 mb-1.5">
                            <span>Pemakaian dari pemasukan</span>
                            <span :class="cashBurnPct > 90 ? 'text-red-500 font-semibold' : cashBurnPct > 70 ? 'text-orange-500 font-semibold' : ''">{{ cashBurnPct }}%</span>
                        </div>
                        <div class="h-2 rounded-full overflow-hidden bg-zinc-200 dark:bg-zinc-800">
                            <div class="h-full rounded-full transition-all"
                                :style="{ width: cashBurnPct + '%' }"
                                :class="cashBurnPct > 90 ? 'bg-red-500' : cashBurnPct > 70 ? 'bg-orange-400' : 'bg-green-500'">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <span class="text-zinc-500">Saving rate</span>
                        <span class="font-semibold" :class="cashSavePct >= 20 ? 'text-green-600 dark:text-green-400' : cashSavePct >= 0 ? 'text-orange-500 dark:text-orange-400' : 'text-red-600 dark:text-red-400'">
                            {{ cashSavePct >= 0 ? '+' : '' }}{{ cashSavePct }}%
                        </span>
                    </div>

                    <a :href="route('keuangan.index')"
                        class="flex items-center justify-between text-xs text-zinc-400 hover:text-indigo-500 dark:hover:text-indigo-400 transition-colors pt-1 border-t border-zinc-100 dark:border-zinc-800">
                        <span>Lihat detail keuangan</span>
                        <ChevronRight :size="13"/>
                    </a>
                </CardContent>
            </Card>

            <template v-if="last">
                <!-- RINCIAN ASET -->
                <div>
                    <p class="text-xs text-zinc-500 uppercase tracking-widest font-medium mb-3">Rincian aset</p>
                    <div class="grid grid-cols-2 gap-2">
                    <Card v-if="gramItemOf(last.items)" class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                        <CardContent class="p-3">
                            <p class="text-xs text-zinc-500 mb-1">Total emas</p>
                            <p class="text-lg font-semibold text-yellow-500 dark:text-yellow-400">{{ (Number(gramItemOf(last.items).gram) + cicilanGram).toFixed(2) }}g</p>
                            <p class="text-xs text-zinc-400 mt-0.5">
                                <template v-if="hasKontrak">Termasuk {{ cicilanGram }}g dalam kontrak cicilan aktif + {{ gramItemOf(last.items).gram }}g tunai</template>
                                <template v-else>{{ gramItemOf(last.items).gram }}g tunai</template>
                            </p>
                        </CardContent>
                    </Card>
                    <Card v-if="gramItemOf(last.items)" class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                        <CardContent class="p-3">
                            <p class="text-xs text-zinc-500 mb-1">Harga emas</p>
                            <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ fmt(last.harga_emas) }}</p>
                            <p class="text-xs text-zinc-400 mt-0.5">per gram</p>
                        </CardContent>
                    </Card>
                    <Card v-for="t in rupiahTypes.slice(0, 3)" :key="t.id" class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                        <CardContent class="p-3">
                            <p class="text-xs text-zinc-500 mb-1 truncate">{{ t.name }}</p>
                            <p class="text-lg font-semibold text-blue-500 dark:text-blue-400">{{ fmt(findItem(last.items, t.name)?.jumlah ?? 0) }}</p>
                        </CardContent>
                    </Card>
                    </div>
                </div>

                <!-- BEP PROGRESS -->
                <Card v-if="hasKontrak" class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                    <CardContent class="p-4">
                        <div class="flex justify-between items-center mb-2">
                            <p class="text-xs text-zinc-500 uppercase tracking-widest">Progress BEP cicilan</p>
                            <Badge class="bg-yellow-100 text-yellow-700 border-yellow-300 dark:bg-yellow-900 dark:text-yellow-400 dark:border-yellow-700 border text-xs">{{ bepPct }}%</Badge>
                        </div>
                        <Progress :model-value="bepPct" class="h-2 bg-zinc-200 dark:bg-zinc-800 mb-3" indicator-class="bg-gradient-to-r from-yellow-600 to-yellow-400"/>
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div>
                                <p class="text-xs text-zinc-500">Sekarang</p>
                                <p class="text-xs font-medium text-zinc-900 dark:text-white mt-0.5">{{ fmt(hargaNow) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-500">Target BEP</p>
                                <p class="text-xs font-medium text-red-500 dark:text-red-400 mt-0.5">{{ fmt(bepTarget) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-500">Kurang</p>
                                <p class="text-xs font-medium text-orange-500 dark:text-orange-400 mt-0.5">{{ fmt(Math.max(0, bepTarget - hargaNow)) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- ALOKASI -->
                <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                    <CardHeader class="pb-2 pt-4 px-4">
                        <CardTitle class="text-xs text-zinc-500 uppercase tracking-widest font-medium">Alokasi bulan berjalan · {{ last.bulan }}</CardTitle>
                    </CardHeader>
                    <CardContent class="px-4 pb-4 space-y-2.5">
    <div v-if="hasKontrak" class="flex justify-between text-sm items-center">
        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Lock :size="13" class="text-yellow-600"/>Cicilan {{ cicilanGram }} gram</span>
        <span class="text-yellow-600 font-medium">{{ fmt(cicilanGram * last.harga_emas) }}</span>
    </div>
    <div v-if="gramItemOf(last.items)" class="flex justify-between text-sm items-center">
        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Coins :size="13" class="text-yellow-500 dark:text-yellow-400"/>{{ gramItemOf(last.items).type_name }} {{ gramItemOf(last.items).gram }}g</span>
        <span class="text-yellow-500 dark:text-yellow-400 font-medium">{{ fmt(gramItemOf(last.items).gram * last.harga_emas) }}</span>
    </div>
    <div v-for="t in rupiahTypes" :key="t.id" class="flex justify-between text-sm items-center">
        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><TrendingUp :size="13" class="text-green-500 dark:text-green-400"/>{{ t.name }}</span>
        <span class="text-green-500 dark:text-green-400 font-medium">{{ fmt(findItem(last.items, t.name)?.jumlah ?? 0) }}</span>
    </div>
    <Separator class="bg-zinc-200 dark:bg-zinc-700"/>
    <div class="flex justify-between font-semibold">
        <span class="text-zinc-600 dark:text-zinc-300">Total</span>
        <span class="text-zinc-900 dark:text-white">{{ fmtJt(totalLast) }}</span>
    </div>
    <p v-if="last.catatan" class="text-xs text-zinc-500 flex items-center gap-1.5 pt-1">
        <StickyNote :size="12"/>{{ last.catatan }}
    </p>
</CardContent>
</Card>

                <!-- RIWAYAT -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs text-zinc-500 uppercase tracking-widest font-medium">{{ showAllRiwayat ? 'Riwayat semua bulan' : 'Riwayat bulan berjalan' }}</p>
                        <div class="flex items-center gap-2">
                            <button v-if="portofolios.length > 1" @click="showAllRiwayat = !showAllRiwayat"
                                class="text-xs px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 text-zinc-500 dark:text-zinc-400 hover:border-indigo-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                {{ showAllRiwayat ? 'Bulan berjalan' : `Semua bulan (${portofolios.length})` }}
                            </button>
                            <button @click="exportPortofolio"
                                class="flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 text-zinc-500 dark:text-zinc-400 hover:border-indigo-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                <Download :size="12"/> Export CSV
                            </button>
                        </div>
                    </div>
                    <div v-for="item in riwayatTampil" :key="item.id" class="mb-2">
                        <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 hover:border-zinc-300 dark:hover:border-zinc-700 transition-colors">
                            <CardContent class="p-4">
                                <div class="flex justify-between items-center mb-3">
                                    <span class="font-semibold text-zinc-900 dark:text-white">{{ item.bulan }}</span>
                                    <div class="flex items-center gap-2">
    <span class="text-indigo-500 dark:text-indigo-400 font-semibold text-sm">{{ fmtJt(item.total) }}</span>
    <button @click="layoutRef?.openCatat(item.id)" :aria-label="`Edit data ${item.bulan}`"
        class="p-1.5 rounded-lg border border-blue-300 dark:border-blue-800 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950 transition-colors">
        <Pencil :size="13"/>
    </button>
    <button @click="confirmHapus(item)" :aria-label="`Hapus data ${item.bulan}`"
        class="p-1.5 rounded-lg border border-red-300 dark:border-red-900 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950 transition-colors">
        <Trash2 :size="13"/>
    </button>
</div>

                                </div>
                                <div class="space-y-1.5 text-xs text-zinc-500">
                                    <div v-if="gramItemOf(item.items)" class="flex justify-between">
                                        <span>{{ gramItemOf(item.items).type_name }}</span>
                                        <span class="text-yellow-500 dark:text-yellow-500">{{ gramItemOf(item.items).gram }}g · {{ fmt(gramItemOf(item.items).gram * item.harga_emas) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Harga/gram</span>
                                        <span class="text-zinc-600 dark:text-zinc-400">{{ fmt(item.harga_emas) }}</span>
                                    </div>
                                    <div v-for="t in rupiahTypes" :key="t.id" class="flex justify-between">
                                        <span>{{ t.name }}</span>
                                        <span class="text-blue-500 dark:text-blue-400">{{ fmt(findItem(item.items, t.name)?.jumlah ?? 0) }}</span>
                                    </div>
                                    <p v-if="item.catatan" class="text-zinc-400 pt-1">📝 {{ item.catatan }}</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

            </template>
        </div>
    </AuthenticatedLayout>

    <!-- DELETE MODAL -->
    <Teleport to="body">
        <Transition enter-active-class="transition" enter-from-class="opacity-0" leave-active-class="transition" leave-to-class="opacity-0">
            <div v-if="deleteTarget" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="batalHapus">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"/>
                <div class="relative w-full max-w-sm bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-zinc-200 dark:border-zinc-800 p-6">
                    <div class="flex items-start gap-3 mb-5">
                        <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center shrink-0">
                            <Trash2 :size="18" class="text-red-600 dark:text-red-400"/>
                        </div>
                        <div class="flex-1">
                            <h3 id="delete-modal-title" class="font-semibold text-zinc-900 dark:text-white text-sm">Hapus data {{ deleteTarget?.bulan }}?</h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Data portofolio bulan ini akan dihapus permanen dan tidak bisa dikembalikan.</p>
                        </div>
                        <button @click="batalHapus" aria-label="Tutup" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors shrink-0">
                            <X :size="16"/>
                        </button>
                    </div>
                    <div class="flex gap-2">
                        <button @click="batalHapus"
                            class="flex-1 py-2.5 rounded-xl text-sm font-medium border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                            Batal
                        </button>
                        <button @click="hapus"
                            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 rounded-xl text-sm font-semibold bg-red-600 hover:bg-red-500 text-white transition-colors">
                            <Trash2 :size="14"/> Hapus
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
