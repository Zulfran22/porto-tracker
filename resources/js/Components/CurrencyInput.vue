<script setup>
import { computed, nextTick } from 'vue'
import { inputClass } from '@/Composables/useFormStyles'

// Input Rupiah dengan pemisah ribuan otomatis SAAT MENGETIK (mis. "1275000"
// tampil "1.275.000"). v-model tetap number murni (atau '' kalau kosong) —
// bukan string berformat — supaya kode yang sudah pakai field ini tidak
// perlu diubah selain nama komponennya.
const props = defineProps({
    modelValue: { type: [Number, String], default: '' },
    placeholder: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    // Field delta yang boleh negatif (mis. tarik saldo investasi) dikasih
    // tombol ganti tanda — keypad angka polos di banyak HP tidak punya
    // tombol "-" yang gampang diakses, jadi tidak boleh cuma mengandalkan
    // user mengetik minus sendiri.
    allowNegative: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])

defineOptions({ inheritAttrs: false })

function toDigits(v) {
    const str = String(v ?? '')
    const negative = str.trimStart().startsWith('-')
    const digits = str.replace(/[^0-9]/g, '')

    return (negative && digits !== '' ? '-' : '') + digits
}

const displayValue = computed(() => {
    if (props.modelValue === '' || props.modelValue === null || props.modelValue === undefined) return ''
    const num = Number(props.modelValue)
    if (Number.isNaN(num)) return ''

    return num.toLocaleString('id-ID')
})

const isNegative = computed(() => Number(props.modelValue) < 0)

function digitsBeforeCursor(value, cursor) {
    let count = 0
    for (let i = 0; i < cursor && i < value.length; i++) {
        if (/[0-9]/.test(value[i])) count++
    }
    return count
}

function caretPositionFromDigitCount(str, digitCount) {
    if (digitCount <= 0) return str.startsWith('-') ? 1 : 0
    let count = 0
    for (let i = 0; i < str.length; i++) {
        if (/[0-9]/.test(str[i])) {
            count++
            if (count === digitCount) return i + 1
        }
    }
    return str.length
}

// Mempertahankan posisi kursor saat mengetik/menghapus di TENGAH angka —
// tanpa ini, tiap keystroke bikin kursor loncat ke ujung kanan karena format
// ulang menyisipkan/membuang titik ribuan di posisi yang berubah-ubah.
function onInput(e) {
    const el = e.target
    const cursor = el.selectionStart ?? el.value.length
    const digitCount = digitsBeforeCursor(el.value, cursor)
    const raw = toDigits(el.value)

    if (raw === '' || raw === '-') {
        emit('update:modelValue', '')
        return
    }

    emit('update:modelValue', Number(raw))

    nextTick(() => {
        const pos = caretPositionFromDigitCount(el.value, digitCount)
        el.setSelectionRange(pos, pos)
    })
}

function toggleSign() {
    if (props.modelValue === '' || props.modelValue === null || props.modelValue === undefined) return
    emit('update:modelValue', Number(props.modelValue) * -1)
}
</script>

<template>
    <div class="flex items-center gap-2 w-full">
        <button v-if="allowNegative" type="button" @click="toggleSign" :disabled="disabled"
            :aria-label="isNegative ? 'Ubah jadi setoran (+)' : 'Ubah jadi tarik saldo (−)'"
            class="shrink-0 w-10 h-10 rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-base font-semibold leading-none text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 disabled:opacity-50 transition-colors">
            {{ isNegative ? '−' : '+' }}
        </button>
        <input
            type="text"
            :inputmode="allowNegative ? 'decimal' : 'numeric'"
            autocomplete="off"
            :value="displayValue"
            @input="onInput"
            :placeholder="placeholder"
            :disabled="disabled"
            :class="[inputClass, 'flex-1 min-w-0']"
            v-bind="$attrs"
        />
    </div>
</template>
