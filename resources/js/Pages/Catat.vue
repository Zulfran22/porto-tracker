<script setup>
import { ref, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import PortfolioItemFields from '@/Components/PortfolioItemFields.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import {
    Lock, StickyNote, Calendar, Loader2,
    Plus, Trash2, Tag, Coins
} from 'lucide-vue-next'
import { inputClass } from '@/Composables/useFormStyles'

const props = defineProps({
    lastHargaEmas: { type: Number, default: null },
    investmentTypes: { type: Array, default: () => [] },
    aktifKontrak:  { type: Object, default: null },
})

// Cuma pre-fill dari kontrak aktif yang benar-benar tercatat — kalau belum ada,
// biarkan kosong (bukan angka contoh) supaya tidak ketubruk tersimpan sebagai data asli.
const cicilanDefault = props.aktifKontrak ? Number(props.aktifKontrak.angsuran_bulan) : ''
const tenorEndLabel  = props.aktifKontrak
    ? new Date(props.aktifKontrak.tanggal_selesai).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
    : null

const now = new Date()
const bulanDefault = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0')

function buildItems(types) {
    return types.map(t => ({
        type_name: t.name,
        unit: t.unit,
        gram: t.unit === 'gram' ? '' : null,
        jumlah: t.unit === 'rupiah' ? 0 : null,
    }))
}

const form = useForm({
    bulan:        bulanDefault,
    harga_emas:   '',
    cicilan:      cicilanDefault,
    catatan:      '',
    items:        buildItems(props.investmentTypes),
})

// Kalau user menambah/menghapus jenis investasi (lihat submitType/hapusType di
// bawah), props.investmentTypes berubah lewat reload Inertia — rebuild items
// sambil mempertahankan nilai yang sudah diisi untuk jenis yang masih ada.
watch(() => props.investmentTypes, (newTypes) => {
    const existingByName = Object.fromEntries(form.items.map(i => [i.type_name, i]))
    form.items = newTypes.map(t => existingByName[t.name] ?? {
        type_name: t.name,
        unit: t.unit,
        gram: t.unit === 'gram' ? '' : null,
        jumlah: t.unit === 'rupiah' ? 0 : null,
    })
})

const submit = () => form.post(route('portofolio.store'))

// Kelola jenis investasi — mirror pola "Kategori Kustom" di Keuangan.vue.
const showTypeForm = ref(false)
const typeForm = useForm({ name: '' })
const submitType = () => typeForm.post(route('investasi.tipe.store'), {
    preserveScroll: true,
    onSuccess: () => { typeForm.reset('name'); showTypeForm.value = false },
})

