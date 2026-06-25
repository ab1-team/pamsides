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

      <div v-if="isAlreadyAllocated" class="mb-4!">
        <div
          class="bg-blue-50 border-2 border-blue-200 rounded-xl p-3.5! flex items-start gap-2.5!"
        >
          <span class="text-base">ℹ️</span>
          <div class="text-sm text-blue-800">
            Alokasi laba untuk tahun <b>{{ selectedTahun }}</b> sudah pernah disimpan. Klik
            <b>Reset</b> untuk mengubah.
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 mb-5!">
        <ContentCard variant="default" padding="normal" hoverable>
          <div class="flex items-center gap-2.5 mb-5! pb-4 border-b border-gray-100">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm bg-cyan-100">
              💸
            </div>
            <span class="text-base font-bold text-gray-800">Laba Dibagikan</span>
          </div>

          <div v-for="item in itemsDibagikan" :key="item.kode_akun" class="mb-4!">
            <div
              class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-1.5! gap-1"
            >
              <span class="text-sm font-semibold text-gray-600">
                {{ item.nama_akun }} ({{ item.persen }}%)
              </span>
              <span
                v-if="!isAlreadyAllocated"
                class="text-xs font-bold px-2 py-1 rounded-full bg-green-100 text-green-600"
                >Valid</span
              >
            </div>
            <MaksMoneyInput
              v-model="item.nominal"
              placeholder="0,00"
              :show-helper="false"
              :readonly="isAlreadyAllocated"
            />
          </div>
        </ContentCard>

        <ContentCard variant="default" padding="normal" hoverable>
          <div class="flex items-center gap-2.5 mb-5! pb-4 border-b border-gray-100">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm bg-teal-50">
              🏦
            </div>
            <span class="text-base font-bold text-gray-800">Laba Ditahan</span>
          </div>

          <div v-for="item in itemsDitahan" :key="item.kode_akun" class="mb-4!">
            <div
              class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-1.5! gap-1"
            >
              <span class="text-sm font-semibold text-gray-600">
                {{ item.nama_akun }} ({{ item.persen }}%)
              </span>
              <span
                v-if="!isAlreadyAllocated"
                class="text-xs font-bold px-2 py-1 rounded-full bg-blue-100 text-blue-600"
                >Invested</span
              >
            </div>
            <MaksMoneyInput
              v-model="item.nominal"
              placeholder="0,00"
              :show-helper="false"
              :readonly="isAlreadyAllocated"
            />
          </div>

          <div
            class="bg-gradient-to-br from-cyan-50 to-cyan-100 border-2 border-dashed border-cyan-300 rounded-xl p-3.5! mt-2! shadow-md"
          >
            <div class="text-xs font-semibold text-cyan-600 mb-1.5!">
              Estimasi Pertumbuhan Aset
            </div>
            <div class="flex items-center justify-between">
              <span class="font-mono text-base font-bold text-cyan-600"
                >+ Rp {{ formatNum(totalDitahan) }}</span
              >
              <span class="text-lg">📈</span>
            </div>
          </div>
        </ContentCard>
      </div>

      <ContentCard variant="bordered" padding="normal" hoverable>
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
          <div
            class="flex flex-col sm:flex-row items-start sm:items-center gap-5 w-full lg:w-auto"
          >
            <div class="w-18 h-18 flex-shrink-0">
              <svg viewBox="0 0 80 80" class="w-full h-full">
                <circle cx="40" cy="40" r="30" fill="none" stroke="#e2e8f0" stroke-width="10" />
                <circle
                  cx="40"
                  cy="40"
                  r="30"
                  fill="none"
                  stroke="#0B7A9E"
                  stroke-width="10"
                  :stroke-dasharray="`${pctDibagikanArc} ${circumference - pctDibagikanArc}`"
                  stroke-linecap="round"
                  transform="rotate(-90 40 40)"
                />
                <circle
                  cx="40"
                  cy="40"
                  r="30"
                  fill="none"
                  stroke="#38bdf8"
                  stroke-width="10"
                  :stroke-dasharray="`${pctDitahanArc} ${circumference - pctDitahanArc}`"
                  stroke-linecap="round"
                  :transform="`rotate(${pctDibagikanDeg - 90} 40 40)`"
                />
                <circle
                  cx="40"
                  cy="40"
                  r="30"
                  fill="none"
                  stroke="#e2e8f0"
                  stroke-width="10"
                  :stroke-dasharray="`${pctSisaArc} ${circumference - pctSisaArc}`"
                  stroke-linecap="round"
                  :transform="`rotate(${pctDitahanEndDeg - 90} 40 40)`"
                />
                <text
                  x="40"
                  y="44"
                  text-anchor="middle"
                  font-size="11"
                  font-weight="800"
                  fill="#1e293b"
                >
                  100%
                </text>
              </svg>
            </div>
            <div class="flex flex-col gap-1.5">
              <div class="flex items-center gap-1.5 text-sm text-gray-600 font-medium">
                <span class="w-2 h-2 rounded-full bg-cyan-700"></span>
                Laba Dibagikan ({{ pctDibagikan.toFixed(0) }}%)
              </div>
              <div class="flex items-center gap-1.5 text-sm text-gray-600 font-medium">
                <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                Laba Ditahan ({{ pctDitahan.toFixed(0) }}%)
              </div>
              <div class="flex items-center gap-1.5 text-sm text-gray-600 font-medium">
                <span class="w-2 h-2 rounded-full bg-gray-200"></span>
                Selisih Alokasi ({{ pctSisa.toFixed(0) }}%)
              </div>
            </div>
          </div>

          <div
            class="rounded-xl p-4! min-w-[200px] w-full sm:w-auto border-2"
            :class="
              isBalanced
                ? 'bg-green-50 border-green-300'
                : 'bg-amber-50 border-amber-300'
            "
          >
            <div
              class="text-xs font-bold uppercase tracking-wider mb-2!"
              :class="isBalanced ? 'text-gray-500' : 'text-amber-700'"
            >
              STATUS VALIDASI
            </div>
            <div class="flex items-center gap-2!">
              <span class="text-base">{{ isBalanced ? '✅' : '⚠️' }}</span>
              <span
                class="text-sm font-bold"
                :class="isBalanced ? 'text-green-700' : 'text-amber-700'"
              >
                {{
                  isBalanced
                    ? isAlreadyAllocated
                      ? 'Sudah Disimpan'
                      : 'Siap Disinkronisasi'
                    : `Selisih: Rp ${formatNum(Math.abs(selisih))}`
                }}
              </span>
            </div>
          </div>
        </div>
      </ContentCard>

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
          v-if="isAlreadyAllocated"
          variant="warning-gradient"
          size="lg"
          @click="handleReset"
          :disabled="isSaving || isLoading"
          class="w-full sm:w-auto px-10! rounded-xl shadow-lg font-bold"
        >
          Reset Alokasi
        </BaseButton>
        <BaseButton
          variant="primary-gradient"
          size="lg"
          @click="handleSimpan"
          :disabled="isSaving || isLoading || !isBalanced || isAlreadyAllocated"
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
import { ref, computed, onMounted } from 'vue'
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
const bookOpen = ref(true)
const isLoading = ref(false)
const loadingStatus = ref(false)
const isSaving = ref(false)
const isAlreadyAllocated = ref(false)

