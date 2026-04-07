<template>
  <div class="space-y-6">
    <!-- Laporan Form Card -->
    <ContentCard variant="elevated" padding="large">
      <!-- Header -->
      <div
        class="bg-gradient-to-r from-blue-500 via-blue-600 to-cyan-500 p-4 rounded-xl relative overflow-hidden -m-6 mt-[-1.5rem]"
      >
        <div
          class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-16 -mt-16"
        ></div>
        <div
          class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-10 rounded-full -ml-12 -mb-12"
        ></div>
        <div class="relative z-10">
          <h2 class="text-xl font-bold text-white mb-1">Laporan Pamsides</h2>
          <p class="text-blue-100 text-sm">Pilih parameter laporan yang ingin ditampilkan</p>
        </div>
        <div
          class="absolute top-4 right-4 bg-white/20 backdrop-blur-sm text-white px-2 py-1 rounded-full text-sm font-medium flex items-center border border-white/30"
        >
          <font-awesome-icon icon="info-circle" class="mr-1" />
          Wajib diisi semua
        </div>
      </div>

      <!-- Form -->
      <div class="pt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
        <SelectSearch
          v-model="selectedTahun"
          :options="tahunOptions"
          label="Tahun"
          placeholder="Pilih Tahun"
        />
        <SelectSearch
          v-model="selectedBulan"
          :options="bulanOptions"
          label="Bulan"
          placeholder="Pilih Bulan"
        />
        <SelectSearch
          v-model="selectedTanggal"
          :options="tanggalOptions"
          label="Tanggal"
          placeholder="Pilih Tanggal"
        />
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <SelectSearch
          v-model="selectedNamaLaporan"
          :options="namaLaporanOptions"
          label="Nama Laporan"
          placeholder="Pilih Jenis Laporan"
        />
        <SelectSearch
          v-model="selectedNamaSubLaporan"
          :options="namaSubLaporanOptions"
          label="Nama Sub Laporan"
          placeholder="Pilih Periode Laporan"
        />
      </div>

      <!-- Buttons -->
      <div class="flex flex-col sm:flex-row gap-3 sm:justify-end">
        <button
          @click="handlePreview"
          class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center gap-2"
        >
          <font-awesome-icon icon="eye" />
          Preview
        </button>
        <button
          @click="handleExcel"
          class="w-full sm:w-auto px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center gap-2"
        >
          <font-awesome-icon icon="file-export" />
          Excel
        </button>
        <button
          @click="handleSimpanSaldo"
          class="w-full sm:w-auto px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors flex items-center justify-center gap-2"
        >
          <font-awesome-icon icon="save" />
          Simpan Saldo
        </button>
      </div>
    </ContentCard>

    <!-- Quick Actions Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Preview Card -->
      <ContentCard variant="bordered" padding="normal" hoverable clickable @click="handlePreview">
        <div class="flex items-center">
          <div
            class="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center flex-shrink-0"
          >
            <font-awesome-icon icon="eye" class="text-white text-sm" />
          </div>
          <div class="ml-3">
            <h3 class="font-semibold text-gray-900 text-sm">Quick Preview</h3>
            <p class="text-sm text-gray-500">Lihat ringkasan laporan</p>
          </div>
        </div>
      </ContentCard>

      <!-- Export Card -->
      <ContentCard variant="bordered" padding="normal" hoverable clickable @click="handleExcel">
        <div class="flex items-center">
          <div
            class="w-8 h-8 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center flex-shrink-0"
          >
            <font-awesome-icon icon="file-export" class="text-white text-sm" />
          </div>
          <div class="ml-3">
            <h3 class="font-semibold text-gray-900 text-sm">Export Data</h3>
            <p class="text-sm text-gray-500">Download format Excel</p>
          </div>
        </div>
      </ContentCard>

      <!-- Save Balance Card -->
      <ContentCard
        variant="bordered"
        padding="normal"
        hoverable
        clickable
        @click="handleSimpanSaldo"
      >
        <div class="flex items-center">
          <div
            class="w-8 h-8 bg-gradient-to-br from-gray-500 to-gray-600 rounded-lg flex items-center justify-center flex-shrink-0"
          >
            <font-awesome-icon icon="save" class="text-white text-sm" />
          </div>
          <div class="ml-3">
            <h3 class="font-semibold text-gray-900 text-sm">Save Balance</h3>
            <p class="text-sm text-gray-500">Simpan saldo akhir</p>
          </div>
        </div>
      </ContentCard>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import ContentCard from '../../../components/ui/ContentCard.vue'
