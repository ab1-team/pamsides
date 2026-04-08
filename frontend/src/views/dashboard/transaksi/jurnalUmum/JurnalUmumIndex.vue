<template>
  <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-4 lg:gap-5 items-start">
    <ContentCard variant="elevated" padding="large" hoverable>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div class="flex flex-col gap-0.5">
          <label class="text-sm mb-1">Tanggal Transaksi</label>
          <AppDatePicker
            v-model="form.tanggal"
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

      <div class="flex flex-col gap-0.5 mb-4">
        <label class="text-sm mb-1">Keterangan</label>
        <textarea
          v-model="form.keterangan"
          class="px-3 py-2 sm:py-2.5 border border-gray-300 bg-white text-sm text-gray-900 w-full rounded-md shadow-sm resize-none focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 placeholder:text-gray-400"
          placeholder="Tambahkan catatan detail mengenai transaksi ini..."
          rows="3"
        />
      </div>

      <div class="flex flex-col gap-0.5 mb-4">
        <label class="text-sm mb-1">Nominal</label>
        <MaksMoneyInput
          v-model="form.nominal"
          placeholder="0,00"
          :show-helper="true"
          helper-text="Contoh: 100.000,00 untuk 100 ribu"
        />
      </div>

      <div class="flex">
        <button
          class="ml-auto px-6 sm:px-8 py-2 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-lg font-semibold transition-all hover:from-gray-600 hover:to-gray-700 hover:shadow-lg hover:-translate-y-0.5 text-sm sm:text-base"
          @click="handleSubmit"
        >
          Simpan Transaksi
        </button>
      </div>
    </ContentCard>

    <div class="flex flex-col gap-4 lg:sticky lg:top-8">
      <ContentCard variant="elevated" padding="none" hoverable class="total-saldo-wrapper">
        <div class="total-saldo-card">
          <div class="text-xs text-white/60 font-semibold mb-1 relative z-10">
            Total Saldo Terkini
          </div>
          <div class="relative z-10 mb-4 flex items-center gap-1">
            <span class="text-xs font-bold">Rp.</span>
            <span class="text-xl font-black tracking-tight">{{ formatSaldo(totalSaldo) }}</span>
          </div>
          <hr class="border-t border-white/20" />
        </div>
      </ContentCard>

      <ContentCard variant="bordered" padding="normal" hoverable>
        <div class="text-sm mb-3 font-semibold">Filter Periode</div>

        <div class="mb-3">
          <SelectSearch
            v-model="filter.tahun"
            :options="tahunOptions.map((y) => ({ id: y, text: y }))"
            label="Tahun"
            placeholder="Pilih Tahun"
            icon="calendar-alt"
          />
        </div>

        <div class="mb-3">
          <SelectSearch
            v-model="filter.bulan"
            :options="bulanOptions.map((b) => ({ id: b.value, text: b.label }))"
            label="Bulan"
            placeholder="Pilih Bulan"
            icon="calendar"
          />
        </div>

        <div class="mb-3">
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

        <div class="flex gap-2">
          <button
            class="flex-1 px-3 py-2 border border-blue-600 bg-white text-blue-600 rounded-md font-medium mt-1 hover:bg-blue-600 hover:text-white text-sm transition-colors"
            @click="handleApplyFilter"
          >
            Terapkan Filter
          </button>
          <button
            class="px-3 py-2 border border-gray-600 bg-white text-gray-600 rounded-md font-medium mt-1 hover:bg-gray-600 hover:text-white text-sm transition-colors"
            @click="handleDetail"
          >
            Detail
          </button>
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
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import SelectSearch from '../../../../components/SelectSearch.vue'
import MaksMoneyInput from '../../../../components/MaksMoneyInput.vue'
import AppDatePicker from '../../../../components/AppDatePicker.vue'
import ContentCard from '../../../../components/ui/ContentCard.vue'
import { useCurrencyFormat } from '../../../../composables/useCurrencyFormat.js'

const form = ref({
  tanggal: new Date(),
  jenisTransaksi: '',
  sumberDana: '',
  disimpanKe: '',
  keterangan: '',
  nominal: null,
})

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

function handleApplyFilter() {
  console.log('Filter applied:', filter.value)
}

function handleDetail() {
  console.log('Show detail')
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