const itemsDibagikan = ref([])
const itemsDitahan = ref([])

const totalDibagikan = computed(() =>
  itemsDibagikan.value.reduce((s, i) => s + (Number(i.nominal) || 0), 0),
)

const totalDitahan = computed(() =>
  itemsDitahan.value.reduce((s, i) => s + (Number(i.nominal) || 0), 0),
)

const totalAlokasi = computed(() => totalDibagikan.value + totalDitahan.value)
const selisih = computed(() => totalSaldo.value - totalAlokasi.value)
const isBalanced = computed(() => Math.abs(selisih.value) < 0.01)

const pctDibagikan = computed(() =>
  totalSaldo.value > 0 ? (totalDibagikan.value / totalSaldo.value) * 100 : 0,
)
const pctDitahan = computed(() =>
  totalSaldo.value > 0 ? (totalDitahan.value / totalSaldo.value) * 100 : 0,
)
const pctSisa = computed(() => Math.max(0, 100 - pctDibagikan.value - pctDitahan.value))

const circumference = 2 * Math.PI * 30
const pctDibagikanArc = computed(() => (pctDibagikan.value / 100) * circumference)
const pctDitahanArc = computed(() => (pctDitahan.value / 100) * circumference)
const pctSisaArc = computed(() => (pctSisa.value / 100) * circumference)
const pctDibagikanDeg = computed(() => (pctDibagikan.value / 100) * 360)
const pctDitahanEndDeg = computed(
  () => pctDibagikanDeg.value + (pctDitahan.value / 100) * 360,
)

