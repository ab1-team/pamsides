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
            class="relative! w-full! h-full! md:w-[95%]! md:h-[92%]! md:max-w-5xl! bg-white! rounded-t-3xl! md:rounded-[2.5rem]! shadow-2xl! flex! flex-col! overflow-hidden!"
          >
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
                    Edit Pemakaian Air
                  </h1>
                  <p
                    class="text-[9px]! md:text-[10px]! font-bold! uppercase! tracking-widest! text-slate-400!"
                  >
                    Billing • Revision Mode
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

            <div class="flex-1! overflow-y-auto! p-4! md:p-8! custom-scrollbar!">
              <div class="max-w-4xl! mx-auto! space-y-6!">
                <section>
                  <div
                    class="flex! items-center! gap-2! mb-3! md:mb-4! text-slate-800! font-black! uppercase! text-[10px]! md:text-xs! tracking-wider!"
                  >
                    <font-awesome-icon
                      icon="user"
                      class="p-1.5! md:p-2! bg-slate-900! text-white! rounded-lg!"
                    />
                    Informasi Pelanggan
                  </div>
                  <div
                    class="flex! flex-col! md:flex-row! items-center! gap-0! bg-linear-to-br! from-blue-600! via-blue-700! to-indigo-800! border-0! rounded-2xl! overflow-hidden! shadow-lg! shadow-blue-500/20!"
                  >
                    <div
                      class="flex-1! w-full! flex! items-center! justify-between! gap-4! px-5! md:px-6! py-3! md:py-4! border-b! md:border-b-0! md:border-r! border-white/10!"
                    >
                      <span
                        class="text-[9px]! md:text-[10px]! font-black! text-blue-100/60! uppercase! tracking-[0.2em]!"
                        >PELANGGAN</span
                      >
                      <span class="text-xs! md:text-sm! font-bold! text-white! tracking-tight!">{{
                        localData.nama
                      }}</span>
                    </div>
                    <div
                      class="flex-1! w-full! flex! items-center! justify-between! gap-4! px-5! md:px-6! py-3! md:py-4!"
                    >
                      <span
                        class="text-[9px]! md:text-[10px]! font-black! text-blue-100/60! uppercase! tracking-[0.2em]!"
                        >KODE INST.</span
                      >
                      <span class="text-xs! md:text-sm! font-mono! font-bold! text-blue-50!"
                        >#{{ localData.id }}</span
                      >
                    </div>
                  </div>
                </section>

                <section>
                  <div
                    class="flex! items-center! gap-2! mb-3! md:mb-4! text-slate-800! font-black! uppercase! text-[10px]! md:text-xs! tracking-wider!"
                  >
                    <font-awesome-icon
                      icon="tint"
                      class="p-1.5! md:p-2! bg-cyan-600! text-white! rounded-lg!"
                    />
                    Detail Pemakaian
                  </div>
                  <div
                    class="grid! md:grid-cols-2! gap-3! md:gap-4! p-4! md:p-6! bg-slate-50/50! border! border-slate-100! rounded-2xl! md:rounded-3xl!"
                  >
                    <AppDatePicker
                      v-model="localData.tanggalAkhir"
                      label="TANGGAL AKHIR"
                      class="usage-input"
                      no-margin
                    />
                    <BaseInput
                      v-model="localData.meterAwal"
                      label="AWAL PEMAKAIAN"
                      type="number"
                      class="usage-input"
                      no-margin
                    />
                    <BaseInput
                      v-model="localData.meterAkhir"
                      label="AKHIR PEMAKAIAN"
                      type="number"
                      class="usage-input"
                      no-margin
                    />
                    <BaseInput
                      :model-value="calculatedJumlah"
                      label="TOTAL PEMAKAIAN"
                      disabled
                      class="usage-input"
                      no-margin
                    />
                  </div>
                </section>
              </div>
            </div>

            <div
              class="p-4! md:p-6! border-t! border-slate-100! flex! flex-col! md:flex-row! items-center! justify-between! gap-4!"
            >
              <p class="text-[9px]! md:text-[10px]! text-slate-400! font-medium!">
                Validasi: Meter akhir ≥ Meter awal
              </p>
              <div class="flex! gap-3! w-full! md:w-auto!">
                <BaseButton
                  variant="secondary-gradient"
                  class="flex-1! px-8! md:px-10! py-3! rounded-xl! font-black! text-slate-800! shadow-lg! shadow-slate-200/50! text-sm! md:text-base!"
                  @click="handleSave"
                >
                  Simpan Perubahan
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
import { ref, watch, computed } from 'vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import BaseInput from '@/presentations/components/ui/BaseInput.vue'
import AppDatePicker from '@/presentations/components/AppDatePicker.vue'
import { MySwal } from '@/utils/swal'

const props = defineProps({ show: Boolean, data: { type: Object, default: () => ({}) } })
const emit = defineEmits(['close', 'save'])
const localData = ref({
  nama: '',
  id: '',
  initials: '',
  tanggalAkhir: new Date(),
  meterAwal: 0,
  meterAkhir: 0,
})

watch(
  () => props.data,
  (v) => {
    if (v)
      localData.value = {
        ...v,
        tanggalAkhir: v.tanggalAkhir || new Date(),
        meterAkhir: v.meterAkhir || 0,
      }
  },
  { immediate: true },
)
const calculatedJumlah = computed(() =>
  Math.max(0, (localData.value.meterAkhir || 0) - (localData.value.meterAwal || 0)),
)

const handleClose = () => emit('close')
const handleSave = () => {
  if (localData.value.meterAkhir < localData.value.meterAwal) {
    return MySwal.fire({
      icon: 'error',
      title: 'Gagal',
      text: 'Meter akhir tidak valid!',
      confirmButtonColor: '#ef4444',
    })
  }

  MySwal.fire({
    icon: 'success',
    title: 'Berhasil diperbarui',
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2000,
    timerProgressBar: true,
    customClass: {
      popup: 'swal-toast-custom',
    },
  })

  emit('save', { ...localData.value, pemakaian: calculatedJumlah.value })
  handleClose()
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}

/* Global Override Detail Pemakaian */
:deep(.usage-input .label),
:deep(.usage-input .base-input__label) {
  font-size: 11px! !important;
  font-weight: 800! !important;
  color: #64748b! !important;
  margin-bottom: 2px! !important;
}
:deep(.usage-input input),
:deep(.usage-input .p-inputtext) {
  height: 3rem! !important;
  width: 100%! !important;
  border-radius: 0.75rem! !important;
  background: #fff! !important;
  border: 1px solid #e2e8f0! !important;
  font-weight: 700! !important;
  font-size: 0.95rem! !important;
}
:deep(.usage-input input:disabled) {
  background: #f1f5f9! !important;
  color: #94a3b8! !important;
}
</style>
