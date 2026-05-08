<template>
  <div class="cater-pemakaian-root p-4! lg:p-8!">
    <!-- Desktop Layout -->
    <div class="hidden! lg:block! max-w-7xl mx-auto">
      <div class="flex! justify-between! items-start! mb-10!">
        <div>
          <h1 class="text-4xl! font-black! text-slate-800! tracking-tight! mb-2!">
            Manajemen <span class="text-blue-600!">Pemakaian Air</span>
          </h1>
          <p class="text-slate-500! text-lg! font-medium!">
            Pilih periode pencatatan untuk memulai input meteran pelanggan.
          </p>
        </div>
        <div class="flex! gap-4!">
          <BaseButton @click="openResultsModal" variant="ghost" icon="history" class="text-slate-600! font-bold!">Hasil Input</BaseButton>
          <BaseButton variant="primary-gradient" icon="print" class="shadow-lg! shadow-blue-200!">Cetak Laporan</BaseButton>
        </div>
      </div>

      <div class="grid! grid-cols-12! gap-8!">
        <!-- Left Column: Form -->
        <div class="col-span-7!">
          <ContentCard variant="bordered" padding="large" rounded="2xl" class="shadow-lg! shadow-slate-200/50!">
            <div class="mb-8!">
              <h3 class="text-xl! font-bold! text-slate-800! mb-6! flex! items-center! gap-3!">
                <div class="w-10! h-10! rounded-xl! bg-blue-50! text-blue-600! flex! items-center! justify-center!">
                  <font-awesome-icon icon="calendar-check" />
                </div>
                Konfigurasi Periode
              </h3>
              
              <div class="grid! grid-cols-2! gap-6!">
                <div class="form-group!">
                  <label class="block! text-xs! font-black! text-slate-400! uppercase! tracking-widest! mb-3!">Tahun Pemakaian</label>
                  <SelectSearch
                    v-model="form.tahun"
                    :options="tahunOptions"
                    placeholder="Pilih Tahun"
                    no-margin
                  />
                </div>
                <div class="form-group!">
                  <label class="block! text-xs! font-black! text-slate-400! uppercase! tracking-widest! mb-3!">Bulan Pemakaian</label>
                  <SelectSearch
                    v-model="form.bulan"
                    :options="bulanOptions"
                    placeholder="Pilih Bulan"
                    no-margin
                  />
                </div>
              </div>
            </div>

            <div class="form-group! mb-10!">
              <label class="block! text-xs! font-black! text-slate-400! uppercase! tracking-widest! mb-3!">Petugas Lapangan (Staff)</label>
              <div class="relative! group!">
                <input
                  type="text"
                  :value="staffName"
                  readonly
                  class="w-full! bg-slate-50! border-2! border-slate-100! rounded-2xl! px-6! py-4! text-sm! font-bold! text-slate-700! focus:border-blue-500! focus:outline-none! transition-all!"
                />
                <div class="absolute! right-6! top-1/2! -translate-y-1/2! text-blue-500!">
                  <font-awesome-icon icon="id-badge" />
                </div>
              </div>
            </div>

            <BaseButton
              to="/instalasi/pemakaian-air/input"
              variant="primary-gradient"
              size="lg"
              block
              icon="arrow-right"
              class="rounded-2xl! h-16! font-black! text-lg! shadow-xl! shadow-blue-200!"
            >
              MULAI INPUT DATA
            </BaseButton>
          </ContentCard>
        </div>

        <!-- Right Column: Stats & Info -->
        <div class="col-span-5! flex! flex-col! gap-6!">
          <div class="bg-gradient-to-br from-blue-600 to-indigo-700! rounded-2xl! p-4! text-white! shadow-lg! shadow-blue-100! relative! overflow-hidden!">
            <div class="relative! z-10!">
              <p class="text-blue-100! text-[9px]! font-black! uppercase! tracking-widest! mb-3!">Progres Pencatatan</p>
              
              <div v-if="stats.loading" class="animate-pulse!">
                <div class="h-12! w-24! bg-white/20! rounded-lg! mb-4!"></div>
                <div class="w-full! h-3! bg-white/20! rounded-full! mb-8!"></div>
                <div class="grid! grid-cols-2! gap-6!">
                  <div class="h-10! bg-white/20! rounded-lg!"></div>
                  <div class="h-10! bg-white/20! rounded-lg!"></div>
                </div>
              </div>
              
              <div v-else>
                <div class="flex! items-end! gap-2! mb-1.5!">
                  <span class="text-3xl! font-black!">{{ stats.percentage }}%</span>
                  <span class="text-blue-100! mb-0.5! text-xs! font-bold!">Selesai</span>
                </div>
                <div class="w-full! h-1.5! bg-white/20! rounded-full! mb-4!">
                  <div :style="{ width: stats.percentage + '%' }" class="h-full! bg-white! rounded-full! shadow-[0_0_15px_rgba(255,255,255,0.5)]!"></div>
                </div>
                <div class="grid! grid-cols-2! gap-3!">
                  <div class="bg-white/10! border! border-white/10! rounded-xl! p-2.5!">
                    <p class="text-blue-100! text-[9px]! font-black! uppercase! tracking-wider! mb-1!">Total Pelanggan</p>
                    <p class="text-xl! font-black!">{{ stats.total }}</p>
                  </div>
                  <div class="bg-white/10! border! border-white/10! rounded-xl! p-2.5!">
                    <p class="text-blue-100! text-[9px]! font-black! uppercase! tracking-wider! mb-1!">Belum Dicatat</p>
                    <p class="text-xl! font-black!">{{ stats.pending }}</p>
                  </div>
                </div>
              </div>
            </div>
            <!-- Decorative circle -->
            <div class="absolute! -right-10! -top-10! w-40! h-40! bg-white/10! rounded-full!"></div>
          </div>

          <ContentCard variant="bordered" padding="small" rounded="2xl" hoverable class="shadow-lg! shadow-slate-200/40! flex-1!">
            <h4 class="text-xs! font-black! text-slate-800! uppercase! tracking-widest! mb-3! flex! items-center! gap-2!">
              <font-awesome-icon icon="lightbulb" class="text-amber-500!" />
              Tips Operasional
            </h4>
            <ul class="space-y-2!">
              <li v-for="(tip, i) in tips" :key="i" class="flex! gap-2.5!">
                <div class="w-5! h-5! rounded-full! bg-slate-100! text-slate-500! flex! items-center! justify-center! text-[9px]! font-black! shrink-0!">
                  {{ i + 1 }}
                </div>
                <p class="text-[11px]! text-slate-500! leading-tight! font-medium!">
                  {{ tip }}
                </p>
              </li>
            </ul>
          </ContentCard>
        </div>
      </div>
    </div>

    <!-- Mobile Layout -->
    <div class="lg:hidden! flex! flex-col! gap-6! pb-24!">
      <div class="header! px-2!">
        <h1 class="text-3xl! font-black! text-slate-800! tracking-tight! mb-1!">
          Input <span class="text-blue-600!">Meter</span>
        </h1>
        <p class="text-slate-500! font-bold! text-sm!">Pencatatan Air Bulanan</p>
      </div>

      <!-- Mobile Progress Card -->
      <div class="bg-gradient-to-br from-blue-600 to-indigo-700! rounded-2xl! p-4! text-white! shadow-lg! shadow-blue-100!">
        <div v-if="stats.loading" class="animate-pulse!">
          <div class="h-4! w-32! bg-white/20! rounded! mb-4!"></div>
          <div class="h-10! w-20! bg-white/20! rounded! mb-4!"></div>
          <div class="h-2! w-full! bg-white/20! rounded-full!"></div>
        </div>
        <div v-else>
          <div class="flex! items-end! gap-3! mb-4!">
            <div class="flex! flex-col!">
              <span class="text-blue-100! text-[9px]! font-black! uppercase! tracking-widest! mb-1!">Pencatatan</span>
              <span class="text-3xl! font-black!">{{ stats.percentage }}%</span>
            </div>
            <div class="h-10! w-px! bg-white/20! mx-2!"></div>
            <div class="flex! flex-col! gap-1!">
              <span class="bg-white/20! px-2! py-0.5! rounded-lg! text-[9px]! font-black! flex! items-center! gap-1.5!">
                <div class="w-1.5! h-1.5! rounded-full! bg-white!"></div>
                {{ stats.total }} Total
              </span>
              <span class="bg-white/10! px-2! py-0.5! rounded-lg! text-[9px]! font-black! flex! items-center! gap-1.5!">
                <div class="w-1.5! h-1.5! rounded-full! bg-blue-300!"></div>
                {{ stats.pending }} Sisa
              </span>
            </div>
          </div>
          <div class="w-full! h-1.5! bg-white/20! rounded-full!">
            <div :style="{ width: stats.percentage + '%' }" class="h-full! bg-white! rounded-full!"></div>
          </div>
        </div>
      </div>

      <div class="flex! flex-col! gap-6! px-2!">
        <div class="form-group!">
          <label class="block! text-[10px]! font-black! text-slate-400! uppercase! tracking-widest! mb-2!">Petugas Pencatat</label>
          <div class="relative!">
            <input
              type="text"
              :value="staffName"
              readonly
              class="w-full! bg-slate-100! border-none! rounded-2xl! px-5! py-4! text-sm! font-bold! text-slate-700!"
            />
            <div class="absolute! right-5! top-1/2! -translate-y-1/2! text-slate-400!">
              <font-awesome-icon icon="user-circle" />
            </div>
          </div>
        </div>

        <div class="grid! grid-cols-2! gap-4!">
          <div class="form-group!">
            <label class="block! text-[10px]! font-black! text-slate-400! uppercase! tracking-widest! mb-2!">Tahun</label>
            <SelectSearch v-model="form.tahun" :options="tahunOptions" no-margin class="rounded-2xl!" />
          </div>
          <div class="form-group!">
            <label class="block! text-[10px]! font-black! text-slate-400! uppercase! tracking-widest! mb-2!">Bulan</label>
            <SelectSearch v-model="form.bulan" :options="bulanOptions" no-margin class="rounded-2xl!" />
          </div>
        </div>

        <div class="form-group!">
          <label class="block! text-[10px]! font-black! text-slate-400! uppercase! tracking-widest! mb-3!">Pilih Periode Cepat</label>
          <div class="grid! grid-cols-3! gap-2!">
            <button
              v-for="m in bulanGrid"
              :key="m.value"
              @click="form.bulan = m.value"
              :class="[
                'py-3.5! rounded-xl! text-xs! font-black! transition-all! duration-300!',
                form.bulan === m.value
                  ? 'bg-blue-600! text-white! shadow-lg! shadow-blue-200!'
                  : 'bg-white! text-slate-500! border-2! border-slate-50!',
              ]"
            >
              {{ m.label }}
            </button>
          </div>
        </div>

        <BaseButton
          :to="{ path: '/instalasi/pemakaian-air/input', query: { month: monthToNumber(form.bulan), year: form.tahun } }"
          variant="primary-gradient"
          size="lg"
          block
          icon="arrow-right"
          class="rounded-2xl! py-5! font-black! text-base! shadow-xl! shadow-blue-200! mt-4!"
        >
          MULAI PENCATATAN
        </BaseButton>

        <BaseButton
          @click="openResultsModal"
          variant="secondary"
          size="lg"
          block
          icon="history"
          class="rounded-2xl! py-5! font-black! text-base! mt-3!"
        >
          HASIL INPUT
        </BaseButton>

        <div class="bg-amber-50! border-2! border-amber-100! rounded-3xl! p-6! mt-4!">
          <div class="flex! items-start! gap-4!">
            <div class="w-10! h-10! bg-amber-100! text-amber-600! rounded-xl! flex! items-center! justify-center! shrink-0!">
              <font-awesome-icon icon="exclamation-triangle" />
            </div>
            <div>
              <h4 class="text-amber-800! font-black! text-sm! mb-1! uppercase! tracking-tight!">PENTING!</h4>
              <p class="text-amber-700/80! text-xs! leading-relaxed! font-bold!">
                Pastikan periode <span class="text-amber-900!">Bulan & Tahun</span> sudah sesuai dengan jadwal pencatatan Anda.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Hasil Input Modal (Full Screen on Mobile, Elegant Modal on Desktop) -->
    <Teleport to="body">
      <div v-if="showResultsModal" class="fixed! inset-0! bg-slate-950/60! backdrop-blur-sm! flex! items-center! justify-center! z-[9999]! p-0 md:p-6! transition-all!">
        <div class="bg-white! w-full! h-full! md:h-[85vh]! md:max-w-[92vw]! md:rounded-3xl! shadow-2xl! flex! flex-col! overflow-hidden! relative! animate-modal-in!">
          <!-- Modal Header -->
          <div class="px-6! py-5! border-b! border-slate-100! flex! justify-between! items-center! bg-slate-50!">
            <div class="flex! items-center! gap-3!">
              <div class="w-10! h-10! rounded-xl! bg-blue-50! text-blue-600! flex! items-center! justify-center!">
                <font-awesome-icon icon="history" />
              </div>
              <div>
                <h3 class="text-lg! font-black! text-slate-800!">Hasil Input Meteran</h3>
                <p class="text-xs! font-bold! text-slate-400! uppercase! tracking-wider!">Periode {{ form.bulan }} {{ form.tahun }}</p>
              </div>
            </div>
            <button @click="closeResultsModal" class="w-10! h-10! rounded-full! hover:bg-slate-200! text-slate-400! hover:text-slate-600! flex! items-center! justify-center! transition-all!">
              <font-awesome-icon icon="times" class="text-lg!" />
            </button>
          </div>

          <!-- Modal Body (Scrollable table) -->
          <div class="flex-1! overflow-y-auto! p-6!">
            <div v-if="loadingResults" class="flex! flex-col! items-center! justify-center! h-64! opacity-50!">
              <font-awesome-icon icon="spinner" spin class="text-3xl! text-blue-500! mb-3!" />
              <p class="text-sm! font-bold! text-slate-500!">Memuat data hasil pencatatan...</p>
            </div>

            <div v-else-if="resultsData.length === 0" class="text-center! py-16! bg-slate-50! rounded-2xl! border! border-dashed! border-slate-200!">
              <div class="w-16! h-16! bg-slate-100! text-slate-400! rounded-full! flex! items-center! justify-center! mx-auto! mb-4! text-2xl!">
                <font-awesome-icon icon="frown" />
              </div>
              <h4 class="text-base! font-black! text-slate-700! mb-1!">Belum Ada Data</h4>
              <p class="text-xs! text-slate-400! font-bold!">Tidak ada data meteran yang tercatat untuk periode ini.</p>
            </div>

            <div v-else class="overflow-x-auto!">
              <table class="w-full! text-left! border-collapse!">
                <thead>
                  <tr class="border-b! border-slate-100!">
                    <th class="py-3! px-4! text-xs! font-black! text-slate-400! uppercase! tracking-widest!">Nama Pelanggan</th>
                    <th class="py-3! px-4! text-xs! font-black! text-slate-400! uppercase! tracking-widest!">Alamat</th>
                    <th class="py-3! px-4! text-xs! font-black! text-slate-400! uppercase! tracking-widest! text-center!">Angka Meter</th>

                    <th class="py-3! px-4! text-xs! font-black! text-slate-400! uppercase! tracking-widest! text-center!">Foto</th>
                  </tr>
                </thead>
                <tbody class="divide-y! divide-slate-50!">
                  <tr v-for="item in resultsData" :key="item.id" class="hover:bg-slate-50/50! transition-colors!">
                    <td class="py-4! px-4!">
                      <p class="text-sm! font-bold! text-slate-800!">{{ item.customer?.user?.name }}</p>
                      <p class="text-[10px]! font-black! text-slate-400! uppercase! tracking-wider!">ID: {{ item.customer?.id }}</p>
                    </td>
                    <td class="py-4! px-4! text-sm! text-slate-500! font-medium! max-w-[200px]! truncate!">{{ item.customer?.ticket?.address || item.customer?.address }}</td>
                    <td class="py-4! px-4! text-sm! font-black! text-blue-600! text-center!">{{ item.meter_value }} m³</td>

                    <td class="py-4! px-4! text-center!">
                      <a v-if="item.photo_url" :href="`/storage/${item.photo_url}`" target="_blank" class="text-xs! font-bold! text-blue-500! hover:underline! flex! items-center! justify-center! gap-1!">
                        <font-awesome-icon icon="image" /> Lihat Foto
                      </a>
                      <span v-else class="text-xs! text-slate-400! font-bold!">Tidak Ada</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { reactive, computed, onMounted, watch, ref } from 'vue'
