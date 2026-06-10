template>
  <div class="max-w-5xl! mx-auto grid! grid-cols-1! lg:grid-cols-3! gap-6!">
    <div class="lg:col-span-2! flex! flex-col! gap-6!">
      <ContentCard variant="bordered" padding="large" rounded="2xl">
        <div class="flex! items-start! justify-between! gap-4!">
          <div class="flex-1!">
            <div class="flex! items-center! justify-between! mb-2!">
              <p class="text-xs! font-bold! text-emerald-500! uppercase! tracking-widest!">
                Customer Profile
              </p>
              <span
                class="px-3! py-1! rounded-full! text-xs! font-bold! bg-emerald-100! text-emerald-700!"
                >AKTIF</span
              >
            </div>
            <h1 class="text-3xl! font-extrabold! text-slate-800! mb-3!">{{ customer.name }}</h1>
            <div class="flex! items-start! gap-2! text-slate-500!">
              <font-awesome-icon
                icon="map-marker-alt"
                class="text-emerald-400! mt-0.5! shrink-0!"
              />
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
            class="bg-gradient-to-br! from-emerald-50! to-green-100! rounded-xl! px-4! py-3! flex! items-center! justify-between! transition-all! duration-300! hover:-translate-y-1! hover:shadow-lg! cursor-pointer! h-full!"
          >
            <p class="text-xs! text-emerald-400! font-medium!">No. Induk</p>
            <p class="text-sm! font-bold! text-slate-800!">{{ customer.noInduk }}</p>
          </div>
        </ContentCard>
        <ContentCard variant="bordered" padding="none" rounded="xl" :hoverable="true">
          <div
            class="bg-gradient-to-br! from-blue-50! to-indigo-100! rounded-xl! px-4! py-3! flex! items-center! justify-between! transition-all! duration-300! hover:-translate-y-1! hover:shadow-lg! cursor-pointer! h-full!"
          >
            <p class="text-xs! text-blue-400! font-medium!">Abodemen</p>
            <p class="text-sm! font-bold! text-slate-800!">{{ customer.abodemen }}</p>
          </div>
        </ContentCard>
        <ContentCard variant="bordered" padding="none" rounded="xl" :hoverable="true">
          <div
            class="bg-gradient-to-br! from-violet-50! to-purple-100! rounded-xl! px-4! py-3! flex! items-center! justify-between! transition-all! duration-300! hover:-translate-y-1! hover:shadow-lg! cursor-pointer! h-full!"
          >
            <p class="text-xs! text-violet-400! font-medium!">Tgl Pasang</p>
            <p class="text-sm! font-bold! text-slate-800!">{{ customer.tglPasang }}</p>
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
        <h3 class="text-sm! font-bold! text-slate-700! mb-4!">Informasi Meter</h3>
        <div class="grid! grid-cols-1! sm:grid-cols-3! gap-4!">
          <div class="text-center!">
            <p class="text-xs! text-slate-400! mb-1!">Meter Awal</p>
            <p class="text-2xl! font-black! text-emerald-600!">
              {{ formatMeter(customer.meterAwal) }}
            </p>
            <p class="text-xs! text-slate-400!">m┬│</p>
          </div>
          <div class="text-center!">
            <p class="text-xs! text-slate-400! mb-1!">Pemakaian Terakhir</p>
            <p class="text-2xl! font-black! text-slate-800!">
              {{ formatMeter(customer.meterAkhir) }}
            </p>
            <p class="text-xs! text-slate-400!">m┬│</p>
          </div>
          <div class="text-center!">
            <p class="text-xs! text-slate-400! mb-1!">Total Pemakaian</p>
            <p class="text-2xl! font-black! text-blue-600!">
              {{ formatMeter(customer.totalPemakaian) }}
            </p>
            <p class="text-xs! text-slate-400!">m┬│</p>
          </div>
        </div>
      </ContentCard>
    </div>

    <div class="flex! flex-col! gap-6!">
      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-center! gap-2! mb-4!">
          <div class="w-7! h-7! bg-emerald-100! rounded-lg! flex! items-center! justify-center!">
            <font-awesome-icon icon="cog" class="text-emerald-500! text-xs!" />
          </div>
          <h3 class="text-base! font-bold! text-slate-800!">Kelola Pelanggan</h3>
        </div>
        <div class="space-y-2! mt-5!">
          <button
            @click="handleBlokir"
            :disabled="!customer.ticketId"
            class="w-full! flex! items-center! justify-center! gap-2! bg-gradient-to-r! from-orange-500! to-amber-500! hover:from-orange-600! hover:to-amber-600! text-white! font-bold! py-3! rounded-xl! shadow-lg! shadow-orange-200/50! transition-all! active:scale-95! disabled:opacity-50! disabled:cursor-not-allowed!"
          >
            <font-awesome-icon icon="ban" />
            Blokir Pelanggan
          </button>
          <button
            @click="handleCabut"
            :disabled="!customer.ticketId"
            class="w-full! flex! items-center! justify-center! gap-2! bg-gradient-to-r! from-red-500! to-rose-600! hover:from-red-600! hover:to-rose-700! text-white! font-bold! py-3! rounded-xl! shadow-lg! shadow-red-200/50! transition-all! active:scale-95! disabled:opacity-50! disabled:cursor-not-allowed!"
          >
            <font-awesome-icon icon="times-circle" />
            Cabut Instalasi
          </button>
          <div class="grid! grid-cols-2! gap-2! pt-1!">
            <button
              @click="handlePrint"
              class="flex! items-center! justify-center! gap-2! border! border-slate-200! hover:bg-slate-50! text-slate-600! font-semibold! py-2.5! rounded-xl! text-sm! transition-all!"
            >
              <font-awesome-icon icon="print" />
              Cetak
            </button>
            <button
              @click="$router.push({ path: '/instalasi/status', query: { filter: 'aktif' } })"
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
defineOptions({ name: 'AktifDetail' })
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useInstalasiStatus } from '@/composables/useInstalasiStatus'
import { useInstalasiActions } from '@/composables/useInstalasiActions'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'

