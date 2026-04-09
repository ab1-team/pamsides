<template>
  <div class="base-select" :class="{ 'mb-2': !noMargin }">
    <label v-if="label" :for="selectId" class="base-select__label">
      {{ label }}
    </label>
    <div class="base-select__wrapper">
      <select
        :id="selectId"
        :value="modelValue"
        :disabled="disabled"
        class="base-select__field"
        @change="handleChange"
      >
        <option v-if="placeholder" value="" disabled selected>
          {{ placeholder }}
        </option>
        <slot></slot>
      </select>
      <div class="base-select__icon">
        <font-awesome-icon icon="chevron-down" />
      </div>
    </div>
    <div v-if="error" class="base-select__error">
      {{ error }}
    </div>
  </div>
</template>

<script setup>
import { useId } from 'vue'

defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
  noMargin: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'change'])

const selectId = useId()

const handleChange = (event) => {
  emit('update:modelValue', event.target.value)
  emit('change', event.target.value)
}
</script>

<style scoped>
@reference "../../assets/main.css";

.base-select {
  @apply w-full transition-all duration-300;
}

.base-select__label {
  @apply block text-sm font-normal text-slate-500 mb-1.5 ml-1;
}

.base-select__wrapper {
  @apply relative flex items-center;
}

.base-select__field {
  @apply block w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 transition-all duration-300 appearance-none cursor-pointer;
  @apply hover:border-blue-400 hover:bg-white hover:shadow-md hover:shadow-blue-500/5;
  @apply focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 focus:shadow-lg focus:shadow-blue-500/5;
}

.base-select__icon {
  @apply absolute right-4 text-slate-400 text-xs pointer-events-none transition-transform duration-300;
}

.base-select__field:focus + .base-select__icon {
  @apply rotate-180 text-blue-500;
}

.base-select__field:disabled {
  @apply bg-slate-100 text-slate-400 cursor-not-allowed border-slate-200 shadow-none hover:border-slate-200 hover:shadow-none;
}

.base-select__error {
  @apply mt-1.5 ml-1 text-xs text-red-600 font-medium;
}
</style>
