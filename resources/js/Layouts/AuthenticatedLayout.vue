<script setup>
import { ref, computed, onBeforeUnmount } from 'vue'
import { Link, usePage, useForm, router } from '@inertiajs/vue3'
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
    StickyNote,
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
import PageSkeleton from '@/Components/PageSkeleton.vue'
import PortfolioItemFields from '@/Components/PortfolioItemFields.vue'
import { useTheme } from '@/Composables/useTheme'
import { inputClass } from '@/Composables/useFormStyles'
import { useEscapeKey } from '@/Composables/useEscapeKey'

const page = usePage()
const user = computed(() => page.props.auth.user)

// Single source of truth for dark mode — shared with every page that themes
// its Chart.js instances off this same composable (Dashboard/Keuangan/Grafik).
// A separate local isDark here previously let the toggle flip the DOM/localStorage
// state without those charts ever finding out, leaving them stuck on the old theme.
const { isDark, toggle: toggleTheme } = useTheme()

// Skeleton loading saat pindah halaman (klik Link di nav) — dibedakan dari
// visit non-GET (submitBudget, hapus, dsb.) yang pakai preserveScroll supaya
// aksi CRUD kecil tidak ikut memicu skeleton full-page.
const isNavigating = ref(false)
const offNavStart = router.on('start', (event) => {
    const visit = event.detail.visit
    if (visit.method === 'get' && !visit.only?.length) isNavigating.value = true
})
const offNavFinish = router.on('finish', () => { isNavigating.value = false })
onBeforeUnmount(() => { offNavStart(); offNavFinish() })

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
const modalInvestmentTypes = ref([])
const modalLastHargaEmas = ref(null)
const modalAktifKontrak = ref(null)
const catatForm = useForm({
    bulan: '',
    harga_emas: '',
    cicilan: '',
    catatan: '',
    items: [],
})

function itemsFromTypes(types, existingItems) {
    const byName = Object.fromEntries((existingItems ?? []).map(i => [i.type_name, i]))
    return types.map(t => {
        const existing = byName[t.name]
        return {
            type_name: t.name,
            unit: t.unit,
            gram: t.unit === 'gram' ? (existing ? Number(existing.gram) : '') : null,
            jumlah: t.unit === 'rupiah' ? (existing ? Number(existing.jumlah) : 0) : null,
        }
    })
}

