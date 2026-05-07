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
                <input
                  type="number"
                  v-model="block.from"
                  class="w-full sm:w-20! text-center! py-2.5! bg-white! border! border-slate-200! rounded-xl! text-sm! font-bold! text-slate-400! cursor-not-allowed! outline-none!"
                  disabled
                />
                <span class="text-slate-300! font-bold!">to</span>
                <input
                  type="number"
                  v-model="block.to"
                  @input="updateNextBlockFrom(index)"
                  class="w-full sm:w-20! text-center! py-2.5! bg-white! border! border-slate-200! rounded-xl! text-sm! font-bold! text-slate-700! focus:outline-none! focus:border-blue-500! focus:ring-4! focus:ring-blue-500/5! transition-all!"
                />
              </div>
            </div>

            <div class="sm:px-2! w-full!">
               <span class="sm:hidden! block! mb-1.5! text-[10px]! font-bold! text-slate-400! uppercase! tracking-wider!">Harga per m³</span>
              <MaksMoneyInput v-model="block.price" placeholder="0" no-margin size="md" />
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
        from: b.usage_min_m3,
        to: b.usage_max_m3,
        price: b.price_per_m3,
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
    const currentTo = parseInt(blocks.value[index].to || 0)
    blocks.value[index + 1].from = currentTo + 1
    if (blocks.value[index + 1].to <= blocks.value[index + 1].from) {
      blocks.value[index + 1].to = blocks.value[index + 1].from + 10
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
      blocks.value[i].from = parseInt(blocks.value[i - 1].to || 0) + 1
    }
    if (blocks.value[i].to <= blocks.value[i].from) {
      blocks.value[i].to = blocks.value[i].from + 9
    }
  }
}

const handleBack = () => {
  router.back()
}

const handleSave = async () => {
  isSaving.value = true
  try {
    const packageId = route.params.id

    await packageService.updatePackage(packageId, {
      name: namaKelas.value,
      installation_fee: parseInt(installationFee.value),
      monthly_abodemen: parseInt(abodemen.value),
      late_penalty: parseInt(denda.value),
    })

    for (const id of deletedBlockIds.value) {
      await packageService.deleteTariffBlock(packageId, id)
    }

    for (const block of blocks.value) {
      const blockPayload = {
        usage_min_m3: parseInt(block.from || 0),
        usage_max_m3: block.to ? parseInt(block.to) : null,
        price_per_m3: parseInt(block.price),
      }

      if (block.id) {
        await packageService.updateTariffBlock(packageId, block.id, blockPayload)
      } else {
        await packageService.createTariffBlock(packageId, blockPayload)
      }
    }

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
