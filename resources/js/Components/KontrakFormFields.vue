<script setup>
import {
    FileText, Building2, Landmark, Calendar, Coins, Wallet,
    Receipt, StickyNote, Paperclip
} from 'lucide-vue-next'

const props = defineProps({
    form: { type: Object, required: true },
    showStatus: { type: Boolean, default: false },
    fileInputKey: { type: [String, Number], default: 0 },
})

const emit = defineEmits(['fileChange'])

const inputClass = "w-full bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl px-3 py-2.5 text-sm text-zinc-900 dark:text-white focus:border-yellow-500 focus:ring-0 outline-none transition-colors placeholder:text-zinc-400 dark:placeholder:text-zinc-600"
</script>

<template>
    <div>
        <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
            <FileText :size="12" class="text-zinc-400"/> Nomor kontrak
        </label>
        <input type="text" v-model="form.nomor_kontrak" placeholder="mis. 17805391142154415301" :class="inputClass"/>
        <p v-if="form.errors.nomor_kontrak" class="text-xs text-red-500 mt-1">{{ form.errors.nomor_kontrak }}</p>
    </div>

    <div v-if="showStatus">
        <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
            <FileText :size="12" class="text-zinc-400"/> Status
        </label>
        <select v-model="form.status" :class="inputClass">
            <option value="aktif">Aktif</option>
            <option value="lunas">Lunas</option>
            <option value="wanprestasi">Wanprestasi</option>
        </select>
        <p v-if="form.errors.status" class="text-xs text-red-500 mt-1">{{ form.errors.status }}</p>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                <Building2 :size="12" class="text-zinc-400"/> Cabang
            </label>
            <input type="text" v-model="form.cabang" placeholder="mis. CP Bontang" :class="inputClass"/>
        </div>
        <div>
            <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                <Landmark :size="12" class="text-zinc-400"/> No. rekening
            </label>
            <input type="text" v-model="form.no_rekening" placeholder="1090 9266 2901 5244" :class="inputClass"/>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                <Calendar :size="12" class="text-zinc-400"/> Tanggal mulai
            </label>
            <input type="date" v-model="form.tanggal_mulai" :class="inputClass"/>
            <p v-if="form.errors.tanggal_mulai" class="text-xs text-red-500 mt-1">{{ form.errors.tanggal_mulai }}</p>
        </div>
        <div>
            <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                <Calendar :size="12" class="text-zinc-400"/> Tenor (bulan)
            </label>
            <input type="number" v-model="form.tenor_bulan" :class="inputClass"/>
            <p v-if="form.errors.tenor_bulan" class="text-xs text-red-500 mt-1">{{ form.errors.tenor_bulan }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                <Coins :size="12" class="text-yellow-500 dark:text-yellow-400"/> Total emas (gram)
            </label>
            <input type="number" step="0.0001" v-model="form.total_gram" :class="inputClass"/>
            <p v-if="form.errors.total_gram" class="text-xs text-red-500 mt-1">{{ form.errors.total_gram }}</p>
        </div>
        <div>
            <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                <Wallet :size="12" class="text-zinc-400"/> Angsuran/bulan (Rp)
            </label>
            <input type="number" v-model="form.angsuran_bulan" :class="inputClass"/>
            <p v-if="form.errors.angsuran_bulan" class="text-xs text-red-500 mt-1">{{ form.errors.angsuran_bulan }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                <Receipt :size="12" class="text-zinc-400"/> Sewa modal (Rp)
            </label>
            <input type="number" v-model="form.sewa_modal" placeholder="mis. 2033750" :class="inputClass"/>
        </div>
        <div>
            <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                <Receipt :size="12" class="text-zinc-400"/> Biaya admin (Rp)
            </label>
            <input type="number" v-model="form.biaya_admin" :class="inputClass"/>
        </div>
    </div>

    <div>
        <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
            <StickyNote :size="12" class="text-zinc-400"/> Catatan (opsional)
        </label>
        <input type="text" v-model="form.catatan" placeholder="mis. PIC Toni Sugianto" :class="inputClass"/>
    </div>

    <div>
        <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
            <Paperclip :size="12" class="text-zinc-400"/> File kontrak (PDF/gambar, opsional)
        </label>
        <input :key="fileInputKey" type="file" accept=".pdf,image/*" @change="emit('fileChange', $event.target.files[0] ?? null)" :class="inputClass"/>
        <p v-if="form.errors.file_kontrak" class="text-xs text-red-500 mt-1">{{ form.errors.file_kontrak }}</p>
    </div>
</template>
