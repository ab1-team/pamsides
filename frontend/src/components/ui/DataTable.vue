<template>
  <div
    :class="
      props.noCard
        ? 'h-full! flex! flex-col!'
        : 'bg-white! rounded-xl! shadow-[0_10px_15px_-3px_rgba(0,0,0,0.1),0_4px_6px_-2px_rgba(0,0,0,0.05)]! overflow-hidden! transition-all! hover:translate-y-[-2px]! hover:shadow-[0_20px_25px_-5px_rgba(0,0,0,0.1),0_10px_10px_-5px_rgba(0,0,0,0.04)]!'
    "
  >
    <div
      v-if="showToolbar"
      class="flex! items-center! justify-between! p-4! border-b! border-slate-100!"
    >
      <div class="flex! items-center! gap-3!">
        <span class="text-sm! font-semibold! text-slate-900!">{{ title }}</span>
        <slot name="toolbar-actions"></slot>
      </div>

      <div class="relative!">
        <span class="absolute! left-3.5! top-1/2! -translate-y-1/2! text-sm! text-slate-400!">
          🔍
        </span>
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="searchPlaceholder"
          class="pl-9! pr-4! py-2! bg-slate-50! border! border-slate-200! rounded-lg! text-sm! text-slate-900! w-56! hover:bg-white! hover:border-slate-300! focus:border-cyan-600! focus:bg-white! focus:outline-none! transition-all!"
        />
      </div>
    </div>

    <div :class="props.noCard ? 'overflow-x-auto! flex-1!' : 'overflow-x-auto!'">
      <table class="w-full! text-left!">
        <thead class="bg-slate-50! border-b! border-slate-200!">
          <tr>
            <th
              v-for="column in columns"
              :key="column.key"
              class="px-4! py-2.5! text-xs! font-bold! text-slate-300! uppercase! tracking-wider! text-left! whitespace-nowrap!"
            >
              {{ column.title }}
            </th>
          </tr>
        </thead>

        <tbody class="divide-y! divide-slate-100!">
          <tr v-if="data.length === 0">
            <td :colspan="columns.length" class="px-4! py-8! text-center! text-sm! text-slate-400!">
              Tidak ada data ditemukan.
            </td>
          </tr>

          <tr
            v-else
            v-for="(row, index) in paginatedData"
            :key="row.id || index"
            class="hover:bg-slate-50! transition-colors!"
            :class="{ 'cursor-pointer!': props.rowClickable }"
            @click="props.rowClickable ? emit('row-click', row) : null"
          >
            <td
              v-for="column in columns"
              :key="column.key"
              class="px-4! py-2! border-b! border-slate-100! align-middle!"
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
          ? 'flex! items-center! justify-between! p-4! border-t! border-slate-100!'
          : 'flex! items-center! justify-between! p-4! border-t! border-slate-100! bg-slate-50!'
      "
    >
      <div class="text-sm! text-slate-500! font-medium!">
        Showing {{ showingInfo.start }} to {{ showingInfo.end }} of
        <strong class="font-bold! text-slate-900!">{{ showingInfo.total }}</strong> entries
      </div>

      <div class="flex! items-center! gap-1.5!">
        <BaseButton
          variant="ghost"
          size="sm"
          :disabled="currentPage === 1"
          @click="$emit('prev-page')"
          class="w-8! h-8! p-0! rounded-lg! border! border-slate-200! bg-white!"
        >
          ‹
        </BaseButton>

        <BaseButton
          v-for="page in visiblePages"
          :key="page"
          variant="ghost"
          size="sm"
          @click="$emit('go-to-page', page)"
          class="min-w-[32px]! h-8! rounded-lg! border! px-2! shadow-sm!"
          :class="
            page === currentPage
              ? 'bg-blue-50! border-blue-200! text-blue-600! font-bold!'
              : 'border-slate-200! bg-white! text-slate-600!'
          "
        >
          {{ page }}
        </BaseButton>

        <BaseButton
          variant="ghost"
          size="sm"
          :disabled="currentPage === totalPages"
          @click="$emit('next-page')"
          class="w-8! h-8! p-0! rounded-lg! border! border-slate-200! bg-white!"
        >
          ›
        </BaseButton>
      </div>
    </div>
  </div>
</template>
<script setup>
import { computed } from 'vue'
import BaseButton from './BaseButton.vue'

const props = defineProps({
  data: {
    type: Array,
    default: () => [],
  },
  columns: {
    type: Array,
    required: true,
  },
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
  modelValue: {
    type: String,
    default: '',
  },
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
  rowClickable: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'prev-page', 'next-page', 'go-to-page', 'row-click'])

const searchQuery = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const paginatedData = computed(() => {
  const start = (props.currentPage - 1) * props.perPage
  const end = start + props.perPage
  return props.data.slice(start, end)
})

const showingInfo = computed(() => {
  const start = props.totalEntries === 0 ? 0 : (props.currentPage - 1) * props.perPage + 1
  const end = Math.min(props.currentPage * props.perPage, props.totalEntries)
  return {
    start,
    end,
    total: props.totalEntries,
  }
})

const getNestedValue = (obj, path) => {
  return path.split('.').reduce((current, key) => {
    return current && current[key] !== undefined ? current[key] : ''
  }, obj)
}
</script>
