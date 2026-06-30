<script setup>
import { useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    portofolio: Object,
})

const form = useForm({
    bulan:        props.portofolio.bulan,
    emas_gram:    props.portofolio.emas_gram,
    harga_emas:   props.portofolio.harga_emas,
    cicilan:      props.portofolio.cicilan,
    dana_darurat: props.portofolio.dana_darurat,
    reksa_dana:   props.portofolio.reksa_dana,
    sbn:          props.portofolio.sbn,
    catatan:      props.portofolio.catatan ?? '',
})

const submit = () => {
    form.put(route('portofolio.update', props.portofolio.id))
}
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-lg mx-auto px-4 py-6 pb-28">

            <!-- HEADER -->
            <div class="flex items-center gap-3 mb-6">
                <a :href="route('dashboard')"
                   class="text-gray-500 hover:text-white transition-colors">
                    ← Kembali
                </a>
                <p class="text-xs text-gray-500 uppercase tracking-widest">Edit data {{ portofolio.bulan }}</p>
            </div>

            <form @submit.prevent="submit" class="space-y-3">
                <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 space-y-4">

                    <div>
                        <label class="text-xs text-gray-400 block mb-1">📅 Bulan & tahun</label>
                        <input type="month" v-model="form.bulan"
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-yellow-500 outline-none" />
                        <p v-if="form.errors.bulan" class="text-xs text-red-400 mt-1">{{ form.errors.bulan }}</p>
                    </div>

                    <div class="border-t border-gray-800 pt-4">
                        <label class="text-xs text-gray-400 block mb-1">🔒 Cicilan emas (Rp)</label>
                        <input type="number" v-model="form.cicilan"
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-yellow-500 outline-none" />
                    </div>

                    <div>
                        <label class="text-xs text-gray-400 block mb-1">🥇 Emas tunai — total gram dimiliki</label>
                        <input type="number" step="0.01" v-model="form.emas_gram"
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-yellow-500 outline-none" />
                        <p v-if="form.errors.emas_gram" class="text-xs text-red-400 mt-1">{{ form.errors.emas_gram }}</p>
                    </div>

                    <div>
                        <label class="text-xs text-gray-400 block mb-1">Harga emas Pegadaian (Rp/gram)</label>
                        <input type="number" v-model="form.harga_emas"
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-yellow-500 outline-none" />
                        <p v-if="form.errors.harga_emas" class="text-xs text-red-400 mt-1">{{ form.errors.harga_emas }}</p>
                    </div>

                    <div class="border-t border-gray-800 pt-4">
                        <label class="text-xs text-gray-400 block mb-1">🛡️ Dana darurat — saldo RDPU (Rp)</label>
                        <input type="number" v-model="form.dana_darurat"
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-yellow-500 outline-none" />
                    </div>

                    <div>
                        <label class="text-xs text-gray-400 block mb-1">📈 Reksa dana — saldo (Rp)</label>
                        <input type="number" v-model="form.reksa_dana"
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-yellow-500 outline-none" />
                    </div>

                    <div>
                        <label class="text-xs text-gray-400 block mb-1">🏛️ SBN / Deposito — saldo (Rp)</label>
                        <input type="number" v-model="form.sbn"
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-yellow-500 outline-none" />
                    </div>

                    <div class="border-t border-gray-800 pt-4">
                        <label class="text-xs text-gray-400 block mb-1">📝 Catatan (opsional)</label>
                        <input type="text" v-model="form.catatan"
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:border-yellow-500 outline-none" />
                    </div>
                </div>

                <button type="submit" :disabled="form.processing"
                    class="w-full bg-yellow-500 text-black font-medium py-3 rounded-xl text-sm disabled:opacity-50">
                    {{ form.processing ? 'Menyimpan...' : 'Update data' }}
                </button>

                <a :href="route('dashboard')"
                   class="block w-full text-center text-gray-500 text-sm py-2 hover:text-white transition-colors">
                    Batal
                </a>
            </form>
        </div>
    </AuthenticatedLayout>
</template>