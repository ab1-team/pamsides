<template>
  <div class="data-instalasi-root">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4!">
      <div class="flex-1!">
        <h1 class="text-2xl font-bold text-cyan-600! tracking-tight mb-1!">Data Instalasi</h1>
        <p class="text-sm text-slate-500! leading-relaxed">
          Daftar seluruh instalasi pelanggan beserta statusnya.
        </p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 lg:flex lg:flex-wrap gap-3! w-full lg:w-auto!">
        <BaseButton
          variant="warning-gradient"
          size="md"
          @click="handleCetakDataInstalasi"
          :disabled="isLoading"
          class="w-full! lg:w-auto! rounded-xl! shadow-lg! shadow-amber-200/50!"
          icon="print"
        >
          Cetak Data Instalasi
        </BaseButton>
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
      v-model="searchQuery"
      :total-entries="tableData.length"
      class="mt-6!"
      search-placeholder="Cari pelanggan..."
      empty-title="Data Instalasi Tidak Ditemukan"
      empty-message="Belum ada instalasi yang tercatat atau kata kunci pencarian tidak cocok."
      empty-icon="tools"
    >
      <template #search-actions>
        <BaseButton
          variant="ghost"
          size="sm"
          @click="fetchData"
          :loading="isLoading"
          class="w-9! h-9! p-0! rounded-lg! border! border-slate-200! hover:border-blue-200! hover:bg-blue-50! text-slate-500! hover:text-blue-600! transition-all!"
          title="Muat Ulang Data"
          icon="sync-alt"
        />
      </template>

      <template #column-kodeInstalasi="{ row }">
        <span
          class="inline-flex! items-center! px-2! py-0.5! rounded-md! text-[11px]! font-bold! tracking-wider! bg-cyan-50! text-cyan-700! border! border-cyan-100! font-mono! whitespace-nowrap!"
        >
          {{ row.kodeInstalasi }}
        </span>
      </template>

      <template #column-nama="{ row }">
        <div class="font-semibold! text-[13px]! text-slate-900!">
          {{ row.nama }}
        </div>
      </template>

      <template #column-alamat="{ row }">
        <div class="text-[13px]! text-slate-600! leading-relaxed!">
          {{ row.alamat }}
        </div>
      </template>

      <template #column-status="{ row }">
        <span
          :class="[
            'inline-flex! items-center! gap-1! px-2! py-0.5! rounded-md! text-[10px]! font-bold! tracking-wider! uppercase! whitespace-nowrap!',
            STATUS_COLORS[row.rawStatus] || 'bg-slate-100 text-slate-700',
          ]"
        >
          • {{ row.status }}
        </span>
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { useDataInstalasi } from '@/composables/useDataInstalasi'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'

const {
  searchQuery,
  currentPage,
  perPage,
  tableData,
  filteredData,
  isLoading,
  totalPages,
  visiblePages,
  STATUS_COLORS,
  fetchData,
  handleCetakDataInstalasi,
} = useDataInstalasi()

const tableColumns = [
  {
    key: 'kodeInstalasi',
    title: 'KODE INSTALASI',
    tdClass: 'whitespace-nowrap!',
  },
  {
    key: 'nama',
    title: 'NAMA PELANGGAN',
    tdClass: '',
  },
  {
    key: 'alamat',
    title: 'ALAMAT',
    tdClass: '',
  },
  {
    key: 'status',
    title: 'STATUS',
    tdClass: 'whitespace-nowrap!',
  },
]
</script>
