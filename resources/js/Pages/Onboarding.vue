<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { Coins, Shield, BarChart2, Landmark, ArrowRight, ChevronLeft } from 'lucide-vue-next'
import AnimatedLogo from '@/Components/AnimatedLogo.vue'

// Ditampilkan sekali per browser: menekan Mulai/Lewati men-set cookie
// onboarding_seen di server, dan kunjungan berikutnya ke "/" langsung
// di-redirect ke /login dari server tanpa merender carousel ini lagi.
const slides = [
    {
        icon: Coins,
        title: 'Selamat datang di WealthID',
        desc: 'Kelola investasi emas, dana darurat, reksa dana, dan SBN dalam satu aplikasi yang simpel dan pribadi.',
    },
    {
        icon: Shield,
        title: 'Tracking emas & dana darurat',
        desc: 'Pantau gram emas cicilan maupun tunai, serta progres dana darurat menuju target yang kamu tetapkan.',
    },
    {
        icon: BarChart2,
        title: 'Grafik & analisis portofolio',
        desc: 'Lihat pertumbuhan reksa dana, SBN, dan total aset dari bulan ke bulan lewat grafik interaktif.',
    },
    {
        icon: Landmark,
        title: 'Cashflow harian & budget',
        desc: 'Catat pemasukan-pengeluaran harian, atur budget kategori, dan simulasikan target saving bulanan.',
    },
]

const current = ref(0)
const direction = ref(1)
const isLast = computed(() => current.value === slides.length - 1)

function finish() {
    // Lewat POST supaya server men-set flag session + cookie onboarding,
    // lalu me-redirect ke /login.
    router.post(route('onboarding.continue'), {}, { replace: true })
}

function next() {
    if (isLast.value) { finish(); return }
    direction.value = 1
    current.value++
}

function prev() {
    if (current.value === 0) return
    direction.value = -1
    current.value--
}

function goTo(i) {
    direction.value = i > current.value ? 1 : -1
    current.value = i
}

function onKeydown(e) {
    if (e.key === 'ArrowRight') next()
    else if (e.key === 'ArrowLeft') prev()
}

let touchStartX = null
function onTouchStart(e) { touchStartX = e.changedTouches[0].clientX }
function onTouchEnd(e) {
    if (touchStartX === null) return
    const delta = e.changedTouches[0].clientX - touchStartX
    if (delta < -50) next()
    else if (delta > 50) prev()
    touchStartX = null
}

onMounted(() => {
    window.addEventListener('keydown', onKeydown)
})
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown))
</script>

<template>
    <Head title="Kenalan dulu yuk" />

    <div class="min-h-screen bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 transition-colors flex flex-col">

        <!-- Top bar -->
        <div class="flex items-center justify-between px-5 pt-6">
            <div class="flex items-center gap-2">
                <img src="/icons/icon.png" alt="WealthID" class="w-7 h-7 rounded-lg shadow-lg shadow-indigo-500/20"/>
                <span class="font-bold text-sm">WealthID</span>
            </div>
            <button v-if="!isLast" type="button" @click="finish"
                class="text-xs font-medium text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors px-3 py-1.5">
                Lewati
            </button>
        </div>

        <!-- Slide -->
        <div class="flex-1 flex items-center justify-center px-6 py-8 overflow-hidden"
             @touchstart="onTouchStart" @touchend="onTouchEnd"
             role="region" aria-roledescription="carousel" :aria-label="`Slide ${current + 1} dari ${slides.length}`">
            <Transition :name="direction > 0 ? 'slide-next' : 'slide-prev'" mode="out-in">
                <div :key="current" class="max-w-sm w-full text-center">
                    <AnimatedLogo v-if="current === 0" class="mx-auto mb-6 justify-center"/>
                    <div v-else class="w-20 h-20 mx-auto rounded-3xl bg-indigo-100 dark:bg-indigo-500/15 flex items-center justify-center mb-6">
                        <component :is="slides[current].icon" :size="34" class="text-indigo-600 dark:text-indigo-400"/>
                    </div>
                    <h1 class="text-xl font-bold text-zinc-900 dark:text-white mb-2.5">{{ slides[current].title }}</h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ slides[current].desc }}</p>
                </div>
            </Transition>
        </div>

        <!-- Dots -->
        <div class="flex items-center justify-center gap-2 pb-6">
            <button v-for="(s, i) in slides" :key="i" type="button" @click="goTo(i)"
                :aria-label="`Ke slide ${i + 1}`" :aria-current="i === current"
                class="h-1.5 rounded-full transition-all"
                :class="i === current ? 'w-6 bg-indigo-500' : 'w-1.5 bg-zinc-300 dark:bg-zinc-700'">
            </button>
        </div>

        <!-- Bottom nav -->
        <div class="px-6 pb-10 flex items-center gap-3">
            <button v-if="current > 0" type="button" @click="prev" aria-label="Sebelumnya"
                class="w-12 h-12 shrink-0 flex items-center justify-center rounded-xl border border-zinc-300 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                <ChevronLeft :size="18"/>
            </button>
            <button type="button" @click="next"
                class="flex-1 flex items-center justify-center gap-2 py-3.5 rounded-xl font-semibold text-sm bg-indigo-500 hover:bg-indigo-400 active:bg-indigo-600 text-white transition-colors shadow-lg shadow-indigo-500/30">
                {{ isLast ? 'Mulai' : 'Lanjut' }}
                <ArrowRight :size="16"/>
            </button>
        </div>
    </div>
</template>

<style scoped>
.slide-next-enter-active, .slide-next-leave-active,
.slide-prev-enter-active, .slide-prev-leave-active {
    transition: opacity 0.45s cubic-bezier(.22,.85,.3,1), transform 0.45s cubic-bezier(.22,.85,.3,1);
}
.slide-next-enter-from { opacity: 0; transform: translateX(32px); }
.slide-next-leave-to   { opacity: 0; transform: translateX(-32px); }
.slide-prev-enter-from { opacity: 0; transform: translateX(-32px); }
.slide-prev-leave-to   { opacity: 0; transform: translateX(32px); }
</style>
