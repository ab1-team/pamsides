<template>
  <div :class="cardClasses" @click="handleClick">
    <div v-if="$slots.header" class="content-card__header!">
      <slot name="header" />
    </div>

    <div class="content-card__body!">
      <slot />
    </div>

    <div v-if="$slots.footer" class="content-card__footer!">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'bordered', 'elevated', 'minimal'].includes(value),
  },
  padding: {
    type: String,
    default: 'normal',
    validator: (value) => ['none', 'small', 'normal', 'large'].includes(value),
  },
  hoverable: {
    type: Boolean,
    default: false,
  },
  clickable: {
    type: Boolean,
    default: false,
  },
  rounded: {
    type: String,
    default: 'xl',
    validator: (value) => ['none', 'sm', 'md', 'lg', 'xl', '2xl', 'full'].includes(value),
  },
})

const emit = defineEmits(['click'])

const cardClasses = computed(() => {
  const base = 'content-card'

  // Variant styles
  const variants = {
    default: 'content-card--default',
    bordered: 'content-card--bordered',
    elevated: 'content-card--elevated',
    minimal: 'content-card--minimal',
  }

  // Padding styles
  const paddings = {
    none: 'content-card--padding-none',
    small: 'content-card--padding-small',
    normal: 'content-card--padding-normal',
    large: 'content-card--padding-large',
  }

  // Rounded styles
  const roundedStyles = {
    none: 'rounded-none',
    sm: 'rounded-sm',
    md: 'rounded-md',
    lg: 'rounded-lg',
    xl: 'rounded-xl',
    '2xl': 'rounded-2xl',
    full: 'rounded-full',
  }

  // Interactive styles
  const interactive = props.hoverable || props.clickable ? 'content-card--interactive' : ''

  return [
    base,
    variants[props.variant],
    paddings[props.padding],
    roundedStyles[props.rounded],
    interactive,
  ]
})

const handleClick = (event) => {
  if (props.clickable) {
    emit('click', event)
  }
}
</script>
