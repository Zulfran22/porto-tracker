<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { LayoutDashboard, PlusCircle, BarChart2, Info, Target, Wallet, FileText } from 'lucide-vue-next'

const page = usePage()

const navItems = [
    { name: 'Dashboard', icon: LayoutDashboard, route: 'dashboard',        match: '/dashboard' },
    { name: 'Catat',     icon: PlusCircle,      route: 'portofolio.create', match: '/catat' },
    { name: 'Grafik',    icon: BarChart2,        route: 'grafik',            match: '/grafik' },
    { name: 'Keuangan',  icon: Wallet,           route: 'keuangan.index',    match: '/keuangan' },
    { name: 'Kontrak',   icon: FileText,         route: 'kontrak-cicilan.index', match: '/kontrak-cicilan' },
    { name: 'Info',      icon: Info,             route: 'info',              match: '/info' },
    { name: 'Target',    icon: Target,           route: 'target',            match: '/target' },
]

const isActive = (item) => {
    if (item.match === '/dashboard') {
        return page.url === '/' || page.url.startsWith('/dashboard')
    }
    return page.url.startsWith(item.match)
}
</script>

<template>
    <nav class="fixed bottom-0 left-0 right-0 z-50">
        <div class="max-w-lg mx-auto overflow-x-auto">
            <div class="bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800 px-1 pt-2 pb-5">
                <div class="flex justify-around items-center min-w-max gap-1">
                    <Link
                        v-for="item in navItems"
                        :key="item.name"
                        :href="route(item.route)"
                        class="flex flex-col items-center gap-1 px-2.5 py-1 rounded-xl transition-all duration-200 min-w-[52px] group">

                        <div class="w-9 h-8 flex items-center justify-center rounded-xl transition-all duration-200"
                             :class="isActive(item)
                                ? 'bg-yellow-400/15'
                                : 'group-hover:bg-zinc-100 dark:group-hover:bg-zinc-800'">
                            <component
                                :is="item.icon"
                                :size="19"
                                :stroke-width="isActive(item) ? 2.5 : 1.8"
                                :class="isActive(item) ? 'text-yellow-500 dark:text-yellow-400' : 'text-zinc-400 group-hover:text-zinc-700 dark:group-hover:text-zinc-300'"
                                class="transition-colors duration-200"/>
                        </div>

                        <span class="text-[9.5px] font-medium leading-none tracking-wide transition-colors duration-200"
                              :class="isActive(item) ? 'text-yellow-500 dark:text-yellow-400' : 'text-zinc-400 group-hover:text-zinc-700 dark:group-hover:text-zinc-300'">
                            {{ item.name }}
                        </span>

                    </Link>
                </div>
            </div>
        </div>
    </nav>
</template>