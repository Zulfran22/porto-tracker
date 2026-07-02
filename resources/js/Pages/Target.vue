<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import { Progress } from '@/Components/ui/progress'
import { Separator } from '@/Components/ui/separator'
import {
    Coins, Shield, TrendingUp, Save,
    CheckCircle2, Loader2
} from 'lucide-vue-next'
import { CICILAN, CICILAN_GRAM, DEFAULT_BUDGET, hitungAlokasiBulanan } from '@/Composables/useFinanceConstants'

const props = defineProps({
    portofolios: Array,
    target: Object,
    aktifKontrak: { type: Object, default: null },
})

const last = computed(() => props.portofolios?.at(-1) ?? null)

const fmt   = (n) => 'Rp' + Math.round(n).toLocaleString('id-ID')
const fmtJt = (n) => 'Rp' + (n / 1000000).toFixed(2) + 'jt'

const form = useForm({
    target_emas:    props.target.target_emas,
    target_darurat: props.target.target_darurat,
    target_reksa:   props.target.target_reksa,
})

const saveTarget = () => form.put(route('target.update'))

// Gram & angsuran cicilan diambil dari kontrak aktif kalau ada; kalau tidak, jatuh ke konstanta
// statis sebagai estimasi (isCicilanEstimasi) agar tidak tampil seperti data nyata.
const cicilanGram       = computed(() => props.aktifKontrak ? Number(props.aktifKontrak.total_gram) : CICILAN_GRAM)
const cicilanBulanan    = computed(() => props.aktifKontrak ? Number(props.aktifKontrak.angsuran_bulan) : CICILAN)
const isCicilanEstimasi = computed(() => !props.aktifKontrak)

// Tanpa data portofolio, harga emas belum diketahui — jangan menebak angka, tampilkan "belum ada data".
const HARGA_EMAS   = computed(() => last.value ? Number(last.value.harga_emas) : null)
const alokasi = hitungAlokasiBulanan(DEFAULT_BUDGET, cicilanBulanan.value)

const emasSekarang    = computed(() => last.value ? Number(last.value.emas_gram) + cicilanGram.value : cicilanGram.value)
const daruratSekarang = computed(() => last.value ? Number(last.value.dana_darurat) : 0)
const reksaSekarang   = computed(() => last.value ? Number(last.value.reksa_dana) : 0)

const pctEmas    = computed(() => Math.min(100, Math.round(emasSekarang.value / form.target_emas * 100)))
const pctDarurat = computed(() => Math.min(100, Math.round(daruratSekarang.value / form.target_darurat * 100)))
const pctReksa   = computed(() => Math.min(100, Math.round(reksaSekarang.value / form.target_reksa * 100)))

const sisaEmas    = computed(() => Math.max(0, form.target_emas - emasSekarang.value))
const sisaDarurat = computed(() => Math.max(0, form.target_darurat - daruratSekarang.value))
const sisaReksa   = computed(() => Math.max(0, form.target_reksa - reksaSekarang.value))

// Alokasi emas (Rupiah/bulan) dikonversi ke gram/bulan pakai harga emas terakhir —
// sebelumnya dihitung pakai asumsi tetap 0.5 gram/bulan yang tidak nyambung dengan alokasi budget.
const gramEmasPerBulan = computed(() => HARGA_EMAS.value ? alokasi.emas / HARGA_EMAS.value : 0)
const sisaBulanEmas    = computed(() => {
    if (sisaEmas.value <= 0) return 0
    return gramEmasPerBulan.value > 0 ? Math.ceil(sisaEmas.value / gramEmasPerBulan.value) : null
})
const sisaBulanDarurat = computed(() => sisaDarurat.value > 0 ? Math.ceil(sisaDarurat.value / alokasi.darurat) : 0)
const sisaBulanReksa   = computed(() => sisaReksa.value > 0 ? Math.ceil(sisaReksa.value / alokasi.reksa) : 0)

function bulanKe(n) {
    if (n === 0) return 'Tercapai!'
    const d = new Date()
    d.setMonth(d.getMonth() + n)
    return d.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
}

