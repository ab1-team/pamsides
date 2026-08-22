<template>
  <div class="currency-input" :class="{ 'mb-2': !noMargin }">
    <label v-if="label" class="currency-label">{{ label }}</label>
    <div class="currency-input-wrapper">
      <span v-if="!hidePrefix" class="currency-prefix">Rp.</span>
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
        class="custom-input-number"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
      />
    </div>

    <div v-if="showHelper && helperText" class="helper-text">{{ helperText }}</div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import InputNumber from 'primevue/inputnumber'

const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  label: { type: String, default: '' },
  placeholder: { type: String, default: '0,00' },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  maxValue: { type: Number, default: null },
  showHelper: { type: Boolean, default: false },
  helperText: { type: String, default: '' },
  noMargin: { type: Boolean, default: false },
  size: { type: String, default: 'normal', validator: (v) => ['normal', 'sm'].includes(v) },
  hidePrefix: { type: Boolean, default: false },
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

// Sync dari dalam ke luar — coerce ke Number agar parent computed tidak NaN/null
function toNum(v) {
  if (v === null || v === undefined || v === '') return 0
  const n = typeof v === 'number' ? v : Number(String(v).replace(/\./g, '').replace(',', '.'))
  return Number.isFinite(n) ? n : 0
}

function handleInput(e) {
  emit('update:modelValue', toNum(e.value))
}

function handleBlur() {
  const v = toNum(internalValue.value)
  internalValue.value = v
  emit('update:modelValue', v)
  emit('change', v)
}

function handleFocus() {}
</script>

<style scoped>
@reference "@/assets/css/main.css";

.currency-input {
  @apply w-full transition-all duration-300;
}

.currency-label {
  @apply block text-sm font-normal text-slate-500 mb-1.5 ml-1;
}

.currency-input-wrapper {
  @apply relative flex items-center;
}

.currency-prefix {
  @apply absolute left-4 text-slate-400 text-sm font-medium z-10;
}

.currency-input-wrapper:not(:has(.currency-prefix)) :deep(.p-inputnumber-input) {
  padding-left: 1rem !important;
}

:deep(.p-inputnumber) {
  @apply w-full;
}

:deep(.p-inputnumber-input) {
  display: block !important;
  width: 100% !important;
  padding: 0 1rem !important;
  background-color: #f8fafc !important;
  border: 1px solid #e2e8f0 !important;
  border-radius: 0.75rem !important;
  font-size: 0.875rem !important;
  font-weight: 400 !important;
  color: #334155 !important;
  transition: all 0.3s !important;
  outline: none !important;
  height: v-bind('size === "sm" ? "2.25rem" : "2.75rem"');
}
:deep(.p-inputnumber-input::placeholder) {
  color: #94a3b8 !important;
  font-weight: 400 !important;
}
:deep(.p-inputnumber-input:hover) {
  border-color: #60a5fa !important;
  background-color: #ffffff !important;
  box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.05) !important;
}
:deep(.p-inputnumber-input:focus),
:deep(.p-inputnumber.p-focus > .p-inputnumber-input) {
  border-color: #3b82f6 !important;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1), 0 10px 15px -3px rgba(59, 130, 246, 0.05) !important;
  background-color: #ffffff !important;
}

:deep(.p-inputnumber-input:disabled) {
  @apply bg-slate-100 text-slate-400 cursor-not-allowed border-slate-200 shadow-none hover:border-slate-200 hover:shadow-none!;
}

.helper-text {
  @apply mt-1.5 ml-1 text-xs text-slate-400;
}
</style>
