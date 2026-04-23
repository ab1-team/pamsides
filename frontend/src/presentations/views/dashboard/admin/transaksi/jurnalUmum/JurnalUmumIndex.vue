<template>
  <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-4 lg:gap-6 items-start pb-10">
    <ContentCard variant="elevated" padding="large" hoverable>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
        <div class="flex flex-col gap-0.5">
          <AppDatePicker
            v-model="form.tanggal"
            label="Tanggal Transaksi"
            placeholder="Pilih tanggal transaksi"
            @date-select="(date) => (form.tanggal = date)"
          />
        </div>

        <div class="flex flex-col gap-0.5">
          <SelectSearch
            v-model="form.jenisTransaksi"
            :options="[
              { id: '', text: 'Pilih Jenis Transaksi' },
              { id: 'pemasukan', text: 'Pemasukan' },
              { id: 'pengeluaran', text: 'Pengeluaran' },
              { id: 'transfer', text: 'Transfer' },
            ]"
            label="Jenis Transaksi"
            placeholder="Pilih Jenis Transaksi"
            icon="list"
          />
        </div>

        <div class="flex flex-col gap-0.5">
          <SelectSearch
            v-model="form.sumberDana"
            :options="[
              { id: '', text: 'Pilih Sumber' },
              { id: 'kas', text: 'Kas' },
              { id: 'bank', text: 'Bank' },
              { id: 'iuran', text: 'Iuran Pelanggan' },
            ]"
            label="Sumber Dana"
            placeholder="Pilih Sumber"
            icon="wallet"
          />
        </div>

        <div class="flex flex-col gap-0.5">
          <SelectSearch
            v-model="form.disimpanKe"
            :options="[
              { id: '', text: 'Pilih Tujuan' },
              { id: 'kas', text: 'Kas' },
              { id: 'bank', text: 'Rekening Bank' },
              { id: 'operasional', text: 'Dana Operasional' },
            ]"
            label="Disimpan Ke"
            placeholder="Pilih Tujuan"
            icon="archive"
          />
        </div>
      </div>

      <BaseInput
        v-model="form.keterangan"
        type="textarea"
        label="Keterangan"
        placeholder="Tambahkan catatan detail mengenai transaksi ini..."
        :rows="3"
        class="mb-2"
      />

      <div class="flex flex-col gap-0.5 mb-2!">
        <MaksMoneyInput
          v-model="form.nominal"
          placeholder="0,00"
          label="Nominal"
          :show-helper="true"
        />
      </div>

      <div class="flex mt-4 justify-end">
        <BaseButton
          variant="secondary"
          size="md"
          @click="handleSubmit"
          class="ml-auto px-5! py-2! rounded-xl shadow-lg shadow-blue-200/50"
          icon="save"
        >
          Simpan Transaksi
        </BaseButton>
      </div>
    </ContentCard>

    <div class="flex flex-col gap-6 lg:sticky lg:top-8">
      <ContentCard variant="elevated" padding="none" hoverable class="total-saldo-wrapper">
        <div class="total-saldo-card">
          <div class="text-xs text-white/60 font-semibold mb-1 relative z-10">
            Total Saldo Terkini
          </div>
          <div class="relative z-10 mb-2! flex items-center gap-1">
            <span class="text-xs font-bold">Rp.</span>
            <span class="text-xl font-black tracking-tight">{{ formatSaldo(totalSaldo) }}</span>
          </div>
          <hr class="border-t border-white/20" />
        </div>
      </ContentCard>

      <ContentCard variant="bordered" padding="normal" hoverable>
        <div class="flex items-center gap-3 mb-2! pb-3 border-b border-slate-100/80">
          <div
            class="w-9 h-9 rounded-xl bg-linear-to-br from-indigo-500 to-blue-600 text-white flex items-center justify-center shadow-lg shadow-blue-200"
          >
            <font-awesome-icon icon="filter" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-slate-800">Filter Periode</h3>
            <p class="text-[10px] text-slate-500 font-medium uppercase tracking-wider">
              Cari & Urutkan Data
            </p>
          </div>
        </div>
        <div class="mb-2!">
          <SelectSearch
            v-model="filter.tahun"
            :options="tahunOptions.map((y) => ({ id: y, text: y }))"
            label="Tahun"
            placeholder="Pilih Tahun"
            icon="calendar-alt"
          />
        </div>

        <div class="mb-2!">
          <SelectSearch
            v-model="filter.bulan"
            :options="bulanOptions.map((b) => ({ id: b.value, text: b.label }))"
            label="Bulan"
            placeholder="Pilih Bulan"
            icon="calendar"
          />
        </div>

        <div class="mb-2!">
          <SelectSearch
            v-model="filter.tanggal"
            :options="[
              { id: '', text: 'Semua Tanggal' },
              ...Array.from({ length: 31 }, (_, i) => ({ id: i + 1, text: i + 1 })),
            ]"
            label="Tanggal"
            placeholder="Pilih Tanggal"
            icon="calendar-day"
          />
        </div>

        <div class="flex gap-2 mt-4! justify-end">
          <BaseButton
            variant="danger"
            size="md"
            @click="handleDetail"
            class="rounded-xl border border-slate-200 hover:bg-slate-50 py-2!"
          >
            Detail Transaksi
          </BaseButton>
        </div>
      </ContentCard>

      <ContentCard variant="minimal" padding="normal" hoverable>
        <div class="flex gap-3 items-start">
          <div class="text-base flex-shrink-0 mt-0.5">ℹ️</div>
          <div class="text-xs text-slate-600 leading-relaxed">
            <strong>Butuh Bantuan?</strong><br />
            Pastikan nominal sesuai bukti transaksi fisik. Periksa saldo kas sebelum menyimpan.
          </div>
        </div>
      </ContentCard>
    </div>
    <DetailModal :show="showDetail" @close="showDetail = false" @openCetak="handleOpenCetak" />
    <CetakTrxModal :show="showCetak" @close="showCetak = false" />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import DetailModal from './partials/DetailModal.vue'
