<template>
  <div class="pricing-config-view w-full! max-w-5xl! mx-auto! pb-20! px-4! sm:px-0!">
    <div class="mb-6! sm:mb-8! flex! items-center! gap-3! sm:gap-4!">
      <BaseButton
        variant="ghost"
        icon="arrow-left"
        @click="handleBack"
        class="w-10! h-10! sm:w-12! sm:h-12! p-0! rounded-full! border! border-slate-200! bg-white! hover:bg-slate-50! hover:border-slate-300! text-slate-600! shadow-sm! shrink-0!"
      />
      <div>
        <h1 class="text-xl! sm:text-3xl! font-extrabold text-slate-900! tracking-tight mb-0.5!">
          Ubah Paket & Tarif
        </h1>
        <p class="text-xs! sm:text-sm! font-medium text-slate-500! leading-relaxed">
          Sesuaikan detail biaya pasang dan skema blok tarif air.
        </p>
      </div>
    </div>

    <ContentCard
      variant="elevated"
      padding="none"
      class="mb-6! border-0! shadow-xl! shadow-slate-200/40! overflow-visible! bg-white! rounded-2xl! sm:rounded-3xl!"
    >
      <div class="p-5! sm:p-10!">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3! mb-6! sm:mb-8!">
          <div class="flex items-center gap-3!">
            <div class="w-1.5! h-6! bg-blue-600! rounded-full!"></div>
            <h2 class="text-lg! sm:text-xl! font-bold! text-slate-800!">Konfigurasi Blok Tarif</h2>
          </div>
          <div class="flex items-center gap-2!">
            <span
              class="px-2! py-1! bg-blue-50! text-blue-700! text-[9px]! sm:text-[10px]! font-black! rounded-lg! uppercase! tracking-widest!"
              >UNIT: M3</span
            >
            <span
              class="px-2! py-1! bg-amber-50! text-amber-700! text-[9px]! sm:text-[10px]! font-black! rounded-lg! uppercase! tracking-widest!"
              >IDR</span
            >
          </div>
        </div>

        <div class="mb-6!">
          <BaseInput
            v-model="namaKelas"
            label="Nama Kelas / Kategori"
            placeholder="Contoh: Rumah Tangga A1"
          />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4! sm:gap-6! mb-8! pb-8! border-b! border-slate-100!">
          <MaksMoneyInput v-model="installationFee" label="Biaya Pasang Baru" placeholder="0" />
          <MaksMoneyInput v-model="abodemen" label="Biaya Abodemen" placeholder="0" />
          <MaksMoneyInput v-model="denda" label="Denda Keterlambatan" placeholder="0" />
        </div>

        <div
          class="hidden sm:grid grid-cols-[1.5fr_2fr_2fr_80px] gap-4! px-6! mb-4! text-[11px]! font-bold! text-slate-400! uppercase! tracking-widest!"
        >
          <div>URUTAN BLOK</div>
          <div class="text-center!">RENTANG VOLUME (M3)</div>
          <div class="text-center!">HARGA PER M3</div>
          <div class="text-center!">AKSI</div>
        </div>

        <div class="space-y-4! sm:space-y-3!">
          <div
            v-for="(block, index) in blocks"
            :key="index"
            class="group relative! flex flex-col sm:grid sm:grid-cols-[1.5fr_2fr_2fr_80px] gap-4! sm:items-center! py-5! px-5! sm:py-4! sm:px-6! rounded-2xl! bg-slate-50/50! border! border-slate-100! sm:border-transparent! hover:border-blue-100! hover:bg-white! hover:shadow-lg! hover:shadow-blue-500/5! transition-all! duration-300!"
          >
            <div class="flex items-center justify-between w-full sm:w-auto!">
              <div class="flex items-center gap-3! sm:gap-4!">
                <div
                  class="w-9! h-9! sm:w-10! sm:h-10! rounded-xl! bg-blue-600! text-white! flex! items-center! justify-center! text-xs! font-black! shrink-0! shadow-lg! shadow-blue-200!"
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

              <BaseButton
                v-if="blocks.length > 1"
                variant="ghost"
                size="sm"
                icon="trash"
                @click="removeBlock(index)"
                class="sm:hidden! w-8! h-8! p-0! rounded-lg! border! border-red-100! bg-red-50/50! text-red-500!"
              />
            </div>

            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2! sm:gap-3! justify-center!">
              <span class="sm:hidden! text-[10px]! font-bold! text-slate-400! uppercase! tracking-wider!">Rentang Volume (m³)</span>
              <div class="flex items-center gap-3! w-full sm:w-auto!">
                <div class="relative! w-full sm:w-24!">
                  <input
                    type="number"
                    v-model="block.from"
                    class="w-full! text-center! py-2.5! bg-slate-50! border! border-slate-200! rounded-xl! text-sm! font-bold! text-slate-400! cursor-not-allowed! outline-none!"
                    disabled
                  />
                  <span class="absolute! -top-2! left-3! bg-white! px-1! text-[8px]! font-bold! text-slate-400! uppercase!">Dari</span>
                </div>
                
                <span class="text-slate-300! font-bold!">to</span>
                
                <div class="relative! w-full sm:w-32!">
                  <input
                    type="number"
                    step="0.01"
                    v-model="block.to"
                    @input="updateNextBlockFrom(index)"
                    :placeholder="index === blocks.length - 1 ? '∞' : '0'"
                    class="w-full! text-center! py-2.5! bg-white! border! border-slate-200! rounded-xl! text-sm! font-bold! text-slate-700! focus:outline-none! focus:border-blue-500! focus:ring-4! focus:ring-blue-500/5! transition-all!"
                  />
                  <span class="absolute! -top-2! left-3! bg-white! px-1! text-[8px]! font-bold! text-slate-400! uppercase!">Hingga</span>
                  <div 
                    v-if="index === blocks.length - 1 && !block.to"
                    class="absolute! right-3! top-1/2! -translate-y-1/2! text-[10px]! font-bold! text-blue-500! uppercase! pointer-events-none!"
                  >
                    ∞ Bebas
                  </div>
                </div>
              </div>
            </div>

            <div class="sm:px-2! w-full!">
               <span class="sm:hidden! block! mb-1.5! text-[10px]! font-bold! text-slate-400! uppercase! tracking-wider!">Harga per m³</span>
              <MaksMoneyInput v-model="block.price" placeholder="0" no-margin />
            </div>

            <div class="hidden sm:flex justify-center!">
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

        <div class="mt-8! flex! justify-center!">
          <button
            @click="addBlock"
            class="flex! items-center! gap-3! px-6! py-3! bg-white! border-2! border-dashed! border-slate-200! rounded-2xl! text-slate-500! hover:border-blue-400! hover:text-blue-600! hover:bg-blue-50/30! transition-all! group!"
          >
            <div
              class="w-8! h-8! rounded-full! bg-slate-100! text-slate-400! flex! items-center! justify-center! group-hover:bg-blue-100! group-hover:text-blue-600! transition-all!"
            >
              <font-awesome-icon icon="circle-plus" class="text-xs!" />
            </div>
            <span class="text-sm! font-bold!">Tambah Blok Baru</span>
          </button>
        </div>
      </div>
    </ContentCard>

    <div class="mt-8! sm:mt-10! flex items-center justify-end!">
      <BaseButton
        variant="secondary"
        size="md"
        @click="handleSave"
        :loading="isSaving"
        class="w-full! sm:w-auto! sm:px-12! py-4! font-bold! rounded-2xl! shadow-xl! shadow-slate-200! transform! transition-all! hover:translate-y-[-2px]! active:scale-95!"
        icon="save"
      >
        Simpan Perubahan
      </BaseButton>
    </div>

    <!-- Loading Overlay -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="isSaving" class="fixed! inset-0! z-[9999]! flex! items-center! justify-center! bg-slate-900/60! backdrop-blur-sm! pointer-events-auto!">
          <div class="bg-white! p-8! rounded-3xl! shadow-2xl! flex! flex-col! items-center! gap-4! max-w-xs! w-full! animate-in! zoom-in! duration-300!">
            <div class="relative! w-16! h-16!">
              <div class="absolute! inset-0! border-4! border-blue-100! rounded-full!"></div>
              <div class="absolute! inset-0! border-4! border-blue-600! border-t-transparent! rounded-full! animate-spin!"></div>
            </div>
            <div class="text-center!">
              <h3 class="text-lg! font-black! text-slate-900! mb-1!">Memperbarui Data</h3>
              <p class="text-sm! font-medium! text-slate-500! leading-relaxed!">
                {{ savingStatus }}
              </p>
            </div>
            <div class="w-full! bg-slate-100! h-1.5! rounded-full! overflow-hidden! mt-2!">
              <div 
                class="h-full! bg-blue-600! transition-all! duration-500!" 
                :style="{ width: saveProgress + '%' }"
              ></div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseInput from '@/presentations/components/ui/BaseInput.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import MaksMoneyInput from '@/presentations/components/MaksMoneyInput.vue'
