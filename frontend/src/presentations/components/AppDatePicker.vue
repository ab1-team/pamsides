<template>
  <div class="app-date-picker" :class="{ 'mb-2!': !noMargin }">
    <label v-if="label" class="label!">{{ label }}</label>
    <div class="picker-wrapper!">
      <font-awesome-icon v-if="icon" :icon="icon" class="prefix-icon!" />
      <DatePicker
        v-model="dateValue"
        :placeholder="placeholder"
        :dateFormat="dateFormat"
        :showIcon="false"
        :disabled="disabled"
        :yearRange="yearRange"
        showYear
        class="base-input!"
        :class="{ 'has-icon!': icon, 'p-invalid!': error }"
        @date-select="handleChange"
      />
    </div>
    <small v-if="error" class="error-text!">{{ error }}</small>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import DatePicker from 'primevue/datepicker'

const props = defineProps({
  modelValue: {
    type: [String, Date],
    default: null,
  },
  label: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Pilih Tanggal',
  },
  dateFormat: {
    type: String,
    default: 'yy-mm-dd',
  },
  icon: {
    type: String,
    default: null,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: null,
  },
  yearRange: {
    type: String,
    default: '1900:2100',
  },
  noMargin: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'change'])

const dateValue = ref(props.modelValue ? new Date(props.modelValue) : null)

watch(
  () => props.modelValue,
  (newVal) => {
    dateValue.value = newVal ? new Date(newVal) : null
  },
)

const handleChange = (newDate) => {
  const formattedDate = newDate ? formatDate(newDate) : null
  emit('update:modelValue', formattedDate)
  emit('change', formattedDate)
}

const formatDate = (date) => {
  if (!date) return null
  const d = new Date(date)
  let month = '' + (d.getMonth() + 1)
  let day = '' + d.getDate()
  const year = d.getFullYear()

  if (month.length < 2) month = '0' + month
  if (day.length < 2) day = '0' + day

  return [year, month, day].join('-')
}
</script>

<style scoped>
@reference "@/assets/css/main.css";

.base-input {
  @apply w-full transition-all duration-300;
}

.label {
  @apply block text-sm font-semibold text-slate-600 mb-1.5 ml-1;
}

.picker-wrapper {
  @apply relative flex items-center;
}

.prefix-icon {
  @apply absolute left-4 text-slate-400 text-sm z-10;
}

:deep(.p-datepicker) {
  @apply w-full;
}

:deep(.p-inputtext) {
  @apply w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 transition-all duration-300;
  @apply placeholder:text-slate-400 placeholder:font-normal;
  @apply hover:border-blue-400 hover:bg-white hover:shadow-md hover:shadow-blue-500/5;
  @apply focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 focus:shadow-lg focus:shadow-blue-500/5;
}

/* Fix untuk Warna Text di Dropdown Bulan/Tahun */
:deep(.p-datepicker-month),
:deep(.p-datepicker-year) {
  color: #000000 !important;
  font-weight: 700 !important;
  @apply hover:text-blue-600! transition-colors!;
}

:deep(.p-datepicker-month) {
  width: 33.3%;
  display: inline-flex !important;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  overflow: hidden;
  position: relative;
  padding: 0.5rem !important;
  transition:
    background 0.2s,
    color 0.2s;
  border-radius: 8px;
  color: #000000 !important;
}

:deep(.p-datepicker-header) {
  @apply bg-white! border-b! border-slate-100! p-3!;
}

:deep(.p-datepicker-title) {
  @apply flex! items-center! gap-2! text-slate-800! font-bold!;
}

/* Month & Year Selection Grid Fix - AGGRESSIVE */
:deep(.p-monthpicker-month),
:deep(.p-yearpicker-year),
:deep(.p-monthpicker span),
:deep(.p-yearpicker span) {
  color: #1e293b !important; /* Force Slate 800 */
  font-weight: 700 !important;
  opacity: 1 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

:deep(.p-monthpicker-month),
:deep(.p-yearpicker-year) {
  @apply p-4! rounded-xl! transition-all!;
}

:deep(.p-monthpicker-month:hover),
:deep(.p-yearpicker-year:hover) {
  background-color: #f1f5f9 !important; /* Slate 100 */
  color: #2563eb !important; /* Blue 600 */
}

/* Selected State */
:deep(.p-monthpicker-month.p-highlight),
:deep(.p-yearpicker-year.p-highlight) {
  background-color: #2563eb !important;
  color: #ffffff !important;
  box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.2) !important;
}

/* Navigation Buttons */
:deep(.p-datepicker-prev),
:deep(.p-datepicker-next) {
  @apply w-10! h-10! rounded-xl! text-slate-400! hover:bg-slate-50! hover:text-slate-800! transition-all!;
}

.has-icon :deep(.p-inputtext) {
  @apply pl-10;
}

.p-invalid :deep(.p-inputtext) {
  @apply border-red-300 bg-red-50/30;
}

.error-text {
  @apply mt-1.5 ml-1 text-xs text-red-600 font-medium;
}
</style>
