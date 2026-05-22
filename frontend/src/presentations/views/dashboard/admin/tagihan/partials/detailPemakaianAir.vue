<template>
  <div class="input-pemakaian-air-root p-4 lg:p-6 min-h-screen">
    <div
      class="hidden! md:flex! flex-col! md:flex-row! md:items-center! justify-between! gap-4! mb-6!"
    >
      <div class="flex! items-center! gap-3! md:gap-4!">
        <BaseButton
          variant="ghost"
          icon="chevron-left"
          class="w-8! h-8! md:w-10! md:h-10! p-0! rounded-full! bg-white! shadow-sm!"
          @click="handleBack"
        />
        <div>
          <h1 class="text-xl! md:text-2xl! font-extrabold! text-slate-800! tracking-tight!">
            Input Pemakaian Air
          </h1>
          <p class="text-xs! md:text-sm! text-slate-500!">
            Silakan input meter akhir pelanggan pada form di bawah ini
          </p>
        </div>
      </div>

      <div class="shrink-0!">
        <BaseButton
          variant="info-gradient"
          size="md"
          @click="handleGenerateBills"
          class="w-full! sm:w-auto! rounded-xl! shadow-lg! text-xs md:text-sm font-bold! flex! items-center! gap-1.5!"
          icon="file-invoice-dollar"
        >
          Generate Tagihan
        </BaseButton>
      </div>
    </div>

    <div class="hidden! md:block!">
      <ContentCard variant="default" padding="none" hoverable class="overflow-hidden!">
        <DataTable
          :data="filteredData"
          :columns="tableColumns"
          title=""
          v-model:current-page="currentPage"
          v-model:per-page="perPage"
          :total-pages="totalPages"
          :visible-pages="visiblePages"
          :total-entries="filteredData.length"
          v-model="searchQuery"
          @row-click="handleRowClick"
          row-clickable
          search-placeholder="Cari Pelanggan..."
          :no-card="true"
        >
          <template #column-meterAwal="{ row }">
            <span class="text-slate-500!">{{ row.meterAwal }}</span>
          </template>

          <template #column-meterAkhir="{ row }">
            <input
              type="text"
              inputmode="numeric"
              v-model.number="row.meterAkhir"
              class="w-16! md:w-20! text-right! bg-transparent! outline-none! border-b! border-transparent! focus:border-cyan-500! hover:border-slate-300! transition-colors! font-semibold!"
              :class="row.meterAkhir === row.meterAwal ? 'text-red-500!' : 'text-orange-500!'"
            />
          </template>

          <template #column-pemakaian="{ row }">
            <span class="text-slate-500!">{{ hitungPemakaian(row) }}</span>
          </template>
        </DataTable>
      </ContentCard>
    </div>

    <div
      class="md:hidden! bg-white! rounded-[2.25rem]! border! border-slate-100! shadow-[0_20px_50px_rgba(0,0,0,0.04)]! overflow-hidden!"
    >
      <div class="p-6! border-b! border-slate-50!">
        <div class="flex! items-center! justify-between! gap-3! mb-5!">
          <div class="flex! items-center! gap-3!">
            <BaseButton
              variant="ghost"
              icon="chevron-left"
              class="w-8! h-8! p-0! rounded-full! bg-slate-100! text-slate-500!"
              @click="handleBack"
            />
            <div>
              <h1 class="text-lg! font-black! text-slate-800! leading-tight!">Input Pemakaian</h1>
              <p class="text-[10px]! uppercase! tracking-[0.2em]! text-slate-400!">Pamsides V2</p>
            </div>
          </div>
          <BaseButton
            variant="info-gradient"
            size="sm"
            @click="handleGenerateBills"
            class="rounded-xl! shadow-sm! text-xs font-bold! flex! items-center! gap-1!"
            icon="file-invoice-dollar"
          >
            Generate
          </BaseButton>
        </div>

        <div class="space-y-4!">
          <div class="relative!">
            <span class="absolute! left-3.5! top-1/2! -translate-y-1/2! text-slate-400! text-sm!">
              <font-awesome-icon icon="search" />
            </span>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari nama atau no. induk..."
              class="w-full! pl-10! pr-4! py-3! bg-slate-50! border! border-slate-200! rounded-2xl! text-sm! text-slate-700! focus:outline-none! focus:ring-2! focus:ring-cyan-500/10! focus:border-cyan-400! transition-all!"
            />
          </div>

          <div class="flex! items-center! justify-between! px-1!">
            <p class="text-[10px]! font-black! text-slate-400! uppercase! tracking-[0.2em]!">
              Daftar Pelanggan
            </p>
          </div>
        </div>
      </div>

      <div v-if="paginatedData.length === 0" class="p-12! text-center!">
        <font-awesome-icon icon="search" class="text-slate-200! text-4xl! mb-3!" />
        <p class="text-slate-400! text-sm!">Data tidak ditemukan</p>
      </div>

      <div v-else class="p-4! space-y-3.5!">
        <div
          v-for="row in paginatedData"
          :key="row.noInduk"
          @click="handleRowClick(row)"
          class="p-4! bg-white! rounded-2xl! border! border-slate-100! shadow-[0_4px_20px_rgba(0,0,0,0.02)]! active:scale-[0.98]! active:bg-slate-50! transition-all! cursor-pointer! flex! flex-col! gap-3!"
        >
          <div class="flex! items-start! justify-between! gap-3!">
            <div class="flex! items-center! gap-3! min-w-0!">
              <div
                class="w-10! h-10! rounded-xl! bg-gradient-to-br! from-cyan-500! to-blue-600! flex! items-center! justify-center! text-white! font-bold! text-sm! shadow-sm! shrink-0!"
              >
                {{ row.nama.charAt(0) }}
              </div>
              <div class="truncate!">
                <p class="text-sm! font-black! text-slate-800! truncate! mb-0.5!">
                  {{ row.nama }}
                </p>
                <div class="flex! items-center! gap-1.5!">
                  <span class="text-[9px]! font-bold! text-slate-400! font-mono!">
                    ID: {{ row.noInduk }}
                  </span>
                  <span class="text-slate-300! text-[9px]! font-bold!">|</span>
                  <span class="text-[9px]! font-bold! text-slate-400! truncate!">
                    {{ row.dusun }}
                  </span>
                </div>
              </div>
            </div>

            <div
              class="w-8! h-8! rounded-full! bg-cyan-50! flex! items-center! justify-center! text-cyan-600! shrink-0!"
            >
              <font-awesome-icon icon="chevron-right" class="text-xs!" />
            </div>
          </div>

          <div
            class="grid! grid-cols-2! gap-2! bg-slate-50! p-2.5! rounded-xl! border! border-slate-100/50!"
          >
            <div class="pl-2!">
              <p class="text-[8px]! font-black! text-slate-400! uppercase! tracking-wider! mb-0.5!">
                METER AWAL
              </p>
              <p class="text-xs! font-bold! text-slate-700!">{{ row.meterAwal }} m³</p>
            </div>
            <div class="pl-3! border-l! border-slate-200!">
              <p class="text-[8px]! font-black! text-slate-400! uppercase! tracking-wider! mb-0.5!">
                PAKAI LALU
              </p>
              <p class="text-xs! font-bold! text-cyan-600!">{{ row.usageLalu }} m³</p>
            </div>
          </div>
        </div>
      </div>

      <div
        class="px-5! py-5! bg-slate-50/50! border-t! border-slate-100! flex! items-center! justify-between!"
      >
        <span class="text-xs! font-bold! text-slate-400! tracking-tight!">
          Page {{ currentPage }} of {{ totalPages }}
        </span>
        <div class="flex! items-center! gap-3!">
          <BaseButton
            variant="ghost"
            size="sm"
            :disabled="currentPage === 1"
            @click="currentPage--"
            class="w-10! h-10! p-0! rounded-full! bg-white! border! border-slate-100! text-slate-400! shadow-sm!"
          >
            <font-awesome-icon icon="chevron-left" class="text-[10px]!" />
          </BaseButton>
          <BaseButton
            variant="ghost"
            size="sm"
            :disabled="currentPage === totalPages"
            @click="currentPage++"
            class="w-10! h-10! p-0! rounded-full! bg-white! border! border-slate-100! text-slate-400! shadow-sm!"
          >
            <font-awesome-icon icon="chevron-right" class="text-[10px]!" />
          </BaseButton>
        </div>
      </div>
    </div>

    <InputMeterModal
      :show="isModalOpen"
      :customer="selectedCustomer"
      @close="isModalOpen = false"
      @save="handleSaveMeter"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUiStore } from '@/stores/uiStore'
