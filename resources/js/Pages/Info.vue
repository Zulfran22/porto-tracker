<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import { Separator } from '@/Components/ui/separator'
import {
    FileText, Target, PiggyBank, Info,
    Lock, Coins, Shield, TrendingUp, Landmark,
    Clock, Building2, Hash, Calendar, Code2
} from 'lucide-vue-next'
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
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Hash :size="12" class="text-zinc-400"/> No. kontrak</span>
                        <span class="text-zinc-700 dark:text-zinc-300 text-xs font-mono">17805391142154415301</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Coins :size="12" class="text-yellow-500 dark:text-yellow-400"/> Emas cicilan</span>
                        <span class="text-yellow-500 dark:text-yellow-400 font-semibold">5,0000 gram</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Lock :size="12" class="text-yellow-600"/> Angsuran/bulan</span>
                        <span class="text-zinc-900 dark:text-white font-semibold">Rp1.032.662</span>
                    </div>
                    <Separator class="bg-zinc-200 dark:bg-zinc-800"/>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Clock :size="12" class="text-red-500 dark:text-red-400"/> Batas bayar</span>
                        <Badge class="bg-red-100 text-red-700 border-red-200 dark:bg-red-900 dark:text-red-400 dark:border-red-700 border text-xs">Tanggal 04 tiap bulan</Badge>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><PiggyBank :size="12" class="text-zinc-400"/> Sewa modal total</span>
                        <span class="text-zinc-700 dark:text-zinc-300">Rp2.033.750</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Calendar :size="12" class="text-zinc-400"/> Jatuh tempo</span>
                        <span class="text-zinc-700 dark:text-zinc-300">04 Juni 2027</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Building2 :size="12" class="text-zinc-400"/> Cabang</span>
                        <span class="text-zinc-700 dark:text-zinc-300">CP Bontang</span>
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
                        <span class="text-yellow-500 dark:text-yellow-400 font-semibold">Rp2.861.639/gram</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><TrendingUp :size="12" class="text-zinc-400"/> Harga sekarang</span>
                        <span class="text-zinc-700 dark:text-zinc-300">Rp2.545.000/gram</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><TrendingUp :size="12" class="text-orange-500 dark:text-orange-400"/> Perlu naik</span>
                        <span class="text-orange-500 dark:text-orange-400 font-semibold">~12,4% lagi</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Calendar :size="12" class="text-zinc-400"/> Estimasi BEP</span>
                        <Badge class="bg-green-100 text-green-700 border-green-200 dark:bg-green-900 dark:text-green-400 dark:border-green-700 border text-xs">1–2 tahun ke depan</Badge>
                    </div>
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
                        <span class="text-zinc-900 dark:text-white font-semibold">Rp3.000.000</span>
                    </div>
                    <Separator class="bg-zinc-200 dark:bg-zinc-800"/>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Lock :size="12" class="text-yellow-600"/> Cicilan emas</span>
                        <span class="text-yellow-600 font-medium">Rp1.032.662</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Coins :size="12" class="text-yellow-500 dark:text-yellow-400"/> Emas tunai</span>
                        <span class="text-yellow-500 dark:text-yellow-400 font-medium">~Rp786.935</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Shield :size="12" class="text-blue-500 dark:text-blue-400"/> Dana darurat</span>
                        <span class="text-blue-500 dark:text-blue-400 font-medium">~Rp491.835</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><TrendingUp :size="12" class="text-green-500 dark:text-green-400"/> Reksa dana</span>
                        <span class="text-green-500 dark:text-green-400 font-medium">~Rp393.468</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5"><Landmark :size="12" class="text-purple-500 dark:text-purple-400"/> SBN</span>
                        <span class="text-purple-500 dark:text-purple-400 font-medium">~Rp295.100</span>
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
                        <span class="text-zinc-900 dark:text-white font-semibold">Porto Tracker</span>
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