import packageService from '@/services/package.service'
import Swal from 'sweetalert2'

const router = useRouter()
const route = useRoute()

const isSaving = ref(false)
const savingStatus = ref('')
const saveProgress = ref(0)
const namaKelas = ref('')
const blockNames = ['Pertama', 'Kedua', 'Ketiga', 'Keempat', 'Kelima']
const abodemen = ref(0)
const denda = ref(0)
const installationFee = ref(0)
const blocks = ref([])
const deletedBlockIds = ref([])

const fetchKelasData = async (id) => {
  try {
    const res = await packageService.getPackageDetail(id)
    if (res.success) {
      const pkg = res.data
      namaKelas.value = pkg.name
      abodemen.value = pkg.monthly_abodemen
      denda.value = pkg.late_penalty
      installationFee.value = pkg.installation_fee
      blocks.value = pkg.water_tariff_blocks.map((b) => ({
        id: b.id,
        from: Number(b.usage_min_m3),
        to: b.usage_max_m3 ? Number(b.usage_max_m3) : null,
        price: Number(b.price_per_m3),
      }))
    }
  } catch (error) {
    Swal.fire('Error', 'Gagal mengambil data paket', 'error')
  }
}

const addBlock = () => {
  const lastBlock = blocks.value[blocks.value.length - 1]
  const nextFrom = lastBlock ? parseInt(lastBlock.to || 0) + 1 : 0
  blocks.value.push({
    from: nextFrom,
    to: nextFrom + 9,
    price: lastBlock ? lastBlock.price + 500 : 1000,
  })
}

