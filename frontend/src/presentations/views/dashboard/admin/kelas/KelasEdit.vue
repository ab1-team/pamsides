<template>
  <div class="pricing-config-view w-full! max-w-5xl! mx-auto! pb-20!">
    <div class="mb-8! flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-extrabold text-slate-900! tracking-tight mb-2!">
          Edit Harga Paket
        </h1>
        <p class="text-sm font-medium text-slate-500! leading-relaxed">
          Ubah detail tarif untuk kelas yang sudah ada. Struktur blok volume sudah ditentukan.
        </p>
      </div>
      <BaseButton
        variant="ghost"
        size="sm"
        @click="handleBack"
        icon="arrow-left"
        class="font-bold!"
      >
        Kembali
      </BaseButton>
    </div>

    <ContentCard
      variant="elevated"
      padding="none"
      class="mb-6! border-0! shadow-xl! shadow-slate-200/40! overflow-visible!"
    >
      <div class="p-6! sm:p-8!">
        <div class="flex items-center justify-between mb-8!">
          <div class="flex items-center gap-3!">
            <div class="w-1.5! h-6! bg-indigo-600! rounded-full!"></div>
            <h2 class="text-lg! font-bold! text-slate-800!">Konfigurasi Detail Kelas</h2>
          </div>
          <div class="flex items-center gap-2!">
            <span
              class="px-2! py-1! bg-slate-50! text-slate-500! text-[9px]! font-black! rounded-md! uppercase! tracking-widest!"
              >UNIT: M3</span
            >
            <span
              class="px-2! py-1! bg-orange-50! text-orange-700! text-[9px]! font-black! rounded-md! uppercase! tracking-widest!"
              >CURRENCY: IDR</span
            >
          </div>
        </div>

        <div class="mb-6!">
          <BaseInput
            v-model="namaKelas"
            label="Nama Kelas / Kategori"
            placeholder="Contoh: Rumah Tangga A1"
            icon="tag"
          />
        </div>

        <div
          class="grid grid-cols-1 md:grid-cols-2 gap-6! mb-10! pb-8! border-b! border-slate-100/50!"
        >
          <MaksMoneyInput
            v-model="abodemen"
            label="Biaya Abodemen / Beban"
            placeholder="0"
            helper-text="Biaya tetap bulanan yang dikenakan pada kelas ini."
            show-helper
          />
          <MaksMoneyInput
            v-model="denda"
            label="Biaya Denda Keterlambatan"
            placeholder="0"
            helper-text="Denda yang dikenakan jika pembayaran melewati jatuh tempo."
            show-helper
          />
        </div>

        <div
          class="grid grid-cols-[1.2fr_2fr_2fr] gap-6! px-4! mb-6! text-[10px]! font-black! text-slate-300! uppercase! tracking-widest!"
        >
          <div>URUTAN BLOK</div>
          <div class="text-center!">RENTANG VOLUME (M3)</div>
          <div class="text-center!">HARGA PER M3</div>
        </div>

        <div class="space-y-3!">
          <div
            v-for="(block, index) in blocks"
            :key="index"
            class="group grid grid-cols-[1.2fr_2fr_2fr] gap-6! items-center! py-4! px-5! rounded-2xl! border! border-transparent! hover:border-slate-100! hover:bg-slate-50/50! transition-all! duration-300"
          >
            <div class="flex items-center gap-4!">
              <div
                class="w-10! h-10! rounded-2xl! bg-white! border! border-slate-100! shadow-sm! text-indigo-600! flex! items-center! justify-center! text-xs! font-black! shrink-0!"
              >
                {{ (index + 1).toString().padStart(2, '0') }}
              </div>
              <div>
                <span class="text-sm! font-bold! text-slate-700! block! mb-0.5!"
                  >Blok {{ blockNames[index] || 'Tambahan' }}</span
                >
                <span class="text-[10px]! font-medium! text-slate-400! uppercase! tracking-tight!"
                  >Tarif Tier {{ index + 1 }}</span
                >
              </div>
            </div>

            <div class="flex items-center gap-4! justify-center!">
              <div class="w-20!">
                <input
                  type="number"
                  v-model="block.from"
                  class="w-full! text-center! py-2.5! bg-slate-50! border! border-slate-100! rounded-xl! text-sm! font-bold! text-slate-400! cursor-not-allowed! transition-all!"
                  disabled
                />
              </div>
              <div class="w-4! h-0.5! bg-slate-100! rounded-full!"></div>
              <div class="w-20!">
                <input
                  type="number"
                  v-model="block.to"
                  class="w-full! text-center! py-2.5! bg-slate-50! border! border-slate-100! rounded-xl! text-sm! font-bold! text-slate-400! cursor-not-allowed! transition-all!"
                  disabled
                />
              </div>
            </div>

            <div class="px-2!">
              <MaksMoneyInput v-model="block.price" placeholder="0" no-margin size="md" />
            </div>
          </div>
        </div>

        <div
          class="mt-10! p-4! bg-amber-50/50! border! border-amber-100/50! rounded-2xl! flex! items-start! gap-4!"
        >
          <div
            class="w-8! h-8! rounded-xl! bg-amber-100! flex! items-center! justify-center! shrink-0!"
          >
            <font-awesome-icon icon="circle-info" class="text-amber-600! text-sm!" />
          </div>
          <div class="flex-1!">
            <h4 class="text-xs! font-bold! text-amber-800! mb-1!">Struktur Blok Terkunci</h4>
            <p class="text-[11px]! font-medium! text-amber-700/80! leading-relaxed!">
              Untuk menjaga integritas data billing yang sudah berjalan, Anda hanya tidak dapat
              menambah atau menghapus blok pada mode Edit. Silakan ubah nilai rentang dan harga pada
              blok yang tersedia.
            </p>
          </div>
        </div>
      </div>
    </ContentCard>

    <div class="mt-10! flex items-center justify-end gap-4!">
      <BaseButton
        variant="secondary"
        size="md"
        @click="handleSave"
        class="px-10! py-3! font-bold! rounded-2xl! shadow-lg! shadow-slate-200/60! transform! transition-all! hover:translate-y-[-2px]! active:scale-95!"
        icon="save"
      >
        Simpan Perubahan
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseInput from '@/presentations/components/ui/BaseInput.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import MaksMoneyInput from '@/presentations/components/MaksMoneyInput.vue'

