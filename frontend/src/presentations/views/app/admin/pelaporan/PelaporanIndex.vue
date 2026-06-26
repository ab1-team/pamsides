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
          </div>
        </div>
        <div class="space-y-3!">
          <div class="grid! grid-cols-1! md:grid-cols-2! lg:grid-cols-3! gap-6!">
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
              placeholder="--"
            />
          </div>

          <div class="grid! grid-cols-1! md:grid-cols-2! gap-6!">
            <SelectSearch
              v-model="selectedNamaLaporan"
              :options="namaLaporanOptions"
              label="Nama Laporan"
              placeholder="--"
            />
            <SelectSearch
              v-model="selectedNamaSubLaporan"
              :options="namaSubLaporanOptions"
              label="Nama Sub Laporan"
              placeholder="--"
            />
          </div>

          <div class="flex! flex-col! sm:flex-row! gap-4! sm:justify-end! pt-4!">
            <BaseButton
              variant="danger"
              size="md"
              @click="handleSimpanSaldo"
              class="px-5! rounded-xl! shadow-lg! shadow-amber-500/20! font-bold! tracking-wide!"
            >
              Simpan Saldo
            </BaseButton>
            <BaseButton
              variant="success"
              size="md"
              @click="handleExcel"
              class="px-5! rounded-xl! shadow-lg! shadow-emerald-500/20! font-bold! tracking-wide!"
            >
              Excel
            </BaseButton>
            <BaseButton
              variant="secondary"
              size="md"
              @click="handlePreview"
              class="px-5! rounded-xl! shadow-lg! shadow-blue-500/20! font-bold! tracking-wide!"
              
            >
              Preview
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
import { ref, onMounted, watch } from 'vue'
import pelaporanService from '@/services/pelaporan.service.js' 
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import SelectSearch from '@/presentations/components/SelectSearch.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'

const selectedTahun = ref('')
const selectedBulan = ref('')
const selectedTanggal = ref('')
const selectedNamaLaporan = ref('')
const selectedNamaSubLaporan = ref('')

const tahunOptions = ref([])
const namaLaporanOptions = ref([])
const namaSubLaporanOptions = ref([]) 

const bulanOptions = ref([
  { id: '01', text: '01. JANUARI' },
  { id: '02', text: '02. FEBRUARI' },
  { id: '03', text: '03. MARET' },
  { id: '04', text: '04. APRIL' },
  { id: '05', text: '05. MEI' },
  { id: '06', text: '06. JUNI' },
  { id: '07', text: '07. JULI' },
  { id: '08', text: '08. AGUSTUS' },
  { id: '09', text: '09. SEPTEMBER' },
  { id: '10', text: '10. OKTOBER' },
  { id: '11', text: '11. NOVEMBER' },
  { id: '12', text: '12. DESEMBER' },
])

const tanggalOptions = ref([
  { id: '', text: '---' },
  ...Array.from({ length: 31 }, (_, i) => ({
    id: String(i + 1).padStart(2, '0'),
    text: String(i + 1),
  }))
])

const fetchMasterFilterPelaporan = async () => {
  try {
    const res = await pelaporanService.getMasterFilter() 
    
    if (res.success) {
      namaLaporanOptions.value = res.data.laporan
      selectedNamaLaporan.value = ''

      const thnAwal = res.data.tahun_awal ? parseInt(res.data.tahun_awal) : new Date().getFullYear()
      const thnSekarang = new Date().getFullYear() 
      
      const listTahun = []
      for (let i = thnSekarang; i >= thnAwal; i--) {
        listTahun.push({ 
          id: String(i), // Disinkronkan dalam tipe data String agar dibaca komponen Select
          text: String(i) 
        })
      }
      tahunOptions.value = listTahun
      selectedTahun.value = String(thnSekarang)
    }
  } catch (error) {
    console.error('Gagal memuat master filter pelaporan:', error)
  }
}

const fetchSubLaporanDinamis = async (fileJenisLaporan) => {
  if (!fileJenisLaporan || fileJenisLaporan === '') {
    namaSubLaporanOptions.value = [
      { id: '', text: '---' }
    ]
    selectedNamaSubLaporan.value = ''
    return
  }

  try {
    const res = await pelaporanService.getSubLaporan(fileJenisLaporan)
    if (res.success) {
      if (res.data && res.data.length > 0) {
        namaSubLaporanOptions.value = res.data.map(sub => ({
          id: String(sub.value),
          text: sub.title
        }))
        selectedNamaSubLaporan.value = namaSubLaporanOptions.value[0].id
      } else {
        namaSubLaporanOptions.value = [
          { id: '', text: '---' }
        ]
        selectedNamaSubLaporan.value = ''
      }
    }
  } catch (error) {
    console.error(`Gagal memuat sub laporan untuk ${fileJenisLaporan}:`, error)
  }
}

watch(selectedNamaLaporan, (newFile) => {
  fetchSubLaporanDinamis(newFile)
})

onMounted(() => {
  fetchMasterFilterPelaporan()
  
  namaSubLaporanOptions.value = [
    { id: '', text: '---' }
  ]
  selectedNamaSubLaporan.value = ''
  selectedTanggal.value = ''

  const currentMonth = String(new Date().getMonth() + 1).padStart(2, '0')
  selectedBulan.value = currentMonth
})

const getFilterPayload = () => {
  return {
    tahun: selectedTahun.value,
    bulan: selectedBulan.value,
    tanggal: selectedTanggal.value,
    nama_laporan: selectedNamaLaporan.value,
    nama_sub_laporan: selectedNamaSubLaporan.value,
  }
}

const handlePreview = async () => {
  const payload = getFilterPayload()
  if (!payload.nama_laporan) {
    window.dispatchEvent(new CustomEvent('toast', {
      detail: { type: 'warning', message: 'Pilih nama laporan terlebih dahulu' },
    }))
    return
  }
  const query = new URLSearchParams({
    tahun: payload.tahun || '',
    bulan: payload.bulan || '',
    tanggal: payload.tanggal || '',
    nama_laporan: payload.nama_laporan || '',
    nama_sub_laporan: payload.nama_sub_laporan || '',
  }).toString()

  const previewUrl = `/app/pelaporan/preview?${query}`
  window.open(previewUrl, '_blank')
}

const handleExcel = async () => {
  try {
    const payload = getFilterPayload()
    const blobData = await pelaporanService.exportExcel(payload)
    
    const url = window.URL.createObjectURL(new Blob([blobData]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Laporan_${selectedNamaLaporan.value}_${selectedTahun.value}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Gagal mengunduh berkas Excel:', error)
  }
}

const handleSimpanSaldo = async () => {
  try {
    const payload = getFilterPayload()
    const res = await pelaporanService.simpanSaldo(payload)
    
    if (res.success) {
      console.log('Saldo akhir berhasil disimpan ke database:', res.message)
    }
  } catch (error) {
    console.error('Gagal menyimpan saldo akhir:', error)
  }
}
</script>