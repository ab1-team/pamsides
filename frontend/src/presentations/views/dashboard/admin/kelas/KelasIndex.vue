<template>
  <div class="p-6! bg-slate-50/30! min-h-screen!">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4! mb-6!">
      <div class="flex-1!">
        <h1 class="text-xl! md:text-2xl! font-bold text-slate-900! tracking-tight mb-1!">
          Paket & Tarif Layanan
        </h1>
        <p class="hidden sm:block! text-sm! text-slate-500! leading-relaxed">
          Kelola kategori paket instalasi dan tarif pemakaian air berdasarkan blok.
        </p>
      </div>

      <BaseButton
        variant="primary"
        size="lg"
        @click="handleAdd"
        class="w-full! sm:w-auto! px-6! py-3! font-bold! rounded-2xl! shadow-lg! shadow-blue-200! transition-all! active:scale-95!"
      >
        <font-awesome-icon icon="circle-plus" class="mr-2.5! text-lg!" />
        Tambah Paket & Tarif
      </BaseButton>
    </div>

    <ContentCard
      padding="none"
      class="border-0! shadow-xl! shadow-slate-200/50! rounded-3xl! overflow-hidden!"
    >
      <DataTable
        :columns="columns"
        :data="items"
        :loading="loading"
        :total-entries="items.length"
        expandable
      >
        <template #column-nama="{ row }">
          <div class="flex items-center gap-3!">
            <div
              class="w-10! h-10! rounded-xl! bg-blue-50! text-blue-600! flex! items-center! justify-center! font-bold! text-xs!"
            >
              {{ row.name.substring(0, 2).toUpperCase() }}
            </div>
            <div>
              <div class="font-bold! text-slate-900!">{{ row.name }}</div>
              <div class="text-[10px]! font-medium! text-slate-400! uppercase! tracking-tight!">
                ID: #{{ row.id }}
              </div>
            </div>
          </div>
        </template>

        <template #column-biaya_pasang="{ row }">
          <div class="text-right! pr-8!">
            <div class="text-sm! font-bold! text-slate-700!">
              {{ formatCurrency(row.installation_fee) }}
            </div>
            <div class="text-[10px]! text-slate-400! font-medium!">ONE TIME</div>
          </div>
        </template>

        <template #column-abodemen="{ row }">
          <div class="text-right! pr-8!">
            <div class="text-sm! font-bold! text-slate-700!">
              {{ formatCurrency(row.monthly_abodemen) }}
            </div>
            <div class="text-[10px]! text-slate-400! font-medium!">MONTHLY</div>
          </div>
        </template>

        <template #column-denda="{ row }">
          <div class="text-right! pr-8!">
            <div class="text-sm! font-bold! text-amber-600!">
              {{ formatCurrency(row.late_penalty) }}
            </div>
            <div class="text-[10px]! text-slate-400! font-medium!">PER VIOLATION</div>
          </div>
        </template>

        <template #expanded-row="{ row }">
          <div class="p-6! bg-slate-50/50! border-t! border-slate-100!">
            <div class="flex items-center gap-3! mb-5!">
              <div class="w-1! h-5! bg-blue-500! rounded-full!"></div>
              <h3 class="font-extrabold! text-slate-800! text-sm! tracking-tight uppercase!">
                Rincian Blok Tarif
              </h3>
            </div>

            <div
              class="grid gap-4!"
              :style="{
                gridTemplateColumns: `repeat(auto-fill, minmax(200px, 1fr))`,
              }"
            >
              <div
                v-for="(block, idx) in row.water_tariff_blocks"
                :key="idx"
                class="bg-white! p-5! rounded-2xl! border! border-slate-100! shadow-sm! hover:shadow-md! hover:border-blue-100! transition-all! relative! overflow-hidden!"
              >
                <div
                  v-if="!block.usage_max_m3"
                  class="absolute! -right-6! -top-6! w-12! h-12! bg-blue-500/10! rounded-full! flex! items-center! justify-center! rotate-12!"
                >
                  <span class="text-blue-500! font-black! text-lg!">∞</span>
                </div>

                <div class="flex items-center justify-between mb-4!">
                  <span class="text-[10px]! font-black! text-slate-300! uppercase! tracking-widest!"
                    >BLOCK {{ idx + 1 }}</span
                  >
                  <div
                    class="w-7! h-7! rounded-lg! bg-blue-50! text-blue-600! flex! items-center! justify-center! text-xs! font-black!"
                  >
                    {{ idx + 1 }}
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-4!">
                  <div class="space-y-1!">
                    <div
                      class="text-[9px]! font-bold! text-slate-400! uppercase! tracking-tighter!"
                    >
                      Volume
                    </div>
                    <div class="text-sm! font-black! text-slate-800!">
                      {{ block.usage_min_m3 }} - {{ block.usage_max_m3 || '∞' }}
                      <span class="text-[10px]! text-slate-400! font-bold! ml-0.5!">m³</span>
                    </div>
                  </div>
                  <div class="space-y-1!">
                    <div
                      class="text-[9px]! font-bold! text-slate-400! uppercase! tracking-tighter!"
                    >
                      Harga
                    </div>
                    <div class="text-sm! font-black! text-blue-600!">
                      {{ formatCurrency(block.price_per_m3) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>

        <template #column-actions="{ row }">
          <div class="flex items-center justify-center gap-2!">
            <BaseButton
              variant="ghost"
              size="sm"
              icon="edit"
              @click="handleEdit(row)"
              class="w-8! h-8! p-0! rounded-lg! border! border-slate-100! hover:border-blue-200! hover:bg-blue-50! text-slate-600! hover:text-blue-600! shadow-sm! transition-all!"
            />
            <BaseButton
              variant="ghost"
              size="sm"
              icon="trash"
              @click="handleDelete(row)"
              class="w-8! h-8! p-0! rounded-lg! border! border-slate-100! hover:border-red-200! hover:bg-red-50! text-slate-600! hover:text-red-600! shadow-sm! transition-all!"
            />
          </div>
        </template>
      </DataTable>
    </ContentCard>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import packageService from '@/services/package.service'
import Swal from 'sweetalert2'

const router = useRouter()
const items = ref([])
const loading = ref(false)

const columns = [
  { key: 'nama', title: 'Nama Kelas', tdClass: 'pl-2! sm:pl-6!' },
  {
    key: 'biaya_pasang',
    title: 'Biaya Pasang',
    tdClass: 'hidden sm:table-cell! w-44!',
    thClass: 'hidden sm:table-cell! text-right! pr-8!',
  },
  {
    key: 'abodemen',
    title: 'Biaya Abodemen',
    tdClass: 'hidden md:table-cell! w-44!',
    thClass: 'hidden md:table-cell! text-right! pr-8!',
  },
  {
    key: 'denda',
    title: 'Denda Keterlambatan',
    tdClass: 'hidden lg:table-cell! w-44!',
    thClass: 'hidden lg:table-cell! text-right! pr-8!',
  },
  {
    key: 'actions',
    title: 'AKSI',
    tdClass: 'w-24! text-center!',
    thClass: 'text-center!',
    sortable: false,
  },
]

const fetchData = async () => {
  loading.value = true
  try {
    const res = await packageService.getPackages()
    if (res.success) {
      items.value = res.data
    }
  } catch (error) {
    Swal.fire('Error', 'Gagal mengambil data paket', 'error')
  } finally {
    loading.value = false
  }
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value)
}

const handleAdd = () => {
  router.push('/kelas-biaya/config')
}

const handleEdit = (item) => {
  router.push(`/kelas-biaya/config/${item.id}`)
}

const handleDelete = async (item) => {
  const result = await Swal.fire({
    title: 'Apakah anda yakin?',
    text: `Menghapus kelas ${item.name} akan menghapus data terkait!`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Ya, Hapus!',
    cancelButtonText: 'Batal',
    reverseButtons: true,
  })

  if (result.isConfirmed) {
    try {
      const res = await packageService.deletePackage(item.id)
      if (res.success) {
        Swal.fire('Terhapus!', 'Kelas biaya berhasil dihapus.', 'success')
        fetchData()
      }
    } catch (error) {
      Swal.fire('Gagal!', 'Terjadi kesalahan saat menghapus data.', 'error')
    }
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
:deep(.data-table-container) {
  border: none !important;
}

:deep(thead th) {
  background-color: white !important;
  border-bottom: 1px solid #f1f5f9 !important;
  color: #94a3b8 !important;
  font-size: 11px !important;
  font-weight: 700 !important;
  text-transform: uppercase !important;
  letter-spacing: 0.05em !important;
  padding-top: 1.25rem !important;
  padding-bottom: 1.25rem !important;
}

:deep(tbody td) {
  padding-top: 1rem !important;
  padding-bottom: 1rem !important;
  border-bottom: 1px solid #f8fafc !important;
}

:deep(tbody tr:hover td) {
  background-color: #f8fafc !important;
}
</style>
