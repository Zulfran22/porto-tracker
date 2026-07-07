<script setup>
import { computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import { Separator } from '@/Components/ui/separator'
import {
    FileText, Target, PiggyBank, Info,
    Lock, Coins, TrendingUp, Tag,
    Clock, Calendar, Code2, Hash
} from 'lucide-vue-next'
import { fmt } from '@/Composables/useCurrency'
import { DEFAULT_BUDGET, hitungAlokasiBulanan } from '@/Composables/useFinanceConstants'

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

// Budget bulanan tersimpan (diatur lewat slider di Dashboard) — sumber yang
// sama dengan Dashboard & Target. Cicilan cuma dikurangi dari budget kalau nyata.
// investmentTypes di sini sudah difilter ke unit='rupiah' oleh controller.
const alokasi = hitungAlokasiBulanan(props.budgetBulanan, cicilanBulanan.value, props.investmentTypes.length)
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

            <!-- RENCANA SAVING -->
            <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                <CardHeader class="pb-2 pt-4 px-4">
                    <CardTitle class="text-xs text-zinc-500 uppercase tracking-widest flex items-center gap-1.5">
                        <PiggyBank :size="12"/> Rencana saving/bulan
                    </CardTitle>
                </CardHeader>
                <CardContent class="px-4 pb-4 space-y-2.5">
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400">Budget total</span>
                        <span class="text-zinc-900 dark:text-white font-semibold">{{ fmt(budgetBulanan) }}</span>
                    </div>
                    <Separator class="bg-zinc-200 dark:bg-zinc-800"/>
                    <div v-if="hasKontrak" class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Lock :size="12" class="text-yellow-600"/> Cicilan emas</span>
                        <span class="text-yellow-600 font-medium">{{ fmt(cicilanBulanan) }}</span>
                    </div>
                    <div v-for="t in investmentTypes" :key="t.id" class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Tag :size="12" class="text-indigo-500"/> {{ t.name }}</span>
                        <span class="text-indigo-500 dark:text-indigo-400 font-medium">~{{ fmt(alokasi.perType) }}</span>
                    </div>
                    <p v-if="!investmentTypes.length" class="text-sm text-zinc-400 text-center py-2">
                        Belum ada jenis investasi ber-Rupiah.
                    </p>
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
