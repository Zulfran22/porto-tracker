<script setup>
import { computed, ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import { Progress } from '@/Components/ui/progress'
import { Separator } from '@/Components/ui/separator'
import {
    Bell, AlertTriangle, AlertCircle,
    Lock, Coins, Shield, TrendingUp, Landmark, StickyNote,
    Pencil, Trash2, ChevronRight, Calendar, Wallet,
    ArrowDownCircle, ArrowUpCircle, Download, X
} from 'lucide-vue-next'
import { exportCSV } from '@/Composables/useExport'


const props = defineProps({
    portofolios: Array,
    cashflow: { type: Object, default: () => ({ income: 0, expense: 0, net: 0 }) },
})

const last = computed(() => props.portofolios?.at(-1) ?? null)
const prev = computed(() => props.portofolios?.at(-2) ?? null)

const fmt   = (n) => 'Rp' + Math.round(n).toLocaleString('id-ID')
const fmtJt = (n) => 'Rp' + (n / 1000000).toFixed(2) + 'jt'

const total = (e) => {
    if (!e) return 0
    return (Number(e.emas_gram) * Number(e.harga_emas)) +
           (5 * Number(e.harga_emas)) +
           Number(e.dana_darurat) +
           Number(e.reksa_dana) +
           Number(e.sbn)
}

const totalLast = computed(() => total(last.value))
const totalPrev = computed(() => total(prev.value))
const diff      = computed(() => totalLast.value - totalPrev.value)

const cashIncome = computed(() => Number(props.cashflow?.income ?? 0))
const cashExpense = computed(() => Number(props.cashflow?.expense ?? 0))
const cashNet = computed(() => Number(props.cashflow?.net ?? 0))
const cashBurnPct = computed(() => cashIncome.value > 0 ? Math.min(100, Math.round(cashExpense.value / cashIncome.value * 100)) : 0)
const cashSavePct = computed(() => cashIncome.value > 0 ? Math.round(cashNet.value / cashIncome.value * 100) : 0)

// Slider simulasi
const budget = ref(3000000)
const pDD    = ref(25)
const pEM    = ref(40)
const pRD    = ref(20)
const pSB    = ref(15)
const tahun  = ref(5)

const CICILAN  = 1032662
const sisa     = computed(() => Math.max(0, budget.value - CICILAN))
const mDD      = computed(() => Math.round(sisa.value * pDD.value / 100))
const mEM      = computed(() => Math.round(sisa.value * pEM.value / 100))
const mRD      = computed(() => Math.round(sisa.value * pRD.value / 100))
const mSB      = computed(() => Math.round(sisa.value * pSB.value / 100))
const totalPct = computed(() => pDD.value + pEM.value + pRD.value + pSB.value)

function fv(monthly, rate, months) {
    const r = rate / 12
    return r === 0 ? monthly * months : monthly * ((Math.pow(1+r, months)-1)/r)
}

const months     = computed(() => tahun.value * 12)
const nilaiAkhir = computed(() =>
    fv(mDD.value,.05,months.value) + fv(mEM.value,.10,months.value) +
    fv(mRD.value,.11,months.value) + fv(mSB.value,.065,months.value)
)
const modalTotal  = computed(() => (mDD.value+mEM.value+mRD.value+mSB.value)*months.value)
const keuntungan  = computed(() => nilaiAkhir.value - modalTotal.value)

// Reminder
const today       = new Date()
const todayDate   = today.getDate()
const daysUntil04 = todayDate <= 4 ? 4 - todayDate : 30 - todayDate + 4
const showReminder = computed(() => todayDate >= 1 && todayDate <= 6)
const isUrgent     = computed(() => todayDate === 4)
const isLate       = computed(() => todayDate > 4 && todayDate <= 6)

// BEP
const BEP     = 2861639
const hargaNow = computed(() => last.value ? Number(last.value.harga_emas) : 0)
const bepPct   = computed(() => Math.min(100, Math.round(hargaNow.value / BEP * 100)))

// Delete modal
const deleteTarget = ref(null)
const confirmHapus = (item) => { deleteTarget.value = item }
const batalHapus   = () => { deleteTarget.value = null }
const hapus = () => {
    if (!deleteTarget.value) return
    router.delete(route('portofolio.destroy', deleteTarget.value.id), {
        onFinish: () => { deleteTarget.value = null }
    })
}

// Export CSV
const exportPortofolio = () => {
    exportCSV('portofolio.csv',
        ['Bulan', 'Emas Gram (tunai)', 'Harga Emas', 'Dana Darurat', 'Reksa Dana', 'SBN', 'Total', 'Catatan'],
        props.portofolios.map(e => [
            e.bulan, e.emas_gram, e.harga_emas, e.dana_darurat, e.reksa_dana, e.sbn,
            Math.round(total(e)), e.catatan ?? ''
        ])
    )
}
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-lg mx-auto lg:max-w-3xl px-4 py-5 lg:py-8 space-y-3">

            <!-- REMINDER -->
<Card v-if="isLate" class="border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/40">
    <CardContent class="p-4 flex gap-3 items-start">
        <AlertCircle :size="22" class="text-red-600 dark:text-red-400 shrink-0 mt-0.5"/>
        <div>
            <p class="text-sm font-semibold text-red-600 dark:text-red-400">Telat bayar cicilan!</p>
            <p class="text-xs text-red-500 dark:text-red-300/70 mt-0.5">Segera bayar <strong>{{ fmt(CICILAN) }}</strong> — hindari denda!</p>
        </div>
    </CardContent>
</Card>
<Card v-else-if="isUrgent" class="border-orange-200 dark:border-orange-700 bg-orange-50 dark:bg-orange-950/40">
    <CardContent class="p-4 flex gap-3 items-start">
        <AlertTriangle :size="22" class="text-orange-600 dark:text-orange-400 shrink-0 mt-0.5"/>
        <div>
            <p class="text-sm font-semibold text-orange-600 dark:text-orange-400">HARI INI jatuh tempo!</p>
            <p class="text-xs text-orange-500 dark:text-orange-300/70 mt-0.5">Bayar <strong>{{ fmt(CICILAN) }}</strong> sekarang!</p>
        </div>
    </CardContent>
</Card>
<Card v-else-if="showReminder" class="border-yellow-200 dark:border-yellow-700/50 bg-yellow-50 dark:bg-yellow-950/30">
    <CardContent class="p-4 flex gap-3 items-start">
        <Bell :size="22" class="text-yellow-600 dark:text-yellow-400 shrink-0 mt-0.5"/>
        <div>
            <p class="text-sm font-semibold text-yellow-700 dark:text-yellow-400">Reminder cicilan emas</p>
            <p class="text-xs text-yellow-600 dark:text-yellow-300/70 mt-0.5">Tanggal 04 — <strong>{{ daysUntil04 }} hari lagi</strong>. Siapkan {{ fmt(CICILAN) }}</p>
        </div>
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
                        class="flex items-center justify-between text-xs text-zinc-400 hover:text-yellow-500 dark:hover:text-yellow-400 transition-colors pt-1 border-t border-zinc-100 dark:border-zinc-800">
                        <span>Lihat detail keuangan</span>
                        <ChevronRight :size="13"/>
                    </a>
                </CardContent>
            </Card>

            <!-- EMPTY STATE -->
            <div v-if="!last" class="text-center py-16">
                <div class="text-6xl mb-4">🏦</div>
                <p class="text-lg font-semibold text-zinc-700 dark:text-zinc-200 mb-2">Belum ada data</p>
                <p class="text-sm text-zinc-500 mb-6">Catat bulan pertama untuk mulai tracking</p>
                <a :href="route('portofolio.create')"
                   class="bg-yellow-500 text-black px-6 py-2.5 rounded-xl font-medium text-sm hover:bg-yellow-400 transition-colors">
                    Catat sekarang
                </a>
            </div>

            <template v-else>
                <!-- TOTAL PORTOFOLIO -->
                <Card class="border-yellow-300/60 dark:border-yellow-700/40 bg-gradient-to-br from-yellow-50 to-white dark:from-yellow-950/40 dark:to-zinc-900">
                    <CardContent class="p-5">
                        <p class="text-xs text-yellow-600 dark:text-yellow-500 uppercase tracking-widest font-medium mb-1">Total portofolio</p>
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

                <!-- KPI GRID -->
                <div class="grid grid-cols-2 gap-2">
                    <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                        <CardContent class="p-3">
                            <p class="text-xs text-zinc-500 mb-1">Total emas</p>
                            <p class="text-lg font-semibold text-yellow-500 dark:text-yellow-400">{{ (Number(last.emas_gram) + 5).toFixed(2) }}g</p>
                            <p class="text-xs text-zinc-400 mt-0.5">5g cicilan + {{ last.emas_gram }}g tunai</p>
                        </CardContent>
                    </Card>
                    <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                        <CardContent class="p-3">
                            <p class="text-xs text-zinc-500 mb-1">Harga emas</p>
                            <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ fmt(last.harga_emas) }}</p>
                            <p class="text-xs text-zinc-400 mt-0.5">per gram</p>
                        </CardContent>
                    </Card>
                    <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                        <CardContent class="p-3">
                            <p class="text-xs text-zinc-500 mb-1">Dana darurat</p>
                            <p class="text-lg font-semibold text-blue-500 dark:text-blue-400">{{ fmt(last.dana_darurat) }}</p>
                        </CardContent>
                    </Card>
                    <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                        <CardContent class="p-3">
                            <p class="text-xs text-zinc-500 mb-1">Reksa dana</p>
                            <p class="text-lg font-semibold text-green-500 dark:text-green-400">{{ fmt(last.reksa_dana) }}</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- BEP PROGRESS -->
                <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
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
                                <p class="text-xs font-medium text-red-500 dark:text-red-400 mt-0.5">{{ fmt(BEP) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-500">Kurang</p>
                                <p class="text-xs font-medium text-orange-500 dark:text-orange-400 mt-0.5">{{ fmt(Math.max(0, BEP - hargaNow)) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- ALOKASI -->
                <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                    <CardHeader class="pb-2 pt-4 px-4">
                        <CardTitle class="text-xs text-zinc-500 uppercase tracking-widest font-medium">Alokasi {{ last.bulan }}</CardTitle>
                    </CardHeader>
                    <CardContent class="px-4 pb-4 space-y-2.5">
    <div class="flex justify-between text-sm items-center">
        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Lock :size="13" class="text-yellow-600"/>Cicilan 5 gram</span>
        <span class="text-yellow-600 font-medium">{{ fmt(5 * last.harga_emas) }}</span>
    </div>
    <div class="flex justify-between text-sm items-center">
        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Coins :size="13" class="text-yellow-500 dark:text-yellow-400"/>Emas tunai {{ last.emas_gram }}g</span>
        <span class="text-yellow-500 dark:text-yellow-400 font-medium">{{ fmt(last.emas_gram * last.harga_emas) }}</span>
    </div>
    <div class="flex justify-between text-sm items-center">
        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Shield :size="13" class="text-blue-500 dark:text-blue-400"/>Dana darurat</span>
        <span class="text-blue-500 dark:text-blue-400 font-medium">{{ fmt(last.dana_darurat) }}</span>
    </div>
    <div class="flex justify-between text-sm items-center">
        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><TrendingUp :size="13" class="text-green-500 dark:text-green-400"/>Reksa dana</span>
        <span class="text-green-500 dark:text-green-400 font-medium">{{ fmt(last.reksa_dana) }}</span>
    </div>
    <div class="flex justify-between text-sm items-center">
        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Landmark :size="13" class="text-purple-500 dark:text-purple-400"/>SBN</span>
        <span class="text-purple-500 dark:text-purple-400 font-medium">{{ fmt(last.sbn) }}</span>
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

                <!-- SIMULASI SAVING -->
<Card class="border-yellow-200 dark:border-yellow-700/30 bg-white dark:bg-zinc-900">
    <CardHeader class="pb-2 pt-4 px-4">
        <CardTitle class="text-xs text-yellow-600 dark:text-yellow-500 uppercase tracking-widest font-medium flex items-center gap-1.5">
            <TrendingUp :size="12"/> Simulasi saving bulanan
        </CardTitle>
    </CardHeader>
    <CardContent class="px-4 pb-4 space-y-3">
        <div v-if="totalPct > 100" class="bg-red-50 dark:bg-red-950/50 border border-red-200 dark:border-red-800 rounded-lg px-3 py-2 text-xs text-red-600 dark:text-red-400 flex items-center gap-1.5">
            <AlertTriangle :size="12"/> Total {{ totalPct }}% — melebihi 100%
        </div>

        <!-- Budget -->
        <div class="flex items-center gap-3">
            <span class="text-xs text-zinc-500 w-28 shrink-0 flex items-center gap-1.5">
                <Wallet :size="12" class="text-zinc-400"/> Budget/bln
            </span>
            <input type="range" v-model="budget" min="2000000" max="6000000" step="100000" class="flex-1 accent-yellow-400 h-1.5">
            <span class="text-xs font-medium w-28 text-right shrink-0 text-zinc-900 dark:text-white">{{ fmt(budget) }}</span>
        </div>

        <!-- Dana darurat -->
        <div class="flex items-center gap-3">
            <span class="text-xs text-zinc-500 w-28 shrink-0 flex items-center gap-1.5">
                <Shield :size="12" class="text-blue-500 dark:text-blue-400"/> Dana darurat
            </span>
            <input type="range" v-model="pDD" min="0" max="100" step="5" class="flex-1 accent-blue-400 h-1.5">
            <span class="text-xs font-medium w-28 text-right shrink-0 text-blue-500 dark:text-blue-400">{{ pDD }}% · {{ fmt(mDD) }}</span>
        </div>

        <!-- Emas tunai -->
        <div class="flex items-center gap-3">
            <span class="text-xs text-zinc-500 w-28 shrink-0 flex items-center gap-1.5">
                <Coins :size="12" class="text-yellow-500 dark:text-yellow-400"/> Emas tunai
            </span>
            <input type="range" v-model="pEM" min="0" max="100" step="5" class="flex-1 accent-yellow-400 h-1.5">
            <span class="text-xs font-medium w-28 text-right shrink-0 text-yellow-500 dark:text-yellow-400">{{ pEM }}% · {{ fmt(mEM) }}</span>
        </div>

        <!-- Reksa dana -->
        <div class="flex items-center gap-3">
            <span class="text-xs text-zinc-500 w-28 shrink-0 flex items-center gap-1.5">
                <TrendingUp :size="12" class="text-green-500 dark:text-green-400"/> Reksa dana
            </span>
            <input type="range" v-model="pRD" min="0" max="100" step="5" class="flex-1 accent-green-400 h-1.5">
            <span class="text-xs font-medium w-28 text-right shrink-0 text-green-500 dark:text-green-400">{{ pRD }}% · {{ fmt(mRD) }}</span>
        </div>

        <!-- SBN -->
        <div class="flex items-center gap-3">
            <span class="text-xs text-zinc-500 w-28 shrink-0 flex items-center gap-1.5">
                <Landmark :size="12" class="text-purple-500 dark:text-purple-400"/> SBN
            </span>
            <input type="range" v-model="pSB" min="0" max="100" step="5" class="flex-1 accent-purple-400 h-1.5">
            <span class="text-xs font-medium w-28 text-right shrink-0 text-purple-500 dark:text-purple-400">{{ pSB }}% · {{ fmt(mSB) }}</span>
        </div>

        <!-- Durasi -->
        <div class="flex items-center gap-3">
            <span class="text-xs text-zinc-500 w-28 shrink-0 flex items-center gap-1.5">
                <Calendar :size="12" class="text-zinc-400"/> Durasi
            </span>
            <input type="range" v-model="tahun" min="1" max="10" step="1" class="flex-1 accent-zinc-400 h-1.5">
            <span class="text-xs font-medium w-28 text-right shrink-0 text-zinc-900 dark:text-white">{{ tahun }} tahun</span>
        </div>

        <!-- Bar alokasi -->
        <div class="h-2 rounded-full overflow-hidden flex bg-zinc-200 dark:bg-zinc-800">
            <div :style="{width:pDD+'%'}" class="bg-blue-500 transition-all"></div>
            <div :style="{width:pEM+'%'}" class="bg-yellow-400 transition-all"></div>
            <div :style="{width:pRD+'%'}" class="bg-green-500 transition-all"></div>
            <div :style="{width:pSB+'%'}" class="bg-purple-500 transition-all"></div>
        </div>

        <!-- KPI hasil -->
        <div class="grid grid-cols-3 gap-2">
            <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-3 text-center">
                <p class="text-xs text-zinc-500 mb-1">Modal</p>
                <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ fmtJt(modalTotal) }}</p>
            </div>
            <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-3 text-center">
                <p class="text-xs text-zinc-500 mb-1">Nilai akhir</p>
                <p class="text-sm font-semibold text-yellow-500 dark:text-yellow-400">{{ fmtJt(nilaiAkhir) }}</p>
            </div>
            <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-3 text-center">
                <p class="text-xs text-zinc-500 mb-1">Untung</p>
                <p class="text-sm font-semibold" :class="keuntungan >= 0 ? 'text-green-500 dark:text-green-400' : 'text-red-500 dark:text-red-400'">
                    {{ keuntungan >= 0 ? '+' : '' }}{{ fmtJt(keuntungan) }}
                </p>
            </div>
        </div>
    </CardContent>
</Card>

                <!-- RIWAYAT -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs text-zinc-500 uppercase tracking-widest font-medium">Riwayat semua bulan</p>
                        <button @click="exportPortofolio"
                            class="flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 text-zinc-500 dark:text-zinc-400 hover:border-yellow-400 hover:text-yellow-600 dark:hover:text-yellow-400 transition-colors">
                            <Download :size="12"/> Export CSV
                        </button>
                    </div>
                    <div v-for="item in [...portofolios].reverse()" :key="item.id" class="mb-2">
                        <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 hover:border-zinc-300 dark:hover:border-zinc-700 transition-colors">
                            <CardContent class="p-4">
                                <div class="flex justify-between items-center mb-3">
                                    <span class="font-semibold text-zinc-900 dark:text-white">{{ item.bulan }}</span>
                                    <div class="flex items-center gap-2">
    <span class="text-yellow-500 dark:text-yellow-400 font-semibold text-sm">{{ fmtJt(total(item)) }}</span>
    <a :href="route('portofolio.edit', item.id)"
        class="p-1.5 rounded-lg border border-blue-300 dark:border-blue-800 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950 transition-colors">
        <Pencil :size="13"/>
    </a>
    <button @click="confirmHapus(item)"
        class="p-1.5 rounded-lg border border-red-300 dark:border-red-900 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950 transition-colors">
        <Trash2 :size="13"/>
    </button>
</div>

                                </div>
                                <div class="space-y-1.5 text-xs text-zinc-500">
                                    <div class="flex justify-between">
                                        <span>Emas tunai</span>
                                        <span class="text-yellow-500 dark:text-yellow-500">{{ item.emas_gram }}g · {{ fmt(item.emas_gram * item.harga_emas) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Harga/gram</span>
                                        <span class="text-zinc-600 dark:text-zinc-400">{{ fmt(item.harga_emas) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Dana darurat</span>
                                        <span class="text-blue-500 dark:text-blue-400">{{ fmt(item.dana_darurat) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Reksa dana</span>
                                        <span class="text-green-500 dark:text-green-400">{{ fmt(item.reksa_dana) }}</span>
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
            <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="batalHapus">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"/>
                <div class="relative w-full max-w-sm bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-zinc-200 dark:border-zinc-800 p-6">
                    <div class="flex items-start gap-3 mb-5">
                        <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center shrink-0">
                            <Trash2 :size="18" class="text-red-600 dark:text-red-400"/>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-zinc-900 dark:text-white text-sm">Hapus data {{ deleteTarget?.bulan }}?</h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Data portofolio bulan ini akan dihapus permanen dan tidak bisa dikembalikan.</p>
                        </div>
                        <button @click="batalHapus" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors shrink-0">
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
