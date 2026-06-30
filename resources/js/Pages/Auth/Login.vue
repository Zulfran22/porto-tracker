<script setup>
import { ref } from 'vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import InputError from '@/Components/InputError.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Eye, EyeOff, Mail, Lock, ArrowRight, Loader2 } from 'lucide-vue-next'

defineProps({
    canResetPassword: Boolean,
    status: String,
})

const showPassword = ref(false)

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <GuestLayout>
        <Head title="Masuk" />

        <div class="mb-6">
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white">Selamat datang</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Masuk ke akun WealthID kamu</p>
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-xl px-3 py-2.5">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
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
                        autofocus
                        autocomplete="username"
                        placeholder="nama@email.com"
                        class="w-full pl-9 pr-4 py-2.5 rounded-xl border text-sm transition-colors
                               bg-white dark:bg-zinc-800
                               border-zinc-300 dark:border-zinc-700
                               text-zinc-900 dark:text-zinc-100
                               placeholder-zinc-400 dark:placeholder-zinc-500
                               focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400
                               dark:focus:ring-yellow-500 dark:focus:border-yellow-500"
                        :class="{ 'border-red-400 dark:border-red-500 focus:ring-red-400': form.errors.email }"
                    />
                </div>
                <InputError :message="form.errors.email" />
            </div>

            <!-- Password -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label for="password" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Password</label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs text-yellow-600 dark:text-yellow-400 hover:underline"
                    >Lupa password?</Link>
                </div>
                <div class="relative">
                    <Lock :size="15" class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 dark:text-zinc-500 pointer-events-none"/>
                    <input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full pl-9 pr-10 py-2.5 rounded-xl border text-sm transition-colors
                               bg-white dark:bg-zinc-800
                               border-zinc-300 dark:border-zinc-700
                               text-zinc-900 dark:text-zinc-100
                               placeholder-zinc-400 dark:placeholder-zinc-500
                               focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400
                               dark:focus:ring-yellow-500 dark:focus:border-yellow-500"
                        :class="{ 'border-red-400 dark:border-red-500 focus:ring-red-400': form.errors.password }"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors"
                    >
                        <EyeOff v-if="showPassword" :size="15"/>
                        <Eye v-else :size="15"/>
                    </button>
                </div>
                <InputError :message="form.errors.password" />
            </div>

            <!-- Remember me -->
            <label class="flex items-center gap-2.5 cursor-pointer group">
                <input
                    type="checkbox"
                    v-model="form.remember"
                    class="w-4 h-4 rounded border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-yellow-500 focus:ring-yellow-400 focus:ring-2 cursor-pointer"
                />
                <span class="text-sm text-zinc-600 dark:text-zinc-400 group-hover:text-zinc-900 dark:group-hover:text-zinc-200 transition-colors">Ingat saya</span>
            </label>

            <!-- Submit -->
            <button
                type="submit"
                :disabled="form.processing"
                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl font-semibold text-sm
                       bg-yellow-500 hover:bg-yellow-400 active:bg-yellow-600
                       text-black transition-colors shadow-md shadow-yellow-500/20
                       disabled:opacity-60 disabled:cursor-not-allowed"
            >
                <Loader2 v-if="form.processing" :size="16" class="animate-spin"/>
                <span>{{ form.processing ? 'Masuk...' : 'Masuk' }}</span>
                <ArrowRight v-if="!form.processing" :size="16"/>
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
            Belum punya akun?
            <Link :href="route('register')" class="text-yellow-600 dark:text-yellow-400 font-medium hover:underline ml-1">Daftar sekarang</Link>
        </p>
    </GuestLayout>
</template>
