<template>
  <div class="pelanggan-root">
    <DataTable
      :data="filteredData"
      :columns="tableColumns"
      title="Daftar Pelanggan Aktif"
      v-model:current-page="currentPage"
      v-model:per-page="perPage"
      :total-pages="totalPages"
      :visible-pages="visiblePages"
      :total-entries="tableData.length"
      v-model="searchQuery"
      v-model:selection="selectedRows"
      selectable
      class="mt-6!"
      search-placeholder="Cari pelanggan (Nama, NIK, ID)..."
      empty-title="Pelanggan Tidak Ditemukan"
      empty-message="Mohon cek kembali kata kunci pencarian Anda atau tambahkan pelanggan baru."
      empty-icon="users-slash"
    >
      <template #bulk-actions="{ selection }">
        <BaseButton
          variant="primary"
          size="sm"
          icon="file-invoice"
          @click="handleBulkInvoice(selection)"
        >
          Generate Tagihan ({{ selection.length }})
        </BaseButton>
        <BaseButton variant="secondary" size="sm" icon="print"> Cetak Label </BaseButton>
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
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { usePelanggan } from '@/composables/usePelanggan'
import { useUiStore } from '@/stores/uiStore'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'

const router = useRouter()
const uiStore = useUiStore()
const selectedRows = ref([])

const {
  searchQuery,
  currentPage,
  tableData,
  filteredData,
  totalPages,
  visiblePages,
  STATUS_COLORS,
  handleEdit,
  handleDelete,
} = usePelanggan(router)

const handleBulkInvoice = (selection) => {
  uiStore.success(`Berhasil generate tagihan untuk ${selection.length} pelanggan.`)
  selectedRows.value = [] // Clear selection
}

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
