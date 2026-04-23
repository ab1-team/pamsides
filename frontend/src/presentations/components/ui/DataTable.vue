<template>
  <div
    :class="
      props.noCard
        ? 'h-full! flex! flex-col!'
        : 'bg-white! rounded-xl! shadow-sm! overflow-hidden! transition-all!'
    "
  >
    <div
      v-if="showToolbar"
      class="flex! flex-col! sm:flex-row! sm:items-center! justify-between! p-3! border-b! border-slate-100! gap-4!"
    >
      <div class="flex! items-center! gap-4!">
        <span v-if="title" class="text-sm! font-semibold! text-slate-900!">{{ title }}</span>

        <!-- Tampilkan Entri -->
        <div v-if="showEntries" class="flex! items-center! gap-2! text-xs! text-slate-500!">
          <span class="whitespace-nowrap!">Show</span>
          <select
            :value="effectivePerPage"
            @change="effectivePerPage = parseInt($event.target.value)"
            class="bg-white! border! border-slate-200! rounded-md! px-2! py-1! outline-none! focus:border-cyan-600! transition-all! cursor-pointer! text-slate-700!"
          >
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
          <span class="whitespace-nowrap!">entries</span>
        </div>

        <slot name="toolbar-actions"></slot>
      </div>

      <div class="relative! w-full! sm:w-auto!">
        <span class="absolute! left-3.5! top-1/2! -translate-y-1/2! text-sm! text-slate-400!">
          🔍
        </span>
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="searchPlaceholder"
          class="pl-9! pr-4! py-2! bg-slate-50! border! border-slate-200! rounded-lg! text-sm! text-slate-900! w-full! sm:w-56! hover:bg-white! hover:border-slate-300! focus:border-cyan-600! focus:bg-white! focus:outline-none! transition-all!"
        />
      </div>
    </div>

    <div :class="props.noCard ? 'overflow-x-auto! flex-1!' : 'overflow-x-auto!'">
      <table class="w-full! text-left!">
        <thead class="bg-slate-50! border-b! border-slate-200!">
          <tr>
            <!-- Checkbox Header -->
            <th v-if="selectable" class="px-4! py-2! w-10!">
              <input
                type="checkbox"
                :checked="isAllSelected"
                @change="toggleSelectAll"
                class="w-4! h-4! rounded! border-slate-300! text-blue-600! focus:ring-blue-500!"
              />
            </th>
            <th
              v-for="column in columns"
              :key="column.key"
              class="px-4! py-2! text-xs! font-bold! text-slate-400! uppercase! tracking-wider! text-left! whitespace-nowrap!"
              :class="column.thClass"
            >
              {{ column.title }}
            </th>
          </tr>
        </thead>

        <tbody class="divide-y! divide-slate-100!">
          <tr v-if="data.length === 0">
            <td :colspan="selectable ? columns.length + 1 : columns.length" class="px-4! py-8!">
              <EmptyState :title="emptyTitle" :message="emptyMessage" :icon="emptyIcon" />
            </td>
          </tr>

          <tr
            v-else
            v-for="(row, index) in paginatedData"
            :key="row.id || index"
            class="hover:bg-slate-50! transition-colors!"
            :class="{
              'cursor-pointer!': props.rowClickable,
              'bg-blue-50/50!': isSelected(row),
            }"
            @click="props.rowClickable ? emit('row-click', row) : null"
          >
            <!-- Checkbox Cell -->
            <td v-if="selectable" class="px-4! py-2! w-10!" @click.stop>
              <input
                type="checkbox"
                :checked="isSelected(row)"
                @change="toggleSelect(row)"
                class="w-4! h-4! rounded! border-slate-300! text-blue-600! focus:ring-blue-500!"
              />
            </td>
            <td
              v-for="column in columns"
              :key="column.key"
              class="px-4! py-1.5! border-b! border-slate-100! align-middle! text-[13px]! text-slate-600!"
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

    <!-- Bulk Action Overlay -->
    <Transition name="fade">
      <div
        v-if="selectable && selection.length > 0"
        class="fixed! bottom-8! left-1/2! -translate-x-1/2! bg-slate-900! text-white! px-6! py-4! rounded-2xl! shadow-2xl! flex! items-center! gap-8! z-50! border! border-white/10!"
      >
        <div class="flex! items-center! gap-3!">
          <div
            class="w-8! h-8! bg-blue-600! rounded-full! flex! items-center! justify-center! font-bold! text-xs!"
          >
            {{ selection.length }}
          </div>
          <span class="text-sm! font-bold!">Data terpilih</span>
        </div>
        <div class="h-6! w-[1px]! bg-white/10!"></div>
        <div class="flex! items-center! gap-4!">
          <slot name="bulk-actions" :selection="selection">
            <!-- Default bulk actions if none provided -->
          </slot>
          <button
            @click="clearSelection"
            class="text-xs! font-bold! text-slate-400! hover:text-white! transition-colors!"
          >
            Batal
          </button>
        </div>
      </div>
    </Transition>

    <div
      v-if="showPagination"
      :class="
        props.noCard
          ? 'flex! flex-col! sm:flex-row! sm:items-center! justify-between! p-3! border-t! border-slate-100! gap-4!'
          : 'flex! flex-col! sm:flex-row! sm:items-center! justify-between! p-3! border-t! border-slate-100! bg-slate-50! gap-4!'
      "
    >
      <div class="text-xs md:text-sm! text-slate-500! font-medium! text-center! sm:text-left!">
        Showing {{ showingInfo.start }} to {{ showingInfo.end }} of
        <strong class="font-bold! text-slate-900!">{{ showingInfo.total }}</strong> entries
      </div>

      <div class="flex! items-center! justify-center! sm:justify-end! gap-1!">
        <BaseButton
          variant="ghost"
          size="sm"
          :disabled="effectiveCurrentPage === 1"
          @click="handlePrevPage"
          class="w-8! h-8! p-0! rounded-lg! border! border-slate-200! bg-white!"
        >
          ‹
        </BaseButton>

        <BaseButton
          v-for="page in effectiveVisiblePages"
          :key="page"
          variant="ghost"
          size="sm"
          @click="handleGoToPage(page)"
          class="min-w-[32px]! h-8! rounded-lg! border! px-1 md:px-2! shadow-sm!"
          :class="
            page === effectiveCurrentPage
              ? 'bg-blue-50! border-blue-200! text-blue-600! font-bold!'
              : 'border-slate-200! bg-white! text-slate-600!'
          "
        >
          {{ page }}
        </BaseButton>

        <BaseButton
          variant="ghost"
          size="sm"
          :disabled="effectiveCurrentPage === effectiveTotalPages"
          @click="handleNextPage"
          class="w-8! h-8! p-0! rounded-lg! border! border-slate-200! bg-white!"
        >
          ›
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import BaseButton from './BaseButton.vue'
import EmptyState from './EmptyState.vue'

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
    default: undefined,
  },
  perPage: {
    type: Number,
    default: undefined,
  },
  totalPages: {
    type: Number,
    default: undefined,
  },
  visiblePages: {
    type: Array,
    default: undefined,
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
  showEntries: {
    type: Boolean,
    default: true,
  },
  selectable: {
    type: Boolean,
    default: false,
  },
  selection: {
    type: Array,
    default: () => [],
  },
  emptyTitle: String,
  emptyMessage: String,
  emptyIcon: String,
})

