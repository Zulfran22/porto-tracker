<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import { Progress } from '@/Components/ui/progress'
import { Separator } from '@/Components/ui/separator'
import {
    Coins, Tag, Save,
    CheckCircle2, Loader2
} from 'lucide-vue-next'
import { fmt, fmtJt } from '@/Composables/useCurrency'
import { DEFAULT_BUDGET } from '@/Composables/useFinanceConstants'

const props = defineProps({
    portofolios: { type: Array, default: () => [] },
    investmentTypes: { type: Array, default: () => [] },
    investmentTargets: { type: Array, default: () => [] },
    target: { type: Object, default: () => ({ budget_bulanan: DEFAULT_BUDGET }) },
    aktifKontrak: { type: Object, default: null },
})

const last = computed(() => props.portofolios?.at(-1) ?? null)

function findItem(items, name) {
    return items?.find(i => i.type_name === name) ?? null
}
function defaultTargetValue(unit) {
    return unit === 'gram' ? 1 : 1000000
}
function stepFor(unit) {
    return unit === 'gram' ? 1 : 1000000
}

const form = useForm({
    targets: props.investmentTypes.map(t => ({
        type_name: t.name,
        unit: t.unit,
        target_value: Number(props.investmentTargets.find(x => x.type_name === t.name)?.target_value
            ?? defaultTargetValue(t.unit)),
    })),
})

const saveTarget = () => form.put(route('target.update'))

// Gram & angsuran cicilan hanya berarti kalau user benar-benar punya kontrak
// aktif tercatat — tanpa itu 0, bukan menebak pakai kontrak siapa pun.
const hasKontrak     = computed(() => !!props.aktifKontrak)
const cicilanGram    = computed(() => hasKontrak.value ? Number(props.aktifKontrak.total_gram) : 0)

// Tanpa data portofolio, harga emas belum diketahui — jangan menebak angka, tampilkan "belum ada data".
const HARGA_EMAS = computed(() => last.value ? Number(last.value.harga_emas) : null)

// Estimasi "saving/bln" & "est. tercapai" di halaman ini pakai split rata budget
// ke SEMUA jenis investasi (termasuk emas) — beda dari simulator di Dashboard yang
// sengaja cuma mencakup jenis ber-Rupiah (emas butuh konversi harga/gram tersendiri).
const budgetBulanan = computed(() => Number(props.target?.budget_bulanan ?? DEFAULT_BUDGET))
const perTypeBulanan = computed(() =>
    form.targets.length > 0 ? Math.round(budgetBulanan.value / form.targets.length) : 0)

function sekarangUntuk(t) {
    if (!last.value) return t.unit === 'gram' ? cicilanGram.value : 0
    const item = findItem(last.value.items, t.type_name)
    if (t.unit === 'gram') return Number(item?.gram ?? 0) + cicilanGram.value
    return Number(item?.jumlah ?? 0)
}

function nilaiUntuk(t, targetValue) {
    if (t.unit === 'gram') return HARGA_EMAS.value !== null ? targetValue * HARGA_EMAS.value : null
    return targetValue
}

function bulanKe(n) {
    if (n === 0) return 'Tercapai!'
    const d = new Date()
    // Tanggal 29-31 bisa overflow saat pindah bulan (31 Jan + 1 bulan → 3 Mar);
    // patok ke tanggal 1 dulu karena yang ditampilkan hanya bulan & tahun.
    d.setDate(1)
    d.setMonth(d.getMonth() + n)
    return d.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
}

function estTercapaiUntuk(t, sisa) {
    if (sisa <= 0) return 0
    if (t.unit === 'gram') {
        if (HARGA_EMAS.value === null || perTypeBulanan.value <= 0) return null
        const gramPerBulan = perTypeBulanan.value / HARGA_EMAS.value
        return gramPerBulan > 0 ? Math.ceil(sisa / gramPerBulan) : null
    }
    return perTypeBulanan.value > 0 ? Math.ceil(sisa / perTypeBulanan.value) : null
}

const adaPerubahanBelumDisimpan = computed(() =>
    form.targets.some(t => {
        const saved = props.investmentTargets.find(x => x.type_name === t.type_name)
        return Number(t.target_value) !== Number(saved?.target_value ?? defaultTargetValue(t.unit))
    }))