import Swal from 'sweetalert2'

const router = useRouter()
const route = useRoute()

const namaKelas = ref('')

const blockNames = ['Pertama', 'Kedua', 'Ketiga', 'Keempat', 'Kelima']
const abodemen = ref(0)
const denda = ref(0)
const blocks = ref([])

const fetchKelasData = (id) => {
  console.log(`Fetching data for ID: ${id}`)
  const mockData = {
    1: {
      nama: 'Rumah Tangga A',
      abodemen: 7500,
      denda: 15000,
      blocks: [
        { from: 0, to: 10, price: 2500 },
        { from: 10, to: 20, price: 3500 },
        { from: 20, to: 50, price: 4500 },
      ],
    },
    2: {
      nama: 'Rumah Tangga B',
      abodemen: 10000,
      denda: 20000,
      blocks: [
        { from: 0, to: 15, price: 3500 },
        { from: 15, to: 30, price: 4500 },
        { from: 30, to: 100, price: 6000 },
      ],
    },
  }

  const data = mockData[id] || mockData[1]
  namaKelas.value = data.nama
  abodemen.value = data.abodemen
  denda.value = data.denda
  blocks.value = data.blocks
}

const handleBack = () => {
  router.back()
}

const handleSave = async () => {
  console.log('Saving Edit:', {
    id: route.params.id,
    nama: namaKelas.value,
    blocks: blocks.value,
  })

  await Swal.fire({
    title: 'Berhasil Diperbarui!',
    text: 'Perubahan pada kelas biaya telah disimpan.',
    icon: 'success',
    timer: 1500,
    showConfirmButton: false,
  })

  router.push('/kelas-biaya')
}

onMounted(() => {
  fetchKelasData(route.params.id)
})
</script>

<style scoped>
@reference "@/assets/css/main.css";

.pricing-config-view {
  animation: fadeIn 0.5s ease-out;
}

input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

input[type='number'] {
  -moz-appearance: textfield;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  @apply bg-transparent;
}

::-webkit-scrollbar-thumb {
  @apply bg-slate-200 rounded-full;
}

::-webkit-scrollbar-thumb:hover {
  @apply bg-slate-300;
}
</style>