const updateNextBlockFrom = (index) => {
  if (index < blocks.value.length - 1) {
    const currentTo = Number(blocks.value[index].to)
    
    if (isNaN(currentTo)) {
      blocks.value[index + 1].from = null 
      return
    }

    const increment = currentTo % 1 === 0 ? 1 : 0.01
    blocks.value[index + 1].from = Number((currentTo + increment).toFixed(2))
    
    if (blocks.value[index + 1].to && Number(blocks.value[index + 1].to) <= Number(blocks.value[index + 1].from)) {
      blocks.value[index + 1].to = Number((Number(blocks.value[index + 1].from) + 10).toFixed(2))
    }
    updateNextBlockFrom(index + 1)
  }
}

const removeBlock = (index) => {
  if (blocks.value.length > 1) {
    const block = blocks.value[index]
    if (block.id) {
      deletedBlockIds.value.push(block.id)
    }
    blocks.value.splice(index, 1)
    adjustBlocksAfterDelete()
  }
}

const adjustBlocksAfterDelete = () => {
  for (let i = 0; i < blocks.value.length; i++) {
    if (i === 0) {
      blocks.value[i].from = 0
    } else {
      const prevTo = Number(blocks.value[i - 1].to)
      if (isNaN(prevTo)) {
        blocks.value[i].from = null
      } else {
        const increment = prevTo % 1 === 0 ? 1 : 0.01
        blocks.value[i].from = Number((prevTo + increment).toFixed(2))
      }
    }
    
    if (blocks.value[i].to && blocks.value[i].from && Number(blocks.value[i].to) <= Number(blocks.value[i].from)) {
      blocks.value[i].to = Number((Number(blocks.value[i].from) + 10).toFixed(2))
    }
  }
}

