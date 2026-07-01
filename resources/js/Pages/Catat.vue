<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import { Separator } from '@/Components/ui/separator'
import {
    Lock, Coins, Shield, TrendingUp, Landmark,
    StickyNote, Globe, Calendar, Loader2, AlertTriangle
} from 'lucide-vue-next'
import { CICILAN } from '@/Composables/useFinanceConstants'

const props = defineProps({
    lastHargaEmas: { type: Number, default: null },
})

const now = new Date()
const bulanDefault = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0')

const form = useForm({
    bulan:        bulanDefault,
    emas_gram:    '',
    harga_emas:   '',
    cicilan:      CICILAN,
    dana_darurat: 0,
    reksa_dana:   0,
    sbn:          0,
    catatan:      '',
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
            xauUsd:    data.xau_usd.toFixed(2),
            usdIdr:    data.usd_idr.toLocaleString('id-ID'),
            spotIdr:   data.spot_idr.toLocaleString('id-ID'),
            pegadaian: data.pegadaian,
        }
        form.harga_emas = data.pegadaian
    } catch {
        errorHarga.value = props.lastHargaEmas
            ? `Gagal ambil harga — menggunakan harga terakhir (Rp${props.lastHargaEmas.toLocaleString('id-ID')}).`
            : 'Gagal ambil harga — isi manual.'
        if (props.lastHargaEmas && !form.harga_emas) {
            form.harga_emas = props.lastHargaEmas
        }
    } finally {
        loadingHarga.value = false
    }
}

const submit = () => form.post(route('portofolio.store'))