import { meterService } from '@/services/meter.service'
import { billingService } from '@/services/billing.service.js'
import { MySwal } from '@/utils/swal.js'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import InputMeterModal from './inputMeterModal.vue'

const router = useRouter()
const uiStore = useUiStore()

const isLoading = ref(false)
const customers = ref([])

const getMonthNumber = (m) => {
  if (!m) return new Date().getMonth() + 1
  if (!isNaN(m)) return parseInt(m)
  
  const monthNamesId = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
  ]
  const idxId = monthNamesId.indexOf(m)
  if (idxId !== -1) return idxId + 1

  const monthNamesEn = [
    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'
  ]
  const idxEn = monthNamesEn.indexOf(m)
  if (idxEn !== -1) return idxEn + 1

  return new Date().getMonth() + 1
}

const fetchData = async () => {
  try {
    isLoading.value = true
    const { month, year, bulan, tahun } = router.currentRoute.value.query
    
    const monthVal = getMonthNumber(month || bulan)
    const yearVal = parseInt(year || tahun) || new Date().getFullYear()

    const res = await meterService.getPendingReadings({ month: monthVal, year: yearVal })
    if (res.data) {
      // Map data backend ke format tabel jika perlu
      customers.value = res.data.map((item) => {
        // Meter Awal diambil dari pencatatan bulan lalu
        const awal =
          item.meter_readings && item.meter_readings.length > 0
            ? item.meter_readings[0].meter_value
            : item.initial_meter_reading

        // Pemakaian bulan lalu dari MonthlyBill
        const usageLalu =
          item.monthly_bills && item.monthly_bills.length > 0 ? item.monthly_bills[0].usage_m3 : 0

        const address = item.ticket?.address || ''
        let rtVal = '-'
        const rtMatch = address.match(/rt\s*[:\.]?\s*(\d+)/i)
        if (rtMatch) {
          rtVal = 'RT ' + rtMatch[1]
        }
        const dusunVal = address ? address.split(',')[0] : '-'

        return {
          nama: item.user?.name || item.ticket?.applicant_name || 'Tanpa Nama',
          noInduk: item.customer_code || '-',
          dusun: dusunVal,
          rt: rtVal,
          meterAwal: awal || 0,
          meterAkhir: awal || 0,
          usageLalu: usageLalu,
          id: item.id,
        }
      })
    }
  } catch (error) {
    console.error('Gagal mengambil data pelanggan:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchData()
})

const handleBack = () => {
  if (uiStore.userRole === 'teknisi') {
    router.push('/instalasi/teknisiPemakaianAir')
  } else {
    router.push('/instalasi/pemakaian-air')
  }
}

const handleGenerateBills = async () => {
  const confirm = await MySwal.fire({
    title: 'Generate Tagihan?',
    text: 'Sistem akan memproses generate tagihan untuk pelanggan yang sudah diinput meteran airnya pada periode terpilih.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Mulai Generate',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#0891b2',
    cancelButtonColor: '#64748b',
    background: '#ffffff',
    customClass: {
      popup: 'rounded-2xl!',
      confirmButton: 'rounded-xl! px-4! py-2! font-bold!',
      cancelButton: 'rounded-xl! px-4! py-2! font-medium!',
    },
  })

  if (!confirm.isConfirmed) return

  try {
    uiStore.setLoading(true)

    const { month, year, bulan, tahun } = router.currentRoute.value.query
    const monthVal = getMonthNumber(month || bulan)
    const yearVal = parseInt(year || tahun) || new Date().getFullYear()

    const res = await billingService.generateMonthlyBills({ month: monthVal, year: yearVal })

    await MySwal.fire({
      title: 'Generate Berhasil!',
      text: res.data?.message || 'Tagihan pelanggan berhasil di-generate!',
      icon: 'success',
      confirmButtonText: 'Lihat Daftar Tagihan',
      confirmButtonColor: '#0891b2',
      customClass: {
        popup: 'rounded-2xl!',
        confirmButton: 'rounded-xl! px-5! py-2.5! font-bold!',
      },
    })

    router.push('/instalasi/daftar-tagihan')
  } catch (error) {
    uiStore.error(error.response?.data?.data?.message || error.response?.data?.message || 'Gagal generate tagihan')
  } finally {
    uiStore.setLoading(false)
  }
}

const isModalOpen = ref(false)
const selectedCustomer = ref({})

const perPage = ref(10)
const searchQuery = ref('')
const currentPage = ref(1)

watch(perPage, () => {
  currentPage.value = 1
})

const handleRowClick = (row) => {
  selectedCustomer.value = { ...row }
  isModalOpen.value = true
}

const handleSaveMeter = async (payload) => {
  try {
    uiStore.setLoading(true)

    const { month, year, bulan, tahun } = router.currentRoute.value.query
    const monthVal = getMonthNumber(month || bulan)
    const yearVal = parseInt(year || tahun) || new Date().getFullYear()

    const formData = new FormData()
    formData.append('customer_id', selectedCustomer.value.id)
    formData.append('meter_value', payload.meterValue)
    formData.append('photo', payload.photo)
    formData.append('month', monthVal)
    formData.append('year', yearVal)

    await meterService.submitReading(formData)

    uiStore.success('Data pemakaian dan foto berhasil disimpan!')

    const index = customers.value.findIndex((c) => c.noInduk === selectedCustomer.value.noInduk)
    if (index !== -1) {
      customers.value.splice(index, 1)
    }

    isModalOpen.value = false
  } catch (error) {
    uiStore.error(error.response?.data?.message || 'Gagal menyimpan data')
  } finally {
    uiStore.setLoading(false)
  }
}

const tableColumns = [
  { key: 'nama', title: 'NAMA PELANGGAN', tdClass: 'font-medium! text-slate-500!' },
  { key: 'dusun', title: 'DUSUN / ALAMAT', tdClass: 'text-slate-500!' },
  {
    key: 'noInduk',
    title: 'NO.INDUK',
    tdClass: 'text-slate-500! hidden! sm:table-cell!',
    thClass: 'hidden! sm:table-cell!',
  },
  {
    key: 'usageLalu',
    title: 'PAKAI BULAN LALU',
    tdClass: 'text-right! w-40! text-blue-600! font-bold!',
  },
]

const hitungPemakaian = (row) => {
  return Math.max(0, row.meterAkhir - row.meterAwal)
}

const filteredData = computed(() => {
  if (!searchQuery.value) return customers.value
  const q = searchQuery.value.toLowerCase()
  return customers.value.filter((item) => {
    return (
      item.nama.toLowerCase().includes(q) ||
      item.noInduk.toLowerCase().includes(q) ||
      item.dusun.toLowerCase().includes(q)
    )
  })
})

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return filteredData.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(filteredData.value.length / perPage.value))
})

const visiblePages = computed(() => {
  const pages = []
  for (let i = 1; i <= Math.min(3, totalPages.value); i++) {
    pages.push(i)
  }
  return pages
})
</script>
