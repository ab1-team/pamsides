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
:options="jenisTransaksiOptions"
            label="Jenis Transaksi"
            placeholder="Pilih Jenis Transaksi"
            icon="list"
          />
        </div>

        <div class="flex flex-col gap-0.5">
          <SelectSearch
            v-model="form.sumberDana"
            :options="sumberDanaOptions"
            label="Kode Akun Debet"
            placeholder="Pilih Akun Debet"
            icon="wallet"
          />
        </div>

        <div class="flex flex-col gap-0.5">
          <SelectSearch
            v-model="form.disimpanKe"
            :options="disimpanKeOptions"
            label="Kode Akun Kredit"
            placeholder="Pilih Akun Kredit"
            icon="archive"
          />
        </div>
      </div>

      <template v-if="isInventaris">
        <!-- Inputan khusus inventaris -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-2">
          <div class="flex flex-col gap-0.5">
            <BaseInput
              v-model="form.relasi"
              label="Relasi"
              placeholder="Masukkan relasi transaksi"
            />
          </div>
          <div class="flex flex-col gap-0.5">
            <BaseInput
              v-model="form.namaBarang"
              label="Nama Barang"
              placeholder="Masukkan nama barang"
            />
          </div>
          <div class="flex flex-col gap-0.5">
            <BaseInput
              v-model="form.jumlahUnit"
              type="number"
              label="Jml. Unit"
              placeholder="0"
            />
          </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-2">
          <div class="flex flex-col gap-0.5">
            <MaksMoneyInput
              v-model="form.hargaSatuan"
              placeholder="0,00"
              label="Harga Satuan"
              :show-helper="true"
            />
          </div>
          <div class="flex flex-col gap-0.5">
            <BaseInput
              v-model="form.umurEkonomis"
              type="number"
              label="Umur Eko. (bulan)"
              placeholder="0"
            />
          </div>
          <div class="flex flex-col gap-0.5">
            <MaksMoneyInput
              v-model="form.hargaPerolehan"
              placeholder="0,00"
              label="Harga Perolehan"
              :show-helper="true"
            />
          </div>
        </div>
      </template>
      
      <template v-else>
        <!-- Inputan biasa -->
        <div v-if="showRelasi" class="mb-2">
          <BaseInput
            v-model="form.relasi"
            label="Relasi"
            placeholder="Masukkan relasi transaksi"
          />
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
      </template>

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
          <div class="relative z-10 mb-2! flex items-center gap-1 text-white">
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
            :options="tahunOptions"
            label="Tahun"
            placeholder="Pilih Tahun"
            icon="calendar-alt"
          />
        </div>

        <div class="mb-2!">
          <SelectSearch
            v-model="filter.bulan"
            :options="bulanOptionsFiltered"
            label="Bulan"
            placeholder="Pilih Bulan"
            icon="calendar"
          />
        </div>

        <div class="mb-2!">
          <SelectSearch
            v-model="filter.tanggal"
            :options="tanggalOptionsFiltered"
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
    <DetailModal 
      :show="showDetail" 
      :transactions="transactions" 
      :loading="loading" 
      :title="modalTitle"
      @close="showDetail = false" 
      @openCetak="handleOpenCetak"
      @delete="deleteTransaction"
    />
    <CetakTrxModal 
      :show="showCetak" 
      :transactions="transactions" 
      :loading="loading" 
      :title="modalTitle"
      @close="showCetak = false"
      @delete="deleteTransaction"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import Swal from 'sweetalert2'
import DetailModal from './partials/DetailModal.vue'
import CetakTrxModal from './partials/CetakTrxModal.vue'
import BaseInput from '@/presentations/components/ui/BaseInput.vue'
import SelectSearch from '@/presentations/components/SelectSearch.vue'
import MaksMoneyInput from '@/presentations/components/MaksMoneyInput.vue'
import AppDatePicker from '@/presentations/components/AppDatePicker.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import { useCurrencyFormat } from '@/composables/useCurrencyFormat.js'
import api from '@/utils/axios'

const form = ref({
  tanggal: new Date(),
  jenisTransaksi: '',
  sumberDana: '',
  disimpanKe: '',
  keterangan: '',
  nominal: null,
  relasi: '',
  namaBarang: '',
  jumlahUnit: 0,
  hargaSatuan: null,
  umurEkonomis: 0,
  hargaPerolehan: null,
})

