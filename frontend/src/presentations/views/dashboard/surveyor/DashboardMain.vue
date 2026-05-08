<template>
  <div class="surveyor-dashboard-modern">
    <div class="relative! mb-10! overflow-hidden! rounded-2xl! bg-gradient-to-br! from-slate-900! via-slate-800! to-slate-900! p-6! lg:p-8! shadow-2xl! shadow-slate-900/20!">
      <div class="absolute! top-0! right-0! w-64! h-64! bg-orange-500/10! rounded-full! blur-3xl! -mr-32! -mt-32!"></div>
      <div class="absolute! bottom-0! left-0! w-48! h-48! bg-indigo-500/10! rounded-full! blur-3xl! -ml-24! -mb-24!"></div>
      
      <div class="relative! z-10! flex! flex-col! lg:flex-row! lg:items-center! justify-between! gap-8!">
        <div>
          <div class="inline-flex! items-center! gap-2! bg-orange-500/10! border! border-orange-500/20! px-4! py-1.5! rounded-full! mb-4!">
            <span class="w-2! h-2! bg-orange-500! rounded-full! animate-pulse!"></span>
            <span class="text-xs! font-black! text-orange-400! uppercase! tracking-widest!">Field Operations Live</span>
          </div>
          <h1 class="text-2xl! lg:text-4xl! font-black! text-white! tracking-tight! leading-tight! whitespace-nowrap!">
            Selamat Pagi, <span class="text-transparent! bg-clip-text! bg-gradient-to-r! from-orange-400! to-orange-200!">{{ surveyorName }}</span>
          </h1>
          <p class="text-slate-400! mt-4! text-base! font-medium! text-justify! lg:whitespace-nowrap!">
            Berikut adalah ringkasan performa dan daftar antrian survey lapangan Anda untuk hari ini.
          </p>
        </div>
        
        <div class="flex! items-center! gap-4! w-full! lg:w-auto!">
          <div class="bg-white/5! backdrop-blur-md! border! border-white/10! p-4! rounded-3xl! w-full! md:min-w-[180px]!">
            <div class="text-xs! font-bold! text-slate-500! uppercase! tracking-wider! mb-1!">Penyelesaian Hari Ini</div>
            <div class="flex! items-end! gap-3!">
              <div class="text-3xl! font-black! text-white!">
                {{ completionRate }}%
              </div>
              <div class="text-xs! font-bold! text-emerald-400! mb-1.5! flex! items-center! gap-1!">
                <font-awesome-icon icon="arrow-up" /> {{ completionTrend }}%
              </div>
            </div>
            <div class="w-full! h-1.5! bg-white/10! rounded-full! mt-3! overflow-hidden!">
              <div 
                class="h-full! bg-orange-500! rounded-full! transition-all! duration-1000!" 
                :style="{ width: completionRate + '%' }"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="grid! grid-cols-1! lg:grid-cols-12! gap-6! lg:gap-8!">
      
      <div class="lg:col-span-8! space-y-6! lg:space-y-8!">
        <div class="grid! grid-cols-1! sm:grid-cols-2! lg:grid-cols-3! gap-4! lg:gap-6!">
          <div
            v-for="(stat, idx) in stats"
            :key="idx"
            class="group! relative! bg-white! p-5! rounded-2xl! border! border-slate-100! transition-all! duration-300! hover:shadow-lg! flex! items-center! gap-4!"
            style="box-shadow: 0 4px 20px -10px rgba(0,0,0,0.05);"
          >
            <div
              :class="`w-12! h-12! rounded-xl! ${stat.bg}! ${stat.color}! flex! items-center! justify-center! text-lg! shrink-0!`"
            >
              <font-awesome-icon :icon="stat.icon" />
            </div>
            <div class="min-w-0!">
              <p class="text-xs! font-bold! text-slate-400! uppercase! tracking-wider! leading-none! mb-1.5!">
                {{ stat.label }}
              </p>
              <div class="flex! items-baseline! gap-2!">
                <h3 class="text-2xl! font-black! text-slate-800! tracking-tight! leading-none!">
                  {{ stat.value }}
                </h3>
                <span class="text-[11px]! font-bold! text-emerald-500! whitespace-nowrap!">
                   {{ stat.trend }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <ContentCard
            variant="default"
            padding="large"
            class="border-0 shadow-sm"
          >
            <div class="flex items-center gap-3 mb-6">
              <div class="w-1.5 h-5 bg-orange-500 rounded-full"></div>
              <h2 class="text-lg font-bold text-slate-800">Distribusi Jarak Pipa</h2>
            </div>
            <div style="height: 180px; position: relative;">
              <PrimeChart type="bar" :data="barChartData" :options="barChartOptions" />
            </div>
          </ContentCard>

          <ContentCard
            variant="default"
            padding="large"
            class="border-0 shadow-sm"
          >
            <div class="flex items-center gap-3 mb-6">
              <div class="w-1.5 h-5 bg-emerald-500 rounded-full"></div>
              <h2 class="text-lg font-bold text-slate-800">Status Verifikasi</h2>
            </div>
            <div style="height: 180px; position: relative;" class="flex items-center justify-center">
              <PrimeChart type="doughnut" :data="doughnutData" :options="doughnutOptions" class="w-full" />
            </div>
          </ContentCard>
        </div>
      </div>

      <div class="lg:col-span-4!">
        <ContentCard
          variant="default"
          padding="none"
          class="h-full! border-0! rounded-2xl! overflow-hidden! shadow-sm!"
        >
          <div class="p-6! border-b! border-slate-50! flex! items-center! justify-between! bg-slate-50/30!">
            <div>
              <h2 class="text-lg! font-black! text-slate-800! tracking-tight!">Antrian Survey</h2>
              <p class="text-[10px]! font-bold! text-slate-400! uppercase! tracking-widest! mt-0.5!">Prioritas Waktu</p>
            </div>
            <div class="flex! items-center! gap-2!">
              <span class="w-2! h-2! bg-emerald-500! rounded-full! animate-pulse!"></span>
              <span class="text-[10px]! font-black! text-slate-600!">AKTIF</span>
            </div>
          </div>
          
          <div class="p-4! space-y-4! max-h-[380px]! overflow-y-auto! custom-scrollbar!">
            <div v-if="tasks.length === 0" class="py-20! text-center!">
              <div class="w-16! h-16! bg-slate-50! rounded-full! flex! items-center! justify-center! mx-auto! mb-4! text-slate-300!">
                <font-awesome-icon icon="clipboard-check" size="2x" />
              </div>
              <p class="text-xs! font-bold! text-slate-400!">Semua survey telah selesai!</p>
            </div>

            <div
              v-for="(task, idx) in tasks"
              :key="idx"
              class="group! relative! bg-white! rounded-2xl! border! border-slate-100! p-4! transition-all! duration-300! hover:shadow-xl! hover:shadow-slate-200/50! hover:border-orange-200!"
            >
              <div class="flex! items-center! justify-between! mb-3!">
                <div class="flex! items-center! gap-2!">
                  <div class="w-1.5! h-1.5! bg-orange-500! rounded-full!"></div>
                  <span class="text-[11px]! font-bold! text-slate-400! tracking-wider!">#{{ task.id }}</span>
                </div>
                <div class="flex! items-center! gap-1! bg-orange-50! px-2! py-0.5! rounded-lg!">
                  <span class="text-[10px]! font-bold! text-orange-600! uppercase!">BARU</span>
                </div>
              </div>

              <div class="mb-4!">
                <h3 class="text-sm! font-black! text-slate-800! mb-1! group-hover:text-orange-600! transition-colors!">
                  {{ task.name }}
                </h3>
                <div class="flex! items-start! gap-2!">
                  <font-awesome-icon icon="map-pin" class="text-slate-300! text-xs! mt-0.5!" />
                  <p class="text-xs! font-bold! text-slate-500! leading-tight!">
                    {{ task.area }}
                  </p>
                </div>
              </div>

              <div class="flex! items-center! justify-between! pt-3! border-t! border-slate-50!">
                <div class="flex! -space-x-1.5!">
                   <div class="w-6! h-6! rounded-full! border-2! border-white! bg-orange-100! flex! items-center! justify-center! text-[10px]! font-bold! text-orange-600!">S</div>
                </div>
                <BaseButton
                  variant="primary-gradient"
                  size="sm"
                  class="rounded-xl! text-[11px]! font-bold! px-3! h-8!"
                  @click="$router.push({ path: '/survey/create', query: { id: task.id } })"
                >
                  MULAI SURVEY
                </BaseButton>
              </div>
            </div>
          </div>
          
          <div v-if="tasks.length > 0" class="p-6! pt-2!">
            <button 
              class="w-full! py-3! border-2! border-dashed! border-slate-200! rounded-xl! text-[10px]! font-bold! text-slate-400! hover:border-orange-300! hover:text-orange-500! transition-all!"
              @click="$router.push('/survey/create')"
            >
              Lihat Semua Antrian
            </button>
          </div>
        </ContentCard>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import ticketService from '@/services/ticket.service'

const router = useRouter()
const tasks = ref([])
const loading = ref(false)
const userData = JSON.parse(localStorage.getItem('user_data') || '{}')
const surveyorName = ref(userData.name || 'Surveyor')
const completionRate = ref(0)
const completionTrend = ref('+12')

const stats = ref([
  {
    label: 'Total Survey',
    value: '0',
    icon: 'clipboard-list',
    bg: 'bg-indigo-50',
    color: 'text-indigo-600',
    trend: 'Total Keseluruhan',
  },
  {
    label: 'Antrian Survey',
    value: '0',
    icon: 'clock',
    bg: 'bg-orange-50',
    color: 'text-orange-600',
    trend: 'Butuh Segera',
  },
  {
    label: 'Selesai Verif',
    value: '0',
    icon: 'check-double',
    bg: 'bg-emerald-50',
    color: 'text-emerald-600',
    trend: 'Berhasil Survey',
  },
])

onMounted(async () => {
  fetchDashboardData()
})

const fetchDashboardData = async () => {
  try {
    loading.value = true
    
    const pendingRes = await ticketService.getTickets({ status: 'pending' })
    const pendingTickets = pendingRes.data.data || []
    
    const surveyedRes = await ticketService.getTickets({ status: 'surveyed' })
    const surveyedTickets = surveyedRes.data.data || []
    
    tasks.value = pendingTickets.slice(0, 15).map(t => ({
      id: t.id,
      name: t.applicant_name,
      area: t.address,
      status: 'Pending'
    }))

    const totalPending = pendingRes.data.total || pendingTickets.length
    const totalSurveyed = surveyedRes.data.total || surveyedTickets.length
    const grandTotal = totalPending + totalSurveyed
    
    stats.value[0].value = grandTotal.toString()
    stats.value[1].value = totalPending.toString()
    stats.value[2].value = totalSurveyed.toString()

    if (grandTotal > 0) {
      completionRate.value = Math.round((totalSurveyed / grandTotal) * 100)
    } else {
      completionRate.value = 0
    }

    const dist = [0, 0, 0, 0]
    surveyedTickets.forEach(t => {
      const survey = Array.isArray(t.survey) ? t.survey[0] : t.survey
      if (survey) {
        const d = parseFloat(survey.distance_to_pipe_m)
        if (!isNaN(d)) {
          if (d < 5) dist[0]++
          else if (d < 15) dist[1]++
          else if (d <= 30) dist[2]++
          else dist[3]++
        }
      }
    })
    
    barChartData.value = {
      labels: ['< 5m', '5-15m', '15-30m', '> 30m'],
      datasets: [
        {
          label: 'Jumlah Tiket',
          data: dist,
          backgroundColor: ['#6366f1', '#f97316', '#10b981', '#ef4444'],
          borderRadius: 12,
        }
      ]
    }

    doughnutData.value = {
      labels: ['Survey Selesai', 'Ditolak', 'Pending'],
      datasets: [
        {
          data: [totalSurveyed, 0, totalPending],
          backgroundColor: ['#10b981', '#ef4444', '#f97316'],
          hoverOffset: 4,
          borderWidth: 0,
        }
      ]
    }
    
  } catch (err) {
    console.error('Failed to fetch dashboard data:', err)
  } finally {
    loading.value = false
  }
}

const scrollToQueue = () => {
  const queueSection = document.querySelector('.lg\\:col-span-4')
  if (queueSection) {
    queueSection.scrollIntoView({ behavior: 'smooth' })
  }
}

const lineChartData = ref({
  labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
  datasets: [
    {
      label: 'Survey Selesai',
      data: [12, 19, 15, 25, 22, 30, 28],
      fill: true,
      borderColor: '#f97316',
      backgroundColor: 'rgba(249, 115, 22, 0.05)',
      tension: 0.4,
      pointBackgroundColor: '#f97316',
      pointBorderColor: '#fff',
      pointBorderWidth: 2,
      pointRadius: 4,
    },
    {
      label: 'Target',
      data: [15, 15, 15, 15, 15, 15, 15],
      borderColor: '#cbd5e1',
      borderDash: [5, 5],
      pointRadius: 0,
      fill: false,
    }
  ]
})

const lineChartOptions = ref({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: {
        display: true,
        color: '#f1f5f9'
      },
      ticks: {
        font: { size: 10, weight: 'bold' },
        color: '#94a3b8'
      }
    },
    x: {
      grid: {
        display: false
      },
      ticks: {
        font: { size: 10, weight: 'bold' },
        color: '#94a3b8'
      }
    }
  }
})

