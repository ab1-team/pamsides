<template>
  <div class="pelanggan-dashboard p-4! lg:p-8!">
    <!-- Header Section -->
    <div class="flex! flex-col! lg:flex-row! lg:items-center! justify-between! mb-10! gap-6!">
      <div>
        <h1 class="text-3xl! font-extrabold! text-slate-800! tracking-tight!">
          Halo, <span class="text-indigo-600!">Bambang Susanto</span>
        </h1>
        <p class="text-slate-500! mt-1! font-medium!">
          Selamat datang kembali. Berikut ringkasan penggunaan air Anda bulan ini.
        </p>
      </div>
      <div class="flex! items-center! gap-4!">
        <div
          class="bg-indigo-600! text-white! px-6! py-4! rounded-3xl! shadow-lg! shadow-indigo-200! flex! items-center! gap-4! relative! overflow-hidden!"
        >
          <div class="relative! z-10!">
            <div class="text-[10px]! font-black! opacity-80! uppercase! tracking-widest!">
              Saldo Tagihan
            </div>
            <div class="text-xl! font-black!">Rp. 124.500</div>
          </div>
          <font-awesome-icon
            icon="wallet"
            class="text-3xl! opacity-20! absolute! -right-2! -bottom-2!"
          />
        </div>
      </div>
    </div>

    <!-- Main Grid -->
    <div class="grid! grid-cols-1! lg:grid-cols-3! gap-8! mb-10!">
      <!-- Bill Status Card -->
      <div class="lg:col-span-1!">
        <ContentCard
          variant="elevated"
          padding="none"
          class="h-full! border-0! shadow-xl! shadow-slate-200/40! bg-gradient-to-br! from-slate-900! to-slate-800! text-white!"
        >
          <div class="p-8!">
            <div class="flex! items-center! justify-between! mb-8!">
              <div
                class="w-12! h-12! rounded-2xl! bg-white/10! flex! items-center! justify-center! text-indigo-400!"
              >
                <font-awesome-icon icon="receipt" size="lg" />
              </div>
              <span
                class="px-3! py-1! bg-red-500/20! text-red-400! text-[10px]! font-black! rounded-full! border! border-red-500/30!"
                >BELUM BAYAR</span
              >
            </div>

            <div class="mb-8!">
              <span class="text-[10px]! font-black! opacity-50! uppercase! tracking-widest!"
                >TAGIHAN MEI 2025</span
              >
              <h2 class="text-4xl! font-black! mt-1!">Rp. 85.000</h2>
              <p class="text-xs! opacity-60! mt-2!">Jatuh tempo pada 20 Mei 2025</p>
            </div>

            <div class="space-y-4! mb-10!">
              <div class="flex! justify-between! items-center! text-sm!">
                <span class="opacity-60!">Pemakaian</span>
                <span class="font-bold!">25 m³</span>
              </div>
              <div class="flex! justify-between! items-center! text-sm!">
                <span class="opacity-60!">Biaya Beban</span>
                <span class="font-bold!">Rp. 10.000</span>
              </div>
              <div class="flex! justify-between! items-center! text-sm!">
                <span class="opacity-60!">Denda</span>
                <span class="font-bold!">Rp. 0</span>
              </div>
            </div>

            <BaseButton
              variant="primary-gradient"
              block
              size="lg"
              class="rounded-2xl! font-black! h-14!"
              @click="$router.push('/pelanggan/tagihan-detail')"
              >LIHAT DETAIL</BaseButton
            >
          </div>
        </ContentCard>
      </div>

      <!-- Usage Chart Section -->
      <div class="lg:col-span-2!">
        <ContentCard
          variant="elevated"
          padding="none"
          class="h-full! border-0! shadow-xl! shadow-slate-200/40!"
        >
          <div class="p-6! border-b! border-slate-50! flex! items-center! justify-between!">
            <div class="flex! items-center! gap-3!">
              <div class="w-1.5! h-6! bg-indigo-600! rounded-full!"></div>
              <h2 class="text-lg! font-bold! text-slate-800!">Statistik Penggunaan (m³)</h2>
            </div>
            <div class="flex! gap-2!">
              <button
                v-for="t in ['6 Bln', '1 Thn']"
                :key="t"
                class="text-[10px]! font-black! px-3! py-1! rounded-lg! border! border-slate-100! hover:bg-slate-50! transition-all!"
              >
                {{ t }}
              </button>
            </div>
          </div>
          <div class="p-8! h-[320px]! flex! items-end! justify-between! gap-4!">
            <div
              v-for="(val, idx) in usageData"
              :key="idx"
              class="flex-1! flex! flex-col! items-center! gap-3!"
            >
              <div
                class="w-full! bg-indigo-50! rounded-t-xl! relative! group! transition-all! duration-500!"
                :style="`height: ${val * 10}px`"
                :class="idx === 4 ? 'bg-indigo-600!' : ''"
              >
                <div
                  class="absolute! -top-8! left-1/2! -translate-x-1/2! bg-slate-800! text-white! text-[9px]! font-bold! px-2! py-1! rounded! opacity-0! group-hover:opacity-100! transition-opacity!"
                >
                  {{ val }}m³
                </div>
              </div>
              <span class="text-[10px]! font-black! text-slate-400! uppercase!">{{
                months[idx]
              }}</span>
            </div>
          </div>
        </ContentCard>
      </div>
    </div>

    <!-- Bottom Actions -->
    <div class="grid! grid-cols-1! md:grid-cols-2! lg:grid-cols-3! gap-6!">
      <div
        v-for="(action, idx) in actions"
        :key="idx"
        @click="action.path ? $router.push(action.path) : null"
        class="bg-white! p-6! rounded-3xl! border! border-slate-100! flex! items-center! gap-5! cursor-pointer! transition-all! hover:shadow-lg! group!"
      >
        <div
          :class="`w-14! h-14! rounded-2xl! ${action.bg}! ${action.color}! flex! items-center! justify-center! text-xl! group-hover:scale-110! transition-transform!`"
        >
          <font-awesome-icon :icon="action.icon" />
        </div>
        <div>
          <h4 class="font-bold! text-slate-800!">{{ action.title }}</h4>
          <p class="text-xs! text-slate-500! mt-0.5!">{{ action.desc }}</p>
        </div>
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
  },
  {
    title: 'Riwayat Tagihan',
    desc: 'Lihat pembayaran terdahulu',
    icon: 'history',
    color: 'text-indigo-600',
    bg: 'bg-indigo-50',
    path: '/pelanggan/tagihan-detail',
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
  animation: slideIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}
</style>
