<template>
  <div class="base-input" :class="{ 'mb-2!': !noMargin }">
    <label v-if="label" :for="inputId" class="base-input__label">
      {{ label }}
    </label>

    <div class="base-input__wrapper">
      <!-- Prefix Icon atau Teks -->
      <div v-if="prefixIcon || prefixText" class="base-input__prefix">
        <span v-if="prefixText">{{ prefixText }}</span>
        <font-awesome-icon v-else :icon="prefixIcon" />
      </div>

      <input
        v-if="type !== 'textarea'"
        :id="inputId"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :class="inputClasses"
        :maxlength="maxlength"
        @input="handleInput"
        @change="$emit('change', $event.target.value)"
        @focus="$emit('focus', $event)"
        @blur="$emit('blur', $event)"
        @keydown="$emit('keydown', $event)"
      />

      <textarea
        v-else
        :id="inputId"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :rows="rows"
        :class="[inputClasses, 'base-input__field--textarea']"
        :maxlength="maxlength"
        @input="handleInput"
        @change="$emit('change', $event.target.value)"
        @focus="$emit('focus', $event)"
        @blur="$emit('blur', $event)"
        @keydown="$emit('keydown', $event)"
      ></textarea>

      <!-- Suffix Icon atau Aksi -->
      <div v-if="$slots.suffix" class="base-input__suffix">
        <slot name="suffix" />
      </div>
    </div>

    <!-- Pesan Error -->
    <div v-if="error" class="base-input__error">
      {{ error }}
    </div>

    <!-- Teks Petunjuk -->
    <div v-if="hint && !error" class="base-input__hint">
      {{ hint }}
    </div>
  </div>
</template>

<script setup>
import { computed, useId } from 'vue'

const props = defineProps({
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
  type: {
    type: String,
    default: 'text',
  },
  prefixIcon: {
    type: String,
    default: null,
  },
  prefixText: {
    type: String,
    default: null,
  },
  error: {
    type: String,
    default: '',
  },
  hint: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  readonly: {
    type: Boolean,
    default: false,
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
  customClass: {
    type: String,
    default: '',
  },
  rows: {
    type: Number,
    default: 3,
  },
  noMargin: {
    type: Boolean,
    default: false,
  },
  maxlength: {
    type: [String, Number],
    default: null,
  },
})

const emit = defineEmits(['update:modelValue', 'change', 'focus', 'blur', 'keydown'])

const inputId = useId()

const inputClasses = computed(() => {
  const base = 'base-input__field'
  const sizes = {
    sm: 'base-input__field--sm',
    md: 'base-input__field--md',
    lg: 'base-input__field--lg',
  }

  return [
    base,
    sizes[props.size],
    props.customClass,
    {
      'base-input__field--error': props.error,
      'base-input__field--disabled': props.disabled,
      'base-input__field--readonly': props.readonly,
      'base-input__field--has-prefix': props.prefixIcon || props.prefixText,
    },
  ]
})

const handleInput = (event) => {
  const value = props.type === 'number' ? Number(event.target.value) || 0 : event.target.value

  emit('update:modelValue', value)
}
</script>
