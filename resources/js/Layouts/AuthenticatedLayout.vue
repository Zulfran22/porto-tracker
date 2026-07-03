<script setup>
import { ref, computed } from 'vue'
import { Link, usePage, useForm } from '@inertiajs/vue3'
import {
    LayoutDashboard,
    ChartNoAxesColumn,
    CirclePlus,
    Wallet,
    FileText,
    Info,
    Target,
    Sun,
    Moon,
    LogOut,
    ChevronDown,
    User,
    Lock,
    Coins,
    Shield,
    TrendingUp,
    Landmark,
    StickyNote,
    Globe,
    Loader2,
    AlertTriangle,
    Calendar,
    X
} from 'lucide-vue-next'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator
} from '@/Components/ui/dropdown-menu'
import { Avatar, AvatarFallback } from '@/Components/ui/avatar'
import { Separator } from '@/Components/ui/separator'
import { useTheme } from '@/Composables/useTheme'

const page = usePage()
const user = computed(() => page.props.auth.user)

// Single source of truth for dark mode — shared with every page that themes
// its Chart.js instances off this same composable (Dashboard/Keuangan/Grafik).
// A separate local isDark here previously let the toggle flip the DOM/localStorage
// state without those charts ever finding out, leaving them stuck on the old theme.
const { isDark, toggle: toggleTheme } = useTheme()

function isActive(routeName) {
    return route().current(routeName)
}

function getInitials(name) {
    return name?.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) || 'U'
}

// Modal "Catat" — dipicu dari FAB di semua halaman. Karena halaman selain
// Dashboard tidak menerima props portofolios/aktifKontrak, konteksnya diambil
// sendiri lewat /api/catat-context saat modal dibuka.
const showCatatModal = ref(false)
const existingId = ref(null)
const loadingContext = ref(false)
const contextError = ref('')
const lastRequestedId = ref(null)
const catatForm = useForm({
    bulan: '',
    emas_gram: '',
    harga_emas: '',
    cicilan: '',
    dana_darurat: 0,
    reksa_dana: 0,
    sbn: 0,
    catatan: '',
})

const loadingHarga = ref(false)
const errorHarga   = ref('')
const hargaFetched = ref(null)

async function fetchHargaEmas() {
    loadingHarga.value = true
    errorHarga.value   = ''
    hargaFetched.value = null
    try {
        const res  = await fetch('/api/harga-emas')
        const data = await res.json()
        if (!data.success) throw new Error(data.message)
        hargaFetched.value = {
            spotIdr:       data.spot_idr.toLocaleString('id-ID'),
            pegadaian:     data.pegadaian,
            markupPercent: data.markup_percent,
        }
        catatForm.harga_emas = data.pegadaian
    } catch {
        errorHarga.value = 'Gagal ambil harga — isi manual.'
    } finally {
        loadingHarga.value = false
    }
}

async function openCatat(id = null) {
    lastRequestedId.value = id
    showCatatModal.value = true
    loadingContext.value = true
    contextError.value = ''
    errorHarga.value = ''
    hargaFetched.value = null
    catatForm.clearErrors()
    try {
        const res = await fetch(id ? route('catat.context', { id }) : route('catat.context'))
        if (!res.ok) throw new Error('catat-context request failed')
        const data = await res.json()
        const p = data.existing

        existingId.value       = p ? p.id : null
        catatForm.bulan        = data.bulan
        catatForm.emas_gram    = p ? p.emas_gram : ''
        catatForm.harga_emas   = p ? p.harga_emas : (data.lastHargaEmas ?? '')
        catatForm.cicilan      = p ? p.cicilan : (data.aktifKontrak ? Number(data.aktifKontrak.angsuran_bulan) : '')
        catatForm.dana_darurat = p ? p.dana_darurat : 0
        catatForm.reksa_dana   = p ? p.reksa_dana : 0
        catatForm.sbn          = p ? p.sbn : 0
        catatForm.catatan      = p ? (p.catatan ?? '') : ''
    } catch {
        contextError.value = 'Gagal memuat data. Coba lagi.'
    } finally {
        loadingContext.value = false
    }
}

function retryOpenCatat() {
    openCatat(lastRequestedId.value)
}

function closeCatat() {
    showCatatModal.value = false
}

function submitCatat() {
    const options = { onSuccess: () => { showCatatModal.value = false } }
    if (existingId.value) {
        catatForm.put(route('portofolio.update', existingId.value), options)
    } else {
        catatForm.post(route('portofolio.store'), options)
    }
}

