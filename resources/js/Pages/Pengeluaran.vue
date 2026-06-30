<script setup>
import { computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import {
    Receipt, Calendar, Tag, StickyNote, Trash2, Loader2,
    UtensilsCrossed, Car, ShoppingBag, Film, HeartPulse, MoreHorizontal,
    Wallet, TrendingDown
} from 'lucide-vue-next'

const props = defineProps({
    expenses: Array,
})

const KATEGORI = [
    { name: 'Makan',     icon: UtensilsCrossed, color: 'text-orange-400' },
    { name: 'Transport', icon: Car,             color: 'text-blue-400'   },
    { name: 'Belanja',   icon: ShoppingBag,     color: 'text-pink-400'   },
    { name: 'Hiburan',   icon: Film,            color: 'text-purple-400' },
    { name: 'Kesehatan', icon: HeartPulse,      color: 'text-red-400'    },
    { name: 'Lainnya',   icon: MoreHorizontal,  color: 'text-zinc-400'   },
]

function kategoriInfo(nama) {
    return KATEGORI.find(k => k.name === nama) ?? KATEGORI[KATEGORI.length - 1]
}

const now = new Date()
const todayStr = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0')

const form = useForm({
    tanggal:  todayStr,
    kategori: 'Makan',
    jumlah:   '',
    catatan:  '',
})

const submit = () => {
    form.post(route('pengeluaran.store'), {
        onSuccess: () => {
            form.reset('jumlah', 'catatan')
            form.kategori = 'Makan'
        },
    })
}

const hapus = (id) => {
    if (confirm('Hapus pengeluaran ini?')) router.delete(route('pengeluaran.destroy', id))
}

const fmt = (n) => 'Rp' + Math.round(n).toLocaleString('id-ID')

const currentMonth = todayStr.slice(0, 7)

const totalHariIni = computed(() =>
    props.expenses.filter(e => e.tanggal === todayStr)
        .reduce((sum, e) => sum + Number(e.jumlah), 0)
)

const bulanIni = computed(() =>
    props.expenses.filter(e => e.tanggal.startsWith(currentMonth))
)

const totalBulanIni = computed(() =>
    bulanIni.value.reduce((sum, e) => sum + Number(e.jumlah), 0)
)

const rataRataHarian = computed(() => {
    const hariBerjalan = now.getDate()
    return hariBerjalan > 0 ? totalBulanIni.value / hariBerjalan : 0
})

const breakdownKategori = computed(() => {
    const map = {}
    bulanIni.value.forEach(e => {
        map[e.kategori] = (map[e.kategori] ?? 0) + Number(e.jumlah)
    })
    return Object.entries(map)
        .map(([kategori, total]) => ({ kategori, total }))
        .sort((a, b) => b.total - a.total)
})

const maxKategori = computed(() =>
    breakdownKategori.value.length ? breakdownKategori.value[0].total : 0
)

const grouped = computed(() => {
    const map = {}
    props.expenses.forEach(e => {
        if (!map[e.tanggal]) map[e.tanggal] = []
        map[e.tanggal].push(e)
    })
    return Object.entries(map).map(([tanggal, items]) => ({
        tanggal,
        items,
        total: items.reduce((sum, e) => sum + Number(e.jumlah), 0),
    }))
})

function fmtTanggal(tgl) {
    const d = new Date(tgl + 'T00:00:00')
    return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
}

const inputClass = "w-full bg-zinc-800 border border-zinc-700 rounded-xl px-3 py-2.5 text-sm text-white focus:border-yellow-500 focus:ring-0 outline-none transition-colors placeholder:text-zinc-600"
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-lg mx-auto px-4 py-5 space-y-3">

            <div class="flex items-center justify-between mb-1">
                <p class="text-xs text-zinc-500 uppercase tracking-widest font-medium flex items-center gap-1.5">
                    <Receipt :size="12"/> Pengeluaran harian
                </p>
                <Badge class="bg-yellow-900/50 text-yellow-400 border-yellow-700 border text-xs">{{ fmt(totalHariIni) }} hari ini</Badge>
            </div>

            <div v-if="$page.props.flash?.success"
                 class="bg-green-950/50 border border-green-800 rounded-xl p-3 text-sm text-green-400">
                {{ $page.props.flash.success }}
            </div>

            <!-- QUICK ADD -->
            <Card class="border-yellow-700/30 bg-zinc-900">
                <CardHeader class="pb-2 pt-4 px-4">
                    <CardTitle class="text-xs text-yellow-500 uppercase tracking-widest flex items-center gap-1.5">
                        <Receipt :size="12"/> Catat pengeluaran
                    </CardTitle>
                </CardHeader>
                <CardContent class="px-4 pb-4">
                    <form @submit.prevent="submit" class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                    <Calendar :size="12" class="text-zinc-500"/> Tanggal
                                </label>
                                <input type="date" v-model="form.tanggal" :class="inputClass"/>
                            </div>
                            <div>
                                <label class="text-xs text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                    <Tag :size="12" class="text-zinc-500"/> Kategori
                                </label>
                                <select v-model="form.kategori" :class="inputClass">
                                    <option v-for="k in KATEGORI" :key="k.name" :value="k.name">{{ k.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                <Wallet :size="12" class="text-zinc-500"/> Jumlah (Rp)
                            </label>
                            <input type="number" v-model="form.jumlah" placeholder="mis. 25000" :class="inputClass"/>
                            <p v-if="form.errors.jumlah" class="text-xs text-red-400 mt-1">{{ form.errors.jumlah }}</p>
                        </div>

                        <div>
                            <label class="text-xs text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                <StickyNote :size="12" class="text-zinc-500"/> Catatan (opsional)
                            </label>
                            <input type="text" v-model="form.catatan" placeholder="mis. makan siang kantor" :class="inputClass"/>
                        </div>

                        <button type="submit" :disabled="form.processing"
                            class="w-full bg-yellow-500 hover:bg-yellow-400 text-black font-semibold py-3 rounded-xl text-sm disabled:opacity-50 transition-colors flex items-center justify-center gap-2">
                            <Loader2 v-if="form.processing" :size="16" class="animate-spin"/>
                            <span>{{ form.processing ? 'Menyimpan...' : 'Catat pengeluaran' }}</span>
                        </button>
                    </form>
                </CardContent>
            </Card>

            <!-- SUMMARY -->
            <div class="grid grid-cols-3 gap-2">
                <Card class="border-zinc-800 bg-zinc-900">
                    <CardContent class="p-3">
                        <p class="text-xs text-zinc-500 mb-1">Hari ini</p>
                        <p class="text-sm font-semibold text-yellow-400">{{ fmt(totalHariIni) }}</p>
                    </CardContent>
                </Card>
                <Card class="border-zinc-800 bg-zinc-900">
                    <CardContent class="p-3">
                        <p class="text-xs text-zinc-500 mb-1">Bulan ini</p>
                        <p class="text-sm font-semibold text-white">{{ fmt(totalBulanIni) }}</p>
                    </CardContent>
                </Card>
                <Card class="border-zinc-800 bg-zinc-900">
                    <CardContent class="p-3">
                        <p class="text-xs text-zinc-500 mb-1">Rata-rata/hari</p>
                        <p class="text-sm font-semibold text-orange-400">{{ fmt(rataRataHarian) }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- BREAKDOWN -->
            <Card v-if="breakdownKategori.length" class="border-zinc-800 bg-zinc-900">
                <CardHeader class="pb-2 pt-4 px-4">
                    <CardTitle class="text-xs text-zinc-500 uppercase tracking-widest flex items-center gap-1.5">
                        <TrendingDown :size="12"/> Breakdown kategori bulan ini
                    </CardTitle>
                </CardHeader>
                <CardContent class="px-4 pb-4 space-y-2.5">
                    <div v-for="b in breakdownKategori" :key="b.kategori">
                        <div class="flex justify-between items-center text-sm mb-1">
                            <span class="text-zinc-400 flex items-center gap-1.5">
                                <component :is="kategoriInfo(b.kategori).icon" :size="13" :class="kategoriInfo(b.kategori).color"/>
                                {{ b.kategori }}
                            </span>
                            <span class="font-medium" :class="kategoriInfo(b.kategori).color">{{ fmt(b.total) }}</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-zinc-800 overflow-hidden">
                            <div class="h-full rounded-full bg-yellow-500/70 transition-all"
                                 :style="{ width: (b.total / maxKategori * 100) + '%' }"></div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- RIWAYAT -->
            <div>
                <p class="text-xs text-zinc-500 uppercase tracking-widest mb-3 font-medium">Riwayat pengeluaran</p>

                <div v-if="!grouped.length" class="text-center py-12">
                    <Receipt :size="40" class="text-zinc-700 mx-auto mb-3"/>
                    <p class="text-sm text-zinc-500">Belum ada pengeluaran dicatat</p>
                </div>

                <div v-for="day in grouped" :key="day.tanggal" class="mb-3">
                    <div class="flex justify-between items-center mb-1.5 px-1">
                        <span class="text-xs text-zinc-500 capitalize">{{ fmtTanggal(day.tanggal) }}</span>
                        <span class="text-xs font-medium text-zinc-400">{{ fmt(day.total) }}</span>
                    </div>
                    <Card class="border-zinc-800 bg-zinc-900">
                        <CardContent class="p-0">
                            <div v-for="(item, i) in day.items" :key="item.id"
                                 class="flex items-center gap-3 px-4 py-3"
                                 :class="i < day.items.length - 1 ? 'border-b border-zinc-800' : ''">
                                <div class="w-8 h-8 rounded-lg bg-zinc-800 flex items-center justify-center shrink-0">
                                    <component :is="kategoriInfo(item.kategori).icon" :size="15" :class="kategoriInfo(item.kategori).color"/>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-zinc-200">{{ item.kategori }}</p>
                                    <p v-if="item.catatan" class="text-xs text-zinc-500 truncate">{{ item.catatan }}</p>
                                </div>
                                <span class="text-sm font-medium text-white shrink-0">{{ fmt(item.jumlah) }}</span>
                                <button @click="hapus(item.id)"
                                    class="p-1.5 rounded-lg text-zinc-600 hover:text-red-400 hover:bg-red-950/30 transition-colors shrink-0">
                                    <Trash2 :size="14"/>
                                </button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>