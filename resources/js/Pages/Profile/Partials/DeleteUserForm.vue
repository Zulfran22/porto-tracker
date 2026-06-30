<script setup>
import InputError from '@/Components/InputError.vue'
import { useForm } from '@inertiajs/vue3'
import { nextTick, ref } from 'vue'
import { Trash2, AlertTriangle, X, Lock } from 'lucide-vue-next'

const confirmingUserDeletion = ref(false)
const passwordInput = ref(null)

const form = useForm({
    password: '',
})

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true
    nextTick(() => passwordInput.value?.focus())
}

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    })
}

const closeModal = () => {
    confirmingUserDeletion.value = false
    form.clearErrors()
    form.reset()
}
</script>

<template>
    <section>
        <h2 class="text-sm font-semibold text-red-600 dark:text-red-400 mb-0.5">Hapus akun</h2>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-5">
            Setelah dihapus, semua data portofolio dan keuanganmu akan hilang permanen.
        </p>

        <button @click="confirmUserDeletion"
            class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold
                   border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400
                   hover:bg-red-50 dark:hover:bg-red-950/40 transition-colors">
            <Trash2 :size="14"/>
            Hapus akun
        </button>

        <!-- Modal konfirmasi -->
        <Teleport to="body">
            <Transition enter-active-class="transition" enter-from-class="opacity-0" leave-active-class="transition" leave-to-class="opacity-0">
                <div v-if="confirmingUserDeletion"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    @click.self="closeModal">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"/>
                    <div class="relative w-full max-w-sm bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-zinc-200 dark:border-zinc-800 p-6">

                        <div class="flex items-start gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center shrink-0">
                                <AlertTriangle :size="18" class="text-red-600 dark:text-red-400"/>
                            </div>
                            <div>
                                <h3 class="font-semibold text-zinc-900 dark:text-white text-sm">Hapus akun?</h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                    Tindakan ini tidak bisa dibatalkan. Masukkan password untuk konfirmasi.
                                </p>
                            </div>
                            <button @click="closeModal" class="ml-auto text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors shrink-0">
                                <X :size="16"/>
                            </button>
                        </div>

                        <div class="space-y-1.5 mb-5">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Password</label>
                            <div class="relative">
                                <Lock :size="15" class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none"/>
                                <input
                                    ref="passwordInput"
                                    type="password"
                                    v-model="form.password"
                                    placeholder="••••••••"
                                    @keyup.enter="deleteUser"
                                    class="w-full pl-9 pr-4 py-2.5 rounded-xl border text-sm transition-colors
                                           bg-white dark:bg-zinc-800 border-zinc-300 dark:border-zinc-700
                                           text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500
                                           focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400"
                                    :class="{ 'border-red-400': form.errors.password }"
                                />
                            </div>
                            <InputError :message="form.errors.password"/>
                        </div>

                        <div class="flex gap-2">
                            <button @click="closeModal"
                                class="flex-1 py-2 rounded-xl text-sm font-medium border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                Batal
                            </button>
                            <button @click="deleteUser" :disabled="form.processing"
                                class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-xl text-sm font-semibold
                                       bg-red-600 hover:bg-red-500 text-white transition-colors
                                       disabled:opacity-60 disabled:cursor-not-allowed">
                                <Trash2 :size="14"/>
                                {{ form.processing ? 'Menghapus...' : 'Ya, hapus' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </section>
</template>