// Ringkasan memakai target yang sudah tersimpan (props.investmentTargets), bukan
// nilai form yang mungkin sedang diubah tapi belum di-save.
const totalNilaiTargetTersimpan = computed(() => {
    let total = 0
    for (const t of props.investmentTypes) {
        const saved = props.investmentTargets.find(x => x.type_name === t.name)
        const val = Number(saved?.target_value ?? defaultTargetValue(t.unit))
        const nilai = nilaiUntuk(t, val)
        if (nilai === null) return null
        total += nilai
    }
    return total
})
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-lg mx-auto lg:max-w-3xl px-4 py-5 lg:py-8 space-y-3">

            <!-- HEADER -->
            <div class="flex justify-between items-center">
                <p class="text-xs text-zinc-500 uppercase tracking-widest font-medium">Target & progress</p>
                <button @click="saveTarget" :disabled="form.processing"
                    class="text-xs px-3 py-1.5 bg-indigo-500 hover:bg-indigo-400 active:bg-indigo-600 text-white rounded-lg font-semibold disabled:opacity-50 transition-colors flex items-center gap-1.5">
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

            <!-- TARGET PER JENIS INVESTASI -->
            <Card v-for="(t, i) in form.targets" :key="t.type_name"
                :class="t.unit === 'gram' ? 'border-yellow-300/60 dark:border-yellow-700/40' : 'border-zinc-200 dark:border-zinc-800'"
                class="bg-white dark:bg-zinc-900">
                <CardHeader class="pb-2 pt-4 px-4">
                    <div class="flex justify-between items-center">
                        <CardTitle class="text-xs uppercase tracking-widest flex items-center gap-1.5"
                            :class="t.unit === 'gram' ? 'text-yellow-600 dark:text-yellow-500' : 'text-zinc-500'">
                            <Coins v-if="t.unit === 'gram'" :size="12"/>
                            <Tag v-else :size="12"/>
                            Target {{ t.type_name }}
                        </CardTitle>
                        <div class="flex items-center gap-2">
                            <button @click="t.target_value = Math.max(t.unit === 'gram' ? 1 : 1000000, t.target_value - stepFor(t.unit))"
                                class="w-7 h-7 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-200 dark:hover:bg-zinc-700 flex items-center justify-center text-base transition-colors">−</button>
                            <span class="text-zinc-900 dark:text-white font-semibold text-xs w-16 text-center">
                                {{ t.unit === 'gram' ? `${t.target_value}g` : fmtJt(t.target_value) }}
                            </span>
                            <button @click="t.target_value += stepFor(t.unit)"
                                class="w-7 h-7 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-200 dark:hover:bg-zinc-700 flex items-center justify-center text-base transition-colors">+</button>
                        </div>
                    </div>
                    <p class="text-xs text-zinc-500 mt-1">
                        Saat ini {{ t.unit === 'gram' ? sekarangUntuk(t).toFixed(2) + ' gram' : fmt(sekarangUntuk(t)) }}
                        <template v-if="t.unit === 'gram' && hasKontrak">— termasuk {{ cicilanGram }}g dalam kontrak cicilan aktif</template>
                    </p>
                </CardHeader>
                <CardContent class="px-4 pb-4">
                    <div class="flex justify-between text-xs text-zinc-500 mb-1.5">
                        <span v-if="t.unit === 'gram'">{{ sekarangUntuk(t).toFixed(2) }}g / {{ t.target_value }}g</span>
                        <span v-else>{{ fmtJt(sekarangUntuk(t)) }} / {{ fmtJt(t.target_value) }}</span>
                        <Badge class="border text-xs"
                            :class="Math.min(100, Math.round(sekarangUntuk(t) / t.target_value * 100)) >= 100
                                ? 'bg-green-100 text-green-700 border-green-300 dark:bg-green-900 dark:text-green-400 dark:border-green-700'
                                : 'bg-zinc-100 text-zinc-700 border-zinc-300 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700'">
                            {{ Math.min(100, Math.round(sekarangUntuk(t) / t.target_value * 100)) }}%
                        </Badge>
                    </div>
                    <Progress :model-value="Math.min(100, Math.round(sekarangUntuk(t) / t.target_value * 100))" class="h-2.5 bg-zinc-200 dark:bg-zinc-800 mb-3"/>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-2.5">
                            <p class="text-xs text-zinc-500 mb-1">Sisa</p>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                                {{ t.unit === 'gram' ? Math.max(0, t.target_value - sekarangUntuk(t)).toFixed(2) + 'g' : fmtJt(Math.max(0, t.target_value - sekarangUntuk(t))) }}
                            </p>
                        </div>
                        <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-2.5">
                            <p class="text-xs text-zinc-500 mb-1">{{ t.unit === 'gram' ? 'Nilai target' : 'Saving/bln' }}</p>
                            <p class="text-sm font-semibold text-indigo-500 dark:text-indigo-400">
                                {{ t.unit === 'gram'
                                    ? (nilaiUntuk(t, t.target_value) !== null ? fmtJt(nilaiUntuk(t, t.target_value)) : 'Belum ada data')
                                    : fmt(perTypeBulanan) }}
                            </p>
                        </div>
                        <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-2.5">
                            <p class="text-xs text-zinc-500 mb-1">Est. tercapai</p>
                            <p class="text-xs font-semibold" :class="estTercapaiUntuk(t, t.target_value - sekarangUntuk(t)) === 0 ? 'text-green-500 dark:text-green-400' : 'text-zinc-600 dark:text-zinc-300'">
                                {{ estTercapaiUntuk(t, t.target_value - sekarangUntuk(t)) === null ? 'Belum ada data' : bulanKe(estTercapaiUntuk(t, t.target_value - sekarangUntuk(t))) }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <p v-if="!form.targets.length" class="text-sm text-zinc-400 text-center py-6">
                Belum ada jenis investasi — tambah lewat halaman Catat.
            </p>

            <!-- RINGKASAN TARGET TERSIMPAN -->
            <Card class="border-indigo-200 dark:border-indigo-700/20 bg-gradient-to-br from-indigo-50 to-white dark:from-indigo-950/20 dark:to-zinc-900">
                <CardHeader class="pb-2 pt-4 px-4">
                    <CardTitle class="text-xs text-indigo-600 dark:text-indigo-400 uppercase tracking-widest flex items-center gap-1.5">
                        <Save :size="12"/> Ringkasan target tersimpan
                    </CardTitle>
                </CardHeader>
                <CardContent class="px-4 pb-4 space-y-2.5">
                    <div v-for="t in investmentTypes" :key="t.id" class="flex justify-between text-sm items-center">
                        <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5">
                            <Coins v-if="t.unit === 'gram'" :size="12" class="text-yellow-500 dark:text-yellow-400"/>
                            <Tag v-else :size="12" class="text-indigo-500"/>
                            Target {{ t.name }}
                            <span v-if="t.unit === 'gram'">({{ investmentTargets.find(x => x.type_name === t.name)?.target_value ?? 1 }}g)</span>
                        </span>
                        <span class="font-semibold" :class="t.unit === 'gram' ? 'text-yellow-500 dark:text-yellow-400' : 'text-indigo-500 dark:text-indigo-400'">
                            {{ (() => {
                                const val = Number(investmentTargets.find(x => x.type_name === t.name)?.target_value ?? defaultTargetValue(t.unit))
                                const nilai = nilaiUntuk(t, val)
                                return nilai !== null ? fmtJt(nilai) : 'Belum ada data'
                            })() }}
                        </span>
                    </div>
                    <Separator class="bg-zinc-200 dark:bg-zinc-700"/>
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-600 dark:text-zinc-300 font-semibold">Total nilai target</span>
                        <span class="text-zinc-900 dark:text-white font-bold text-base">{{ totalNilaiTargetTersimpan !== null ? fmtJt(totalNilaiTargetTersimpan) : 'Belum ada data' }}</span>
                    </div>
                    <p v-if="adaPerubahanBelumDisimpan" class="text-xs text-zinc-400 dark:text-zinc-500 pt-1">
                        Ada perubahan target yang belum disimpan — tekan "Simpan target" untuk memperbaruinya.
                    </p>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 pt-1">
                        Estimasi dihitung dari budget {{ fmt(budgetBulanan) }}/bln dibagi rata ke {{ investmentTypes.length }} jenis investasi — atur budget lewat slider "Budget/bln" di Dashboard.
                    </p>
                </CardContent>
            </Card>

        </div>
    </AuthenticatedLayout>
</template>
