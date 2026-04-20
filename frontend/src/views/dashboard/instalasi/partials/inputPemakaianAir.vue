<template>
  <div class="input-pemakaian-air-root !p-4 !lg:p-6">
    <div class="!flex !flex-col !md:flex-row !md:items-center !justify-between !gap-4 !mb-6">
      <div class="!flex !items-center !gap-3 md:!gap-4">
        <BaseButton
          variant="ghost"
          icon="chevron-left"
          class="!w-8 !h-8 md:!w-10 md:!h-10 !p-0 !rounded-full !bg-white !shadow-sm"
          @click="$router.push('/instalasi/pemakaian-air')"
        />
        <div>
          <h1 class="!text-xl md:!text-2xl !font-extrabold !text-slate-800 !tracking-tight">
            Input Pemakaian Air
          </h1>
          <p class="!text-xs md:!text-sm !text-slate-500">
            Silakan input meter akhir pelanggan pada form di bawah ini
          </p>
        </div>
      </div>
    </div>

    <DataTable
      :data="filteredData"
      :columns="tableColumns"
      title=""
      :current-page="currentPage"
      :per-page="perPage"
      :total-pages="totalPages"
      :visible-pages="visiblePages"
      :total-entries="filteredData.length"
      v-model="searchQuery"
      @prev-page="currentPage--"
      @next-page="currentPage++"
      @go-to-page="currentPage = $event"
      @row-click="handleRowClick"
      search-placeholder="Cari Pelanggan..."
    >
      <template #toolbar-actions>
        <div class="!flex !items-center !gap-2 !text-xs md:!text-sm !text-slate-600">
          <span>Show</span>
          <select
            v-model="perPage"
            class="!border !border-slate-300 !rounded !px-2 !py-1 !focus:outline-none !focus:border-cyan-500 !bg-white"
          >
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </select>
          <span>entries</span>
        </div>
      </template>

      <template #column-meterAwal="{ row }">
        <span class="!text-slate-500">{{ row.meterAwal }}</span>
      </template>

      <template #column-meterAkhir="{ row }">
        <input
          type="text"
          inputmode="numeric"
          v-model.number="row.meterAkhir"
          class="!w-16 md:!w-20 !text-right !bg-transparent !outline-none !border-b !border-transparent !focus:border-cyan-500 !hover:border-slate-300 !transition-colors !font-semibold"
          :class="row.meterAkhir === row.meterAwal ? '!text-red-500' : '!text-orange-500'"
        />
      </template>

      <template #column-pemakaian="{ row }">
        <span class="!text-slate-500">{{ hitungPemakaian(row) }}</span>
      </template>
    </DataTable>

    <InputMeterModal
      :show="isModalOpen"
      :customer="selectedCustomer"
      @close="isModalOpen = false"
      @save="handleSaveMeter"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import DataTable from '@/components/ui/DataTable.vue'
import InputMeterModal from './InputMeterModal.vue'

const isModalOpen = ref(false)
const selectedCustomer = ref({})

const perPage = ref(10)
const searchQuery = ref('')
const currentPage = ref(1)

watch(perPage, () => {
  currentPage.value = 1
})

const handleRowClick = (row) => {
  selectedCustomer.value = { ...row }
  isModalOpen.value = true
}

const handleSaveMeter = (newMeterAkhir) => {
  const index = dummyData.value.findIndex((c) => c.noInduk === selectedCustomer.value.noInduk)
  if (index !== -1) {
    dummyData.value[index].meterAkhir = newMeterAkhir
  }
}

const tableColumns = [
  { key: 'nama', title: 'NAMA', tdClass: '!font-medium !text-slate-500' },
  { key: 'dusun', title: 'DUSUN', tdClass: '!text-slate-500' },
  { key: 'rt', title: 'RT', tdClass: '!text-slate-500' },
  { key: 'noInduk', title: 'NO.INDUK', tdClass: '!text-slate-500' },
  { key: 'meterAwal', title: 'METER AWAL', tdClass: '!text-right !w-24' },
  { key: 'meterAkhir', title: 'METER AKHIR', tdClass: '!text-right !w-32' },
  { key: 'pemakaian', title: 'PEMAKAIAN', tdClass: '!text-right !w-24' },
]

const dummyData = ref([
  {
    nama: 'Adi Sawal',
    dusun: 'Mulo',
    rt: '-',
    noInduk: '2.03.0116.W',
    meterAwal: 584,
    meterAkhir: 584,
  },
  {
    nama: 'Agung/yuni',
    dusun: 'Mulo',
    rt: '-',
    noInduk: '2.8.121405.W',
    meterAwal: 346,
    meterAkhir: 346,
  },
  {
    nama: 'Agus Setyawan',
    dusun: 'Kamal',
    rt: '-',
    noInduk: '4.12.0946.W',
    meterAwal: 921,
    meterAkhir: 921,
  },
  {
    nama: 'Agus Sukiyanto',
    dusun: 'Mulo',
    rt: '-',
    noInduk: '2.03.0124.W',
    meterAwal: 3941,
    meterAkhir: 3941,
  },
  {
    nama: 'Anis Suprihatin',
    dusun: 'Karangasem',
    rt: '-',
    noInduk: '3.6.121383.W',
    meterAwal: 14,
    meterAkhir: 14,
  },
  {
    nama: 'Arjo Senu/wastini',
    dusun: 'Mulo',
    rt: '-',
    noInduk: '2.07.0206.W',
    meterAwal: 1455,
    meterAkhir: 1455,
  },
])

const hitungPemakaian = (row) => {
  return Math.max(0, row.meterAkhir - row.meterAwal)
}

const filteredData = computed(() => {
  if (!searchQuery.value) return dummyData.value
  const q = searchQuery.value.toLowerCase()
  return dummyData.value.filter((item) => {
    return (
      item.nama.toLowerCase().includes(q) ||
      item.noInduk.toLowerCase().includes(q) ||
      item.dusun.toLowerCase().includes(q)
    )
  })
})

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(filteredData.value.length / perPage.value))
})

const visiblePages = computed(() => {
  const pages = []
  for (let i = 1; i <= Math.min(3, totalPages.value); i++) {
    pages.push(i)
  }
  return pages
})
</script>

<style scoped>
input[type='text']::-webkit-inner-spin-button,
input[type='text']::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type='text'] {
  -moz-appearance: textfield;
}
</style>
