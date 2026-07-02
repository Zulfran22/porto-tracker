<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import { Progress } from '@/Components/ui/progress'
import { Chart, registerables } from 'chart.js'
import { useTheme } from '@/Composables/useTheme'
import {
    Wallet, Calendar, Tag, StickyNote, Trash2, Loader2,
    UtensilsCrossed, Car, ShoppingBag, Film, HeartPulse, MoreHorizontal,
    Briefcase, Gift, PackageOpen, TrendingUp, TrendingDown, ArrowDownCircle, ArrowUpCircle,
    BarChart3, Target, PiggyBank, X, Download, RefreshCw, Plus, ToggleLeft, ToggleRight, Repeat2
} from 'lucide-vue-next'
import { exportCSV } from '@/Composables/useExport'
Chart.register(...registerables)

const props = defineProps({
    transactions: { type: Array, default: () => [] },
    budgets:      { type: Array, default: () => [] },
    recurrings:   { type: Array, default: () => [] },
    customCats:   { type: Array, default: () => [] },
})

const { isDark } = useTheme()

const KATEGORI_EXPENSE = [
    { name: 'Makan',     icon: UtensilsCrossed, color: 'text-orange-500 dark:text-orange-400' },
    { name: 'Transport', icon: Car,             color: 'text-blue-500 dark:text-blue-400'     },
    { name: 'Belanja',   icon: ShoppingBag,     color: 'text-pink-500 dark:text-pink-400'     },
    { name: 'Hiburan',   icon: Film,            color: 'text-purple-500 dark:text-purple-400' },
    { name: 'Kesehatan', icon: HeartPulse,      color: 'text-red-500 dark:text-red-400'       },
    { name: 'Lainnya',   icon: MoreHorizontal,  color: 'text-zinc-500 dark:text-zinc-400'     },
]

const KATEGORI_INCOME = [
    { name: 'Gaji',        icon: Briefcase,      color: 'text-green-600 dark:text-green-400'   },
    { name: 'Bonus',       icon: Gift,           color: 'text-emerald-600 dark:text-emerald-400'},
    { name: 'Jual barang', icon: PackageOpen,    color: 'text-teal-600 dark:text-teal-400'     },
    { name: 'Lainnya',     icon: MoreHorizontal, color: 'text-zinc-500 dark:text-zinc-400'     },
]

function kategoriInfo(type, nama) {
    const list = type === 'income' ? KATEGORI_INCOME : KATEGORI_EXPENSE
    return list.find(k => k.name === nama) ?? list[list.length - 1]
}

const now = new Date()
const todayStr = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0')
const currentMonth = todayStr.slice(0, 7)

// FORM
const activeType = ref('expense')

const form = useForm({
    tanggal:  todayStr,
    type:     'expense',
    kategori: 'Makan',
    jumlah:   '',
    catatan:  '',
})

function switchType(type) {
    activeType.value = type
    form.type = type
    form.kategori = type === 'income' ? 'Gaji' : 'Makan'
}

const submit = () => {
    form.post(route('keuangan.store'), {
        onSuccess: () => {
            form.reset('jumlah', 'catatan')
        },
    })
}

const budgetForm = useForm({
    kategori: 'Makan',
    limit_jumlah: '',
})

const submitBudget = () => {
    budgetForm.post(route('keuangan.budget'), {
        preserveScroll: true,
        onSuccess: () => budgetForm.reset('limit_jumlah'),
    })
}

const hapus = (id) => {
    if (confirm('Hapus transaksi ini?')) router.delete(route('keuangan.destroy', id))
}

const hapusBudget = (kategori) => {
    if (confirm(`Hapus budget "${kategori}"?`))
        router.delete(route('keuangan.budget.destroy', kategori), { preserveScroll: true })
}

// Export CSV keuangan
const exportKeuangan = () => {
    exportCSV('keuangan.csv',
        ['Tanggal', 'Tipe', 'Kategori', 'Jumlah', 'Catatan'],
        props.transactions.map(t => [t.tanggal, t.type, t.kategori, t.jumlah, t.catatan ?? ''])
    )
}

// Recurring form
const showRecurringForm = ref(false)
const recurringForm = useForm({ type: 'expense', kategori: 'Makan', jumlah: '', catatan: '' })
const submitRecurring = () => recurringForm.post(route('recurring.store'), {
    preserveScroll: true,
    onSuccess: () => { recurringForm.reset('jumlah', 'catatan'); showRecurringForm.value = false }
})
const applyRecurring = () => router.post(route('recurring.apply'), {}, { preserveScroll: true })
const toggleRecurring = (id) => router.patch(route('recurring.toggle', id), {}, { preserveScroll: true })
const hapusRecurring = (id) => { if (confirm('Hapus transaksi berulang ini?')) router.delete(route('recurring.destroy', id), { preserveScroll: true }) }

