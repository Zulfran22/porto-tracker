<script setup>
import { ref, computed } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Separator } from '@/Components/ui/separator'
import { Coins, TrendingUp, Globe, Loader2, AlertTriangle } from 'lucide-vue-next'
import { inputClass } from '@/Composables/useFormStyles'

// Dipakai bareng oleh Catat.vue (halaman penuh) dan modal "Catat" cepat di
// AuthenticatedLayout.vue — supaya dua form ini tidak drift saat salah satu diubah.
const props = defineProps({
    items: { type: Array, required: true },
    hargaEmas: { type: [Number, String], default: '' },
    lastHargaEmas: { type: Number, default: null },
    hargaEmasError: { type: String, default: '' },
    aktifKontrak: { type: Object, default: null },
})
const emit = defineEmits(['update:hargaEmas'])

const gramItem    = computed(() => props.items.find(i => i.unit === 'gram'))
const rupiahItems = computed(() => props.items.filter(i => i.unit === 'rupiah'))
const needsHargaEmas = computed(() =>
    (gramItem.value && Number(gramItem.value.gram) > 0) || !!props.aktifKontrak)

const localHargaEmas = computed({
    get: () => props.hargaEmas,
    set: (v) => emit('update:hargaEmas', v),
})

const loadingHarga = ref(false)
const errorHarga   = ref('')
const hargaFetched = ref(null)

async function fetchHargaEmas() {
    loadingHarga.value = true
    errorHarga.value   = ''
    hargaFetched.value = null
    try {
        const res  = await fetch('/api/harga-emas')
        const data = await res.json()
        if (!data.success) throw new Error(data.message)
        hargaFetched.value = {
            xauUsd:        data.xau_usd?.toFixed?.(2),
            usdIdr:        data.usd_idr?.toLocaleString?.('id-ID'),
            spotIdr:       data.spot_idr.toLocaleString('id-ID'),
            pegadaian:     data.pegadaian,
            markupPercent: data.markup_percent,
        }
        localHargaEmas.value = data.pegadaian
    } catch {
        errorHarga.value = props.lastHargaEmas
            ? `Gagal ambil harga — menggunakan harga terakhir (Rp${props.lastHargaEmas.toLocaleString('id-ID')}).`
            : 'Gagal ambil harga — isi manual.'
        if (props.lastHargaEmas && !localHargaEmas.value) {
            localHargaEmas.value = props.lastHargaEmas
        }
    } finally {
        loadingHarga.value = false
    }
}
</script>

<template>
    <!-- DATA EMAS -->
    <Card v-if="gramItem" class="border-yellow-200 dark:border-yellow-700/30 bg-white dark:bg-zinc-900">
        <CardHeader class="pb-2 pt-4 px-4">
            <CardTitle class="text-xs text-yellow-600 dark:text-yellow-500 uppercase tracking-widest flex items-center gap-1.5">
                <Coins :size="12"/> Data emas
            </CardTitle>
        </CardHeader>
        <CardContent class="px-4 pb-4 space-y-3">
            <div>
                <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                    <Coins :size="12" class="text-yellow-500 dark:text-yellow-400"/> {{ gramItem.type_name }} — total gram dimiliki
                </label>
                <input type="number" step="0.01" v-model="gramItem.gram" placeholder="mis. 0.50" :class="inputClass"/>
            </div>
            <div>
                <div class="flex justify-between items-center mb-1.5">
                    <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5">
                        <Globe :size="12" class="text-zinc-400"/> Harga emas (Rp/gram){{ needsHargaEmas ? ' *' : '' }}
                    </label>
                    <button type="button" @click="fetchHargaEmas" :disabled="loadingHarga"
                        class="text-xs px-3 py-1.5 rounded-lg border border-indigo-400/60 dark:border-indigo-700/50 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 disabled:opacity-50 transition-colors flex items-center gap-1.5">
                        <Loader2 v-if="loadingHarga" :size="12" class="animate-spin"/>
                        <Globe v-else :size="12"/>
                        <span>{{ loadingHarga ? 'Mengambil...' : 'Ambil harga' }}</span>
                    </button>
                </div>
                <div v-if="hargaFetched" class="bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-yellow-700/20 rounded-xl p-3 mb-2 space-y-1.5">
                    <div class="flex justify-between text-xs">
                        <span class="text-zinc-500">Spot/gram</span>
                        <span class="text-zinc-700 dark:text-zinc-300">Rp{{ hargaFetched.spotIdr }}</span>
                    </div>
                    <Separator class="bg-zinc-200 dark:bg-zinc-700 my-1"/>
                    <div class="flex justify-between text-xs">
                        <span class="text-yellow-600 dark:text-yellow-400 font-medium">Est. Pegadaian (+{{ hargaFetched.markupPercent }}%)</span>
                        <span class="text-yellow-600 dark:text-yellow-400 font-medium">Rp{{ hargaFetched.pegadaian.toLocaleString('id-ID') }}</span>
                    </div>
                </div>
                <div v-if="errorHarga" class="bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl p-2.5 mb-2 text-xs text-red-600 dark:text-red-400 flex items-center gap-1.5">
                    <AlertTriangle :size="12"/> {{ errorHarga }}
                </div>
                <input type="number" v-model="localHargaEmas" placeholder="mis. 2545000" :class="inputClass"/>
                <p v-if="hargaEmasError" class="text-xs text-red-500 mt-1">{{ hargaEmasError }}</p>
                <p v-else-if="lastHargaEmas && !localHargaEmas" class="text-xs text-zinc-400 mt-1">
                    Harga bulan lalu: Rp{{ lastHargaEmas.toLocaleString('id-ID') }}
                </p>
            </div>
        </CardContent>
    </Card>

    <!-- SALDO INVESTASI -->
    <Card v-if="rupiahItems.length" class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
        <CardHeader class="pb-2 pt-4 px-4">
            <CardTitle class="text-xs text-zinc-500 uppercase tracking-widest flex items-center gap-1.5">
                <TrendingUp :size="12"/> Saldo investasi
            </CardTitle>
        </CardHeader>
        <CardContent class="px-4 pb-4 space-y-3">
            <div v-for="item in rupiahItems" :key="item.type_name">
                <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                    <TrendingUp :size="12" class="text-green-500 dark:text-green-400"/> {{ item.type_name }} (Rp)
                </label>
                <input type="number" v-model="item.jumlah" :class="inputClass"/>
            </div>
        </CardContent>
    </Card>
</template>
