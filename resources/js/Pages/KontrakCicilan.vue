<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import KontrakFormFields from '@/Components/KontrakFormFields.vue'
import { FileText, Trash2, Loader2, Eye, X, Paperclip, Pencil } from 'lucide-vue-next'
import { CICILAN, CICILAN_GRAM, DEFAULT_TENOR_BULAN, DEFAULT_BIAYA_ADMIN } from '@/Composables/useFinanceConstants'

const props = defineProps({
    kontrak: { type: Array, default: () => [] },
})

const now = new Date()
const todayStr = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0')

const form = useForm({
    nomor_kontrak:  '',
    cabang:         '',
    no_rekening:    '',
    tanggal_mulai:  todayStr,
    tenor_bulan:    DEFAULT_TENOR_BULAN,
    total_gram:     CICILAN_GRAM,
    angsuran_bulan: CICILAN,
    sewa_modal:     '',
    biaya_admin:    DEFAULT_BIAYA_ADMIN,
    catatan:        '',
    file_kontrak:   null,
})

const fileInputKey = ref(0)

const onFileChange = (file) => form.file_kontrak = file

const submit = () => form.post(route('kontrak-cicilan.store'), {
    onSuccess: () => {
        form.reset('nomor_kontrak', 'cabang', 'no_rekening', 'sewa_modal', 'catatan', 'file_kontrak')
        fileInputKey.value++
    },
})

const hapus = (id) => {
    if (confirm('Hapus kontrak cicilan ini?')) router.delete(route('kontrak-cicilan.destroy', id))
}

const detailTarget = ref(null)
const lihatDetail = (k) => detailTarget.value = k
const tutupDetail = () => detailTarget.value = null

// EDIT
const editId = ref(null)
const editFileInputKey = ref(0)
const editForm = useForm({
    nomor_kontrak: '', cabang: '', no_rekening: '', tanggal_mulai: '', tenor_bulan: DEFAULT_TENOR_BULAN,
    total_gram: 0, angsuran_bulan: 0, sewa_modal: '', biaya_admin: DEFAULT_BIAYA_ADMIN,
    status: 'aktif', catatan: '', file_kontrak: null,
})

const bukaEdit = (k) => {
    editId.value = k.id
    editForm.nomor_kontrak  = k.nomor_kontrak
    editForm.cabang         = k.cabang
    editForm.no_rekening    = k.no_rekening
    editForm.tanggal_mulai  = k.tanggal_mulai.slice(0, 10)
    editForm.tenor_bulan    = k.tenor_bulan
    editForm.total_gram     = k.total_gram
    editForm.angsuran_bulan = k.angsuran_bulan
    editForm.sewa_modal     = k.sewa_modal
    editForm.biaya_admin    = k.biaya_admin
    editForm.status         = k.status
    editForm.catatan        = k.catatan
    editForm.file_kontrak   = null
    editForm.clearErrors()
}

const tutupEdit = () => {
    editId.value = null
    editForm.reset()
    editFileInputKey.value++
}

const submitEdit = () => {
    // PUT + file (multipart) tidak konsisten diparse Laravel — kirim sebagai POST dengan spoof _method sesuai rekomendasi Inertia.
    editForm.transform((data) => ({ ...data, _method: 'put' })).post(route('kontrak-cicilan.update', editId.value), {
        onSuccess: () => tutupEdit(),
    })
}

const rupiah = (n) => 'Rp' + Number(n ?? 0).toLocaleString('id-ID')

const tanggalLabel = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : '-'

const totalPembayaran = (k) => Number(k.angsuran_bulan) * Number(k.tenor_bulan)

const sisaBulan = (k) => {
    const selesai = new Date(k.tanggal_selesai)
    let diff = (selesai.getFullYear() - now.getFullYear()) * 12 + (selesai.getMonth() - now.getMonth())
    if (selesai.getDate() < now.getDate()) diff -= 1
    return Math.max(0, diff)
}