const inputClass = "w-full bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl px-3 py-2.5 text-sm text-zinc-900 dark:text-white focus:border-yellow-500 focus:ring-0 outline-none transition-colors placeholder:text-zinc-400 dark:placeholder:text-zinc-600"

defineExpose({ openCatat })
</script>

<template>
    <div class="min-h-screen bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 transition-colors">

        <!-- Mobile Header -->
        <header class="lg:hidden sticky top-0 z-40 w-full border-b border-zinc-200 dark:border-zinc-800/60 bg-white/90 dark:bg-zinc-950/90 backdrop-blur-md">
            <div class="max-w-lg mx-auto px-4 h-14 flex items-center justify-between">
                <Link href="/dashboard" class="flex items-center gap-2.5">
                    <img src="/icons/icon.svg" alt="WealthID" class="w-8 h-8 rounded-xl shadow-lg shadow-yellow-500/20">
                    <span class="font-semibold text-sm text-zinc-900 dark:text-zinc-100 tracking-tight">WealthID</span>
                </Link>
                <div class="flex items-center gap-2">
                    <button @click="toggleTheme"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        <Sun v-if="isDark" :size="16" />
                        <Moon v-else :size="16" />
                    </button>
                    <DropdownMenu>
                        <DropdownMenuTrigger class="outline-none">
                            <div class="flex items-center gap-1.5">
                                <Avatar class="size-8">
                                    <AvatarFallback class="bg-yellow-500 text-black text-xs font-bold">
                                        {{ getInitials(user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <ChevronDown :size="14" class="text-zinc-400" />
                            </div>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-48">
                            <DropdownMenuItem as-child>
                                <Link href="/profile" class="flex items-center gap-2">
                                    <User :size="14" />
                                    <span>Profil</span>
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem as-child>
                                <Link href="/logout" method="post" as="button" class="flex items-center gap-2 text-red-500 w-full">
                                    <LogOut :size="14" />
                                    <span>Keluar</span>
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </header>

        <!-- Desktop Layout -->
        <div class="lg:flex lg:min-h-screen">

            <!-- Desktop Sidebar -->
            <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-800">
                <!-- Logo -->
                <div class="flex items-center gap-3 px-6 py-5 border-b border-zinc-200 dark:border-zinc-800">
                    <img src="/icons/icon.svg" alt="WealthID" class="w-9 h-9 rounded-xl shadow-lg shadow-yellow-500/20">
                    <div>
                        <p class="font-bold text-zinc-900 dark:text-zinc-100 text-sm">WealthID</p>
                        <p class="text-xs text-zinc-500">Investasi emas & saving</p>
                    </div>
                </div>

                <!-- Nav -->
                <nav class="flex-1 px-3 py-4 space-y-1">
                    <Link v-for="item in [
                        { name: 'Dashboard', route: 'dashboard', href: '/dashboard', icon: 'dashboard' },
                        { name: 'Catat', route: 'portofolio.create', href: '/catat', icon: 'catat' },
                        { name: 'Grafik', route: 'grafik', href: '/grafik', icon: 'grafik' },
                        { name: 'Keuangan', route: 'keuangan.index', href: '/keuangan', icon: 'keuangan' },
                        { name: 'Kontrak', route: 'kontrak-cicilan.index', href: '/kontrak-cicilan', icon: 'kontrak' },
                        { name: 'Info', route: 'info', href: '/info', icon: 'info' },
                        { name: 'Target', route: 'target', href: '/target', icon: 'target' },
                    ]" :key="item.name"
                        :href="item.href"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group"
                        :class="route().current(item.route)
                            ? 'bg-yellow-500/15 text-yellow-600 dark:text-yellow-400'
                            : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-100'">
                        <LayoutDashboard v-if="item.icon === 'dashboard'" :size="18" stroke-width="2.5" class="shrink-0 transition-colors" />
                        <CirclePlus v-else-if="item.icon === 'catat'" :size="18" stroke-width="1.8" class="shrink-0 transition-colors" />
                        <ChartNoAxesColumn v-else-if="item.icon === 'grafik'" :size="18" stroke-width="1.8" class="shrink-0 transition-colors" />
                        <Wallet v-else-if="item.icon === 'keuangan'" :size="18" stroke-width="1.8" class="shrink-0 transition-colors" />
                        <FileText v-else-if="item.icon === 'kontrak'" :size="18" stroke-width="1.8" class="shrink-0 transition-colors" />
                        <Info v-else-if="item.icon === 'info'" :size="18" stroke-width="1.8" class="shrink-0 transition-colors" />
                        <Target v-else-if="item.icon === 'target'" :size="18" stroke-width="1.8" class="shrink-0 transition-colors" />
                        <span class="text-sm font-medium">{{ item.name }}</span>
                    </Link>
                </nav>

                <!-- Theme Toggle -->
                <div class="px-3 pb-2">
                    <button @click="toggleTheme"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors text-sm">
                        <Sun v-if="isDark" :size="18" />
                        <Moon v-else :size="18" />
                        <span>{{ isDark ? 'Light mode' : 'Dark mode' }}</span>
                    </button>
                </div>

                <!-- User -->
                <div class="px-3 py-4 border-t border-zinc-200 dark:border-zinc-800">
                    <DropdownMenu>
                        <DropdownMenuTrigger class="w-full outline-none">
                            <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer">
                                <Avatar class="size-8">
                                    <AvatarFallback class="bg-yellow-500 text-black text-xs font-bold">
                                        {{ getInitials(user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="flex-1 text-left min-w-0">
                                    <p class="text-sm font-medium truncate">{{ user?.name }}</p>
                                    <p class="text-xs text-zinc-500 truncate">{{ user?.email }}</p>
                                </div>
                                <ChevronDown :size="14" class="text-zinc-400 shrink-0" />
                            </div>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" side="top" class="w-48">
                            <DropdownMenuItem as-child>
                                <Link href="/profile" class="flex items-center gap-2">
                                    <User :size="14" />
                                    <span>Profil</span>
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem as-child>
                                <Link href="/logout" method="post" as="button" class="flex items-center gap-2 text-red-500 w-full">
                                    <LogOut :size="14" />
                                    <span>Keluar</span>
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 lg:ml-64 pb-24 lg:pb-8">
                <div class="max-w-lg mx-auto px-4 lg:max-w-3xl lg:py-8">
                    <slot />
                </div>
            </main>
        </div>

        <!-- Mobile Bottom Nav - COMPACT 5 item -->
        <nav class="fixed bottom-0 left-0 right-0 z-50 lg:hidden">
            <div class="max-w-lg mx-auto">
                <div class="bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800 px-2 pt-2 pb-5">
                    <div class="flex justify-around items-end">

                        <!-- Dashboard -->
                        <Link :href="route('dashboard')"
                            class="flex flex-col items-center gap-1 px-3 py-1 rounded-xl transition-all duration-200 group"
                            :class="route().current('dashboard') ? 'text-yellow-500' : 'text-zinc-400'">
                            <div class="w-9 h-8 flex items-center justify-center rounded-xl transition-all"
                                :class="route().current('dashboard') ? 'bg-yellow-400/15' : 'group-hover:bg-zinc-100 dark:group-hover:bg-zinc-800'">
                                <LayoutDashboard :size="19"
                                    :stroke-width="route().current('dashboard') ? 2.5 : 1.8"
                                    :class="route().current('dashboard') ? 'text-yellow-500 dark:text-yellow-400' : 'text-zinc-400 group-hover:text-zinc-700 dark:group-hover:text-zinc-300'" />
                            </div>
                            <span class="text-xs font-medium">Dashboard</span>
                        </Link>

                        <!-- Grafik -->
                        <Link :href="route('grafik')"
                            class="flex flex-col items-center gap-1 px-3 py-1 rounded-xl transition-all duration-200 group"
                            :class="route().current('grafik') ? 'text-yellow-500' : 'text-zinc-400'">
                            <div class="w-9 h-8 flex items-center justify-center rounded-xl transition-all"
                                :class="route().current('grafik') ? 'bg-yellow-400/15' : 'group-hover:bg-zinc-100 dark:group-hover:bg-zinc-800'">
                                <ChartNoAxesColumn :size="19"
                                    :stroke-width="route().current('grafik') ? 2.5 : 1.8"
                                    :class="route().current('grafik') ? 'text-yellow-500 dark:text-yellow-400' : 'text-zinc-400 group-hover:text-zinc-700 dark:group-hover:text-zinc-300'" />
                            </div>
                            <span class="text-xs font-medium">Grafik</span>
                        </Link>

                        <!-- CATAT FAB -->
                        <button @click="openCatat"
                            class="flex flex-col items-center gap-1 px-3 pb-1 rounded-xl transition-all duration-200 group relative">
                            <div class="w-12 h-10 flex items-center justify-center rounded-2xl bg-yellow-500 shadow-lg shadow-yellow-500/30 -mt-5 transition-transform active:scale-95">
                                <CirclePlus :size="22" stroke-width="2.5" class="text-white" />
                            </div>
                            <span class="text-xs font-medium text-yellow-500">Catat</span>
                        </button>

                        <!-- Keuangan -->
                        <Link :href="route('keuangan.index')"
                            class="flex flex-col items-center gap-1 px-3 py-1 rounded-xl transition-all duration-200 group"
                            :class="route().current('keuangan.index') ? 'text-yellow-500' : 'text-zinc-400'">
                            <div class="w-9 h-8 flex items-center justify-center rounded-xl transition-all"
                                :class="route().current('keuangan.index') ? 'bg-yellow-400/15' : 'group-hover:bg-zinc-100 dark:group-hover:bg-zinc-800'">
                                <Wallet :size="19"
                                    :stroke-width="route().current('keuangan.index') ? 2.5 : 1.8"
                                    :class="route().current('keuangan.index') ? 'text-yellow-500 dark:text-yellow-400' : 'text-zinc-400 group-hover:text-zinc-700 dark:group-hover:text-zinc-300'" />
                            </div>
                            <span class="text-xs font-medium">Keuangan</span>
                        </Link>

                        <!-- Target -->
                        <Link :href="route('target')"
                            class="flex flex-col items-center gap-1 px-3 py-1 rounded-xl transition-all duration-200 group"
                            :class="route().current('target') ? 'text-yellow-500' : 'text-zinc-400'">
                            <div class="w-9 h-8 flex items-center justify-center rounded-xl transition-all"
                                :class="route().current('target') ? 'bg-yellow-400/15' : 'group-hover:bg-zinc-100 dark:group-hover:bg-zinc-800'">
                                <Target :size="19"
                                    :stroke-width="route().current('target') ? 2.5 : 1.8"
                                    :class="route().current('target') ? 'text-yellow-500 dark:text-yellow-400' : 'text-zinc-400 group-hover:text-zinc-700 dark:group-hover:text-zinc-300'" />
                            </div>
                            <span class="text-xs font-medium">Target</span>
                        </Link>

                    </div>
                </div>
            </div>
        </nav>

    </div>

    <!-- CATAT BOTTOM SHEET -->
    <Teleport to="body">
        <Transition enter-active-class="transition" enter-from-class="opacity-0" leave-active-class="transition" leave-to-class="opacity-0">
            <div v-if="showCatatModal" class="fixed inset-0 z-50 flex items-end justify-center" @click.self="closeCatat">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"/>
                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="translate-y-full"
                    enter-to-class="translate-y-0"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="translate-y-0"
                    leave-to-class="translate-y-full"
                    appear>
                    <div v-if="showCatatModal" class="relative w-full max-w-lg max-h-[90vh] overflow-y-auto bg-white dark:bg-zinc-900 rounded-t-3xl shadow-2xl border-t border-x border-zinc-200 dark:border-zinc-800 px-4 pt-3 pb-6">
                        <div class="w-10 h-1.5 rounded-full bg-zinc-300 dark:bg-zinc-700 mx-auto mb-3"/>
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-zinc-900 dark:text-white text-sm">{{ existingId ? 'Edit' : 'Catat' }} data portofolio</h3>
                            <button @click="closeCatat" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors shrink-0">
                                <X :size="18"/>
                            </button>
                        </div>

                        <div v-if="loadingContext" class="flex items-center justify-center py-10">
                            <Loader2 :size="24" class="animate-spin text-zinc-400"/>
                        </div>

                        <div v-else-if="contextError" class="flex flex-col items-center gap-3 py-10 text-center">
                            <AlertTriangle :size="24" class="text-red-500"/>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ contextError }}</p>
                            <button type="button" @click="retryOpenCatat"
                                class="text-xs px-3 py-1.5 rounded-lg border border-zinc-300 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                Coba lagi
                            </button>
                        </div>

                        <form v-else @submit.prevent="submitCatat" class="space-y-3">

                            <div>
                                <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                    <Calendar :size="12" class="text-zinc-400"/> Bulan & tahun
                                </label>
                                <input type="month" v-model="catatForm.bulan" :class="inputClass"/>
                                <p v-if="catatForm.errors.bulan" class="text-xs text-red-500 mt-1">{{ catatForm.errors.bulan }}</p>
                            </div>

                            <div>
                                <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                    <Lock :size="12" class="text-yellow-600"/> Cicilan emas (Rp)
                                </label>
                                <input type="number" v-model="catatForm.cicilan" :class="inputClass"/>
                                <p v-if="catatForm.errors.cicilan" class="text-xs text-red-500 mt-1">{{ catatForm.errors.cicilan }}</p>
                            </div>

                            <div>
                                <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                    <Coins :size="12" class="text-yellow-500 dark:text-yellow-400"/> Emas tunai — total gram dimiliki
                                </label>
                                <input type="number" step="0.01" v-model="catatForm.emas_gram" placeholder="mis. 0.50" :class="inputClass"/>
                                <p v-if="catatForm.errors.emas_gram" class="text-xs text-red-500 mt-1">{{ catatForm.errors.emas_gram }}</p>
                            </div>

                            <div>
                                <div class="flex justify-between items-center mb-1.5">
                                    <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5">
                                        <Globe :size="12" class="text-zinc-400"/> Harga emas (Rp/gram)
                                    </label>
                                    <button type="button" @click="fetchHargaEmas" :disabled="loadingHarga"
                                        class="text-xs px-3 py-1.5 rounded-lg border border-yellow-400/60 dark:border-yellow-700/50 text-yellow-600 dark:text-yellow-400 hover:bg-yellow-50 dark:hover:bg-yellow-950/50 disabled:opacity-50 transition-colors flex items-center gap-1.5">
                                        <Loader2 v-if="loadingHarga" :size="12" class="animate-spin"/>
                                        <Globe v-else :size="12"/>
                                        <span>{{ loadingHarga ? 'Mengambil...' : 'Ambil harga' }}</span>
                                    </button>
                                </div>
                                <div v-if="hargaFetched" class="bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-yellow-700/20 rounded-xl p-3 mb-2 space-y-1.5">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-zinc-500">Spot/gram</span>
                                        <span class="text-zinc-700 dark:text-zinc-300">Rp{{ hargaFetched.spotIdr }}</span>
                                    </div>
                                    <Separator class="bg-zinc-200 dark:bg-zinc-700 my-1"/>
                                    <div class="flex justify-between text-xs">
                                        <span class="text-yellow-600 dark:text-yellow-400 font-medium">Est. Pegadaian (+{{ hargaFetched.markupPercent }}%)</span>
                                        <span class="text-yellow-600 dark:text-yellow-400 font-medium">Rp{{ hargaFetched.pegadaian.toLocaleString('id-ID') }}</span>
                                    </div>
                                </div>
                                <div v-if="errorHarga" class="bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl p-2.5 mb-2 text-xs text-red-600 dark:text-red-400 flex items-center gap-1.5">
                                    <AlertTriangle :size="12"/> {{ errorHarga }}
                                </div>
                                <input type="number" v-model="catatForm.harga_emas" placeholder="mis. 2545000" :class="inputClass"/>
                                <p v-if="catatForm.errors.harga_emas" class="text-xs text-red-500 mt-1">{{ catatForm.errors.harga_emas }}</p>
                            </div>

                            <div>
                                <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                    <Shield :size="12" class="text-blue-500 dark:text-blue-400"/> Dana darurat — RDPU (Rp)
                                </label>
                                <input type="number" v-model="catatForm.dana_darurat" :class="inputClass"/>
                            </div>

                            <div>
                                <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                    <TrendingUp :size="12" class="text-green-500 dark:text-green-400"/> Reksa dana (Rp)
                                </label>
                                <input type="number" v-model="catatForm.reksa_dana" :class="inputClass"/>
                            </div>

                            <div>
                                <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                    <Landmark :size="12" class="text-purple-500 dark:text-purple-400"/> SBN / Deposito (Rp)
                                </label>
                                <input type="number" v-model="catatForm.sbn" :class="inputClass"/>
                            </div>

                            <div>
                                <label class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                    <StickyNote :size="12" class="text-zinc-400"/> Catatan (opsional)
                                </label>
                                <input type="text" v-model="catatForm.catatan" placeholder="mis. dapat diskon GAJIANEMAS" :class="inputClass"/>
                            </div>

                            <div class="flex gap-2 pt-1">
                                <button type="button" @click="closeCatat"
                                    class="flex-1 py-2.5 rounded-xl text-sm font-medium border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                    Batal
                                </button>
                                <button type="submit" :disabled="catatForm.processing"
                                    class="flex-1 flex items-center justify-center gap-1.5 py-2.5 rounded-xl text-sm font-semibold bg-yellow-500 hover:bg-yellow-400 text-black disabled:opacity-50 transition-colors">
                                    <Loader2 v-if="catatForm.processing" :size="14" class="animate-spin"/>
                                    <span>{{ catatForm.processing ? 'Menyimpan...' : 'Simpan' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
