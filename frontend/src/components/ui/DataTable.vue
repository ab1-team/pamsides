<template>
  <div
    :class="
      props.noCard
        ? 'h-full flex flex-col'
        : 'bg-white rounded-xl shadow-[0_10px_15px_-3px_rgba(0,0,0,0.1),0_4px_6px_-2px_rgba(0,0,0,0.05)] overflow-hidden transition-all hover:[transform:translateY(-2px)] hover:shadow-[0_20px_25px_-5px_rgba(0,0,0,0.1),0_10px_10px_-5px_rgba(0,0,0,0.04)]'
    "
  >
    <div v-if="showToolbar" class="flex items-center justify-between p-4 border-b border-slate-100">
      <div class="flex items-center gap-3">
        <span class="text-sm font-semibold text-slate-900">{{ title }}</span>
        <slot name="toolbar-actions"></slot>
      </div>
      <div class="relative">
        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400">🔍</span>
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="searchPlaceholder"
          class="pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-900 w-56 hover:bg-white hover:border-slate-300 focus:border-cyan-600 focus:bg-white focus:outline-none transition-all"
        />
      </div>
    </div>

    <div :class="props.noCard ? 'overflow-x-auto flex-1' : 'overflow-x-auto'">
      <table class="w-full text-left">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr>
            <th
              v-for="column in columns"
              :key="column.key"
              class="px-4 py-2.5 text-xs font-bold text-slate-500 uppercase tracking-wider text-left whitespace-nowrap"
            >
              {{ column.title }}
            </th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-100">
          <tr v-if="data.length === 0">
            <td :colspan="columns.length" class="px-4 py-8 text-center text-sm text-slate-400">
              Tidak ada data ditemukan.
            </td>
          </tr>

          <tr
            v-else
            v-for="(row, index) in paginatedData"
            :key="row.id || index"
            class="hover:bg-slate-50 transition-colors"
          >
            <td
              v-for="column in columns"
              :key="column.key"
              class="px-4 py-2 border-b border-slate-100 align-middle"
              :class="column.tdClass"
            >
              <slot :name="`column-${column.key}`" :row="row" :column="column" :index="index">
                <span v-if="column.render" v-html="column.render(row, column, index)"></span>
                <span v-else>{{ getNestedValue(row, column.key) }}</span>
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      v-if="showPagination"
      :class="
        props.noCard
          ? 'flex items-center justify-between p-4 border-t border-slate-100'
          : 'flex items-center justify-between p-4 border-t border-slate-100 bg-slate-50'
      "
    >
      <div class="text-sm text-slate-500 font-medium">
        Showing {{ showingInfo.start }} to {{ showingInfo.end }} of
        <strong class="font-bold text-slate-900">{{ showingInfo.total }}</strong> entries
      </div>
      <div class="flex items-center gap-1.5">
        <button
          class="w-8 h-8 flex items-center justify-center rounded-md border border-slate-200 bg-white text-sm font-semibold text-slate-600 cursor-pointer transition-all hover:border-cyan-600 hover:text-cyan-600 hover:bg-cyan-50 disabled:opacity-40 disabled:cursor-not-allowed"
          :disabled="currentPage === 1"
          @click="$emit('prev-page')"
        >
          ‹
        </button>
        <button
          v-for="page in visiblePages"
          :key="page"
          class="min-w-[32px] h-8 flex items-center justify-center rounded-md border border-slate-200 bg-white text-sm font-semibold text-slate-600 cursor-pointer transition-all px-2 hover:border-cyan-600 hover:text-cyan-600 hover:bg-cyan-50"
          :class="{ 'bg-cyan-50 border-cyan-600 text-cyan-600': page === currentPage }"
          @click="$emit('go-to-page', page)"
        >
          {{ page }}
        </button>
        <button
          class="w-8 h-8 flex items-center justify-center rounded-md border border-slate-200 bg-white text-sm font-semibold text-slate-600 cursor-pointer transition-all hover:border-cyan-600 hover:text-cyan-600 hover:bg-cyan-50 disabled:opacity-40 disabled:cursor-not-allowed"
          :disabled="currentPage === totalPages"
          @click="$emit('next-page')"
        >
          ›
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  // Data
  data: {
    type: Array,
    default: () => [],
  },
  columns: {
    type: Array,
    required: true,
  },

  // UI Options
  title: {
    type: String,
    default: '',
  },
  showToolbar: {
    type: Boolean,
    default: true,
  },
  showPagination: {
    type: Boolean,
    default: true,
  },
  searchPlaceholder: {
    type: String,
    default: 'Find subscriber name...',
  },
  searchQuery: {
    type: String,
    default: '',
  },

  // Pagination
  currentPage: {
    type: Number,
    default: 1,
  },
  perPage: {
    type: Number,
    default: 10,
  },
  totalPages: {
    type: Number,
    default: 1,
  },
  visiblePages: {
    type: Array,
    default: () => [],
  },
  totalEntries: {
    type: Number,
    default: 0,
  },
  noCard: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:searchQuery', 'prev-page', 'next-page', 'go-to-page'])

const searchQuery = computed({
  get: () => props.searchQuery,
  set: (value) => emit('update:searchQuery', value),
})

// Computed property for paginated data
const paginatedData = computed(() => {
  const start = (props.currentPage - 1) * props.perPage
  const end = start + props.perPage
  return props.data.slice(start, end)
})

// Computed property for showing info
const showingInfo = computed(() => {
  const start = props.totalEntries === 0 ? 0 : (props.currentPage - 1) * props.perPage + 1
  const end = Math.min(props.currentPage * props.perPage, props.totalEntries)
  return {
    start,
    end,
    total: props.totalEntries,
  }
})

// Helper function to get nested object values
const getNestedValue = (obj, path) => {
  return path.split('.').reduce((current, key) => {
    return current && current[key] !== undefined ? current[key] : ''
  }, obj)
}
</script>
