<template>
  <div class="max-w-5xl! mx-auto grid! grid-cols-1! lg:grid-cols-3! gap-6">
    <div class="lg:col-span-2! flex! flex-col! gap-6!">
      <ContentCard variant="bordered" padding="large" rounded="2xl">
        <div class="flex! items-start! justify-between! gap-4!">
          <div class="flex-1!">
            <p class="text-xs! font-bold! text-indigo-500! uppercase! tracking-widest! mb-2!">
              Customer Profile
            </p>
            <h1 class="text-2xl! md:text-3xl! font-extrabold! text-slate-800! mb-3!">
              {{ customer.name }}
            </h1>
            <div class="flex! items-start! gap-2! text-slate-500!">
              <font-awesome-icon icon="map-marker-alt" class="text-indigo-400! mt-0.5! shrink-0!" />
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

      <div class="grid! grid-cols-1! sm:grid-cols-2! gap-3!">
        <ContentCard variant="bordered" padding="none" rounded="xl" :hoverable="true">
          <div
            class="bg-gradient-to-br! from-indigo-50! to-violet-100! rounded-xl! px-4! py-3! flex! items-center! justify-between! transition-all! duration-300! hover:-translate-y-1! hover:shadow-lg! cursor-pointer! h-full!"
          >
            <p class="text-xs! text-indigo-400! font-medium!">No. Induk</p>
            <p class="text-sm! font-bold! text-slate-800!">{{ customer.noInduk }}</p>
          </div>
        </ContentCard>
        <ContentCard variant="bordered" padding="none" rounded="xl" :hoverable="true">
          <div
            class="bg-gradient-to-br! from-sky-50! to-cyan-100! rounded-xl! px-4! py-3! flex! items-center! justify-between! transition-all! duration-300! hover:-translate-y-1! hover:shadow-lg! cursor-pointer! h-full!"
          >
            <p class="text-xs! text-sky-400! font-medium!">Abodemen</p>
            <p class="text-sm! font-bold! text-slate-800!">{{ customer.abodemen }}</p>
          </div>
        </ContentCard>
        <ContentCard variant="bordered" padding="none" rounded="xl" :hoverable="true">
          <div
            class="bg-gradient-to-br! from-emerald-50! to-teal-100! rounded-xl! px-4! py-3! flex! items-center! justify-between! transition-all! duration-300! hover:-translate-y-1! hover:shadow-lg! cursor-pointer! h-full!"
          >
            <p class="text-xs! text-emerald-400! font-medium!">Tgl Order</p>
            <p class="text-sm! font-bold! text-slate-800!">{{ customer.tglOrder }}</p>
          </div>
        </ContentCard>
        <ContentCard variant="bordered" padding="none" rounded="xl" :hoverable="true">
          <div
            class="bg-gradient-to-br! from-amber-50! to-orange-100! rounded-xl! px-4! py-3! flex! items-center! justify-between! transition-all! duration-300! hover:-translate-y-1! hover:shadow-lg! cursor-pointer! h-full!"
          >
            <p class="text-xs! text-amber-500! font-medium!">Paket Instalasi</p>
            <p class="text-sm! font-bold! text-slate-800!">{{ customer.paket }}</p>
          </div>
        </ContentCard>
      </div>

      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-center! justify-between!">
          <div>
            <p class="text-xs! text-slate-400! mb-2!">Status Pembayaran</p>
            <span
              class="px-3! py-1! rounded-full! text-xs! font-bold!"
              :class="
                customer.isPaid ? 'bg-green-100! text-green-700!' : 'bg-red-100! text-red-600!'
              "
            >
              {{ customer.isPaid ? 'PAID' : 'UNPAID' }}
            </span>
          </div>
          <div class="text-right!">
            <p class="text-xs! text-slate-400!">Last checked</p>
            <p class="text-xs! font-semibold! text-slate-500!">Just now</p>
          </div>
        </div>
      </ContentCard>
    </div>

    <div class="flex! flex-col! gap-6!">
      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-center! gap-2! mb-4!">
          <div class="w-7! h-7! bg-indigo-100! rounded-lg! flex! items-center! justify-center!">
            <font-awesome-icon icon="edit" class="text-indigo-500! text-xs!" />
          </div>
          <h3 class="text-base! font-bold! text-slate-800!">Detail Permohonan</h3>
        </div>

        <div class="space-y-4!">
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
              >Tanggal Permohonan</label
            >
            <input
              type="text"
              :value="customer.tglOrder"
              readonly
              class="w-full! border! border-slate-200! rounded-xl! px-3! py-2.5! text-sm! text-slate-700! bg-slate-50! focus:outline-none!"
            />
          </div>
          <div>
            <label
              class="text-xs! font-semibold! text-slate-500! uppercase! tracking-wide! block! mb-1!"
              >Keterangan</label
            >
            <textarea
              rows="3"
              placeholder="Keterangan permohonan..."
              class="w-full! border! border-slate-200! rounded-xl! px-3! py-2.5! text-sm! text-slate-700! focus:outline-none! focus:ring-2! focus:ring-indigo-300! focus:border-indigo-400! transition-all! resize-none!"
            ></textarea>
          </div>
        </div>

        <div class="mt-5! space-y-2!">
          <button
            class="w-full! flex! items-center! justify-center! gap-2! bg-gradient-to-r! from-indigo-500! to-blue-600! hover:from-indigo-600! hover:to-blue-700! text-white! font-bold! py-3! rounded-xl! shadow-lg! shadow-indigo-200/50! transition-all! active:scale-95!"
          >
            <font-awesome-icon icon="check-circle" />
            Proses ke Pasang Baru
          </button>
          <div class="grid! grid-cols-2! gap-2!">
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
defineOptions({ name: 'PermohonanDetail' })
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useInstalasiStatus } from '@/composables/useInstalasiStatus'
import ContentCard from '@/components/ui/ContentCard.vue'

const route = useRoute()
const { dataMap } = useInstalasiStatus()
const id = decodeURIComponent(route.params.id)

const customer = computed(() => {
  const found = dataMap.value.permohonan?.find((r) => r.id === id)
  if (!found)
    return {
      name: 'Tidak Ditemukan',
      address: '-',
      region: '-',
      noInduk: '-',
      abodemen: '0',
      tglOrder: '-',
      paket: '-',
      kodeInstalasi: '-',
      isPaid: false,
    }
  return {
    name: found.name,
    address: found.address,
    region: 'Kabupaten / DI Yogyakarta',
    noInduk: found.id,
    abodemen: '10,000.00',
    tglOrder: '2024-11-11',
    paket: found.type,
    kodeInstalasi: found.id.replace('#MA-', '5..12.'),
    isPaid: false,
  }
})
</script>
