<template>
  <div class="max-w-5xl! mx-auto grid! grid-cols-1! lg:grid-cols-3! gap-6!">
    <div class="lg:col-span-2! flex! flex-col! gap-6!">
      <ContentCard variant="bordered" padding="large" rounded="2xl">
        <div class="flex! items-start! justify-between! gap-4!">
          <div class="flex-1!">
            <div class="flex! items-center! justify-between! mb-2!">
              <p class="text-xs! font-bold! text-orange-500! uppercase! tracking-widest!">
                Customer Profile
              </p>
              <span
                class="px-3! py-1! rounded-full! text-xs! font-bold! bg-orange-100! text-orange-700!"
                >BLOKIR</span
              >
            </div>
            <h1 class="text-3xl! font-extrabold! text-slate-800! mb-3!">{{ customer.name }}</h1>
            <div class="flex! items-start! gap-2! text-slate-500!">
              <font-awesome-icon icon="map-marker-alt" class="text-orange-400! mt-0.5! shrink-0!" />
              <div>
                <p class="text-sm! font-medium!">{{ customer.address }}</p>
                <p class="text-xs! text-slate-400!">{{ customer.region }}</p>
              </div>
            </div>
          </div>
          <div class="shrink-0! flex! flex-col! items-center! gap-2!">
            <div
              class="w-24! h-24! bg-slate-800! rounded-xl! flex! items-center! justify-center! shadow-md!"
            >
              <font-awesome-icon icon="qrcode" class="text-white! text-5xl!" />
            </div>
            <span class="text-[10px]! text-slate-400! font-medium! tracking-wide!"
              >Work Order QR</span
            >
          </div>
        </div>
      </ContentCard>

      <div
        class="bg-orange-50! border! border-orange-200! rounded-2xl! p-4! flex! items-start! gap-3!"
      >
        <font-awesome-icon icon="exclamation-triangle" class="text-orange-500! mt-0.5! shrink-0!" />
        <div>
          <p class="text-sm! font-bold! text-orange-700!">Akun Diblokir</p>
          <p class="text-xs! text-orange-600! mt-0.5!">
            Pelanggan ini sedang dalam status blokir. Layanan air terputus sementara. Silakan
            selesaikan tunggakan untuk mengaktifkan kembali.
          </p>
        </div>
      </div>

      <div class="grid! grid-cols-1! sm:grid-cols-2! gap-3!">
        <ContentCard variant="bordered" padding="none" rounded="xl" :hoverable="true">
          <div
            class="bg-gradient-to-br! from-orange-50! to-amber-100! rounded-xl! px-4! py-3! flex! items-center! justify-between! transition-all! duration-300! hover:-translate-y-1! hover:shadow-lg! cursor-pointer! h-full!"
          >
            <p class="text-xs! text-orange-400! font-medium!">No. Induk</p>
            <p class="text-sm! font-bold! text-slate-800!">{{ customer.noInduk }}</p>
          </div>
        </ContentCard>
        <ContentCard variant="bordered" padding="none" rounded="xl" :hoverable="true">
          <div
            class="bg-gradient-to-br! from-sky-50! to-blue-100! rounded-xl! px-4! py-3! flex! items-center! justify-between! transition-all! duration-300! hover:-translate-y-1! hover:shadow-lg! cursor-pointer! h-full!"
          >
            <p class="text-xs! text-sky-400! font-medium!">Abodemen</p>
            <p class="text-sm! font-bold! text-slate-800!">{{ customer.abodemen }}</p>
          </div>
        </ContentCard>
        <ContentCard variant="bordered" padding="none" rounded="xl" :hoverable="true">
          <div
            class="bg-gradient-to-br! from-red-50! to-rose-100! rounded-xl! px-4! py-3! flex! items-center! justify-between! transition-all! duration-300! hover:-translate-y-1! hover:shadow-lg! cursor-pointer! h-full!"
          >
            <p class="text-xs! text-red-400! font-medium!">Tgl Blokir</p>
            <p class="text-sm! font-bold! text-red-600!">{{ customer.tglBlokir }}</p>
          </div>
        </ContentCard>
        <ContentCard variant="bordered" padding="none" rounded="xl" :hoverable="true">
          <div
            class="bg-gradient-to-br! from-violet-50! to-purple-100! rounded-xl! px-4! py-3! flex! items-center! justify-between!"
          >
            <p class="text-xs! text-violet-400! font-medium!">Alasan Blokir</p>
            <p class="text-sm! font-bold! text-slate-800!">{{ customer.alasanBlokir }}</p>
          </div>
        </ContentCard>
      </div>

      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <h3 class="text-sm! font-bold! text-slate-700! mb-4!">Total Tunggakan</h3>
        <div class="grid! grid-cols-1! sm:grid-cols-2! gap-4!">
          <div>
            <p class="text-xs! text-slate-400! mb-1!">Jumlah Tagihan</p>
            <p class="text-2xl! font-black! text-orange-600!">Rp {{ customer.totalTagihan }}</p>
          </div>
          <div>
            <p class="text-xs! text-slate-400! mb-1!">Bulan Tunggakan</p>
            <p class="text-2xl! font-black! text-slate-800!">{{ customer.bulanTunggakan }}</p>
          </div>
        </div>
      </ContentCard>
    </div>

    <div class="flex! flex-col! gap-6!">
      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-center! gap-2! mb-4!">
          <div class="w-7! h-7! bg-orange-100! rounded-lg! flex! items-center! justify-center!">
            <font-awesome-icon icon="ban" class="text-orange-500! text-xs!" />
          </div>
          <h3 class="text-base! font-bold! text-slate-800!">Kelola Blokir</h3>
        </div>
        <div class="space-y-3! mb-4!">
          <div>
            <label
              class="text-xs! font-semibold! text-slate-500! uppercase! tracking-wide! block! mb-1!"
              >Kode Instalasi</label
            >
            <input
              type="text"
              :value="customer.kodeInstalasi"
              readonly
              class="w-full! border! border-slate-200! rounded-xl! px-3! py-2.5! text-sm! text-slate-700! bg-slate-50! focus:outline-none!"
            />
          </div>
          <div>
            <label
              class="text-xs! font-semibold! text-slate-500! uppercase! tracking-wide! block! mb-1!"
              >Catatan</label
            >
            <textarea
              v-model="catatan"
              rows="3"
              placeholder="Tambah catatan blokir..."
              class="w-full! border! border-slate-200! rounded-xl! px-3! py-2.5! text-sm! text-slate-700! focus:outline-none! focus:ring-2! focus:ring-orange-300! focus:border-orange-400! transition-all! resize-none!"
            ></textarea>
          </div>
        </div>
        <div class="space-y-2!">
          <button
            class="w-full! flex! items-center! justify-center! gap-2! bg-gradient-to-r! from-emerald-500! to-green-600! hover:from-emerald-600! hover:to-green-700! text-white! font-bold! py-3! rounded-xl! shadow-lg! shadow-emerald-200/50! transition-all! active:scale-95!"
          >
            <font-awesome-icon icon="check-circle" />
            Aktifkan Kembali
          </button>
          <button
            class="w-full! flex! items-center! justify-center! gap-2! bg-gradient-to-r! from-red-500! to-rose-600! hover:from-red-600! hover:to-rose-700! text-white! font-bold! py-3! rounded-xl! shadow-lg! shadow-red-200/50! transition-all! active:scale-95!"
          >
            <font-awesome-icon icon="times-circle" />
            Cabut Instalasi
          </button>
          <div class="grid! grid-cols-2! gap-2! pt-1!">
            <button
              class="flex! items-center! justify-center! gap-2! border! border-slate-200! hover:bg-slate-50! text-slate-600! font-semibold! py-2.5! rounded-xl! text-sm! transition-all!"
            >
              <font-awesome-icon icon="print" />
              Cetak
            </button>
            <button
              @click="$router.back()"
              class="flex! items-center! justify-center! gap-2! border! border-slate-200! hover:bg-slate-50! text-slate-600! font-semibold! py-2.5! rounded-xl! text-sm! transition-all!"
            >
              <font-awesome-icon icon="arrow-left" />
              Kembali
            </button>
          </div>
        </div>
      </ContentCard>
    </div>
  </div>
</template>

<script setup>
defineOptions({ name: 'BlokirDetail' })
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useInstalasiStatus } from '@/composables/useInstalasiStatus'
import ContentCard from '@/components/ui/ContentCard.vue'

const route = useRoute()
const { dataMap } = useInstalasiStatus()
const id = decodeURIComponent(route.params.id)
const catatan = ref('')

const customer = computed(() => {
  const found = dataMap.value.blokir?.find((r) => r.id === id)
  if (!found)
    return {
      name: 'Tidak Ditemukan',
      address: '-',
      region: '-',
      noInduk: '-',
      abodemen: '0',
      tglBlokir: '-',
      alasanBlokir: '-',
      kodeInstalasi: '-',
      totalTagihan: '0',
      bulanTunggakan: '0 Bulan',
    }
  return {
    name: found.name,
    address: found.address,
    region: 'Kabupaten / DI Yogyakarta',
    noInduk: found.id,
    abodemen: '10,000.00',
    tglBlokir: '2024-03-01',
    alasanBlokir: 'Tunggakan > 3 Bulan',
    kodeInstalasi: found.id.replace('#MA-', '5..12.'),
    totalTagihan: '150,000',
    bulanTunggakan: '3 Bulan',
  }
})
</script>
