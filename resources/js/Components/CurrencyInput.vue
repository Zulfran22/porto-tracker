<script setup>
import { computed } from 'vue'
import { inputClass } from '@/Composables/useFormStyles'

// Input Rupiah dengan pemisah ribuan otomatis SAAT MENGETIK (mis. "1275000"
// tampil "1.275.000"). v-model tetap number murni (atau '' kalau kosong) —
// bukan string berformat — supaya kode yang sudah pakai field ini tidak
// perlu diubah selain nama komponennya. Mendukung minus di depan (dipakai
// field delta yang boleh negatif, mis. penarikan saldo investasi).
const props = defineProps({
    modelValue: { type: [Number, String], default: '' },
    placeholder: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
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

function onInput(e) {
    const raw = toDigits(e.target.value)

    if (raw === '' || raw === '-') {
        emit('update:modelValue', '')
        return
    }

    emit('update:modelValue', Number(raw))
}
</script>

<template>
    <input
        type="text"
        inputmode="numeric"
        autocomplete="off"
        :value="displayValue"
        @input="onInput"
        :placeholder="placeholder"
        :disabled="disabled"
        :class="inputClass"
        v-bind="$attrs"
    />
</template>
