<template>
  <div class="pricing-config-view w-full! max-w-5xl! mx-auto! pb-20!">
    <div class="mb-8! flex! items-center! gap-4!">
      <BaseButton
        variant="ghost"
        icon="arrow-left"
        @click="handleBack"
        class="w-12! h-12! p-0! rounded-full! border! border-slate-200! bg-white! hover:bg-slate-50! hover:border-slate-300! text-slate-600! shadow-sm! shrink-0!"
      />
      <div>
        <h1 class="text-3xl font-extrabold text-slate-900! tracking-tight mb-0.5!">
          Edit Harga Paket
        </h1>
        <p class="text-sm font-medium text-slate-500! leading-relaxed">
          Ubah detail tarif untuk kelas yang sudah ada.
        </p>
      </div>
    </div>

    <ContentCard
      variant="elevated"
      padding="none"
      class="mb-6! border-0! shadow-xl! shadow-slate-200/40! overflow-visible! bg-white! rounded-3xl!"
    >
      <div class="p-6! sm:p-10!">
        <div class="flex items-center justify-between mb-8!">
          <div class="flex items-center gap-3!">
            <div class="w-1.5! h-6! bg-blue-600! rounded-full!"></div>
            <h2 class="text-xl! font-bold! text-slate-800!">Konfigurasi Blok Tarif</h2>
          </div>
          <div class="flex items-center gap-2!">
            <span
              class="px-3! py-1! bg-blue-50! text-blue-700! text-[10px]! font-black! rounded-lg! uppercase! tracking-widest!"
              >UNIT: M3</span
            >
            <span
              class="px-3! py-1! bg-amber-50! text-amber-700! text-[10px]! font-black! rounded-lg! uppercase! tracking-widest!"
              >IDR</span
            >
          </div>
        </div>

        <!-- Reduced margin here -->
        <div class="mb-6!">
          <BaseInput
            v-model="namaKelas"
            label="Nama Kelas / Kategori"
            placeholder="Contoh: Rumah Tangga A1"
          />
        </div>

        <!-- Reduced gap and margin here -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6! mb-8! pb-8! border-b! border-slate-100!">
          <MaksMoneyInput v-model="abodemen" label="Biaya Abodemen" placeholder="0" />
          <MaksMoneyInput v-model="denda" label="Biaya Denda" placeholder="0" />
        </div>

        <!-- Table Header -->
        <div
          class="grid grid-cols-[1.5fr_2fr_2fr] gap-4! px-6! mb-4! text-[11px]! font-bold! text-slate-400! uppercase! tracking-widest!"
        >
          <div>URUTAN BLOK</div>
          <div class="text-center!">RENTANG VOLUME (M3)</div>
          <div class="text-center!">HARGA PER M3</div>
        </div>

        <!-- Table Rows -->
        <div class="space-y-3!">
          <div
            v-for="(block, index) in blocks"
            :key="index"
            class="group grid grid-cols-[1.5fr_2fr_2fr] gap-4! items-center! py-4! px-6! rounded-2xl! bg-slate-50/50! border! border-transparent! hover:border-blue-100! hover:bg-white! hover:shadow-lg! hover:shadow-blue-500/5! transition-all! duration-300!"
          >
            <div class="flex items-center gap-4!">
              <div
                class="w-10! h-10! rounded-xl! bg-blue-600! text-white! flex! items-center! justify-center! text-xs! font-black! shrink-0! shadow-lg! shadow-blue-200!"
              >
                {{ (index + 1).toString().padStart(2, '0') }}
              </div>
              <div class="flex flex-col!">
                <span class="text-sm! font-bold! text-slate-700!"
                  >Blok {{ blockNames[index] || 'Lanjutan' }}</span
                >
                <span class="text-[10px]! font-medium! text-slate-400! uppercase! tracking-tight!"
                  >Tarif Tier {{ index + 1 }}</span
                >
              </div>
            </div>

            <div class="flex items-center gap-3! justify-center!">
              <input
                type="number"
                v-model="block.from"
                class="w-20! text-center! py-2.5! bg-slate-100! border! border-slate-200! rounded-xl! text-sm! font-bold! text-slate-400! cursor-not-allowed! transition-all!"
                disabled
              />
              <span class="text-slate-300! font-bold!">to</span>
              <input
                type="number"
                v-model="block.to"
                class="w-20! text-center! py-2.5! bg-slate-100! border! border-slate-200! rounded-xl! text-sm! font-bold! text-slate-400! cursor-not-allowed! transition-all!"
                disabled
              />
            </div>

            <div class="px-2!">
              <MaksMoneyInput v-model="block.price" placeholder="0" no-margin size="md" />
            </div>
          </div>
        </div>

        <!-- Warning Note -->
        <div
          class="mt-8! p-4! bg-amber-50! border! border-amber-100! rounded-2xl! flex! items-start! gap-4!"
        >
          <div
            class="w-8! h-8! rounded-xl! bg-amber-100! flex! items-center! justify-center! shrink-0!"
          >
            <font-awesome-icon icon="circle-info" class="text-amber-600! text-sm!" />
          </div>
          <div class="flex-1!">
            <h4 class="text-xs! font-bold! text-amber-800! mb-1!">Struktur Blok Terkunci</h4>
            <p class="text-[11px]! font-medium! text-amber-700/80! leading-relaxed!">
              Untuk menjaga integritas data billing, Anda tidak dapat menambah atau menghapus blok
              pada mode Edit. Silakan ubah harga pada blok yang tersedia.
            </p>
          </div>
        </div>
      </div>
    </ContentCard>

    <div class="mt-10! flex items-center justify-end! gap-4!">
      <BaseButton
        variant="secondary"
        size="md"
        @click="handleSave"
        class="px-12! py-4! font-bold! rounded-2xl! shadow-xl! shadow-slate-200! transform! transition-all! hover:translate-y-[-2px]! active:scale-95!"
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
  await Swal.fire({
    title: 'Berhasil!',
    text: 'Perubahan kelas biaya telah disimpan.',
    icon: 'success',
    confirmButtonText: 'Selesai',
    confirmButtonColor: '#3b82f6',
  })

  router.push('/kelas-biaya')
}

onMounted(() => {
  fetchKelasData(route.params.id)
})
</script>

<style scoped>
.pricing-config-view {
  animation: fadeIn 0.5s ease-out;
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

input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

input[type='number'] {
  -moz-appearance: textfield;
}
</style>