async function openCatat(id = null) {
    lastRequestedId.value = id
    showCatatModal.value = true
    loadingContext.value = true
    contextError.value = ''
    catatForm.clearErrors()
    try {
        // Accept: application/json penting — tanpa itu, sesi yang kedaluwarsa
        // dibalas redirect ke halaman login (HTML), fetch mengikutinya, dan
        // res.json() gagal dengan pesan generik yang menyesatkan. Dengan
        // header ini Laravel membalas 401 yang bisa dikenali.
        const res = await fetch(id ? route('catat.context', { id }) : route('catat.context'), {
            headers: { Accept: 'application/json' },
        })
        if (res.status === 401 || res.status === 419) {
            contextError.value = 'Sesi login berakhir — memuat ulang halaman…'
            window.location.reload()
            return
        }
        if (!res.ok) throw new Error('catat-context request failed')
        const data = await res.json()
        const p = data.existing

        existingId.value = p ? p.id : null
        modalInvestmentTypes.value = data.investmentTypes ?? []
        modalLastHargaEmas.value = data.lastHargaEmas ?? null
        modalAktifKontrak.value = data.aktifKontrak ?? null

        catatForm.bulan      = data.bulan
        catatForm.harga_emas = p ? p.harga_emas : (data.lastHargaEmas ?? '')
        catatForm.cicilan    = p ? p.cicilan : (data.aktifKontrak ? Number(data.aktifKontrak.angsuran_bulan) : '')
        catatForm.catatan    = p ? (p.catatan ?? '') : ''
        catatForm.items      = itemsFromTypes(modalInvestmentTypes.value, p?.items)
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

useEscapeKey(showCatatModal, closeCatat)

function submitCatat() {
    const options = { onSuccess: () => { showCatatModal.value = false } }
    if (existingId.value) {
        catatForm.put(route('portofolio.update', existingId.value), options)
    } else {
        catatForm.post(route('portofolio.store'), options)
    }
}

defineExpose({ openCatat })
</script>

<template>
    <div class="min-h-screen bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 transition-colors">

        <!-- Mobile Header -->
        <header class="lg:hidden sticky top-0 z-40 w-full border-b border-zinc-200 dark:border-zinc-800/60 bg-white/90 dark:bg-zinc-950/90 backdrop-blur-md">
            <div class="max-w-lg mx-auto px-4 h-14 flex items-center justify-between">
                <Link href="/dashboard" class="flex items-center gap-2.5">
                    <img src="/icons/icon.png" alt="WealthID" class="w-8 h-8 rounded-xl shadow-lg shadow-indigo-500/20">
                    <span class="font-semibold text-sm text-zinc-900 dark:text-zinc-100 tracking-tight">WealthID</span>
                </Link>
                <div class="flex items-center gap-2">
                    <button @click="toggleTheme" :aria-label="isDark ? 'Ganti ke mode terang' : 'Ganti ke mode gelap'"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        <Sun v-if="isDark" :size="16" />
                        <Moon v-else :size="16" />
                    </button>
                    <DropdownMenu>
                        <DropdownMenuTrigger class="outline-none">
                            <div class="flex items-center gap-1.5">
                                <Avatar class="size-8">
                                    <AvatarFallback class="bg-indigo-500 text-white text-xs font-bold">
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
                    <img src="/icons/icon.png" alt="WealthID" class="w-9 h-9 rounded-xl shadow-lg shadow-indigo-500/20">
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
                            ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400'
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
                                    <AvatarFallback class="bg-indigo-500 text-white text-xs font-bold">
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
                    <PageSkeleton v-if="isNavigating"/>
                    <slot v-else />
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
                            :class="route().current('dashboard') ? 'text-indigo-500 dark:text-indigo-400' : 'text-zinc-400'">
                            <div class="w-9 h-8 flex items-center justify-center rounded-xl transition-all"
                                :class="route().current('dashboard') ? 'bg-indigo-500/10' : 'group-hover:bg-zinc-100 dark:group-hover:bg-zinc-800'">
                                <LayoutDashboard :size="19"
                                    :stroke-width="route().current('dashboard') ? 2.5 : 1.8"
                                    :class="route().current('dashboard') ? 'text-indigo-500 dark:text-indigo-400' : 'text-zinc-400 group-hover:text-zinc-700 dark:group-hover:text-zinc-300'" />
                            </div>
                            <span class="text-xs font-medium">Dashboard</span>
                        </Link>

                        <!-- Grafik -->
                        <Link :href="route('grafik')"
                            class="flex flex-col items-center gap-1 px-3 py-1 rounded-xl transition-all duration-200 group"
                            :class="route().current('grafik') ? 'text-indigo-500 dark:text-indigo-400' : 'text-zinc-400'">
                            <div class="w-9 h-8 flex items-center justify-center rounded-xl transition-all"
                                :class="route().current('grafik') ? 'bg-indigo-500/10' : 'group-hover:bg-zinc-100 dark:group-hover:bg-zinc-800'">
                                <ChartNoAxesColumn :size="19"
                                    :stroke-width="route().current('grafik') ? 2.5 : 1.8"
                                    :class="route().current('grafik') ? 'text-indigo-500 dark:text-indigo-400' : 'text-zinc-400 group-hover:text-zinc-700 dark:group-hover:text-zinc-300'" />
                            </div>
                            <span class="text-xs font-medium">Grafik</span>
                        </Link>

                        <!-- CATAT FAB -->
                        <button @click="openCatat"
                            class="flex flex-col items-center gap-1 px-3 pb-1 rounded-xl transition-all duration-200 group relative">
                            <div class="w-12 h-10 flex items-center justify-center rounded-2xl bg-indigo-500 shadow-lg shadow-indigo-500/30 -mt-5 transition-transform active:scale-95">
                                <CirclePlus :size="22" stroke-width="2.5" class="text-white" />
                            </div>
                            <span class="text-xs font-medium text-indigo-500 dark:text-indigo-400">Catat</span>
                        </button>

                        <!-- Keuangan -->
                        <Link :href="route('keuangan.index')"
                            class="flex flex-col items-center gap-1 px-3 py-1 rounded-xl transition-all duration-200 group"
                            :class="route().current('keuangan.index') ? 'text-indigo-500 dark:text-indigo-400' : 'text-zinc-400'">
                            <div class="w-9 h-8 flex items-center justify-center rounded-xl transition-all"
                                :class="route().current('keuangan.index') ? 'bg-indigo-500/10' : 'group-hover:bg-zinc-100 dark:group-hover:bg-zinc-800'">
                                <Wallet :size="19"
                                    :stroke-width="route().current('keuangan.index') ? 2.5 : 1.8"
                                    :class="route().current('keuangan.index') ? 'text-indigo-500 dark:text-indigo-400' : 'text-zinc-400 group-hover:text-zinc-700 dark:group-hover:text-zinc-300'" />
                            </div>
                            <span class="text-xs font-medium">Keuangan</span>
                        </Link>

                        <!-- Target -->
                        <Link :href="route('target')"
                            class="flex flex-col items-center gap-1 px-3 py-1 rounded-xl transition-all duration-200 group"
                            :class="route().current('target') ? 'text-indigo-500 dark:text-indigo-400' : 'text-zinc-400'">
                            <div class="w-9 h-8 flex items-center justify-center rounded-xl transition-all"
                                :class="route().current('target') ? 'bg-indigo-500/10' : 'group-hover:bg-zinc-100 dark:group-hover:bg-zinc-800'">
                                <Target :size="19"
                                    :stroke-width="route().current('target') ? 2.5 : 1.8"
                                    :class="route().current('target') ? 'text-indigo-500 dark:text-indigo-400' : 'text-zinc-400 group-hover:text-zinc-700 dark:group-hover:text-zinc-300'" />
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
                    <div v-if="showCatatModal" role="dialog" aria-modal="true" aria-labelledby="catat-modal-title"
                        class="relative w-full max-w-lg max-h-[90vh] overflow-y-auto bg-white dark:bg-zinc-900 rounded-t-3xl shadow-2xl border-t border-x border-zinc-200 dark:border-zinc-800 px-4 pt-3 pb-6">
                        <div class="w-10 h-1.5 rounded-full bg-zinc-300 dark:bg-zinc-700 mx-auto mb-3"/>
                        <div class="flex items-center justify-between mb-4">
                            <h3 id="catat-modal-title" class="font-semibold text-zinc-900 dark:text-white text-sm">{{ existingId ? 'Edit' : 'Catat' }} data portofolio</h3>
                            <button @click="closeCatat" aria-label="Tutup" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors shrink-0">
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
                                <label for="catat-bulan" class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                    <Calendar :size="12" class="text-zinc-400"/> Bulan & tahun
                                </label>
                                <input id="catat-bulan" type="month" v-model="catatForm.bulan" :class="inputClass"/>
                                <p v-if="catatForm.errors.bulan" class="text-xs text-red-500 mt-1">{{ catatForm.errors.bulan }}</p>
                            </div>

                            <div>
                                <label for="catat-cicilan" class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                    <Lock :size="12" class="text-yellow-600"/> Cicilan emas (Rp)
                                </label>
                                <input id="catat-cicilan" type="number" v-model="catatForm.cicilan" :class="inputClass"/>
                                <p v-if="catatForm.errors.cicilan" class="text-xs text-red-500 mt-1">{{ catatForm.errors.cicilan }}</p>
                            </div>

                            <PortfolioItemFields
                                :items="catatForm.items"
                                v-model:harga-emas="catatForm.harga_emas"
                                :last-harga-emas="modalLastHargaEmas"
                                :harga-emas-error="catatForm.errors.harga_emas"
                                :aktif-kontrak="modalAktifKontrak"
                            />

                            <div>
                                <label for="catat-catatan" class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mb-1.5">
                                    <StickyNote :size="12" class="text-zinc-400"/> Catatan (opsional)
                                </label>
                                <input id="catat-catatan" type="text" v-model="catatForm.catatan" placeholder="mis. dapat diskon GAJIANEMAS" :class="inputClass"/>
                            </div>

                            <div class="flex gap-2 pt-1">
                                <button type="button" @click="closeCatat"
                                    class="flex-1 py-2.5 rounded-xl text-sm font-medium border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                    Batal
                                </button>
                                <button type="submit" :disabled="catatForm.processing"
                                    class="flex-1 flex items-center justify-center gap-1.5 py-2.5 rounded-xl text-sm font-semibold bg-indigo-500 hover:bg-indigo-400 active:bg-indigo-600 text-white disabled:opacity-50 transition-colors">
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
