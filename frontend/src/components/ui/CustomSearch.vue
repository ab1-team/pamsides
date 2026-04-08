<template>
  <div class="custom-search">
    <div class="custom-search__icon">
      <font-awesome-icon icon="search" class="text-sm" />
    </div>
    <input
      v-model="searchQuery"
      type="text"
      :placeholder="placeholder"
      class="custom-search__input"
      @input="handleInput"
    />
    <button class="custom-search__button" @click="handleSearch">
      <span class="hidden sm:inline">{{ buttonText }}</span>
      <font-awesome-icon icon="arrow-right" class="sm:hidden" />
    </button>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

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

// Watch for external changes
watch(
  () => props.modelValue,
  (newValue) => {
    searchQuery.value = newValue
  },
)

// Emit changes
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
/* =============================================
   CUSTOM SEARCH - REUSABLE COMPONENT
   ============================================= */

.custom-search {
  display: flex;
  align-items: center;
  background-color: white;
  border-radius: 9999px;
  box-shadow:
    0 4px 6px -1px rgba(0, 0, 0, 0.1),
    0 2px 4px -1px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

.custom-search__icon {
  padding-left: 1.25rem;
  padding-right: 0.5rem;
  color: #94a3b8;
}

.custom-search__input {
  flex: 1;
  padding: 0.5rem 1rem 0.5rem 1rem !important;
  font-size: var(--text-sm) !important;
  color: #494b50 !important;
  background-color: #ffffff !important;
  outline: none;
  border: 1px solid #ffffff !important;
  border-radius: 0.6rem !important;
  transition: all 0.2s ease !important;
  height: 38px !important;
  margin: 0 1rem !important;
  width: calc(100% - 2rem) !important;
}

.custom-search__input::placeholder {
  color: #94a3b8;
}

.custom-search__input:focus {
  outline: none;
}

.custom-search__button {
  padding: 0.75rem 1.5rem;
  background-color: #0b7a9e;
  color: white;
  font-size: 0.875rem;
  font-weight: 600;
  transition: all 0.2s ease;
  border-radius: 9999px;
  margin: 0.25rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 2.5rem;
}

@media (max-width: 640px) {
  .custom-search__button {
    padding: 0.75rem;
    min-width: 2.5rem;
  }
}

.custom-search__button:hover {
  background-color: #094e67;
  transform: translateY(-1px);
  box-shadow:
    0 10px 15px -3px rgba(11, 122, 158, 0.3),
    0 4px 6px -2px rgba(11, 122, 158, 0.2);
}

.custom-search__button:active {
  transform: translateY(0);
}
</style>
