<template>
  <div class="space-y-6!">
    <div v-if="!bookClosed && !loadingStatus" class="text-center! py-12! text-gray-500! text-sm!">
      Silakan tutup buku terlebih dahulu pada tahun yang dipilih.
    </div>

    <template v-else>
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6! gap-4">
        <div class="header-left">
          <h1 class="text-3xl sm:text-4xl font-bold text-gray-800">Alokasi Laba</h1>
        </div>
        <ContentCard variant="elevated" padding="normal" hoverable>
          <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1!">
            TOTAL LABA TERSEDIA
          </div>
          <div class="text-xl sm:text-2xl font-bold text-gray-800 font-mono">
            Rp {{ formatNum(totalSaldo) }}
          </div>
        </ContentCard>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 mb-5!">
        <ContentCard variant="default" padding="normal" hoverable>
          <div class="flex items-center gap-2.5 mb-5! pb-4 border-b border-gray-100">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm bg-cyan-100">
              💸
            </div>
            <div class="flex flex-col">
              <span class="text-base font-bold text-gray-800">Laba Dibagihkan</span>
              <span class="text-xs text-gray-500">Akun 2.1.01.% (Utang)</span>
            </div>
          </div>

          <div
            v-for="(item, idx) in itemsDibagihkan"
            :key="item.kode_akun + '-' + idx"
            class="mb-4!"
          >
            <div
              class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-1.5! gap-1"
            >
              <span class="text-sm font-semibold text-gray-600">
                {{ item.nama_akun }}
              </span>
            </div>
            <MaksMoneyInput
              v-model="item.nominal"
              placeholder="0"
              :show-helper="false"
              size="md"
              no-margin
            />
          </div>
        </ContentCard>

        <ContentCard variant="default" padding="normal" hoverable>
          <div class="flex items-center gap-2.5 mb-5! pb-4 border-b border-gray-100">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm bg-teal-50">
              🏦
            </div>
            <div class="flex flex-col">
              <span class="text-base font-bold text-gray-800">Laba Ditahan</span>
              <span class="text-xs text-gray-500">Pemupukan Modal (Akun 3.2.01.01 - Debit)</span>
            </div>
          </div>

          <div
            v-for="(item, idx) in itemsDitahan"
            :key="'ditahan-' + idx"
            class="mb-4!"
          >
            <div
              class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-1.5! gap-1"
            >
              <span class="text-sm font-semibold text-gray-600">
                {{ item.nama_akun }}
              </span>
            </div>
            <MaksMoneyInput
              v-model="item.nominal"
              placeholder="0"
              :show-helper="false"
              size="md"
              no-margin
              readonly
            />
          </div>
        </ContentCard>
      </div>

      <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mt-8!">
        <BaseButton
          variant="ghost"
          size="lg"
          @click="handleBack"
          class="w-full sm:w-auto px-10! rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-500 font-bold"
        >
          Kembali
        </BaseButton>
        <BaseButton
          variant="primary-gradient"
          size="lg"
          @click="handleSimpan"
          :disabled="isSaving || isLoading || !canSave"
          :loading="isSaving"
          class="w-full sm:w-auto px-10! rounded-xl shadow-lg shadow-blue-200/50 font-bold"
        >
          Simpan Alokasi Laba
        </BaseButton>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUiStore } from '@/stores/uiStore'
import MaksMoneyInput from '@/presentations/components/MaksMoneyInput.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import { accountingService } from '@/services/accounting.service'

const route = useRoute()
const router = useRouter()
const uiStore = useUiStore()

const selectedTahun = ref('')
const totalSaldo = ref(0)
const bookClosed = ref(false)
const isLoading = ref(false)
const loadingStatus = ref(false)
const isSaving = ref(false)

const itemsDibagihkan = ref([])
const itemsDitahan = ref([
  {
    kode_akun: '3.2.01.01',
    nama_akun: 'Laba Ditahan',
    jenis_mutasi: 'debit',
    nominal: 0,
  },
])

const totalDibagihkan = computed(() =>
  itemsDibagihkan.value.reduce((s, i) => s + (Number(i.nominal) || 0), 0),
)

const totalAlokasi = computed(() => totalDibagihkan.value + (Number(itemsDitahan.value[0]?.nominal) || 0))
const selisih = computed(() => totalSaldo.value - totalAlokasi.value)
const isBalanced = computed(() => Math.abs(selisih.value) < 0.01)

const allAccountsFilled = computed(() => {
  return itemsDibagihkan.value.every((item) => {
    const val = Number(item.nominal)
    return Number.isFinite(val) && val >= 0
  })
})

