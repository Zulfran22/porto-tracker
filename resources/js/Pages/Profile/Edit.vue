<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DeleteUserForm from './Partials/DeleteUserForm.vue'
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue'
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue'
import { Head } from '@inertiajs/vue3'

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
})
</script>

<template>
    <Head title="Profil" />

    <AuthenticatedLayout>
        <div class="max-w-lg mx-auto lg:max-w-2xl px-4 py-6 space-y-4">

            <p class="text-xs text-zinc-500 uppercase tracking-widest font-medium">Pengaturan akun</p>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5">
                <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status"/>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5">
                <UpdatePasswordForm/>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-red-100 dark:border-red-900/40 rounded-2xl p-5">
                <DeleteUserForm/>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