import CetakTrxModal from './partials/CetakTrxModal.vue'
import BaseInput from '@/presentations/components/ui/BaseInput.vue'
import SelectSearch from '@/presentations/components/SelectSearch.vue'
import MaksMoneyInput from '@/presentations/components/MaksMoneyInput.vue'
import AppDatePicker from '@/presentations/components/AppDatePicker.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import { useCurrencyFormat } from '@/composables/useCurrencyFormat.js'

const form = ref({
  tanggal: new Date(),
  jenisTransaksi: '',
  sumberDana: '',
  disimpanKe: '',
  keterangan: '',
  nominal: null,
})

const showDetail = ref(false)
const showCetak = ref(false)

const totalSaldo = ref(24580000)

const filter = ref({
  tahun: new Date().getFullYear(),
  bulan: '',
  tanggal: '',
})

const tahunOptions = computed(() => {
  const current = new Date().getFullYear()
  return Array.from({ length: 5 }, (_, i) => current - i)
})

const bulanOptions = [
  { value: 'Januari', label: 'Januari' },
  { value: 'Februari', label: 'Februari' },
  { value: 'Maret', label: 'Maret' },
  { value: 'April', label: 'April' },
  { value: 'Mei', label: 'Mei' },
  { value: 'Juni', label: 'Juni' },
  { value: 'Juli', label: 'Juli' },
  { value: 'Agustus', label: 'Agustus' },
  { value: 'September', label: 'September' },
  { value: 'Oktober', label: 'Oktober' },
  { value: 'November', label: 'November' },
  { value: 'Desember', label: 'Desember' },
]

function formatSaldo(val) {
  return useCurrencyFormat(val)
}

function handleSubmit() {
  if (!form.value.tanggal) {
    alert('Tanggal transaksi harus diisi.')
    return
  }
  if (!form.value.jenisTransaksi || !form.value.nominal) {
    alert('Mohon lengkapi data transaksi.')
    return
  }

  const payload = {
    ...form.value,
    tanggal: form.value.tanggal.toISOString().split('T')[0],
  }
  console.log('Simpan transaksi:', payload)
}

function handleDetail() {
  showDetail.value = true
}

function handleOpenCetak() {
  showDetail.value = false
  showCetak.value = true
}
</script>

<style scoped>
.total-saldo-card {
  background: linear-gradient(to bottom right, #0284c7, #0c4a6e);
  border-radius: 1rem;
  padding: 1rem 1.25rem;
  color: white;
  position: relative;
  overflow: hidden;
}

.total-saldo-card::before {
  content: '';
  position: absolute;
  top: -2rem;
  right: -2rem;
  width: 7rem;
  height: 7rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
}

.total-saldo-wrapper {
  background: transparent !important;
}

.total-saldo-wrapper:hover {
  transform: translateY(-2px);
  box-shadow:
    0 20px 25px -5px rgba(0, 0, 0, 0.1),
    0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
</style>
