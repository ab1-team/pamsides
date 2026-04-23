<template>
  <div class="max-w-5xl! mx-auto grid! grid-cols-1! lg:grid-cols-3! gap-6!">
    <div class="lg:col-span-2! flex! flex-col! gap-6!">
      <ContentCard variant="bordered" padding="large" rounded="2xl">
        <div class="flex! items-start! justify-between! gap-4!">
          <div class="flex-1!">
            <div class="flex! items-center! justify-between! mb-2!">
              <p class="text-xs! font-bold! text-red-500! uppercase! tracking-widest!">
                Customer Profile
              </p>
              <span class="px-3! py-1! rounded-full! text-xs! font-bold! bg-red-100! text-red-700!">
                CABUT
              </span>
            </div>
            <h1 class="text-3xl! font-extrabold! text-slate-800! mb-3!">{{ customer.name }}</h1>
            <div class="flex! items-start! gap-2! text-slate-500!">
              <font-awesome-icon icon="map-marker-alt" class="text-red-400! mt-0.5! shrink-0!" />
              <div>
                <p class="text-sm! font-medium!">{{ customer.address }}</p>
                <p class="text-xs! text-slate-400!">{{ customer.region }}</p>
              </div>
            </div>
          </div>
          <div class="shrink-0! flex! flex-col! items-center! gap-2!">
            <div
              class="w-24! h-24! bg-slate-300! rounded-xl! flex! items-center! justify-center! shadow-md! opacity-40!"
            >
              <font-awesome-icon icon="qrcode" class="text-slate-600! text-5xl!" />
            </div>
            <span class="text-[10px]! text-slate-400! font-medium! tracking-wide! italic!">
              QR tidak aktif
            </span>
          </div>
        </div>
      </ContentCard>

      <div class="bg-red-50! border! border-red-200! rounded-2xl! p-4! flex! items-start! gap-3!">
        <font-awesome-icon icon="times-circle" class="text-red-500! mt-0.5! shrink-0!" />
        <div>
          <p class="text-xs! text-red-600!">
            <span class="font-bold! text-red-700! mr-1!">Instalasi Dicabut:</span>
            Sambungan air pelanggan ini telah dicabut. Layanan tidak aktif dan tidak dapat
            digunakan. Hubungi admin untuk pengajuan ulang.
          </p>
        </div>
      </div>

      <div class="grid! grid-cols-1! sm:grid-cols-2! gap-3!">
        <ContentCard variant="bordered" padding="none" rounded="xl" :hoverable="true">
          <div
            class="bg-gradient-to-br! from-red-50! to-rose-100! rounded-xl! px-4! py-3! flex! items-center! justify-between! transition-all! duration-300! hover:-translate-y-1! hover:shadow-lg! cursor-pointer! h-full!"
          >
            <p class="text-xs! text-red-400! font-medium!">No. Induk</p>
            <p class="text-sm! font-bold! text-slate-800!">{{ customer.noInduk }}</p>
          </div>
        </ContentCard>
        <ContentCard variant="bordered" padding="none" rounded="xl" :hoverable="true">
          <div
            class="bg-gradient-to-br! from-slate-50! to-gray-100! rounded-xl! px-4! py-3! flex! items-center! justify-between! transition-all! duration-300! hover:-translate-y-1! hover:shadow-lg! cursor-pointer! h-full!"
          >
            <p class="text-xs! text-slate-400! font-medium!">Paket Instalasi</p>
            <p class="text-sm! font-bold! text-slate-800!">{{ customer.paket }}</p>
          </div>
        </ContentCard>
        <ContentCard variant="bordered" padding="none" rounded="xl" :hoverable="true">
          <div
            class="bg-gradient-to-br! from-amber-50! to-yellow-100! rounded-xl! px-4! py-3! flex! items-center! justify-between! transition-all! duration-300! hover:-translate-y-1! hover:shadow-lg! cursor-pointer! h-full!"
          >
            <p class="text-xs! text-amber-500! font-medium!">Tgl Pasang</p>
            <p class="text-sm! font-bold! text-slate-800!">{{ customer.tglPasang }}</p>
          </div>
        </ContentCard>
        <ContentCard variant="bordered" padding="none" rounded="xl" :hoverable="true">
          <div
            class="bg-gradient-to-br! from-rose-50! to-red-100! rounded-xl! px-4! py-3! flex! items-center! justify-between!"
          >
            <p class="text-xs! text-rose-400! font-medium!">Tgl Cabut</p>
            <p class="text-sm! font-bold! text-red-600!">{{ customer.tglCabut }}</p>
          </div>
        </ContentCard>
      </div>

      <div class="grid! grid-cols-1! sm:grid-cols-3! gap-3! mt-3!">
        <button
          @click="handleDelete"
          class="flex! items-center! justify-center! gap-2! bg-red-500! hover:bg-red-600! text-white! font-bold! py-3! rounded-xl! text-sm! transition-all! shadow-lg! shadow-red-200/50! border-red-500!"
        >
          <font-awesome-icon icon="trash-alt" />
          Hapus Pelanggan
        </button>
        <button
          class="flex! items-center! justify-center! gap-2! border! border-slate-200! hover:bg-slate-50! text-slate-600! font-semibold! py-3! rounded-xl! text-sm! transition-all! bg-white!"
        >
          <font-awesome-icon icon="print" />
          Cetak
        </button>
        <button
          @click="$router.back()"
          class="flex! items-center! justify-center! gap-2! border! border-slate-200! hover:bg-slate-50! text-slate-600! font-semibold! py-3! rounded-xl! text-sm! transition-all! bg-white!"
        >
          <font-awesome-icon icon="arrow-left" />
          Kembali
        </button>
      </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div class="flex! flex-col! gap-6!">
      <!-- History Timeline -->
      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <h3 class="text-sm! font-bold! text-slate-700! mb-4!">Riwayat Status</h3>
        <div class="space-y-3!">
          <div
            v-for="event in timeline"
            :key="event.date"
            class="flex! items-center! justify-between! gap-4!"
          >
            <div class="flex! items-center! gap-3!">
              <div class="w-2! h-2! rounded-full! shrink-0!" :class="event.color"></div>
              <p class="text-sm! font-semibold! text-slate-700!">{{ event.label }}</p>
            </div>
            <p class="text-xs! text-slate-400! font-medium! font-mono!">{{ event.date }}</p>
          </div>
        </div>
      </ContentCard>

      <div class="flex! flex-col! gap-6!">
        <ContentCard variant="bordered" padding="normal" rounded="2xl">
          <div class="flex! items-center! gap-2! mb-4!">
            <div class="w-7! h-7! bg-red-100! rounded-lg! flex! items-center! justify-center!">
              <font-awesome-icon icon="times-circle" class="text-red-500! text-xs!" />
            </div>
            <h3 class="text-base! font-bold! text-slate-800!">Info Pencabutan</h3>
          </div>
          <div class="space-y-3! mb-4!">
            <div>
              <label
                class="text-xs! font-semibold! text-slate-500! uppercase! tracking-wide! block! mb-1!"
              >
                Kode Instalasi
              </label>
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
              >
                Alasan Pencabutan
              </label>
              <input
                type="text"
                :value="customer.alasanCabut"
                readonly
                class="w-full! border! border-slate-200! rounded-xl! px-3! py-2.5! text-sm! text-slate-700! bg-slate-50! focus:outline-none!"
              />
            </div>
          </div>
        </ContentCard>
      </div>
    </div>
  </div>
