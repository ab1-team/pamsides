<template>
  <div class="flex flex-col gap-6! py-2!">
    <div
      class="relative! group! py-4! px-6! bg-white! border! border-slate-100! rounded-2xl! shadow-sm!"
    >
      <div class="flex flex-col md:flex-row! md:items-center! gap-8!">
        <div class="flex-1! py-1!">
          <div
            class="inline-flex! items-center! gap-2! px-2.5! py-1! bg-indigo-50! text-indigo-600! rounded-lg! mb-4!"
          >
            <font-awesome-icon icon="shield-halved" class="text-[10px]!" />
            <span class="text-[9px]! font-black! uppercase! tracking-widest!"
              >Identitas Lembaga</span
            >
          </div>

          <h3 class="text-lg! md:text-xl! font-black! text-slate-800! leading-none! mb-3!">
            Logo Lembaga Utama
          </h3>

          <p class="text-[11px]! text-slate-500! leading-relaxed! mb-6! max-w-sm!">
            Logo ini akan ditampilkan pada laporan, tagihan, dan halaman dashboard utama. Gunakan
            file dengan latar belakang transparan (PNG) untuk hasil terbaik.
          </p>

          <div class="flex! items-center! gap-4!">
            <div class="flex! flex-col! gap-1!">
              <span class="text-[9px]! font-bold! text-slate-400! uppercase! tracking-tighter!"
                >Format File</span
              >
              <div
                class="px-3! py-1! bg-slate-50! border! border-slate-100! rounded-lg! text-[10px]! font-black! text-slate-700!"
              >
                PNG / JPG
              </div>
            </div>
            <div class="flex! flex-col! gap-1!">
              <span class="text-[9px]! font-bold! text-slate-400! uppercase! tracking-tighter!"
                >Ukuran Maksimum</span
              >
              <div
                class="px-3! py-1! bg-slate-50! border! border-slate-100! rounded-lg! text-[10px]! font-black! text-slate-700!"
              >
                2 MB
              </div>
            </div>
          </div>
        </div>

        <div class="w-48! h-48! md:w-64! md:h-64! shrink-0! mx-auto! md:mx-0!">
          <div
            @click="triggerUpload('mainLogo')"
            class="group/logo relative! w-full! h-full! bg-slate-50! border-2! border-dashed! border-slate-200! rounded-2xl! overflow-hidden! cursor-pointer! hover:border-indigo-400! hover:bg-white! transition-all! duration-500! shadow-inner!"
          >
            <div class="absolute inset-0! flex! items-center! justify-center! p-4! md:p-6!">
              <img
                :src="form.previews.mainLogo"
                class="max-w-full! max-h-full! object-contain! transition-transform! duration-700! group-hover/logo:scale-110!"
              />
            </div>

            <div
              class="absolute inset-0! bg-indigo-900/60! backdrop-blur-[2px]! flex! items-center! justify-center! opacity-0! group-hover/logo:opacity-100! transition-all! duration-500!"
            >
              <div
                class="flex! flex-col! items-center! gap-2! translate-y-4! group-hover/logo:translate-y-0! transition-transform! duration-500!"
              >
                <div
                  class="w-10! h-10! bg-white! rounded-full! flex! items-center! justify-center! shadow-2xl!"
                >
                  <font-awesome-icon icon="camera" class="text-indigo-600!" />
                </div>
                <span class="text-[10px]! font-bold! text-white! uppercase! tracking-widest!"
                  >Unggah Logo</span
                >
              </div>
            </div>
          </div>
          <input
            type="file"
            id="mainLogo"
            class="hidden"
            accept="image/*"
            @change="onUpload($event, 'mainLogo')"
          />
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
        Simpan Branding
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import BaseButton from '@/components/ui/BaseButton.vue'

const form = defineModel({ required: true })

defineProps({
  onUpload: {
    type: Function,
    required: true,
  },
  onSave: {
    type: Function,
    required: true,
  },
})

const triggerUpload = (id) => {
  document.getElementById(id).click()
}
</script>