// Custom categories
const showCatForm = ref(false)
const catForm = useForm({ type: 'expense', name: '' })
const submitCat = () => catForm.post(route('kategori.store'), {
    preserveScroll: true,
    onSuccess: () => { catForm.reset('name'); showCatForm.value = false }
})
const hapusCat = (id) => router.delete(route('kategori.destroy', id), { preserveScroll: true })

// All categories (default + custom) for the form
// De-dupe: kategori kustom bisa dibuat dengan nama yang kebetulan sama dengan kategori
// bawaan (tidak divalidasi di backend) — tanpa Set ini, nama kembar akan dobel-hitung
// di budgetProgress (satu dari KATEGORI_EXPENSE, satu lagi dari customCats).
const allExpenseKategori = computed(() => {
    const custom = props.customCats.filter(c => c.type === 'expense').map(c => c.name)
    return [...new Set([...KATEGORI_EXPENSE.map(k => k.name), ...custom])]
})
const allIncomeKategori = computed(() => {
    const custom = props.customCats.filter(c => c.type === 'income').map(c => c.name)
    return [...new Set([...KATEGORI_INCOME.map(k => k.name), ...custom])]
})
const currentKategoriList = computed(() =>
    activeType.value === 'income' ? allIncomeKategori.value : allExpenseKategori.value
)

const fmt = (n) => 'Rp' + Math.round(n).toLocaleString('id-ID')

// SUMMARY
const bulanIni = computed(() => props.transactions.filter(t => t.tanggal.startsWith(currentMonth)))

const totalIncomeBulan = computed(() =>
    bulanIni.value.filter(t => t.type === 'income').reduce((s, t) => s + Number(t.jumlah), 0)
)
const totalExpenseBulan = computed(() =>
    bulanIni.value.filter(t => t.type === 'expense').reduce((s, t) => s + Number(t.jumlah), 0)
)
const netBulan = computed(() => totalIncomeBulan.value - totalExpenseBulan.value)

const totalExpenseHariIni = computed(() =>
    props.transactions.filter(t => t.tanggal === todayStr && t.type === 'expense')
        .reduce((s, t) => s + Number(t.jumlah), 0)
)

const rataRataHarian = computed(() => {
    const hariBerjalan = now.getDate()
    return hariBerjalan > 0 ? totalExpenseBulan.value / hariBerjalan : 0
})

const breakdownExpense = computed(() => {
    const map = {}
    bulanIni.value.filter(t => t.type === 'expense').forEach(t => {
        map[t.kategori] = (map[t.kategori] ?? 0) + Number(t.jumlah)
    })
    return Object.entries(map)
        .map(([kategori, total]) => ({ kategori, total }))
        .sort((a, b) => b.total - a.total)
})

const maxKategori = computed(() =>
    breakdownExpense.value.length ? breakdownExpense.value[0].total : 0
)

const budgetMap = computed(() => {
    const map = {}
    props.budgets.forEach(b => {
        map[b.kategori] = Number(b.limit_jumlah)
    })
    return map
})

const expenseByKategori = computed(() => {
    const map = {}
    bulanIni.value.filter(t => t.type === 'expense').forEach(t => {
        map[t.kategori] = (map[t.kategori] ?? 0) + Number(t.jumlah)
    })
    return map
})

// Pakai allExpenseKategori (bawaan + kustom) agar pengeluaran kategori kustom
// ikut terhitung ke Total bulan ini / Sisa budget, bukan cuma 6 kategori bawaan.
const budgetProgress = computed(() =>
    allExpenseKategori.value.map(name => {
        const info   = kategoriInfo('expense', name)
        const limit  = budgetMap.value[name] ?? 0
        const spent  = expenseByKategori.value[name] ?? 0
        const percent = limit > 0 ? Math.round(spent / limit * 100) : 0
        return {
            name,
            icon: info.icon,
            color: info.color,
            limit,
            spent,
            remaining: Math.max(0, limit - spent),
            percent,
            cappedPercent: Math.min(100, percent),
        }
    }).filter(item => item.limit > 0 || item.spent > 0)
)

const totalBudget = computed(() => budgetProgress.value.reduce((s, b) => s + b.limit, 0))
const totalBudgetUsed = computed(() => budgetProgress.value.reduce((s, b) => s + b.spent, 0))
const totalBudgetLeft = computed(() => Math.max(0, totalBudget.value - totalBudgetUsed.value))
const totalBudgetPct = computed(() => totalBudget.value > 0 ? Math.min(100, Math.round(totalBudgetUsed.value / totalBudget.value * 100)) : 0)

