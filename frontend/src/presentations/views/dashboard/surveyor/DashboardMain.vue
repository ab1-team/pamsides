<template>
  <div class="surveyor-dashboard-modern">
    <div class="relative! mb-10! overflow-hidden! rounded-2xl! bg-gradient-to-br! from-slate-900! via-slate-800! to-slate-900! p-6! lg:p-8! shadow-2xl! shadow-slate-900/20!">
      <div class="absolute! top-0! right-0! w-64! h-64! bg-orange-500/10! rounded-full! blur-3xl! -mr-32! -mt-32!"></div>
      <div class="absolute! bottom-0! left-0! w-48! h-48! bg-indigo-500/10! rounded-full! blur-3xl! -ml-24! -mb-24!"></div>
      
      <div class="relative! z-10! flex! flex-col! lg:flex-row! lg:items-center! justify-between! gap-8!">
        <div>
          <div class="inline-flex! items-center! gap-2! bg-orange-500/10! border! border-orange-500/20! px-4! py-1.5! rounded-full! mb-2!">
            <span class="w-2! h-2! bg-orange-500! rounded-full! animate-pulse!"></span>
            <span class="text-xs! font-black! text-orange-400! uppercase! tracking-widest!">Field Operations Live</span>
          </div>
          <h1 class="text-3xl! lg:text-4xl! font-black! text-white! tracking-tight! leading-tight!">
            Selamat Pagi, <span class="text-transparent! bg-clip-text! bg-gradient-to-r! from-orange-400! to-orange-200!">Surveyor</span>
          </h1>
          <p class="text-slate-400! mt-2! text-base! font-medium! lg:whitespace-nowrap!">
            Berikut adalah ringkasan performa dan daftar antrian survey lapangan Anda untuk hari ini.
          </p>
        </div>
        
        <div class="flex! items-center! gap-4!">
          <div class="bg-white/5! backdrop-blur-md! border! border-white/10! p-4! rounded-3xl! min-w-[180px]!">
            <div class="text-[10px]! font-black! text-slate-500! uppercase! tracking-widest! mb-1!">Penyelesaian Hari Ini</div>
            <div class="flex! items-end! gap-3!">
              <div class="text-3xl! font-black! text-white!">75%</div>
              <div class="text-xs! font-bold! text-emerald-400! mb-1.5! flex! items-center! gap-1!">
                <font-awesome-icon icon="arrow-up" /> +12%
              </div>
            </div>
            <div class="w-full! h-1.5! bg-white/10! rounded-full! mt-3! overflow-hidden!">
              <div class="h-full! bg-orange-500! rounded-full!" style="width: 75%"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="grid! grid-cols-1! md:grid-cols-2! lg:grid-cols-4! gap-6! mb-10!">
      <div
        v-for="(stat, idx) in stats"
        :key="idx"
        class="group! relative! bg-white! p-4! rounded-2xl! border! border-slate-100! transition-all! duration-500! hover:-translate-y-1!"
        style="box-shadow: var(--card-shadow-base);"
      >
        <div class="flex! items-start! justify-between! mb-4!">
          <div
            :class="`w-10! h-10! rounded-lg! ${stat.bg}! ${stat.color}! flex! items-center! justify-center! text-base! shadow-inner! group-hover:scale-110! transition-transform! duration-500!`"
          >
            <font-awesome-icon :icon="stat.icon" />
          </div>
          <div class="px-3! py-1! rounded-full! bg-slate-50! text-[10px]! font-black! text-slate-400! uppercase! tracking-wider!">
            {{ stat.trend }}
          </div>
        </div>
        <div class="space-y-1!">
          <h3 class="text-xl! font-black! text-slate-800! tracking-tight!">
            {{ stat.value }}
          </h3>
          <p class="text-sm! font-bold! text-slate-400! uppercase! tracking-widest! text-[10px]!">
            {{ stat.label }}
          </p>
        </div>
      </div>
    </div>

    <div class="grid! grid-cols-1! lg:grid-cols-12! gap-8!">
      <div class="lg:col-span-8! space-y-8!">
        <ContentCard
          variant="default"
          padding="large"
          class="border-0!"
        >
          <template #header>
            <div class="flex! items-center! justify-between! mb-2!">
              <div class="flex! items-center! gap-3!">
                <div class="w-1.5! h-6! bg-indigo-500! rounded-full!"></div>
                <h2 class="text-xl! font-extrabold! text-slate-800!">Analisis Aktivitas Survey</h2>
              </div>
              <div class="flex! gap-2!">
                <button class="px-4! py-1.5! rounded-xl! bg-slate-50! text-xs! font-bold! text-slate-600! border! border-slate-100!">Mingguan</button>
                <button class="px-4! py-1.5! rounded-xl! bg-white! text-xs! font-bold! text-slate-400! border! border-transparent!">Bulanan</button>
              </div>
            </div>
          </template>

          <div class="h-[350px]! mt-6!">
            <PrimeChart type="line" :data="lineChartData" :options="lineChartOptions" class="h-full!" />
          </div>
        </ContentCard>

        <div class="grid! grid-cols-1! md:grid-cols-2! gap-8!">
          <ContentCard
            variant="default"
            padding="large"
            class="border-0!"
          >
            <div class="flex! items-center! gap-3! mb-6!">
              <div class="w-1.5! h-5! bg-orange-500! rounded-full!"></div>
              <h2 class="text-lg! font-bold! text-slate-800!">Distribusi Jarak Pipa</h2>
            </div>
            <div class="h-[250px]!">
              <PrimeChart type="bar" :data="barChartData" :options="barChartOptions" class="h-full!" />
            </div>
          </ContentCard>

          <ContentCard
            variant="default"
            padding="large"
            class="border-0!"
          >
            <div class="flex! items-center! gap-3! mb-6!">
              <div class="w-1.5! h-5! bg-emerald-500! rounded-full!"></div>
              <h2 class="text-lg! font-bold! text-slate-800!">Status Verifikasi</h2>
            </div>
            <div class="h-[250px]! flex! items-center! justify-center!">
              <PrimeChart type="doughnut" :data="doughnutData" :options="doughnutOptions" class="h-full! w-full!" />
            </div>
          </ContentCard>
        </div>
      </div>

      <div class="lg:col-span-4!">
        <ContentCard
          variant="default"
          padding="none"
          class="h-full! border-0! rounded-2xl! overflow-hidden!"
        >
          <div class="p-6! border-b! border-slate-50! flex! items-center! justify-between! bg-slate-50/30!">
            <div>
              <h2 class="text-lg! font-black! text-slate-800! tracking-tight!">Antrian Survey</h2>
              <p class="text-[10px]! font-bold! text-slate-400! uppercase! tracking-widest! mt-0.5!">Prioritas Waktu</p>
            </div>
            <div class="flex! items-center! gap-2!">
              <span class="w-2! h-2! bg-emerald-500! rounded-full! animate-pulse!"></span>
              <span class="text-[10px]! font-black! text-slate-600!">4 ACTIVE</span>
            </div>
          </div>
          
          <div class="p-4! space-y-4!">
            <div
              v-for="(task, idx) in tasks"
              :key="idx"
              class="group! relative! bg-white! rounded-3xl! border! border-slate-100! p-5! transition-all! duration-300! hover:shadow-xl! hover:shadow-slate-200/50! hover:border-orange-200!"
            >
              <div class="flex! items-center! justify-between! mb-4!">
                <div class="flex! items-center! gap-2!">
                  <div class="w-2! h-2! bg-orange-500! rounded-full!"></div>
                  <span class="text-[10px]! font-black! text-slate-400! tracking-widest!">#{{ task.id }}</span>
                </div>
                <div class="flex! items-center! gap-1.5! bg-orange-50! px-2! py-1! rounded-lg!">
                  <font-awesome-icon icon="clock" class="text-[10px]! text-orange-600!" />
                  <span class="text-[9px]! font-black! text-orange-600! uppercase!">15 Min</span>
                </div>
              </div>

              <div class="mb-4!">
                <h3 class="text-base! font-black! text-slate-800! mb-1! group-hover:text-orange-600! transition-colors!">
                  {{ task.name }}
                </h3>
                <div class="flex! items-start! gap-2!">
                  <font-awesome-icon icon="map-pin" class="text-slate-300! mt-1!" />
                  <p class="text-xs! font-bold! text-slate-500! leading-relaxed!">
                    {{ task.area }}
                  </p>
                </div>
              </div>

              <div class="flex! items-center! justify-between! pt-4! border-t! border-slate-50!">
                <div class="flex! -space-x-2!">
                  <div class="w-7! h-7! rounded-full! border-2! border-white! bg-slate-100! flex! items-center! justify-center! text-[10px]! font-bold! text-slate-400!">A</div>
                  <div class="w-7! h-7! rounded-full! border-2! border-white! bg-orange-100! flex! items-center! justify-center! text-[10px]! font-bold! text-orange-600!">S</div>
                </div>
                <BaseButton
                  variant="primary-gradient"
                  size="sm"
                  class="rounded-xl! text-[10px]! font-black! px-4! h-9! shadow-lg! shadow-orange-500/20!"
                  @click="$router.push('/survey/input')"
                >
                  MULAI SURVEY
                </BaseButton>
              </div>
            </div>
          </div>
          
          <div class="p-6! pt-2!">
            <button class="w-full! py-3! border-2! border-dashed! border-slate-200! rounded-2xl! text-xs! font-bold! text-slate-400! hover:border-orange-300! hover:text-orange-500! transition-all!">
              Lihat Seluruh Riwayat Antrian
            </button>
          </div>
        </ContentCard>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'

