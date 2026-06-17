<template>
  <div class="flex flex-col gap-6! py-2!">
    <div
      class="relative! group! py-5! px-6! bg-white! border! border-slate-100! rounded-2xl! shadow-sm!"
    >
      <div
        class="inline-flex! items-center! gap-2! px-2.5! py-1! bg-indigo-50! text-indigo-600! rounded-lg! mb-4!"
      >
        <font-awesome-icon icon="shield-halved" class="text-[10px]!" />
        <span class="text-[9px]! font-black! uppercase! tracking-widest!"
          >Identitas Lembaga</span
        >
      </div>

      <div class="flex! items-start! justify-between! gap-4! mb-5!">
        <div>
          <h3 class="text-lg! md:text-xl! font-black! text-slate-800! leading-none! mb-2!">
            Logo Lembaga
          </h3>
          <p class="text-[11px]! text-slate-500! leading-relaxed! max-w-md!">
            Logo ini akan ditampilkan pada laporan, tagihan, dan halaman dashboard utama. Gunakan
            file dengan latar belakang transparan (PNG) untuk hasil terbaik.
          </p>
        </div>
        <div class="flex! items-center! gap-2! shrink-0!">
          <div class="flex! flex-col! gap-0.5!">
            <span class="text-[9px]! font-bold! text-slate-400! uppercase! tracking-tighter!"
              >Format</span
            >
            <div
              class="px-2.5! py-1! bg-slate-50! border! border-slate-100! rounded-md! text-[10px]! font-black! text-slate-700!"
            >
              PNG / JPG
            </div>
          </div>
          <div class="flex! flex-col! gap-0.5!">
            <span class="text-[9px]! font-bold! text-slate-400! uppercase! tracking-tighter!"
              >Maks</span
            >
            <div
              class="px-2.5! py-1! bg-slate-50! border! border-slate-100! rounded-md! text-[10px]! font-black! text-slate-700!"
            >
              2 MB
            </div>
          </div>
        </div>
      </div>

      <div class="grid! grid-cols-1! md:grid-cols-[auto_1fr]! gap-4! items-stretch!">
        <div
          @click="triggerUpload"
          class="group/logo relative! w-40! h-40! md:w-48! md:h-48! bg-slate-50! border-2! border-dashed! border-slate-200! rounded-2xl! overflow-hidden! cursor-pointer! hover:border-indigo-400! hover:bg-white! transition-all! duration-500! shadow-inner!"
        >
          <div class="absolute inset-0! flex! items-center! justify-center! p-3! md:p-4!">
            <img
              v-if="form.preview"
              :src="form.preview"
              class="max-w-full! max-h-full! object-contain! transition-transform! duration-700! group-hover/logo:scale-105!"
            />
            <div v-else class="flex! flex-col! items-center! gap-2! text-slate-300!">
              <div
                class="w-14! h-14! rounded-xl! bg-white! border! border-slate-100! flex! items-center! justify-center! shadow-sm!"
              >
                <font-awesome-icon icon="image" class="text-2xl!" />
              </div>
              <div class="flex! flex-col! items-center! gap-0.5!">
                <span
                  class="text-[10px]! font-black! uppercase! tracking-widest! text-slate-400!"
                  >Belum ada logo</span
                >
                <span class="text-[9px]! text-slate-400!">Klik untuk unggah</span>
              </div>
            </div>
          </div>

          <div
            class="absolute inset-0! bg-indigo-500/60! backdrop-blur-[2px]! flex! items-center! justify-center! opacity-0! group-hover/logo:opacity-100! transition-all! duration-500!"
          >
            <div
              class="flex! flex-col! items-center! gap-1.5! translate-y-3! group-hover/logo:translate-y-0! transition-transform! duration-500!"
            >
              <div
                class="w-10! h-10! bg-white! rounded-full! flex! items-center! justify-center! shadow-2xl!"
              >
                <font-awesome-icon icon="camera" class="text-indigo-600! text-base!" />
              </div>
              <span class="text-[10px]! font-black! text-white! uppercase! tracking-widest!"
                >{{ form.preview ? 'Ganti' : 'Unggah' }}</span
              >
            </div>
          </div>
        </div>

        <div
          class="h-40! md:h-48! p-4! rounded-2xl! border! border-slate-100! bg-slate-50/60! flex! flex-col! justify-between! gap-3!"
        >
          <div v-if="form.file || form.preview" class="flex! items-start! gap-3! min-w-0!">
            <div
              class="w-12! h-12! rounded-xl! bg-white! border! border-slate-100! overflow-hidden! shrink-0! flex! items-center! justify-center!"
            >
              <font-awesome-icon
                v-if="!form.preview"
                icon="file-image"
                class="text-slate-300! text-lg!"
              />
              <img v-else :src="form.preview" class="w-full! h-full! object-contain!" />
            </div>
            <div class="min-w-0! flex-1!">
              <p class="text-[11px]! font-bold! text-slate-700! truncate!">
                {{ form.file?.name || form.previewName || 'Logo saat ini' }}
              </p>
              <p class="text-[10px]! text-slate-400! mt-0.5!">
                <template v-if="form.file">
                  {{ (form.file.size / 1024).toFixed(1) }} KB • Siap diunggah
                </template>
                <template v-else-if="form.previewName">
                  Tersimpan di server
                </template>
              </p>
            </div>
          </div>

          <div v-else class="flex! flex-col! gap-1.5!">
            <div class="flex! items-center! gap-2!">
              <font-awesome-icon
                icon="circle-info"
                class="text-slate-400! text-xs!"
              />
              <span class="text-[10px]! font-bold! text-slate-500! uppercase! tracking-wider!"
                >Belum ada file</span
              >
            </div>
            <p class="text-[10px]! text-slate-400! leading-relaxed!">
              Pilih file gambar dari perangkat Anda. Logo akan ditampilkan di laporan, tagihan,
              dan dashboard.
            </p>
          </div>

          <div class="flex! items-center! justify-end! gap-2! mt-auto!">
            <button
              v-if="form.preview"
              type="button"
              @click="clearFile"
              class="flex! items-center! gap-1.5! px-3! py-2! bg-white! border! border-rose-200! text-rose-500! hover:bg-rose-50! rounded-lg! text-[10px]! font-black! uppercase! tracking-widest! transition-colors!"
            >
              <font-awesome-icon icon="trash" class="text-[10px]!" />
              Hapus
            </button>
          </div>
        </div>
      </div>

      <input
        type="file"
        ref="fileInput"
        class="hidden"
        accept="image/png,image/jpeg,image/webp"
        @change="onUpload"
      />
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
        Simpan Logo
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'

const form = defineModel({ required: true })

defineProps({
  onSave: {
    type: Function,
    required: true,
  },
})

const fileInput = ref(null)

const triggerUpload = () => {
  fileInput.value?.click()
}

const onUpload = (event) => {
  const file = event.target.files?.[0]
  if (!file) return
  form.value.file = file
  const reader = new FileReader()
  reader.onload = (e) => {
    form.value.preview = e.target.result
  }
  reader.readAsDataURL(file)
  event.target.value = ''
}

const clearFile = () => {
  form.value.file = null
  form.value.preview = ''
}
</script>