const transactions = ref([])
const loading = ref(false)
const pagination = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  last_page: 1,
})
const accounts = ref([])

const showDetail = ref(false)
const showCetak = ref(false)

const totalSaldo = ref(0)

const filter = ref({
  tahun: new Date().getFullYear(),
  bulan: '',
  tanggal: '',
})

const filterParams = computed(() => {
  const params = {}
  
  // Jika tahun tidak dipilih, return params kosong (tanpa filter tanggal)
  if (!filter.value.tahun) {
    return params
  }
  
  let tgl_dari = `${filter.value.tahun}-01-01`
  let tgl_sampai = `${filter.value.tahun}-12-31`

  if (filter.value.bulan) {
    const bulanNum = bulanMap[filter.value.bulan]
    if (bulanNum) {
      tgl_dari = `${filter.value.tahun}-${bulanNum}-01`
      const lastDay = new Date(filter.value.tahun, parseInt(bulanNum), 0).getDate()
      tgl_sampai = `${filter.value.tahun}-${bulanNum}-${lastDay}`
    }
  }

  if (filter.value.tanggal) {
    const tanggal = filter.value.tanggal.toString().padStart(2, '0')
    const bulanNum = filter.value.bulan ? bulanMap[filter.value.bulan] : '01'
    tgl_dari = `${filter.value.tahun}-${bulanNum}-${tanggal}`
    tgl_sampai = tgl_dari
  }

  params.tgl_dari = tgl_dari
  params.tgl_sampai = tgl_sampai
  return params
})

const selectedJenisTransaksi = computed(() => {
  if (!form.value.jenisTransaksi) return null
  return jenisTransaksiOptions.value.find(item => item.id === form.value.jenisTransaksi)
})

const modalTitle = computed(() => {
  let title = ''
  
  // Jenis transaksi
  if (selectedJenisTransaksi.value) {
    title += selectedJenisTransaksi.value.text
  }
  
  // Sumber dana
  if (sumberDanaText.value) {
    const namaSumber = sumberDanaText.value.split(' - ').slice(1).join(' - ') || sumberDanaText.value
    title += ` - ${namaSumber}`
  }
  
  // Filter waktu
  const parts = []
  if (filter.value.tahun) {
    parts.push(`Tahun ${filter.value.tahun}`)
  }
  if (filter.value.bulan) {
    parts.push(`Bulan ${filter.value.bulan}`)
  }
  if (filter.value.tanggal) {
    parts.push(`Tanggal ${filter.value.tanggal}`)
  }
  
  if (parts.length > 0) {
    title += ` (${parts.join(', ')})`
  }
  
  return title || 'Detail Transaksi'
})

const showRelasi = computed(() => {
  // Tampilkan inputan relasi jika sumber dana atau disimpan ke dimulai dengan "1.1.01."
  const sumber = form.value.sumberDana
  const tujuan = form.value.disimpanKe
  
  return (sumber && sumber.startsWith('1.1.01.')) || (tujuan && tujuan.startsWith('1.1.01.'))
})

// ID jenis transaksi yang menggunakan inputan inventaris (sesuaikan dengan database)
// Asumsi: 1 = Aset Masuk, 2 = Aset Keluar, 3 = Pemindahan Aset dan Saldo
const inventarisJenisIds = [1, 3] // Aset Masuk dan Pemindahan Aset dan Saldo

const isInventaris = computed(() => {
  // Khusus inventaris: kode akun 1.2.01.04, disimpan ke, jenis transaksi sesuai id
  const tujuan = form.value.disimpanKe
  const jenisId = form.value.jenisTransaksi
  
  return tujuan === '1.2.01.04' && inventarisJenisIds.includes(jenisId)
})

