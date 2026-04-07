<template>
  <div class="base-input">
    <label v-if="label" :for="inputId" class="base-input__label">
      {{ label }}
    </label>
    <div class="base-input__wrapper">
      <span v-if="prefix" class="base-input__prefix">
        {{ prefix }}
      </span>
      <input
        :id="inputId"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :class="inputClasses"
        :style="style"
        class="base-input__field"
        @input="handleInput"
        @click="$emit('click', $event)"
        @focus="$emit('focus', $event)"
        @blur="$emit('blur', $event)"
        @keydown="$emit('keydown', $event)"
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
  type: {
    type: String,
    default: 'text',
  },
  placeholder: {
    type: String,
    default: '',
  },
  prefix: {
    type: String,
    default: '',
  },
  suffix: {
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
  error: {
    type: String,
    default: '',
  },
  hint: {
    type: String,
    default: '',
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
  style: {
    type: [String, Object],
    default: '',
  },
})

const emit = defineEmits(['update:modelValue', 'click', 'focus', 'blur', 'keydown'])

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
    },
  ]
})

const handleInput = (event) => {
  const value = props.type === 'number' ? Number(event.target.value) || 0 : event.target.value

  emit('update:modelValue', value)
}
</script>

<style scoped>
@reference "../../assets/main.css";

/* =============================================
   BASE INPUT - STATIC COMPONENT
   ============================================= */

.base-input {
  @apply w-full;
}

.base-input__label {
  @apply block text-sm font-medium text-slate-700 mb-1;
}

.base-input__wrapper {
  @apply relative flex items-center;
}

.base-input__prefix,
.base-input__suffix {
  @apply text-sm text-slate-500;
}

.base-input__prefix {
  @apply mr-2;
}

.base-input__suffix {
  @apply ml-2;
}

.base-input__field {
  @apply block w-full px-3 py-2 bg-white border border-slate-200 rounded-md text-sm shadow-sm placeholder:text-slate-400 focus:outline-none focus:border-[#0B7A9E] focus:ring-1 focus:ring-[#0B7A9E] transition-colors;
}

.base-input__field--sm {
  @apply px-2 py-1 text-xs;
}

.base-input__field--lg {
  @apply px-4 py-3 text-base;
}

.base-input__field--error {
  @apply border-red-300 focus:border-red-500 focus:ring-red-500;
}

.base-input__field--disabled {
  @apply bg-slate-100 text-slate-500 cursor-not-allowed;
}

.base-input__field--readonly {
  @apply bg-slate-50 text-slate-600;
}

.base-input__error {
  @apply mt-1 text-xs text-red-600;
}

.base-input__hint {
  @apply mt-1 text-xs text-slate-500;
}

/* Custom class untuk menghilangkan border radius bagian atas */
.base-input__field.no-top-radius {
  border-top-left-radius: 0 !important;
  border-top-right-radius: 0 !important;
  border-radius: 0 0 0.375rem 0.375rem !important;
}
</style>
