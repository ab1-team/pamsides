<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition! duration-300! ease-out!"
      enter-from-class="opacity-0!"
      enter-to-class="opacity-100!"
      leave-active-class="transition! duration-200! ease-in!"
      leave-from-class="opacity-100!"
      leave-to-class="opacity-0!"
    >
      <div v-if="show" class="fixed! inset-0! z-[9999]! flex! items-center! justify-center!">
        <div
          class="absolute! inset-0! bg-slate-900/40! backdrop-blur-xl!"
          @click="handleClose"
        ></div>

        <Transition
          enter-active-class="transition! duration-500! [transition-timing-function:cubic-bezier(0.34,1.56,0.64,1)]!"
          enter-from-class="opacity-0! scale-95! translate-y-8!"
          enter-to-class="opacity-100! scale-100! translate-y-0!"
          leave-active-class="transition! duration-300! ease-in!"
          leave-from-class="opacity-100! scale-100! translate-y-0!"
          leave-to-class="opacity-0! scale-95! translate-y-8!"
        >
          <div
            v-if="show"
            class="relative! w-full! h-full! md:w-[95%]! md:h-[92%]! md:max-w-4xl! bg-white! rounded-t-3xl! md:rounded-[2.5rem]! shadow-2xl! flex! flex-col! overflow-hidden!"
          >
            <!-- Header Bar -->
            <div
              class="h-1.5! w-full! bg-linear-to-r! from-cyan-500! via-blue-600! to-indigo-600!"
            ></div>

            <div
              class="px-4! md:px-6! py-3! md:py-4! border-b! border-slate-100! flex! items-center! justify-between! bg-white/80! backdrop-blur-md! sticky! top-0! z-10!"
            >
              <div class="flex! items-center! gap-3! md:gap-4!">
                <div
                  class="w-10! h-10! md:w-12! md:h-12! rounded-xl! bg-cyan-50! flex! items-center! justify-center! border! border-cyan-100!"
                >
                  <font-awesome-icon icon="edit" class="text-lg! md:text-xl! text-cyan-600!" />
                </div>
                <div>
                  <h1 class="text-lg! md:text-2xl! font-black! text-slate-800! leading-none! mb-1!">
                    Edit Retribusi Sampah
                  </h1>
                  <p
                    class="text-[9px]! md:text-[10px]! font-bold! uppercase! tracking-widest! text-slate-400!"
                  >
                    Waste Management • Admin Mode
                  </p>
                </div>
              </div>
              <BaseButton
                variant="ghost"
                class="w-9! h-9! md:w-10! md:h-10! rounded-full! bg-slate-100! text-slate-500! hover:bg-red-50! hover:text-red-600! transition-all!"
                @click="handleClose"
              >
                <font-awesome-icon icon="times" />
              </BaseButton>
            </div>

            <!-- Content Area -->
            <div class="flex-1! overflow-y-auto! p-4! md:p-8! custom-scrollbar!">
              <div class="max-w-3xl! mx-auto! space-y-8!">
                <!-- Section: Customer Info -->
                <section>
                  <div
                    class="flex! items-center! gap-2! mb-4! text-slate-800! font-black! uppercase! text-[10px]! md:text-xs! tracking-wider!"
                  >
                    <font-awesome-icon
                      icon="user"
                      class="p-1.5! md:p-2! bg-slate-900! text-white! rounded-lg!"
                    />
                    Informasi Pelanggan
                  </div>
                  <div
                    class="flex! flex-col! md:flex-row! items-center! gap-0! bg-linear-to-br! from-cyan-600! via-cyan-700! to-blue-800! border-0! rounded-2xl! overflow-hidden! shadow-lg! shadow-cyan-500/20!"
                  >
                    <div
                      class="flex-1! w-full! flex! items-center! justify-between! gap-4! px-5! md:px-6! py-3! md:py-4! border-b! md:border-b-0! md:border-r! border-white/10!"
                    >
                      <span
                        class="text-[9px]! md:text-[10px]! font-black! text-cyan-100/60! uppercase! tracking-[0.2em]!"
                        >NAMA LENGKAP</span
                      >
                      <span class="text-xs! md:text-sm! font-bold! text-white! tracking-tight!">
                        {{ localData.nama }}
                      </span>
                    </div>
                    <div
                      class="flex-1! w-full! flex! items-center! justify-between! gap-4! px-5! md:px-6! py-3! md:py-4!"
                    >
                      <span
                        class="text-[9px]! md:text-[10px]! font-black! text-cyan-100/60! uppercase! tracking-[0.2em]!"
                        >ID RETRIBUSI</span
                      >
                      <span class="text-xs! md:text-sm! font-mono! font-bold! text-cyan-50!">
                        #{{ localData.id }}
                      </span>
                    </div>
                  </div>
                </section>

                <!-- Section: Fee Details -->
                <section>
                  <div
                    class="flex! items-center! gap-2! mb-4! text-slate-800! font-black! uppercase! text-[10px]! md:text-xs! tracking-wider!"
                  >
                    <font-awesome-icon
                      icon="file-invoice-dollar"
                      class="p-1.5! md:p-2! bg-cyan-600! text-white! rounded-lg!"
                    />
                    Detail Tagihan & Status
                  </div>
                  <div
                    class="grid! md:grid-cols-2! gap-4! md:gap-6! p-4! md:p-8! bg-slate-50/50! border! border-slate-100! rounded-2xl! md:rounded-3xl!"
                  >
                    <div class="space-y-1.5!">
                      <label class="text-[11px]! font-black! text-slate-500! uppercase! tracking-wider!">Jumlah Tagihan (Rp)</label>
                      <BaseInput
                        v-model="localData.tagihan"
                        type="number"
                        placeholder="25.000"
                        no-margin
                        class="premium-input!"
                      />
                    </div>

                    <div class="space-y-1.5!">
                      <label class="text-[11px]! font-black! text-slate-500! uppercase! tracking-wider!">Unit Pemakaian</label>
                      <BaseInput
                        v-model="localData.pemakaian"
                        type="number"
                        placeholder="1"
                        no-margin
                        class="premium-input!"
                      />
                    </div>

                    <div class="space-y-1.5!">
                      <label class="text-[11px]! font-black! text-slate-500! uppercase! tracking-wider!">Jatuh Tempo</label>
                      <BaseInput
                        v-model="localData.jatuhTempo"
                        placeholder="20 Mei 2024"
                        no-margin
                        class="premium-input!"
                      />
                    </div>

                    <div class="space-y-1.5!">
                      <label class="text-[11px]! font-black! text-slate-500! uppercase! tracking-wider!">Status Pembayaran</label>
                      <div class="grid! grid-cols-2! gap-2!">
                        <button
                          v-for="status in ['Sudah Bayar', 'Belum Bayar']"
                          :key="status"
                          @click="localData.status = status"
                          :class="[
                            'py-2.5! rounded-xl! text-[10px]! font-black! uppercase! tracking-widest! transition-all!',
                            localData.status === status
                              ? 'bg-cyan-600! text-white! shadow-lg! shadow-cyan-200!'
                              : 'bg-white! text-slate-400! border! border-slate-200! hover:border-cyan-200!'
                          ]"
                        >
                          {{ status }}
                        </button>
                      </div>
                    </div>
                  </div>
                </section>
              </div>
            </div>

            <!-- Footer Actions -->
            <div
              class="p-4! md:p-6! border-t! border-slate-100! bg-slate-50/30! flex! flex-col! md:flex-row! items-center! justify-between! gap-4!"
            >
              <div class="flex! items-center! gap-2! text-slate-400!">
                <font-awesome-icon icon="info-circle" class="text-xs!" />
                <p class="text-[9px]! md:text-[10px]! font-bold! uppercase! tracking-widest!">
                  Perubahan data akan langsung berdampak pada laporan.
                </p>
              </div>
              <div class="flex! gap-3! w-full! md:w-auto!">
                <BaseButton
                  variant="primary-gradient"
                  class="flex-1! px-8! md:px-12! py-4! rounded-2xl! font-black! text-white! shadow-xl! shadow-cyan-200! transition-all! hover:scale-105! active:scale-95!"
                  @click="handleSave"
                >
                  SIMPAN PERUBAHAN
                </BaseButton>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import BaseInput from '@/presentations/components/ui/BaseInput.vue'