const sumberDanaOptions = computed(() => {
  const baseOptions = [{ id: '', text: 'Pilih Sumber' }]
  if (!selectedJenisTransaksi.value) {
    // Jika belum pilih jenis transaksi, dropdown kosong (hanya placeholder)
    return baseOptions
  }
  
  const jenisText = selectedJenisTransaksi.value.text.toLowerCase().replace(/\s+/g, '_')
  
  if (jenisText === 'aset_masuk') {
    // Aset masuk: sumber dana hanya akun dengan kode_akun tidak dimulai dari 1 (selain aset)
    return [
      ...baseOptions,
      ...accounts.value
        .filter(acc => !acc.kode_akun.startsWith('1'))
        .map(acc => ({ id: acc.kode_akun, text: `${acc.kode_akun} - ${acc.nama_akun}` }))
    ]
  } else if (jenisText === 'aset_keluar') {
    // Aset keluar: sumber dana semua akun
    return [
      ...baseOptions,
      ...accounts.value.map(acc => ({ id: acc.kode_akun, text: `${acc.kode_akun} - ${acc.nama_akun}` }))
    ]
  } else if (jenisText === 'simpan_saldo') {
    // Simpan saldo: semua akun
    return [
      ...baseOptions,
      ...accounts.value.map(acc => ({ id: acc.kode_akun, text: `${acc.kode_akun} - ${acc.nama_akun}` }))
    ]
  }
  
  // Default: semua akun (jika teks tidak cocok)
  return [
    ...baseOptions,
    ...accounts.value.map(acc => ({ id: acc.kode_akun, text: `${acc.kode_akun} - ${acc.nama_akun}` }))
  ]
})

const sumberDanaText = computed(() => {
  if (!form.value.sumberDana) return ''
  const option = sumberDanaOptions.value.find(opt => opt.id === form.value.sumberDana)
  return option ? option.text : ''
})

const disimpanKeText = computed(() => {
  if (!form.value.disimpanKe) return ''
  const option = disimpanKeOptions.value.find(opt => opt.id === form.value.disimpanKe)
  return option ? option.text : ''
})

const disimpanKeLabel = computed(() => {
  const jenisText = selectedJenisTransaksi.value ? selectedJenisTransaksi.value.text.toLowerCase().replace(/\s+/g, '_') : ''
  return jenisText === 'aset_keluar' ? 'Keperluan' : 'Disimpan Ke'
})

const disimpanKeOptions = computed(() => {
  const baseOptions = [{ id: '', text: 'Pilih Tujuan' }]
  if (!selectedJenisTransaksi.value) {
    // Jika belum pilih jenis transaksi, dropdown kosong (hanya placeholder)
    return baseOptions
  }
  
  const jenisText = selectedJenisTransaksi.value.text.toLowerCase().replace(/\s+/g, '_')
  
  if (jenisText === 'aset_masuk') {
    // Aset masuk: disimpan ke semua akun
    return [
      ...baseOptions,
      ...accounts.value.map(acc => ({ id: acc.kode_akun, text: `${acc.kode_akun} - ${acc.nama_akun}` }))
    ]
  } else if (jenisText === 'aset_keluar') {
    // Aset keluar: disimpan ke hanya akun dengan kode_akun tidak dimulai dari 1 (selain aset)
    return [
      ...baseOptions,
      ...accounts.value
        .filter(acc => !acc.kode_akun.startsWith('1'))
        .map(acc => ({ id: acc.kode_akun, text: `${acc.kode_akun} - ${acc.nama_akun}` }))
    ]
  } else if (jenisText === 'simpan_saldo') {
    // Simpan saldo: semua akun
    return [
      ...baseOptions,
      ...accounts.value.map(acc => ({ id: acc.kode_akun, text: `${acc.kode_akun} - ${acc.nama_akun}` }))
    ]
  }
  
  // Default: semua akun (jika teks tidak cocok)
  return [
    ...baseOptions,
    ...accounts.value.map(acc => ({ id: acc.kode_akun, text: `${acc.kode_akun} - ${acc.nama_akun}` }))
  ]
})

const jenisTransaksiOptions = ref([
  { id: '', text: 'Pilih Jenis Transaksi' },
])

const fetchJenisTransaksi = async () => {
  try {
    const response = await api.get('/jenis-transactions')
    if (response.data.success) {
      jenisTransaksiOptions.value = [
        { id: '', text: 'Pilih Jenis Transaksi' },
        ...response.data.data,
      ]
    }
  } catch (error) {
    console.error('Gagal mengambil jenis transaksi:', error)
  }
}

const fetchAccounts = async () => {
  try {
    const response = await api.get('/accounts')
    if (response.data.success) {
      accounts.value = response.data.data
    }
  } catch (error) {
    console.error('Gagal mengambil data akun:', error)
  }
}

const fetchTotalSaldo = async () => {
  try {
    const response = await api.get('/amount/total-saldo')
    if (response.data.success) {
      totalSaldo.value = response.data.data.saldo
    }
  } catch (error) {
    console.error('Gagal mengambil total saldo:', error)
    totalSaldo.value = 0
  }
}

