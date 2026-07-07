<script setup>
import { computed } from 'vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { MailCheck, Loader2, RefreshCw, LogOut } from 'lucide-vue-next'

const props = defineProps({
    status: String,
})

const form = useForm({})

const submit = () => {
    form.post(route('verification.send'))
}

const verificationLinkSent = computed(() => props.status === 'verification-link-sent')
</script>

<template>
    <GuestLayout>
        <Head title="Verifikasi Email" />

        <div class="flex flex-col items-center text-center mb-6">
            <div class="w-14 h-14 rounded-2xl bg-indigo-100 dark:bg-indigo-500/15 flex items-center justify-center mb-4">
                <MailCheck :size="26" class="text-indigo-600 dark:text-indigo-400"/>
            </div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white">Verifikasi emailmu</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-2 max-w-xs">
                Terima kasih sudah mendaftar! Klik link verifikasi yang kami kirim ke emailmu untuk melanjutkan.
            </p>
        </div>

        <div
            v-if="verificationLinkSent"
            class="mb-4 text-sm font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-xl px-3 py-2.5 text-center"
        >
            Link verifikasi baru telah dikirim ke emailmu!
        </div>

        <form @submit.prevent="submit" class="space-y-3">
            <button
                type="submit"
                :disabled="form.processing"
                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl font-semibold text-sm
                       bg-indigo-500 hover:bg-indigo-400 active:bg-indigo-600
                       text-white transition-colors shadow-md shadow-indigo-500/20
                       disabled:opacity-60 disabled:cursor-not-allowed"
            >
                <Loader2 v-if="form.processing" :size="16" class="animate-spin"/>
                <RefreshCw v-else :size="16"/>
                <span>{{ form.processing ? 'Mengirim...' : 'Kirim ulang email verifikasi' }}</span>
            </button>

            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-medium
                       border border-zinc-200 dark:border-zinc-700
                       text-zinc-600 dark:text-zinc-400
                       hover:bg-zinc-50 dark:hover:bg-zinc-800
                       transition-colors"
            >
                <LogOut :size="15"/>
                <span>Keluar</span>
            </Link>
        </form>
    </GuestLayout>
</template>