const inputClass = "w-full bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl px-3 py-2.5 text-sm text-zinc-900 dark:text-white focus:border-yellow-500 focus:ring-0 outline-none transition-colors placeholder:text-zinc-400 dark:placeholder:text-zinc-600"
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-lg mx-auto lg:max-w-3xl px-4 py-5 lg:py-8 space-y-3">

            <div class="flex items-center justify-between mb-1">
                <p class="text-xs text-zinc-500 uppercase tracking-widest font-medium">Catat bulan ini</p>
                <Badge class="bg-yellow-100 text-yellow-700 border-yellow-300 dark:bg-yellow-900/50 dark:text-yellow-400 dark:border-yellow-700 border text-xs">{{ form.bulan }}</Badge>
            </div>

            <form @submit.prevent="submit" class="space-y-3">

                <!-- PERIODE & CICILAN -->
                <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                    <CardHeader class="pb-2 pt-4 px-4">
                        <CardTitle class="text-xs text-zinc-500 uppercase tracking-widest flex items-center gap-1.5">
                            <Calendar :size="12"/> Periode & cicilan
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="px-4 pb-4 space-y-3">
                        <div>
                            <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                <Calendar :size="12" class="text-zinc-400"/> Bulan & tahun
                            </label>
                            <input type="month" v-model="form.bulan" :class="inputClass"/>
                            <p v-if="form.errors.bulan" class="text-xs text-red-500 mt-1">{{ form.errors.bulan }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                <Lock :size="12" class="text-yellow-600"/> Cicilan emas (Rp)
                            </label>
                            <input type="number" v-model="form.cicilan" :class="inputClass"/>
                        </div>
                    </CardContent>
                </Card>

                <!-- DATA EMAS -->
                <Card class="border-yellow-200 dark:border-yellow-700/30 bg-white dark:bg-zinc-900">
                    <CardHeader class="pb-2 pt-4 px-4">
                        <CardTitle class="text-xs text-yellow-600 dark:text-yellow-500 uppercase tracking-widest flex items-center gap-1.5">
                            <Coins :size="12"/> Data emas
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="px-4 pb-4 space-y-3">
                        <div>
                            <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                <Coins :size="12" class="text-yellow-500 dark:text-yellow-400"/> Emas tunai — total gram dimiliki
                            </label>
                            <input type="number" step="0.01" v-model="form.emas_gram"
                                placeholder="mis. 0.50" :class="inputClass"/>
                            <p v-if="form.errors.emas_gram" class="text-xs text-red-500 mt-1">{{ form.errors.emas_gram }}</p>
                        </div>
                        <div>
                            <div class="flex justify-between items-center mb-1.5">
                                <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5">
                                    <Globe :size="12" class="text-zinc-400"/> Harga emas (Rp/gram)
                                </label>
                                <button type="button" @click="fetchHargaEmas" :disabled="loadingHarga"
                                    class="text-xs px-3 py-1.5 rounded-lg border border-yellow-400/60 dark:border-yellow-700/50 text-yellow-600 dark:text-yellow-400 hover:bg-yellow-50 dark:hover:bg-yellow-950/50 disabled:opacity-50 transition-colors flex items-center gap-1.5">
                                    <Loader2 v-if="loadingHarga" :size="12" class="animate-spin"/>
                                    <Globe v-else :size="12"/>
                                    <span>{{ loadingHarga ? 'Mengambil...' : 'Ambil harga' }}</span>
                                </button>
                            </div>
                            <div v-if="hargaFetched" class="bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-yellow-700/20 rounded-xl p-3 mb-2 space-y-1.5">
                                <div class="flex justify-between text-xs">
                                    <span class="text-zinc-500">XAU/USD</span>
                                    <span class="text-zinc-700 dark:text-zinc-300">${{ hargaFetched.xauUsd }}/oz</span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-zinc-500">USD/IDR</span>
                                    <span class="text-zinc-700 dark:text-zinc-300">Rp{{ hargaFetched.usdIdr }}</span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-zinc-500">Spot/gram</span>
                                    <span class="text-zinc-700 dark:text-zinc-300">Rp{{ hargaFetched.spotIdr }}</span>
                                </div>
                                <Separator class="bg-zinc-200 dark:bg-zinc-700 my-1"/>
                                <div class="flex justify-between text-xs">
                                    <span class="text-yellow-600 dark:text-yellow-400 font-medium">Est. Pegadaian (+4%)</span>
                                    <span class="text-yellow-600 dark:text-yellow-400 font-medium">Rp{{ hargaFetched.pegadaian.toLocaleString('id-ID') }}</span>
                                </div>
                                <p class="text-zinc-400 text-xs">*Selalu cek harga aktual di app Pegadaian</p>
                            </div>
                            <div v-if="errorHarga" class="bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl p-2.5 mb-2 text-xs text-red-600 dark:text-red-400 flex items-center gap-1.5">
                                <AlertTriangle :size="12"/> {{ errorHarga }}
                            </div>
                            <input type="number" v-model="form.harga_emas"
                                placeholder="mis. 2545000" :class="inputClass"/>
                            <p v-if="form.errors.harga_emas" class="text-xs text-red-500 mt-1">{{ form.errors.harga_emas }}</p>
                            <p v-else-if="lastHargaEmas && !form.harga_emas" class="text-xs text-zinc-400 mt-1">
                                Harga bulan lalu: Rp{{ lastHargaEmas.toLocaleString('id-ID') }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- SALDO INVESTASI -->
                <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                    <CardHeader class="pb-2 pt-4 px-4">
                        <CardTitle class="text-xs text-zinc-500 uppercase tracking-widest flex items-center gap-1.5">
                            <TrendingUp :size="12"/> Saldo investasi
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="px-4 pb-4 space-y-3">
                        <div>
                            <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                <Shield :size="12" class="text-blue-500 dark:text-blue-400"/> Dana darurat — RDPU (Rp)
                            </label>
                            <input type="number" v-model="form.dana_darurat" :class="inputClass"/>
                        </div>
                        <div>
                            <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                <TrendingUp :size="12" class="text-green-500 dark:text-green-400"/> Reksa dana (Rp)
                            </label>
                            <input type="number" v-model="form.reksa_dana" :class="inputClass"/>
                        </div>
                        <div>
                            <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                <Landmark :size="12" class="text-purple-500 dark:text-purple-400"/> SBN / Deposito (Rp)
                            </label>
                            <input type="number" v-model="form.sbn" :class="inputClass"/>
                        </div>
                    </CardContent>
                </Card>

                <!-- CATATAN -->
                <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                    <CardContent class="p-4">
                        <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                            <StickyNote :size="12" class="text-zinc-400"/> Catatan (opsional)
                        </label>
                        <input type="text" v-model="form.catatan"
                            placeholder="mis. dapat diskon GAJIANEMAS" :class="inputClass"/>
                    </CardContent>
                </Card>

                <button type="submit" :disabled="form.processing"
                    class="w-full bg-yellow-500 hover:bg-yellow-400 text-black font-semibold py-3.5 rounded-xl text-sm disabled:opacity-50 transition-colors flex items-center justify-center gap-2">
                    <Loader2 v-if="form.processing" :size="16" class="animate-spin"/>
                    <span>{{ form.processing ? 'Menyimpan...' : 'Simpan data bulan ini' }}</span>
                </button>

            </form>
        </div>
    </AuthenticatedLayout>
</template>
