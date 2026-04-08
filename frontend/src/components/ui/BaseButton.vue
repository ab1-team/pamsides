<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="buttonClasses"
    class="base-button"
    @click="handleClick"
  >
    <font-awesome-icon v-if="loading" icon="spinner" class="base-button__spinner" spin />
    <font-awesome-icon
      v-else-if="icon && iconPosition === 'left'"
      :icon="icon"
      class="base-button__icon"
    />
    <span v-if="$slots.default" class="base-button__text">
      <slot />
    </span>
    <font-awesome-icon
      v-if="!loading && icon && iconPosition === 'right'"
      :icon="icon"
      class="base-button__icon"
    />
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  type: {
    type: String,
    default: 'button',
  },
  variant: {
    type: String,
    default: 'primary',
    validator: (value) =>
      ['primary', 'secondary', 'success', 'warning', 'danger', 'ghost', 'link'].includes(value),
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
  icon: {
    type: String,
    default: '',
  },
  iconPosition: {
    type: String,
    default: 'left',
    validator: (value) => ['left', 'right'].includes(value),
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  block: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['click'])

const buttonClasses = computed(() => {
  const base = 'base-button'
  const variants = {
    primary: 'base-button--primary',
    secondary: 'base-button--secondary',
    success: 'base-button--success',
    warning: 'base-button--warning',
    danger: 'base-button--danger',
    ghost: 'base-button--ghost',
    link: 'base-button--link',
  }
  const sizes = {
    sm: 'base-button--sm',
    md: 'base-button--md',
    lg: 'base-button--lg',
  }

  return [
    base,
    variants[props.variant],
    sizes[props.size],
    {
      'base-button--disabled': props.disabled,
      'base-button--loading': props.loading,
      'base-button--block': props.block,
    },
  ]
})

const handleClick = (event) => {
  if (!props.disabled && !props.loading) {
    emit('click', event)
  }
}
</script>

<style scoped>
@reference "../../assets/main.css";

.base-button {
  @apply inline-flex items-center justify-center font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed;
}

.base-button--sm {
  @apply px-3 py-1.5 text-xs rounded-md;
}

.base-button--md {
  @apply px-4 py-2 text-sm rounded-lg;
}

.base-button--lg {
  @apply px-6 py-3 text-base rounded-xl;
}

.base-button--primary {
  @apply bg-gray-600 hover:bg-gray-700 text-white focus:ring-gray-500 shadow-sm hover:shadow-md;
}

.base-button--secondary {
  @apply bg-slate-100 hover:bg-slate-200 text-slate-700 focus:ring-slate-500 border border-slate-300;
}

.base-button--success {
  @apply bg-emerald-500 hover:bg-emerald-600 text-white focus:ring-emerald-500 shadow-sm hover:shadow-md;
}

.base-button--warning {
  @apply bg-amber-500 hover:bg-amber-600 text-white focus:ring-amber-500 shadow-sm hover:shadow-md;
}

.base-button--danger {
  @apply bg-red-500 hover:bg-red-600 text-white focus:ring-red-500 shadow-sm hover:shadow-md;
}

.base-button--ghost {
  @apply bg-transparent hover:bg-slate-100 text-slate-700 focus:ring-slate-500;
}

.base-button--link {
  @apply bg-transparent hover:bg-transparent text-[#0B7A9E] hover:text-[#094e67] focus:ring-[#0B7A9E] underline-offset-4 hover:underline p-0;
}

/* States */
.base-button--disabled {
  @apply opacity-50 cursor-not-allowed;
}

.base-button--loading {
  @apply cursor-wait;
}

.base-button--block {
  @apply w-full;
}

/* Elements */
.base-button__icon {
  @apply flex-shrink-0;
}

.base-button__icon:first-child:not(:last-child) {
  @apply mr-2;
}

.base-button__icon:last-child:not(:first-child) {
  @apply ml-2;
}

.base-button__text {
  @apply truncate;
}

.base-button__spinner {
  @apply animate-spin;
}
</style>