import Swal from 'sweetalert2'

const props = defineProps({
  show: Boolean,
  data: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['close', 'save'])

const localData = ref({
  id: '',
  nama: '',
  tagihan: 0,
  pemakaian: 0,
  jatuhTempo: '',
  status: ''
})

watch(
  () => props.data,
  (v) => {
    if (v) {
      localData.value = { ...v }
    }
  },
  { immediate: true }
)

const handleClose = () => emit('close')

const handleSave = () => {
  if (!localData.value.tagihan) {
    return Swal.fire({
      icon: 'error',
      title: 'Validasi Gagal',
      text: 'Jumlah tagihan tidak boleh kosong!',
      confirmButtonColor: '#0891b2'
    })
  }

  Swal.fire({
    icon: 'success',
    title: 'Data Diperbarui',
    text: 'Data retribusi berhasil disimpan ke sistem.',
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true
  })

  emit('save', { ...localData.value })
  handleClose()
}
</script>

<style scoped>
@reference "@/assets/css/main.css";

.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}

:deep(.premium-input input) {
  height: 3.5rem! !important;
  border-radius: 1rem! !important;
  background: #fff! !important;
  border: 1px solid #e2e8f0! !important;
  font-weight: 700! !important;
  font-size: 1rem! !important;
  color: #1e293b! !important;
  transition: all 0.3s! !important;
}

:deep(.premium-input input:focus) {
  border-color: #06b6d4! !important;
  box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.1)! !important;
}
</style>