function editBudget(kategori) {
    budgetForm.kategori = kategori
    budgetForm.limit_jumlah = budgetMap.value[kategori] ?? ''
}

function monthLabel(date) {
    return date.toLocaleDateString('id-ID', { month: 'short' })
}

const trendMonths = computed(() => {
    const months = []
    for (let i = 5; i >= 0; i--) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1)
        const key = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0')
        const items = props.transactions.filter(t => t.tanggal.startsWith(key))
        const income = items.filter(t => t.type === 'income').reduce((s, t) => s + Number(t.jumlah), 0)
        const expense = items.filter(t => t.type === 'expense').reduce((s, t) => s + Number(t.jumlah), 0)
        months.push({ key, label: monthLabel(d), income, expense, net: income - expense })
    }
    return months
})

const trendNet = computed(() => trendMonths.value.at(-1)?.net ?? 0)
const trendPrevNet = computed(() => trendMonths.value.at(-2)?.net ?? 0)
const trendDiff = computed(() => trendNet.value - trendPrevNet.value)

const chartType = ref('line')
const chartTrend = ref(null)
let trendChart = null

function buildChart() {
    if (!chartTrend.value) return
    trendChart?.destroy()

    const dark = isDark.value
    const gridC = dark ? '#27272a' : '#e4e4e7'
    const txtC  = dark ? '#71717a' : '#52525b'
    const ptBdr = dark ? '#09090b' : '#ffffff'
    const isBar = chartType.value === 'bar'

    trendChart = new Chart(chartTrend.value, {
        type: chartType.value,
        data: {
            labels: trendMonths.value.map(m => m.label),
            datasets: [
                {
                    label: 'Pemasukan',
                    data: trendMonths.value.map(m => m.income),
                    borderColor: '#22c55e',
                    backgroundColor: isBar ? 'rgba(34,197,94,0.6)' : 'rgba(34,197,94,0.08)',
                    borderWidth: isBar ? 0 : 2,
                    pointRadius: isBar ? 0 : 4,
                    pointBackgroundColor: '#22c55e',
                    pointBorderColor: ptBdr,
                    pointBorderWidth: 2,
                    fill: false,
                    tension: 0.4,
                    borderRadius: isBar ? 4 : 0,
                },
                {
                    label: 'Pengeluaran',
                    data: trendMonths.value.map(m => m.expense),
                    borderColor: '#ef4444',
                    backgroundColor: isBar ? 'rgba(239,68,68,0.6)' : 'rgba(239,68,68,0.08)',
                    borderWidth: isBar ? 0 : 2,
                    pointRadius: isBar ? 0 : 4,
                    pointBackgroundColor: '#ef4444',
                    pointBorderColor: ptBdr,
                    pointBorderWidth: 2,
                    fill: false,
                    tension: 0.4,
                    borderRadius: isBar ? 4 : 0,
                },
                {
                    label: 'Net',
                    data: trendMonths.value.map(m => m.net),
                    borderColor: '#eab308',
                    backgroundColor: isBar ? 'rgba(234,179,8,0.5)' : 'rgba(234,179,8,0.08)',
                    borderWidth: isBar ? 0 : 2,
                    borderDash: isBar ? [] : [5, 3],
                    pointRadius: isBar ? 0 : 3,
                    pointBackgroundColor: '#eab308',
                    pointBorderColor: ptBdr,
                    pointBorderWidth: 2,
                    fill: false,
                    tension: 0.4,
                    borderRadius: isBar ? 4 : 0,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: { color: txtC, boxWidth: 10, font: { size: 11 } },
                },
                tooltip: { callbacks: { label: ctx => ' ' + ctx.dataset.label + ': ' + fmt(ctx.parsed.y) } },
            },
            scales: {
                y: { grid: { color: gridC }, ticks: { color: txtC, callback: v => fmt(v) } },
                x: { grid: { display: false }, ticks: { color: txtC, font: { size: 11 } } },
            },
        },
    })
}

onMounted(buildChart)

onBeforeUnmount(() => {
    trendChart?.destroy()
})

// RIWAYAT — grouped by tanggal
const grouped = computed(() => {
    const map = {}
    props.transactions.forEach(t => {
        if (!map[t.tanggal]) map[t.tanggal] = []
        map[t.tanggal].push(t)
    })
    return Object.entries(map).map(([tanggal, items]) => {
        const income  = items.filter(i => i.type === 'income').reduce((s, i) => s + Number(i.jumlah), 0)
        const expense = items.filter(i => i.type === 'expense').reduce((s, i) => s + Number(i.jumlah), 0)
        return { tanggal, items, income, expense, net: income - expense }
    })
})