const handleBack = () => {
  router.back()
}

const handleSave = async () => {
  if (!namaKelas.value) {
    return Swal.fire('Peringatan', 'Nama kelas harus diisi', 'warning')
  }

  // Validasi blok
  for (let i = 0; i < blocks.value.length; i++) {
    const block = blocks.value[i]
    
    if (block.price === null || block.price === undefined || block.price < 0) {
      return Swal.fire('Peringatan', `Harga pada Blok ${i + 1} harus diisi`, 'warning')
    }

    if (i < blocks.value.length - 1) {
      if (!block.to) {
        return Swal.fire('Peringatan', `Batas atas pada Blok ${i + 1} harus diisi. Hanya blok terakhir yang boleh kosong (tak terbatas).`, 'warning')
      }
      if (Number(block.to) <= Number(block.from)) {
        return Swal.fire('Peringatan', `Batas atas pada Blok ${i + 1} harus lebih besar dari batas bawah (${block.from})`, 'warning')
      }
    } else {
      // Blok terakhir
      if (block.to && Number(block.to) <= Number(block.from)) {
        return Swal.fire('Peringatan', `Batas atas pada Blok ${i + 1} harus lebih besar dari batas bawah (${block.from})`, 'warning')
      }
    }
  }

  isSaving.value = true
  savingStatus.value = 'Mempersiapkan data...'
  saveProgress.value = 5

  try {
    const packageId = route.params.id

    savingStatus.value = 'Memperbarui data paket...'
    saveProgress.value = 15
    await packageService.updatePackage(packageId, {
      name: namaKelas.value,
      installation_fee: Number(installationFee.value),
      monthly_abodemen: Number(abodemen.value),
      late_penalty: Number(denda.value),
    })

    const totalDeletions = deletedBlockIds.value.length
    if (totalDeletions > 0) {
      for (let i = 0; i < totalDeletions; i++) {
        const id = deletedBlockIds.value[i]
        savingStatus.value = `Menghapus blok lama (${i + 1}/${totalDeletions})...`
        saveProgress.value = 15 + ((i + 1) / totalDeletions) * 20
        await packageService.deleteTariffBlock(packageId, id)
      }
    }

    const totalBlocks = blocks.value.length
    for (let i = 0; i < totalBlocks; i++) {
      const block = blocks.value[i]
      savingStatus.value = `Menyimpan blok tarif ${i + 1} dari ${totalBlocks}...`
      saveProgress.value = 35 + ((i + 1) / totalBlocks) * 60

      const blockPayload = {
        usage_min_m3: Number(block.from || 0),
        usage_max_m3: block.to ? Number(block.to) : null,
        price_per_m3: Number(block.price),
      }

      if (block.id) {
        await packageService.updateTariffBlock(packageId, block.id, blockPayload)
      } else {
        await packageService.createTariffBlock(packageId, blockPayload)
      }
    }

    saveProgress.value = 100
    savingStatus.value = 'Selesai!'
    isSaving.value = false

    await Swal.fire({
      title: 'Berhasil!',
      text: 'Perubahan paket & tarif layanan telah disimpan.',
      icon: 'success',
      confirmButtonText: 'Selesai',
      confirmButtonColor: '#3b82f6',
    })

    router.push('/kelas-biaya')
  } catch (error) {
    Swal.fire('Error', 'Gagal menyimpan perubahan: ' + (error.response?.data?.message || error.message), 'error')
  } finally {
    isSaving.value = false
    deletedBlockIds.value = []
  }
}

onMounted(() => {
  fetchKelasData(route.params.id)
  document.body.style.overflowX = 'hidden'
})

onUnmounted(() => {
  document.body.style.overflowX = 'auto'
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
