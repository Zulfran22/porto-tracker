<script setup>
import { ref } from 'vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import InputError from '@/Components/InputError.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Eye, EyeOff, Mail, Lock, User, ArrowRight, Loader2 } from 'lucide-vue-next'

const showPassword = ref(false)
const showConfirm = ref(false)

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <GuestLayout>
        <Head title="Daftar" />

        <div class="mb-6">
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white">Buat akun baru</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Mulai tracking investasimu hari ini</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Nama -->
            <div class="space-y-1.5">
                <label for="name" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nama lengkap</label>
                <div class="relative">
                    <User :size="15" class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 dark:text-zinc-500 pointer-events-none"/>
                    <input
                        id="name"
                        type="text"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Nama kamu"
                        class="w-full pl-9 pr-4 py-2.5 rounded-xl border text-sm transition-colors
                               bg-white dark:bg-zinc-800
                               border-zinc-300 dark:border-zinc-700
                               text-zinc-900 dark:text-zinc-100
                               placeholder-zinc-400 dark:placeholder-zinc-500
                               focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400
                               dark:focus:ring-yellow-500 dark:focus:border-yellow-500"
                        :class="{ 'border-red-400 dark:border-red-500': form.errors.name }"
                    />
                </div>
                <InputError :message="form.errors.name" />
            </div>

            <!-- Email -->
            <div class="space-y-1.5">
                <label for="email" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Email</label>
                <div class="relative">
                    <Mail :size="15" class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 dark:text-zinc-500 pointer-events-none"/>
                    <input
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autocomplete="username"
                        placeholder="nama@email.com"
                        class="w-full pl-9 pr-4 py-2.5 rounded-xl border text-sm transition-colors
                               bg-white dark:bg-zinc-800
                               border-zinc-300 dark:border-zinc-700
                               text-zinc-900 dark:text-zinc-100
                               placeholder-zinc-400 dark:placeholder-zinc-500
                               focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400
                               dark:focus:ring-yellow-500 dark:focus:border-yellow-500"
                        :class="{ 'border-red-400 dark:border-red-500': form.errors.email }"
                    />
                </div>
                <InputError :message="form.errors.email" />
            </div>

            <!-- Password -->
            <div class="space-y-1.5">
                <label for="password" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Password</label>
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
                               focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400
                               dark:focus:ring-yellow-500 dark:focus:border-yellow-500"
                        :class="{ 'border-red-400 dark:border-red-500': form.errors.password }"
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

            <!-- Konfirmasi Password -->
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
                        placeholder="Ulangi password"
                        class="w-full pl-9 pr-10 py-2.5 rounded-xl border text-sm transition-colors
                               bg-white dark:bg-zinc-800
                               border-zinc-300 dark:border-zinc-700
                               text-zinc-900 dark:text-zinc-100
                               placeholder-zinc-400 dark:placeholder-zinc-500
                               focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400
                               dark:focus:ring-yellow-500 dark:focus:border-yellow-500"
                        :class="{ 'border-red-400 dark:border-red-500': form.errors.password_confirmation }"
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

            <!-- Submit -->
            <button
                type="submit"
                :disabled="form.processing"
                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl font-semibold text-sm
                       bg-yellow-500 hover:bg-yellow-400 active:bg-yellow-600
                       text-black transition-colors shadow-md shadow-yellow-500/20
                       disabled:opacity-60 disabled:cursor-not-allowed mt-2"
            >
                <Loader2 v-if="form.processing" :size="16" class="animate-spin"/>
                <span>{{ form.processing ? 'Mendaftar...' : 'Daftar sekarang' }}</span>
                <ArrowRight v-if="!form.processing" :size="16"/>
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
            Sudah punya akun?
            <Link :href="route('login')" class="text-yellow-600 dark:text-yellow-400 font-medium hover:underline ml-1">Masuk</Link>
        </p>
    </GuestLayout>
</template>