import { useUiStore } from '@/stores/uiStore'
import api from '@/utils/axios'
import { meterService } from '@/services/meter.service'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import SelectSearch from '@/presentations/components/SelectSearch.vue'

const uiStore = useUiStore()

const now = new Date()
const currentYear = now.getFullYear().toString()
const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des']
const currentMonth = monthNames[now.getMonth()]

const stats = reactive({
  total: 0,
  done: 0,
  pending: 0,
  percentage: 0,
  loading: true,
})

const showResultsModal = ref(false)
const loadingResults = ref(false)
const resultsData = ref([])

const monthToNumber = (name) => {
  return monthNames.indexOf(name) + 1
}

const form = reactive({
  tahun: currentYear,
  bulan: currentMonth,
})

// Ambil data dari Backend (Mengikuti struktur backend yang ada)
const fetchStats = async () => {
  try {
    stats.loading = true
    const res = await meterService.getPendingReadings({
      month: monthToNumber(form.bulan),
      year: form.tahun
    })
    // Backend mengembalikan { message: "...", data: [...], total_customers: 123 }
    if (res.data) {
      stats.pending = res.data.length
      stats.total = res.total_customers || 0
      stats.done = stats.total - stats.pending
      stats.percentage = stats.total > 0 ? Math.round((stats.done / stats.total) * 100) : 0
    }
  } catch (error) {
    console.error('Gagal mengambil data pending:', error)
  } finally {
    stats.loading = false
  }
}

