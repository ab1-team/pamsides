<template>
  <Teleport to="body">
    <Transition name="toast">
      <div
        v-if="visible"
        class="fixed! top-4! right-4! z-[9999]! flex! items-start! gap-3! max-w-sm! bg-emerald-50! border! border-emerald-200! rounded-xl! shadow-xl! p-4!"
      >
        <div class="shrink-0! w-8! h-8! rounded-full! bg-emerald-500! flex! items-center! justify-center!">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5! h-5! text-white!" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="flex-1! min-w-0!">
          <p class="text-sm! font-bold! text-emerald-900!">{{ title || 'Berhasil!' }}</p>
          <p class="text-xs! text-emerald-700! mt-0.5!">{{ message }}</p>
        </div>
        <button
          type="button"
          class="shrink-0! text-emerald-500! hover:text-emerald-700! transition-colors!"
          @click="visible = false"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4! h-4!" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  message: { type: String, default: '' },
  duration: { type: Number, default: 3000 },
})

const emit = defineEmits(['update:modelValue'])

const visible = ref(props.modelValue)
let timer = null

const show = () => {
  visible.value = true
  if (timer) clearTimeout(timer)
  timer = setTimeout(() => { visible.value = false }, props.duration)
}

watch(() => props.modelValue, (v) => {
  if (v) show()
  else { visible.value = false; if (timer) clearTimeout(timer) }
})

watch(visible, (v) => {
  if (!v) emit('update:modelValue', false)
})
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.25s ease;
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(20px);
}
.toast-leave-to {
  opacity: 0;
  transform: translateX(20px);
}
</style>