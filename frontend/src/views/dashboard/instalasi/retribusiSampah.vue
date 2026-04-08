<template>
  <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
    <div class="flex-1">
      <h1 class="text-2xl font-bold text-cyan-600 tracking-tight mb-1">Usage Data Pemakaian</h1>
      <p class="text-sm text-slate-500 leading-relaxed">
        Manage and monitor regional water consumption cycles.
      </p>
    </div>
    <div class="flex gap-2">
      <button
        @click="handleCetakFormInput"
        class="px-3 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg text-sm font-semibold hover:from-blue-700 hover:to-blue-800 transition-all shadow-md"
      >
        Cetak Form Input
      </button>
      <button
        @click="handleHasilInput"
        class="px-3 py-2 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white rounded-lg text-sm font-semibold hover:from-emerald-700 hover:to-emerald-800 transition-all shadow-md"
      >
        Hasil Input
      </button>
      <button
        @click="handleInputPemakaian"
        class="px-3 py-2 bg-gradient-to-r from-orange-600 to-orange-700 text-white rounded-lg text-sm font-semibold hover:from-orange-700 hover:to-orange-800 transition-all shadow-md"
      >
        Input Pemakaian
      </button>
    </div>
  </div>

  <ContentCard variant="elevated" padding="normal" hoverable>
    <div class="flex flex-col lg:flex-row lg:items-end gap-4">
      <div class="flex-1 min-w-[150px]">
        <SelectSearch
          v-model="filter.tahun"
          :options="tahunOptions.map((y) => ({ id: y, text: y }))"
          label="TAHUN PEMAKAIAN"
          placeholder="Pilih Tahun"
        />
      </div>
      <div class="flex-1 min-w-[150px]">
        <SelectSearch
          v-model="filter.bulan"
          :options="bulanOptions.map((b) => ({ id: b, text: b }))"
          label="BULAN PEMAKAIAN"
          placeholder="Pilih Bulan"
        />
      </div>
      <div class="flex-1 min-w-[150px]">
        <SelectSearch
          v-model="filter.cater"
          :options="[
            { id: '', text: 'Semua Cater' },
            { id: 'cater-001', text: 'Cater-001' },
            { id: 'cater-002', text: 'Cater-002' },
            { id: 'cater-003', text: 'Cater-003' },
          ]"
          label="CATER"
          placeholder="Pilih Cater"
        />
      </div>
      <button
        @click="handleApplyFilter"
        class="flex items-center gap-2 px-6 py-2.5 bg-cyan-600 text-white rounded-lg text-sm font-semibold hover:bg-cyan-700 transition-colors shadow-md self-end"
      >
        <span class="text-xs">▼</span> Apply Filter
      </button>
    </div>
  </ContentCard>

  <DataTable
    :data="filteredData"
    :columns="tableColumns"
    title=""
    :current-page="currentPage"
    :total-pages="totalPages"
    :visible-pages="visiblePages"
    v-model:search-query="searchQuery"
    @prev-page="currentPage--"
    @next-page="currentPage++"
    @go-to-page="currentPage = $event"
  >
    <template #column-nama="{ row }">
      <div class="flex items-center gap-3">
        <div
          class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
          :style="{ background: row.avatarColor }"
        >
          {{ row.initials }}
        </div>
        <div>
          <div class="font-semibold text-sm text-slate-900 mb-0.5">{{ row.nama }}</div>
          <div class="text-xs text-slate-400 font-normal">ID: {{ row.id }}</div>
        </div>
      </div>
    </template>

    <template #column-meterRange="{ row }">
      <div class="flex items-center gap-2">
        <span class="text-sm font-medium text-slate-500">{{
          row.meterAwal.toLocaleString('id-ID')
        }}</span>
        <span class="text-sm text-slate-300">→</span>
        <span class="text-sm font-semibold text-cyan-600">{{
          row.meterAkhir.toLocaleString('id-ID')
        }}</span>
      </div>
    </template>

    <template #column-pemakaian="{ row }">
      <span class="text-sm font-semibold text-slate-900">{{ row.pemakaian }} m³</span>
    </template>

    <template #column-tagihan="{ row }">
      <div class="text-sm text-slate-400 mb-0.5">Rp</div>
      <div class="font-bold text-sm text-slate-900">
        {{ row.tagihan.toLocaleString('id-ID') }}
      </div>
    </template>

    <template #column-jatuhTempo="{ row }">
      <span class="text-sm text-slate-500 font-medium">{{ row.jatuhTempo }}</span>
    </template>

    <template #column-status="{ row }">
      <span
        :class="[
          'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-semibold tracking-wide',
          STATUS_COLORS[row.status] || '',
        ]"
      >
        • {{ row.status }}
      </span>
    </template>

    <template #column-aksi="{ row }">
      <div class="flex items-center gap-1.5">
        <button
          @click="handleEdit(row)"
          class="w-7 h-7 flex items-center justify-center rounded-md border border-slate-200 bg-white text-slate-600 hover:border-cyan-600 hover:text-cyan-600 hover:bg-cyan-50 transition-all"
          title="Edit"
        >
          ✏️
        </button>
        <button
          @click="handleDelete(row)"
          class="w-7 h-7 flex items-center justify-center rounded-md border border-slate-200 bg-white text-slate-600 hover:border-red-600 hover:text-red-600 hover:bg-red-50 transition-all"
          title="Delete"
        >
          🗑️
        </button>
      </div>
    </template>
  </DataTable>
</template>

<script setup>
import { usePemakaianAir } from '@/composables/usePemakaianAir'
import SelectSearch from '@/components/SelectSearch.vue'
import DataTable from '@/components/ui/DataTable.vue'
import ContentCard from '@/components/ui/ContentCard.vue'

const {
  filter,
  searchQuery,
  currentPage,
  tahunOptions,
  bulanOptions,
  filteredData,
  totalPages,
  visiblePages,
  STATUS_COLORS,
  handleApplyFilter,
  handleCetakFormInput,
  handleHasilInput,
  handleInputPemakaian,
  handleEdit,
  handleDelete,
} = usePemakaianAir()

const tableColumns = [
  {
    key: 'nama',
    title: 'NAMA / NO. INDUK',
    tdClass: '',
  },
  {
    key: 'meterRange',
    title: 'METER RANGE',
    tdClass: '',
  },
  {
    key: 'pemakaian',
    title: 'PEMAKAIAN',
    tdClass: '',
  },
  {
    key: 'tagihan',
    title: 'TAGIHAN',
    tdClass: 'font-medium',
  },
  {
    key: 'jatuhTempo',
    title: 'JATUH TEMPO',
    tdClass: '',
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
