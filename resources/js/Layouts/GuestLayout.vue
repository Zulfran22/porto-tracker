<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<template>
    <div class="min-h-screen bg-zinc-50 dark:bg-zinc-950 flex flex-col items-center justify-center px-4 py-12 transition-colors">

        <!-- Logo -->
        <Link href="/" class="flex items-center gap-3 mb-8 group">
            <img src="/icons/icon.png" alt="WealthID" class="w-10 h-10 rounded-2xl shadow-lg shadow-indigo-500/30 group-hover:shadow-indigo-500/50 transition-shadow"/>
            <div>
                <p class="font-bold text-zinc-900 dark:text-white text-base leading-tight">WealthID</p>
                <p class="text-xs text-zinc-500 leading-tight">Investasi emas &amp; saving</p>
            </div>
        </Link>

        <!-- Card -->
        <div class="w-full max-w-sm bg-white dark:bg-zinc-900 rounded-2xl shadow-xl shadow-zinc-200/60 dark:shadow-zinc-950/80 border border-zinc-200 dark:border-zinc-800 px-6 py-8">
            <slot />
        </div>

        <p class="mt-6 text-xs text-zinc-400">© {{ new Date().getFullYear() }} WealthID</p>
    </div>
</template>
