<script setup>
import { Skeleton } from '@/Components/ui/skeleton'
import { Card, CardContent, CardHeader } from '@/Components/ui/card'
</script>

<template>
    <div class="py-5 space-y-3" role="status" aria-live="polite" aria-label="Memuat halaman">
        <div class="flex items-center justify-between mb-1">
            <Skeleton class="h-3 w-32"/>
            <Skeleton class="h-5 w-24 rounded-full"/>
        </div>

        <Skeleton class="h-12 w-full rounded-xl"/>

        <div class="grid grid-cols-2 gap-2">
            <Card v-for="i in 4" :key="i" class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                <CardContent class="p-3 space-y-2">
                    <Skeleton class="h-3 w-20"/>
                    <Skeleton class="h-4 w-16"/>
                </CardContent>
            </Card>
        </div>

        <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
            <CardHeader class="pb-2 pt-4 px-4">
                <Skeleton class="h-3 w-28"/>
            </CardHeader>
            <CardContent class="px-4 pb-4">
                <Skeleton class="h-36 w-full rounded-lg"/>
            </CardContent>
        </Card>

        <Card class="border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
            <CardContent class="p-0">
                <div v-for="i in 4" :key="i" class="flex items-center gap-3 px-4 py-3"
                     :class="i < 4 ? 'border-b border-zinc-100 dark:border-zinc-800' : ''">
                    <Skeleton class="w-8 h-8 rounded-lg shrink-0"/>
                    <div class="flex-1 space-y-1.5">
                        <Skeleton class="h-3.5 w-1/3"/>
                        <Skeleton class="h-3 w-1/2"/>
                    </div>
                    <Skeleton class="h-3.5 w-14 shrink-0"/>
                </div>
            </CardContent>
        </Card>

        <span class="sr-only">Memuat halaman...</span>
    </div>
</template>
