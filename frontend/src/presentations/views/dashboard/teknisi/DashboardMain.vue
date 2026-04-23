<template>
  <div class="teknisi-dashboard p-4! lg:p-8!">
    <!-- Header Section -->
    <div class="flex! flex-col! lg:flex-row! lg:items-center! justify-between! mb-10! gap-6!">
      <div>
        <h1 class="text-3xl! font-extrabold! text-slate-800! tracking-tight!">
          Technical <span class="text-emerald-500!">Operations</span>
        </h1>
        <p class="text-slate-500! mt-1! font-medium!">
          Monitor pemeliharaan jaringan dan respon cepat gangguan teknis.
        </p>
      </div>
      <div class="flex! items-center! gap-4!">
        <div
          class="bg-white! px-5! py-3! rounded-2xl! shadow-sm! border! border-slate-100! flex! items-center! gap-4!"
        >
          <div
            class="w-10! h-10! rounded-xl! bg-emerald-50! text-emerald-600! flex! items-center! justify-center!"
          >
            <font-awesome-icon icon="tools" />
          </div>
          <div>
            <div class="text-[10px]! font-black! text-slate-400! uppercase! tracking-widest!">
              Status Alat
            </div>
            <div class="text-sm! font-bold! text-slate-700!">Semua Optimal</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Priority Tickets -->
    <div class="grid! grid-cols-1! lg:grid-cols-3! gap-8! mb-10!">
      <div
        v-for="(ticket, idx) in priorityTickets"
        :key="idx"
        class="bg-white! p-6! rounded-3xl! shadow-sm! border! border-slate-100! relative! overflow-hidden! transition-all! hover:shadow-xl! hover:-translate-y-1!"
      >
        <div
          class="absolute! top-0! right-0! w-24! h-24! -mr-8! -mt-8! rounded-full! opacity-5!"
          :class="ticket.bg"
        ></div>
        <div class="flex! items-center! gap-3! mb-4!">
          <div
            :class="`w-8! h-8! rounded-lg! ${ticket.bg}! ${ticket.color}! flex! items-center! justify-center! text-xs!`"
          >
            <font-awesome-icon :icon="ticket.icon" />
          </div>
          <span
            class="text-[10px]! font-black! uppercase! tracking-widest!"
            :class="ticket.color"
            >{{ ticket.category }}</span
          >
        </div>
        <h3 class="text-lg! font-bold! text-slate-800! mb-2!">{{ ticket.title }}</h3>
        <p class="text-xs! text-slate-500! mb-4! line-clamp-2!">{{ ticket.desc }}</p>
        <div class="flex! items-center! justify-between! pt-4! border-t! border-slate-50!">
          <div class="flex! items-center! gap-2!">
            <div class="w-6! h-6! rounded-full! bg-slate-200! pulse-status!"></div>
            <span class="text-[10px]! font-bold! text-slate-600!"
              >Pelapor: {{ ticket.reporter }}</span
            >
          </div>
          <BaseButton
            variant="ghost"
            size="sm"
            class="text-[10px]! font-black!"
            @click="$router.push('/teknisi/pencatatan-meter')"
            >CATAT METER</BaseButton
          >
        </div>
      </div>
    </div>

    <div class="grid! grid-cols-1! lg:grid-cols-2! gap-8!">
      <!-- Maintenance Schedule -->
      <ContentCard
        variant="elevated"
        padding="none"
        class="border-0! shadow-xl! shadow-slate-200/40!"
      >
        <div class="p-6! border-b! border-slate-50! flex! items-center! justify-between!">
          <div class="flex! items-center! gap-3!">
            <div class="w-1.5! h-6! bg-emerald-500! rounded-full!"></div>
            <h2 class="text-lg! font-bold! text-slate-800!">Jadwal Pemeliharaan</h2>
          </div>
          <span class="text-[10px]! font-black! text-slate-400! uppercase! tracking-widest!"
            >Minggu Ini</span
          >
        </div>
        <div class="p-6! space-y-6!">
          <div v-for="(item, idx) in schedule" :key="idx" class="flex! gap-6! items-start!">
            <div class="flex! flex-col! items-center! min-w-[40px]!">
              <span class="text-sm! font-black! text-slate-800!">0{{ idx + 5 }}</span>
              <span class="text-[10px]! font-bold! text-slate-400! uppercase!">Mei</span>
            </div>
            <div class="flex-1! bg-slate-50! p-4! rounded-2xl! relative! overflow-hidden!">
              <div class="absolute! left-0! top-0! bottom-0! w-1!" :class="item.accent"></div>
              <h4 class="text-sm! font-bold! text-slate-800! mb-1!">{{ item.task }}</h4>
              <p class="text-xs! text-slate-500!">Lokasi: {{ item.location }}</p>
            </div>
          </div>
        </div>
      </ContentCard>

      <!-- Technical Overview -->
      <ContentCard
        variant="elevated"
        padding="none"
        class="border-0! shadow-xl! shadow-slate-200/40!"
      >
        <div class="p-6! border-b! border-slate-50!">
          <h2 class="text-lg! font-bold! text-slate-800!">Ringkasan Operasional</h2>
        </div>
        <div class="p-8!">
          <div class="grid! grid-cols-2! gap-8!">
            <div v-for="(stat, idx) in techStats" :key="idx" class="text-center!">
              <div
                :class="`w-14! h-14! rounded-2xl! ${stat.bg}! ${stat.color}! flex! items-center! justify-center! mx-auto! mb-4! text-xl!`"
              >
                <font-awesome-icon :icon="stat.icon" />
              </div>
              <div class="text-2xl! font-black! text-slate-800!">{{ stat.value }}</div>
              <div
                class="text-[10px]! font-black! text-slate-400! uppercase! tracking-widest! mt-1!"
              >
                {{ stat.label }}
              </div>
            </div>
          </div>

          <div
            class="mt-10! p-5! bg-indigo-600! rounded-2xl! text-white! relative! overflow-hidden!"
          >
            <div class="relative! z-10!">
              <h4 class="text-sm! font-bold! mb-1!">Siap Bertugas?</h4>
              <p class="text-[10px]! opacity-80! mb-4!">
                Pastikan semua peralatan lengkap sebelum ke lapangan.
              </p>
              <BaseButton variant="glass" size="sm" class="font-bold! text-[10px]!"
                >ABSENSI LAPANGAN</BaseButton
              >
            </div>
            <font-awesome-icon
              icon="hard-hat"
              class="absolute! -right-4! -bottom-4! text-6xl! opacity-10! -rotate-12!"
            />
          </div>
        </div>
      </ContentCard>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'

