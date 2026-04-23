<template>
  <div class="custom-select-search" :class="{ 'mb-2!': !noMargin }">
    <!-- Label -->
    <label v-if="label" class="select-label">{{ label }}</label>

    <div class="select-wrapper" ref="dropdownRef">
      <!-- Input Area -->
      <div
        class="select-display"
        :class="{
          'select-display--active': isOpen,
          'select-display--error': error,
          'select-display--disabled': disabled,
        }"
        @click="toggleDropdown"
      >
        <div class="select-content">
          <font-awesome-icon v-if="icon" :icon="icon" class="select-content-icon" />
          <span v-if="!selectedOption" class="placeholder">
            {{ placeholder }}
          </span>
          <span v-else class="selected-text" :class="{ 'has-icon': icon }">
            {{ getLabel(selectedOption) }}
          </span>
        </div>
        <font-awesome-icon
          icon="chevron-down"
          class="chevron-icon"
          :class="{ 'chevron-icon--active': isOpen }"
        />
      </div>

      <!-- Dropdown Menu -->
      <transition name="fade-slide">
        <div v-if="isOpen" class="select-dropdown">
          <!-- Search Header -->
          <div v-if="searchable" class="search-container">
            <div class="search-input-wrapper">
              <font-awesome-icon icon="search" class="search-icon" />
              <input
                ref="searchInput"
                v-model="searchQuery"
                type="text"
                :placeholder="searchPlaceholder"
                class="search-input-field"
                @click.stop
                @keydown.esc="closeDropdown"
              />
            </div>
          </div>

          <!-- Options List -->
          <div class="options-list custom-scrollbar">
            <div
              v-for="option in filteredOptions"
              :key="getValue(option)"
              class="option-item"
              :class="{ 'option-item--selected': isSelected(option) }"
              @click="selectOption(option)"
            >
              <div class="option-label">
                {{ getLabel(option) }}
              </div>
              <div v-if="isSelected(option)" class="selected-indicator">
                <font-awesome-icon icon="check" />
              </div>
            </div>

            <!-- Empty State -->
            <div v-if="filteredOptions.length === 0" class="empty-state">
              <font-awesome-icon icon="search" class="text-slate-200! text-3xl! mb-2!" />
              <p>Tidak ada data ditemukan</p>
            </div>
          </div>
        </div>
      </transition>
    </div>

    <!-- Error/Hint -->
    <div v-if="error" class="error-text">{{ error }}</div>
    <div v-if="hint && !error" class="hint-text">{{ hint }}</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: [String, Number, Object],
    default: null,
  },
  options: {
    type: Array,
    default: () => [],
  },
  label: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Pilih opsi...',
  },
  icon: {
    type: String,
    default: '',
  },
  searchable: {
    type: Boolean,
    default: true,
  },
  searchPlaceholder: {
    type: String,
    default: 'Cari...',
  },
  labelKey: {
    type: String,
    default: 'label',
  },
  valueKey: {
    type: String,
    default: 'value',
  },
  error: {
    type: String,
    default: '',
  },
  hint: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  noMargin: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'change'])

// State
const isOpen = ref(false)
const searchQuery = ref('')
const dropdownRef = ref(null)
const searchInput = ref(null)

// Komputasi
const selectedOption = computed(() => {
  if (props.modelValue === null || props.modelValue === undefined) return null
  return (
    props.options.find((opt) => getValue(opt) === getValue(props.modelValue)) || props.modelValue
  )
})

const filteredOptions = computed(() => {
  if (!props.searchable || !searchQuery.value) return props.options
  const query = searchQuery.value.toLowerCase()
  return props.options.filter((opt) => getLabel(opt).toLowerCase().includes(query))
})

// Fungsi-fungsi penanganan
const getLabel = (opt) => {
  if (opt === null || opt === undefined) return ''
  if (typeof opt === 'object') {
    // Jika labelKey tidak ada, coba atribut umum
    return opt[props.labelKey] ?? opt.text ?? opt.name ?? opt.label ?? ''
  }
  return opt
}