const formatNum = (val) =>
  Number(val || 0).toLocaleString('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  })

const resetForm = () => {
  itemsDibagikan.value = []
  itemsDitahan.value = []
  totalSaldo.value = 0
  isAlreadyAllocated.value = false
}

const applyAllocation = (dibagikan, ditahan, totalLaba) => {
  totalSaldo.value = Number(totalLaba) || 0
  itemsDibagikan.value = (dibagikan || []).map((it) => ({
    kode_akun: it.kode_akun,
    nama_akun: it.nama_akun,
    kategori: it.kategori,
    persen: Number(it.persen) || 0,
    nominal: Number(it.nominal) || 0,
  }))
  itemsDitahan.value = (ditahan || []).map((it) => ({
    kode_akun: it.kode_akun,
    nama_akun: it.nama_akun,
    kategori: it.kategori,
    persen: Number(it.persen) || 0,
    nominal: Number(it.nominal) || 0,
  }))
}

const loadBookStatus = async (year) => {
  loadingStatus.value = true
  try {
    const res = await accountingService.checkAllocation(year)
    if (res?.success && res.data) {
      bookClosed.value = !!res.data.tutup_buku
      bookOpen.value = !bookClosed.value
      isAlreadyAllocated.value = !!res.data.alokasi_saved
      return bookClosed.value
    }
    bookClosed.value = false
    bookOpen.value = true
    isAlreadyAllocated.value = false
    return false
  } catch (e) {
    console.error('Gagal cek status:', e)
    bookClosed.value = false
    bookOpen.value = true
    isAlreadyAllocated.value = false
    return false
  } finally {
    loadingStatus.value = false
  }
}

const calculateAllocation = async (year) => {
  try {
    const res = await accountingService.calculateAllocation(year, null)
    if (res?.success && res.data) {
      applyAllocation(
        res.data.laba_dibagikan,
        res.data.laba_ditahan,
        res.data.total_laba,
      )
      isAlreadyAllocated.value = false
    }
  } catch (e) {
    console.error('Gagal hitung alokasi:', e)
    uiStore.error(e?.response?.data?.message || 'Gagal memuat perhitungan alokasi.')
  }
}

const loadExistingAllocation = async (year) => {
  try {
    const res = await accountingService.calculateAllocation(year, null)
    if (res?.success && res.data) {
      applyAllocation(
        res.data.laba_dibagikan,
        res.data.laba_ditahan,
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
    bookOpen.value = true
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
      ...itemsDibagikan.value.map((i) => ({
        kode_akun: i.kode_akun,
        nama_akun: i.nama_akun,
        kategori: i.kategori,
        persen: i.persen,
        nominal: Number(i.nominal) || 0,
      })),
      ...itemsDitahan.value.map((i) => ({
        kode_akun: i.kode_akun,
        nama_akun: i.nama_akun,
        kategori: i.kategori,
        persen: i.persen,
        nominal: Number(i.nominal) || 0,
      })),
    ]
    const res = await accountingService.saveAllocation(selectedTahun.value, items)
    if (res?.success) {
      isAlreadyAllocated.value = true
      uiStore.success(
        res.data?.message || `Alokasi laba tahun ${selectedTahun.value} berhasil disimpan.`,
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

const handleReset = async () => {
  isSaving.value = true
  try {
    await calculateAllocation(selectedTahun.value)
  } finally {
    isSaving.value = false
  }
}

const handleBack = () => router.push('/app/transaksi/tutup-buku')

onMounted(async () => {
  const qTahun = Number(route.query.tahun)
  if (qTahun) {
    selectedTahun.value = qTahun
    await handleYearChange(qTahun)
  }
})
</script>

<style scoped>
.w-18 {
  width: 4.5rem;
}

.h-18 {
  height: 4.5rem;
}
</style>
