<template>
  <div class="flex flex-col gap-6! py-2!">
    <div
      class="relative! group! py-3! px-6! bg-white! border! border-slate-100! rounded-2xl! shadow-sm! hover:shadow-md! transition-all! duration-300!"
    >
      <div class="flex flex-col sm:flex-row! sm:items-center! gap-6!">
        <div class="flex-1! flex! items-center! gap-4!">
          <div
            class="w-8! h-8! rounded-lg! bg-indigo-50! text-indigo-600! flex! items-center! justify-center! text-xs! shrink-0!"
          >
            <font-awesome-icon icon="file-invoice-dollar" />
          </div>
          <div class="flex! flex-col!">
            <h4 class="text-slate-800! font-bold! text-sm! leading-tight!">Biaya Pasang Baru</h4>
            <p class="text-[10px]! text-slate-400! leading-relaxed!">
              Total biaya pendaftaran dan instalasi baru yang ditagihkan.
            </p>
          </div>
        </div>

        <div class="w-full sm:w-56!">
          <MaksMoneyInput v-model="form.biayaPasang" placeholder="0" :no-margin="true" />
        </div>
      </div>
    </div>

    <div class="space-y-4!">
      <div
        class="flex! items-center! gap-2! text-slate-800! font-bold! text-xs! uppercase! tracking-wider!"
      >
        <font-awesome-icon icon="info-circle" class="text-indigo-500!" />
        Status Pembayaran Pendaftaran
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4!">
        <div
          @click="form.statusPembayaran = 'wajib'"
          class="group! relative! cursor-pointer! py-3.5! px-5! rounded-2xl! border-2! transition-all! duration-300!"
          :class="[
            form.statusPembayaran === 'wajib'
              ? 'border-indigo-600! bg-indigo-50/30! shadow-lg! shadow-indigo-100!'
              : 'border-slate-100! bg-white! hover:border-indigo-200! hover:bg-slate-50/50!',
          ]"
        >
          <div class="flex! items-center! gap-4!">
            <div
              class="w-10! h-10! rounded-xl! flex! items-center! justify-center! transition-colors! shrink-0!"
              :class="
                form.statusPembayaran === 'wajib'
                  ? 'bg-indigo-600! text-white!'
                  : 'bg-slate-100! text-slate-400! group-hover:bg-indigo-100! group-hover:text-indigo-600!'
              "
            >
              <font-awesome-icon icon="lock" class="text-base!" />
            </div>
            <div class="flex-1!">
              <div class="flex! items-center! justify-between! mb-1!">
                <span
                  class="font-bold! text-sm!"
                  :class="
                    form.statusPembayaran === 'wajib' ? 'text-indigo-900!' : 'text-slate-700!'
                  "
                >
                  A. Wajib Lunas
                </span>
                <font-awesome-icon
                  v-if="form.statusPembayaran === 'wajib'"
                  icon="check-circle"
                  class="text-indigo-600!"
                />
              </div>
              <p
                class="text-[11px]! leading-relaxed!"
                :class="
                  form.statusPembayaran === 'wajib' ? 'text-indigo-700/70!' : 'text-slate-400!'
                "
              >
                Pelanggan harus melunasi seluruh biaya sebelum pemasangan dilakukan.
              </p>
            </div>
          </div>
        </div>

        <div
          @click="form.statusPembayaran = 'tidak'"
          class="group! relative! cursor-pointer! py-3.5! px-5! rounded-2xl! border-2! transition-all! duration-300!"
          :class="[
            form.statusPembayaran === 'tidak'
              ? 'border-indigo-600! bg-indigo-50/30! shadow-lg! shadow-indigo-100!'
              : 'border-slate-100! bg-white! hover:border-indigo-200! hover:bg-slate-50/50!',
          ]"
        >
          <div class="flex! items-center! gap-4!">
            <div
              class="w-10! h-10! rounded-xl! flex! items-center! justify-center! transition-colors! shrink-0!"
              :class="
                form.statusPembayaran === 'tidak'
                  ? 'bg-indigo-600! text-white!'
                  : 'bg-slate-100! text-slate-400! group-hover:bg-indigo-100! group-hover:text-indigo-600!'
              "
            >
              <font-awesome-icon icon="unlock" class="text-base!" />
            </div>
            <div class="flex-1!">
              <div class="flex! items-center! justify-between! mb-1!">
                <span
                  class="font-bold! text-sm!"
                  :class="
                    form.statusPembayaran === 'tidak' ? 'text-indigo-900!' : 'text-slate-700!'
                  "
                >
                  B. Tidak Wajib Lunas
                </span>
                <font-awesome-icon
                  v-if="form.statusPembayaran === 'tidak'"
                  icon="check-circle"
                  class="text-indigo-600!"
                />
              </div>
              <p
                class="text-[11px]! leading-relaxed!"
                :class="
                  form.statusPembayaran === 'tidak' ? 'text-indigo-700/70!' : 'text-slate-400!'
                "
              >
                Pemasangan tetap bisa dilakukan meskipun pembayaran belum lunas sepenuhnya.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="space-y-4!">
      <div
        class="flex! items-center! gap-2! text-slate-800! font-bold! text-xs! uppercase! tracking-wider!"
      >
        <font-awesome-icon icon="info-circle" class="text-cyan-500!" />
        Kategori Layanan Pendaftaran
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4!">
        <!-- Pemakaian Air (Wajib) -->
        <div
          class="relative! py-3.5! px-8! rounded-full! border-2! transition-all! duration-300! border-cyan-600! bg-cyan-50/30! shadow-lg! shadow-cyan-100!"
        >
          <div class="flex! items-center! gap-4!">
            <div
              class="w-10! h-10! rounded-full! flex! items-center! justify-center! bg-cyan-600! text-white! shrink-0!"
            >
              <font-awesome-icon icon="droplet" class="text-base!" />
            </div>
            <div class="flex-1!">
              <div class="flex! items-center! justify-between! mb-0.5!">
                <div class="flex! items-center! gap-2!">
                  <span class="font-bold! text-sm! text-cyan-900!"> Pemakaian Air </span>
                  <span
                    class="px-1.5! py-0.5! bg-cyan-600! text-white! text-[8px]! font-black! uppercase! rounded-md! tracking-tighter!"
                  >
                    Wajib
                  </span>
                </div>
                <font-awesome-icon icon="check-circle" class="text-cyan-600!" />
              </div>
              <p class="text-[11px]! leading-tight! text-cyan-700/70!">
                Sambungan baru pemakaian air bersih.
              </p>
            </div>
          </div>
        </div>

        <!-- Retribusi Sampah (Opsional) -->
        <div
          @click="form.enableSampah = !form.enableSampah"
          class="group! relative! cursor-pointer! py-3.5! px-8! rounded-full! border-2! transition-all! duration-300!"
          :class="[
            form.enableSampah
              ? 'border-emerald-600! bg-emerald-50/30! shadow-lg! shadow-emerald-100!'
              : 'border-slate-100! bg-white! hover:border-emerald-200! hover:bg-slate-50/50!',
          ]"
        >
          <div class="flex! items-center! gap-4!">
            <div
              class="w-10! h-10! rounded-full! flex! items-center! justify-center! transition-colors! shrink-0!"
              :class="
                form.enableSampah
                  ? 'bg-emerald-600! text-white!'
                  : 'bg-slate-100! text-slate-400! group-hover:bg-emerald-100! group-hover:text-emerald-600!'
              "
            >
              <font-awesome-icon icon="trash" class="text-base!" />
            </div>
            <div class="flex-1!">
              <div class="flex! items-center! justify-between! mb-0.5!">
                <span
                  class="font-bold! text-sm!"
                  :class="form.enableSampah ? 'text-emerald-900!' : 'text-slate-700!'"
                >
                  Retribusi Sampah
                </span>
                <font-awesome-icon
                  v-if="form.enableSampah"
                  icon="check-circle"
                  class="text-emerald-600!"
                />
              </div>
              <p
                class="text-[11px]! leading-tight!"
                :class="form.enableSampah ? 'text-emerald-700/70!' : 'text-slate-400!'"
              >
                Layanan retribusi pengelolaan sampah.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div
      class="flex! items-center! justify-center! md:justify-end! pt-6! border-t! border-slate-50!"
    >
      <BaseButton
        variant="secondary"
        size="md"
        @click="onSave"
        icon="save"
        class="w-full! md:w-auto! rounded-xl! px-8! shadow-lg! shadow-indigo-100!"
      >
        Simpan Pengaturan Pasang Baru
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import BaseButton from '@/components/ui/BaseButton.vue'
import MaksMoneyInput from '@/components/MaksMoneyInput.vue'

const form = defineModel({ required: true })

defineProps({
  onSave: {
    type: Function,
    required: true,
  },
})
</script>
