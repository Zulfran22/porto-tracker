<script setup>
import InputError from '@/Components/InputError.vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { User, Mail, Save, CheckCircle } from 'lucide-vue-next'

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
})

const user = usePage().props.auth.user

const form = useForm({
    name: user.name,
    email: user.email,
})
</script>

<template>
    <section>
        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white mb-0.5">Informasi profil</h2>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-5">Perbarui nama dan alamat email akunmu.</p>

        <form @submit.prevent="form.patch(route('profile.update'))" class="space-y-4">
            <div class="space-y-1.5">
                <label for="name" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nama</label>
                <div class="relative">
                    <User :size="15" class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none"/>
                    <input id="name" type="text" v-model="form.name" required autofocus autocomplete="name"
                        class="w-full pl-9 pr-4 py-2.5 rounded-xl border text-sm transition-colors
                               bg-white dark:bg-zinc-800 border-zinc-300 dark:border-zinc-700
                               text-zinc-900 dark:text-zinc-100
                               focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400"
                        :class="{ 'border-red-400': form.errors.name }"/>
                </div>
                <InputError :message="form.errors.name"/>
            </div>

            <div class="space-y-1.5">
                <label for="email" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Email</label>
                <div class="relative">
                    <Mail :size="15" class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none"/>
                    <input id="email" type="email" v-model="form.email" required autocomplete="username"
                        class="w-full pl-9 pr-4 py-2.5 rounded-xl border text-sm transition-colors
                               bg-white dark:bg-zinc-800 border-zinc-300 dark:border-zinc-700
                               text-zinc-900 dark:text-zinc-100
                               focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400"
                        :class="{ 'border-red-400': form.errors.email }"/>
                </div>
                <InputError :message="form.errors.email"/>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null"
                class="text-xs text-zinc-500 dark:text-zinc-400 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700/40 rounded-xl px-3 py-2.5">
                Email belum diverifikasi.
                <Link :href="route('verification.send')" method="post" as="button"
                    class="text-yellow-600 dark:text-yellow-400 underline ml-1">
                    Kirim ulang verifikasi
                </Link>
                <span v-show="status === 'verification-link-sent'" class="block mt-1 text-green-600 dark:text-green-400 font-medium">
                    Link verifikasi sudah dikirim!
                </span>
            </div>

            <div class="flex items-center gap-3 pt-1">
                <button type="submit" :disabled="form.processing"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold
                           bg-yellow-500 hover:bg-yellow-400 text-black transition-colors
                           disabled:opacity-60 disabled:cursor-not-allowed">
                    <Save :size="14"/>
                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
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