const priorityTickets = ref([
  {
    category: 'URGENT',
    title: 'Kebocoran Pipa Utama',
    desc: 'Laporan kebocoran besar di pipa distribusi utama Dusun Krajan.',
    reporter: 'Warga RT 02',
    icon: 'exclamation-triangle',
    color: 'text-red-500',
    bg: 'bg-red-50',
  },
  {
    category: 'MAINTENANCE',
    title: 'Pengecekan Pompa 02',
    desc: 'Jadwal rutin pembersihan filter dan pengecekan oli mesin pompa.',
    reporter: 'System',
    icon: 'wrench',
    color: 'text-blue-500',
    bg: 'bg-blue-50',
  },
  {
    category: 'INSTALL',
    title: 'Pemasangan Meter Baru',
    desc: '5 titik pemasangan meteran baru di perumahan Griya Indah.',
    reporter: 'Admin',
    icon: 'plus-circle',
    color: 'text-emerald-500',
    bg: 'bg-emerald-50',
  },
])

const schedule = ref([
  { task: 'Kalibrasi Sensor Debit', location: 'Reservoir Pusat', accent: 'bg-emerald-500' },
  { task: 'Penggantian Pipa 2 Inch', location: 'Blok Mawar No. 4', accent: 'bg-blue-500' },
  { task: 'Cek Kualitas Air', location: 'Semua Titik Distribusi', accent: 'bg-indigo-500' },
])

const techStats = ref([
  {
    label: 'Tiket Selesai',
    value: '152',
    icon: 'check-circle',
    color: 'text-emerald-600',
    bg: 'bg-emerald-50',
  },
  {
    label: 'Rerata Respon',
    value: '18m',
    icon: 'bolt',
    color: 'text-amber-600',
    bg: 'bg-amber-50',
  },
  {
    label: 'Pipa Diperbaiki',
    value: '420m',
    icon: 'project-diagram',
    color: 'text-indigo-600',
    bg: 'bg-indigo-50',
  },
  {
    label: 'Material Tersedia',
    value: '85%',
    icon: 'boxes',
    color: 'text-slate-600',
    bg: 'bg-slate-50',
  },
])
</script>

<style scoped>
.teknisi-dashboard {
  animation: fadeIn 0.8s ease-out;
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

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
</style>
