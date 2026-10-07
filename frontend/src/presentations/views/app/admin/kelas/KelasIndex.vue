<template>
  <div class="kelas-biaya-root">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4! mb-6!">
      <div class="flex-1!">
        <h1 class="text-xl! md:text-2xl! font-bold text-slate-900! tracking-tight mb-1!">
          Paket & Tarif Layanan
        </h1>
        <p class="text-sm! text-slate-500! leading-relaxed">
          Kelola kategori paket instalasi dan tarif pemakaian air berdasarkan blok.
        </p>
      </div>

      <BaseButton
        variant="primary"
        size="lg"
        @click="handleAdd"
        class="w-full! sm:w-auto! px-6! py-3! font-bold! rounded-2xl! shadow-lg! shadow-blue-200! hover:shadow-xl! hover:shadow-blue-300/40! transition-all! active:scale-95!"
      >
        <font-awesome-icon icon="circle-plus" class="mr-2.5! text-lg!" />
        Tambah Paket & Tarif
      </BaseButton>
    </div>

    <ContentCard
      variant="bordered"
      padding="none"
      rounded="2xl"
      class="overflow-hidden! shadow-lg! shadow-slate-200/50! hover:shadow-2xl! transition-all! duration-300!"
    >
      <DataTable
        v-model="searchQuery"
        :columns="columns"
        :data="filteredItems"
        :loading="loading"
        :total-entries="filteredItems.length"
        search-placeholder="Cari nama paket..."
        empty-title="Paket Tidak Ditemukan"
        empty-message="Belum ada paket yang terdaftar atau kata kunci pencarian tidak cocok."
        empty-icon="folder-open"
        no-card
        expandable
      >
        <template #column-nama="{ row }">
          <div class="flex items-center gap-3!">
            <div
              class="w-9! h-9! rounded-xl! bg-gradient-to-br! from-blue-500! to-blue-600! flex! items-center! justify-center! text-white! text-xs! font-black! shrink-0! shadow-md! shadow-blue-200!"
            >
              <font-awesome-icon icon="droplet" />
            </div>
            <div>
              <div class="font-bold! text-slate-900!">{{ row.name }}</div>
              <div
                class="text-[10px]! font-medium! text-slate-400! uppercase! tracking-tight!"
              >
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
          <div class="pb-2!">
            <div class="flex items-center gap-3! mb-4!">
              <div class="w-1! h-5! bg-blue-500! rounded-full!"></div>
              <h3 class="font-extrabold! text-slate-800! text-sm! tracking-tight uppercase!">
                Rincian Blok Tarif
              </h3>
              <span
                class="px-2! py-0.5! bg-blue-50! text-blue-700! text-[10px]! font-black! rounded-lg! uppercase! tracking-widest!"
              >
                {{ row.water_tariff_blocks?.length || 0 }} Blok
              </span>
            </div>

            <div
              v-if="row.water_tariff_blocks?.length"
              class="grid gap-4!"
              :style="{
                gridTemplateColumns: `repeat(auto-fill, minmax(260px, 1fr))`,
              }"
            >
              <div
                v-for="(block, idx) in row.water_tariff_blocks"
                :key="idx"
                class="bg-white! p-3! rounded-2xl! border! border-slate-100! shadow-md! shadow-slate-200/40! hover:shadow-xl! hover:shadow-blue-200/30! hover:border-blue-100! transition-all! relative! overflow-hidden!"
              >
                <div
                  v-if="isUnbounded(block)"
                  class="absolute! -right-6! -top-6! w-12! h-12! bg-blue-500/10! rounded-full! flex! items-center! justify-center! rotate-12!"
                >
                  <span class="text-blue-500! font-black! text-lg!">∞</span>
                </div>

                <div class="flex items-center justify-between mb-3!">
                  <span
                    class="text-[10px]! font-black! text-slate-300! uppercase! tracking-widest!"
                    >BLOCK {{ idx + 1 }}</span
                  >
                  <div
                    class="w-7! h-7! rounded-lg! bg-blue-50! text-blue-600! flex! items-center! justify-center! text-xs! font-black! shadow-sm! shadow-blue-100!"
                  >
                    {{ idx + 1 }}
                  </div>
                </div>

                <div class="flex items-start justify-between gap-3!">
                  <div class="min-w-0! space-y-0.5!">
                    <div
                      class="text-[9px]! font-bold! text-slate-400! uppercase! tracking-tighter!"
                    >
                      Volume
                    </div>
                    <div
                      class="text-sm! font-black! text-slate-800! whitespace-nowrap! tabular-nums!"
                    >
                      {{ block.usage_min_m3 }} - {{ maxUsage(block) }}
                      <span class="text-[10px]! text-slate-400! font-bold! ml-0.5!">m³</span>
                    </div>
                  </div>
                  <div class="shrink-0! text-right! space-y-0.5!">
                    <div
                      class="text-[9px]! font-bold! text-slate-400! uppercase! tracking-tighter!"
                    >
                      Harga
                    </div>
                    <div
                      class="text-sm! font-black! text-blue-600! whitespace-nowrap! tabular-nums!"
                    >
                      {{ formatCurrency(block.price_per_m3) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <p v-else class="text-xs! text-slate-400! font-medium! italic!">
              Belum ada skema blok tarif untuk paket ini.
            </p>
          </div>
        </template>

        <template #column-actions="{ row }">
          <div class="flex items-center justify-center gap-2!">
            <BaseButton
              variant="ghost"
              size="sm"
              icon="edit"
              title="Ubah"
              @click="handleEdit(row)"
              class="w-8! h-8! p-0! rounded-lg! border! border-slate-100! hover:border-blue-200! hover:bg-blue-50! text-slate-600! hover:text-blue-600! shadow-sm! transition-all!"
            />
            <BaseButton
              variant="ghost"
              size="sm"
              icon="trash"
              title="Hapus"
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
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import packageService from '@/services/package.service'
import Swal from 'sweetalert2'
import { confirmDelete } from '@/utils/deleteHandler'

const router = useRouter()
const items = ref([])
const loading = ref(false)
const searchQuery = ref('')

const filteredItems = computed(() => {
  const keyword = searchQuery.value.trim().toLowerCase()
  if (!keyword) return items.value
  return items.value.filter((item) => item.name?.toLowerCase().includes(keyword))
})

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

// Blok terakhir biasanya tanpa batas atas (`usage_max_m3` kosong / null).
const isUnbounded = (block) => {
  const max = block?.usage_max_m3
  return max === null || max === undefined || max === ''
}

const maxUsage = (block) => (isUnbounded(block) ? '∞' : block.usage_max_m3)

const handleAdd = () => {
  router.push('/app/kelas-biaya/config')
}

const handleEdit = (item) => {
  router.push(`/app/kelas-biaya/config/${item.id}`)
}

const handleDelete = async (item) => {
  await confirmDelete({
    title: 'Hapus Paket & Tarif?',
    text: `Kelas "${item.name}" akan dihapus`,
    successMessage: 'Kelas biaya berhasil dihapus',
    entity: 'kelas biaya',
    errorCode: 'PACKAGE_IN_USE',
    onConfirm: async () => {
      await packageService.deletePackage(item.id)
      await fetchData()
    },
  })
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
:deep(.data-table-container) {
  border: none !important;
}
</style>