<template>
  <div class="surveyor-dashboard p-4! lg:p-8!">
    <!-- Header Section -->
    <div class="flex! flex-col! lg:flex-row! lg:items-center! justify-between! mb-10! gap-6!">
      <div>
        <h1 class="text-3xl! font-extrabold! text-slate-800! tracking-tight!">
          Surveyor <span class="text-orange-500!">Command Center</span>
        </h1>
        <p class="text-slate-500! mt-1! font-medium!">
          Kelola verifikasi lapangan dan pemetaan pelanggan baru dengan efisien.
        </p>
      </div>
      <div class="flex! items-center! gap-4!">
        <div
          class="bg-white! px-5! py-3! rounded-2xl! shadow-sm! border! border-slate-100! flex! items-center! gap-4!"
        >
          <div
            class="w-10! h-10! rounded-xl! bg-orange-50! text-orange-600! flex! items-center! justify-center!"
          >
            <font-awesome-icon icon="calendar-check" />
          </div>
          <div>
            <div class="text-[10px]! font-black! text-slate-400! uppercase! tracking-widest!">
              Tugas Hari Ini
            </div>
            <div class="text-sm! font-bold! text-slate-700!">8 Lokasi Survey</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid! grid-cols-1! md:grid-cols-2! lg:grid-cols-4! gap-6! mb-10!">
      <div
        v-for="(stat, idx) in stats"
        :key="idx"
        class="bg-white! p-6! rounded-3xl! shadow-sm! border! border-slate-100! transition-all! duration-300! hover:shadow-xl! hover:shadow-slate-200/50! group!"
      >
        <div class="flex! items-center! justify-between! mb-4!">
          <div
            :class="`w-12! h-12! rounded-2xl! ${stat.bg}! ${stat.color}! flex! items-center! justify-center! text-lg! transition-transform! group-hover:scale-110!`"
          >
            <font-awesome-icon :icon="stat.icon" />
          </div>
          <div class="flex! flex-col! items-end!">
            <span class="text-[10px]! font-black! text-slate-400! uppercase! tracking-widest!"
              >Target</span
            >
            <span class="text-xs! font-bold! text-slate-600!">{{ stat.target }}</span>
          </div>
        </div>
        <h3 class="text-2xl! font-black! text-slate-800! mb-1!">{{ stat.value }}</h3>
        <p class="text-xs! font-bold! text-slate-500!">{{ stat.label }}</p>
      </div>
    </div>

    <div class="grid! grid-cols-1! lg:grid-cols-3! gap-8!">
      <!-- Map Placeholder Section -->
      <div class="lg:col-span-2!">
        <ContentCard
          variant="elevated"
          padding="none"
          class="h-full! overflow-hidden! border-0! shadow-xl! shadow-slate-200/40!"
        >
          <div class="p-6! border-b! border-slate-50! flex! items-center! justify-between!">
            <div class="flex! items-center! gap-3!">
              <div class="w-1.5! h-6! bg-orange-500! rounded-full!"></div>
              <h2 class="text-lg! font-bold! text-slate-800!">Pemetaan Lokasi Survey</h2>
            </div>
            <BaseButton variant="ghost" icon="expand" size="sm" />
          </div>
          <div class="relative! h-[450px]! bg-slate-50! overflow-hidden!">
            <MapContainer v-model="surveyLocation" height="450px" :zoom="15" />
          </div>
        </ContentCard>
      </div>

      <!-- Recent Tasks Section -->
      <div class="lg:col-span-1!">
        <ContentCard
          variant="elevated"
          padding="none"
          class="h-full! border-0! shadow-xl! shadow-slate-200/40!"
        >
          <div class="p-6! border-b! border-slate-50!">
            <h2 class="text-lg! font-bold! text-slate-800!">Tugas Terbaru</h2>
          </div>
          <div class="p-2! space-y-2!">
            <div
              v-for="(task, idx) in tasks"
              :key="idx"
              class="p-4! rounded-2xl! hover:bg-slate-50! transition-all! cursor-pointer! group!"
            >
              <div class="flex! items-start! gap-4!">
                <div
                  class="w-10! h-10! rounded-xl! bg-slate-100! flex! items-center! justify-center! group-hover:bg-white! group-hover:shadow-md! transition-all!"
                >
                  <font-awesome-icon
                    icon="user-clock"
                    class="text-slate-400! group-hover:text-orange-500!"
                  />
                </div>
                <div class="flex-1!">
                  <div class="flex! items-center! justify-between! mb-1!">
                    <span class="text-sm! font-bold! text-slate-800!">#{{ task.id }}</span>
                    <BaseButton
                      variant="ghost"
                      size="sm"
                      class="text-[9px]! font-black! text-orange-600! bg-orange-50! px-2! py-0.5! rounded-md!"
                      @click="$router.push('/survey/input')"
                      >SURVEY SEKARANG</BaseButton
                    >
                  </div>
                  <div class="text-xs! font-bold! text-slate-700! mb-1!">
                    Survey Lokasi: {{ task.name }}
                  </div>
                  <div
                    class="text-[10px]! font-medium! text-slate-400! flex! items-center! gap-1.5!"
                  >
                    <font-awesome-icon icon="map-pin" />
                    {{ task.area }}
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="p-6! mt-2!">
            <BaseButton variant="secondary" block class="rounded-2xl! font-bold!"
              >Lihat Semua Tugas</BaseButton
            >
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
import MapContainer from '@/presentations/components/ui/MapContainer.vue'

const surveyLocation = ref({ lat: -6.1754, lng: 106.8272 })

const stats = ref([
  {
    label: 'Total Survey',
    value: '1,284',
    icon: 'clipboard-list',
    bg: 'bg-blue-50',
    color: 'text-blue-600',
    target: '1.5k',
  },
  {
    label: 'Pending Verif',
    value: '42',
    icon: 'clock',
    bg: 'bg-orange-50',
    color: 'text-orange-600',
    target: '0',
  },
  {
    label: 'Telah Aktif',
    value: '98%',
    icon: 'check-double',
    bg: 'bg-emerald-50',
    color: 'text-emerald-600',
    target: '100%',
  },
  {
    label: 'Wilayah Kerja',
    value: '12',
    icon: 'map-marked-alt',
    bg: 'bg-indigo-50',
    color: 'text-indigo-600',
    target: '15',
  },
])

const tasks = ref([
  { id: 'SRV-0921', name: 'Bpk. Ahmad Subarjo', area: 'Dusun Wetan, RT 04/02', status: 'Pending' },
  { id: 'SRV-0922', name: 'Ibu Ratna Sari', area: 'Perum Asri, Blok C/12', status: 'Pending' },
  { id: 'SRV-0923', name: 'Klinik Sehat', area: 'Jl. Utama No. 88', status: 'Pending' },
  { id: 'SRV-0924', name: 'Masjid Al-Ikhlas', area: 'Dusun Kulon, RT 01/01', status: 'Pending' },
])
</script>

<style scoped>
.surveyor-dashboard {
  animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

.pulse-status {
  position: relative;
}

.pulse-status::after {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 9999px;
  background: inherit;
  animation: pulse-ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;
}

@keyframes pulse-ping {
  75%,
  100% {
    transform: scale(2);
    opacity: 0;
  }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