const barChartData = ref({
  labels: ['< 5m', '5-15m', '15-30m', '> 30m'],
  datasets: [
    {
      label: 'Jumlah Tiket',
      data: [0, 0, 0, 0],
      backgroundColor: ['#6366f1', '#f97316', '#10b981', '#ef4444'],
      borderRadius: 12,
    }
  ]
})

const barChartOptions = ref({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#1e293b',
      titleFont: { size: 13, weight: 'bold' },
      bodyFont: { size: 13 },
      padding: 12,
      cornerRadius: 8,
      displayColors: false
    }
  },
  scales: {
    y: { 
      display: true,
      beginAtZero: true,
      ticks: {
        stepSize: 1,
        callback: (value) => (Number.isInteger(value) ? value : null),
        font: { size: 12, weight: 'bold' },
        color: '#94a3b8'
      },
      grid: { color: '#f8fafc' }
    },
    x: {
      grid: { display: false },
      ticks: { 
        font: { size: 12, weight: 'bold' }, 
        color: '#64748b',
        padding: 12
      }
    }
  },
  barPercentage: 0.7,
  categoryPercentage: 0.9
})

const doughnutData = ref({
  labels: ['Survey Selesai', 'Ditolak', 'Pending'],
  datasets: [
    {
      data: [0, 0, 0],
      backgroundColor: ['#10b981', '#ef4444', '#f97316'],
      hoverOffset: 12,
      borderWidth: 0,
    }
  ]
})

const doughnutOptions = ref({
  responsive: true,
  maintainAspectRatio: false,
  cutout: '65%',
  plugins: {
    legend: {
      position: 'right',
      align: 'center',
      labels: {
        usePointStyle: true,
        pointStyle: 'circle',
        padding: 20,
        font: { size: 12, weight: 'bold' },
        color: '#64748b'
      }
    },
    tooltip: {
      backgroundColor: '#1e293b',
      padding: 12,
      cornerRadius: 8,
    }
  }
})
</script>

<style scoped>
.surveyor-dashboard-modern {
  animation: slideIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.p-4::-webkit-scrollbar {
  width: 4px;
}
.p-4::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
</style>
