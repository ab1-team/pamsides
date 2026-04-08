<template>
  <div class="select-search-wrapper" :class="{ 'dropdown-open': showDropdown }" ref="wrapperRef">
    <label v-if="label" class="text-sm mb-1">
      {{ label }}
    </label>

    <div class="select-input-wrapper" @click="toggleDropdown($event)">
      <input
        ref="inputRef"
        :value="displayText"
        readonly
        :placeholder="placeholder"
        class="select-input"
      />
      <div class="select-chevron" :class="{ 'rotate-180': showDropdown }">
        <font-awesome-icon icon="chevron-down" />
      </div>
    </div>

    <div v-if="showDropdown" class="select-dropdown">
      <!-- Search Input -->
      <div class="search-input-wrapper">
        <font-awesome-icon icon="search" class="search-icon" />
        <input
          ref="searchRef"
          v-model="searchQuery"
          :placeholder="searchPlaceholder"
          class="search-input"
          @click.stop
        />
      </div>

      <!-- Options List -->
      <div class="options-list">
        <div v-if="filteredOptions.length === 0" class="no-options">
          <font-awesome-icon icon="search" class="text-gray-400 mr-2" />
          Tidak ada data ditemukan
        </div>

        <div
          v-for="option in filteredOptions"
          :key="option.id"
          class="option-item"
          :class="{ selected: isSelected(option), 'has-group': option.group }"
          @click="selectOption(option, $event)"
        >
          <span v-if="option.group" class="option-group">{{ option.group }}</span>
          <span class="option-text">
            <span v-if="!option.group" class="option-number">{{ option.id }}.</span>
            <span class="option-name">{{ option.text }}</span>
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  options: {
    type: Array,
    required: true,
  },
  label: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Pilih opsi',
  },
  searchPlaceholder: {
    type: String,
    default: 'Cari...',
  },
  icon: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'change'])

// Refs
const wrapperRef = ref(null)
const inputRef = ref(null)
const searchRef = ref(null)
const showDropdown = ref(false)
const searchQuery = ref('')

// Computed
const displayText = computed(() => {
  const selected = props.options.find((opt) => opt.id === props.modelValue)
  return selected ? selected.text : props.placeholder
})

const filteredOptions = computed(() => {
  if (!searchQuery.value) {
    return props.options
  }

  const query = searchQuery.value.toLowerCase()
  return props.options.filter(
    (option) =>
      option.text.toLowerCase().includes(query) ||
      (option.group && option.group.toLowerCase().includes(query)),
  )
})

// Methods
const isSelected = (option) => {
  return option.id === props.modelValue
}

const selectOption = (option, event) => {
  event.stopPropagation()
  emit('update:modelValue', option.id)
  emit('change', option)
  showDropdown.value = false
  searchQuery.value = ''
}

const toggleDropdown = (event) => {
  if (props.disabled) return
  event.stopPropagation()

  // If opening dropdown, close all other dropdowns first
  if (!showDropdown.value) {
    document.dispatchEvent(
      new CustomEvent('close-other-selects', {
        detail: { excludeId: wrapperRef.value },
      }),
    )
  }

  showDropdown.value = !showDropdown.value

  if (showDropdown.value) {
    nextTick(() => {
      searchRef.value?.focus()
    })
  }
}

const closeDropdown = (event) => {
  if (!wrapperRef.value?.contains(event.target)) {
    showDropdown.value = false
    searchQuery.value = ''
  }
}

// Watch for outside clicks
onMounted(() => {
  document.addEventListener('click', closeDropdown)
  // Listen for custom event to close other dropdowns
  document.addEventListener('close-other-selects', handleCloseOtherSelects)
})

onUnmounted(() => {
  cleanup()
})

// Handle close other dropdowns event
const handleCloseOtherSelects = (event) => {
  if (event.detail && event.detail.excludeId !== wrapperRef.value) {
    showDropdown.value = false
    searchQuery.value = ''
  }
}

