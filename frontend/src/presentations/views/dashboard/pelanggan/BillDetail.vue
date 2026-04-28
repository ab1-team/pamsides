<template>
  <div class="bill-detail-view bg-slate-50/50! min-h-screen!">
    <div class="max-w-6xl! mx-auto! p-4! lg:p-10!">
      <div class="mb-8! flex! items-center! justify-between! gap-4!">
        <BaseButton
          variant="ghost"
          icon="arrow-left"
          class="w-10! h-10! lg:w-12! lg:h-12! rounded-full! bg-white! shadow-sm! border! border-slate-100! flex! items-center! justify-center! text-slate-600! hover:bg-slate-50!"
          @click="$router.back()"
        />
        <div class="flex! items-center! gap-2! lg:gap-3!">
          <BaseButton
            v-if="isMobile"
            variant="secondary"
            icon="print"
            class="w-10! h-10! rounded-full! flex! items-center! justify-center! p-0!"
            @click="printInvoice"
          />
          <BaseButton
            v-else
            variant="secondary"
            icon="print"
            class="lg:w-auto! lg:h-12! lg:rounded-xl! flex! items-center! justify-center! lg:px-6!"
            @click="printInvoice"
          >
            Cetak PDF
          </BaseButton>

          <BaseButton
            v-if="isMobile"
            variant="primary-gradient"
            icon="share-alt"
            class="w-10! h-10! rounded-full! flex! items-center! justify-center! p-0!"
          />
          <BaseButton
            v-else
            variant="primary-gradient"
            icon="share-alt"
            class="lg:w-auto! lg:h-12! lg:rounded-xl! flex! items-center! justify-center! lg:px-6!"
          >
            Bagikan
          </BaseButton>
        </div>
      </div>

      <div class="relative!">
        <div
          class="absolute! -top-20! left-1/2! -translate-x-1/2! w-64! h-64! bg-indigo-500/10! blur-[100px]! rounded-full! pointer-events-none!"
        ></div>

        <ContentCard
          id="printable-invoice"
          variant="elevated"
          padding="none"
          class="border-0! shadow-[0_25px_50px_-12px_rgba(0,0,0,0.15)]! overflow-hidden! rounded-[2.5rem]! bg-white! relative! z-10!"
        >
          <div
            class="p-6! lg:p-10! bg-gradient-to-br! from-slate-900! via-slate-800! to-slate-900! text-white! relative! overflow-hidden!"
          >
            <div
              class="absolute! top-0! right-0! w-64! h-64! bg-white/5! rounded-full! -mr-20! -mt-20! blur-3xl!"
            ></div>

            <div
              class="relative! z-10! flex! flex-col! lg:flex-row! justify-between! gap-6! lg:gap-8!"
            >
              <div
                class="flex! flex-col! items-center! lg:items-start! text-center! lg:text-left! flex-1!"
              >
                <div class="flex! items-center! justify-center! lg:justify-start! gap-3! mb-4!">
                  <div
                    class="w-10! h-10! lg:w-12! lg:h-12! bg-indigo-500! rounded-xl! lg:rounded-2xl! flex! items-center! justify-center! shadow-lg! shadow-indigo-500/20! flex-shrink-0!"
                  >
                    <font-awesome-icon icon="droplet" class="text-xl! lg:text-2xl! !m-auto!" />
                  </div>
                  <div class="text-left!">
                    <h2 class="text-lg! lg:text-xl! font-black! tracking-tighter!">
                      PAMSIDES <span class="text-indigo-400!">DIGITAL</span>
                    </h2>
                    <p class="text-[9px]! font-black! opacity-40! tracking-widest! uppercase!">
                      Official Invoice
                    </p>
                  </div>
                </div>

                <div class="mt-2! lg:mt-6!">
                  <span
                    class="px-3! py-1! bg-red-500/20! text-red-300! rounded-full! text-[9px]! font-black! border! border-red-500/30! tracking-widest!"
                    >UNPAID</span
                  >
                  <h1 class="text-3xl! lg:text-5xl! font-black! tracking-tighter! mt-3!">
                    Rp. 85.000
                  </h1>
                  <p class="text-[10px]! lg:text-sm! font-medium! opacity-60! mt-1!">
                    Tagihan Mei 2025
                  </p>
                </div>
              </div>

              <div
                class="flex! flex-col! items-center! lg:items-end! justify-between! gap-4! w-full! lg:w-auto!"
              >
                <div
                  class="flex! items-center! lg:items-start! justify-between! lg:justify-end! w-full! lg:w-auto! gap-6!"
                >
                  <div class="text-left! lg:text-right!">
                    <p
                      class="text-[9px]! lg:text-xs! font-bold! opacity-40! tracking-widest! uppercase!"
                    >
                      Invoice Number
                    </p>
                    <p class="text-xs! lg:text-sm! font-black!">#INV/2025/05/882109</p>
                  </div>
                  <div
                    class="w-12! h-12! lg:w-16! lg:h-16! bg-white! p-1! rounded-xl! shadow-lg! flex! items-center! justify-center! flex-shrink-0!"
                  >
                    <div
                      class="w-full! h-full! bg-slate-100! rounded-md! flex! items-center! justify-center! text-slate-300!"
                    >
                      <font-awesome-icon icon="qrcode" size="lg" class="!m-auto!" />
                    </div>
                  </div>
                </div>

                <div
                  class="bg-white/10! p-4! lg:p-6! rounded-2xl! lg:rounded-3xl! backdrop-blur-md! border! border-white/10! w-full! max-w-[280px]!"
                >
                  <div class="flex! items-center! gap-3! lg:gap-4! mb-2! lg:mb-4!">
                    <div
                      class="w-8! h-8! lg:w-10! lg:h-10! bg-white! rounded-lg! lg:rounded-xl! flex! items-center! justify-center! text-slate-800! flex-shrink-0!"
                    >
                      <font-awesome-icon icon="user" size="xs" class="!m-auto!" />
                    </div>
                    <div class="text-left!">
                      <p
                        class="text-[8px]! lg:text-[10px]! font-black! opacity-40! tracking-widest! uppercase!"
                      >
                        Customer
                      </p>
                      <p class="text-xs! lg:text-sm! font-black!">Bambang Susanto</p>
                    </div>
                  </div>
                  <p class="text-[9px]! lg:text-[11px]! text-left! opacity-60! leading-relaxed!">
                    #MA-882109 • Southern Spring, Blok B/12
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div class="p-6! lg:p-10!">
            <div class="grid! grid-cols-2! lg:grid-cols-3! gap-4! lg:gap-8! mb-8!">
              <div
                class="col-span-1! bg-slate-50! rounded-2xl! p-4! lg:p-8! border! border-slate-100! flex! flex-col! items-center! justify-center! text-center!"
              >
                <div
                  class="w-10! lg:w-16! h-10! lg:h-16! bg-white! shadow-lg! shadow-slate-200! rounded-full! flex! items-center! justify-center! text-indigo-600! mb-2! lg:mb-4! flex-shrink-0!"
                >
                  <font-awesome-icon icon="tint" class="text-lg! lg:text-2xl! !m-auto!" />
                </div>
                <h4
                  class="text-[8px]! lg:text-[10px]! font-black! text-slate-400! uppercase! tracking-widest! mb-1!"
                >
                  Total Pemakaian
                </h4>
                <div class="text-xl! lg:text-3xl! font-black! text-slate-800!">
                  25 <span class="text-[10px]! lg:text-sm! opacity-30!">m³</span>
                </div>
              </div>

              <div
                class="col-span-1! lg:col-span-2! flex! flex-col! justify-center! space-y-4! lg:space-y-6!"
              >
                <div
                  class="flex! items-center! justify-between! pb-2! lg:pb-4! border-b! border-slate-100!"
                >
                  <div class="flex! items-center! gap-2! lg:gap-4!">
                    <div
                      class="w-6! h-6! lg:w-8! lg:h-8! bg-slate-100! rounded-full! flex! items-center! justify-center! text-slate-400! text-[10px]!"
                    >
                      <font-awesome-icon icon="history" class="!m-auto!" />
                    </div>
                    <span class="text-[10px]! lg:text-sm! font-bold! text-slate-500!">Lalu</span>
                  </div>
                  <span class="text-sm! lg:text-lg! font-black! text-slate-700!"
                    >1.240 <span class="text-[9px]! opacity-30!">m³</span></span
                  >
                </div>
                <div
                  class="flex! items-center! justify-between! pb-2! lg:pb-4! border-b! border-slate-100!"
                >
                  <div class="flex! items-center! gap-2! lg:gap-4!">
                    <div
                      class="w-6! h-6! lg:w-8! lg:h-8! bg-indigo-50! text-indigo-500! rounded-full! flex! items-center! justify-center! text-[10px]!"
                    >
                      <font-awesome-icon icon="camera" class="!m-auto!" />
                    </div>
                    <span class="text-[10px]! lg:text-sm! font-bold! text-slate-500!">Kini</span>
                  </div>
                  <span class="text-sm! lg:text-lg! font-black! text-slate-900!"
                    >1.265 <span class="text-[9px]! opacity-30!">m³</span></span
                  >
                </div>
              </div>
            </div>

            <div class="mb-6!">
              <h4
                class="text-[10px]! font-black! text-slate-400! uppercase! tracking-widest! mb-3! flex! items-center! gap-3!"
              >
                <span class="w-8! h-[1px]! bg-slate-200!"></span>
                Rincian Biaya
                <span class="flex-1! h-[1px]! bg-slate-200!"></span>
              </h4>

              <div class="space-y-2!">
                <div
                  v-for="(item, idx) in breakdown"
                  :key="idx"
                  class="flex! items-center! justify-between! p-3! rounded-2xl! hover:bg-slate-50! transition-colors!"
                >
                  <div class="flex! items-center! gap-4!">
                    <div
                      class="w-10! h-10! bg-slate-50! group-hover:bg-white! rounded-xl! flex! items-center! justify-center! text-slate-400! flex-shrink-0!"
                    >
                      <font-awesome-icon :icon="item.icon" size="sm" class="!m-auto!" />
                    </div>
                    <div>
                      <p class="text-sm! font-bold! text-slate-700!">
                        {{ item.name }}
                        <span
                          v-if="item.sub"
                          class="block! text-[10px]! text-slate-400! font-medium!"
                          >{{ item.sub }}</span
                        >
                      </p>
                    </div>
                  </div>
                  <span class="text-sm! font-black! text-slate-800!"
                    >Rp. {{ item.price.toLocaleString() }}</span
                  >
                </div>
              </div>
            </div>

            <div
              class="bg-gradient-to-br! from-slate-900! via-indigo-950! to-slate-900! rounded-[2rem]! p-5! lg:p-8! text-white! flex! flex-row! justify-between! items-center! gap-4! lg:gap-6! relative! overflow-hidden! shadow-xl! shadow-indigo-900/10!"
            >
              <div
                class="absolute! top-0! left-0! w-full! h-full! bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]! opacity-5!"
              ></div>
              <div class="relative! z-10!">
                <p
                  class="text-[8px]! lg:text-[10px]! font-black! opacity-40! tracking-widest! uppercase! mb-1!"
                >
                  Total Bayar
                </p>
                <div class="text-2xl! lg:text-4xl! font-black! tracking-tighter!">Rp. 85k</div>
              </div>
              <div class="relative! z-10! flex-shrink-0!">
                <BaseButton
                  v-if="isMobile"
                  variant="primary-gradient"
                  icon="credit-card"
                  class="w-10! h-10! rounded-full! flex! items-center! justify-center! p-0! shadow-xl! shadow-indigo-500/30!"
                />
                <BaseButton
                  v-else
                  variant="primary-gradient"
                  icon="credit-card"
                  class="lg:w-auto! lg:h-14! px-10! rounded-full! font-black! shadow-xl! shadow-indigo-500/30! transition-all! hover:scale-105! flex! items-center! justify-center!"
                >
                  <span class="ml-2!">BAYAR SEKARANG</span>
                  <font-awesome-icon icon="chevron-right" class="ml-3! text-xs!" />
                </BaseButton>
              </div>
            </div>
          </div>
        </ContentCard>

        <div
          class="absolute! -bottom-10! -left-10! w-48! h-48! bg-indigo-100! rounded-full! blur-3xl! opacity-50! pointer-events-none!"
        ></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'

const isMobile = ref(false)
const checkMobile = () => {
  isMobile.value = window.innerWidth < 1024
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})

const breakdown = ref([
  { name: 'Biaya Air', sub: '25 m³ x Rp. 3.000', price: 75000, icon: 'tint' },
  { name: 'Biaya Beban', sub: 'Pemeliharaan Jaringan', price: 5000, icon: 'wrench' },
  { name: 'Biaya Admin', sub: 'Aplikasi Pamsides', price: 5000, icon: 'receipt' },
])

const printInvoice = () => {
  window.print()
}
</script>

<style scoped>
.bill-detail-view {
  animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
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

@media print {
  .bill-detail-view {
    background: white !important;
    padding: 0 !important;
  }
  .max-w-4xl {
    max-width: 100% !important;
  }
  button,
  .no-print {
    display: none !important;
  }
}
</style>