const regenerateAmount = async () => {
  const now = new Date()
  try {
    await api.post('/generate-amount', {
      bulan: now.getMonth() + 1,
      tahun: now.getFullYear()
    })
  } catch (error) {
    console.error('Gagal regenerate amount:', error)
  }
}

const bulanMap = {
  'Januari': '01',
  'Februari': '02',
  'Maret': '03',
  'April': '04',
  'Mei': '05',
  'Juni': '06',
  'Juli': '07',
  'Agustus': '08',
  'September': '09',
  'Oktober': '10',
  'November': '11',
  'Desember': '12',
}

const fetchTransactions = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page: page,
      per_page: pagination.value.per_page,
      ...filterParams.value,
    }

    const response = await api.get('/transactions', { params })
    if (response.data.success) {
      transactions.value = response.data.data.data
      pagination.value = {
        current_page: response.data.data.current_page,
        per_page: response.data.data.per_page,
        total: response.data.data.total,
        last_page: response.data.data.last_page,
      }
    }
  } catch (error) {
    console.error('Gagal mengambil data transaksi:', error)
  } finally {
    loading.value = false
  }
}

const fetchAllTransactions = async () => {
  loading.value = true
  try {
    const params = {
      per_page: 1000,
      ...filterParams.value,
    }

    const response = await api.get('/transactions', { params })
    if (response.data.success) {
      transactions.value = response.data.data.data
    }
  } catch (error) {
    console.error('Gagal mengambil data transaksi:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchJenisTransaksi()
  fetchAccounts()
  fetchTransactions()
  fetchTotalSaldo()
})

watch(
  () => [filter.value.tahun, filter.value.bulan, filter.value.tanggal],
  () => {
    fetchTransactions()
  },
  { deep: true }
)

watch(
  () => form.value.jenisTransaksi,
  () => {
    // Reset sumber dana dan disimpan ke saat jenis transaksi berubah
    form.value.sumberDana = ''
    form.value.disimpanKe = ''
    form.value.keterangan = ''
  }
)

watch(
  () => [form.value.sumberDana, form.value.disimpanKe],
  () => {
    // Generate keterangan otomatis
    if (form.value.sumberDana && form.value.disimpanKe) {
      // Ambil nama akun saja (tanpa kode)
      const sumberNama = sumberDanaText.value.split(' - ').slice(1).join(' - ') || sumberDanaText.value
      const tujuanNama = disimpanKeText.value.split(' - ').slice(1).join(' - ') || disimpanKeText.value
      
      if (sumberNama && tujuanNama) {
        form.value.keterangan = `${sumberNama} disimpan ke ${tujuanNama}`
      }
    }
  },
  { immediate: true }
)

const tahunOptions = computed(() => {
  const current = new Date().getFullYear()
  return [
    { id: '', text: 'Semua Tahun' },
    ...Array.from({ length: 5 }, (_, i) => ({ id: current - i, text: current - i }))
  ]
})

const bulanOptions = [
  { id: '', text: 'Semua Bulan' },
  { id: 'Januari', text: 'Januari' },
  { id: 'Februari', text: 'Februari' },
  { id: 'Maret', text: 'Maret' },
  { id: 'April', text: 'April' },
  { id: 'Mei', text: 'Mei' },
  { id: 'Juni', text: 'Juni' },
  { id: 'Juli', text: 'Juli' },
  { id: 'Agustus', text: 'Agustus' },
  { id: 'September', text: 'September' },
  { id: 'Oktober', text: 'Oktober' },
  { id: 'November', text: 'November' },
  { id: 'Desember', text: 'Desember' },
]

const bulanOptionsFiltered = computed(() => {
  if (!filter.value.tahun) {
    // Jika tahun tidak dipilih, dropdown bulan kosong (hanya placeholder)
    return [{ id: '', text: 'Pilih Tahun Dulu' }]
  }
  return bulanOptions
})

const tanggalOptionsFiltered = computed(() => {
  if (!filter.value.tahun || !filter.value.bulan) {
    // Jika tahun atau bulan tidak dipilih, dropdown tanggal kosong (hanya placeholder)
    return [{ id: '', text: 'Pilih Bulan Dulu' }]
  }
  return [
    { id: '', text: 'Semua Tanggal' },
    ...Array.from({ length: 31 }, (_, i) => ({ id: i + 1, text: i + 1 }))
  ]
})

function formatSaldo(val) {
  return useCurrencyFormat(val)
}

function formatDate(dateString) {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

function formatCurrency(amount) {
  if (!amount) return '0.00'
  return new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount)
}

async function deleteTransaction(id) {
  const result = await Swal.fire({
    title: 'Konfirmasi Hapus',
    text: 'Apakah Anda yakin ingin menghapus transaksi ini?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
  })

  if (result.isConfirmed) {
    try {
      const response = await api.delete(`/transactions/${id}`)
      if (response.data.success) {
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'success',
          title: 'Transaksi berhasil dihapus!',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
        })
        // Refresh data transaksi
        fetchTransactions()
        fetchAllTransactions()
        fetchTotalSaldo()
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Gagal menghapus transaksi',
          text: response.data.message || 'Terjadi kesalahan pada server.',
          confirmButtonText: 'OK',
        })
      }
    } catch (error) {
      console.error('Error deleting transaction:', error)
      let errorMessage = 'Terjadi kesalahan saat menghapus transaksi.'
      if (error.response && error.response.data && error.response.data.message) {
        errorMessage = error.response.data.message
      }
      Swal.fire({
        icon: 'error',
        title: 'Gagal menghapus transaksi',
        text: errorMessage,
        confirmButtonText: 'OK',
      })
    }
  }
}

