<template>
  <div class="flex flex-col gap-6! py-2!">
    <div class="relative! p-1! bg-slate-50/50! border! border-slate-100! rounded-3xl! shadow-sm!">
      <div
        class="grid grid-cols-1 md:grid-cols-3! divide-y! md:divide-y-0! md:divide-x! divide-slate-100!"
      >
        <div
          class="p-5! hover:bg-white! transition-colors! first:rounded-t-3xl! md:first:rounded-tr-none! md:first:rounded-l-3xl!"
        >
          <div class="flex! flex-col! gap-3!">
            <div class="flex! items-center! gap-2.5!">
              <div
                class="w-8! h-8! rounded-lg! bg-amber-500! text-white! flex! items-center! justify-center! text-xs! shadow-lg! shadow-amber-100!"
              >
                <font-awesome-icon icon="calendar-day" />
              </div>
              <div class="flex-1!">
                <h4 class="text-xs! font-black! text-slate-800! leading-tight!">
                  Batas Tagihan Bulanan
                </h4>
                <p class="text-[10px]! text-slate-400!">Tanggal jatuh tempo.</p>
              </div>
            </div>
            <BaseInput
              v-model="form.jatuhTempo"
              placeholder="Tanggal"
              type="number"
              size="md"
              prefix-icon="calendar"
              :no-margin="true"
              class="w-full!"
            />
          </div>
        </div>

        <div class="p-5! hover:bg-white! transition-colors!">
          <div class="flex! flex-col! gap-3!">
            <div class="flex! items-center! gap-2.5!">
              <div
                class="w-8! h-8! rounded-lg! bg-indigo-500! text-white! flex! items-center! justify-center! text-xs! shadow-lg! shadow-indigo-100!"
              >
                <font-awesome-icon icon="file-invoice-dollar" />
              </div>
              <div class="flex-1!">
                <h4 class="text-xs! font-black! text-slate-800! leading-tight!">Biaya Aktivasi</h4>
                <p class="text-[10px]! text-slate-400!">Biaya per aktif kembali.</p>
              </div>
            </div>
            <MaksMoneyInput
              v-model="form.biayaAktivasi"
              placeholder="0"
              :no-margin="true"
              class="w-full!"
            />
          </div>
        </div>

        <div
          class="p-5! hover:bg-white! transition-colors! last:rounded-b-3xl! md:last:rounded-bl-none! md:last:rounded-r-3xl!"
        >
          <div class="flex! flex-col! gap-3!">
            <div class="flex! items-center! gap-2.5!">
              <div
                class="w-8! h-8! rounded-lg! bg-rose-500! text-white! flex! items-center! justify-center! text-xs! shadow-lg! shadow-rose-100!"
              >
                <font-awesome-icon icon="history" />
              </div>
              <div class="flex-1!">
                <h4 class="text-xs! font-black! text-slate-800! leading-tight!">
                  Toleransi Menunggak
                </h4>
                <p class="text-[10px]! text-slate-400!">Batas bulan tunggakan.</p>
              </div>
            </div>
            <BaseInput
              v-model="form.toleransiTunggakan"
              placeholder="0 Bulan"
              type="number"
              size="md"
              prefix-icon="history"
              :no-margin="true"
              class="w-full!"
            />
          </div>
        </div>
      </div>
    </div>

    <div
      class="p-5! bg-linear-to-r! from-indigo-900/5! to-blue-900/5! border! border-indigo-100! rounded-2xl! relative! overflow-hidden!"
    >
      <div
        class="absolute! -top-8! -right-8! w-32! h-32! bg-indigo-600/5! rounded-full! blur-3xl!"
      ></div>

      <div class="flex! items-start! gap-4! relative! z-10!">
        <div
          class="w-10! h-10! rounded-xl! bg-indigo-600! flex! items-center! justify-center! text-white! shadow-lg! shadow-indigo-100! shrink-0!"
        >
          <font-awesome-icon icon="robot" class="text-lg!" />
        </div>
        <div>
          <h4 class="text-xs! font-black! text-indigo-900! uppercase! tracking-widest! mb-1!">
            Otomatisasi Status Sistem
          </h4>
          <p class="text-[11px]! font-medium! text-slate-500! leading-relaxed!">
            Instalasi <span class="text-indigo-600! font-bold!">AKTIF</span> yang melewati batas
            toleransi tunggakan di atas akan otomatis diubah menjadi
            <span class="text-rose-600! font-bold!">BLOKIR</span>. Sistem akan memberikan perintah
            penutupan air sementara hingga seluruh tunggakan dilunasi.
          </p>
        </div>
      </div>
    </div>

    <div
      class="flex! items-center! justify-center! md:justify-end! pt-4! border-t! border-slate-50!"
    >
      <BaseButton
        variant="secondary"
        size="md"
        @click="onSave"
        icon="save"
        class="w-full! md:w-auto! rounded-xl! px-8! shadow-lg! shadow-indigo-100!"
      >
        Simpan Sistem Tagihan
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import BaseInput from '@/presentations/components/ui/BaseInput.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import MaksMoneyInput from '@/presentations/components/MaksMoneyInput.vue'

const form = defineModel({ required: true })

defineProps({
  onSave: {
    type: Function,
    required: true,
  },
})
</script>