</template>

<script setup>
defineOptions({ name: 'CabutDetail' })
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useInstalasiStatus } from '@/composables/useInstalasiStatus'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import Swal from 'sweetalert2'

const route = useRoute()
const router = useRouter()
const { dataMap } = useInstalasiStatus()
const id = decodeURIComponent(route.params.id)

const handleDelete = async () => {
  const result = await Swal.fire({
    title: 'Hapus Pelanggan?',
    text: 'Data pelanggan ini akan dihapus secara permanen dari aplikasi!',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Ya, Hapus Permanen',
    cancelButtonText: 'Batal',
    reverseButtons: true,
  })

  if (result.isConfirmed) {
    // Logic delete here
    await Swal.fire({
      title: 'Terhapus!',
      text: 'Data pelanggan telah dihapus secara permanen.',
      icon: 'success',
      timer: 1500,
      showConfirmButton: false,
    })
    router.push({ name: 'Status Instalasi' })
  }
}

const timeline = [
  { label: 'Permohonan Diterima', date: '2020-01-10', color: 'bg-blue-500!' },
  { label: 'Pasang Baru Selesai', date: '2020-01-15', color: 'bg-sky-500!' },
  { label: 'Status Aktif', date: '2020-01-16', color: 'bg-emerald-500!' },
  { label: 'Diblokir - Tunggakan', date: '2020-06-01', color: 'bg-orange-500!' },
  { label: 'Instalasi Dicabut', date: '2020-09-01', color: 'bg-red-500!' },
]

const customer = computed(() => {
  const found = dataMap.value.cabut?.find((r) => r.id === id)
  if (!found)
    return {
      name: 'Tidak Ditemukan',
      address: '-',
      region: '-',
      noInduk: '-',
      paket: '-',
      tglPasang: '-',
      tglCabut: '-',
      kodeInstalasi: '-',
      alasanCabut: '-',
    }
  return {
    name: found.name,
    address: found.address,
    region: 'Kabupaten / DI Yogyakarta',
    noInduk: found.id,
    paket: found.type,
    tglPasang: '2020-01-15',
    tglCabut: '2020-09-01',
    kodeInstalasi: found.id.replace('#MA-', '5..12.'),
    alasanCabut: 'Pindah Domisili',
  }
})
</script>
