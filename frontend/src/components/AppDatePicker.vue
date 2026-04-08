<template>
  <div class="app-datepicker" :class="{ 'dp-dark': dark }">
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
      @date-select="onDateSelect"
      @clear-click="onClearClick"
    />

    <div v-if="showHelper && helperText" class="dp-helper">{{ helperText }}</div>
    <div v-if="validationError" class="dp-error">{{ validationError }}</div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import DatePicker from 'primevue/datepicker'

const props = defineProps({
  modelValue: { type: [Date, String], default: null },
  placeholder: { type: String, default: 'Pilih tanggal' },
  dark: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
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
  preventFuture: { type: Boolean, default: false },
  preventPast: { type: Boolean, default: false },
  minDate: { type: Date, default: null },
  maxDate: { type: Date, default: null },

  showHelper: { type: Boolean, default: false },
  helperText: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue', 'date-select', 'clear-click', 'validation-error'])

const internalValue = ref(null)
const validationError = ref('')

watch(
  () => props.modelValue,
  (val) => {
    internalValue.value = val ? new Date(val) : null
  },
  { immediate: true },
)

watch(internalValue, (val) => emit('update:modelValue', val))

/*
  Hanya set minDate/maxDate dari prop eksplisit.
  preventFuture & preventPast ditangani manual di onDateSelect
  agar tidak menyebabkan PrimeVue meng-disable tanggal bulan aktif.
*/
const computedMinDate = computed(() => props.minDate ?? null)
const computedMaxDate = computed(() => props.maxDate ?? null)

function onDateSelect(date) {
  validationError.value = ''
  const selected = new Date(date)

  internalValue.value = selected
  emit('date-select', selected)
}

function onClearClick() {
  validationError.value = ''
  emit('clear-click')
}

defineExpose({
  validate: () => {
    if (!internalValue.value) return true
    return (onDateSelect(internalValue.value), validationError.value === '')
  },
  clear: () => {
    internalValue.value = null
    validationError.value = ''
  },
  setToday: () => {
    internalValue.value = new Date()
  },
})
</script>

<style scoped>
.app-datepicker {
  position: relative;
  width: 100%;
}

/* Lebar penuh untuk wrapper PrimeVue */
:deep(.p-datepicker) {
  width: 100%;
}

.dp-helper {
  font-size: 0.7rem;
  color: #6b7280;
  margin-top: 0.25rem;
}
.dp-error {
  font-size: 0.7rem;
  color: #ef4444;
  margin-top: 0.25rem;
}
</style>
