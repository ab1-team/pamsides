<template>
  <div class="pelanggan-root relative">
    <!-- Loading Overlay -->
    <div v-if="isLoading" class="absolute inset-0 z-50 flex items-center justify-center bg-white/60 backdrop-blur-sm rounded-3xl">
      <div class="flex flex-col items-center gap-4">
        <font-awesome-icon icon="spinner" class="text-blue-500 text-4xl animate-spin" />
        <span class="text-slate-500 font-medium">Memuat data...</span>
      </div>
    </div>
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
      search-placeholder="Cari pelanggan..."
      empty-title="Pelanggan Tidak Ditemukan"
      empty-message="Mohon cek kembali kata kunci pencarian Anda atau tambahkan pelanggan baru."
      empty-icon="users-slash"
    >
      <template #search-actions>
        <BaseButton
          variant="ghost"
          size="sm"
          @click="fetchCustomers"
          :loading="isLoading"
          class="w-9! h-9! p-0! rounded-lg! border! border-slate-200! hover:border-blue-200! hover:bg-blue-50! text-slate-500! hover:text-blue-600! transition-all!"
          title="Muat Ulang Data"
          icon="sync-alt"
        />
      </template>

      <template #column-nama="{ row }">
        <div class="flex items-center gap-3!">
          <div
            class="w-9! h-9! rounded-full! flex items-center justify-center text-white! text-xs! font-bold! shrink-0!"
            :style="{ background: row.avatarColor }"
          >
            {{ row.initials }}
          </div>

          <div>
            <div class="font-semibold! text-sm! text-slate-900! mb-0.5!">
              {{ row.nama }}
            </div>
            <div class="text-xs! text-slate-400! font-normal!">ID: {{ row.id }}</div>
          </div>
        </div>
      </template>

      <template #column-status="{ row }">
        <span
          :class="[
            'inline-flex! items-center! gap-1! px-2! py-0.5! rounded-md! text-[10px]! font-bold! tracking-wider! uppercase! whitespace-nowrap!',
            STATUS_COLORS[row.status] || '',
          ]"
        >
          • {{ row.status }}
        </span>
      </template>

      <template #column-no_hp="{ row }">
        <div class="text-xs! font-medium! text-slate-600! whitespace-nowrap!">
          {{ row.no_hp }}
        </div>
      </template>

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
import { useRouter } from 'vue-router'
import { usePelanggan } from '@/composables/usePelanggan'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'

const router = useRouter()

const {
  searchQuery,
  currentPage,
  tableData,
  filteredData,
  isLoading,
  totalPages,
  visiblePages,
  STATUS_COLORS,
  handleEdit,
  handleDelete,
  fetchCustomers,
} = usePelanggan(router)

const tableColumns = [
  {
    key: 'nama',
    title: 'NAMA / NO. INDUK',
    tdClass: '',
  },
  {
    key: 'nik',
    title: 'NIK',
    tdClass: '',
  },
  {
    key: 'alamat',
    title: 'ALAMAT',
    tdClass: '',
  },
  {
    key: 'no_hp',
    title: 'NO. HP',
    tdClass: 'font-medium',
  },
  {
    key: 'status',
    title: 'STATUS',
    tdClass: '',
  },
  {
    key: 'aksi',
    title: 'AKSI',
    tdClass: '',
  },
]
</script>