const emit = defineEmits([
  'update:modelValue',
  'prev-page',
  'next-page',
  'go-to-page',
  'row-click',
  'update:currentPage',
  'update:perPage',
  'update:per-page',
  'update:selection',
])

// Selection Logic
const isSelected = (row) => props.selection.some((item) => item.id === row.id)
const isAllSelected = computed(() => {
  return props.data.length > 0 && props.selection.length === props.data.length
})

const toggleSelect = (row) => {
  let newSelection = [...props.selection]
  const index = newSelection.findIndex((item) => item.id === row.id)

  if (index > -1) {
    newSelection.splice(index, 1)
  } else {
    newSelection.push(row)
  }
  emit('update:selection', newSelection)
}

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    emit('update:selection', [])
  } else {
    emit('update:selection', [...props.data])
  }
}

const clearSelection = () => {
  emit('update:selection', [])
}

// Internal state for when parent doesn't provide v-model
const internalCurrentPage = ref(1)
const internalPerPage = ref(10)

const effectiveCurrentPage = computed({
  get: () => (props.currentPage !== undefined ? props.currentPage : internalCurrentPage.value),
  set: (val) => {
    if (props.currentPage !== undefined) {
      emit('update:currentPage', val)
    } else {
      internalCurrentPage.value = val
    }
  },
})

const effectivePerPage = computed({
  get: () => (props.perPage !== undefined ? props.perPage : internalPerPage.value),
  set: (val) => {
    if (props.perPage !== undefined) {
      emit('update:perPage', val)
      emit('update:per-page', val)
    } else {
      internalPerPage.value = val
      internalCurrentPage.value = 1 // Reset to page 1 when perPage changes
    }
  },
})

const effectiveTotalPages = computed(() => {
  if (props.totalPages !== undefined) return props.totalPages
  return Math.max(1, Math.ceil(props.totalEntries / effectivePerPage.value))
})

const effectiveVisiblePages = computed(() => {
  if (props.visiblePages !== undefined) return props.visiblePages
  const pages = []
  const maxPages = 5
  let start = Math.max(1, effectiveCurrentPage.value - 2)
  let end = Math.min(effectiveTotalPages.value, start + maxPages - 1)

  if (end - start < maxPages - 1) {
    start = Math.max(1, end - maxPages + 1)
  }

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

const searchQuery = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const paginatedData = computed(() => {
  const start = (effectiveCurrentPage.value - 1) * effectivePerPage.value
  const end = start + effectivePerPage.value
  return props.data.slice(start, end)
})

const showingInfo = computed(() => {
  const start =
    props.totalEntries === 0 ? 0 : (effectiveCurrentPage.value - 1) * effectivePerPage.value + 1
  const end = Math.min(effectiveCurrentPage.value * effectivePerPage.value, props.totalEntries)
  return {
    start,
    end,
    total: props.totalEntries,
  }
})

const handlePrevPage = () => {
  if (effectiveCurrentPage.value > 1) {
    effectiveCurrentPage.value--
    emit('prev-page')
  }
}

const handleNextPage = () => {
  if (effectiveCurrentPage.value < effectiveTotalPages.value) {
    effectiveCurrentPage.value++
    emit('next-page')
  }
}

const handleGoToPage = (page) => {
  effectiveCurrentPage.value = page
  emit('go-to-page', page)
}

const getNestedValue = (obj, path) => {
  return path.split('.').reduce((current, key) => {
    return current && current[key] !== undefined ? current[key] : ''
  }, obj)
}
</script>