const getValue = (opt) => {
  if (opt === null || opt === undefined) return null
  if (typeof opt === 'object') {
    // Jika valueKey tidak ada, coba atribut umum
    return opt[props.valueKey] ?? opt.id ?? opt.value ?? null
  }
  return opt
}

const isSelected = (opt) => {
  return getValue(opt) === getValue(props.modelValue)
}

const toggleDropdown = () => {
  if (props.disabled) return
  isOpen.value = !isOpen.value
}

const closeDropdown = () => {
  isOpen.value = false
  searchQuery.value = ''
}

const selectOption = (opt) => {
  emit('update:modelValue', getValue(opt))
  emit('change', opt)
  closeDropdown()
}

// Penanganan klik di luar elemen
const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    closeDropdown()
  }
}

// Pantau fokus elemen
watch(isOpen, (newVal) => {
  if (newVal && props.searchable) {
    setTimeout(() => {
      searchInput.value?.focus()
    }, 50)
  }
})

onMounted(() => {
  document.addEventListener('mousedown', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('mousedown', handleClickOutside)
})
</script>

<style scoped>
@reference "@/assets/css/main.css";

.custom-select-search {
  @apply w-full transition-all duration-300;
}

.select-label {
  @apply block text-sm font-normal text-slate-500 mb-1.5 ml-1;
}

.select-wrapper {
  @apply relative w-full;
}

.select-display {
  @apply flex items-center justify-between w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 transition-all duration-300 cursor-pointer;
  @apply hover:border-blue-400 hover:bg-white hover:shadow-md hover:shadow-blue-500/5;
}

.select-display--active {
  @apply bg-white border-blue-500 ring-4 ring-blue-500/10 shadow-lg shadow-blue-500/5;
}

.select-display--error {
  @apply border-red-300 bg-red-50/30;
}

.select-display--disabled {
  @apply bg-slate-100 text-slate-400 cursor-not-allowed border-slate-200 shadow-none hover:shadow-none;
}

.select-content {
  @apply flex items-center gap-3 overflow-hidden;
}

.select-content-icon {
  @apply text-slate-400 text-sm shrink-0;
}

.placeholder {
  @apply text-slate-400 text-sm truncate font-normal;
}

.selected-text {
  @apply text-slate-700 text-sm font-medium truncate;
}

.chevron-icon {
  @apply text-slate-400 text-xs transition-transform duration-300 shrink-0 ml-2;
}

.chevron-icon--active {
  @apply rotate-180 text-blue-500;
}

.select-dropdown {
  @apply absolute top-full left-0 right-0 mt-2 bg-white border border-slate-200 rounded-2xl shadow-xl shadow-slate-200/50 z-[9999] overflow-hidden;
}

.search-container {
  @apply p-2.5 border-b border-slate-100 bg-slate-50/50;
}

.search-input-wrapper {
  @apply relative flex items-center;
}

.search-icon {
  @apply absolute left-3 text-slate-400 text-sm;
}

.search-input-field {
  @apply w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 transition-all;
  @apply placeholder:text-slate-400 placeholder:font-normal;
  @apply focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/5;
}

.options-list {
  @apply max-h-60 overflow-y-auto p-1;
}

.option-item {
  @apply flex items-center justify-between px-3 py-2.5 rounded-xl cursor-pointer transition-all duration-200;
}

.option-item:hover {
  @apply bg-blue-50 text-blue-700;
}

.option-item--selected {
  @apply bg-blue-600 text-white hover:bg-blue-600 hover:text-white! shadow-md shadow-blue-200;
}

.option-label {
  @apply text-sm truncate font-medium;
}

.selected-indicator {
  @apply text-xs ml-2;
}

.empty-state {
  @apply py-8 px-4 text-center text-sm text-slate-400 font-medium;
}

.error-text {
  @apply mt-1.5 ml-1 text-xs text-red-600 font-medium;
}

.hint-text {
  @apply mt-1.5 ml-1 text-xs text-slate-400;
}

.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.2s ease-out;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  @apply bg-slate-200 rounded-full;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  @apply bg-slate-300;
}
</style>
