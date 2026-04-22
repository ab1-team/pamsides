<template>
  <div class="w-full!">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4! mb-6!">
      <div class="flex-1!">
        <h1 class="text-2xl font-bold text-indigo-600! tracking-tight mb-1!">Kelas Biaya</h1>
        <p class="text-sm text-slate-500! leading-relaxed">
          Kelola kategori kelas biaya dan tarif pemakaian air berdasarkan blok.
        </p>
      </div>

      <div class="flex items-center gap-3! w-full lg:w-auto!">
        <BaseButton
          variant="info-gradient"
          size="md"
          @click="addKelas"
          class="w-full! lg:w-auto! rounded-xl! shadow-lg! shadow-blue-200/50!"
          icon="plus-circle"
        >
          Tambah Kelas & Biaya
        </BaseButton>
      </div>
    </div>

    <ContentCard variant="default" padding="none" hoverable class="overflow-hidden!">
      <DataTable
        :data="filteredData"
        :columns="tableColumns"
        v-model:current-page="currentPage"
        v-model:per-page="perPage"
        :total-pages="totalPages"
        :visible-pages="visiblePages"
        :total-entries="filteredData.length"
        :show-toolbar="true"
        v-model="searchQuery"
        search-placeholder="Cari nama kelas..."
        :no-card="true"
      >
        <template #toolbar-actions>
          <h2 class="text-base! font-bold! text-slate-800!">
            Daftar Kelas
            <span class="text-slate-400! font-normal! text-sm!"
              >· Total {{ filteredData.length }} Data</span
            >
          </h2>
        </template>

        <template #column-nama="{ row }">
          <div class="flex items-center gap-3!">
            <div
              class="w-9! h-9! rounded-xl! bg-indigo-50! text-indigo-600! flex! items-center! justify-center! text-sm! font-bold! shrink-0!"
            >
              {{ row.nama.charAt(0) }}
            </div>
            <span class="text-sm! font-bold! text-slate-700!">{{ row.nama }}</span>
          </div>
        </template>

        <template #column-block1="{ row }">
          <div class="flex flex-col!">
            <span class="text-xs! font-bold! text-blue-600!">{{ formatCurrency(row.block1) }}</span>
            <span class="text-[10px]! text-slate-400! font-medium!">0 - 10 M³</span>
          </div>
        </template>

        <template #column-block2="{ row }">
          <div class="flex flex-col!">
            <span class="text-xs! font-bold! text-blue-600!">{{ formatCurrency(row.block2) }}</span>
            <span class="text-[10px]! text-slate-400! font-medium!">11 - 20 M³</span>
          </div>
        </template>

        <template #column-block3="{ row }">
          <div class="flex flex-col!">
            <span class="text-xs! font-bold! text-blue-600!">{{ formatCurrency(row.block3) }}</span>
            <span class="text-[10px]! text-slate-400! font-medium!"> > 20 M³</span>
          </div>
        </template>

        <template #column-aksi="{ row }">
          <div class="flex items-center gap-2!">
            <BaseButton
              variant="ghost"
              size="sm"
              @click="editKelas(row)"
              class="w-8! h-8! p-0! rounded-lg! text-slate-400! hover:text-indigo-600! hover:bg-indigo-50!"
            >
              <font-awesome-icon icon="edit" />
            </BaseButton>
            <BaseButton
              variant="ghost"
              size="sm"
              @click="deleteKelas(row)"
              class="w-8! h-8! p-0! rounded-lg! text-slate-400! hover:text-red-600! hover:bg-red-50!"
            >
              <font-awesome-icon icon="trash" />
            </BaseButton>
          </div>
        </template>
      </DataTable>
    </ContentCard>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import DataTable from '@/components/ui/DataTable.vue'
import ContentCard from '@/components/ui/ContentCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { useCurrencyFormat } from '@/composables/useCurrencyFormat.js'

const router = useRouter()

const tableColumns = [
  { key: 'nama', title: 'Nama Kelas', tdClass: '' },
  { key: 'block1', title: 'Block 1 (10 M³)', tdClass: '' },
  { key: 'block2', title: 'Block 2 (20 M³)', tdClass: '' },
  { key: 'block3', title: 'Block 3 (> 20 M³)', tdClass: '' },
  { key: 'aksi', title: 'Aksi', tdClass: 'w-20!' },
]

const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(10)

const kelasList = ref([
  { id: 1, nama: 'Rumah Tangga A', block1: 2500, block2: 3500, block3: 4500 },
  { id: 2, nama: 'Rumah Tangga B', block1: 3000, block2: 4000, block3: 5000 },
  { id: 3, nama: 'Sosial / Masjid', block1: 1500, block2: 2500, block3: 3500 },
  { id: 4, nama: 'Industri Kecil', block1: 5000, block2: 6500, block3: 8000 },
  { id: 5, nama: 'Industri Besar', block1: 8000, block2: 10000, block3: 12000 },
  { id: 6, nama: 'Instansi Pemerintah', block1: 4000, block2: 5000, block3: 6000 },
])

const filteredData = computed(() => {
  if (!searchQuery.value) return kelasList.value

  const query = searchQuery.value.toLowerCase()
  return kelasList.value.filter((item) => item.nama.toLowerCase().includes(query))
})

const totalPages = computed(() => Math.ceil(filteredData.value.length / perPage.value))

const visiblePages = computed(() => {
  const pages = []
  for (let i = 1; i <= totalPages.value; i++) {
    pages.push(i)
  }
  return pages
})

const formatCurrency = (val) => {
  return useCurrencyFormat(val)
}

const addKelas = () => {
  router.push('/kelas-biaya/config')
}

const editKelas = (row) => {
  router.push(`/kelas-biaya/config/${row.id}`)
}

const deleteKelas = (row) => {
  console.log('Delete Kelas:', row)
}
</script>

<style scoped></style>