const canSave = computed(() => isBalanced.value && allAccountsFilled.value)

const pemupukanModalNominal = computed(() => {
  return totalSaldo.value - totalDibagihkan.value
})

const formatNum = (val) =>
  Number(val || 0).toLocaleString('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  })

const resetForm = () => {
  itemsDibagihkan.value = []
  itemsDitahan.value = [
    {
      kode_akun: '3.2.01.01',
      nama_akun: 'Laba Ditahan',
      jenis_mutasi: 'debit',
      nominal: 0,
    },
  ]
  totalSaldo.value = 0
}

const applyAllocation = (dibagihkan, totalLaba) => {
  totalSaldo.value = Number(totalLaba) || 0
  itemsDibagihkan.value = (dibagihkan || []).map((it) => {
    const nominal = Number(it.nominal) || 0
    return {
      kode_akun: it.kode_akun,
      nama_akun: it.nama_akun,
      jenis_mutasi: it.jenis_mutasi,
      nominal: nominal,
    }
  })
  if (itemsDitahan.value.length === 0) {
    itemsDitahan.value = [
      {
        kode_akun: '3.2.01.01',
        nama_akun: 'Laba Ditahan',
        jenis_mutasi: 'debit',
        nominal: 0,
      },
    ]
  }
  itemsDitahan.value[0].nominal = totalSaldo.value - totalDibagihkan.value
}

const loadBookStatus = async (year) => {
  loadingStatus.value = true
  try {
    const res = await accountingService.checkAllocation(year)
    if (res?.success && res.data) {
      bookClosed.value = !!res.data.tutup_buku
      if (res.data.total_laba) {
        totalSaldo.value = Number(res.data.total_laba) || 0
      }
      return bookClosed.value
    }
    bookClosed.value = false
    return false
  } catch (e) {
    console.error('Gagal cek status:', e)
    bookClosed.value = false
    return false
  } finally {
    loadingStatus.value = false
  }
}

const loadExistingAllocation = async (year) => {
  try {
    const res = await accountingService.calculateAllocation(year, null)
    if (res?.success && res.data) {
      applyAllocation(
        res.data.laba_dibagihkan,
        res.data.total_laba,
      )
    }
  } catch (e) {
    console.error('Gagal load alokasi existing:', e)
  }
}

const handleYearChange = async (year) => {
  resetForm()
  if (!year) {
    bookClosed.value = false
    return
  }

  isLoading.value = true
  try {
    const closed = await loadBookStatus(year)
    if (!closed) return
    await loadExistingAllocation(year)
  } finally {
    isLoading.value = false
  }
}

const handleSimpan = async () => {
  if (!selectedTahun.value) {
    uiStore.error('Pilih tahun terlebih dahulu.')
    return
  }
  if (Math.abs(selisih.value) >= 0.01) {
    uiStore.error('Total alokasi harus sama dengan total laba tersedia.')
    return
  }

  isSaving.value = true
  try {
    const items = [
      ...itemsDibagihkan.value.map((i) => ({
        kode_akun: i.kode_akun,
        nama_akun: i.nama_akun,
        jenis_mutasi: i.jenis_mutasi,
        nominal: Number(i.nominal) || 0,
      })),
      ...itemsDitahan.value.map((i) => ({
        kode_akun: i.kode_akun,
        nama_akun: i.nama_akun,
        jenis_mutasi: i.jenis_mutasi,
        nominal: Number(i.nominal) || 0,
      })),
    ]

    const res = await accountingService.saveAllocation(selectedTahun.value, items)
    if (res?.success) {
      uiStore.success(
        res.data?.message || `Alokasi laba tahun ${selectedTahun.value} berhasil disimpan ke bulan 13.`,
      )
    } else {
      uiStore.error(res?.message || 'Gagal menyimpan alokasi laba.')
    }
  } catch (e) {
    console.error('Error simpan alokasi:', e)
    uiStore.error(e?.response?.data?.message || 'Terjadi kesalahan saat menyimpan alokasi.')
  } finally {
    isSaving.value = false
  }
}

const handleBack = () => router.push('/app/transaksi/tutup-buku')

watch(
  [totalDibagihkan, totalSaldo],
  () => {
    if (itemsDitahan.value.length > 0) {
      itemsDitahan.value[0].nominal = pemupukanModalNominal.value
    }
  },
  { immediate: true },
)

onMounted(async () => {
  const qTahun = Number(route.query.tahun)
  if (qTahun) {
    selectedTahun.value = qTahun
    await handleYearChange(qTahun)
  }
})
</script>
