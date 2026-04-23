<template>
  <div class="cater-root">
    <DataTable
      :data="filteredData"
      :columns="tableColumns"
      title=""
      v-model:current-page="currentPage"
      v-model:per-page="perPage"
      :total-pages="totalPages"
      :visible-pages="visiblePages"
      :total-entries="tableData.length"
      v-model="searchQuery"
      class="mt-6!"
      search-placeholder="Cari cater (Nama, Alamat, Username)..."
    >
      <template #column-aksi="{ row }">
        <div class="flex items-center gap-2!">
          <BaseButton
            variant="ghost"
            size="sm"
            @click="handleEdit(row)"
            class="w-8! h-8! p-0! rounded-lg! border! border-slate-100! hover:border-blue-200! hover:bg-blue-50! text-slate-600! hover:text-blue-600! shadow-sm!"
            title="Edit"
            icon="edit"
          />
          <BaseButton
            variant="ghost"
            size="sm"
            @click="handleDelete(row)"
            class="w-8! h-8! p-0! rounded-lg! border! border-slate-100! hover:border-red-200! hover:bg-red-50! text-slate-600! hover:text-red-600! shadow-sm!"
            title="Delete"
            icon="trash"
          />
        </div>
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { useCater } from '@/composables/useCater'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'

const {
  searchQuery,
  currentPage,
  tableData,
  filteredData,
  totalPages,
  visiblePages,
  handleEdit,
  handleDelete,
} = useCater()

const tableColumns = [
  {
    key: 'nama',
    title: 'NAMA',
    tdClass: 'font-semibold! text-slate-900!',
  },
  {
    key: 'alamat',
    title: 'ALAMAT',
    tdClass: 'text-slate-600!',
  },
  {
    key: 'telepon',
    title: 'TELEPON',
    tdClass: 'text-slate-600!',
  },
  {
    key: 'username',
    title: 'USERNAME',
    tdClass: 'font-medium text-slate-900',
  },
  {
    key: 'aksi',
    title: 'AKSI',
    tdClass: '',
  },
]
</script>
