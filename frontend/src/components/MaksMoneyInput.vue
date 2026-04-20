<template>
  <div class="currency-input" :class="{ 'mb-2': !noMargin }">
    <label v-if="label" class="currency-label">{{ label }}</label>
    <div class="currency-input-wrapper">
      <span class="currency-prefix">Rp.</span>
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
function handleInput(e) {
  emit('update:modelValue', e.value)
}

function handleBlur() {
  emit('update:modelValue', internalValue.value)
  emit('change', internalValue.value)
}

function handleFocus() {}
</script>

<style scoped>
@reference "../assets/main.css";

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

:deep(.p-inputnumber) {
  @apply w-full;
}

:deep(.p-inputnumber-input) {
  @apply block w-full pl-10 pr-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-normal! text-slate-700 transition-all duration-300!;
  height: v-bind('size === "sm" ? "2.25rem" : "2.75rem"');
  @apply placeholder:text-slate-400 placeholder:font-normal!;
  @apply hover:border-blue-400 hover:bg-white hover:shadow-md hover:shadow-blue-500/5!;
  @apply focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 focus:shadow-lg focus:shadow-blue-500/5!;
}

:deep(.p-inputnumber-input:disabled) {
  @apply bg-slate-100 text-slate-400 cursor-not-allowed border-slate-200 shadow-none hover:border-slate-200 hover:shadow-none!;
}

.helper-text {
  @apply mt-1.5 ml-1 text-xs text-slate-400;
}
</style>
