<script setup>
import { ref } from 'vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import InputError from '@/Components/InputError.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Eye, EyeOff, Mail, Lock, Loader2, KeyRound } from 'lucide-vue-next'

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
})

const showPassword = ref(false)
const showConfirm = ref(false)

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <GuestLayout>
        <Head title="Reset Password" />

        <div class="mb-6">
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white">Buat password baru</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Masukkan password baru untuk akunmu.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Email (readonly) -->
            <div class="space-y-1.5">
                <label for="email" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Email</label>
                <div class="relative">
                    <Mail :size="15" class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none"/>
                    <input
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autocomplete="username"
                        readonly
                        class="w-full pl-9 pr-4 py-2.5 rounded-xl border text-sm
                               bg-zinc-50 dark:bg-zinc-800/50
                               border-zinc-200 dark:border-zinc-700
                               text-zinc-500 dark:text-zinc-400
                               cursor-not-allowed"
                    />
                </div>
                <InputError :message="form.errors.email" />
            </div>

            <!-- Password baru -->
            <div class="space-y-1.5">
                <label for="password" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Password baru</label>
                <div class="relative">
                    <Lock :size="15" class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 dark:text-zinc-500 pointer-events-none"/>
                    <input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                        placeholder="Min. 8 karakter"
                        class="w-full pl-9 pr-10 py-2.5 rounded-xl border text-sm transition-colors
                               bg-white dark:bg-zinc-800
                               border-zinc-300 dark:border-zinc-700
                               text-zinc-900 dark:text-zinc-100
                               placeholder-zinc-400 dark:placeholder-zinc-500
                               focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400"
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

            <!-- Konfirmasi -->
            <div class="space-y-1.5">
                <label for="password_confirmation" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Konfirmasi password</label>
                <div class="relative">
                    <Lock :size="15" class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 dark:text-zinc-500 pointer-events-none"/>
                    <input
                        id="password_confirmation"
                        :type="showConfirm ? 'text' : 'password'"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Ulangi password baru"
                        class="w-full pl-9 pr-10 py-2.5 rounded-xl border text-sm transition-colors
                               bg-white dark:bg-zinc-800
                               border-zinc-300 dark:border-zinc-700
                               text-zinc-900 dark:text-zinc-100
                               placeholder-zinc-400 dark:placeholder-zinc-500
                               focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400"
                        :class="{ 'border-red-400': form.errors.password_confirmation }"
                    />
                    <button type="button" @click="showConfirm = !showConfirm"
                        :aria-label="showConfirm ? 'Sembunyikan konfirmasi password' : 'Tampilkan konfirmasi password'"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors">
                        <EyeOff v-if="showConfirm" :size="15"/>
                        <Eye v-else :size="15"/>
                    </button>
                </div>
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl font-semibold text-sm
                       bg-indigo-500 hover:bg-indigo-400 active:bg-indigo-600
                       text-white transition-colors shadow-md shadow-indigo-500/20
                       disabled:opacity-60 disabled:cursor-not-allowed"
            >
                <Loader2 v-if="form.processing" :size="16" class="animate-spin"/>
                <KeyRound v-else :size="16"/>
                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan password baru' }}</span>
            </button>
        </form>
    </GuestLayout>
</template>
