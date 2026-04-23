<template>
  <div class="empty-state flex! flex-col! items-center! justify-center! p-12! text-center!">
    <div class="w-48! h-48! mb-6! relative!">
      <!-- Background Circles -->
      <div class="absolute! inset-0! bg-slate-50! rounded-full! scale-100! animate-pulse!"></div>
      <div class="absolute! inset-0! bg-slate-100! rounded-full! scale-75! opacity-50!"></div>

      <!-- Icon/Illustration Placeholder -->
      <div class="absolute! inset-0! flex! items-center! justify-center! text-slate-300!">
        <font-awesome-icon :icon="icon" size="4x" class="drop-shadow-sm!" />
      </div>
    </div>

    <h3 class="text-xl! font-bold! text-slate-800! mb-2!">{{ title }}</h3>
    <p class="text-slate-500! max-w-xs! mx-auto! mb-8! font-medium! leading-relaxed!">
      {{ message }}
    </p>

    <slot>
      <BaseButton v-if="actionLabel" variant="primary" @click="$emit('action')">
        {{ actionLabel }}
      </BaseButton>
    </slot>
  </div>
</template>

<script setup>
import BaseButton from './BaseButton.vue'

defineProps({
  title: {
    type: String,
    default: 'Tidak Ada Data',
  },
  message: {
    type: String,
    default: 'Belum ada data yang tersedia di sini untuk saat ini.',
  },
  icon: {
    type: String,
    default: 'folder-open',
  },
  actionLabel: {
    type: String,
    default: '',
  },
})

defineEmits(['action'])
</script>

<style scoped>
.empty-state {
  animation: fadeIn 0.8s ease-out;
}
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
