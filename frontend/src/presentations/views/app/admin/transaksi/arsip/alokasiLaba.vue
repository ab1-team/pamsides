<template>
  <div>
    <div class="text-sm text-gray-500 font-medium mb-3! flex items-center gap-1">
      <span>Reports</span>
      <span class="text-gray-400">›</span>
      <span class="text-cyan-600 font-bold">Alokasi Laba</span>
    </div>

    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6! gap-4">
      <div class="header-left">
        <h1 class="text-3xl sm:text-4xl font-bold text-gray-800">Alokasi Laba</h1>
        <h2 class="text-3xl sm:text-4xl font-bold text-cyan-600">Tahun {{ selectedTahun }}</h2>
      </div>
      <ContentCard variant="elevated" padding="normal" hoverable>
        <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1!">
          TOTAL SALDO TERSEDIA
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
          <span class="text-base font-bold text-gray-800">Laba Dibagikan</span>
        </div>

        <div class="mb-4!">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-1.5! gap-1">
            <span class="text-sm font-semibold text-gray-600">Utang Dividen Pemdes (45%)</span>
            <span class="text-xs font-bold px-2 py-1 rounded-full bg-green-100 text-green-600"
              >Valid</span
            >
          </div>
          <MaksMoneyInput v-model="labaDibagikan.dividen" placeholder="0,00" :show-helper="false" />
        </div>

        <div class="mb-4!">
          <div class="flex items-center justify-between mb-1.5!">
            <span class="text-sm font-semibold text-gray-600">Bantuan Sosial (5%)</span>
          </div>
          <MaksMoneyInput
            v-model="labaDibagikan.bantuanSosial"
            placeholder="0,00"
            :show-helper="false"
          />
        </div>

        <div class="mb-4!">
          <div class="flex items-center justify-between mb-1.5!">
            <span class="text-sm font-semibold text-gray-600">Bonus / Jasa Produksi (10%)</span>
          </div>
          <MaksMoneyInput v-model="labaDibagikan.bonus" placeholder="0,00" :show-helper="false" />
        </div>
      </ContentCard>

      <ContentCard variant="default" padding="normal" hoverable>
        <div class="flex items-center gap-2.5 mb-5! pb-4 border-b border-gray-100">
          <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm bg-teal-50">
            🏦
          </div>
          <span class="text-base font-bold text-gray-800">Laba Ditahan</span>
        </div>

        <div class="mb-4!">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-1.5! gap-1">
            <span class="text-sm font-semibold text-gray-600">Pemupukan Modal (20%)</span>
            <span class="text-xs font-bold px-2 py-1 rounded-full bg-blue-100 text-blue-600"
              >Invested</span
            >
          </div>
          <MaksMoneyInput
            v-model="labaDitahan.pemupukanModal"
            placeholder="0,00"
            :show-helper="false"
          />
        </div>

        <div class="mb-4!">
          <div class="flex items-center justify-between mb-1.5!">
            <span class="text-sm font-semibold text-gray-600">Cadangan Umum (15%)</span>
          </div>
          <MaksMoneyInput
            v-model="labaDitahan.cadanganUmum"
            placeholder="0,00"
            :show-helper="false"
          />
        </div>

        <div
          class="bg-gradient-to-br from-cyan-50 to-cyan-100 border-2 border-dashed border-cyan-300 rounded-xl p-3.5! mt-2! shadow-md"
        >
          <div class="text-xs font-semibold text-cyan-600 mb-1.5!">Estimasi Pertumbuhan Aset</div>
          <div class="flex items-center justify-between">
            <span class="font-mono text-base font-bold text-cyan-600"
              >+ Rp {{ formatNum(estimasiPertumbuhan) }}</span
            >
            <span class="text-lg">📈</span>
          </div>
        </div>
      </ContentCard>
    </div>

    <ContentCard variant="bordered" padding="normal" hoverable>
      <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5 w-full lg:w-auto">
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
                stroke-dasharray="113.1 75.4"
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
                stroke-dasharray="65.97 122.52"
                stroke-linecap="round"
                transform="rotate(42 40 40)"
              />
              <circle
                cx="40"
                cy="40"
                r="30"
                fill="none"
                stroke="#e2e8f0"
                stroke-width="10"
                stroke-dasharray="9.42 179.07"
                stroke-linecap="round"
                transform="rotate(161 40 40)"
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
              Laba Dibagikan (60%)
            </div>
            <div class="flex items-center gap-1.5 text-sm text-gray-600 font-medium">
              <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
              Laba Ditahan (35%)
            </div>
            <div class="flex items-center gap-1.5 text-sm text-gray-600 font-medium">
              <span class="w-2 h-2 rounded-full bg-gray-200"></span>
              Selisih Alokasi (5%)
            </div>
          </div>
        </div>

        <div
          class="bg-green-50 border-2 border-green-300 rounded-xl p-4! min-w-[200px] w-full sm:w-auto"
        >
          <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2!">
            STATUS VALIDASI
          </div>
          <div class="flex items-center gap-2!">
            <span class="text-base">✅</span>
            <span class="text-sm font-bold text-green-700">Siap Disinkronisasi</span>
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
        variant="primary-gradient"
        size="lg"
        @click="handleSimpan"
        class="w-full sm:w-auto px-10! rounded-xl shadow-lg shadow-blue-200/50 font-bold"
        icon="save"
      >
        Simpan Alokasi Laba
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import MaksMoneyInput from '@/presentations/components/MaksMoneyInput.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'

const selectedTahun = ref(2026)
const totalSaldo = ref(128450000)

const persen = {
  dividen: 0.45,
  bantuanSosial: 0.05,
  bonus: 0.1,
  pemupukanModal: 0.2,
  cadanganUmum: 0.15,
}

const labaDibagikan = ref({
  dividen: totalSaldo.value * persen.dividen,
  bantuanSosial: totalSaldo.value * persen.bantuanSosial,
  bonus: totalSaldo.value * persen.bonus,
})

const labaDitahan = ref({
  pemupukanModal: totalSaldo.value * persen.pemupukanModal,
  cadanganUmum: totalSaldo.value * persen.cadanganUmum,
})

const estimasiPertumbuhan = computed(
  () => labaDitahan.value.pemupukanModal + labaDitahan.value.cadanganUmum,
)

const formatNum = (val) =>
  Number(val).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 })

const handleBack = () => console.log('Kembali')
const handleSimpan = () => console.log('Simpan Alokasi Laba')
</script>

<style scoped>
.w-18 {
  width: 4.5rem;
}

.h-18 {
  height: 4.5rem;
}
</style>