const deleteTypeTarget = ref(null)
const deleteTypeProcessing = ref(false)
const confirmHapusType = (t) => { deleteTypeTarget.value = t }
const batalHapusType = () => { deleteTypeTarget.value = null }
function hapusType() {
    if (!deleteTypeTarget.value) return
    deleteTypeProcessing.value = true
    router.delete(route('investasi.tipe.destroy', deleteTypeTarget.value.id), {
        preserveScroll: true,
        onFinish: () => { deleteTypeProcessing.value = false; deleteTypeTarget.value = null },
    })
}
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-lg mx-auto lg:max-w-3xl px-4 py-5 lg:py-8 space-y-3">

            <div class="flex items-center justify-between mb-1">
                <p class="text-xs text-zinc-500 uppercase tracking-widest font-medium">Catat bulan ini</p>
                <Badge class="bg-indigo-100 text-indigo-700 border-indigo-300 dark:bg-indigo-900/50 dark:text-indigo-400 dark:border-indigo-700 border text-xs">{{ form.bulan }}</Badge>
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
                            <label for="catat-page-bulan" class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                <Calendar :size="12" class="text-zinc-400"/> Bulan & tahun
                            </label>
                            <input id="catat-page-bulan" type="month" v-model="form.bulan" :class="inputClass"/>
                            <p v-if="form.errors.bulan" class="text-xs text-red-500 mt-1">{{ form.errors.bulan }}</p>
                        </div>
                        <div>
                            <label for="catat-page-cicilan" class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                <Lock :size="12" class="text-yellow-600"/> Cicilan emas (Rp)
                            </label>
                            <input id="catat-page-cicilan" type="number" v-model="form.cicilan" placeholder="0 kalau tidak punya cicilan emas" :class="inputClass"/>
                            <p v-if="aktifKontrak" class="text-xs text-zinc-400 mt-1">
                                Jatuh tempo tgl {{ new Date(aktifKontrak.tanggal_mulai).getDate() }} setiap bulan · kontrak lunas {{ tenorEndLabel }} · No. {{ aktifKontrak.nomor_kontrak }}
                            </p>
                            <p v-else class="text-xs text-zinc-400 mt-1">
                                Belum ada kontrak cicilan emas tercatat —
                                <a :href="route('kontrak-cicilan.index')" class="text-indigo-500 hover:underline">tambah di sini</a>
                                kalau kamu punya.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <PortfolioItemFields
                    :items="form.items"
                    v-model:harga-emas="form.harga_emas"
                    :last-harga-emas="lastHargaEmas"
                    :harga-emas-error="form.errors.harga_emas"
                    :aktif-kontrak="aktifKontrak"
                />

                <!-- JENIS INVESTASI -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between px-1">
                        <p class="text-xs text-zinc-500 uppercase tracking-widest font-medium flex items-center gap-1.5">
                            <Tag :size="12"/> Jenis investasi
                        </p>
                        <button type="button" @click="showTypeForm = !showTypeForm"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-500 hover:bg-indigo-400 active:bg-indigo-600 text-white text-xs font-medium transition-colors">
                            <Plus :size="12"/> Tambah
                        </button>
                    </div>

                    <Card v-if="showTypeForm" class="border-indigo-400/40 dark:border-indigo-600/30 bg-indigo-50/50 dark:bg-indigo-900/10">
                        <CardContent class="p-4 space-y-3">
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Tambah jenis investasi baru</p>
                            <input v-model="typeForm.name" type="text" placeholder="mis. Kripto, Saham, Deposito" maxlength="50"
                                :class="inputClass"/>
                            <p v-if="typeForm.errors.name" class="text-xs text-red-500">{{ typeForm.errors.name }}</p>
                            <div class="flex gap-2 justify-end">
                                <button type="button" @click="showTypeForm = false"
                                    class="px-4 py-2 rounded-lg text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                                    Batal
                                </button>
                                <button type="button" @click="submitType" :disabled="typeForm.processing || !typeForm.name"
                                    class="px-4 py-2 rounded-lg bg-indigo-500 hover:bg-indigo-400 active:bg-indigo-600 text-white text-sm font-medium transition-colors disabled:opacity-50 flex items-center gap-1.5">
                                    <Loader2 v-if="typeForm.processing" :size="14" class="animate-spin"/>
                                    Simpan
                                </button>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                        <CardContent class="p-0">
                            <div v-for="(t, i) in investmentTypes" :key="t.id"
                                 class="flex items-center gap-3 px-4 py-3"
                                 :class="i < investmentTypes.length - 1 ? 'border-b border-zinc-100 dark:border-zinc-800' : ''">
                                <component :is="t.unit === 'gram' ? Coins : Tag" :size="14"
                                    :class="t.unit === 'gram' ? 'text-yellow-500' : 'text-indigo-500'"/>
                                <span class="flex-1 text-sm text-zinc-700 dark:text-zinc-200">{{ t.name }}</span>
                                <button v-if="t.unit !== 'gram'" type="button"
                                    @click="confirmHapusType(t)" :aria-label="`Hapus jenis ${t.name}`"
                                    class="p-1.5 rounded-lg text-zinc-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors">
                                    <Trash2 :size="14"/>
                                </button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- CATATAN -->
                <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                    <CardContent class="p-4">
                        <label for="catat-page-catatan" class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                            <StickyNote :size="12" class="text-zinc-400"/> Catatan (opsional)
                        </label>
                        <input id="catat-page-catatan" type="text" v-model="form.catatan"
                            placeholder="mis. dapat diskon GAJIANEMAS" :class="inputClass"/>
                    </CardContent>
                </Card>

                <button type="submit" :disabled="form.processing"
                    class="w-full bg-indigo-500 hover:bg-indigo-400 active:bg-indigo-600 text-white font-semibold py-3.5 rounded-xl text-sm disabled:opacity-50 transition-colors flex items-center justify-center gap-2">
                    <Loader2 v-if="form.processing" :size="16" class="animate-spin"/>
                    <span>{{ form.processing ? 'Menyimpan...' : 'Simpan data bulan ini' }}</span>
                </button>

            </form>
        </div>

        <ConfirmModal
            :open="!!deleteTypeTarget"
            title="Hapus jenis investasi ini?"
            :description="`Jenis '${deleteTypeTarget?.name ?? ''}' akan dihapus. Data bulan-bulan sebelumnya tetap tersimpan, tapi tidak bisa dipilih lagi untuk bulan baru.`"
            :loading="deleteTypeProcessing"
            @confirm="hapusType"
            @cancel="batalHapusType"
        />
    </AuthenticatedLayout>
</template>
