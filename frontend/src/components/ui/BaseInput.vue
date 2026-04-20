<template>
  <div class="base-input" :class="{ 'mb-2!': !noMargin }">
    <label v-if="label" :for="inputId" class="base-input__label">
      {{ label }}
    </label>

    <div class="base-input__wrapper">
      <!-- Prefix Icon/Text -->
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
        @input="handleInput"
        @change="$emit('change', $event.target.value)"
        @focus="$emit('focus', $event)"
        @blur="$emit('blur', $event)"
        @keydown="$emit('keydown', $event)"
      ></textarea>

      <!-- Suffix Icon or Action -->
      <div v-if="$slots.suffix" class="base-input__suffix">
        <slot name="suffix" />
      </div>
    </div>

    <!-- Error Message -->
    <div v-if="error" class="base-input__error">
      {{ error }}
    </div>

    <!-- Hint Text -->
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

<style scoped>
@reference "../../assets/main.css";

/* =============================================
   BASE INPUT - PREMIUM STANDARDIZED
   ============================================= */

.base-input {
  @apply w-full transition-all duration-300;
}

.base-input__label {
  @apply block text-sm font-normal text-slate-500 mb-1.5 ml-1;
}

.base-input__wrapper {
  @apply relative flex items-center;
}

.base-input__prefix {
  @apply absolute left-4 text-slate-400 text-sm font-medium z-10;
}

.base-input__suffix {
  @apply absolute right-4 text-slate-400 text-sm font-medium z-10;
}

.base-input__field {
  @apply block w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 transition-all duration-300;
  @apply placeholder:text-slate-400 placeholder:font-normal;
  @apply hover:border-blue-400 hover:bg-white hover:shadow-md hover:shadow-blue-500/5;
  @apply focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 focus:shadow-lg focus:shadow-blue-500/5;
}

.base-input__field--has-prefix {
  @apply pl-10;
}

.base-input__field--textarea {
  @apply h-auto py-3 px-4 leading-relaxed resize-none;
}

.base-input__field--sm {
  @apply h-9 px-3 text-xs rounded-lg;
}

.base-input__field--lg {
  @apply h-12 px-5 text-base rounded-2xl;
}

.base-input__field--error {
  @apply border-red-300 bg-red-50/30 focus:border-red-500 focus:ring-red-500/10;
}

.base-input__field--disabled {
  @apply bg-slate-100 text-slate-400 cursor-not-allowed border-slate-200 shadow-none hover:border-slate-200 hover:shadow-none;
}

.base-input__field--readonly {
  @apply bg-slate-50 text-slate-600 border-slate-200;
}

.base-input__error {
  @apply mt-1.5 ml-1 text-xs text-red-600 font-medium;
}

.base-input__hint {
  @apply mt-1.5 ml-1 text-xs text-slate-400;
}
</style>
