<script setup>
import { ref } from 'vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import InputError from '@/Components/InputError.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Eye, EyeOff, Lock, ShieldCheck, Loader2 } from 'lucide-vue-next'

const showPassword = ref(false)

const form = useForm({
    password: '',
})

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    })
}
</script>

<template>
    <GuestLayout>
        <Head title="Konfirmasi Password" />

        <div class="flex flex-col items-center text-center mb-6">
            <div class="w-14 h-14 rounded-2xl bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center mb-4">
                <ShieldCheck :size="26" class="text-yellow-600 dark:text-yellow-400"/>
            </div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white">Area aman</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-2 max-w-xs">
                Konfirmasi passwordmu untuk melanjutkan ke halaman ini.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div class="space-y-1.5">
                <label for="password" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Password</label>
                <div class="relative">
                    <Lock :size="15" class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 dark:text-zinc-500 pointer-events-none"/>
                    <input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        v-model="form.password"
                        required
                        autofocus
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full pl-9 pr-10 py-2.5 rounded-xl border text-sm transition-colors
                               bg-white dark:bg-zinc-800
                               border-zinc-300 dark:border-zinc-700
                               text-zinc-900 dark:text-zinc-100
                               placeholder-zinc-400 dark:placeholder-zinc-500
                               focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400"
                        :class="{ 'border-red-400': form.errors.password }"
                    />
                    <button type="button" @click="showPassword = !showPassword"
                        :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors">
                        <EyeOff v-if="showPassword" :size="15"/>
                        <Eye v-else :size="15"/>
                    </button>
                </div>
                <InputError :message="form.errors.password" />
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl font-semibold text-sm
                       bg-yellow-500 hover:bg-yellow-400 active:bg-yellow-600
                       text-black transition-colors shadow-md shadow-yellow-500/20
                       disabled:opacity-60 disabled:cursor-not-allowed"
            >
                <Loader2 v-if="form.processing" :size="16" class="animate-spin"/>
                <ShieldCheck v-else :size="16"/>
                <span>{{ form.processing ? 'Mengonfirmasi...' : 'Konfirmasi' }}</span>
            </button>
        </form>
    </GuestLayout>
</template>
