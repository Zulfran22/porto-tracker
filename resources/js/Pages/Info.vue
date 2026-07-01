<script setup>
import { computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import { Separator } from '@/Components/ui/separator'
import {
    FileText, Target, PiggyBank, Info,
    Lock, Coins, Shield, TrendingUp, Landmark,
    Clock, Calendar, Code2, Hash
} from 'lucide-vue-next'
import { CICILAN, CICILAN_GRAM, BEP, DUE_DATE_DAY, DEFAULT_BUDGET, hitungAlokasiBulanan } from '@/Composables/useFinanceConstants'

const props = defineProps({
    lastHargaEmas: { type: Number, default: null },
    lastCicilan:   { type: Number, default: null },
})

const fmt = (n) => 'Rp' + Math.round(n).toLocaleString('id-ID')

const cicilanBulanan = computed(() => props.lastCicilan ?? CICILAN)
const hargaSekarang  = computed(() => props.lastHargaEmas)
const bepGap         = computed(() => hargaSekarang.value
    ? Math.max(0, Math.round((BEP - hargaSekarang.value) / BEP * 1000) / 10)
    : null)

const alokasi = hitungAlokasiBulanan()
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
                <CardContent class="px-4 pb-4 space-y-2.5">
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Coins :size="12" class="text-yellow-500 dark:text-yellow-400"/> Emas cicilan</span>
                        <span class="text-yellow-500 dark:text-yellow-400 font-semibold">{{ CICILAN_GRAM.toFixed(4) }} gram</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Lock :size="12" class="text-yellow-600"/> Angsuran/bulan</span>
                        <span class="text-zinc-900 dark:text-white font-semibold">{{ fmt(cicilanBulanan) }}</span>
                    </div>
                    <Separator class="bg-zinc-200 dark:bg-zinc-800"/>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Clock :size="12" class="text-red-500 dark:text-red-400"/> Batas bayar</span>
                        <Badge class="bg-red-100 text-red-700 border-red-200 dark:bg-red-900 dark:text-red-400 dark:border-red-700 border text-xs">Tanggal {{ String(DUE_DATE_DAY).padStart(2, '0') }} tiap bulan</Badge>
                    </div>
                </CardContent>
            </Card>

            <!-- BEP -->
            <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                <CardHeader class="pb-2 pt-4 px-4">
                    <CardTitle class="text-xs text-zinc-500 uppercase tracking-widest flex items-center gap-1.5">
                        <Target :size="12"/> Target BEP
                    </CardTitle>
                </CardHeader>
                <CardContent class="px-4 pb-4 space-y-2.5">
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Target :size="12" class="text-yellow-500 dark:text-yellow-400"/> BEP cicilan</span>
                        <span class="text-yellow-500 dark:text-yellow-400 font-semibold">{{ fmt(BEP) }}/gram</span>
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
                        <span class="text-zinc-900 dark:text-white font-semibold">{{ fmt(DEFAULT_BUDGET) }}</span>
                    </div>
                    <Separator class="bg-zinc-200 dark:bg-zinc-800"/>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Lock :size="12" class="text-yellow-600"/> Cicilan emas</span>
                        <span class="text-yellow-600 font-medium">{{ fmt(cicilanBulanan) }}</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Coins :size="12" class="text-yellow-500 dark:text-yellow-400"/> Emas tunai</span>
                        <span class="text-yellow-500 dark:text-yellow-400 font-medium">~{{ fmt(alokasi.emas) }}</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Shield :size="12" class="text-blue-500 dark:text-blue-400"/> Dana darurat</span>
                        <span class="text-blue-500 dark:text-blue-400 font-medium">~{{ fmt(alokasi.darurat) }}</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><TrendingUp :size="12" class="text-green-500 dark:text-green-400"/> Reksa dana</span>
                        <span class="text-green-500 dark:text-green-400 font-medium">~{{ fmt(alokasi.reksa) }}</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Landmark :size="12" class="text-purple-500 dark:text-purple-400"/> SBN</span>
                        <span class="text-purple-500 dark:text-purple-400 font-medium">~{{ fmt(alokasi.sbn) }}</span>
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
