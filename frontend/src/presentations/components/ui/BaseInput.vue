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
        :type="inputType"
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
      <!-- Password Visibility Toggle -->
      <div v-if="type === 'password'" class="base-input__password-toggle">
        <button type="button" @click="togglePassword" class="focus:outline-none!">
          <font-awesome-icon :icon="showPassword ? 'eye-slash' : 'eye'" />
        </button>
      </div>

      <!-- Suffix Icon or Action -->
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
import { computed, useId, ref } from 'vue'

const showPassword = ref(false)
const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const inputType = computed(() => {
  if (props.type === 'password') {
    return showPassword.value ? 'text' : 'password'
  }
  return props.type
})

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

<style scoped>
.base-input {
  display: flex;
  flex-direction: column;
}

.base-input__label {
  display: block;
  font-size: 0.875rem;
  font-weight: 400;
  color: #64748b;
  margin-bottom: 0.375rem;
  margin-left: 0.25rem;
}

.base-input__wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.base-input__prefix {
  position: absolute;
  left: 1rem;
  color: #94a3b8;
  display: flex;
  align-items: center;
  pointer-events: none;
}

.base-input__field {
  width: 100%;
  border-radius: 0.75rem;
  border: 1px solid #e2e8f0;
  background-color: #ffffff;
  padding: 0.625rem 1rem;
  font-size: 0.875rem;
  color: #1e293b;
  transition: all 0.2s ease;
}

.base-input__field:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.base-input__field--has-prefix {
  padding-left: 2.75rem;
}

.base-input__field--disabled {
  background-color: #f1f5f9;
  color: #94a3b8;
  cursor: not-allowed;
  border-color: #e2e8f0;
}

.base-input__password-toggle {
  position: absolute;
  right: 1rem;
  color: #94a3b8;
  cursor: pointer;
  display: flex;
  align-items: center;
  transition: color 0.2s;
}

.base-input__password-toggle:hover {
  color: #64748b;
}

.base-input__error {
  color: #ef4444;
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.base-input__hint {
  color: #64748b;
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.base-input__field--textarea {
  resize: vertical;
}
</style>
