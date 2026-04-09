<template>
  <div class="base-input" :class="{ 'mb-2': !noMargin }">
    <label v-if="label" class="base-input__label">
      {{ label }}
    </label>

    <div class="base-input__wrapper">
      <span v-if="prefix" class="base-input__prefix">
        {{ prefix }}
      </span>

      <DatePicker
        v-model="internalValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :dateFormat="dateFormat"
        :showIcon="showIcon"
        :showButtonBar="showButtonBar"
        :showWeek="showWeek"
        :minDate="computedMinDate"
        :maxDate="computedMaxDate"
        :disabledDates="disabledDates"
        :disabledDays="disabledDays"
        :monthNavigator="monthNavigator"
        :yearNavigator="yearNavigator"
        :yearRange="yearRange"
        :manualInput="manualInput"
        :selectOtherMonths="selectOtherMonths"
        :showOtherMonths="showOtherMonths"
        class="base-datepicker"
        @date-select="onDateSelect"
        @clear-click="onClearClick"
      />

      <span v-if="suffix" class="base-input__suffix">
        {{ suffix }}
      </span>
    </div>

    <div v-if="error" class="base-input__error">
      {{ error }}
    </div>

    <div v-if="hint && !error" class="base-input__hint">
      {{ hint }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import DatePicker from 'primevue/datepicker'

const props = defineProps({
  modelValue: { type: [Date, String], default: null },
  label: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  prefix: { type: String, default: '' },
  suffix: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },

  dateFormat: { type: String, default: 'dd/mm/yy' },
  showIcon: { type: Boolean, default: false },
  showButtonBar: { type: Boolean, default: true },
  showWeek: { type: Boolean, default: false },
  disabledDates: { type: Array, default: () => [] },
  disabledDays: { type: Array, default: () => [] },
  monthNavigator: { type: Boolean, default: true },
  yearNavigator: { type: Boolean, default: true },
  yearRange: { type: String, default: '2020:2030' },
  manualInput: { type: Boolean, default: false },
  selectOtherMonths: { type: Boolean, default: false },
  showOtherMonths: { type: Boolean, default: true },

  minDate: { type: Date, default: null },
  maxDate: { type: Date, default: null },

  noMargin: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'date-select', 'clear-click'])

const internalValue = ref(null)

watch(
  () => props.modelValue,
  (val) => {
    internalValue.value = val ? new Date(val) : null
  },
  { immediate: true },
)

watch(internalValue, (val) => {
  emit('update:modelValue', val)
})

const computedMinDate = computed(() => props.minDate ?? null)
const computedMaxDate = computed(() => props.maxDate ?? null)

function onDateSelect(date) {
  const selected = new Date(date)
  internalValue.value = selected
  emit('date-select', selected)
}

function onClearClick() {
  internalValue.value = null
  emit('clear-click')
}
</script>

<style scoped>
@reference "../assets/main.css";

.base-input {
  @apply w-full transition-all duration-300;
}

.base-input__label {
  @apply block text-sm font-normal text-slate-500 mb-1.5 ml-1;
}

.base-datepicker {
  @apply w-full;
}

:deep(.p-datepicker) {
  @apply w-full bg-transparent border-none shadow-none;
}

:deep(.p-datepicker-input) {
  @apply !block !w-full !h-11 !px-4 !bg-slate-50 !border !border-slate-200 !rounded-xl !text-sm !text-slate-700 !transition-all !duration-300;
  @apply placeholder:!text-slate-400 placeholder:!font-normal;
  @apply hover:!border-blue-400 hover:!bg-white hover:!shadow-md hover:!shadow-blue-500/5;
  @apply focus:!outline-none focus:!bg-white focus:!border-blue-500 focus:!ring-4 focus:!ring-blue-500/10 focus:!shadow-lg focus:!shadow-blue-500/5;
}

:deep(.p-datepicker-input:disabled) {
  @apply bg-slate-100 text-slate-400 cursor-not-allowed border-slate-200 shadow-none hover:border-slate-200 hover:shadow-none!;
}

:deep(.p-datepicker-panel) {
  @apply rounded-xl border border-slate-200 shadow-xl;
}
</style>