import SelectSearch from '../../../components/SelectSearch.vue'

defineOptions({
  name: 'PelaporanIndex',
})

const selectedTahun = ref('')
const selectedBulan = ref('')
const selectedTanggal = ref('')
const selectedNamaLaporan = ref('')
const selectedNamaSubLaporan = ref('')

const tahunOptions = ref([
  { id: '2026', text: '2026' },
  { id: '2025', text: '2025' },
  { id: '2024', text: '2024' },
  { id: '2023', text: '2023' },
  { id: '2022', text: '2022' },
  { id: '2021', text: '2021' },
  { id: '2020', text: '2020' },
  { id: '2019', text: '2019' },
  { id: '2018', text: '2018' },
  { id: '2017', text: '2017' },
  { id: '2016', text: '2016' },
  { id: '2015', text: '2015' },
])

const bulanOptions = ref([
  { id: '01', text: 'Januari' },
  { id: '02', text: 'Februari' },
  { id: '03', text: 'Maret' },
  { id: '04', text: 'April' },
  { id: '05', text: 'Mei' },
  { id: '06', text: 'Juni' },
  { id: '07', text: 'Juli' },
  { id: '08', text: 'Agustus' },
  { id: '09', text: 'September' },
  { id: '10', text: 'Oktober' },
  { id: '11', text: 'November' },
  { id: '12', text: 'Desember' },
])

const tanggalOptions = ref(
  Array.from({ length: 31 }, (_, i) => ({
    id: String(i + 1).padStart(2, '0'),
    text: String(i + 1),
  })),
)

const namaLaporanOptions = ref([
  { id: 'laba-rugi', text: 'Laba Rugi' },
  { id: 'neraca', text: 'Neraca' },
  { id: 'arus-kas', text: 'Arus Kas' },
  { id: 'perubahan-modal', text: 'Perubahan Modal' },
  { id: 'rekap-transaksi', text: 'Rekapitulasi Transaksi' },
  { id: 'jurnal-umum', text: 'Jurnal Umum' },
  { id: 'buku-besar', text: 'Buku Besar' },
  { id: 'laporan-penjualan', text: 'Laporan Penjualan' },
  { id: 'laporan-pembelian', text: 'Laporan Pembelian' },
  { id: 'laporan-stok', text: 'Laporan Stok Barang' },
  { id: 'laporan-piutang', text: 'Laporan Piutang Usaha' },
  { id: 'laporan-utang', text: 'Laporan Utang Usaha' },
])

const namaSubLaporanOptions = ref([
  { id: 'harian', text: 'Harian' },
  { id: 'mingguan', text: 'Mingguan' },
  { id: 'bulanan', text: 'Bulanan' },
  { id: 'kuartalan', text: 'Kuartalan' },
  { id: 'semester', text: 'Semester' },
  { id: 'tahunan', text: 'Tahunan' },
  { id: 'kumulatif', text: 'Kumulatif' },
  { id: 'per-periode', text: 'Per Periode' },
])

const handlePreview = () => {
  console.log('Preview clicked', {
    tahun: selectedTahun.value,
    bulan: selectedBulan.value,
    tanggal: selectedTanggal.value,
    namaLaporan: selectedNamaLaporan.value,
    namaSubLaporan: selectedNamaSubLaporan.value,
  })
}

const handleExcel = () => {
  console.log('Excel clicked', {
    tahun: selectedTahun.value,
    bulan: selectedBulan.value,
    tanggal: selectedTanggal.value,
    namaLaporan: selectedNamaLaporan.value,
    namaSubLaporan: selectedNamaSubLaporan.value,
  })
}

const handleSimpanSaldo = () => {
  console.log('Simpan Saldo clicked', {
    tahun: selectedTahun.value,
    bulan: selectedBulan.value,
    tanggal: selectedTanggal.value,
    namaLaporan: selectedNamaLaporan.value,
    namaSubLaporan: selectedNamaSubLaporan.value,
  })
}
</script>
