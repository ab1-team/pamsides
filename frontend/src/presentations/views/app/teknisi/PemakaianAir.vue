<template>
  <div class="pemakaian-air-root">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4!">
      <div class="flex-1!">
        <h1 class="text-xl md:text-2xl font-bold text-cyan-600! tracking-tight mb-1!">
          Input Pemakaian Air
        </h1>
        <p class="text-xs md:text-sm text-slate-500! leading-relaxed!">
          Kelola pencatatan meter pelanggan di wilayah Anda.
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

        <!--
          Teknisi tidak punya filter "Pilih Teknisi" — datanya sudah
          otomatis miliknya sendiri (backend membatasi `user_id` ke teknisi
          yang login). Yang ditampilkan hanya nama dia sendiri, supaya
          teknisi tahu data ini miliknya.
        -->
        <div class="flex-1! min-w-[120px]!">
          <div
            class="flex! items-center! gap-2! h-11! px-3! bg-slate-50! border! border-slate-200! rounded-xl! text-sm! font-semibold! text-slate-700!"
          >
            <font-awesome-icon icon="id-badge" class="text-cyan-600! text-xs!" />
            <span class="truncate!">{{ currentUserLabel }}</span>
          </div>
        </div>
      </div>
    </ContentCard>

    <DataTable
      :data="filteredData"
      :columns="tableColumns"
      v-model:current-page="currentPage"
      v-model:per-page="perPage"
      :total-pages="totalPages"
      :visible-pages="visiblePages"
      :total-entries="filteredData.length"
      v-model="searchQuery"
      :loading="isLoading"
      empty-title="Tidak Ada Data"
      empty-message="Belum ada data pemakaian air untuk periode ini di wilayah Anda."
      empty-icon="filter"
      class="mt-6!"
      search-placeholder="Cari..."
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
        <span class="text-sm! font-semibold! text-slate-900!"> {{ row.pemakaian }}</span>
      </template>

      <template #column-tagihan="{ row }">
        <div class="font-bold! text-sm! text-slate-900!">
          Rp.
          {{
            Number(row.tagihan || 0).toLocaleString('id-ID', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            })
          }}
        </div>
        <div
          v-if="Number(row.denda || 0) > 0"
          class="mt-0.5! text-[10px]! font-semibold! text-red-500! leading-tight!"
        >
          + denda <span v-if="row.denda_bulan"> {{ row.denda_bulan }}</span> Rp.{{
            Number(row.denda).toLocaleString('id-ID', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            })
          }}
        </div>
      </template>

      <template #column-jatuhTempo="{ row }">
        <span class="text-sm! text-slate-700!">
          {{ formatDate(row.jatuhTempo) }}
        </span>
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
    </DataTable>

    <!--
      Modal "Hasil Input" — komponen yang SAMA dengan yang dipakai admin
      (`admin/tagihan/partials/hasilInputModal.vue`), supaya tampilan,
      kolom, dan tombol cetaknya identik.

      Perbedaannya cuma pada isi:
        - `filter.cater`   → nama teknisi yang login (admin: teknisi terpilih)
        - `filter.teknisi` → ID teknisi yang login, supaya query cetakan
                            (cetak struk / daftar tagihan) membawa identitas
                            yang sama dengan admin.
    -->
    <HasilInputModal
      :show="showHasilModal"
      :grouped-data="groupedData"
      :filter="{ ...filter, cater: currentUserLabel, teknisi: uiStore.userData?.id }"
      @close="showHasilModal = false"
    />
  </div>
</template>

<script setup>
import { usePemakaianAir } from '@/composables/usePemakaianAir'
import SelectSearch from '@/presentations/components/SelectSearch.vue'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import HasilInputModal from '@/presentations/views/app/admin/tagihan/partials/hasilInputModal.vue'
import { useRouter } from 'vue-router'
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useUiStore } from '@/stores/uiStore'

const uiStore = useUiStore()

const {
  filter,
  searchQuery,
  currentPage,
  perPage,
  tahunOptions,
  bulanOptions,
  currentUserLabel,
  filteredData,
  groupedData,
  totalPages,
  visiblePages,
  STATUS_COLORS,
  isLoading,
  handleApplyFilter,
} = usePemakaianAir()

const router = useRouter()

const showHasilModal = ref(false)

const handleHasilInput = () => {
  showHasilModal.value = true
}

// Perubahan periode → muat ulang data.
let filterDebounceTimer = null
watch(
  () => [filter.value.bulan, filter.value.tahun],
  () => {
    if (filterDebounceTimer) clearTimeout(filterDebounceTimer)
    filterDebounceTimer = setTimeout(() => {
      handleApplyFilter()
    }, 300)
  },
)
onUnmounted(() => {
  if (filterDebounceTimer) clearTimeout(filterDebounceTimer)
})

onMounted(() => {
  handleApplyFilter()
})

const handleInputPemakaian = () => {
  router.push({
    name: 'Input Pemakaian Air',
    query: {
      tahun: filter.value.tahun,
      bulan: filter.value.bulan,
    },
  })
}

/**
 * Cetak Form Input.
 *
 * Membuka route yang SAMA dengan admin ('Cetak Form' → `cetakForm.vue`),
 * supaya hasil cetak identik: preview PDF lengkap dengan thumbnail
 * sidebar, zoom, dan pagination per dusun.
 *
 * Query `teknisi` diisi ID teknisi yang login. Backend
 * (`MonthlyBillController::usage`) mengabaikan `user_id` untuk teknisi dan
 * selalu memakai user yang sedang login — jadi angka dan barisnya tetap
 * milik teknisi itu sendiri, bukan pelanggan teknisi lain.
 */
const handleCetakFormInput = () => {
  const url = router.resolve({
    name: 'Cetak Form',
    query: {
      tahun: filter.value.tahun,
      bulan: filter.value.bulan,
      teknisi: uiStore.userData?.id,
      cater: currentUserLabel.value,
    },
  }).href
  window.open(url, '_blank')
}

const tableColumns = [
  { key: 'nama', title: 'NAMA / NO. INDUK' },
  { key: 'meterRange', title: 'METER' },
  { key: 'pemakaian', title: 'PEMAKAIAN' },
  { key: 'tagihan', title: 'TAGIHAN' },
  { key: 'jatuhTempo', title: 'TGL AKHIR BAYAR' },
  { key: 'status', title: 'STATUS' },
]

const formatDate = (val) => {
  if (!val) return '-'
  const d = new Date(val)
  if (Number.isNaN(d.getTime())) return val
  d.setDate(d.getDate() - 1)
  return d.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}
</script>

<style scoped>
.pemakaian-air-root {
  width: 100%;
}
</style>
