<template>
  <div
    :class="statCardClasses"
    role="button"
    :tabindex="0"
    @click="handleCardClick"
    @keydown.enter="handleCardClick"
    @keydown.space.prevent="handleCardClick"
  >
    <div class="stat-card-header">
      <div class="stat-card-icon">
        <template v-if="!!$slots.default">
          <slot />
        </template>
        <template v-else>
          <font-awesome-icon icon="building-user" style="color: #0ea5e9" />
        </template>
      </div>
      <div class="stat-card-title">{{ label }}</div>
    </div>

    <div class="stat-card-content">
      <div class="stat-card-value">{{ value }}</div>
    </div>

    <div class="stat-card-footer">
      <button
        v-if="!link"
        type="button"
        class="stat-card-link bg-transparent border-none p-0 cursor-pointer text-inherit"
        @click.stop="$emit('detail-click')"
      >
        <span>Lihat Detail</span>
        <font-awesome-icon icon="arrow-right" class="link-icon" />
      </button>
      <router-link v-else :to="link" class="stat-card-link" @click.stop>
        <span>Lihat Detail</span>
        <font-awesome-icon icon="arrow-right" class="link-icon" />
      </router-link>
    </div>

    <div class="stat-card-progress">
      <div class="stat-card-progress-bar" :style="{ width: progress + '%' }"></div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const emit = defineEmits(['detail-click'])

const props = defineProps({
  label: String,
  value: Number,
  link: {
    type: String,
    default: '#',
  },
  progress: {
    type: Number,
    default: 0,
    validator: (v) => v >= 0 && v <= 100,
  },
})

const handleCardClick = () => {
  emit('detail-click')
}

const statCardClasses = computed(() => {
  const labelLower = props.label.toLowerCase()

  if (labelLower.includes('instalasi') || labelLower.includes('permohonan')) {
    return 'stat-card stat-card--instalasi'
  } else if (labelLower.includes('pemakaian')) {
    return 'stat-card stat-card--pemakaian'
  } else if (labelLower.includes('tunggakan')) {
    return 'stat-card stat-card--tunggakan'
  } else if (labelLower.includes('tagihan')) {
    return 'stat-card stat-card--tagihan'
  }

  return 'stat-card'
})
</script>
