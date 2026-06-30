<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { TrendingUp, Shield, BarChart2, Target, ArrowRight, Coins, Landmark } from 'lucide-vue-next'

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
})

const features = [
    { icon: Coins,     title: 'Tracking Emas',      desc: 'Pantau gram dan nilai emas cicilan maupun tunai secara real-time.' },
    { icon: Shield,    title: 'Dana Darurat',        desc: 'Monitor progres dana darurat menuju target yang kamu tetapkan.' },
    { icon: TrendingUp,title: 'Reksa Dana & SBN',    desc: 'Catat dan analisis pertumbuhan investasi reksa dana dan SBN.' },
    { icon: BarChart2, title: 'Grafik Portofolio',   desc: 'Visualisasi pertumbuhan aset dari bulan ke bulan dengan grafik interaktif.' },
    { icon: Target,    title: 'Simulasi Saving',     desc: 'Hitung proyeksi nilai investasi dengan simulator alokasi bulanan.' },
    { icon: Landmark,  title: 'Cashflow Harian',     desc: 'Catat pemasukan & pengeluaran, pantau burn rate dan saving rate.' },
]
</script>

<template>
    <Head title="WealthID — Investasi Emas &amp; Saving" />

    <div class="min-h-screen bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 transition-colors">

        <!-- NAV -->
        <header class="sticky top-0 z-40 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50/90 dark:bg-zinc-950/90 backdrop-blur-md">
            <div class="max-w-5xl mx-auto px-5 h-14 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-yellow-500 flex items-center justify-center shadow-lg shadow-yellow-500/20">
                        <span class="text-xs font-black text-black">PT</span>
                    </div>
                    <span class="font-bold text-sm text-zinc-900 dark:text-white">WealthID</span>
                </div>

                <nav v-if="canLogin" class="flex items-center gap-2">
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')"
                        class="px-4 py-1.5 rounded-xl text-sm font-medium bg-yellow-500 hover:bg-yellow-400 text-black transition-colors">
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link :href="route('login')"
                            class="px-4 py-1.5 rounded-xl text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">
                            Masuk
                        </Link>
                        <Link v-if="canRegister" :href="route('register')"
                            class="px-4 py-1.5 rounded-xl text-sm font-medium bg-yellow-500 hover:bg-yellow-400 text-black transition-colors shadow-md shadow-yellow-500/20">
                            Daftar gratis
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- HERO -->
        <section class="max-w-5xl mx-auto px-5 pt-20 pb-16 text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-yellow-100 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-700/50 text-yellow-700 dark:text-yellow-400 text-xs font-medium mb-6">
                <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span>
                Gratis & Pribadi
            </div>

            <h1 class="text-4xl sm:text-5xl font-black text-zinc-900 dark:text-white tracking-tight leading-tight mb-5">
                Tracking investasi emas<br/>
                <span class="text-yellow-500">&amp; keuangan pribadi</span>
            </h1>

            <p class="text-base text-zinc-500 dark:text-zinc-400 max-w-lg mx-auto mb-8 leading-relaxed">
                Pantau portofolio emas, dana darurat, reksa dana, dan SBN dalam satu tempat.
                Lengkap dengan grafik, simulasi saving, dan tracking cashflow harian.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <Link v-if="canRegister" :href="route('register')"
                    class="flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-sm bg-yellow-500 hover:bg-yellow-400 text-black transition-colors shadow-lg shadow-yellow-500/30">
                    Mulai gratis
                    <ArrowRight :size="16"/>
                </Link>
                <Link v-if="canLogin && !$page.props.auth.user" :href="route('login')"
                    class="flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-sm border border-zinc-300 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                    Sudah punya akun
                </Link>
                <Link v-if="$page.props.auth.user" :href="route('dashboard')"
                    class="flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-sm bg-yellow-500 hover:bg-yellow-400 text-black transition-colors shadow-lg shadow-yellow-500/30">
                    Buka Dashboard
                    <ArrowRight :size="16"/>
                </Link>
            </div>
        </section>

        <!-- FEATURES -->
        <section class="max-w-5xl mx-auto px-5 pb-20">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="f in features" :key="f.title"
                    class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 hover:border-yellow-300 dark:hover:border-yellow-700/50 transition-colors">
                    <div class="w-9 h-9 rounded-xl bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center mb-3">
                        <component :is="f.icon" :size="18" class="text-yellow-600 dark:text-yellow-400"/>
                    </div>
                    <h3 class="font-semibold text-sm text-zinc-900 dark:text-white mb-1">{{ f.title }}</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ f.desc }}</p>
                </div>
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="border-t border-zinc-200 dark:border-zinc-800 py-6 text-center text-xs text-zinc-400">
            © {{ new Date().getFullYear() }} WealthID — dibuat dengan ❤️ untuk tracking investasi pribadi
        </footer>
    </div>
</template>
