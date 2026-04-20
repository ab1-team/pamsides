<template>
  <component
    :is="componentTag"
    :type="isButton ? type : undefined"
    :to="to"
    :href="href"
    :disabled="disabled || loading"
    :class="buttonClasses"
    class="base-button"
    @click="handleClick"
  >
    <font-awesome-icon v-if="loading" icon="spinner" class="base-button__spinner" spin />
    <font-awesome-icon
      v-else-if="icon && iconPosition === 'left' && !iconRight"
      :icon="icon"
      class="base-button__icon"
    />
    <span v-if="$slots.default" class="base-button__text">
      <slot />
    </span>
    <font-awesome-icon
      v-if="!loading && icon && (iconPosition === 'right' || iconRight)"
      :icon="icon"
      class="base-button__icon"
    />
  </component>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  type: {
    type: String,
    default: 'button',
  },
  to: {
    type: [String, Object],
    default: null,
  },
  href: {
    type: String,
    default: null,
  },
  variant: {
    type: String,
    default: 'primary',
    validator: (value) =>
      [
        'primary',
        'info',
        'info-gradient',
        'primary-gradient',
        'secondary',
        'secondary-gradient',
        'dark-gradient',
        'success',
        'success-gradient',
        'warning',
        'warning-gradient',
        'danger',
        'danger-gradient',
        'ghost',
        'link',
        'glass',
      ].includes(value),
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
  iconRight: {
    type: Boolean,
    default: false,
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

const componentTag = computed(() => {
  if (props.to) return 'router-link'
  if (props.href) return 'a'
  return 'button'
})

const isButton = computed(() => componentTag.value === 'button')

const buttonClasses = computed(() => {
  const base = 'base-button'
  const variants = {
    primary: 'base-button--primary',
    'primary-gradient': 'base-button--primary-gradient',
    info: 'base-button--info',
    'info-gradient': 'base-button--info-gradient',
    secondary: 'base-button--secondary',
    'secondary-gradient': 'base-button--secondary-gradient',
    'dark-gradient': 'base-button--dark-gradient',
    success: 'base-button--success',
    'success-gradient': 'base-button--success-gradient',
    warning: 'base-button--warning',
    'warning-gradient': 'base-button--warning-gradient',
    danger: 'base-button--danger',
    'danger-gradient': 'base-button--danger-gradient',
    ghost: 'base-button--ghost',
    link: 'base-button--link',
    glass: 'base-button--glass',
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