const openResultsModal = async () => {
  showResultsModal.value = true
  await fetchResults()
}

const closeResultsModal = () => {
  showResultsModal.value = false
}

const fetchResults = async () => {
  try {
    loadingResults.value = true
    const res = await api.get('/meter-readings/completed', {
      params: {
        month: monthToNumber(form.bulan),
        year: form.tahun
      }
    })
    if (res.data && res.data.success) {
      resultsData.value = res.data.data
    }
  } catch (error) {
    console.error('Gagal mengambil data hasil input:', error)
  } finally {
    loadingResults.value = false
  }
}

watch(() => [form.bulan, form.tahun], () => {
  fetchStats()
  if (showResultsModal.value) {
    fetchResults()
  }
})

onMounted(() => {
  fetchStats()
})

const staffName = computed(() => uiStore.userData?.name || 'Petugas Lapangan')

const tahunOptions = [
  { id: '2024', text: '2024' },
  { id: '2025', text: '2025' },
  { id: '2026', text: '2026' },
]

const bulanOptions = [
  { id: 'Jan', text: 'Januari' },
  { id: 'Feb', text: 'Februari' },
  { id: 'Mar', text: 'Maret' },
  { id: 'Apr', text: 'April' },
  { id: 'Mei', text: 'Mei' },
  { id: 'Jun', text: 'Juni' },
  { id: 'Jul', text: 'Juli' },
  { id: 'Ags', text: 'Agustus' },
  { id: 'Sep', text: 'September' },
  { id: 'Okt', text: 'Oktober' },
  { id: 'Nov', text: 'November' },
  { id: 'Des', text: 'Desember' },
]

