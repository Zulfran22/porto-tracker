<script setup>
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import { Separator } from '@/Components/ui/separator'
import {
    FileText, Target, PiggyBank, Info,
    Lock, Coins, TrendingUp, Tag,
    Clock, Calendar, Code2, Hash, AlertTriangle
} from 'lucide-vue-next'
import { fmt, fmtJt } from '@/Composables/useCurrency'
import { inputClass } from '@/Composables/useFormStyles'
import { DEFAULT_BUDGET } from '@/Composables/useFinanceConstants'

const props = defineProps({
    lastHargaEmas: { type: Number, default: null },
    lastCicilan:   { type: Number, default: null },
    investmentTypes: { type: Array, default: () => [] },
    aktifKontrak:  { type: Object, default: null },
    budgetBulanan: { type: Number, default: DEFAULT_BUDGET },
})

// Kontrak & BEP hanya berarti kalau user benar-benar punya kontrak aktif tercatat —
// tanpa itu jangan menebak pakai kontrak siapa pun, tampilkan empty-state di template.
const hasKontrak     = computed(() => !!props.aktifKontrak)
const cicilanGram    = computed(() => hasKontrak.value ? Number(props.aktifKontrak.total_gram) : 0)
const cicilanBulanan = computed(() => hasKontrak.value ? Number(props.aktifKontrak.angsuran_bulan) : 0)
const bepTarget      = computed(() => hasKontrak.value ? Number(props.aktifKontrak.bep_per_gram) : 0)
const hargaSekarang  = computed(() => props.lastHargaEmas)
const bepGap         = computed(() => (hasKontrak.value && bepTarget.value > 0 && hargaSekarang.value)
    ? Math.max(0, Math.round((bepTarget.value - hargaSekarang.value) / bepTarget.value * 1000) / 10)
    : null)

// Simulasi saving — budget di-init dari server dan dipersist balik (debounced)
// supaya Target & Info memakai satu angka budget yang sama. Dipindah dari
// Dashboard ke sini karena "Rencana saving/bulan" sudah membahas hal yang
// sama — daripada dua kartu alokasi budget terpisah di dua halaman berbeda.
const BUDGET_MIN = 1000000
const BUDGET_MAX = 100000000
const budget = ref(props.budgetBulanan)
let budgetTimer = null
watch(budget, (val) => {
    clearTimeout(budgetTimer)
    budgetTimer = setTimeout(() => {
        // Input bebas ketik (bukan slider yang rentangnya otomatis valid) —
        // clamp dulu ke batas yang divalidasi TargetController::updateBudget
        // supaya nilai di luar batas atau kosong tidak diam-diam ditolak backend
        // tanpa notifikasi apa pun ke user.
        const clamped = Math.min(BUDGET_MAX, Math.max(BUDGET_MIN, Number(val) || 0))
        if (clamped !== Number(val)) budget.value = clamped
        router.put(route('target.budget'), { budget_bulanan: clamped }, {
            preserveScroll: true,
            preserveState: true,
            only: [],
        })
    }, 600)
})

// Input angka manual (bukan slider) — alokasi per jenis investasi ber-Rupiah,
// split rata secara default, sisa pembulatan diserap entri terakhir.
function evenSplit(n) {
    if (n === 0) return []
    const base = Math.floor(100 / n)
    const arr = Array(n).fill(base)
    arr[n - 1] = 100 - base * (n - 1)
    return arr
}
const allocations = ref(props.investmentTypes.map((t, i) => ({
    type_name: t.name,
    pct: evenSplit(props.investmentTypes.length)[i],
})))
watch(() => props.investmentTypes, (newTypes) => {
    const existing = Object.fromEntries(allocations.value.map(a => [a.type_name, a.pct]))
    const splits = evenSplit(newTypes.length)
    allocations.value = newTypes.map((t, i) => ({ type_name: t.name, pct: existing[t.name] ?? splits[i] }))
})

const tahun = ref(5)
const sisa  = computed(() => Math.max(0, budget.value - cicilanBulanan.value))
const totalPct = computed(() => allocations.value.reduce((sum, a) => sum + a.pct, 0))

function monthlyFor(alloc) {
    return Math.round(sisa.value * alloc.pct / 100)
}

// Asumsi return tahunan per jenis investasi — dikenal untuk 3 default, jatuh
// ke 8% generik untuk jenis custom yang tidak punya data historis.
const DEFAULT_RATES = { 'Dana Darurat': 0.05, 'Reksa Dana': 0.11, 'SBN': 0.065 }
function rateFor(name) {
    return DEFAULT_RATES[name] ?? 0.08
}

