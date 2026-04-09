<template>
  <div :class="statCardClasses">
    <div class="stat-card-header">
      <div class="stat-card-title">{{ label }}</div>
    </div>

    <div class="stat-card-content">
      <div class="stat-card-value-container">
        <div class="stat-card-value">{{ value }}</div>
        <router-link :to="link || '#'" class="stat-card-link">
          <span>Lihat Detail</span>
          <font-awesome-icon icon="arrow-right" class="link-icon" />
        </router-link>
      </div>
      <div class="stat-card-icon">
        <template v-if="!!$slots.default">
          <slot />
        </template>
        <template v-else>
          <font-awesome-icon icon="building-user" style="color: #0ea5e9" />
        </template>
      </div>
    </div>

    <div class="stat-card-progress">
      <div class="stat-card-progress-bar" style="width: 74%"></div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: String,
  value: Number,
  link: {
    type: String,
    default: '#',
  },
})

const statCardClasses = computed(() => {
  const labelLower = props.label.toLowerCase()

  if (labelLower.includes('instalasi')) {
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