const bulanGrid = [
  { label: 'JAN', value: 'Jan' },
  { label: 'FEB', value: 'Feb' },
  { label: 'MAR', value: 'Mar' },
  { label: 'APR', value: 'Apr' },
  { label: 'MEI', value: 'Mei' },
  { label: 'JUN', value: 'Jun' },
  { label: 'JUL', value: 'Jul' },
  { label: 'AGS', value: 'Ags' },
  { label: 'SEP', value: 'Sep' },
  { label: 'OKT', value: 'Okt' },
  { label: 'NOV', value: 'Nov' },
  { label: 'DES', value: 'Des' },
]

const tips = [
  'Gunakan pencahayaan yang cukup saat mengambil foto meteran.',
  'Pastikan angka meteran terlihat jelas dan tidak buram.',
  'Segera laporkan jika menemukan meteran yang rusak atau segel terbuka.',
  'Data yang sudah disimpan akan diverifikasi oleh Admin Pusat.'
]
</script>

<style scoped>
.cater-pemakaian-root {
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.shadow-blue-200 {
  --tw-shadow-color: rgba(37, 99, 235, 0.15);
}

input:read-only {
  cursor: default;
  background-color: rgba(248, 250, 252, 0.5);
}

@media (max-width: 1024px) {
  :deep(.custom-select-search .select-display) {
    height: 3.5rem !important;
    border-radius: 1rem !important;
    border: 2px solid #f1f5f9 !important;
    background-color: #f8fafc !important;
    font-weight: 700 !important;
    color: rgb(51, 65, 85) !important;
    box-shadow: 0 10px 15px -3px rgba(148, 163, 184, 0.12), 0 4px 6px -2px rgba(148, 163, 184, 0.06) !important;
  }
}

@keyframes modalIn {
  from { opacity: 0; transform: scale(0.95) translateY(10px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

.animate-modal-in {
  animation: modalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