const stats = ref([
  {
    label: 'Total Survey',
    value: '1,284',
    icon: 'clipboard-list',
    bg: 'bg-indigo-50',
    color: 'text-indigo-600',
    trend: '+12% bln ini',
  },
  {
    label: 'Menunggu Verif',
    value: '42',
    icon: 'clock',
    bg: 'bg-orange-50',
    color: 'text-orange-600',
    trend: 'Butuh Segera',
  },
  {
    label: 'Tingkat Aktivasi',
    value: '98%',
    icon: 'check-double',
    bg: 'bg-emerald-50',
    color: 'text-emerald-600',
    trend: 'Sangat Baik',
  },
  {
    label: 'Wilayah Aktif',
    value: '12',
    icon: 'map-marked-alt',
    bg: 'bg-blue-50',
    color: 'text-blue-600',
    trend: 'Dusun Wetan',
  },
])

const tasks = ref([
  { id: 'SRV-0921', name: 'Bpk. Ahmad Subarjo', area: 'Dusun Wetan, RT 04/02', status: 'Pending' },
  { id: 'SRV-0922', name: 'Ibu Ratna Sari', area: 'Perum Asri, Blok C/12', status: 'Pending' },
  { id: 'SRV-0923', name: 'Klinik Sehat', area: 'Jl. Utama No. 88', status: 'Pending' },
])

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
      data: [45, 120, 65, 25],
      backgroundColor: ['#6366f1', '#f97316', '#10b981', '#ef4444'],
      borderRadius: 12,
    }
  ]
})

const barChartOptions = ref({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false }
  },
  scales: {
    y: { display: false },
    x: {
      grid: { display: false },
      ticks: { font: { size: 10, weight: 'bold' }, color: '#94a3b8' }
    }
  }
})

const doughnutData = ref({
  labels: ['Disetujui', 'Ditolak', 'Pending'],
  datasets: [
    {
      data: [85, 5, 10],
      backgroundColor: ['#10b981', '#ef4444', '#f97316'],
      hoverOffset: 4,
      borderWidth: 0,
    }
  ]
})

const doughnutOptions = ref({
  responsive: true,
  maintainAspectRatio: false,
  cutout: '75%',
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        usePointStyle: true,
        padding: 20,
        font: { size: 10, weight: 'bold' },
        color: '#64748b'
      }
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
