<template>
  <div class="pemakaian-air-root">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4!">
      <div class="flex-1!">
        <h1 class="text-2xl font-bold text-cyan-600! tracking-tight mb-1!">Data Instalasi</h1>
        <p class="text-sm text-slate-500! leading-relaxed">
          Manage and monitor regional water consumption cycles.
        </p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 lg:flex lg:flex-wrap gap-3! w-full lg:w-auto!">
        <BaseButton
          variant="warning-gradient"
          size="md"
          @click="handleCetakFormInput"
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
      :current-page="currentPage"
      :total-pages="totalPages"
      :visible-pages="visiblePages"
      v-model="searchQuery"
      @prev-page="currentPage--"
      @next-page="currentPage++"
      @go-to-page="currentPage = $event"
      class="mt-6!"
    >
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

      <template #column-meterRange="{ row }">
        <div class="flex items-center gap-2!">
          <span class="text-sm! font-medium! text-slate-500!">
            {{ row.meterAwal.toLocaleString('id-ID') }}
          </span>
          <span class="text-sm! text-slate-300!">→</span>
          <span class="text-sm! font-semibold! text-cyan-600!">
            {{ row.meterAkhir.toLocaleString('id-ID') }}
          </span>
        </div>
      </template>

      <template #column-pemakaian="{ row }">
        <span class="text-sm! font-semibold! text-slate-900!"> {{ row.pemakaian }} m³ </span>
      </template>

      <template #column-tagihan="{ row }">
        <div class="font-bold! text-sm! text-slate-900!">
          {{ row.tagihan.toLocaleString('id-ID') }}
        </div>
      </template>

      <template #column-jatuhTempo="{ row }">
        <span class="text-sm! text-slate-500! font-medium!">
          {{ row.jatuhTempo }}
        </span>
      </template>

      <template #column-status="{ row }">
        <span
          :class="[
            'inline-flex! items-center! gap-1! px-2! py-0.5! rounded-md! text-sm! font-semibold! tracking-wide!',
            STATUS_COLORS[row.status] || '',
          ]"
        >
          • {{ row.status }}
        </span>
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
import { usePemakaianAir } from '@/composables/usePemakaianAir'
import DataTable from '@/components/ui/DataTable.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const {
  searchQuery,
  currentPage,
  filteredData,
  totalPages,
  visiblePages,
  STATUS_COLORS,
  handleCetakFormInput,
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
]
</script>
