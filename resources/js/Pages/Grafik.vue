<script setup>
import { onMounted, onBeforeUnmount, ref, computed, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import { Progress } from '@/Components/ui/progress'
import { Chart, registerables } from 'chart.js'
import { useTheme } from '@/Composables/useTheme'
import { fmt, fmtJt } from '@/Composables/useCurrency'
import { CICILAN_GRAM, BEP } from '@/Composables/useFinanceConstants'
Chart.register(...registerables)

const props = defineProps({
    portofolios: { type: Array, default: () => [] },
    aktifKontrak: { type: Object, default: null },
})
const { isDark } = useTheme()

const chartTotal  = ref(null)
const chartHarga  = ref(null)
const chartEmas   = ref(null)
const chartInvest = ref(null)

// Gram cicilan diambil dari kontrak aktif kalau ada; kalau tidak, jatuh ke konstanta
// statis sebagai estimasi (isCicilanEstimasi) agar tidak tampil seperti data nyata.
const cicilanGram       = computed(() => props.aktifKontrak ? Number(props.aktifKontrak.total_gram) : CICILAN_GRAM)
const isCicilanEstimasi = computed(() => !props.aktifKontrak)
const bepTarget         = computed(() => props.aktifKontrak ? Number(props.aktifKontrak.bep_per_gram) : BEP)

// Total per bulan dihitung di backend (Portofolio::getTotalAttribute) agar satu sumber
// kebenaran dengan gram cicilan dari kontrak aktif — lihat app/Models/Portofolio.php.
const last       = computed(() => props.portofolios.at(-1) ?? null)
const first      = computed(() => props.portofolios.at(0) ?? null)
const totalLast  = computed(() => Number(last.value?.total ?? 0))
const totalFirst = computed(() => Number(first.value?.total ?? 0))
const growthPct  = computed(() => {
    if (!first.value || totalFirst.value === 0) return 0
    return ((totalLast.value - totalFirst.value) / totalFirst.value * 100).toFixed(1)
})
const totalEmasGram  = computed(() => last.value ? (Number(last.value.emas_gram) + cicilanGram.value).toFixed(2) : 0)
const hargaSekarang  = computed(() => last.value ? Number(last.value.harga_emas) : 0)
const bepPct         = computed(() => Math.min(100, Math.round(hargaSekarang.value / bepTarget.value * 100)))
const bepSisa        = computed(() => Math.max(0, bepTarget.value - hargaSekarang.value))

let totalChart = null
let hargaChart = null
let emasChart = null
let investChart = null

function destroyCharts() {
    totalChart?.destroy()
    hargaChart?.destroy()
    emasChart?.destroy()
    investChart?.destroy()
}

function buildCharts() {
    if (!props.portofolios.length) return
    destroyCharts()

    const dark   = isDark.value
    const gridC  = dark ? '#27272a' : '#e4e4e7'
    const txtC   = dark ? '#71717a' : '#52525b'
    const ptBdr  = dark ? '#09090b' : '#ffffff'

    const labels  = props.portofolios.map(d => d.bulan)
    const totals  = props.portofolios.map(d => Math.round(d.total))
    const harga   = props.portofolios.map(d => Number(d.harga_emas))
    const grams   = props.portofolios.map(d => parseFloat(d.emas_gram))
    const darurat = props.portofolios.map(d => Number(d.dana_darurat))
    const reksa   = props.portofolios.map(d => Number(d.reksa_dana))
    const sbn     = props.portofolios.map(d => Number(d.sbn))

    const chartOpts = (yCallback) => ({
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { grid: { color: gridC }, ticks: { color: txtC, callback: yCallback } },
            x: { grid: { display: false }, ticks: { color: txtC, font: { size: 11 } } }
        }
    })

    totalChart = new Chart(chartTotal.value, {
        type: 'line',
        data: { labels, datasets: [{
            label: 'Total', data: totals,
            borderColor: '#eab308', backgroundColor: 'rgba(234,179,8,0.08)',
            borderWidth: 2.5, pointRadius: 5, pointBackgroundColor: '#eab308',
            pointBorderColor: ptBdr, pointBorderWidth: 2, fill: true, tension: 0.4,
        }]},
        options: { ...chartOpts(v => fmtJt(v)),
            plugins: { legend: { display: false },
                tooltip: { callbacks: { label: ctx => ' '+fmtJt(ctx.parsed.y) } }
            }
        }
    })

    hargaChart = new Chart(chartHarga.value, {
        type: 'line',
        data: { labels, datasets: [
            { label: 'Harga/gram', data: harga, borderColor: '#fbbf24',
              backgroundColor: 'rgba(251,191,36,0.07)', borderWidth: 2,
              pointRadius: 5, pointBackgroundColor: '#fbbf24',
              pointBorderColor: ptBdr, pointBorderWidth: 2, fill: true, tension: 0.4 },
            { label: 'BEP target', data: Array(labels.length).fill(bepTarget.value),
              borderColor: '#ef4444', borderDash: [6,4], borderWidth: 1.5, pointRadius: 0, fill: false },
        ]},
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: true, position: 'bottom',
                labels: { color: txtC, boxWidth: 10, font: { size: 11 } } },
                tooltip: { callbacks: { label: ctx => ' '+fmt(ctx.parsed.y) } }
            },
            scales: {
                y: { grid: { color: gridC }, ticks: { color: txtC, callback: v => 'Rp'+(v/1000000).toFixed(1)+'jt' } },
                x: { grid: { display: false }, ticks: { color: txtC, font: { size: 11 } } }
            }
        }
    })

    emasChart = new Chart(chartEmas.value, {
        type: 'bar',
        data: { labels, datasets: [{
            label: 'Gram', data: grams,
            backgroundColor: 'rgba(234,179,8,0.7)', borderColor: '#eab308',
            borderWidth: 1, borderRadius: 6, borderSkipped: false,
        }]},
        options: { ...chartOpts(v => v+'g'),
            plugins: { legend: { display: false },
                tooltip: { callbacks: { label: ctx => ' '+ctx.parsed.y+'g' } }
            }
        }
    })

    investChart = new Chart(chartInvest.value, {
        type: 'bar',
        data: { labels, datasets: [
            { label: 'Dana darurat', data: darurat, backgroundColor: '#3b82f6', borderRadius: 4, borderSkipped: false },
            { label: 'Reksa dana',   data: reksa,   backgroundColor: '#22c55e', borderRadius: 4, borderSkipped: false },
            { label: 'SBN',          data: sbn,     backgroundColor: '#a855f7', borderRadius: 4, borderSkipped: false },
        ]},
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: true, position: 'bottom',
                labels: { color: txtC, boxWidth: 10, font: { size: 11 } } },
                tooltip: { callbacks: { label: ctx => ' '+fmt(ctx.parsed.y) } }
            },
            scales: {
                x: { stacked: true, grid: { display: false }, ticks: { color: txtC, font: { size: 11 } } },
                y: { stacked: true, grid: { color: gridC }, ticks: { color: txtC, callback: v => fmtJt(v) } }
            }
        }
    })
}

