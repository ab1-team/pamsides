<template>
  <div class="pricing-config-view w-full! max-w-5xl! mx-auto! pb-20!">
    <div class="mb-8!">
      <div>
        <h1 class="text-3xl font-extrabold text-slate-900! tracking-tight mb-2!">
          Tentukan Harga Paket
        </h1>
        <p class="text-sm font-medium text-slate-500! leading-relaxed">
          Konfigurasikan tarif bertingkat berdasarkan blok volume penggunaan air untuk setiap
          kategori kelas.
        </p>
      </div>
    </div>

    <ContentCard
      variant="elevated"
      padding="none"
      class="mb-6! border-0! shadow-xl! shadow-slate-200/40! overflow-visible!"
    >
      <div class="p-6! sm:p-8!">
        <div class="flex items-center justify-between mb-8!">
          <div class="flex items-center gap-3!">
            <div class="w-1.5! h-6! bg-sky-600! rounded-full!"></div>
            <h2 class="text-lg! font-bold! text-slate-800!">Konfigurasi Blok Tarif</h2>
          </div>
          <div class="flex items-center gap-2!">
            <span
              class="px-2! py-1! bg-sky-50! text-sky-700! text-[9px]! font-black! rounded-md! uppercase! tracking-widest!"
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
          class="grid grid-cols-1 md:grid-cols-2 gap-6! mb-2! pb-8! border-b! border-slate-100/50!"
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
          class="grid grid-cols-[1fr_2fr_2fr_auto] gap-4! px-4! mb-4! text-[10px]! font-black! text-slate-300! uppercase! tracking-widest!"
        >
          <div>URUTAN BLOK</div>
          <div class="text-center!">RENTANG VOLUME (M3)</div>
          <div class="text-center!">HARGA PER M3</div>
          <div class="w-10!">AKSI</div>
        </div>

        <div class="space-y-4!">
          <div
            v-for="(block, index) in blocks"
            :key="index"
            class="group grid grid-cols-[1fr_2fr_2fr_auto] gap-4! items-center! py-3! px-4! rounded-2xl! transition-all! duration-300 hover:bg-slate-50/80!"
          >
            <div class="flex items-center gap-3!">
              <div
                class="w-8! h-8! rounded-xl! bg-blue-50! text-blue-600! flex! items-center! justify-center! text-[11px]! font-black! shrink-0!"
              >
                {{ (index + 1).toString().padStart(2, '0') }}
              </div>
              <span class="text-sm! font-bold! text-slate-700!"
                >Blok {{ blockNames[index] || 'Tambahan' }}</span
              >
            </div>

            <div class="flex items-center gap-3! justify-center!">
              <div class="w-16!">
                <input
                  type="number"
                  v-model="block.from"
                  class="w-full! text-center! py-2! bg-slate-50! border! border-slate-200! rounded-xl! text-sm! font-bold! text-slate-700! focus:outline-none! focus:bg-white! focus:border-blue-500! focus:ring-4! focus:ring-blue-500/5! transition-all!"
                  :disabled="index === 0"
                />
              </div>
              <span class="text-slate-300! font-bold!">——</span>
              <div class="w-16!">
                <input
                  type="number"
                  v-model="block.to"
                  class="w-full! text-center! py-2! bg-slate-50! border! border-slate-200! rounded-xl! text-sm! font-bold! text-slate-700! focus:outline-none! focus:bg-white! focus:border-blue-500! focus:ring-4! focus:ring-blue-500/5! transition-all!"
                />
              </div>
            </div>

            <div class="px-2!">
              <MaksMoneyInput v-model="block.price" placeholder="0" no-margin size="sm" />
            </div>

            <div class="flex justify-center!">
              <button
                @click="removeBlock(index)"
                v-if="blocks.length > 1"
                class="w-8! h-8! flex! items-center! justify-center! text-slate-200! hover:text-red-500! hover:bg-red-50! rounded-lg! transition-all!"
              >
                <font-awesome-icon icon="trash" class="text-sm!" />
              </button>
              <div v-else class="w-8!"></div>
            </div>
          </div>
        </div>

        <div class="mt-8! px-4!">
          <button
            @click="addBlock"
            class="flex! items-center! gap-2.5! text-sky-600! hover:text-sky-700! font-bold! text-xs! transition-all! group!"
          >
            <div
              class="w-6! h-6! rounded-full! bg-sky-100! flex! items-center! justify-center! group-hover:scale-110! transition-transform!"
            >
              <font-awesome-icon icon="plus" class="text-[10px]!" />
            </div>
            Tambah Blok Baru
            <span
              class="px-1.5! py-0.5! bg-slate-100! text-slate-400! text-[8px]! rounded-md! font-black! ml-1!"
              >ALT + N</span
            >
          </button>
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
        Simpan Kelas Baru
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import ContentCard from '@/components/ui/ContentCard.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import MaksMoneyInput from '@/components/MaksMoneyInput.vue'

import Swal from 'sweetalert2'

const router = useRouter()

const namaKelas = ref('')

const blockNames = ['Pertama', 'Kedua', 'Ketiga', 'Keempat', 'Kelima']
const abodemen = ref(0)
const denda = ref(0)
const blocks = ref([
  { from: 0, to: 10, price: 2500 },
  { from: 10, to: 20, price: 3750 },
  { from: 20, to: 100, price: 5000 },
])

const addBlock = () => {
  const lastBlock = blocks.value[blocks.value.length - 1]
  blocks.value.push({
    from: lastBlock ? lastBlock.to : 0,
    to: (lastBlock ? lastBlock.to : 0) + 10,
    price: lastBlock ? lastBlock.price + 500 : 1000,
  })
}

const removeBlock = (index) => {
  if (blocks.value.length > 1) {
    blocks.value.splice(index, 1)
    adjustBlocksAfterDelete()
  }
}

const adjustBlocksAfterDelete = () => {
  for (let i = 0; i < blocks.value.length; i++) {
    if (i === 0) {
      blocks.value[i].from = 0
    } else {
      blocks.value[i].from = blocks.value[i - 1].to
    }
    if (blocks.value[i].to <= blocks.value[i].from) {
      blocks.value[i].to = blocks.value[i].from + 10
    }
  }
}

const handleBack = () => {
  router.back()
}

const handleSave = async () => {
  console.log('Saving New Kelas:', {
    nama: namaKelas.value,
    blocks: blocks.value,
  })

  await Swal.fire({
    title: 'Berhasil Disimpan!',
    text: 'Kelas biaya baru telah berhasil ditambahkan.',
    icon: 'success',
    timer: 1500,
    showConfirmButton: false,
  })

  router.push('/kelas-biaya')
}

const handleKeydown = (e) => {
  if (e.altKey && e.key === 'n') {
    e.preventDefault()
    addBlock()
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
@reference "../../../assets/main.css";

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
