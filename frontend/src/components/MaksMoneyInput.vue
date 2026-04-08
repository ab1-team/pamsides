<template>
  <div class="currency-input">
    <InputNumber
      v-model="internalValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :readonly="readonly"
      mode="decimal"
      locale="id-ID"
      :minFractionDigits="2"
      :maxFractionDigits="2"
      :min="0"
      @input="handleInput"
      @blur="handleBlur"
      @focus="handleFocus"
    />

    <div v-if="showHelper && helperText" class="helper-text">{{ helperText }}</div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import InputNumber from 'primevue/inputnumber'

const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  placeholder: { type: String, default: '0,00' },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  maxValue: { type: Number, default: null },
  showHelper: { type: Boolean, default: false },
  helperText: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue', 'change'])

const internalValue = ref(props.modelValue ?? null)

// Sync dari luar ke dalam
watch(
  () => props.modelValue,
  (val) => {
    if (val !== internalValue.value) internalValue.value = val ?? null
  },
)

// Sync dari dalam ke luar — hanya emit setelah nilai final (blur/enter)
// TIDAK watch internalValue langsung karena mengganggu ketik
function handleInput(e) {
  // e.value adalah angka hasil parse PrimeVue
  emit('update:modelValue', e.value)
}

function handleBlur() {
  // Pastikan nilai final tersync
  emit('update:modelValue', internalValue.value)
  emit('change', internalValue.value)
}

function handleFocus() {}
</script>

<style scoped>
.currency-input {
  width: 100%;
}

:deep(.p-inputnumber) {
  width: 100%;
}

:deep(.p-inputnumber-input) {
  width: 100% !important;
  padding: 0.5rem 0.75rem !important;
  border: 1px solid #d1d5db !important;
  border-radius: 0.375rem !important;
  font-size: 0.875rem !important;
  color: #111827 !important;
  background-color: #ffffff !important;
  font-family: inherit !important;
  outline: none !important;
  transition:
    border-color 0.15s ease,
    box-shadow 0.15s ease !important;
}

:deep(.p-inputnumber-input:hover:not(:focus):not(:disabled)) {
  border-color: #6b7280 !important;
}

:deep(.p-inputnumber-input:focus) {
  border-color: #3b82f6 !important;
  box-shadow: 0 0 0 1px #3b82f6 !important;
}

:deep(.p-inputnumber-input:disabled) {
  background-color: #f3f4f6 !important;
  color: #9ca3af !important;
  cursor: not-allowed !important;
}

.helper-text {
  font-size: 0.7rem;
  color: #6b7280;
  margin-top: 0.25rem;
}
</style>
