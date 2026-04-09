<template>
  <div class="space-y-10! pb-10!">
    <ContentCard variant="elevated" padding="large">
      <div class="relative!">
        <div
          class="relative! -m-6! mb-8! p-6! rounded-2xl! bg-linear-to-r! from-blue-500! via-blue-600! to-cyan-500! shadow-lg! shadow-blue-500/10! overflow-hidden!"
        >
          <div
            class="absolute! top-0! right-0! w-44! h-44! bg-white/10! rounded-full! -mr-20! -mt-20! blur-2xl!"
          ></div>
          <div
            class="absolute! bottom-0! left-0! w-32! h-32! bg-white/10! rounded-full! -ml-16! -mb-16! blur-xl!"
          ></div>
          <div
            class="relative! z-10! flex! flex-col! md:flex-row! md:items-center! md:justify-between! gap-4!"
          >
            <div>
              <h2 class="text-2xl! font-bold! text-white! mb-1!">Laporan Pamsides</h2>
              <p class="text-blue-100/80! text-sm!">
                Pilih parameter laporan yang ingin ditampilkan
              </p>
            </div>
            <div
              class="self-start! flex! items-center! px-4! py-2! rounded-xl! bg-white/10! backdrop-blur-md! border! border-white/20! text-white! text-sm! font-semibold! shadow-sm!"
            >
              <font-awesome-icon icon="info-circle" class="mr-2! opacity-80!" />
              Wajib diisi semua
            </div>
          </div>
        </div>
        <div class="space-y-3!">
          <div class="grid! grid-cols-1! md:grid-cols-2! lg:grid-cols-3! gap-6!">
            <SelectSearch
              v-model="selectedTahun"
              :options="tahunOptions"
              label="Tahun"
              placeholder="Pilih Tahun"
              icon="calendar"
            />
            <SelectSearch
              v-model="selectedBulan"
              :options="bulanOptions"
              label="Bulan"
              placeholder="Pilih Bulan"
              icon="calendar-days"
            />
            <SelectSearch
              v-model="selectedTanggal"
              :options="tanggalOptions"
              label="Tanggal"
              placeholder="Pilih Tanggal"
              icon="calendar-check"
            />
          </div>

          <div class="grid! grid-cols-1! md:grid-cols-2! gap-6!">
            <SelectSearch
              v-model="selectedNamaLaporan"
              :options="namaLaporanOptions"
              label="Nama Laporan"
              placeholder="Pilih Jenis Laporan"
              icon="file-lines"
            />
            <SelectSearch
              v-model="selectedNamaSubLaporan"
              :options="namaSubLaporanOptions"
              label="Nama Sub Laporan"
              placeholder="Pilih Periode Laporan"
              icon="clock-rotate-left"
            />
          </div>

          <div class="flex! flex-col! sm:flex-row! gap-4! sm:justify-end! pt-4!">
            <BaseButton
              variant="secondary"
              size="md"
              @click="handlePreview"
              class="px-5! rounded-xl! shadow-lg! shadow-blue-500/20! font-bold! tracking-wide!"
              icon="eye"
            >
              Preview Laporan
            </BaseButton>
            <BaseButton
              variant="success"
              size="md"
              @click="handleExcel"
              class="px-5! rounded-xl! shadow-lg! shadow-emerald-500/20! font-bold! tracking-wide!"
              icon="file-export"
            >
              Download Excel
            </BaseButton>
            <BaseButton
              variant="danger"
              size="md"
              @click="handleSimpanSaldo"
              class="px-5! rounded-xl! shadow-lg! shadow-amber-500/20! font-bold! tracking-wide!"
              icon="save"
            >
              Simpan Saldo
            </BaseButton>
          </div>
        </div>
      </div>
    </ContentCard>

    <div class="space-y-4!">
      <h3 class="text-sm! font-bold! text-slate-400! uppercase! tracking-widest! ml-1!">
        Aksi Cepat
      </h3>
      <div class="grid! grid-cols-1! md:grid-cols-3! gap-6!">
        <ContentCard
          variant="bordered"
          padding="normal"
          hoverable
          clickable
          @click="handlePreview"
          class="group! transition-all! duration-300!"
        >
          <div class="flex! items-center! gap-4!">
            <div
              class="w-12! h-12! bg-linear-to-br! from-blue-500! to-blue-600! rounded-2xl! flex! items-center! justify-center! shrink-0! shadow-lg! shadow-blue-500/20! group-hover:scale-110! transition-transform! duration-300!"
            >
              <font-awesome-icon icon="eye" class="text-white! text-lg!" />
            </div>
            <div>
              <h3 class="font-bold! text-slate-800!">Quick Preview</h3>
              <p class="text-sm! text-slate-500!">Lihat ringkasan laporan</p>
            </div>
          </div>
        </ContentCard>

        <ContentCard
          variant="bordered"
          padding="normal"
          hoverable
          clickable
          @click="handleExcel"
          class="group! transition-all! duration-300!"
        >
          <div class="flex! items-center! gap-4!">
            <div
              class="w-12! h-12! bg-linear-to-br! from-emerald-500! to-emerald-600! rounded-2xl! flex! items-center! justify-center! shrink-0! shadow-lg! shadow-emerald-500/20! group-hover:scale-110! transition-transform! duration-300!"
            >
              <font-awesome-icon icon="file-export" class="text-white! text-lg!" />
            </div>
            <div>
              <h3 class="font-bold! text-slate-800!">Export Data</h3>
              <p class="text-sm! text-slate-500!">Download format Excel</p>
            </div>
          </div>
        </ContentCard>

        <ContentCard
          variant="bordered"
          padding="normal"
          hoverable
          clickable
          @click="handleSimpanSaldo"
          class="group! transition-all! duration-300!"
        >
          <div class="flex! items-center! gap-4!">
            <div
              class="w-12! h-12! bg-linear-to-br! from-slate-600! to-slate-700! rounded-2xl! flex! items-center! justify-center! shrink-0! shadow-lg! shadow-slate-500/20! group-hover:scale-110! transition-transform! duration-300!"
            >
              <font-awesome-icon icon="save" class="text-white! text-lg!" />
            </div>
            <div>
              <h3 class="font-bold! text-slate-800!">Save Balance</h3>
              <p class="text-sm! text-slate-500!">Simpan saldo akhir</p>
            </div>
          </div>
        </ContentCard>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import ContentCard from '../../../components/ui/ContentCard.vue'
import SelectSearch from '../../../components/SelectSearch.vue'
import BaseButton from '../../../components/ui/BaseButton.vue'

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
