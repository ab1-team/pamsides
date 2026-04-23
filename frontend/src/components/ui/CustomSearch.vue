<template>
  <div
    class="relative! flex! items-center! bg-white! rounded-full! shadow-md! border! border-slate-100! p-1! group! transition-all! duration-300! focus-within:ring-2! focus-within:ring-blue-100! focus-within:border-blue-300!"
  >
    <div class="pl-4! pr-1! text-slate-400! group-focus-within:text-blue-500! transition-colors!">
      <font-awesome-icon icon="search" class="text-sm!" />
    </div>
    <input
      v-model="searchQuery"
      type="text"
      :placeholder="placeholder"
      class="flex-1! bg-transparent! border-0! ring-0! focus:ring-0! text-sm! py-2! px-3! text-slate-700! placeholder-slate-400! outline-none!"
      @input="handleInput"
    />
    <BaseButton
      variant="danger"
      size="md"
      class="rounded-full! shadow-md! min-w-[40px]! sm:min-w-[140px]! py-2.5!"
      @click="handleSearch"
    >
      <span class="hidden sm:inline">{{ buttonText }}</span>
      <font-awesome-icon icon="arrow-right" class="sm:hidden!" />
    </BaseButton>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseButton from './BaseButton.vue'

const props = defineProps({
  placeholder: {
    type: String,
    default: 'Search customer name, ID, or installation code...',
  },
  buttonText: {
    type: String,
    default: 'Detail Transaksi',
  },
  modelValue: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['update:modelValue', 'search', 'input'])

const searchQuery = ref(props.modelValue)

// Pantau perubahan dari luar
watch(
  () => props.modelValue,
  (newValue) => {
    searchQuery.value = newValue
  },
)

// Emit perubahan
watch(searchQuery, (newValue) => {
  emit('update:modelValue', newValue)
})

const handleInput = (event) => {
  emit('input', event.target.value)
}

const handleSearch = () => {
  emit('search', searchQuery.value)
}
</script>

<style scoped>
@reference "../../assets/main.css";

input::placeholder {
  @apply text-slate-400 font-normal italic opacity-70;
}
</style>
