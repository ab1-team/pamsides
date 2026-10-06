<template>
  <div class="data-instalasi-root">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4!">
      <div class="flex-1!">
        <h1 class="text-2xl font-bold text-cyan-600! tracking-tight mb-1!">Data Instalasi</h1>
        <p class="text-sm text-slate-500! leading-relaxed">
          Daftar seluruh instalasi pelanggan beserta statusnya. Klik baris untuk melihat
          riwayat &amp; memperbarui paket pelanggan.
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
      :total-entries="filteredData.length"
      v-model="searchQuery"
      class="mt-6!"
      search-placeholder="Cari pelanggan..."
      empty-title="Data Instalasi Tidak Ditemukan"
      empty-message="Belum ada instalasi yang tercatat atau kata kunci pencarian tidak cocok."
      empty-icon="tools"
      :row-clickable="true"
      @row-click="handleShowDetail"
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

      <template #column-paket="{ row }">
        <span
          class="inline-flex! items-center! px-2! py-0.5! rounded-md! text-[11px]! font-bold! tracking-wider! bg-indigo-50! text-indigo-700! border! border-indigo-100! whitespace-nowrap!"
        >
          {{ row.paket }}
        </span>
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

      <template #column-aksi="{ row }">
        <button
          @click.stop="openUpdatePackage(row)"
          class="inline-flex! items-center! gap-1.5! px-3! py-1.5! rounded-lg! text-[11px]! font-bold! tracking-wider! uppercase! bg-cyan-600! hover:bg-cyan-700! text-white! border! border-cyan-700! shadow-sm! transition-all! hover:shadow-md! whitespace-nowrap!"
          title="Ubah paket pelanggan"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-5 5v8a2 2 0 002 2h11a5 5 0 005-5v-1M18.478 14v-2m0 0h-3m3 0-3-3m-3 9v-3m0 0h3m-3 0 3 3" />
          </svg>
          Ubah Paket
        </button>
      </template>
    </DataTable>

    <!-- ==================== POPUP DETAIL PAKET & TAGIHAN ==================== -->
    <PackageDetailModal
      :show="!!detailData || isDetailLoading"
      :loading="isDetailLoading"
      :data="detailData"
      :format-rupiah="formatRupiah"
      :format-date-time="formatDateTime"
      @close="closeDetail"
      @open-update="openUpdatePackageFromDetail"
    />

    <!-- ==================== MODAL UPDATE PAKET ==================== -->
    <PackageUpdateModal
      :show="!!updateForm.ticketId"
      :form="updateForm"
      :packages="packages"
      :loading="isUpdateSubmitting"
      :format-rupiah="formatRupiah"
      @close="closeUpdatePackage"
      @submit="submitUpdatePackage"
    />
  </div>
</template>

<script setup>
import { useDataInstalasi } from '@/composables/useDataInstalasi'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import PackageDetailModal from './partials/PackageDetailModal.vue'
import PackageUpdateModal from './partials/PackageUpdateModal.vue'

const {
  searchQuery,
  currentPage,
  perPage,
  filteredData,
  isLoading,
  STATUS_COLORS,
  fetchData,
  handleCetakDataInstalasi,

  // state paket
  detailData,
  updateForm,
  packages,
  isDetailLoading,
  isUpdateSubmitting,
  handleShowDetail,
  closeDetail,
  openUpdatePackage,
  closeUpdatePackage,
  submitUpdatePackage,
  formatRupiah,
  formatDateTime,
} = useDataInstalasi()

/** Handler klik tombol "Ubah Paket" dari dalam popup detail. */
const openUpdatePackageFromDetail = (row) => {
  if (!row?.ticketId && detailData.value?.ticket?.id) {
    row = { ticketId: detailData.value.ticket.id }
  }
  closeDetail()
  openUpdatePackage(row)
}

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
    key: 'paket',
    title: 'PAKET',
    tdClass: 'whitespace-nowrap!',
  },
  {
    key: 'status',
    title: 'STATUS',
    tdClass: 'whitespace-nowrap!',
  },
  {
    key: 'aksi',
    title: 'AKSI',
    tdClass: 'whitespace-nowrap!',
    sortable: false,
  },
]
</script>