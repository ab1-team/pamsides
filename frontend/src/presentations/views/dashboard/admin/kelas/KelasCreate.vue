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
          Tentukan Harga Paket
        </h1>
        <p class="text-sm font-medium text-slate-500! leading-relaxed">
          Konfigurasikan tarif bertingkat berdasarkan blok volume penggunaan air.
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
          class="grid grid-cols-[1.5fr_2fr_2fr_80px] gap-4! px-6! mb-4! text-[11px]! font-bold! text-slate-400! uppercase! tracking-widest!"
        >
          <div>URUTAN BLOK</div>
          <div class="text-center!">RENTANG VOLUME (M3)</div>
          <div class="text-center!">HARGA PER M3</div>
          <div class="text-center!">AKSI</div>
        </div>

        <!-- Table Rows -->
        <div class="space-y-3!">
          <div
            v-for="(block, index) in blocks"
            :key="index"
            class="group grid grid-cols-[1.5fr_2fr_2fr_80px] gap-4! items-center! py-4! px-6! rounded-2xl! bg-slate-50/50! border! border-transparent! hover:border-blue-100! hover:bg-white! hover:shadow-lg! hover:shadow-blue-500/5! transition-all! duration-300!"
          >
            <div class="flex items-center gap-4!">
              <div
                class="w-10! h-10! rounded-xl! bg-blue-600! text-white! flex! items-center! justify-center! text-xs! font-black! shrink-0! shadow-lg! shadow-blue-200!"
              >
                {{ (index + 1).toString().padStart(2, '0') }}
              </div>
              <span class="text-sm! font-bold! text-slate-700!"
                >Blok {{ blockNames[index] || 'Lanjutan' }}</span
              >
            </div>

            <div class="flex items-center gap-3! justify-center!">
              <input
                type="number"
                v-model="block.from"
                class="w-20! text-center! py-2.5! bg-white! border! border-slate-200! rounded-xl! text-sm! font-bold! text-slate-700! focus:outline-none! focus:border-blue-500! focus:ring-4! focus:ring-blue-500/5! transition-all!"
                :disabled="index === 0"
              />
              <span class="text-slate-300! font-bold!">to</span>
              <input
                type="number"
                v-model="block.to"
                class="w-20! text-center! py-2.5! bg-white! border! border-slate-200! rounded-xl! text-sm! font-bold! text-slate-700! focus:outline-none! focus:border-blue-500! focus:ring-4! focus:ring-blue-500/5! transition-all!"
              />
            </div>

            <div class="px-2!">
              <MaksMoneyInput v-model="block.price" placeholder="0" no-margin size="md" />
            </div>

            <div class="flex justify-center!">
              <BaseButton
                v-if="blocks.length > 1"
                variant="ghost"
                size="sm"
                icon="trash"
                @click="removeBlock(index)"
                class="w-8! h-8! p-0! rounded-lg! border! border-slate-100! hover:border-red-200! hover:bg-red-50! text-slate-600! hover:text-red-600! shadow-sm!"
                title="Hapus Blok"
              />
            </div>
          </div>
        </div>

        <!-- Add Block Button -->
        <div class="mt-8! flex! justify-center!">
          <button
            @click="addBlock"
            class="flex! items-center! gap-3! px-6! py-3! bg-white! border-2! border-dashed! border-slate-200! rounded-2xl! text-slate-500! hover:border-blue-400! hover:text-blue-600! hover:bg-blue-50/30! transition-all! group!"
          >
            <div
              class="w-8! h-8! rounded-full! bg-slate-100! text-slate-400! flex! items-center! justify-center! group-hover:bg-blue-100! group-hover:text-blue-600! transition-all!"
            >
              <font-awesome-icon icon="plus" class="text-xs!" />
            </div>
            <span class="text-sm! font-bold!">Tambah Blok Baru</span>
          </button>
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
        Simpan Kelas Biaya
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseInput from '@/presentations/components/ui/BaseInput.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import MaksMoneyInput from '@/presentations/components/MaksMoneyInput.vue'
import packageService from '@/services/package.service'
import Swal from 'sweetalert2'

const router = useRouter()

const namaKelas = ref('')
const blockNames = ['Pertama', 'Kedua', 'Ketiga', 'Keempat', 'Kelima']
const abodemen = ref(0)
const denda = ref(0)
const installationFee = ref(1500000) // Default installation fee
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
  if (!namaKelas.value) {
    Swal.fire('Error', 'Nama Kelas harus diisi!', 'error')
    return
  }

  try {
    Swal.fire({
      title: 'Menyimpan...',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading()
      },
    })

    // 1. Create Package
    const packageRes = await packageService.createPackage({
      name: namaKelas.value,
      installation_fee: installationFee.value,
      monthly_abodemen: abodemen.value,
      late_penalty: denda.value,
    })

    if (packageRes.success) {
      const packageId = packageRes.data.id

      // 2. Create Blocks
      for (const block of blocks.value) {
        await packageService.createTariffBlock(packageId, {
          usage_min_m3: block.from,
          usage_max_m3: block.to,
          price_per_m3: block.price,
        })
      }

      await Swal.fire({
        title: 'Berhasil!',
        text: 'Konfigurasi harga paket telah disimpan.',
        icon: 'success',
        confirmButtonText: 'Selesai',
        confirmButtonColor: '#3b82f6',
      })

      router.push('/kelas-biaya')
    }
  } catch (error) {
    Swal.fire('Error', 'Gagal menyimpan konfigurasi: ' + (error.response?.data?.message || error.message), 'error')
  }
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
