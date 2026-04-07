<template>
  <div :class="cardClasses" @click="handleClick">
    <div v-if="$slots.header" class="content-card__header">
      <slot name="header" />
    </div>

    <div class="content-card__body">
      <slot />
    </div>

    <div v-if="$slots.footer" class="content-card__footer">
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

<style scoped>
.content-card {
  background-color: white;
  transition: all 0.2s ease;
}

.content-card--default {
  box-shadow:
    0 4px 6px -1px rgba(0, 0, 0, 0.1),
    0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.content-card--bordered {
  box-shadow:
    0 4px 6px -1px rgba(0, 0, 0, 0.1),
    0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.content-card--elevated {
  box-shadow:
    0 10px 15px -3px rgba(0, 0, 0, 0.1),
    0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.content-card--minimal {
  box-shadow:
    0 4px 6px -1px rgba(0, 0, 0, 0.1),
    0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.content-card--padding-none {
  padding: 0;
}

.content-card--padding-small {
  padding: 0.75rem;
}

.content-card--padding-normal {
  padding: 1rem;
}

.content-card--padding-large {
  padding: 1.5rem;
}

.content-card--interactive {
  cursor: pointer;
}

.content-card--interactive:hover {
  transform: translateY(-2px);
  box-shadow:
    0 20px 25px -5px rgba(0, 0, 0, 0.1),
    0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.content-card--interactive:active {
  transform: translateY(0);
  box-shadow:
    0 4px 6px -1px rgba(0, 0, 0, 0.1),
    0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.content-card__header {
  padding-bottom: 0.5rem;
  margin-bottom: 0.5rem;
}

.content-card__body {
  flex: 1;
}

.content-card__footer {
  padding-top: 0.75rem;
  margin-top: 0.75rem;
}

@media (max-width: 768px) {
  .content-card--padding-normal {
    padding: 0.75rem;
  }

  .content-card--padding-large {
    padding: 1rem;
  }
}
</style>
