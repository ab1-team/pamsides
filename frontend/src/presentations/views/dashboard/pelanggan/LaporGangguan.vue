<template>
  <div class="report-trouble">
    <div class="max-w-6xl! mx-auto!">
      <div class="flex! items-start! gap-6! mb-10!">
        <BaseButton
          v-if="$route.query.from === 'dashboard'"
          variant="ghost"
          icon="arrow-left"
          class="w-12! h-12! rounded-full! bg-white! shadow-sm! border! border-slate-100! flex! items-center! justify-center! text-slate-600! hover:bg-slate-50! flex-shrink-0! mt-1!"
          @click="$router.back()"
        />
        <div :class="{ 'text-center! w-full! pr-12!': $route.query.from !== 'dashboard' }">
          <h1 class="text-2xl! lg:text-3xl! font-black! text-slate-800! tracking-tight!">
            Lapor Gangguan
          </h1>
          <p class="text-slate-500! mt-1! font-medium! text-sm! lg:text-base!">
            Kami siap membantu kendala air Anda dengan cepat dan sigap.
          </p>
        </div>
      </div>

      <div class="grid! grid-cols-1! lg:grid-cols-2! gap-8! mb-12!">
        <ContentCard
          variant="elevated"
          padding="none"
          class="border-0! shadow-[0_20px_50px_-12px_rgba(0,0,0,0.12)]! group! transition-all! cursor-pointer! rounded-3xl! overflow-hidden! relative! hover:-translate-y-2! hover:shadow-[0_30px_60px_-15px_rgba(0,0,0,0.2)]!"
          @click="openWhatsApp"
        >
          <div class="absolute! top-0! right-0! p-6!">
            <div class="flex! items-center! gap-2! bg-green-500/10! px-3! py-1! rounded-full!">
              <div class="w-2! h-2! bg-green-500! rounded-full! animate-pulse!"></div>
              <span class="text-[10px]! font-black! text-green-600! uppercase! tracking-widest!"
                >Online</span
              >
            </div>
          </div>

          <div class="p-6! lg:p-10! flex! flex-col! h-full!">
            <div class="flex! items-center! gap-4! lg:gap-6! mb-8! lg:mb-10!">
              <div
                class="w-16! lg:w-20! h-16! lg:h-20! bg-gradient-to-br! from-green-400! to-emerald-600! text-white! rounded-2xl! lg:rounded-[2rem]! flex! items-center! justify-center! shadow-xl! shadow-green-200! group-hover:rotate-6! transition-transform!"
              >
                <font-awesome-icon :icon="['fab', 'whatsapp']" class="text-2xl! lg:text-4xl!" />
              </div>
              <div>
                <h3 class="text-lg! lg:text-xl! font-black! text-slate-800! tracking-tight!">
                  WhatsApp Center
                </h3>
                <p
                  class="text-[9px]! lg:text-[10px]! text-slate-400! font-black! uppercase! tracking-widest! mt-1!"
                >
                  Respon Cepat & Sigap
                </p>
              </div>
            </div>

            <p class="text-slate-500! leading-relaxed! mb-10! font-medium!">
              Layanan pengaduan melalui pesan singkat. Anda bisa mengirimkan lokasi dan foto kendala
              untuk penanganan yang lebih akurat.
            </p>

            <div
              class="mt-auto! flex! items-center! justify-between! bg-slate-50! -mx-6! lg:-mx-10! -mb-6! lg:-mb-10! p-6! lg:p-8! border-t! border-slate-100! group-hover:bg-green-50! transition-colors!"
            >
              <div>
                <div
                  class="text-[9px]! lg:text-[10px]! font-black! text-slate-400! uppercase! tracking-widest! mb-1!"
                >
                  Nomor WhatsApp
                </div>
                <div class="text-lg! lg:text-xl! font-black! text-slate-800!">0812-3456-7890</div>
              </div>
              <div
                class="w-10! h-10! lg:w-12! lg:h-12! bg-slate-900! text-white! rounded-xl! lg:rounded-2xl! flex! items-center! justify-center! shadow-lg! group-hover:bg-green-600! transition-all!"
              >
                <font-awesome-icon icon="paper-plane" size="sm" />
              </div>
            </div>
          </div>
        </ContentCard>

        <ContentCard
          variant="elevated"
          padding="none"
          class="border-0! shadow-[0_20px_50px_-12px_rgba(0,0,0,0.12)]! group! transition-all! cursor-pointer! rounded-3xl! overflow-hidden! relative! hover:-translate-y-2! hover:shadow-[0_30px_60px_-15px_rgba(0,0,0,0.2)]!"
          @click="makeCall"
        >
          <div class="absolute! top-0! right-0! p-6!">
            <div class="flex! items-center! gap-2! bg-red-500/10! px-3! py-1! rounded-full!">
              <div class="w-2! h-2! bg-red-500! rounded-full! animate-pulse!"></div>
              <span class="text-[10px]! font-black! text-red-600! uppercase! tracking-widest!"
                >24 Jam</span
              >
            </div>
          </div>

          <div class="p-6! lg:p-10! flex! flex-col! h-full!">
            <div class="flex! items-center! gap-4! lg:gap-6! mb-8! lg:mb-10!">
              <div
                class="w-16! lg:w-20! h-16! lg:h-20! bg-gradient-to-br! from-red-500! to-rose-700! text-white! rounded-2xl! lg:rounded-[2rem]! flex! items-center! justify-center! shadow-xl! shadow-red-200! group-hover:rotate-6! transition-transform!"
              >
                <font-awesome-icon icon="phone" class="text-2xl! lg:text-4xl!" />
              </div>
              <div>
                <h3 class="text-lg! lg:text-xl! font-black! text-slate-800! tracking-tight!">
                  Panggilan Darurat
                </h3>
                <p
                  class="text-[9px]! lg:text-[10px]! text-slate-400! font-black! uppercase! tracking-widest! mt-1!"
                >
                  Hanya untuk Kondisi Mendesak
                </p>
              </div>
            </div>

            <p class="text-slate-500! leading-relaxed! mb-10! font-medium!">
              Gunakan layanan telepon untuk kendala kritis seperti pipa utama pecah atau air
              berhenti total di seluruh wilayah.
            </p>

            <div
              class="mt-auto! flex! items-center! justify-between! bg-slate-50! -mx-6! lg:-mx-10! -mb-6! lg:-mb-10! p-6! lg:p-8! border-t! border-slate-100! group-hover:bg-red-50! transition-colors!"
            >
              <div>
                <div
                  class="text-[9px]! lg:text-[10px]! font-black! text-slate-400! uppercase! tracking-widest! mb-1!"
                >
                  Nomor Hotline
                </div>
                <div class="text-lg! lg:text-xl! font-black! text-slate-800!">(0274) 889-123</div>
              </div>
              <div
                class="w-10! h-10! lg:w-12! lg:h-12! bg-slate-900! text-white! rounded-xl! lg:rounded-2xl! flex! items-center! justify-center! shadow-lg! group-hover:bg-red-600! transition-all!"
              >
                <font-awesome-icon icon="phone-volume" size="sm" />
              </div>
            </div>
          </div>
        </ContentCard>
      </div>

      <ContentCard
        variant="elevated"
        padding="large"
        class="bg-slate-900! rounded-3xl! text-white! overflow-hidden! relative! border-0! shadow-2xl! shadow-slate-900/20!"
      >
        <div class="relative! z-10!">
          <h3 class="text-xl! font-bold! mb-6!">Panduan Pelaporan</h3>
          <div class="space-y-6!">
            <div v-for="(step, idx) in steps" :key="idx" class="flex! gap-4!">
              <div
                class="w-8! h-8! bg-white/10! rounded-xl! flex-shrink-0! flex! items-center! justify-center! text-sm! font-black! border! border-white/10!"
              >
                {{ idx + 1 }}
              </div>
              <div>
                <h4 class="font-bold! text-slate-200!">{{ step.title }}</h4>
                <p class="text-xs! text-slate-400! mt-1! leading-relaxed!">
                  {{ step.desc }}
                </p>
              </div>
            </div>
          </div>
        </div>
        <div
          class="absolute! -right-20! -bottom-20! w-64! h-64! bg-indigo-500/10! rounded-full! blur-3xl!"
        ></div>
      </ContentCard>
    </div>
  </div>
</template>

<script setup>
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'

const steps = [
  {
    title: 'Identifikasi Masalah',
    desc: 'Pastikan apakah air mati total, debit kecil, atau ada kebocoran pada pipa.',
  },
  {
    title: 'Siapkan ID Pelanggan',
    desc: 'Sebutkan ID Pelanggan Anda agar tim kami dapat melacak lokasi dengan cepat.',
  },
  {
    title: 'Lampirkan Foto/Video',
    desc: 'Khusus untuk kebocoran, lampirkan bukti visual agar tim teknisi membawa peralatan yang tepat.',
  },
]

const openWhatsApp = () => {
  window.open('https://wa.me/6281234567890', '_blank')
}

const makeCall = () => {
  window.location.href = 'tel:0274889123'
}
</script>

<style scoped>
.report-trouble {
  animation: slideInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
