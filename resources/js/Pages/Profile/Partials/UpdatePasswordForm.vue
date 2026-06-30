<script setup>
import InputError from '@/Components/InputError.vue'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Lock, Save, CheckCircle } from 'lucide-vue-next'

const passwordInput = ref(null)
const currentPasswordInput = ref(null)

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation')
                passwordInput.value.focus()
            }
            if (form.errors.current_password) {
                form.reset('current_password')
                currentPasswordInput.value.focus()
            }
        },
    })
}
</script>

<template>
    <section>
        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white mb-0.5">Ubah password</h2>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-5">Gunakan password yang panjang dan acak agar akun tetap aman.</p>

        <form @submit.prevent="updatePassword" class="space-y-4">
            <div class="space-y-1.5">
                <label for="current_password" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Password saat ini</label>
                <div class="relative">
                    <Lock :size="15" class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none"/>
                    <input id="current_password" ref="currentPasswordInput" type="password" v-model="form.current_password"
                        autocomplete="current-password" placeholder="••••••••"
                        class="w-full pl-9 pr-4 py-2.5 rounded-xl border text-sm transition-colors
                               bg-white dark:bg-zinc-800 border-zinc-300 dark:border-zinc-700
                               text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500
                               focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400"
                        :class="{ 'border-red-400': form.errors.current_password }"/>
                </div>
                <InputError :message="form.errors.current_password"/>
            </div>

            <div class="space-y-1.5">
                <label for="new_password" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Password baru</label>
                <div class="relative">
                    <Lock :size="15" class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none"/>
                    <input id="new_password" ref="passwordInput" type="password" v-model="form.password"
                        autocomplete="new-password" placeholder="Min. 8 karakter"
                        class="w-full pl-9 pr-4 py-2.5 rounded-xl border text-sm transition-colors
                               bg-white dark:bg-zinc-800 border-zinc-300 dark:border-zinc-700
                               text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500
                               focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400"
                        :class="{ 'border-red-400': form.errors.password }"/>
                </div>
                <InputError :message="form.errors.password"/>
            </div>

            <div class="space-y-1.5">
                <label for="password_confirmation" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Konfirmasi password baru</label>
                <div class="relative">
                    <Lock :size="15" class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none"/>
                    <input id="password_confirmation" type="password" v-model="form.password_confirmation"
                        autocomplete="new-password" placeholder="Ulangi password baru"
                        class="w-full pl-9 pr-4 py-2.5 rounded-xl border text-sm transition-colors
                               bg-white dark:bg-zinc-800 border-zinc-300 dark:border-zinc-700
                               text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500
                               focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400"
                        :class="{ 'border-red-400': form.errors.password_confirmation }"/>
                </div>
                <InputError :message="form.errors.password_confirmation"/>
            </div>

            <div class="flex items-center gap-3 pt-1">
                <button type="submit" :disabled="form.processing"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold
                           bg-yellow-500 hover:bg-yellow-400 text-black transition-colors
                           disabled:opacity-60 disabled:cursor-not-allowed">
                    <Save :size="14"/>
                    {{ form.processing ? 'Menyimpan...' : 'Ubah password' }}
                </button>
                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" enter-active-class="transition" leave-active-class="transition">
                    <span v-if="form.recentlySuccessful" class="flex items-center gap-1 text-xs text-green-600 dark:text-green-400 font-medium">
                        <CheckCircle :size="13"/> Tersimpan
                    </span>
                </Transition>
            </div>
        </form>
    </section>
</template>
