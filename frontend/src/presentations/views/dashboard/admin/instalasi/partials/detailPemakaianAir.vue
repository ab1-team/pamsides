<template>
  <div class="input-pemakaian-air-root p-4 lg:p-6 min-h-screen">
    <!-- View Desktop Header -->
    <div
      class="hidden! md:flex! flex-col! md:flex-row! md:items-center! justify-between! gap-4! mb-6!"
    >
      <div class="flex! items-center! gap-3! md:gap-4!">
        <BaseButton
          variant="ghost"
          icon="chevron-left"
          class="w-8! h-8! md:w-10! md:h-10! p-0! rounded-full! bg-white! shadow-sm!"
          @click="$router.push('/instalasi/pemakaian-air')"
        />
        <div>
          <h1 class="text-xl! md:text-2xl! font-extrabold! text-slate-800! tracking-tight!">
            Input Pemakaian Air
          </h1>
          <p class="text-xs! md:text-sm! text-slate-500!">
            Silakan input meter akhir pelanggan pada form di bawah ini
          </p>
        </div>
      </div>
    </div>

    <!-- View Desktop Table -->
    <div class="hidden! md:block!">
      <ContentCard variant="default" padding="none" hoverable class="overflow-hidden!">
        <DataTable
          :data="filteredData"
          :columns="tableColumns"
          title=""
          v-model:current-page="currentPage"
          v-model:per-page="perPage"
          :total-pages="totalPages"
          :visible-pages="visiblePages"
          :total-entries="filteredData.length"
          v-model="searchQuery"
          @row-click="handleRowClick"
          row-clickable
          search-placeholder="Cari Pelanggan..."
          :no-card="true"
        >
          <template #column-meterAwal="{ row }">
            <span class="text-slate-500!">{{ row.meterAwal }}</span>
          </template>

          <template #column-meterAkhir="{ row }">
            <input
              type="text"
              inputmode="numeric"
              v-model.number="row.meterAkhir"
              class="w-16! md:w-20! text-right! bg-transparent! outline-none! border-b! border-transparent! focus:border-cyan-500! hover:border-slate-300! transition-colors! font-semibold!"
              :class="row.meterAkhir === row.meterAwal ? 'text-red-500!' : 'text-orange-500!'"
            />
          </template>

          <template #column-pemakaian="{ row }">
            <span class="text-slate-500!">{{ hitungPemakaian(row) }}</span>
          </template>
        </DataTable>
      </ContentCard>
    </div>

    <!-- View Mobile Unified Card -->
    <div
      class="md:hidden! bg-white! rounded-[2.25rem]! border! border-slate-100! shadow-[0_20px_50px_rgba(0,0,0,0.04)]! overflow-hidden!"
    >
      <!-- Card Header Section -->
      <div class="p-6! border-b! border-slate-50!">
        <div class="flex! items-center! gap-3! mb-5!">
          <BaseButton
            variant="ghost"
            icon="chevron-left"
            class="w-8! h-8! p-0! rounded-full! bg-slate-100! text-slate-500!"
            @click="$router.push('/instalasi/pemakaian-air')"
          />
          <div>
            <h1 class="text-lg! font-black! text-slate-800! leading-tight!">Input Pemakaian</h1>
            <p class="text-[10px]! uppercase! tracking-[0.2em]! text-slate-400!">Pamsides V2</p>
          </div>
        </div>

        <div class="space-y-4!">
          <div class="relative!">
            <span class="absolute! left-3.5! top-1/2! -translate-y-1/2! text-slate-400! text-sm!">
              <font-awesome-icon icon="search" />
            </span>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari nama atau no. induk..."
              class="w-full! pl-10! pr-4! py-3! bg-slate-50! border! border-slate-200! rounded-2xl! text-sm! text-slate-700! focus:outline-none! focus:ring-2! focus:ring-cyan-500/10! focus:border-cyan-400! transition-all!"
            />
          </div>

          <div class="flex! items-center! justify-between! px-1!">
            <p class="text-[10px]! font-black! text-slate-400! uppercase! tracking-[0.2em]!">
              Daftar Pelanggan
            </p>
          </div>
        </div>
      </div>

      <!-- Card List Section -->
      <div v-if="paginatedData.length === 0" class="p-12! text-center!">
        <font-awesome-icon icon="search" class="text-slate-200! text-4xl! mb-3!" />
        <p class="text-slate-400! text-sm!">Data tidak ditemukan</p>
      </div>

      <div v-else class="divide-y! divide-slate-50!">
        <div
          v-for="row in paginatedData"
          :key="row.noInduk"
          @click="handleRowClick(row)"
          class="p-2! active:bg-slate-50! transition-all! cursor-pointer!"
        >
          <div class="flex! items-center! justify-between! mb-1.5!">
            <div class="flex! items-center! gap-3! min-w-0!">
              <div
                class="w-8! h-8! rounded-full! bg-gradient-to-br from-cyan-500 to-blue-600! flex! items-center! justify-center! text-white! font-bold! text-xs! shadow-sm! shrink-0!"
              >
                {{ row.nama.charAt(0) }}
              </div>
              <div class="truncate!">
                <p class="text-xs! font-bold! text-slate-800! truncate! mb-0!">
                  {{ row.nama }}
                </p>
                <p class="text-[9px]! font-medium! text-slate-400! tracking-wide!">
                  {{ row.dusun }} Pelanggan
                </p>
              </div>
            </div>
            <div class="text-right! shrink-0!">
              <p class="text-[9px]! font-bold! text-slate-700! mb-0.5!">#{{ row.noInduk }}</p>
              <span
                class="text-[8px]! font-black! px-1.5! py-0.5! rounded-full! border! border-cyan-100! bg-cyan-50! text-cyan-600! uppercase!"
              >
                Meter Air
              </span>
            </div>
          </div>

          <div
            class="grid! grid-cols-3! gap-2! bg-slate-50! p-2! rounded-xl! border! border-slate-100/50!"
          >
            <div class="text-center!">
              <p class="text-[8px]! font-black! text-slate-400! uppercase! mb-0!">Awal</p>
              <p class="text-xs! font-bold! text-slate-600!">{{ row.meterAwal }}</p>
            </div>
            <div class="text-center! border-x! border-slate-200!">
              <p class="text-[8px]! font-black! text-slate-400! uppercase! mb-0!">Akhir</p>
              <p
                class="text-xs! font-bold!"
                :class="row.meterAkhir === row.meterAwal ? 'text-red-500!' : 'text-orange-600!'"
              >
                {{ row.meterAkhir }}
              </p>
            </div>
            <div class="text-center!">
              <p class="text-[8px]! font-black! text-slate-400! uppercase! mb-0!">Konsumsi</p>
              <p class="text-xs! font-bold! text-cyan-600!">
                {{ hitungPemakaian(row) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Card Footer Section -->
      <div
        class="px-5! py-5! bg-slate-50/50! border-t! border-slate-100! flex! items-center! justify-between!"
      >
        <span class="text-xs! font-bold! text-slate-400! tracking-tight!">
          Page {{ currentPage }} of {{ totalPages }}
        </span>
        <div class="flex! items-center! gap-3!">
          <BaseButton
            variant="ghost"
            size="sm"
            :disabled="currentPage === 1"
            @click="currentPage--"
            class="w-10! h-10! p-0! rounded-full! bg-white! border! border-slate-100! text-slate-400! shadow-sm!"
          >
            <font-awesome-icon icon="chevron-left" class="text-[10px]!" />
          </BaseButton>
          <BaseButton
            variant="ghost"
            size="sm"
            :disabled="currentPage === totalPages"
            @click="currentPage++"
            class="w-10! h-10! p-0! rounded-full! bg-white! border! border-slate-100! text-slate-400! shadow-sm!"
          >
            <font-awesome-icon icon="chevron-right" class="text-[10px]!" />
          </BaseButton>
        </div>
      </div>
    </div>

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
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import InputMeterModal from './inputMeterModal.vue'

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
  { key: 'nama', title: 'NAMA', tdClass: 'font-medium! text-slate-500!' },
  { key: 'dusun', title: 'DUSUN', tdClass: 'text-slate-500!' },
  {
    key: 'rt',
    title: 'RT',
    tdClass: 'text-slate-500! hidden! md:table-cell!',
    thClass: 'hidden! md:table-cell!',
  },
  {
    key: 'noInduk',
    title: 'NO.INDUK',
    tdClass: 'text-slate-500! hidden! sm:table-cell!',
    thClass: 'hidden! sm:table-cell!',
  },
  { key: 'meterAwal', title: 'METER AWAL', tdClass: 'text-right! w-24!' },
  { key: 'meterAkhir', title: 'METER AKHIR', tdClass: 'text-right! w-32!' },
  {
    key: 'pemakaian',
    title: 'PEMAKAIAN',
    tdClass: 'text-right! w-24! hidden! sm:table-cell!',
    thClass: 'hidden! sm:table-cell!',
  },
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

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return filteredData.value.slice(start, end)
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
