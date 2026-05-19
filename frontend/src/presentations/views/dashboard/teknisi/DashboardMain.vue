<template>
  <div class="teknisi-dashboard p-4! lg:p-8!">
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
          <h1 class="text-3xl! font-extrabold! text-slate-800! tracking-tight!">
            Technical <span class="text-cyan-500!">Operations</span>
          </h1>
          <p class="text-slate-500! mt-1! font-medium!">
            Ringkasan tugas lapangan, pencatatan meter, dan pemeliharaan jaringan.
          </p>
        </div>
        <div class="flex! items-center! gap-4!">
          <div
            class="bg-white! px-5! py-3! rounded-2xl! shadow-sm! border! border-slate-100! flex! items-center! gap-4!"
          >
            <div
              class="w-10! h-10! rounded-xl! bg-cyan-50! text-cyan-600! flex! items-center! justify-center!"
            >
              <font-awesome-icon icon="calendar-day" />
            </div>
            <div>
              <div class="text-[10px]! font-black! text-slate-400! uppercase! tracking-widest!">
                Periode Aktif
              </div>
              <div class="text-sm! font-bold! text-slate-700!">Bulan Ini</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions (Top Row) -->
      <div class="grid! grid-cols-1! md:grid-cols-2! lg:grid-cols-4! gap-6! mb-10!">
        <!-- Primary Action: Catat Meter -->
        <div
          @click="$router.push('/instalasi/teknisiPemakaianAir')"
          class="group! cursor-pointer! bg-gradient-to-br! from-cyan-500! to-blue-600! p-6! rounded-3xl! shadow-lg! shadow-cyan-500/30! relative! overflow-hidden! transition-all! hover:-translate-y-1! hover:shadow-cyan-500/50!"
        >
          <div
            class="absolute! top-0! right-0! w-32! h-32! bg-white/10! rounded-full! blur-2xl! -mr-10! -mt-10!"
          ></div>
          <div
            class="w-12! h-12! bg-white/20! backdrop-blur-sm! rounded-xl! flex! items-center! justify-center! text-white! text-xl! mb-4! group-hover:scale-110! transition-transform!"
          >
            <font-awesome-icon icon="tachometer-alt" />
          </div>
          <h3 class="text-xl! font-black! text-white! mb-1!">Catat Meter</h3>
          <p class="text-xs! text-cyan-100! font-medium!">Input pemakaian pelanggan bulan ini</p>
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

        <!-- Secondary Action: Laporan Gangguan -->
        <div
          class="group! cursor-pointer! bg-white! border! border-slate-100! p-6! rounded-3xl! shadow-sm! relative! overflow-hidden! transition-all! hover:-translate-y-1! hover:border-amber-200! hover:shadow-xl! hover:shadow-amber-500/10!"
        >
          <div
            class="w-12! h-12! bg-amber-50! rounded-xl! flex! items-center! justify-center! text-amber-500! text-xl! mb-4! group-hover:scale-110! transition-transform!"
          >
            <font-awesome-icon icon="exclamation-triangle" />
          </div>
          <h3 class="text-xl! font-black! text-slate-800! mb-1!">Gangguan</h3>
          <p class="text-xs! text-slate-500! font-medium!">Tinjau laporan kebocoran / macet</p>
        </div>

        <!-- Stats: Target Pencatatan -->
        <div
          class="bg-white! border! border-slate-100! p-6! rounded-3xl! shadow-sm! lg:col-span-2! flex! flex-col! justify-center!"
        >
          <div class="flex! items-center! justify-between! mb-4!">
            <div class="flex! items-center! gap-3!">
              <div
                class="w-10! h-10! rounded-xl! bg-emerald-50! text-emerald-500! flex! items-center! justify-center!"
              >
                <font-awesome-icon icon="tasks" />
              </div>
              <div>
                <h3 class="text-sm! font-bold! text-slate-800!">Progres Catat Meter</h3>
                <p class="text-[10px]! font-black! text-slate-400! uppercase! tracking-widest!">
                  Bulan Ini
                </p>
              </div>
            </div>
            <div class="text-right!">
              <div class="text-2xl! font-black! text-slate-800!">
                {{ readCustomers }}
                <span class="text-sm! font-bold! text-slate-400!">/ {{ totalCustomers }}</span>
              </div>
            </div>
          </div>

          <div class="relative! w-full! h-3! bg-slate-100! rounded-full! overflow-hidden!">
            <div
              class="absolute! top-0! left-0! h-full! bg-gradient-to-r! from-emerald-400! to-emerald-500! rounded-full! transition-all! duration-1000!"
              :style="{ width: meterProgressPercentage + '%' }"
            ></div>
          </div>
          <div class="flex! justify-between! mt-2! text-[10px]! font-bold! text-slate-400!">
            <span>{{ meterProgressPercentage }}% Selesai</span>
            <span>{{ unreadCustomers }} Belum Dicatat</span>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid! grid-cols-1! lg:grid-cols-3! gap-8!">
        <!-- Priority Tasks (Left, takes 2 cols) -->
        <div class="lg:col-span-2! space-y-6!">
          <div class="flex! items-center! justify-between!">
            <h2 class="text-lg! font-black! text-slate-800! flex! items-center! gap-2!">
              <font-awesome-icon icon="clipboard-list" class="text-cyan-500!" /> Tugas Pemasangan /
              Survei
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
            <!-- Empty State -->
            <div
              v-if="displayedTasks.length === 0"
              class="text-center! py-10! bg-slate-50! rounded-2xl! border! border-dashed! border-slate-200!"
            >
              <p class="text-sm! font-bold! text-slate-400!">Tidak ada tugas prioritas saat ini.</p>
            </div>

            <!-- Task Item -->
            <div
              v-for="(task, idx) in displayedTasks"
              :key="idx"
              class="bg-white! p-5! rounded-2xl! shadow-sm! border! border-slate-100! flex! flex-col! sm:flex-row! sm:items-center! justify-between! gap-4! transition-all! hover:shadow-md! hover:border-cyan-100!"
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
                  variant="secondary"
                  size="sm"
                  class="w-full! sm:w-auto! text-[10px]! font-black! uppercase!"
                  >Tindak Lanjut</BaseButton
                >
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Stats & Tools (Right Col) -->
        <div class="space-y-6!">
          <h2 class="text-lg! font-black! text-slate-800! flex! items-center! gap-2!">
            <font-awesome-icon icon="chart-pie" class="text-cyan-500!" /> Ikhtisar Kinerja
          </h2>

          <ContentCard
            variant="elevated"
            padding="none"
            class="border-0! shadow-xl! shadow-slate-200/40!"
          >
            <div class="p-6! border-b! border-slate-50!">
              <h3 class="text-sm! font-bold! text-slate-800!">Statistik Sistem</h3>
            </div>
            <div class="p-6!">
              <div class="grid! grid-cols-2! gap-4!">
                <div
                  v-for="(stat, idx) in techStats"
                  :key="idx"
                  class="bg-slate-50! p-4! rounded-2xl! text-center!"
                >
                  <div
                    :class="`w-8! h-8! rounded-lg! mx-auto! mb-2! flex! items-center! justify-center! ${stat.bg} ${stat.color}`"
                  >
                    <font-awesome-icon :icon="stat.icon" class="text-sm!" />
                  </div>
                  <div class="text-lg! font-black! text-slate-800!">{{ stat.value }}</div>
                  <div
                    class="text-[9px]! font-black! text-slate-400! uppercase! tracking-widest! mt-1!"
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
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'