async function handleSubmit() {
  if (!form.value.tanggal) {
    Swal.fire({
      icon: 'error',
      title: 'Validasi Gagal',
      text: 'Tanggal transaksi harus diisi.',
      confirmButtonText: 'OK'
    })
    return
  }
  if (!form.value.jenisTransaksi) {
    Swal.fire({
      icon: 'error',
      title: 'Validasi Gagal',
      text: 'Jenis transaksi harus dipilih.',
      confirmButtonText: 'OK'
    })
    return
  }
  if (!form.value.sumberDana) {
    Swal.fire({
      icon: 'error',
      title: 'Validasi Gagal',
      text: 'Sumber dana harus dipilih.',
      confirmButtonText: 'OK'
    })
    return
  }
  if (!form.value.disimpanKe) {
    Swal.fire({
      icon: 'error',
      title: 'Validasi Gagal',
      text: 'Disimpan ke/keperluan harus dipilih.',
      confirmButtonText: 'OK'
    })
    return
  }
  if (!form.value.nominal || form.value.nominal <= 0) {
    Swal.fire({
      icon: 'error',
      title: 'Validasi Gagal',
      text: 'Nominal harus diisi dan lebih dari 0.',
      confirmButtonText: 'OK'
    })
    return
  }

  const payload = {
    tgl_transaksi: form.value.tanggal.toISOString().split('T')[0],
    account_debet: form.value.sumberDana,
    account_kredit: form.value.disimpanKe,
    transaction_group: form.value.jenisTransaksi,
    keterangan_transaksi: form.value.keterangan,
    relasi: form.value.relasi,
    saldo: form.value.nominal,
  }

  try {
    const response = await api.post('/transactions', payload)
    if (response.data.success) {
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Transaksi berhasil disimpan!',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      })
      form.value = {
        tanggal: new Date(),
        jenisTransaksi: '',
        sumberDana: '',
        disimpanKe: '',
        keterangan: '',
        nominal: null,
        relasi: '',
        namaBarang: '',
        jumlahUnit: 0,
        hargaSatuan: null,
        umurEkonomis: 0,
        hargaPerolehan: null,
      }
      fetchTransactions()
      fetchTotalSaldo()
      regenerateAmount()
    } else {
      // Notifikasi gagal dengan detail error
      Swal.fire({
        icon: 'error',
        title: 'Gagal menyimpan transaksi',
        text: response.data.message || 'Terjadi kesalahan pada server.',
        confirmButtonText: 'OK'
      })
    }
  } catch (error) {
    console.error('Error saving transaction:', error)
    let errorMessage = 'Terjadi kesalahan saat menyimpan transaksi.'
    if (error.response && error.response.data && error.response.data.message) {
      errorMessage = error.response.data.message
    } else if (error.message) {
      errorMessage = error.message
    }
    Swal.fire({
      icon: 'error',
      title: 'Gagal menyimpan transaksi',
      text: errorMessage,
      confirmButtonText: 'OK'
    })
  }
}

async function handleDetail() {
  await fetchAllTransactions()
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