onMounted(buildCharts)
watch(isDark, buildCharts)
onBeforeUnmount(destroyCharts)
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-lg mx-auto lg:max-w-3xl px-4 py-5 lg:py-8 space-y-3">

            <p class="text-xs text-zinc-500 uppercase tracking-widest font-medium">Grafik portofolio</p>

            <!-- EMPTY -->
            <div v-if="!portofolios.length" class="text-center py-16">
                <div class="text-6xl mb-4">📈</div>
                <p class="text-lg font-semibold text-zinc-700 dark:text-zinc-200 mb-2">Belum ada data</p>
                <p class="text-sm text-zinc-500 mb-6">Catat minimal 1 bulan untuk melihat grafik</p>
                <a :href="route('portofolio.create')"
                   class="bg-yellow-500 text-black px-6 py-2.5 rounded-xl font-medium text-sm">
                    Catat sekarang
                </a>
            </div>

            <template v-else>

                <!-- SUMMARY -->
                <div class="grid grid-cols-2 gap-2">
                    <Card class="border-yellow-300/60 dark:border-yellow-700/40 bg-gradient-to-br from-yellow-50 to-white dark:from-yellow-950/40 dark:to-zinc-900">
                        <CardContent class="p-3">
                            <p class="text-xs text-yellow-600 dark:text-yellow-500 mb-1">Total portofolio</p>
                            <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ fmtJt(totalLast) }}</p>
                            <Badge class="text-xs mt-1 border"
                                :class="growthPct >= 0
                                    ? 'bg-green-100 text-green-700 border-green-300 dark:bg-green-900 dark:text-green-400 dark:border-green-700'
                                    : 'bg-red-100 text-red-700 border-red-300 dark:bg-red-900 dark:text-red-400 dark:border-red-700'">
                                {{ growthPct >= 0 ? '▲' : '▼' }} {{ Math.abs(growthPct) }}% sejak awal
                            </Badge>
                        </CardContent>
                    </Card>
                    <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                        <CardContent class="p-3">
                            <p class="text-xs text-zinc-500 mb-1">Total emas</p>
                            <p class="text-lg font-bold text-yellow-500 dark:text-yellow-400">{{ totalEmasGram }}g</p>
                            <p class="text-xs text-zinc-400 mt-1">{{ cicilanGram }}g cicilan{{ isCicilanEstimasi ? ' (estimasi)' : '' }} + {{ last?.emas_gram }}g tunai</p>
                        </CardContent>
                    </Card>
                    <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                        <CardContent class="p-3">
                            <p class="text-xs text-zinc-500 mb-1">Harga emas</p>
                            <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ fmt(hargaSekarang) }}</p>
                            <p class="text-xs text-zinc-400 mt-1">per gram</p>
                        </CardContent>
                    </Card>
                    <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                        <CardContent class="p-3">
                            <p class="text-xs text-zinc-500 mb-1">Data tersimpan</p>
                            <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ portofolios.length }} bulan</p>
                            <p class="text-xs text-zinc-400 mt-1">dari {{ first?.bulan }}</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- BEP TRACKER -->
                <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                    <CardContent class="p-4">
                        <div class="flex justify-between items-center mb-2">
                            <p class="text-xs text-zinc-500 uppercase tracking-widest">BEP cicilan emas</p>
                            <Badge class="border text-xs"
                                :class="bepPct >= 100
                                    ? 'bg-green-100 text-green-700 border-green-300 dark:bg-green-900 dark:text-green-400 dark:border-green-700'
                                    : 'bg-yellow-100 text-yellow-700 border-yellow-300 dark:bg-yellow-900 dark:text-yellow-400 dark:border-yellow-700'">
                                {{ bepPct }}%
                            </Badge>
                        </div>
                        <Progress :model-value="bepPct" class="h-2.5 bg-zinc-200 dark:bg-zinc-800 mb-3"/>
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div>
                                <p class="text-xs text-zinc-500">Sekarang</p>
                                <p class="text-xs font-semibold text-zinc-900 dark:text-white mt-0.5">{{ fmt(hargaSekarang) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-500">Target BEP{{ isCicilanEstimasi ? ' (estimasi)' : '' }}</p>
                                <p class="text-xs font-semibold text-red-500 dark:text-red-400 mt-0.5">{{ fmt(bepTarget) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-500">Kurang</p>
                                <p class="text-xs font-semibold mt-0.5"
                                   :class="bepSisa === 0 ? 'text-green-500 dark:text-green-400' : 'text-orange-500 dark:text-orange-400'">
                                    {{ bepSisa === 0 ? '✅ Tercapai!' : fmt(bepSisa) }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- CHART 1 -->
                <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                    <CardHeader class="pb-2 pt-4 px-4">
                        <CardTitle class="text-xs text-zinc-500 uppercase tracking-widest">Pertumbuhan total portofolio</CardTitle>
                    </CardHeader>
                    <CardContent class="px-4 pb-4" style="height:200px;">
                        <canvas ref="chartTotal"></canvas>
                    </CardContent>
                </Card>

                <!-- CHART 2 -->
                <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                    <CardHeader class="pb-2 pt-4 px-4">
                        <CardTitle class="text-xs text-zinc-500 uppercase tracking-widest">Harga emas/gram vs BEP</CardTitle>
                    </CardHeader>
                    <CardContent class="px-4 pb-4" style="height:180px;">
                        <canvas ref="chartHarga"></canvas>
                    </CardContent>
                </Card>

                <!-- CHART 3 -->
                <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                    <CardHeader class="pb-2 pt-4 px-4">
                        <CardTitle class="text-xs text-zinc-500 uppercase tracking-widest">Akumulasi emas tunai</CardTitle>
                    </CardHeader>
                    <CardContent class="px-4 pb-4" style="height:160px;">
                        <canvas ref="chartEmas"></canvas>
                    </CardContent>
                </Card>

                <!-- CHART 4 -->
                <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                    <CardHeader class="pb-2 pt-4 px-4">
                        <CardTitle class="text-xs text-zinc-500 uppercase tracking-widest">Dana darurat & investasi</CardTitle>
                    </CardHeader>
                    <CardContent class="px-4 pb-4" style="height:180px;">
                        <canvas ref="chartInvest"></canvas>
                    </CardContent>
                </Card>

            </template>
        </div>
    </AuthenticatedLayout>
</template>