const isLoading = ref(true)
const dashboardData = ref(null)
const pendingReadingsCount = ref(0)
const isExpanded = ref(false)

const fetchDashboardData = async () => {
  try {
    isLoading.value = true

    // Gunakan Promise.all agar request berjalan paralel secara efisien
    const [statsRes, pendingRes] = await Promise.all([
      api.get('/dashboard/statistics'),
      meterService.getPendingReadings(),
    ])

    dashboardData.value = statsRes.data.data
    pendingReadingsCount.value = pendingRes.data?.data?.length || pendingRes.data?.length || 0
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

// Priority Tasks (Diambil dari tiket pemasangan terbaru)
const priorityTasks = computed(() => {
  if (!dashboardData.value?.latest_tickets) return []
  return dashboardData.value.latest_tickets.map((ticket) => {
    return {
      type: ticket.status === 'pending' ? 'Perlu Survei' : 'Instalasi',
      time: new Date(ticket.created_at).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
      }),
      title: ticket.applicant_name + ' - ' + (ticket.package?.name || 'Pemasangan Baru'),
      location: ticket.address,
      icon: ticket.status === 'pending' ? 'search' : 'wrench',
      bgClass: ticket.status === 'pending' ? 'bg-amber-50' : 'bg-emerald-50',
      textClass: ticket.status === 'pending' ? 'text-amber-500' : 'text-emerald-500',
    }
  })
})

const displayedTasks = computed(() => {
  if (isExpanded.value) return priorityTasks.value
  return priorityTasks.value.slice(0, 3)
})

// Kinerja (Diambil dari tiket by status)
const techStats = computed(() => {
  const statuses = dashboardData.value?.tickets_by_status || {}
  const completed = statuses['completed'] || 0
  const processing = statuses['processing'] || 0
  const pending = statuses['pending'] || 0

  return [
    {
      label: 'Tiket Selesai',
      value: completed.toString(),
      icon: 'check',
      color: 'text-emerald-600',
      bg: 'bg-emerald-100',
    },
    {
      label: 'Proses Instalasi',
      value: processing.toString(),
      icon: 'wrench',
      color: 'text-blue-600',
      bg: 'bg-blue-100',
    },
    {
      label: 'Antrean Survei',
      value: pending.toString(),
      icon: 'tools',
      color: 'text-amber-600',
      bg: 'bg-amber-100',
    },
    {
      label: 'Total Pelanggan',
      value: totalCustomers.value.toString(),
      icon: 'users',
      color: 'text-indigo-600',
      bg: 'bg-indigo-100',
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
