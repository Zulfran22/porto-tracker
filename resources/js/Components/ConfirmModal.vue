<script setup>
import { Trash2, X, Loader2 } from 'lucide-vue-next'
import { useEscapeKey } from '@/Composables/useEscapeKey'

const props = defineProps({
    open:        { type: Boolean, default: false },
    title:       { type: String, required: true },
    description: { type: String, default: '' },
    confirmText: { type: String, default: 'Hapus' },
    cancelText:  { type: String, default: 'Batal' },
    loading:     { type: Boolean, default: false },
})
const emit = defineEmits(['confirm', 'cancel'])

useEscapeKey(() => props.open, () => emit('cancel'))
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition" enter-from-class="opacity-0" leave-active-class="transition" leave-to-class="opacity-0">
            <div v-if="open" role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="emit('cancel')">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"/>
                <div class="relative w-full max-w-sm bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-zinc-200 dark:border-zinc-800 p-6">
                    <div class="flex items-start gap-3 mb-5">
                        <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center shrink-0">
                            <Trash2 :size="18" class="text-red-600 dark:text-red-400"/>
                        </div>
                        <div class="flex-1">
                            <h3 id="confirm-modal-title" class="font-semibold text-zinc-900 dark:text-white text-sm">{{ title }}</h3>
                            <p v-if="description" class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ description }}</p>
                        </div>
                        <button @click="emit('cancel')" aria-label="Tutup" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors shrink-0">
                            <X :size="16"/>
                        </button>
                    </div>
                    <div class="flex gap-2">
                        <button @click="emit('cancel')" :disabled="loading"
                            class="flex-1 py-2.5 rounded-xl text-sm font-medium border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors disabled:opacity-50">
                            {{ cancelText }}
                        </button>
                        <button @click="emit('confirm')" :disabled="loading"
                            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 rounded-xl text-sm font-semibold bg-red-600 hover:bg-red-500 text-white transition-colors disabled:opacity-50">
                            <Loader2 v-if="loading" :size="14" class="animate-spin"/>
                            <Trash2 v-else :size="14"/> {{ confirmText }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