const route = useRoute()
const router = useRouter()
const { dataMap, fetchData } = useInstalasiStatus()
const { transitionStatus, printDetail } = useInstalasiActions()
const id = decodeURIComponent(route.params.id)

const formatMeter = (val) => {
  const n = Number(val || 0)
  return n.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}

const customer = computed(() => {
  const found = dataMap.value.aktif?.find((r) => r.id === id)
  if (!found)
    return {
      name: 'Tidak Ditemukan',
      address: '-',
      region: '-',
      noInduk: '-',
      nik: '-',
      phone: '-',
      abodemen: '0',
      tglPasang: '-',
      paket: '-',
      kodeInstalasi: '-',
      meterAwal: 0,
      meterAkhir: 0,
      totalPemakaian: 0,
      ticketId: null,
      rawStatus: null,
    }

  const customerRecord = Array.isArray(found.rawData?.customer)
    ? found.rawData.customer[0]
    : found.rawData?.customer

  const meterAwal = Number(customerRecord?.initial_meter_reading || 0)

  const readings = Array.isArray(customerRecord?.meter_readings)
    ? customerRecord.meter_readings
    : []
  const lastReading = readings.length
    ? readings.reduce((latest, r) => {
        const latestDate = new Date(latest.recorded_at || 0).getTime()
        const rDate = new Date(r.recorded_at || 0).getTime()
        return rDate > latestDate ? r : latest
      }, readings[0])
    : null
  const meterAkhir = Number(lastReading?.meter_value || meterAwal)
  const totalPemakaian = Math.max(0, meterAkhir - meterAwal)

  return {
    name: found.name,
    address: found.address,
    region: found.village || '-',
    noInduk: found.id,
    nik: found.nik,
    phone: found.phone,
    abodemen: found.rawData?.package?.installation_fee || '0',
    tglPasang: customerRecord?.activated_at || found.orderDate || found.createdAt,
    paket: found.type,
    kodeInstalasi: found.id,
    meterAwal,
    meterAkhir,
    totalPemakaian,
    ticketId: found.ticketId,
    rawStatus: found.rawStatus,
    rawData: found.rawData,
  }
})

const handleBlokir = async () => {
  if (!customer.value.ticketId) return
  const kodeInstalasi = customer.value.kodeInstalasi
  const result = await transitionStatus(
    customer.value.ticketId,
    'suspended',
    `Blokir layanan untuk pelanggan "${customer.value.name}"?`,
  )
  if (result.success) {
    await fetchData()
    router.push({
      path: `/instalasi/status/blokir/${encodeURIComponent(kodeInstalasi)}`,
    })
  }
}

const handleCabut = async () => {
  if (!customer.value.ticketId) return
  const kodeInstalasi = customer.value.kodeInstalasi
  const result = await transitionStatus(
    customer.value.ticketId,
    'terminated',
    `Cabut instalasi untuk pelanggan "${customer.value.name}"? Tindakan ini tidak dapat dikembalikan.`,
  )
  if (result.success) {
    await fetchData()
    router.push({
      path: `/instalasi/status/cabut/${encodeURIComponent(kodeInstalasi)}`,
    })
  }
}

const handlePrint = () => {
  printDetail({ ...customer.value, tglOrder: customer.value.tglPasang }, 'Aktif')
}
</script>