// Cleanup function
const cleanup = () => {
  document.removeEventListener('click', closeDropdown)
  document.removeEventListener('close-other-selects', handleCloseOtherSelects)
}

// Watch modelValue changes
watch(
  () => props.modelValue,
  (newValue) => {
    emit(
      'change',
      props.options.find((opt) => opt.id === newValue),
    )
  },
)
</script>

<style scoped>
.select-search-wrapper {
  position: relative;
  width: 100%;
  z-index: 1000;
  isolation: isolate;
}

.select-search-wrapper.dropdown-open {
  z-index: 9999999;
}

.select-input-wrapper {
  position: relative;
  cursor: pointer;
}

.select-input {
  width: 100%;
  padding: 0.625rem 2.5rem 0.625rem 0.875rem;
  border: 1px solid #e2e8f0;
  border-radius: var(--border-radius-md);
  background: #f8fafc;
  font-size: var(--text-sm);
  color: #374151;
  cursor: pointer;
  transition: all 0.2s ease;
  pointer-events: none;
}

.select-input:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.select-input:focus {
  outline: none;
  background: #f8fafc;
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

.select-chevron {
  position: absolute;
  right: 0.875rem;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
  pointer-events: none;
  transition: transform 0.2s ease;
}

.select-chevron.rotate-180 {
  transform: translateY(-50%) rotate(180deg);
}

.select-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 9999999;
  margin-top: 0.25rem;
  background: white;
  border: none;
  border-radius: 0.75rem;
  box-shadow:
    0 20px 25px -5px rgba(0, 0, 0, 0.1),
    0 10px 10px -5px rgba(0, 0, 0, 0.04);
  max-height: 300px;
  overflow: hidden;
  transform: translateY(0);
  will-change: transform;
}

.search-input-wrapper {
  position: relative;
  padding: 0.75rem;
  border-bottom: none;
  background: #f8fafc;
  border-radius: 0.75rem 0.75rem 0 0;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  font-size: var(--text-sm);
}

.search-input {
  width: 100%;
  padding: 0.5rem 0.5rem 0.5rem 2rem;
  border: 1px solid #e2e8f0;
  border-radius: var(--border-radius-md);
  background: white;
  font-size: var(--text-sm);
  color: #374151;
  transition: all 0.2s ease;
}

.search-input:focus {
  outline: none;
  background: white;
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

.options-list {
  max-height: 220px;
  overflow-y: auto;
  border-top: none;
}

.option-item {
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: all 0.15s ease;
  border-left: none;
  border-bottom: none;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.option-item.has-group {
  padding-left: 1.5rem;
}

.option-item.has-group .option-text {
  gap: 0;
}

.option-item.has-group .option-name {
  color: #374151;
}

.option-item:last-child {
  border-bottom: none;
}

.option-item:hover {
  background-color: #f1f5f9;
}

.option-item.selected {
  background-color: #eff6ff;
  color: #1e40af;
  font-weight: 600;
}

.option-group {
  display: block;
  font-size: var(--text-xs);
  font-weight: var(--font-weight-semibold);
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.option-text {
  display: flex;
  align-items: center;
  font-size: var(--text-sm);
  color: #374151;
  font-weight: var(--font-weight-medium);
  gap: 0.5rem;
}

.option-number {
  color: #6b7280;
  font-weight: 600;
  min-width: 2rem;
}

.option-name {
  color: #374151;
  font-weight: 500;
}

.no-options {
  padding: 1rem;
  text-align: center;
  color: #9ca3af;
  font-size: var(--text-sm);
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Scrollbar styling */
.options-list::-webkit-scrollbar {
  width: 8px;
}

.options-list::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.options-list::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
  border: 1px solid #f1f1f1;
}

.options-list::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

.options-list::-webkit-scrollbar-corner {
  background: #f1f1f1;
}

/* Disabled state */
.select-search-wrapper.disabled .select-input {
  background-color: #f9fafb;
  color: #9ca3af;
  cursor: not-allowed;
}

.select-search-wrapper.disabled .select-input-wrapper {
  cursor: not-allowed;
}
</style>