function fmtTanggal(tgl) {
    const d = new Date(tgl + 'T00:00:00')
    return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
}

const inputClass = "w-full bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl px-3 py-2.5 text-sm text-zinc-900 dark:text-white focus:border-yellow-500 focus:ring-0 outline-none transition-colors placeholder:text-zinc-400 dark:placeholder:text-zinc-600"
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-lg mx-auto px-4 py-5 space-y-3">

            <div class="flex items-center justify-between mb-1">
                <p class="text-xs text-zinc-500 uppercase tracking-widest font-medium flex items-center gap-1.5">
                    <Wallet :size="12"/> Keuangan harian
                </p>
                <Badge class="border text-xs"
                    :class="netBulan >= 0
                        ? 'bg-green-100 text-green-700 border-green-300 dark:bg-green-900 dark:text-green-400 dark:border-green-700'
                        : 'bg-red-100 text-red-700 border-red-300 dark:bg-red-900 dark:text-red-400 dark:border-red-700'">
                    {{ netBulan >= 0 ? '+' : '' }}{{ fmt(netBulan) }} bulan ini
                </Badge>
            </div>

            <div v-if="$page.props.flash?.success"
                 class="bg-green-50 dark:bg-green-950/50 border border-green-200 dark:border-green-800 rounded-xl p-3 text-sm text-green-700 dark:text-green-400">
                {{ $page.props.flash.success }}
            </div>

            <!-- QUICK ADD -->
            <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                <CardContent class="px-4 pt-4 pb-4">

                    <!-- TOGGLE -->
                    <div class="flex bg-zinc-100 dark:bg-zinc-800 rounded-xl p-1 mb-3">
                        <button type="button" @click="switchType('expense')"
                            class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="activeType === 'expense'
                                ? 'bg-red-100 dark:bg-red-500/20 text-red-600 dark:text-red-400'
                                : 'text-zinc-500'">
                            <ArrowDownCircle :size="14"/> Pengeluaran
                        </button>
                        <button type="button" @click="switchType('income')"
                            class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="activeType === 'income'
                                ? 'bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-400'
                                : 'text-zinc-500'">
                            <ArrowUpCircle :size="14"/> Pemasukan
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                    <Calendar :size="12" class="text-zinc-400"/> Tanggal
                                </label>
                                <input type="date" v-model="form.tanggal" :class="inputClass"/>
                            </div>
                            <div>
                                <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                    <Tag :size="12" class="text-zinc-400"/> Kategori
                                </label>
                                <select v-model="form.kategori" :class="inputClass">
                                    <option v-for="k in currentKategoriList" :key="k" :value="k">{{ k }}</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                <Wallet :size="12" class="text-zinc-400"/> Jumlah (Rp)
                            </label>
                            <input type="number" v-model="form.jumlah" placeholder="mis. 25000" :class="inputClass"/>
                            <p v-if="form.errors.jumlah" class="text-xs text-red-500 mt-1">{{ form.errors.jumlah }}</p>
                        </div>

                        <div>
                            <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                <StickyNote :size="12" class="text-zinc-400"/> Catatan (opsional)
                            </label>
                            <input type="text" v-model="form.catatan" placeholder="mis. makan siang kantor" :class="inputClass"/>
                        </div>

                        <button type="submit" :disabled="form.processing"
                            class="w-full font-semibold py-3 rounded-xl text-sm disabled:opacity-50 transition-colors flex items-center justify-center gap-2"
                            :class="activeType === 'income' ? 'bg-green-500 hover:bg-green-400 text-black' : 'bg-yellow-500 hover:bg-yellow-400 text-black'">
                            <Loader2 v-if="form.processing" :size="16" class="animate-spin"/>
                            <span>{{ form.processing ? 'Menyimpan...' : (activeType === 'income' ? 'Catat pemasukan' : 'Catat pengeluaran') }}</span>
                        </button>
                    </form>
                </CardContent>
            </Card>

            <!-- SUMMARY -->
            <div class="grid grid-cols-2 gap-2">
                <Card class="border-green-200 dark:border-green-700/30 bg-white dark:bg-zinc-900">
                    <CardContent class="p-3">
                        <p class="text-xs text-zinc-500 mb-1 flex items-center gap-1"><TrendingUp :size="11" class="text-green-500 dark:text-green-400"/> Pemasukan/bln</p>
                        <p class="text-sm font-semibold text-green-600 dark:text-green-400">{{ fmt(totalIncomeBulan) }}</p>
                    </CardContent>
                </Card>
                <Card class="border-red-200 dark:border-red-700/30 bg-white dark:bg-zinc-900">
                    <CardContent class="p-3">
                        <p class="text-xs text-zinc-500 mb-1 flex items-center gap-1"><TrendingDown :size="11" class="text-red-500 dark:text-red-400"/> Pengeluaran/bln</p>
                        <p class="text-sm font-semibold text-red-600 dark:text-red-400">{{ fmt(totalExpenseBulan) }}</p>
                    </CardContent>
                </Card>
                <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                    <CardContent class="p-3">
                        <p class="text-xs text-zinc-500 mb-1">Keluar hari ini</p>
                        <p class="text-sm font-semibold text-yellow-600 dark:text-yellow-400">{{ fmt(totalExpenseHariIni) }}</p>
                    </CardContent>
                </Card>
                <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                    <CardContent class="p-3">
                        <p class="text-xs text-zinc-500 mb-1">Rata-rata keluar/hari</p>
                        <p class="text-sm font-semibold text-orange-600 dark:text-orange-400">{{ fmt(rataRataHarian) }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- TREND CASHFLOW -->
            <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                <CardHeader class="pb-2 pt-4 px-4">
                    <div class="flex items-center justify-between gap-3">
                        <CardTitle class="text-xs text-zinc-500 uppercase tracking-widest flex items-center gap-1.5">
                            <BarChart3 :size="12"/> Tren 6 bulan
                        </CardTitle>
                        <div class="flex items-center gap-2">
                            <div class="flex bg-zinc-100 dark:bg-zinc-800 rounded-lg p-0.5">
                                <button @click="chartType='line'; buildChart()"
                                    class="px-2 py-0.5 text-xs rounded-md transition-colors"
                                    :class="chartType==='line' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-400'">
                                    Line
                                </button>
                                <button @click="chartType='bar'; buildChart()"
                                    class="px-2 py-0.5 text-xs rounded-md transition-colors"
                                    :class="chartType==='bar' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-400'">
                                    Bar
                                </button>
                            </div>
                            <Badge class="border text-xs"
                                :class="trendDiff >= 0
                                    ? 'bg-green-100 text-green-700 border-green-300 dark:bg-green-900 dark:text-green-400 dark:border-green-700'
                                    : 'bg-red-100 text-red-700 border-red-300 dark:bg-red-900 dark:text-red-400 dark:border-red-700'">
                                {{ trendDiff >= 0 ? '+' : '' }}{{ fmt(trendDiff) }}
                            </Badge>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="px-4 pb-4">
                    <div class="h-44">
                        <canvas ref="chartTrend"></canvas>
                    </div>
                    <div class="grid grid-cols-3 gap-2 mt-3 text-center">
                        <div>
                            <p class="text-xs text-zinc-500">Net bulan ini</p>
                            <p class="text-xs font-semibold" :class="trendNet >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">{{ trendNet >= 0 ? '+' : '' }}{{ fmt(trendNet) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-500">Pemasukan</p>
                            <p class="text-xs font-semibold text-green-600 dark:text-green-400">{{ fmt(totalIncomeBulan) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-500">Pengeluaran</p>
                            <p class="text-xs font-semibold text-red-600 dark:text-red-400">{{ fmt(totalExpenseBulan) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- BUDGET -->
            <Card class="border-yellow-200 dark:border-yellow-700/30 bg-white dark:bg-zinc-900">
                <CardHeader class="pb-2 pt-4 px-4">
                    <CardTitle class="text-xs text-yellow-600 dark:text-yellow-500 uppercase tracking-widest flex items-center gap-1.5">
                        <Target :size="12"/> Budget kategori
                    </CardTitle>
                </CardHeader>
                <CardContent class="px-4 pb-4 space-y-3">
                    <form @submit.prevent="submitBudget" class="grid grid-cols-[1fr_1fr_auto] gap-2">
                        <select v-model="budgetForm.kategori" :class="inputClass">
                            <option v-for="k in allExpenseKategori" :key="k" :value="k">{{ k }}</option>
                        </select>
                        <input type="number" v-model="budgetForm.limit_jumlah" min="0" placeholder="Limit Rp" :class="inputClass"/>
                        <button type="submit" :disabled="budgetForm.processing"
                            class="px-3 rounded-xl bg-yellow-500 hover:bg-yellow-400 text-black text-sm font-semibold disabled:opacity-50 flex items-center justify-center">
                            <Loader2 v-if="budgetForm.processing" :size="15" class="animate-spin"/>
                            <span v-else>Simpan</span>
                        </button>
                    </form>
                    <p v-if="budgetForm.errors.limit_jumlah" class="text-xs text-red-500">{{ budgetForm.errors.limit_jumlah }}</p>

                    <div v-if="totalBudget > 0" class="rounded-xl bg-zinc-100 dark:bg-zinc-800 p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-zinc-500 flex items-center gap-1.5"><PiggyBank :size="12"/> Total bulan ini</span>
                            <span class="text-xs font-semibold" :class="totalBudgetUsed > totalBudget ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-white'">
                                {{ fmt(totalBudgetUsed) }} / {{ fmt(totalBudget) }}
                            </span>
                        </div>
                        <Progress :model-value="totalBudgetPct" class="h-2 bg-zinc-200 dark:bg-zinc-700"/>
                        <p class="text-xs text-zinc-500 mt-2">Sisa budget: <span class="font-medium text-green-600 dark:text-green-400">{{ fmt(totalBudgetLeft) }}</span></p>
                    </div>

                    <div v-if="!budgetProgress.length" class="text-center py-4">
                        <Target :size="28" class="text-zinc-300 dark:text-zinc-700 mx-auto mb-2"/>
                        <p class="text-sm text-zinc-500">Belum ada budget kategori</p>
                    </div>

                    <div v-for="b in budgetProgress" :key="b.name" class="space-y-1.5">
                        <div class="flex justify-between items-center gap-2 text-sm">
                            <button type="button" @click="editBudget(b.name)" class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 hover:text-yellow-600 dark:hover:text-yellow-400 transition-colors">
                                <component :is="b.icon" :size="13" :class="b.color"/>
                                {{ b.name }}
                            </button>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium"
                                    :class="b.limit === 0 ? 'text-zinc-400 dark:text-zinc-500' : b.percent > 100 ? 'text-red-600 dark:text-red-400' : b.percent >= 80 ? 'text-orange-500 dark:text-orange-400' : 'text-zinc-600 dark:text-zinc-300'">
                                    {{ b.limit === 0 ? 'Tanpa limit' : b.percent + '%' }} · {{ fmt(b.spent) }}
                                </span>
                                <button v-if="b.limit > 0" type="button" @click="hapusBudget(b.name)"
                                    class="p-1 rounded text-zinc-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors">
                                    <X :size="11"/>
                                </button>
                            </div>
                        </div>
                        <div class="h-1.5 rounded-full overflow-hidden bg-zinc-200 dark:bg-zinc-800">
                            <div class="h-full rounded-full transition-all"
                                :style="{ width: (b.limit === 0 ? 0 : b.cappedPercent) + '%' }"
                                :class="b.percent > 100 ? 'bg-red-500' : b.percent >= 80 ? 'bg-orange-400' : 'bg-green-500'">
                            </div>
                        </div>
                        <div class="flex justify-between text-xs text-zinc-500">
                            <span>Limit {{ b.limit === 0 ? '—' : fmt(b.limit) }}</span>
                            <span :class="b.percent > 100 ? 'text-red-500 dark:text-red-400 font-medium' : ''">
                                {{ b.limit === 0 ? 'Belum ada limit' : (b.percent > 100 ? '⚠ Lewat ' + fmt(b.spent - b.limit) : 'Sisa ' + fmt(b.remaining)) }}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- BREAKDOWN EXPENSE -->
            <Card v-if="breakdownExpense.length" class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                <CardHeader class="pb-2 pt-4 px-4">
                    <CardTitle class="text-xs text-zinc-500 uppercase tracking-widest flex items-center gap-1.5">
                        <TrendingDown :size="12"/> Breakdown pengeluaran bulan ini
                    </CardTitle>
                </CardHeader>
                <CardContent class="px-4 pb-4 space-y-2.5">
                    <div v-for="b in breakdownExpense" :key="b.kategori">
                        <div class="flex justify-between items-center text-sm mb-1">
                            <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5">
                                <component :is="kategoriInfo('expense', b.kategori).icon" :size="13" :class="kategoriInfo('expense', b.kategori).color"/>
                                {{ b.kategori }}
                            </span>
                            <span class="font-medium" :class="kategoriInfo('expense', b.kategori).color">{{ fmt(b.total) }}</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-zinc-200 dark:bg-zinc-800 overflow-hidden">
                            <div class="h-full rounded-full bg-yellow-500/70 transition-all"
                                 :style="{ width: (b.total / maxKategori * 100) + '%' }"></div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- RIWAYAT -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs text-zinc-500 uppercase tracking-widest font-medium">Riwayat transaksi</p>
                    <button @click="exportKeuangan"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-xs transition-colors">
                        <Download :size="12"/>
                        Export CSV
                    </button>
                </div>

                <div v-if="!grouped.length" class="text-center py-12">
                    <Wallet :size="40" class="text-zinc-300 dark:text-zinc-700 mx-auto mb-3"/>
                    <p class="text-sm text-zinc-500">Belum ada transaksi dicatat</p>
                </div>

                <div v-for="day in grouped" :key="day.tanggal" class="mb-3">
                    <div class="flex justify-between items-center mb-1.5 px-1">
                        <span class="text-xs text-zinc-500 capitalize">{{ fmtTanggal(day.tanggal) }}</span>
                        <span class="text-xs font-medium" :class="day.net >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                            {{ day.net >= 0 ? '+' : '' }}{{ fmt(day.net) }}
                        </span>
                    </div>
                    <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                        <CardContent class="p-0">
                            <div v-for="(item, i) in day.items" :key="item.id"
                                 class="flex items-center gap-3 px-4 py-3"
                                 :class="i < day.items.length - 1 ? 'border-b border-zinc-100 dark:border-zinc-800' : ''">
                                <div class="w-8 h-8 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center shrink-0">
                                    <component :is="kategoriInfo(item.type, item.kategori).icon" :size="15" :class="kategoriInfo(item.type, item.kategori).color"/>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-zinc-700 dark:text-zinc-200">{{ item.kategori }}</p>
                                    <p v-if="item.catatan" class="text-xs text-zinc-500 truncate">{{ item.catatan }}</p>
                                </div>
                                <span class="text-sm font-medium shrink-0"
                                    :class="item.type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-zinc-900 dark:text-white'">
                                    {{ item.type === 'income' ? '+' : '-' }}{{ fmt(item.jumlah) }}
                                </span>
                                <button @click="hapus(item.id)"
                                    class="p-1.5 rounded-lg text-zinc-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors shrink-0">
                                    <Trash2 :size="14"/>
                                </button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

        <!-- Transaksi Berulang -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 flex items-center gap-2">
                    <Repeat2 :size="16" class="text-yellow-500"/>
                    Transaksi Berulang
                </h2>
                <div class="flex gap-2">
                    <button @click="applyRecurring"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-green-500 hover:bg-green-600 text-white text-xs font-medium transition-colors">
                        <RefreshCw :size="12"/>
                        Terapkan Bulan Ini
                    </button>
                    <button @click="showRecurringForm = !showRecurringForm"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-yellow-500 hover:bg-yellow-600 text-zinc-900 text-xs font-medium transition-colors">
                        <Plus :size="12"/>
                        Tambah
                    </button>
                </div>
            </div>

            <!-- Form tambah recurring -->
            <Card v-if="showRecurringForm" class="border-yellow-400/40 dark:border-yellow-600/30 bg-yellow-50/50 dark:bg-yellow-900/10">
                <CardContent class="p-4 space-y-3">
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Tambah Transaksi Berulang</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-zinc-500 dark:text-zinc-400 mb-1 block">Tipe</label>
                            <select v-model="recurringForm.type"
                                class="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                                <option value="expense">Pengeluaran</option>
                                <option value="income">Pemasukan</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-zinc-500 dark:text-zinc-400 mb-1 block">Kategori</label>
                            <select v-model="recurringForm.kategori"
                                class="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                                <option v-for="k in (recurringForm.type === 'income' ? allIncomeKategori : allExpenseKategori)" :key="k" :value="k">{{ k }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-zinc-500 dark:text-zinc-400 mb-1 block">Jumlah (Rp)</label>
                            <input v-model="recurringForm.jumlah" type="number" placeholder="0" min="1"
                                class="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400"/>
                            <p v-if="recurringForm.errors.jumlah" class="text-xs text-red-500 mt-1">{{ recurringForm.errors.jumlah }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-zinc-500 dark:text-zinc-400 mb-1 block">Catatan (opsional)</label>
                            <input v-model="recurringForm.catatan" type="text" placeholder="Contoh: Cicilan KPR"
                                class="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400"/>
                        </div>
                    </div>
                    <div class="flex gap-2 justify-end">
                        <button @click="showRecurringForm = false"
                            class="px-4 py-2 rounded-lg text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                            Batal
                        </button>
                        <button @click="submitRecurring" :disabled="recurringForm.processing"
                            class="px-4 py-2 rounded-lg bg-yellow-500 hover:bg-yellow-600 text-zinc-900 text-sm font-medium transition-colors disabled:opacity-50 flex items-center gap-1.5">
                            <Loader2 v-if="recurringForm.processing" :size="14" class="animate-spin"/>
                            Simpan
                        </button>
                    </div>
                </CardContent>
            </Card>

            <!-- List recurring -->
            <div v-if="recurrings.length === 0" class="text-center py-8 text-zinc-400 dark:text-zinc-600 text-sm">
                Belum ada transaksi berulang
            </div>
            <Card v-else class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                <CardContent class="p-0">
                    <div v-for="(r, i) in recurrings" :key="r.id"
                         class="flex items-center gap-3 px-4 py-3"
                         :class="i < recurrings.length - 1 ? 'border-b border-zinc-100 dark:border-zinc-800' : ''">
                        <div class="w-8 h-8 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center shrink-0">
                            <component :is="kategoriInfo(r.type, r.kategori).icon" :size="15" :class="kategoriInfo(r.type, r.kategori).color"/>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-zinc-700 dark:text-zinc-200">{{ r.kategori }}</p>
                            <p class="text-xs text-zinc-500">{{ r.catatan || (r.type === 'income' ? 'Pemasukan' : 'Pengeluaran') }} · {{ fmt(r.jumlah) }}</p>
                        </div>
                        <button @click="toggleRecurring(r.id)"
                            :class="r.aktif ? 'text-green-500 hover:text-green-600' : 'text-zinc-400 hover:text-zinc-500'"
                            class="p-1 transition-colors shrink-0" :title="r.aktif ? 'Aktif — klik untuk nonaktifkan' : 'Nonaktif — klik untuk aktifkan'">
                            <component :is="r.aktif ? ToggleRight : ToggleLeft" :size="22"/>
                        </button>
                        <button @click="hapusRecurring(r.id)"
                            class="p-1.5 rounded-lg text-zinc-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors shrink-0">
                            <Trash2 :size="14"/>
                        </button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Kategori Kustom -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 flex items-center gap-2">
                    <Tag :size="16" class="text-yellow-500"/>
                    Kategori Kustom
                </h2>
                <button @click="showCatForm = !showCatForm"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-yellow-500 hover:bg-yellow-600 text-zinc-900 text-xs font-medium transition-colors">
                    <Plus :size="12"/>
                    Tambah
                </button>
            </div>

            <!-- Form tambah kategori -->
            <Card v-if="showCatForm" class="border-yellow-400/40 dark:border-yellow-600/30 bg-yellow-50/50 dark:bg-yellow-900/10">
                <CardContent class="p-4 space-y-3">
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Tambah Kategori Baru</p>
                    <div class="flex gap-3">
                        <select v-model="catForm.type"
                            class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                            <option value="expense">Pengeluaran</option>
                            <option value="income">Pemasukan</option>
                        </select>
                        <input v-model="catForm.name" type="text" placeholder="Nama kategori" maxlength="50"
                            class="flex-1 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400"/>
                    </div>
                    <p v-if="catForm.errors.name" class="text-xs text-red-500">{{ catForm.errors.name }}</p>
                    <div class="flex gap-2 justify-end">
                        <button @click="showCatForm = false"
                            class="px-4 py-2 rounded-lg text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                            Batal
                        </button>
                        <button @click="submitCat" :disabled="catForm.processing || !catForm.name"
                            class="px-4 py-2 rounded-lg bg-yellow-500 hover:bg-yellow-600 text-zinc-900 text-sm font-medium transition-colors disabled:opacity-50 flex items-center gap-1.5">
                            <Loader2 v-if="catForm.processing" :size="14" class="animate-spin"/>
                            Simpan
                        </button>
                    </div>
                </CardContent>
            </Card>

            <!-- List custom categories -->
            <div v-if="customCats.length === 0" class="text-center py-6 text-zinc-400 dark:text-zinc-600 text-sm">
                Belum ada kategori kustom
            </div>
            <div v-else class="grid grid-cols-1 gap-3">
                <div v-for="tipe in ['expense', 'income']" :key="tipe">
                    <div v-if="customCats.filter(c => c.type === tipe).length > 0">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-2 uppercase tracking-wide">
                            {{ tipe === 'expense' ? 'Pengeluaran' : 'Pemasukan' }}
                        </p>
                        <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                            <CardContent class="p-0">
                                <div v-for="(cat, i) in customCats.filter(c => c.type === tipe)" :key="cat.id"
                                     class="flex items-center gap-3 px-4 py-3"
                                     :class="i < customCats.filter(c => c.type === tipe).length - 1 ? 'border-b border-zinc-100 dark:border-zinc-800' : ''">
                                    <Tag :size="14" :class="tipe === 'income' ? 'text-green-500' : 'text-orange-500'"/>
                                    <span class="flex-1 text-sm text-zinc-700 dark:text-zinc-200">{{ cat.name }}</span>
                                    <button @click="hapusCat(cat.id)"
                                        class="p-1.5 rounded-lg text-zinc-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors">
                                        <Trash2 :size="14"/>
                                    </button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>

        </div>
    </AuthenticatedLayout>
</template>
