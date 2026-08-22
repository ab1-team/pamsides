<template>
  <div
    :class="meterCardClasses"
    @click="handleClick"
    @mouseenter="showPriceDetail = true"
    @mouseleave="showPriceDetail = false"
    :style="isClickable ? 'cursor: pointer; position: relative;' : ''"
  >
    <div class="flex items-center justify-between mb-1.5">
      <div :class="titleClasses">{{ title }}</div>
      <div :class="iconClasses">
        <font-awesome-icon :icon="icon" :class="iconColorClasses" />
      </div>
    </div>
    <div class="flex items-baseline gap-1">
      <span :class="valueClasses">{{ value }}</span>
      <span :class="unitClasses">{{ unit }}</span>
    </div>
    <font-awesome-icon
      v-if="showDecoration"
      icon="tint"
      class="text-gray-400! text-sm! mt-1! self-end!"
    />

    <div v-if="title === 'HARGA METER'" class="price-detail-wrapper">
      <PriceDetailHover v-show="showPriceDetail" />
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import PriceDetailHover from './PriceDetailHover.vue'

const props = defineProps({
  type: {
    type: String,
    required: true,
    validator: (value) => ['initial', 'final', 'total'].includes(value),
  },
  title: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number],
    required: true,
  },
  unit: {
    type: String,
    default: 'm³',
  },
  showDecoration: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['click'])

const showPriceDetail = ref(false)

const isClickable = computed(() => {
  return props.title === 'HARGA METER'
})

const handleClick = () => {
  if (isClickable.value) {
    emit('click')
  }
}

const meterCardClasses = computed(() => {
  const base =
    props.type === 'total' && props.title === 'HARGA METER'
      ? 'rounded-xl! p-3! shadow-md! relative!'
      : 'rounded-xl! p-3! shadow-md!'

  switch (props.type) {
    case 'initial':
      return `${base} bg-gradient-to-br! from-slate-50! via-slate-100! to-slate-200!`
    case 'final':
      return `${base} bg-gradient-to-br! from-zinc-50! via-zinc-100! to-zinc-200!`
    case 'total':
      return `${base} bg-gradient-to-br! from-gray-700! via-gray-800! to-gray-900! shadow-xl! flex! flex-col!`
    default:
      return base
  }
})

const titleClasses = computed(() => {
  switch (props.type) {
    case 'initial':
      return 'text-[9px]! font-bold! text-slate-700! uppercase! tracking-wide!'
    case 'final':
      return 'text-[9px]! font-bold! text-zinc-700! uppercase! tracking-wide!'
    case 'total':
      return 'text-[9px]! font-bold! text-gray-300! uppercase! tracking-wide!'
    default:
      return 'text-[9px]! font-bold! text-slate-700! uppercase! tracking-wide!'
  }
})

const iconClasses = computed(() => {
  const base = 'w-5! h-5! rounded-lg! flex! items-center! justify-center!'

  switch (props.type) {
    case 'initial':
      return `${base} bg-slate-300/50`
    case 'final':
      return `${base} bg-zinc-300/50`
    case 'total':
      return `${base} bg-gray-600/50`
    default:
      return base
  }
})

const valueClasses = computed(() => {
  switch (props.type) {
    case 'initial':
      return 'text-lg! font-bold! text-slate-800! leading-none!'
    case 'final':
      return 'text-lg! font-bold! text-zinc-800! leading-none!'
    case 'total':
      return 'text-2xl! font-extrabold! text-white! leading-none!'
    default:
      return 'text-lg! font-bold! text-slate-800! leading-none!'
  }
})

const unitClasses = computed(() => {
  switch (props.type) {
    case 'initial':
      return 'text-xs! font-medium! text-slate-500!'
    case 'final':
      return 'text-xs! font-medium! text-zinc-500!'
    case 'total':
      return 'text-sm! font-medium! text-gray-400!'
    default:
      return 'text-xs! font-medium! text-slate-500!'
  }
})

const icon = computed(() => {
  const icons = {
    initial: 'arrow-down',
    final: 'arrow-up',
    total: 'tint',
  }
  return icons[props.type]
})

const iconColorClasses = computed(() => {
  switch (props.type) {
    case 'initial':
      return 'text-slate-600! text-xs!'
    case 'final':
      return 'text-zinc-600! text-xs!'
    case 'total':
      return 'text-gray-300! text-xs!'
    default:
      return 'text-slate-600! text-xs!'
  }
})
</script>

<style scoped>
.price-detail-wrapper {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 1000;
}
</style>
