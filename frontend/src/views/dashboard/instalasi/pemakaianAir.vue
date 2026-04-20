<template>
  <div class="pemakaian-air-root">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4!">
      <div class="flex-1!">
        <h1 class="text-xl md:text-2xl font-bold text-cyan-600! tracking-tight mb-1!">
          Data Pemakaian Air
        </h1>
        <p class="text-xs md:text-sm text-slate-500! leading-relaxed">
          Manage and monitor regional water consumption cycles and billing.
        </p>
      </div>

      <div
        class="grid grid-cols-1 sm:grid-cols-2 lg:flex lg:flex-wrap gap-2 md:gap-3! w-full lg:w-auto!"
      >
        <BaseButton
          variant="warning-gradient"
          size="md"
          @click="handleCetakFormInput"
          class="w-full! lg:w-auto! rounded-xl! shadow-lg! shadow-amber-200/50! text-xs md:text-sm"
          icon="print"
        >
          Cetak Form
        </BaseButton>

        <BaseButton
          variant="success-gradient"
          size="md"
          @click="handleHasilInput"
          class="w-full! lg:w-auto! rounded-xl! shadow-lg! shadow-emerald-200/50! text-xs md:text-sm"
          icon="file-alt"
        >
          Hasil Input
        </BaseButton>

        <BaseButton
          variant="primary-gradient"
          size="md"
          @click="handleInputPemakaian"
          class="w-full! lg:w-auto! rounded-xl! shadow-lg! shadow-indigo-200/50! text-xs md:text-sm sm:col-span-2 lg:col-span-1"
          icon="plus"
        >
          Input Pemakaian
        </BaseButton>
      </div>
    </div>

    <ContentCard variant="elevated" padding="normal" hoverable class="mt-4!">
      <div
        class="flex flex-col md:grid md:grid-cols-2 lg:flex lg:flex-row lg:items-end gap-3 md:gap-4!"
      >
        <div class="flex-1! min-w-[120px]!">
          <SelectSearch
            v-model="filter.tahun"
            :options="[{ id: '', text: 'Tahun' }, ...tahunOptions.map((y) => ({ id: y, text: y }))]"
            placeholder="Tahun"
            no-margin
          />
        </div>

        <div class="flex-1! min-w-[120px]!">
          <SelectSearch
            v-model="filter.bulan"
            :options="[{ id: '', text: 'Bulan' }, ...bulanOptions.map((b) => ({ id: b, text: b }))]"
            placeholder="Bulan"
            no-margin
          />
        </div>

        <div class="flex-1! min-w-[120px]!">
          <SelectSearch
            v-model="filter.cater"
            :options="[
              { id: '', text: 'Cater' },
              { id: 'cater-001', text: 'Cater-001' },
              { id: 'cater-002', text: 'Cater-002' },
              { id: 'cater-003', text: 'Cater-003' },
            ]"
            placeholder="Cater"
            no-margin
          />
        </div>

        <BaseButton
          variant="info-gradient"
          @click="handleApplyFilter"
          class="w-full! lg:w-auto! lg:min-w-[140px]! rounded-xl! shadow-md! lg:self-end! h-11! text-xs md:text-sm"
          icon="filter"
          icon-right
        >
          Filter
        </BaseButton>
      </div>
    </ContentCard>

    <DataTable
      :data="filteredData"
      :columns="tableColumns"
      :current-page="currentPage"
      :total-pages="totalPages"
      :visible-pages="visiblePages"
      :total-entries="tableData.length"
      v-model="searchQuery"
      @prev-page="currentPage--"
      @next-page="currentPage++"
      @go-to-page="currentPage = $event"
      class="mt-6!"
      search-placeholder="Cari..."
    >
      <template #column-nama="{ row }">
        <div class="flex items-center gap-2 md:gap-3!">
          <div
            class="w-8 md:w-9! h-8 md:h-9! rounded-full! flex items-center justify-center text-white! text-[10px] md:text-xs! font-bold! shrink-0!"
            :style="{ background: row.avatarColor }"
          >
            {{ row.initials }}
          </div>
          <div>
            <div class="font-semibold! text-xs md:text-sm! text-slate-900! mb-0.5!">
              {{ row.nama }}
            </div>
            <div class="text-[10px] md:text-xs! text-slate-400! font-normal!">ID: {{ row.id }}</div>
          </div>
        </div>
      </template>

      <template #column-meterRange="{ row }">
        <div class="flex items-center gap-1 md:gap-2!">
          <span class="text-xs md:text-sm! font-medium! text-slate-500!">
            {{ row.meterAwal.toLocaleString('id-ID') }}
          </span>
          <span class="text-xs md:text-sm! text-slate-300!">→</span>
          <span class="text-xs md:text-sm! font-semibold! text-cyan-600!">
            {{ row.meterAkhir.toLocaleString('id-ID') }}
          </span>
        </div>
      </template>

      <template #column-pemakaian="{ row }">
        <span class="text-xs md:text-sm! font-semibold! text-slate-900!">
          {{ row.pemakaian }} m³
        </span>
      </template>

      <template #column-tagihan="{ row }">
        <div class="font-bold! text-xs md:text-sm! text-slate-900!">
          {{ row.tagihan.toLocaleString('id-ID') }}
        </div>
      </template>

      <template #column-status="{ row }">
        <span
          :class="[
            'inline-flex! items-center! gap-1.5! px-2 md:px-3! py-1 md:py-1.5! rounded-md! text-[10px] md:text-sm! font-semibold! tracking-wide!',
            STATUS_COLORS[row.status] || '',
          ]"
        >
          {{ row.status }}
        </span>
      </template>

      <template #column-aksi="{ row }">
        <div class="flex items-center gap-1 md:gap-2!">
          <BaseButton
            variant="ghost"
            size="sm"
            @click="handleEdit(row)"
            class="w-7 md:w-8! h-7 md:h-8! p-0! rounded-lg! border! border-slate-100! text-slate-600!"
            icon="edit"
          />
          <BaseButton
            variant="ghost"
            size="sm"
            @click="handleDelete(row)"
            class="w-7 md:w-8! h-7 md:h-8! p-0! rounded-lg! border! border-slate-100! text-slate-600!"
            icon="trash"
          />
        </div>
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { usePemakaianAir } from '@/composables/usePemakaianAir'
import SelectSearch from '@/components/SelectSearch.vue'
import DataTable from '@/components/ui/DataTable.vue'
import ContentCard from '@/components/ui/ContentCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { useRouter } from 'vue-router'

const {
  filter,
  searchQuery,
  currentPage,
  tahunOptions,
  bulanOptions,
  tableData,
  filteredData,
  totalPages,
  visiblePages,
  STATUS_COLORS,
  handleApplyFilter,
  handleHasilInput,
  handleEdit,
  handleDelete,
} = usePemakaianAir()

const router = useRouter()

const handleInputPemakaian = () => {
  router.push({ name: 'Input Pemakaian Air' })
}

const handleCetakFormInput = () => {
  const url = router.resolve({ name: 'Cetak Input' }).href
  window.open(url, '_blank')
}

const tableColumns = [
  { key: 'nama', title: 'NAMA / NO. INDUK' },
  { key: 'meterRange', title: 'METER' },
  { key: 'pemakaian', title: 'PEMAKAIAN' },
  { key: 'tagihan', title: 'TAGIHAN' },
  { key: 'status', title: 'STATUS' },
  { key: 'aksi', title: 'AKSI' },
]
</script>

<style scoped>
.pemakaian-air-root {
  width: 100%;
}
</style>
