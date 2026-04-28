<template>
  <div class="w-full!">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4! mb-6!">
      <div class="flex-1!">
        <h1 class="text-2xl font-bold text-slate-900! tracking-tight mb-1!">Kelas Biaya</h1>
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
          icon="plus"
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
              class="w-8! h-8! p-0! rounded-lg! border! border-slate-100! hover:border-blue-200! hover:bg-blue-50! text-slate-600! hover:text-blue-600! shadow-sm!"
              title="Edit"
              icon="edit"
            />
            <BaseButton
              variant="ghost"
              size="sm"
              @click="deleteKelas(row)"
              class="w-8! h-8! p-0! rounded-lg! border! border-slate-100! hover:border-red-200! hover:bg-red-50! text-slate-600! hover:text-red-600! shadow-sm!"
              title="Delete"
              icon="trash"
            />
          </div>
        </template>
      </DataTable>
    </ContentCard>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import { useCurrencyFormat } from '@/composables/useCurrencyFormat.js'
import packageService from '@/services/package.service'

import Swal from 'sweetalert2'

const router = useRouter()

const tableColumns = [
  { key: 'nama', title: 'Nama Kelas', tdClass: '' },
  { key: 'abodemen', title: 'Abodemen', tdClass: '' },
  { key: 'denda', title: 'Denda', tdClass: '' },
  { key: 'aksi', title: 'AKSI', tdClass: 'w-20!' },
]

const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(10)
const loading = ref(false)

const kelasList = ref([])

const fetchPackages = async () => {
  try {
    loading.value = true
    const res = await packageService.getPackages()
    if (res.success) {
      kelasList.value = res.data.map((item) => ({
        id: item.id,
        nama: item.name,
        abodemen: item.monthly_abodemen,
        denda: item.late_penalty,
      }))
    }
  } catch (error) {
    Swal.fire('Error', 'Gagal mengambil data kelas biaya', 'error')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchPackages()
})

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

const deleteKelas = async (row) => {
  const result = await Swal.fire({
    title: 'Hapus Kelas?',
    text: `Kelas "${row.nama}" akan dihapus secara permanent dari aplikasi`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus!',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    reverseButtons: true,
  })

  if (result.isConfirmed) {
    try {
      const res = await packageService.deletePackage(row.id)
      if (res.success) {
        Swal.fire({
          title: 'Terhapus!',
          text: 'Data kelas telah berhasil dihapus.',
          icon: 'success',
          timer: 1500,
          showConfirmButton: false,
        })
        fetchPackages()
      }
    } catch (error) {
      Swal.fire('Error', 'Gagal menghapus kelas', 'error')
    }
  }
}
</script>

<style scoped></style>
