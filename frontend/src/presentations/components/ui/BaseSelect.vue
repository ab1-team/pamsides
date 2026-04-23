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
