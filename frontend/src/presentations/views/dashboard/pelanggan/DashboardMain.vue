<template>
  <div class="pelanggan-dashboard">
    <div class="max-w-7xl! mx-auto!">
      <div
        class="mb-10! lg:mb-14! flex! flex-col! lg:flex-row! lg:items-center! justify-between! gap-8!"
      >
        <div class="flex! flex-col! items-center! lg:items-start! w-full! lg:w-auto!">
          <div class="flex! justify-center! lg:justify-start! mb-4!">
            <div
              class="inline-flex! items-center! gap-2! bg-indigo-50! px-4! py-1.5! rounded-full! border! border-indigo-100!"
            >
              <div class="w-1.5! h-1.5! bg-indigo-600! rounded-full! animate-pulse!"></div>
              <span
                class="text-[9px]! lg:text-[10px]! font-black! text-indigo-600! uppercase! tracking-[0.2em]!"
                >Ringkasan Layanan</span
              >
            </div>
          </div>
          <div class="text-center! lg:text-left!">
            <h1
              class="text-xl! sm:text-2xl! lg:text-5xl! font-black! text-slate-800! tracking-tighter! mb-2! whitespace-nowrap!"
            >
              Selamat Datang,
              <span
                class="bg-gradient-to-r! from-indigo-600! to-blue-500! bg-clip-text! text-transparent!"
                >Bambang</span
              >
              👋
            </h1>
            <p class="text-slate-500! font-medium! text-sm! lg:text-lg! max-w-md! lg:mx-0!">
              Pantau tagihan & pemakaian air Anda.
            </p>
          </div>
        </div>

        <div class="flex! items-center! justify-end! lg:justify-center! w-full! lg:w-auto!">
          <div
            class="bg-white! px-5! py-3! rounded-2xl! lg:rounded-3xl! shadow-xl! shadow-indigo-100! flex! items-center! gap-4! border! border-indigo-50!"
          >
            <div
              class="w-8! h-8! bg-indigo-50! rounded-full! flex! items-center! justify-center! text-indigo-600! flex-shrink-0!"
            >
              <font-awesome-icon icon="wallet" class="text-xs! !m-auto!" />
            </div>
            <div class="flex! flex-col! items-start! whitespace-nowrap!">
              <span
                class="text-[9px]! font-black! text-slate-400! uppercase! tracking-widest! mb-0.5!"
                >Saldo</span
              >
              <span class="text-sm! lg:text-base! font-black! text-slate-800!">Rp. 124.500</span>
            </div>
          </div>
        </div>
      </div>

      <div class="grid! grid-cols-1! lg:grid-cols-12! gap-10! mb-12!">
        <div class="lg:col-span-4!">
          <ContentCard
            variant="elevated"
            padding="none"
            class="h-full! border-0! shadow-[0_25px_50px_-12px_rgba(0,0,0,0.15)]! rounded-3xl! overflow-hidden! bg-white! relative!"
          >
            <div
              class="absolute! top-0! left-0! right-0! h-2! bg-gradient-to-r! from-indigo-500! to-blue-400!"
            ></div>

            <div class="p-5! lg:p-8!">
              <div class="flex! items-center! justify-between! mb-6! lg:mb-8!">
                <div
                  class="w-14! h-14! rounded-full! bg-indigo-50! flex! items-center! justify-center! text-indigo-600! shadow-inner!"
                >
                  <font-awesome-icon icon="receipt" size="lg" />
                </div>
                <div class="flex! items-center!">
                  <span
                    class="px-4! py-1.5! bg-red-50! text-red-600! text-[10px]! font-black! rounded-full! border! border-red-100! tracking-widest!"
                    >BELUM LUNAS</span
                  >
                </div>
              </div>

              <div class="mb-8!">
                <h3
                  class="text-slate-400! text-[10px]! font-black! uppercase! tracking-widest! mb-2!"
                >
                  Total Tagihan
                </h3>
                <div class="flex! items-baseline! justify-end! gap-1!">
                  <span class="text-base! lg:text-lg! font-black! text-slate-400!">Rp.</span>
                  <span class="text-3xl! lg:text-4xl! font-black! text-slate-800! tracking-tighter!"
                    >85.000</span
                  >
                </div>
                <div class="mt-4! flex! items-center! gap-2! text-xs! font-bold! text-red-500!">
                  <font-awesome-icon icon="clock" size="xs" />
                  <span>Jatuh tempo: 20 Mei 2025</span>
                </div>
              </div>

              <div
                class="space-y-4! mb-10! bg-slate-50! p-5! rounded-3xl! border! border-slate-100!"
              >
                <div class="flex! justify-between! items-center!">
                  <span class="text-sm! font-bold! text-slate-500!">Pemakaian Air</span>
                  <span class="text-sm! font-black! text-slate-800!">25 m³</span>
                </div>
                <div class="w-full! h-px! bg-slate-200!"></div>
                <div class="flex! justify-between! items-center!">
                  <span class="text-sm! font-bold! text-slate-500!">Biaya Beban</span>
                  <span class="text-sm! font-black! text-slate-800!">Rp. 10.000</span>
                </div>
              </div>

              <BaseButton
                variant="primary-gradient"
                block
                class="rounded-full! font-black! h-12! text-sm! shadow-xl! shadow-indigo-200! hover:-translate-y-1! transition-all!"
                @click="$router.push('/pelanggan/tagihan-detail')"
              >
                CEK DETAIL
                <font-awesome-icon icon="chevron-right" class="ml-2! text-[10px]!" />
              </BaseButton>
            </div>
          </ContentCard>
        </div>

        <div class="lg:col-span-8!">
          <ContentCard
            variant="elevated"
            padding="none"
            class="h-full! border-0! shadow-[0_25px_50px_-12px_rgba(0,0,0,0.1)]! rounded-3xl! bg-white!"
          >
            <div class="p-5! lg:p-8! flex! items-center! justify-between! mb-2!">
              <div>
                <h2 class="text-lg! lg:text-xl! font-black! text-slate-800! tracking-tight!">
                  Distribusi Penggunaan
                </h2>
                <p class="text-slate-400! text-[10px]! lg:text-xs! font-medium! mt-1!">
                  Perbandingan volume air 5 bulan terakhir
                </p>
              </div>
              <div class="flex! bg-slate-50! p-1.5! rounded-2xl! border! border-slate-100!">
                <button
                  class="text-[10px]! font-black! px-4! py-2! rounded-xl! bg-white! shadow-md! text-indigo-600!"
                >
                  Pie View
                </button>
              </div>
            </div>

            <div
              class="px-5! lg:px-8! pb-8! lg:pb-10! flex! flex-col! lg:flex-row! items-center! justify-center! gap-8! lg:gap-10! min-h-[280px]! lg:min-h-[300px]!"
            >
              <div class="relative! w-48! lg:w-52! h-48! lg:h-52! flex-shrink-0!">
                <svg viewBox="0 0 100 100" class="w-full! h-full! -rotate-90!">
                  <circle
                    cx="50"
                    cy="50"
                    r="40"
                    fill="transparent"
                    stroke="#f1f5f9"
                    stroke-width="12"
                  />

                  <!-- Mei (25m3) - ~23% -->
                  <circle
                    cx="50"
                    cy="50"
                    r="40"
                    fill="transparent"
                    stroke="url(#indigoGradient)"
                    stroke-width="12"
                    stroke-dasharray="23.1 100"
                    stroke-dashoffset="0"
                    class="transition-all! duration-1000! hover:stroke-width-[14!]"
                  />

                  <!-- Apr (28m3) - ~26% -->
                  <circle
                    cx="50"
                    cy="50"
                    r="40"
                    fill="transparent"
                    stroke="#6366f1"
                    stroke-width="12"
                    stroke-dasharray="25.9 100"
                    stroke-dashoffset="-23.1"
                    class="opacity-80!"
                  />

                  <!-- Mar (15m3) - ~14% -->
                  <circle
                    cx="50"
                    cy="50"
                    r="40"
                    fill="transparent"
                    stroke="#818cf8"
                    stroke-width="12"
                    stroke-dasharray="13.8 100"
                    stroke-dashoffset="-49.0"
                    class="opacity-60!"
                  />

                  <!-- Feb (22m3) - ~20% -->
                  <circle
                    cx="50"
                    cy="50"
                    r="40"
                    fill="transparent"
                    stroke="#a5b4fc"
                    stroke-width="12"
                    stroke-dasharray="20.4 100"
                    stroke-dashoffset="-62.8"
                    class="opacity-40!"
                  />

                  <!-- Jan (18m3) - ~17% -->
                  <circle
                    cx="50"
                    cy="50"
                    r="40"
                    fill="transparent"
                    stroke="#c7d2fe"
                    stroke-width="12"
                    stroke-dasharray="16.7 100"
                    stroke-dashoffset="-83.2"
                    class="opacity-20!"
                  />

                  <defs>
                    <linearGradient id="indigoGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                      <stop offset="0%" style="stop-color: #4f46e5; stop-opacity: 1" />
                      <stop offset="100%" style="stop-color: #06b6d4; stop-opacity: 1" />
                    </linearGradient>
                  </defs>
                </svg>
                <div class="absolute! inset-0! flex! flex-col! items-center! justify-center!">
                  <span class="text-2xl! font-black! text-slate-800!">108</span>
                  <span class="text-[9px]! font-black! text-slate-400! uppercase! tracking-widest!"
                    >Total m³</span
                  >
                </div>
              </div>

              <div class="grid! grid-cols-1! gap-4! w-full! max-w-[240px]!">
                <div
                  v-for="(val, idx) in usageData.slice().reverse()"
                  :key="idx"
                  class="flex! items-center! justify-between! p-3! rounded-2xl! hover:bg-slate-50! transition-colors! group!"
                >
                  <div class="flex! items-center! gap-3!">
                    <div
                      class="w-3! h-3! rounded-full!"
                      :class="idx === 0 ? 'bg-indigo-600!' : 'bg-indigo-200!'"
                    ></div>
                    <span class="text-sm! font-bold! text-slate-600!">{{
                      months.slice().reverse()[idx]
                    }}</span>
                  </div>
                  <div class="flex! items-center! gap-2!">
                    <span class="text-sm! font-black! text-slate-800!">{{ val }} m³</span>
                    <span class="text-[10px]! font-bold! text-slate-400! w-8! text-right!">
                      {{ Math.round((val / 108) * 100) }}%
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </ContentCard>
        </div>
      </div>

      <div class="grid! grid-cols-1! md:grid-cols-3! gap-8!">
        <ContentCard
          v-for="(action, idx) in actions"
          :key="idx"
          variant="elevated"
          padding="none"
          clickable
          class="border-0! shadow-[0_15px_30px_-10px_rgba(0,0,0,0.12)]! group! rounded-2xl! lg:rounded-3xl! overflow-hidden! bg-white! hover:-translate-y-2! hover:shadow-[0_25px_50px_-12px_rgba(0,0,0,0.2)]! transition-all!"
          @click="action.path ? $router.push(action.path) : null"
        >
          <div class="p-4! lg:p-5! flex! items-center! gap-4!">
            <div
              :class="`w-12! h-12! rounded-full! ${action.bg}! ${action.color}! flex! items-center! justify-center! text-lg! flex-shrink-0! shadow-inner! group-hover:scale-110! transition-transform!`"
            >
              <font-awesome-icon :icon="action.icon" />
            </div>
            <div>
              <h4 class="text-base! font-black! text-slate-800! leading-tight!">
                {{ action.title }}
              </h4>
              <p class="text-[10px]! text-slate-400! font-bold! mt-1! uppercase! tracking-wider!">
                {{ action.desc }}
              </p>
            </div>
            <div class="ml-auto! text-slate-300! group-hover:text-indigo-500! transition-colors!">
              <font-awesome-icon icon="chevron-right" />
            </div>
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

const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei']
const usageData = [18, 22, 15, 28, 25]

const actions = ref([
  {
    title: 'Lapor Gangguan',
    desc: 'Air mati atau pipa bocor?',
    icon: 'headset',
    color: 'text-red-600',
    bg: 'bg-red-50',
    path: '/pelanggan/lapor-gangguan?from=dashboard',
  },
  {
    title: 'Riwayat Tagihan',
    desc: 'Lihat pembayaran terdahulu',
    icon: 'history',
    color: 'text-indigo-600',
    bg: 'bg-indigo-50',
    path: '/pelanggan/riwayat-tagihan?from=dashboard',
  },
  {
    title: 'Info Pamsimas',
    desc: 'Berita & pengumuman terbaru',
    icon: 'bullhorn',
    color: 'text-amber-600',
    bg: 'bg-amber-50',
  },
])
</script>

<style scoped>
.pelanggan-dashboard {
  animation: fadeIn 1s cubic-bezier(0.16, 1, 0.3, 1);
}

.ease-out-expo {
  transition-timing-function: cubic-bezier(0.19, 1, 0.22, 1);
}

@keyframes fadeIn {
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