function fv(monthly, rate, months) {
    const r = rate / 12
    return r === 0 ? monthly * months : monthly * ((Math.pow(1 + r, months) - 1) / r)
}

const months     = computed(() => tahun.value * 12)
const nilaiAkhir = computed(() =>
    allocations.value.reduce((sum, a) => sum + fv(monthlyFor(a), rateFor(a.type_name), months.value), 0))
const modalTotal  = computed(() => allocations.value.reduce((sum, a) => sum + monthlyFor(a), 0) * months.value)
const keuntungan  = computed(() => nilaiAkhir.value - modalTotal.value)
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-lg mx-auto lg:max-w-3xl px-4 py-5 lg:py-8 space-y-3">

            <p class="text-xs text-zinc-500 uppercase tracking-widest font-medium">Informasi</p>

            <!-- KONTRAK -->
            <Card class="border-yellow-300/60 dark:border-yellow-700/40 bg-white dark:bg-zinc-900">
                <CardHeader class="pb-2 pt-4 px-4">
                    <CardTitle class="text-xs text-yellow-600 dark:text-yellow-500 uppercase tracking-widest flex items-center gap-1.5">
                        <FileText :size="12"/> Kontrak cicilan emas
                    </CardTitle>
                </CardHeader>
                <CardContent v-if="hasKontrak" class="px-4 pb-4 space-y-2.5">
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Coins :size="12" class="text-yellow-500 dark:text-yellow-400"/> Emas cicilan</span>
                        <span class="text-yellow-500 dark:text-yellow-400 font-semibold">{{ cicilanGram.toFixed(4) }} gram</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Lock :size="12" class="text-yellow-600"/> Angsuran/bulan</span>
                        <span class="text-zinc-900 dark:text-white font-semibold">{{ fmt(cicilanBulanan) }}</span>
                    </div>
                    <Separator class="bg-zinc-200 dark:bg-zinc-800"/>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Clock :size="12" class="text-red-500 dark:text-red-400"/> Batas bayar</span>
                        <Badge class="bg-red-100 text-red-700 border-red-200 dark:bg-red-900 dark:text-red-400 dark:border-red-700 border text-xs">Tanggal {{ new Date(aktifKontrak.tanggal_mulai).getDate() }} tiap bulan</Badge>
                    </div>
                </CardContent>
                <CardContent v-else class="px-4 pb-4">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3">Belum ada kontrak cicilan emas tercatat.</p>
                    <a :href="route('kontrak-cicilan.index')"
                       class="inline-block text-xs px-3 py-1.5 rounded-lg bg-indigo-500 hover:bg-indigo-400 text-white font-medium transition-colors">
                        Tambah kontrak
                    </a>
                </CardContent>
            </Card>

            <!-- BEP -->
            <Card v-if="hasKontrak" class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                <CardHeader class="pb-2 pt-4 px-4">
                    <CardTitle class="text-xs text-zinc-500 uppercase tracking-widest flex items-center gap-1.5">
                        <Target :size="12"/> Target BEP
                    </CardTitle>
                </CardHeader>
                <CardContent class="px-4 pb-4 space-y-2.5">
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Target :size="12" class="text-yellow-500 dark:text-yellow-400"/> BEP cicilan</span>
                        <span class="text-yellow-500 dark:text-yellow-400 font-semibold">{{ fmt(bepTarget) }}/gram</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><TrendingUp :size="12" class="text-zinc-400"/> Harga sekarang</span>
                        <span class="text-zinc-700 dark:text-zinc-300">{{ hargaSekarang ? fmt(hargaSekarang) + '/gram' : 'Belum ada data' }}</span>
                    </div>
                    <template v-if="bepGap !== null">
                        <div class="flex justify-between text-sm items-center">
                            <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><TrendingUp :size="12" class="text-orange-500 dark:text-orange-400"/> Perlu naik</span>
                            <span class="text-orange-500 dark:text-orange-400 font-semibold">~{{ bepGap }}% lagi</span>
                        </div>
                    </template>
                </CardContent>
            </Card>

            <!-- RENCANA & SIMULASI SAVING -->
            <Card class="border-indigo-200 dark:border-indigo-700/30 bg-white dark:bg-zinc-900">
                <CardHeader class="pb-2 pt-4 px-4">
                    <CardTitle class="text-xs text-indigo-600 dark:text-indigo-400 uppercase tracking-widest flex items-center gap-1.5">
                        <PiggyBank :size="12"/> Rencana &amp; simulasi saving/bulan
                    </CardTitle>
                </CardHeader>
                <CardContent class="px-4 pb-4 space-y-3">
                    <div v-if="totalPct > 100" class="bg-red-50 dark:bg-red-950/50 border border-red-200 dark:border-red-800 rounded-lg px-3 py-2 text-xs text-red-600 dark:text-red-400 flex items-center gap-1.5">
                        <AlertTriangle :size="12"/> Total {{ totalPct }}% — melebihi 100%
                    </div>

                    <!-- Budget -->
                    <div>
                        <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">Budget total/bulan (Rp)</label>
                        <input type="number" step="100000" :min="BUDGET_MIN" :max="BUDGET_MAX" v-model.number="budget" :class="inputClass"/>
                        <p class="text-xs text-zinc-400 mt-1">Min {{ fmt(BUDGET_MIN) }} — Maks {{ fmt(BUDGET_MAX) }}</p>
                    </div>

                    <div v-if="hasKontrak" class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Lock :size="12" class="text-yellow-600"/> Cicilan emas</span>
                        <span class="text-yellow-600 font-medium">{{ fmt(cicilanBulanan) }}</span>
                    </div>

                    <!-- Alokasi per jenis investasi (input angka, bukan slider) -->
                    <div v-for="alloc in allocations" :key="alloc.type_name" class="flex items-center gap-3">
                        <span class="text-xs text-zinc-500 dark:text-zinc-400 w-28 shrink-0 flex items-center gap-1.5 truncate">
                            <Tag :size="12" class="text-indigo-500 shrink-0"/> {{ alloc.type_name }}
                        </span>
                        <input type="number" step="5" min="0" max="100" v-model.number="alloc.pct"
                            class="w-16 shrink-0 rounded-lg border text-sm px-2 py-1.5 bg-white dark:bg-zinc-800 border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-indigo-400"/>
                        <span class="text-xs text-zinc-400">%</span>
                        <span class="flex-1 text-right text-xs font-medium text-indigo-500 dark:text-indigo-400">{{ fmt(monthlyFor(alloc)) }}</span>
                    </div>
                    <p v-if="!allocations.length" class="text-xs text-zinc-400 text-center py-2">
                        Belum ada jenis investasi ber-Rupiah — tambah lewat halaman Catat.
                    </p>

                    <!-- Durasi -->
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-zinc-500 dark:text-zinc-400 w-28 shrink-0 flex items-center gap-1.5">
                            <Calendar :size="12" class="text-zinc-400"/> Durasi
                        </span>
                        <input type="number" min="1" max="30" v-model.number="tahun"
                            class="w-16 shrink-0 rounded-lg border text-sm px-2 py-1.5 bg-white dark:bg-zinc-800 border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-indigo-400"/>
                        <span class="text-xs text-zinc-400">tahun</span>
                    </div>

                    <Separator class="bg-zinc-200 dark:bg-zinc-800"/>

                    <!-- KPI hasil -->
                    <div class="grid grid-cols-3 gap-2">
                        <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-3 text-center">
                            <p class="text-xs text-zinc-500 mb-1">Modal</p>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ fmtJt(modalTotal) }}</p>
                        </div>
                        <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-3 text-center">
                            <p class="text-xs text-zinc-500 mb-1">Nilai akhir</p>
                            <p class="text-sm font-semibold text-indigo-500 dark:text-indigo-400">{{ fmtJt(nilaiAkhir) }}</p>
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

            <!-- APP INFO -->
            <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                <CardHeader class="pb-2 pt-4 px-4">
                    <CardTitle class="text-xs text-zinc-500 uppercase tracking-widest flex items-center gap-1.5">
                        <Info :size="12"/> Tentang aplikasi
                    </CardTitle>
                </CardHeader>
                <CardContent class="px-4 pb-4 space-y-2.5">
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Info :size="12" class="text-zinc-400"/> Nama</span>
                        <span class="text-zinc-900 dark:text-white font-semibold">WealthID</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Code2 :size="12" class="text-zinc-400"/> Stack</span>
                        <span class="text-zinc-600 dark:text-zinc-300 text-xs">Laravel + Vue + Shadcn</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Hash :size="12" class="text-zinc-400"/> Versi</span>
                        <Badge class="bg-zinc-100 text-zinc-600 border-zinc-300 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700 border text-xs">v1.0.0</Badge>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Calendar :size="12" class="text-zinc-400"/> Dibuat</span>
                        <span class="text-zinc-600 dark:text-zinc-300">Juni 2026</span>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AuthenticatedLayout>
</template>