const nilaiTargetEmas = computed(() => HARGA_EMAS.value !== null ? form.target_emas * HARGA_EMAS.value : null)
const nilaiEmasSkrg   = computed(() => HARGA_EMAS.value !== null ? emasSekarang.value * HARGA_EMAS.value : null)
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-lg mx-auto lg:max-w-3xl px-4 py-5 lg:py-8 space-y-3">

            <!-- HEADER -->
            <div class="flex justify-between items-center">
                <p class="text-xs text-zinc-500 uppercase tracking-widest font-medium">Target & progress</p>
                <button @click="saveTarget" :disabled="form.processing"
                    class="text-xs px-3 py-1.5 bg-yellow-500 hover:bg-yellow-400 text-black rounded-lg font-semibold disabled:opacity-50 transition-colors flex items-center gap-1.5">
                    <Loader2 v-if="form.processing" :size="12" class="animate-spin"/>
                    <Save v-else :size="12"/>
                    <span>{{ form.processing ? 'Menyimpan...' : 'Simpan target' }}</span>
                </button>
            </div>

            <!-- FLASH -->
            <div v-if="$page.props.flash?.success"
                 class="bg-green-50 dark:bg-green-950/50 border border-green-200 dark:border-green-800 rounded-xl p-3 text-sm text-green-700 dark:text-green-400 flex items-center gap-2">
                <CheckCircle2 :size="14"/> {{ $page.props.flash.success }}
            </div>

            <!-- TARGET EMAS -->
            <Card class="border-yellow-300/60 dark:border-yellow-700/40 bg-white dark:bg-zinc-900">
                <CardHeader class="pb-2 pt-4 px-4">
                    <div class="flex justify-between items-center">
                        <CardTitle class="text-xs text-yellow-600 dark:text-yellow-500 uppercase tracking-widest flex items-center gap-1.5">
                            <Coins :size="12"/> Target emas tunai
                        </CardTitle>
                        <div class="flex items-center gap-2">
                            <button @click="form.target_emas = Math.max(1, form.target_emas - 1)"
                                class="w-7 h-7 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-200 dark:hover:bg-zinc-700 flex items-center justify-center text-base transition-colors">−</button>
                            <span class="text-zinc-900 dark:text-white font-semibold text-sm w-10 text-center">{{ form.target_emas }}g</span>
                            <button @click="form.target_emas++"
                                class="w-7 h-7 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-200 dark:hover:bg-zinc-700 flex items-center justify-center text-base transition-colors">+</button>
                        </div>
                    </div>
                    <p class="text-xs text-zinc-500 mt-1">Saat ini {{ emasSekarang.toFixed(2) }} gram (termasuk {{ cicilanGram }}g cicilan{{ isCicilanEstimasi ? ' — estimasi' : '' }})</p>
                </CardHeader>
                <CardContent class="px-4 pb-4">
                    <div class="flex justify-between text-xs text-zinc-500 mb-1.5">
                        <span>{{ emasSekarang.toFixed(2) }}g / {{ form.target_emas }}g</span>
                        <Badge class="border text-xs"
                            :class="pctEmas >= 100
                                ? 'bg-green-100 text-green-700 border-green-300 dark:bg-green-900 dark:text-green-400 dark:border-green-700'
                                : 'bg-yellow-100 text-yellow-700 border-yellow-300 dark:bg-yellow-900 dark:text-yellow-400 dark:border-yellow-700'">
                            {{ pctEmas }}%
                        </Badge>
                    </div>
                    <Progress :model-value="pctEmas" class="h-2.5 bg-zinc-200 dark:bg-zinc-800 mb-3"/>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-2.5">
                            <p class="text-xs text-zinc-500 mb-1">Sisa</p>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ sisaEmas.toFixed(2) }}g</p>
                        </div>
                        <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-2.5">
                            <p class="text-xs text-zinc-500 mb-1">Nilai target</p>
                            <p class="text-sm font-semibold text-yellow-500 dark:text-yellow-400">{{ nilaiTargetEmas !== null ? fmtJt(nilaiTargetEmas) : 'Belum ada data' }}</p>
                        </div>
                        <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-2.5">
                            <p class="text-xs text-zinc-500 mb-1">Est. tercapai</p>
                            <p class="text-xs font-semibold" :class="sisaBulanEmas === 0 ? 'text-green-500 dark:text-green-400' : 'text-zinc-600 dark:text-zinc-300'">
                                {{ sisaBulanEmas === null ? 'Belum ada data' : bulanKe(sisaBulanEmas) }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- TARGET DANA DARURAT -->
            <Card class="border-blue-200 dark:border-blue-700/30 bg-white dark:bg-zinc-900">
                <CardHeader class="pb-2 pt-4 px-4">
                    <div class="flex justify-between items-center">
                        <CardTitle class="text-xs text-blue-600 dark:text-blue-400 uppercase tracking-widest flex items-center gap-1.5">
                            <Shield :size="12"/> Target dana darurat
                        </CardTitle>
                        <div class="flex items-center gap-2">
                            <button @click="form.target_darurat = Math.max(1000000, form.target_darurat - 1000000)"
                                class="w-7 h-7 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-200 dark:hover:bg-zinc-700 flex items-center justify-center text-base transition-colors">−</button>
                            <span class="text-zinc-900 dark:text-white font-semibold text-xs w-16 text-center">{{ fmtJt(form.target_darurat) }}</span>
                            <button @click="form.target_darurat += 1000000"
                                class="w-7 h-7 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-200 dark:hover:bg-zinc-700 flex items-center justify-center text-base transition-colors">+</button>
                        </div>
                    </div>
                    <p class="text-xs text-zinc-500 mt-1">Saat ini {{ fmt(daruratSekarang) }}</p>
                </CardHeader>
                <CardContent class="px-4 pb-4">
                    <div class="flex justify-between text-xs text-zinc-500 mb-1.5">
                        <span>{{ fmtJt(daruratSekarang) }} / {{ fmtJt(form.target_darurat) }}</span>
                        <Badge class="border text-xs"
                            :class="pctDarurat >= 100
                                ? 'bg-green-100 text-green-700 border-green-300 dark:bg-green-900 dark:text-green-400 dark:border-green-700'
                                : 'bg-blue-100 text-blue-700 border-blue-300 dark:bg-blue-900 dark:text-blue-400 dark:border-blue-700'">
                            {{ pctDarurat }}%
                        </Badge>
                    </div>
                    <Progress :model-value="pctDarurat" class="h-2.5 bg-zinc-200 dark:bg-zinc-800 mb-3"/>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-2.5">
                            <p class="text-xs text-zinc-500 mb-1">Sisa</p>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ fmtJt(sisaDarurat) }}</p>
                        </div>
                        <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-2.5">
                            <p class="text-xs text-zinc-500 mb-1">Saving/bln</p>
                            <p class="text-sm font-semibold text-blue-500 dark:text-blue-400">{{ fmt(alokasi.darurat) }}</p>
                        </div>
                        <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-2.5">
                            <p class="text-xs text-zinc-500 mb-1">Est. tercapai</p>
                            <p class="text-xs font-semibold" :class="sisaBulanDarurat === 0 ? 'text-green-500 dark:text-green-400' : 'text-zinc-600 dark:text-zinc-300'">
                                {{ bulanKe(sisaBulanDarurat) }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- TARGET REKSA DANA -->
            <Card class="border-green-200 dark:border-green-700/30 bg-white dark:bg-zinc-900">
                <CardHeader class="pb-2 pt-4 px-4">
                    <div class="flex justify-between items-center">
                        <CardTitle class="text-xs text-green-600 dark:text-green-400 uppercase tracking-widest flex items-center gap-1.5">
                            <TrendingUp :size="12"/> Target reksa dana
                        </CardTitle>
                        <div class="flex items-center gap-2">
                            <button @click="form.target_reksa = Math.max(1000000, form.target_reksa - 5000000)"
                                class="w-7 h-7 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-200 dark:hover:bg-zinc-700 flex items-center justify-center text-base transition-colors">−</button>
                            <span class="text-zinc-900 dark:text-white font-semibold text-xs w-16 text-center">{{ fmtJt(form.target_reksa) }}</span>
                            <button @click="form.target_reksa += 5000000"
                                class="w-7 h-7 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-200 dark:hover:bg-zinc-700 flex items-center justify-center text-base transition-colors">+</button>
                        </div>
                    </div>
                    <p class="text-xs text-zinc-500 mt-1">Saat ini {{ fmtJt(reksaSekarang) }}</p>
                </CardHeader>
                <CardContent class="px-4 pb-4">
                    <div class="flex justify-between text-xs text-zinc-500 mb-1.5">
                        <span>{{ fmtJt(reksaSekarang) }} / {{ fmtJt(form.target_reksa) }}</span>
                        <Badge class="border text-xs"
                            :class="pctReksa >= 100
                                ? 'bg-green-100 text-green-700 border-green-300 dark:bg-green-900 dark:text-green-400 dark:border-green-700'
                                : 'bg-green-50 text-green-600 border-green-200 dark:bg-green-950 dark:text-green-500 dark:border-green-800'">
                            {{ pctReksa }}%
                        </Badge>
                    </div>
                    <Progress :model-value="pctReksa" class="h-2.5 bg-zinc-200 dark:bg-zinc-800 mb-3"/>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-2.5">
                            <p class="text-xs text-zinc-500 mb-1">Sisa</p>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ fmtJt(sisaReksa) }}</p>
                        </div>
                        <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-2.5">
                            <p class="text-xs text-zinc-500 mb-1">Saving/bln</p>
                            <p class="text-sm font-semibold text-green-500 dark:text-green-400">{{ fmt(alokasi.reksa) }}</p>
                        </div>
                        <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-2.5">
                            <p class="text-xs text-zinc-500 mb-1">Est. tercapai</p>
                            <p class="text-xs font-semibold" :class="sisaBulanReksa === 0 ? 'text-green-500 dark:text-green-400' : 'text-zinc-600 dark:text-zinc-300'">
                                {{ bulanKe(sisaBulanReksa) }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- RINGKASAN -->
            <Card class="border-yellow-200 dark:border-yellow-700/20 bg-gradient-to-br from-yellow-50 to-white dark:from-yellow-950/20 dark:to-zinc-900">
                <CardHeader class="pb-2 pt-4 px-4">
                    <CardTitle class="text-xs text-yellow-600 dark:text-yellow-500 uppercase tracking-widest flex items-center gap-1.5">
                        <Coins :size="12"/> Ringkasan nilai saat ini
                    </CardTitle>
                </CardHeader>
                <CardContent v-if="last" class="px-4 pb-4 space-y-2.5">
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5">
                            <Coins :size="12" class="text-yellow-500 dark:text-yellow-400"/> Nilai emas ({{ emasSekarang.toFixed(2) }}g)
                        </span>
                        <span class="text-yellow-500 dark:text-yellow-400 font-semibold">{{ fmtJt(nilaiEmasSkrg) }}</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5">
                            <Shield :size="12" class="text-blue-500 dark:text-blue-400"/> Dana darurat
                        </span>
                        <span class="text-blue-500 dark:text-blue-400 font-semibold">{{ fmt(daruratSekarang) }}</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5">
                            <TrendingUp :size="12" class="text-green-500 dark:text-green-400"/> Reksa dana
                        </span>
                        <span class="text-green-500 dark:text-green-400 font-semibold">{{ fmt(reksaSekarang) }}</span>
                    </div>
                    <Separator class="bg-zinc-200 dark:bg-zinc-700"/>
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-600 dark:text-zinc-300 font-semibold">Total aset</span>
                        <span class="text-zinc-900 dark:text-white font-bold text-base">{{ fmtJt(nilaiEmasSkrg + daruratSekarang + reksaSekarang) }}</span>
                    </div>
                </CardContent>
                <CardContent v-else class="px-4 pb-4">
                    <p class="text-sm text-zinc-400 text-center py-3">Belum ada data. Isi lewat halaman Catat.</p>
                </CardContent>
            </Card>

        </div>
    </AuthenticatedLayout>
</template>
