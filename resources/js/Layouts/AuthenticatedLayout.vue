<script setup>
import { ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import {
    LayoutDashboard, PlusCircle, BarChart2,
    Info, Target, LogOut, User, ChevronDown,
    ChevronRight, Sun, Moon, Wallet, FileText
} from 'lucide-vue-next'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'
import { Avatar, AvatarFallback } from '@/Components/ui/avatar'
import NavBottom from '@/Components/NavBottom.vue'
import { useTheme } from '@/Composables/useTheme'

const page = usePage()
const user = page.props.auth.user
const { isDark, toggle } = useTheme()

const initials = (name) => name?.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) ?? 'ZS'
const logout = () => router.post(route('logout'))

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
    if (item.match === '/dashboard') return page.url === '/' || page.url.startsWith('/dashboard')
    return page.url.startsWith(item.match)
}
</script>

<template>
    <div class="min-h-screen bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 transition-colors">

        <!-- TOP BAR — mobile only -->
        <header class="lg:hidden sticky top-0 z-40 w-full border-b border-zinc-200 dark:border-zinc-800/60 bg-white/90 dark:bg-zinc-950/90 backdrop-blur-md">
            <div class="max-w-lg mx-auto px-4 h-14 flex items-center justify-between">
                <Link :href="route('dashboard')" class="flex items-center gap-2.5">
                    <img src="/icons/icon.svg" alt="WealthID" class="w-8 h-8 rounded-xl shadow-lg shadow-yellow-500/20"/>
                    <span class="font-semibold text-sm text-zinc-900 dark:text-zinc-100 tracking-tight">WealthID</span>
                </Link>

                <div class="flex items-center gap-2">
                    <button @click="toggle"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        <Sun v-if="isDark" :size="16"/>
                        <Moon v-else :size="16"/>
                    </button>

                    <DropdownMenu>
                        <DropdownMenuTrigger class="outline-none flex items-center gap-2 group">
                            <Avatar class="w-8 h-8 cursor-pointer ring-2 ring-zinc-300 dark:ring-zinc-700 group-hover:ring-yellow-500 transition-all duration-200">
                                <AvatarFallback class="bg-yellow-500 text-black text-xs font-bold">
                                    {{ initials(user?.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <ChevronDown :size="14" class="text-zinc-500"/>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-52 bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100 shadow-xl">
                            <div class="px-3 py-2.5">
                                <p class="text-sm font-semibold">{{ user?.name }}</p>
                                <p class="text-xs text-zinc-500 mt-0.5">{{ user?.email }}</p>
                            </div>
                            <DropdownMenuSeparator class="bg-zinc-200 dark:bg-zinc-700"/>
                            <DropdownMenuItem as-child>
                                <Link :href="route('profile.edit')" class="cursor-pointer flex items-center gap-2 hover:text-yellow-600 dark:hover:text-white px-3 py-2 text-sm">
                                    <User :size="15" class="text-zinc-500"/> Profile
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator class="bg-zinc-200 dark:bg-zinc-700"/>
                            <DropdownMenuItem @click="logout" class="cursor-pointer flex items-center gap-2 text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300 px-3 py-2 text-sm">
                                <LogOut :size="15"/> Log out
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </header>

        <!-- DESKTOP LAYOUT -->
        <div class="lg:flex lg:min-h-screen">

            <!-- SIDEBAR — desktop only -->
            <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-800">

                <div class="flex items-center gap-3 px-6 py-5 border-b border-zinc-200 dark:border-zinc-800">
                    <img src="/icons/icon.svg" alt="WealthID" class="w-9 h-9 rounded-xl shadow-lg shadow-yellow-500/20"/>
                    <div>
                        <p class="font-bold text-zinc-900 dark:text-white text-sm">WealthID</p>
                        <p class="text-xs text-zinc-500">Investasi emas & saving</p>
                    </div>
                </div>

                <nav class="flex-1 px-3 py-4 space-y-1">
                    <Link v-for="item in navItems" :key="item.name"
                        :href="route(item.route)"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group"
                        :class="isActive(item)
                            ? 'bg-yellow-500/15 text-yellow-600 dark:text-yellow-400'
                            : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800'">
                        <component :is="item.icon" :size="18"
                            :stroke-width="isActive(item) ? 2.5 : 1.8"
                            class="shrink-0 transition-colors"/>
                        <span class="text-sm font-medium">{{ item.name }}</span>
                        <ChevronRight v-if="isActive(item)" :size="14" class="ml-auto text-yellow-600/60 dark:text-yellow-400/60"/>
                    </Link>
                </nav>

                <!-- Toggle theme -->
                <div class="px-3 pb-2">
                    <button @click="toggle"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        <Sun v-if="isDark" :size="18"/>
                        <Moon v-else :size="18"/>
                        <span class="text-sm font-medium">{{ isDark ? 'Light mode' : 'Dark mode' }}</span>
                    </button>
                </div>

                <!-- User profile -->
                <div class="px-3 py-4 border-t border-zinc-200 dark:border-zinc-800">
                    <DropdownMenu>
                        <DropdownMenuTrigger class="w-full outline-none">
                            <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer">
                                <Avatar class="w-8 h-8 ring-2 ring-zinc-300 dark:ring-zinc-700">
                                    <AvatarFallback class="bg-yellow-500 text-black text-xs font-bold">
                                        {{ initials(user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="flex-1 text-left min-w-0">
                                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200 truncate">{{ user?.name }}</p>
                                    <p class="text-xs text-zinc-500 truncate">{{ user?.email }}</p>
                                </div>
                                <ChevronDown :size="14" class="text-zinc-500 shrink-0"/>
                            </div>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent side="top" align="start" class="w-52 bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100 shadow-xl mb-1">
                            <DropdownMenuItem as-child>
                                <Link :href="route('profile.edit')" class="cursor-pointer flex items-center gap-2 hover:text-yellow-600 dark:hover:text-white px-3 py-2 text-sm">
                                    <User :size="15" class="text-zinc-500"/> Profile
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator class="bg-zinc-200 dark:bg-zinc-700"/>
                            <DropdownMenuItem @click="logout" class="cursor-pointer flex items-center gap-2 text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300 px-3 py-2 text-sm">
                                <LogOut :size="15"/> Log out
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </aside>

            <!-- MAIN CONTENT -->
            <main class="flex-1 lg:ml-64 pb-24 lg:pb-8">
                <slot />
            </main>
        </div>

        <!-- BOTTOM NAV — mobile only -->
        <div class="lg:hidden">
            <NavBottom />
        </div>

    </div>
</template>