const statusBadge = (status) => ({
    aktif:        'bg-yellow-100 text-yellow-700 border-yellow-300 dark:bg-yellow-900/50 dark:text-yellow-400 dark:border-yellow-700',
    lunas:        'bg-green-100 text-green-700 border-green-300 dark:bg-green-900/50 dark:text-green-400 dark:border-green-700',
    wanprestasi:  'bg-red-100 text-red-700 border-red-300 dark:bg-red-900/50 dark:text-red-400 dark:border-red-700',
}[status] ?? 'bg-zinc-100 text-zinc-700 border-zinc-300')
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-lg mx-auto lg:max-w-3xl px-4 py-5 lg:py-8 space-y-3">

            <p class="text-xs text-zinc-500 uppercase tracking-widest font-medium mb-1">Kontrak cicilan emas</p>

            <!-- FORM TAMBAH KONTRAK -->
            <form @submit.prevent="submit" class="space-y-3">
                <Card class="border-yellow-200 dark:border-yellow-700/30 bg-white dark:bg-zinc-900">
                    <CardHeader class="pb-2 pt-4 px-4">
                        <CardTitle class="text-xs text-yellow-600 dark:text-yellow-500 uppercase tracking-widest flex items-center gap-1.5">
                            <FileText :size="12"/> Tambah kontrak baru
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="px-4 pb-4 space-y-3">
                        <KontrakFormFields :form="form" :file-input-key="fileInputKey" @file-change="onFileChange"/>
                    </CardContent>
                </Card>

                <button type="submit" :disabled="form.processing"
                    class="w-full bg-yellow-500 hover:bg-yellow-400 text-black font-semibold py-3.5 rounded-xl text-sm disabled:opacity-50 transition-colors flex items-center justify-center gap-2">
                    <Loader2 v-if="form.processing" :size="16" class="animate-spin"/>
                    <span>{{ form.processing ? 'Menyimpan...' : 'Simpan kontrak' }}</span>
                </button>
            </form>

            <!-- DAFTAR KONTRAK -->
            <div class="pt-2 space-y-2">
                <p class="text-xs text-zinc-500 uppercase tracking-widest font-medium">Daftar kontrak</p>

                <p v-if="!kontrak.length" class="text-sm text-zinc-400 py-6 text-center">Belum ada kontrak tercatat.</p>

                <Card v-for="k in kontrak" :key="k.id" class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                    <CardContent class="p-4 space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ k.nomor_kontrak }}</p>
                                <p class="text-xs text-zinc-500">{{ k.cabang || '-' }} · {{ k.total_gram }} gram · {{ k.tenor_bulan }} bulan</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge :class="statusBadge(k.status) + ' border text-xs'">{{ k.status }}</Badge>
                                <button @click="bukaEdit(k)" class="text-zinc-400 hover:text-yellow-500 transition-colors">
                                    <Pencil :size="14"/>
                                </button>
                                <button @click="hapus(k.id)" class="text-zinc-400 hover:text-red-500 transition-colors">
                                    <Trash2 :size="14"/>
                                </button>
                            </div>
                        </div>
                        <div class="flex justify-between text-xs text-zinc-500 dark:text-zinc-400">
                            <span>{{ k.tanggal_mulai }} s/d {{ k.tanggal_selesai }}</span>
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ rupiah(k.angsuran_bulan) }}/bulan</span>
                        </div>
                        <p v-if="k.catatan" class="text-xs text-zinc-400 italic">{{ k.catatan }}</p>
                        <a v-if="k.file_kontrak" :href="`/storage/${k.file_kontrak}`" target="_blank"
                            class="inline-flex items-center gap-1 text-xs text-yellow-600 dark:text-yellow-400 hover:underline">
                            <Paperclip :size="11"/> Lihat file kontrak
                        </a>
                        <button @click="lihatDetail(k)"
                            class="w-full flex items-center justify-center gap-1.5 text-xs font-medium text-yellow-600 dark:text-yellow-400 border border-yellow-400/60 dark:border-yellow-700/50 rounded-lg py-2 hover:bg-yellow-50 dark:hover:bg-yellow-950/50 transition-colors">
                            <Eye :size="12"/> Lihat detail
                        </button>
                    </CardContent>
                </Card>
            </div>

        </div>
    </AuthenticatedLayout>

    <!-- DETAIL MODAL -->
    <Teleport to="body">
        <Transition enter-active-class="transition" enter-from-class="opacity-0" leave-active-class="transition" leave-to-class="opacity-0">
            <div v-if="detailTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="tutupDetail">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"/>
                <div class="relative w-full max-w-sm bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-zinc-200 dark:border-zinc-800 p-6 max-h-[85vh] overflow-y-auto">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <h3 class="font-semibold text-zinc-900 dark:text-white text-sm">Detail kontrak</h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ detailTarget.nomor_kontrak }}</p>
                        </div>
                        <button @click="tutupDetail" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors shrink-0">
                            <X :size="16"/>
                        </button>
                    </div>

                    <div class="space-y-2.5 text-xs">
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Status</span>
                            <Badge :class="statusBadge(detailTarget.status) + ' border text-xs'">{{ detailTarget.status }}</Badge>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Cabang</span>
                            <span class="text-zinc-800 dark:text-zinc-200 font-medium">{{ detailTarget.cabang || '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">No. rekening</span>
                            <span class="text-zinc-800 dark:text-zinc-200 font-medium">{{ detailTarget.no_rekening || '-' }}</span>
                        </div>
                        <div class="border-t border-zinc-100 dark:border-zinc-800 my-2"/>
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Tanggal mulai</span>
                            <span class="text-zinc-800 dark:text-zinc-200 font-medium">{{ tanggalLabel(detailTarget.tanggal_mulai) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Tanggal lunas</span>
                            <span class="text-zinc-800 dark:text-zinc-200 font-medium">{{ tanggalLabel(detailTarget.tanggal_selesai) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Tenor</span>
                            <span class="text-zinc-800 dark:text-zinc-200 font-medium">{{ detailTarget.tenor_bulan }} bulan · sisa {{ sisaBulan(detailTarget) }} bulan</span>
                        </div>
                        <div class="border-t border-zinc-100 dark:border-zinc-800 my-2"/>
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Total emas</span>
                            <span class="text-zinc-800 dark:text-zinc-200 font-medium">{{ detailTarget.total_gram }} gram</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Angsuran/bulan</span>
                            <span class="text-zinc-800 dark:text-zinc-200 font-medium">{{ rupiah(detailTarget.angsuran_bulan) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Sewa modal</span>
                            <span class="text-zinc-800 dark:text-zinc-200 font-medium">{{ rupiah(detailTarget.sewa_modal) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Biaya admin</span>
                            <span class="text-zinc-800 dark:text-zinc-200 font-medium">{{ rupiah(detailTarget.biaya_admin) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400 font-medium">Total seluruh angsuran</span>
                            <span class="text-yellow-600 dark:text-yellow-400 font-semibold">{{ rupiah(totalPembayaran(detailTarget)) }}</span>
                        </div>
                        <template v-if="detailTarget.catatan">
                            <div class="border-t border-zinc-100 dark:border-zinc-800 my-2"/>
                            <p class="text-zinc-400 italic">{{ detailTarget.catatan }}</p>
                        </template>
                        <a v-if="detailTarget.file_kontrak" :href="`/storage/${detailTarget.file_kontrak}`" target="_blank"
                            class="flex items-center justify-center gap-1.5 mt-2 text-yellow-600 dark:text-yellow-400 hover:underline">
                            <Paperclip :size="12"/> Lihat file kontrak
                        </a>
                    </div>

                    <button @click="tutupDetail"
                        class="w-full mt-5 py-2.5 rounded-xl text-sm font-medium border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- EDIT MODAL -->
    <Teleport to="body">
        <Transition enter-active-class="transition" enter-from-class="opacity-0" leave-active-class="transition" leave-to-class="opacity-0">
            <div v-if="editId" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="tutupEdit">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"/>
                <form @submit.prevent="submitEdit"
                    class="relative w-full max-w-sm bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-zinc-200 dark:border-zinc-800 p-6 max-h-[85vh] overflow-y-auto space-y-3">
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <h3 class="font-semibold text-zinc-900 dark:text-white text-sm">Edit kontrak</h3>
                        <button type="button" @click="tutupEdit" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors shrink-0">
                            <X :size="16"/>
                        </button>
                    </div>

                    <KontrakFormFields :form="editForm" show-status :file-input-key="editFileInputKey"
                        @file-change="(file) => editForm.file_kontrak = file"/>

                    <div class="flex gap-2 pt-2">
                        <button type="button" @click="tutupEdit"
                            class="flex-1 py-2.5 rounded-xl text-sm font-medium border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                            Batal
                        </button>
                        <button type="submit" :disabled="editForm.processing"
                            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 rounded-xl text-sm font-semibold bg-yellow-500 hover:bg-yellow-400 text-black disabled:opacity-50 transition-colors">
                            <Loader2 v-if="editForm.processing" :size="14" class="animate-spin"/>
                            <span>{{ editForm.processing ? 'Menyimpan...' : 'Simpan perubahan' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </Transition>
    </Teleport>
</template>
