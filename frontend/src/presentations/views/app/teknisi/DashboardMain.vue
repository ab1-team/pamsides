<template>
  <div class="teknisi-dashboard">
    <!-- Loading Overlay / State -->
    <div
      v-if="isLoading"
      class="flex! flex-col! items-center! justify-center! h-[60vh]! opacity-50!"
    >
      <font-awesome-icon icon="spinner" spin class="text-4xl! text-cyan-500! mb-4!" />
      <p class="text-sm! font-bold! text-slate-500!">Menyiapkan data operasional...</p>
    </div>

    <div v-else>
      <!-- Header Section -->
      <div class="flex! flex-col! lg:flex-row! lg:items-center! justify-between! mb-8! gap-6!">
        <div>
          <div
            class="inline-flex! items-center! gap-2! bg-cyan-500/10! border! border-cyan-500/20! px-4! py-1.5! rounded-full! mb-3!"
          >
            <span class="w-2! h-2! bg-cyan-500! rounded-full! animate-pulse!"></span>
            <span class="text-xs! font-black! text-cyan-600! uppercase! tracking-widest!"
              >Teknisi Lapangan</span
            >
          </div>
          <h1 class="text-2xl! lg:text-3xl! font-black! text-slate-800! tracking-tight!">
            Selamat Datang,
            <span
              class="text-transparent! bg-clip-text! bg-linear-to-r! from-cyan-500! to-blue-500!"
              >{{ teknisiName }}</span
            >
          </h1>
          <p class="text-slate-500! mt-2! text-sm! font-medium!">
            Ringkasan pencatatan meter bulanan dan tugas instalasi Anda.
          </p>
        </div>
        <div class="flex! items-center! gap-4!">
          <div
            class="bg-white! px-4! py-3! rounded-2xl! shadow-xs! border! border-slate-100! flex! items-center! gap-3!"
          >
            <div
              class="w-9! h-9! rounded-lg! bg-cyan-50! text-cyan-600! flex! items-center! justify-center!"
            >
              <font-awesome-icon icon="calendar-day" class="text-sm!" />
            </div>
            <div>
              <div class="text-[9px]! font-black! text-slate-400! uppercase! tracking-widest!">
                Periode Aktif
              </div>
              <div class="text-xs! font-bold! text-slate-700!">{{ currentMonth }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions (Top Row) -->
      <div class="grid! grid-cols-1! md:grid-cols-2! lg:grid-cols-4! gap-6! mb-10!">
        <!-- Primary Action: Catat Meter -->
        <div
          @click="$router.push('/app/instalasi/teknisiPemakaianAir')"
          class="group! cursor-pointer! bg-linear-to-br! from-cyan-500! to-blue-600! p-6! rounded-3xl! shadow-lg! shadow-cyan-500/30! relative! overflow-hidden! transition-all! hover:-translate-y-1! hover:shadow-cyan-500/50!"
        >
          <div
            class="absolute! top-0! right-0! w-32! h-32! bg-white/10! rounded-full! blur-2xl! -mr-10! -mt-10!"
          ></div>
          <div
            class="w-12! h-12! bg-white/20! backdrop-blur-xs! rounded-xl! flex! items-center! justify-center! text-white! text-xl! mb-4! group-hover:scale-110! transition-transform!"
          >
            <font-awesome-icon icon="tachometer-alt" />
          </div>
          <h3 class="text-lg! font-black! text-white! mb-1!">Catat Meter Bulanan</h3>
          <p class="text-xs! text-cyan-100! font-medium!">Pencatatan pemakaian air per bulan</p>
          <div
            class="mt-4! flex! items-center! gap-2! text-white! text-[10px]! font-bold! uppercase! tracking-widest!"
          >
            Mulai Tugas
            <font-awesome-icon
              icon="arrow-right"
              class="group-hover:translate-x-1! transition-transform!"
            />
          </div>
        </div>

        <!-- Secondary Action: Daftar Tagihan -->
        <div
          @click="$router.push('/app/teknisi/daftar-tagihan')"
          class="group! cursor-pointer! bg-white! border! border-slate-100! p-6! rounded-3xl! shadow-xs! relative! overflow-hidden! transition-all! hover:-translate-y-1! hover:border-rose-200! hover:shadow-xl! hover:shadow-rose-500/10!"
        >
          <div class="flex! items-start! justify-between! mb-4!">
            <div
              class="w-12! h-12! bg-rose-50! rounded-xl! flex! items-center! justify-center! text-rose-500! text-xl! group-hover:scale-110! transition-transform!"
            >
              <font-awesome-icon icon="file-invoice-dollar" />
            </div>
            <div
              class="px-2.5! py-1! rounded-full! text-[10px]! font-black! uppercase! tracking-widest!"
              :class="unpaidBillsCount > 0 ? 'bg-rose-100! text-rose-600!' : 'bg-emerald-100! text-emerald-600!'"
            >
              {{ unpaidBillsCount > 0 ? `${unpaidBillsCount} Tunggakan` : 'Lunas' }}
            </div>
          </div>
          <h3 class="text-lg! font-black! text-slate-800! mb-1!">Daftar Tagihan</h3>
          <p class="text-xs! text-slate-500! font-medium!">Tagihan pelanggan yang belum dilunasi</p>
          <div
            class="mt-4! flex! items-center! gap-2! text-rose-500! text-[10px]! font-bold! uppercase! tracking-widest!"
          >
            Buka Daftar
            <font-awesome-icon
              icon="arrow-right"
              class="group-hover:translate-x-1! transition-transform!"
            />
          </div>
        </div>

        <!-- Stats: Target Pencatatan -->
        <div
          class="bg-white! border! border-slate-100! p-4! rounded-3xl! shadow-xs! lg:col-span-2! flex! flex-col! justify-center!"
        >
          <div class="flex! items-center! justify-between! mb-3!">
            <div class="flex! items-center! gap-2!">
              <div
                class="w-8! h-8! rounded-lg! bg-emerald-50! text-emerald-500! flex! items-center! justify-center!"
              >
                <font-awesome-icon icon="tasks" class="text-sm!" />
              </div>
              <div>
                <h3 class="text-xs! font-bold! text-slate-800!">Pencatatan Meter Bulanan</h3>
                <p class="text-[9px]! font-black! text-slate-400! uppercase! tracking-widest!">
                  {{ currentMonth }}
                </p>
              </div>
            </div>
            <div class="text-right!">
              <div class="text-xl! font-black! text-slate-800!">
                {{ readCustomers }}
                <span class="text-xs! font-bold! text-slate-400!">/ {{ totalCustomers }}</span>
              </div>
            </div>
          </div>

          <div class="relative! w-full! h-2.5! bg-slate-100! rounded-full! overflow-hidden!">
            <div
              class="absolute! top-0! left-0! h-full! bg-linear-to-r! from-emerald-400! to-emerald-500! rounded-full! transition-all! duration-1000!"
              :style="{ width: meterProgressPercentage + '%' }"
            ></div>
          </div>
          <div class="flex! justify-between! mt-1.5! text-[9px]! font-bold! text-slate-400!">
            <span>{{ meterProgressPercentage }}% Selesai</span>
            <span>{{ unreadCustomers }} Belum Dicatat</span>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid! grid-cols-1! lg:grid-cols-3! gap-8!">
        <!-- Tugas Teknisi (Instalasi + Suspended) -->
        <div class="lg:col-span-2! space-y-6!">
          <div class="flex! items-center! justify-between!">
            <h2 class="text-base! font-black! text-slate-800! flex! items-center! gap-2!">
              <font-awesome-icon icon="clipboard-list" class="text-cyan-500! text-sm!" /> Tugas
              Teknisi
            </h2>
            <button
              v-if="priorityTasks.length > 3"
              @click="isExpanded = !isExpanded"
              class="text-xs! font-bold! text-cyan-600! hover:text-cyan-700!"
            >
              {{ isExpanded ? 'Tampilkan Lebih Sedikit' : 'Lihat Semua' }}
            </button>
          </div>

          <div
            class="space-y-4! pr-2 custom-scrollbar!"
            :class="{ 'max-h-[420px]! overflow-y-auto!': isExpanded && priorityTasks.length > 3 }"
          >
            <!-- Empty State Instalasi -->
            <div
              v-if="displayedTasks.length === 0"
              class="text-center! py-10! bg-slate-50! rounded-2xl! border! border-dashed! border-slate-200!"
            >
              <p class="text-sm! font-bold! text-slate-400!">Tidak ada tugas instalasi saat ini.</p>
            </div>

            <!-- Task Instalasi -->
            <div
              v-for="(task, idx) in displayedTasks"
              :key="idx"
              class="bg-white! p-5! rounded-2xl! shadow-xs! border! border-slate-100! flex! flex-col! sm:flex-row! sm:items-center! justify-between! gap-4! transition-all! hover:shadow-md! hover:border-cyan-100!"
            >
              <div class="flex! items-start! gap-4!">
                <div
                  :class="`w-12! h-12! rounded-xl! shrink-0! flex! items-center! justify-center! text-lg! ${task.bgClass} ${task.textClass}`"
                >
                  <font-awesome-icon :icon="task.icon" />
                </div>
                <div>
                  <div class="flex! items-center! gap-2! mb-1!">
                    <span
                      :class="`text-[9px]! font-black! uppercase! tracking-widest! px-2! py-0.5! rounded-md! ${task.bgClass} ${task.textClass}`"
                      >{{ task.type }}</span
                    >
                    <span class="text-xs! font-bold! text-slate-400!"
                      ><font-awesome-icon icon="clock" class="mr-1!" />{{ task.time }}</span
                    >
                  </div>
                  <h4 class="text-sm! font-bold! text-slate-800! leading-snug!">
                    {{ task.title }}
                  </h4>
                  <p class="text-xs! text-slate-500! mt-1! line-clamp-1!">
                    <font-awesome-icon icon="map-marker-alt" class="text-slate-300! mr-1!" />
                    {{ task.location }}
                  </p>
                </div>
              </div>
              <div class="sm:text-right! shrink-0!">
                <BaseButton
                  v-if="task.type === 'Instalasi'"
                  variant="primary"
                  size="sm"
                  class="w-full! sm:w-auto! text-[10px]! font-black! uppercase!"
                  @click="$router.push(`/app/teknisi/hasil-instalasi/${task.ticketId}`)"
                  >Upload Hasil</BaseButton
                >
                <BaseButton
                  v-else
                  variant="secondary"
                  size="sm"
                  class="w-full! sm:w-auto! text-[10px]! font-black! uppercase!"
                  >Tindak Lanjut</BaseButton
                >
              </div>
            </div>
          </div>

          <!-- Sub-section: Pelanggan Suspended -->
          <div class="pt-2!">
            <div class="flex! items-center! justify-between! mb-3!">
              <h3 class="text-sm! font-black! text-slate-700! flex! items-center! gap-2!">
                <font-awesome-icon icon="ban" class="text-rose-500! text-xs!" /> Pelanggan
                Suspended
              </h3>
              <span
                class="px-2.5! py-1! rounded-full! text-[9px]! font-black! uppercase! tracking-widest!"
                :class="suspendedList.length > 0 ? 'bg-rose-100! text-rose-700!' : 'bg-emerald-100! text-emerald-700!'"
              >
                {{ suspendedList.length }} Aktif
              </span>
            </div>

            <div
              v-if="suspendedList.length === 0"
              class="p-6! text-center! bg-emerald-50/50! border! border-dashed! border-emerald-200! rounded-2xl!"
            >
              <font-awesome-icon
                icon="check-circle"
                class="text-emerald-500! text-xl! mb-1!"
              />
              <p class="text-xs! font-black! text-slate-600!">Tidak ada pelanggan suspended</p>
            </div>

            <div v-else class="space-y-2.5!">
              <div
                v-for="cust in suspendedList"
                :key="cust.id"
                class="bg-white! p-4! rounded-2xl! shadow-xs! border! border-rose-100! flex! items-center! gap-3! transition-all! hover:shadow-md! hover:border-rose-200!"
              >
                <div
                  class="w-10! h-10! rounded-xl! bg-rose-100! text-rose-600! flex! items-center! justify-center! shrink-0! font-black! text-sm!"
                >
                  {{ (cust.name || '?').charAt(0).toUpperCase() }}
                </div>
                <div class="min-w-0! flex-1!">
                  <div class="flex! items-center! gap-2!">
                    <h4 class="text-sm! font-black! text-slate-800! truncate!">{{ cust.name }}</h4>
                    <span
                      class="text-[9px]! font-black! uppercase! px-1.5! py-0.5! rounded! bg-slate-100! text-slate-500! font-mono! shrink-0!"
                      >{{ cust.customer_code }}</span
                    >
                  </div>
                  <div
                    class="flex! items-center! gap-2! mt-1! text-[10px]! font-bold! flex-wrap!"
                  >
                    <span class="text-rose-600!">
                      {{ cust.unpaid_count }} tagihan
                    </span>
                    <span class="text-slate-300!">•</span>
                    <span class="text-slate-700! font-mono!">
                      Rp.
                      {{
                        Number(cust.total_unpaid || 0).toLocaleString('id-ID', {
                          minimumFractionDigits: 0,
                        })
                      }}
                    </span>
                  </div>
                </div>
                <button
                  @click="handleRestore(cust)"
                  :disabled="restoringId === cust.id || cust.unpaid_count > 0"
                  class="px-3! py-2! rounded-xl! text-[10px]! font-black! uppercase! tracking-wider! transition-all! active:scale-95! flex! items-center! gap-1.5! shadow-xs! shrink-0!"
                  :class="
                    cust.unpaid_count > 0
                      ? 'bg-slate-100! text-slate-400! cursor-not-allowed!'
                      : 'bg-emerald-500! hover:bg-emerald-600! text-white! shadow-emerald-200!'
                  "
                >
                  <font-awesome-icon
                    :icon="restoringId === cust.id ? 'spinner' : 'undo'"
                    :spin="restoringId === cust.id"
                  />
                  {{
                    restoringId === cust.id
                      ? '…'
                      : cust.unpaid_count > 0
                        ? 'Tunggu Admin'
                        : 'Aktifkan'
                  }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Stats & Tools (Right Col) -->
        <div class="space-y-6!">
          <h2 class="text-base! font-black! text-slate-800! flex! items-center! gap-2!">
            <font-awesome-icon icon="chart-pie" class="text-cyan-500! text-sm!" /> Statistik
            Pekerjaan
          </h2>

          <ContentCard
            variant="elevated"
            padding="none"
            class="border-0! shadow-xl! shadow-slate-200/40!"
          >
            <div class="p-4! border-b! border-slate-50!">
              <h3 class="text-xs! font-bold! text-slate-800!">Ringkasan Tugas</h3>
            </div>
            <div class="p-4!">
              <div class="grid! grid-cols-2! gap-3!">
                <div
                  v-for="(stat, idx) in techStats"
                  :key="idx"
                  class="bg-slate-50! p-3! rounded-xl! text-center!"
                >
                  <div
                    :class="`w-7! h-7! rounded-lg! mx-auto! mb-1.5! flex! items-center! justify-center! ${stat.bg} ${stat.color}`"
                  >
                    <font-awesome-icon :icon="stat.icon" class="text-xs!" />
                  </div>
                  <div class="text-base! font-black! text-slate-800!">{{ stat.value }}</div>
                  <div
                    class="text-[8px]! font-black! text-slate-400! uppercase! tracking-widest! mt-0.5!"
                  >
                    {{ stat.label }}
                  </div>
                </div>
              </div>
            </div>
          </ContentCard>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/utils/axios'
import { meterService } from '@/services/meter.service'
import { billingService } from '@/services/billing.service'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import { MySwal } from '@/utils/swal'

const isLoading = ref(true)
const dashboardData = ref(null)
const pendingReadingsCount = ref(0)
const unpaidBillsCount = ref(0)
const suspendedList = ref([])
const restoringId = ref(null)
const isExpanded = ref(false)

const userData = JSON.parse(localStorage.getItem('user_data') || '{}')
const teknisiName = ref(userData.name || 'Teknisi')

const currentMonth = computed(() => {
  const months = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
  ]
  const now = new Date()
  return `${months[now.getMonth()]} ${now.getFullYear()}`
})

const fetchSuspended = async () => {
  try {
    const res = await billingService.getSuspendedCustomers()
    suspendedList.value = res?.data || []
  } catch (err) {
    console.error('Gagal memuat pelanggan suspended:', err)
    suspendedList.value = []
  }
}

const handleRestore = async (cust) => {
  if (cust.unpaid_count > 0 || restoringId.value) return

  const ok = await MySwal.fire({
    icon: 'question',
    title: `Kembalikan ${cust.name}?`,
    text: 'Pastikan pelanggan telah melunasi seluruh tagihan. Status akan dikembalikan ke Aktif.',
    showCancelButton: true,
    confirmButtonText: 'Ya, Kembalikan',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#10b981',
    customClass: {
      popup: 'rounded-2xl!',
      confirmButton: 'rounded-xl! px-5! py-2.5! font-bold!',
      cancelButton: 'rounded-xl! px-5! py-2.5! font-bold!',
    },
  })
  if (!ok.isConfirmed) return

  try {
    restoringId.value = cust.id
    await billingService.restoreCustomer(cust.id)
    await MySwal.fire({
      icon: 'success',
      title: 'Berhasil Diaktifkan',
      text: `${cust.name} telah dikembalikan ke status aktif.`,
      confirmButtonColor: '#10b981',
      customClass: { popup: 'rounded-2xl!', confirmButton: 'rounded-xl! px-5! py-2.5! font-bold!' },
    })
    await fetchSuspended()
  } catch (err) {
    MySwal.fire({
      icon: 'error',
      title: 'Gagal',
      text: err.response?.data?.message || 'Tidak dapat mengembalikan status pelanggan.',
      confirmButtonColor: '#ef4444',
    })
  } finally {
    restoringId.value = null
  }
}

const fetchDashboardData = async () => {
  try {
    isLoading.value = true

    const now = new Date()
    const currentMonthNum = now.getMonth() + 1
    const currentYear = now.getFullYear()

    // Gunakan Promise.all agar request berjalan paralel secara efisien
    const [statsRes, pendingRes, unpaidRes] = await Promise.all([
      api.get('/dashboard/statistics'),
      meterService.getPendingReadings({ month: currentMonthNum, year: currentYear }),
      billingService.getBills({ status: 'unpaid', per_page: 1 }).catch(() => null),
    ])

    dashboardData.value = statsRes.data.data || {}

    const totalActive = pendingRes.total_customers || 0
    const belumDicatat = pendingRes.data?.length || 0

    dashboardData.value.total_customers = totalActive
    pendingReadingsCount.value = belumDicatat
    unpaidBillsCount.value =
      unpaidRes?.data?.total ?? unpaidRes?.data?.bills?.length ?? unpaidRes?.total ?? 0

    await fetchSuspended()
  } catch (error) {
    console.error('Error fetching dashboard data:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchDashboardData()
})

// === COMPUTED PROPERTIES MENGGUNAKAN DATA API ASLI ===

// Meter Progress
const totalCustomers = computed(() => dashboardData.value?.total_customers || 0)
const unreadCustomers = computed(() => pendingReadingsCount.value)
const readCustomers = computed(() => Math.max(0, totalCustomers.value - unreadCustomers.value))
const meterProgressPercentage = computed(() => {
  if (totalCustomers.value === 0) return 0
  return Math.round((readCustomers.value / totalCustomers.value) * 100)
})

// Priority Tasks (Diambil dari tiket pemasangan yang perlu instalasi)
const priorityTasks = computed(() => {
  if (!dashboardData.value?.latest_tickets) return []
  return dashboardData.value.latest_tickets
    .filter((ticket) => ticket.status === 'processing' || ticket.status === 'paid')
    .map((ticket) => {
      return {
        ticketId: ticket.id,
        type: 'Instalasi',
        time: new Date(ticket.created_at).toLocaleDateString('id-ID', {
          day: 'numeric',
          month: 'short',
        }),
        title: ticket.applicant_name + ' - ' + (ticket.package?.name || 'Pemasangan Baru'),
        location: ticket.address,
        icon: 'wrench',
        bgClass: 'bg-cyan-50',
        textClass: 'text-cyan-600',
      }
    })
})

const displayedTasks = computed(() => {
  if (isExpanded.value) return priorityTasks.value
  return priorityTasks.value.slice(0, 3)
})

// Statistik Pekerjaan Teknisi
const techStats = computed(() => {
  const statuses = dashboardData.value?.tickets_by_status || {}
  const completed = statuses['completed'] || 0
  const processing = statuses['processing'] || 0

  return [
    {
      label: 'Meter Dicatat',
      value: readCustomers.value.toString(),
      icon: 'check-circle',
      color: 'text-emerald-600',
      bg: 'bg-emerald-100',
    },
    {
      label: 'Belum Dicatat',
      value: unreadCustomers.value.toString(),
      icon: 'clock',
      color: 'text-orange-600',
      bg: 'bg-orange-100',
    },
    {
      label: 'Tagihan Tunggakan',
      value: unpaidBillsCount.value.toString(),
      icon: 'file-invoice-dollar',
      color: 'text-rose-600',
      bg: 'bg-rose-100',
    },
    {
      label: 'Instalasi Selesai',
      value: completed.toString(),
      icon: 'wrench',
      color: 'text-blue-600',
      bg: 'bg-blue-100',
    },
    {
      label: 'Proses Instalasi',
      value: processing.toString(),
      icon: 'tools',
      color: 'text-cyan-600',
      bg: 'bg-cyan-100',
    },
  ]
})
</script>

<style scoped>
.teknisi-dashboard {
  animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: rgba(6, 182, 212, 0.3) transparent;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: rgba(6, 182, 212, 0.3);
  border-radius: 20px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: rgba(6, 182, 212, 0.5);
}
</style>
