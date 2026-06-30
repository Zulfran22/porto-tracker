<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import InputError from '@/Components/InputError.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Mail, ArrowLeft, Loader2, Send } from 'lucide-vue-next'

defineProps({
    status: String,
})

const form = useForm({
    email: '',
})

const submit = () => {
    form.post(route('password.email'))
}
</script>

<template>
    <GuestLayout>
        <Head title="Lupa Password" />

        <div class="mb-6">
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white">Lupa password?</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Masukkan emailmu dan kami akan kirim link reset password.
            </p>
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-xl px-3 py-2.5">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
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
                        :class="{ 'border-red-400 dark:border-red-500': form.errors.email }"
                    />
                </div>
                <InputError :message="form.errors.email" />
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
                <Send v-else :size="16"/>
                <span>{{ form.processing ? 'Mengirim...' : 'Kirim link reset' }}</span>
            </button>
        </form>

        <div class="mt-6 text-center">
            <Link :href="route('login')" class="inline-flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400 hover:text-yellow-600 dark:hover:text-yellow-400 transition-colors">
                <ArrowLeft :size="14"/> Kembali ke halaman masuk
            </Link>
        </div>
    </GuestLayout>
</template>
