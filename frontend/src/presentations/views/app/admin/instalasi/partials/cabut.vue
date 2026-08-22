<template>
  <div class="max-w-5xl! mx-auto grid! grid-cols-1! lg:grid-cols-3! gap-6!">
    <div class="lg:col-span-2! flex! flex-col! gap-6!">
      <ContentCard variant="bordered" padding="large" rounded="2xl">
        <div class="flex! items-center! justify-between! mb-3!">
          <p class="text-[10px]! font-bold! text-red-500! uppercase! tracking-widest!">
            Status Terakhir
          </p>
          <span
            class="inline-flex! items-center! gap-1! px-2.5! py-1! rounded-full! text-[10px]! font-bold! uppercase! tracking-wider! bg-red-100! text-red-700!"
          >
            <span class="w-1.5! h-1.5! rounded-full! bg-current! opacity-60!"></span>
            Cabut
          </span>
        </div>
        <h1 class="text-2xl! font-extrabold! text-slate-800! mb-2!">{{ customer.name }}</h1>
        <div class="flex! items-start! gap-2! text-slate-500!">
          <font-awesome-icon icon="map-marker-alt" class="text-red-400! mt-0.5! shrink-0!" />
          <p class="text-[11px]!">{{ customer.address }}, {{ customer.region }}</p>
        </div>

        <div
          class="rounded-xl! bg-red-50! border! border-red-200! p-3! mt-4! text-[11px]! text-red-700! leading-relaxed! flex! items-start! gap-2!"
        >
          <font-awesome-icon icon="exclamation-triangle" class="text-red-500! mt-0.5! shrink-0!" />
          <div>
            <p class="font-bold! text-red-800!">Instalasi Dicabut</p>
            <p>
              Sambungan air telah dicabut permanen. Layanan tidak aktif dan tidak dapat digunakan.
              Hubungi admin untuk pengajuan ulang.
            </p>
          </div>
        </div>

        <div class="grid! grid-cols-2! gap-2! mt-4! pt-4! border-t! border-slate-100!">
          <div class="info-item">
            <span class="info-label">No. Induk</span>
            <span class="info-value">{{ customer.noInduk }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">NIK</span>
            <span class="info-value">{{ customer.nik }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Paket</span>
            <span class="info-value">{{ customer.paket }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">No. Telepon</span>
            <span class="info-value">{{ customer.phone }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Tgl Pasang</span>
            <span class="info-value">{{ customer.tglPasang }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Tgl Cabut</span>
            <span class="info-value text-red-600!">{{ customer.tglCabut }}</span>
          </div>
        </div>
      </ContentCard>

      <div
        v-if="customer.tunggakanList.length > 0"
        class="rounded-2xl! bg-rose-50! border! border-rose-200! p-4! text-[11px]! text-rose-700! leading-relaxed! flex! items-start! gap-2.5!"
      >
        <font-awesome-icon icon="lock" class="text-rose-600! mt-0.5! shrink-0! text-sm!" />
        <div>
          <p class="font-bold! text-rose-800! text-xs! mb-0.5!">Hapus Terkunci</p>
          <p>
            Pelanggan masih memiliki
            <strong>{{ customer.tunggakanList.length }} tunggakan</strong> senilai
            <strong>Rp {{ formatRibuan(customer.totalTunggakan + customer.totalDenda) }}</strong>.
            Lunasi semua tunggakan terlebih dahulu sebelum menghapus data.
          </p>
        </div>
      </div>
      <div
        v-else
        class="rounded-2xl! bg-emerald-50! border! border-emerald-200! p-4! text-[11px]! text-emerald-700! leading-relaxed! flex! items-start! gap-2.5!"
      >
        <font-awesome-icon icon="check-circle" class="text-emerald-600! mt-0.5! shrink-0! text-sm!" />
        <div>
          <p class="font-bold! text-emerald-800! text-xs! mb-0.5!">Semua Tunggakan Lunas</p>
          <p>
            Semua tagihan sudah clear. Data pelanggan dapat dihapus permanen dari sistem.
          </p>
        </div>
      </div>

      <div class="grid! grid-cols-3! gap-3!">
        <button
          @click="handleDelete"
          :disabled="!customer.ticketId || customer.tunggakanList.length > 0"
          class="flex! items-center! justify-center! gap-2! bg-linear-to-r! from-red-500! to-rose-600! hover:from-red-600! hover:to-rose-700! text-white! font-bold! py-2.5! rounded-xl! shadow-lg! shadow-red-200/50! transition-all! active:scale-95! disabled:opacity-50! disabled:cursor-not-allowed!"
        >
          <font-awesome-icon icon="trash-alt" />
          Hapus Permanen
        </button>
        <button
          @click="handlePrint"
          class="flex! items-center! justify-center! gap-2! border! border-slate-200! hover:bg-slate-50! text-slate-600! font-semibold! py-2.5! rounded-xl! transition-all! bg-white!"
        >
          <font-awesome-icon icon="print" />
          Cetak
        </button>
        <button
          @click="$router.push({ path: '/app/instalasi/status', query: { filter: 'cabut' } })"
          class="flex! items-center! justify-center! gap-2! border! border-slate-200! hover:bg-slate-50! text-slate-600! font-semibold! py-2.5! rounded-xl! transition-all! bg-white!"
        >
          <font-awesome-icon icon="arrow-left" />
          Kembali
        </button>
      </div>
    </div>

    <div class="flex! flex-col! gap-6!">
      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-center! gap-2! mb-4!">
          <div class="w-7! h-7! bg-rose-100! rounded-lg! flex! items-center! justify-center!">
            <font-awesome-icon icon="file-invoice-dollar" class="text-rose-600! text-xs!" />
          </div>
          <h3 class="text-sm! font-bold! text-slate-800!">Rincian Tunggakan</h3>
        </div>

        <div class="grid! grid-cols-2! gap-2! mb-4!">
          <div class="text-center! p-3! rounded-xl! bg-rose-50! border! border-rose-100!">
            <p class="text-[9px]! font-bold! text-rose-700! uppercase! tracking-wider! mb-1!">
              Total Tunggakan
            </p>
            <p class="text-base! font-black! text-rose-700! leading-tight!">
              Rp {{ formatRibuan(customer.totalTunggakan) }}
            </p>
          </div>
          <div class="text-center! p-3! rounded-xl! bg-slate-50! border! border-slate-100!">
            <p class="text-[9px]! font-bold! text-slate-500! uppercase! tracking-wider! mb-1!">
              Denda
            </p>
            <p class="text-base! font-black! text-slate-700! leading-tight!">
              Rp {{ formatRibuan(customer.totalDenda) }}
            </p>
          </div>
        </div>

        <div
          v-if="customer.tunggakanList.length === 0"
          class="text-center! py-4! text-xs! text-emerald-500! font-medium!"
        >
          <font-awesome-icon icon="check-circle" class="text-emerald-400! text-lg! mb-1!" />
          <p>Semua tagihan sudah lunas.</p>
        </div>
        <div v-else class="space-y-2!">
          <div
            v-for="b in customer.tunggakanList"
            :key="b.key"
            class="flex! items-center! justify-between! px-3! py-2.5! rounded-lg! bg-rose-50/60! border! border-rose-100!"
          >
            <div class="flex! items-center! gap-2!">
              <div
                class="w-8! h-8! rounded-full! bg-white! border! border-rose-200! flex! items-center! justify-center! text-[10px]! font-bold! text-rose-600!"
              >
                {{ b.label }}
              </div>
              <div>
                <div class="text-[11px]! font-bold! text-slate-700!">
                  {{ b.periodLabel }}
                </div>
                <div v-if="b.penalty_amount > 0" class="text-[10px]! text-slate-400!">
                  denda Rp {{ formatRibuan(b.penalty_amount) }}
                </div>
              </div>
            </div>
            <span class="text-[12px]! font-extrabold! text-rose-700!">
              Rp {{ formatRibuan(b.total_amount) }}
            </span>
          </div>
        </div>
      </ContentCard>
    </div>
  </div>
</template>

<script setup>
defineOptions({ name: 'CabutDetail' })
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Swal from 'sweetalert2'
import { useInstalasiStatus } from '@/composables/useInstalasiStatus'
import { useInstalasiActions } from '@/composables/useInstalasiActions'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'

const route = useRoute()
const router = useRouter()
const { dataMap, fetchData } = useInstalasiStatus()
const { deleteTicket, printDetail } = useInstalasiActions()
const id = decodeURIComponent(route.params.id)

const MONTHS_ID = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
]

const formatRibuan = (val) => {
  if (val === null || val === undefined || val === '') return '0'
  return Number(val).toLocaleString('id-ID')
}

const formatDate = (dateStr) => {
  if (!dateStr || dateStr === '-') return '-'
  const d = new Date(dateStr)
  if (isNaN(d.getTime())) return '-'
  return `${d.getDate()} ${MONTHS_ID[d.getMonth()]} ${d.getFullYear()}`
}

const handleDelete = async () => {
  if (!customer.value.ticketId) return

  if (customer.value.tunggakanList.length > 0) {
    await Swal.fire({
      title: 'Tidak Dapat Menghapus',
      html: `
        <div style="text-align:left; font-size:13px; color:#475569;">
          <p>Pelanggan <strong>${customer.value.name}</strong> masih memiliki
          <strong style="color:#dc2626;">${customer.value.tunggakanList.length} tunggakan</strong>
          yang belum dilunasi.</p>
          <p style="margin-top:8px;">Sisa tunggakan:
            <strong style="color:#dc2626;">Rp ${formatRibuan(customer.value.totalTunggakan + customer.value.totalDenda)}</strong>
          </p>
          <p style="margin-top:10px; font-size:12px; color:#94a3b8;">
            Semua tunggakan harus dilunasi terlebih dahulu sebelum data pelanggan dapat dihapus permanen.
          </p>
        </div>
      `,
      icon: 'warning',
      confirmButtonColor: '#3b82f6',
      confirmButtonText: 'Mengerti',
    })
    return
  }

  const result = await deleteTicket(customer.value.ticketId, customer.value.name)
  if (result.success) {
    await fetchData()
    router.push({ path: '/app/instalasi/status', query: { filter: 'cabut' } })
  }
}

const handlePrint = () => {
  printDetail({ ...customer.value, tglOrder: customer.value.tglPasang }, 'Cabut')
}

onMounted(async () => {
  await fetchData()
})

const customer = computed(() => {
  const empty = {
    name: 'Pelanggan Tidak Ditemukan',
    address: 'Alamat belum tercatat',
    region: 'Wilayah belum tercatat',
    noInduk: 'Tidak tersedia',
    nik: 'Tidak tersedia',
    phone: 'Tidak tersedia',
    paket: 'Belum ada paket',
    tglPasang: 'Belum tercatat',
    tglCabut: 'Belum tercatat',
    kodeInstalasi: 'Tidak tersedia',
    ticketId: null,
    rawStatus: null,
    tunggakanList: [],
    totalTunggakan: 0,
    totalDenda: 0,
  }

  const found = dataMap.value.cabut?.find((r) => r.id === id)
  if (!found) return empty

  const customerRecord = Array.isArray(found.rawData?.customer)
    ? found.rawData.customer[0]
    : found.rawData?.customer

  const billsRaw = Array.isArray(customerRecord?.monthly_bills)
    ? customerRecord.monthly_bills
    : []
  const billsMap = new Map()
  for (const b of billsRaw) {
    const key = `${b.billing_period_year}-${b.billing_period_month}`
    const prev = billsMap.get(key)
    if (
      !prev ||
      new Date(b.updated_at || b.created_at || 0).getTime() >
        new Date(prev.updated_at || prev.created_at || 0).getTime()
    ) {
      billsMap.set(key, b)
    }
  }
  const unpaidBills = Array.from(billsMap.values())
    .filter((b) => b.status === 'unpaid')
    .sort(
      (a, b) =>
        (Number(a.billing_period_year) - Number(b.billing_period_year)) * 12 +
        (Number(a.billing_period_month) - Number(b.billing_period_month)),
    )
  const totalTunggakan = unpaidBills.reduce((s, b) => s + Number(b.total_amount || 0), 0)
  const totalDenda = unpaidBills.reduce((s, b) => s + Number(b.penalty_amount || 0), 0)

  const tunggakanList = unpaidBills.map((b, idx) => {
    const monthIdx = Number(b.billing_period_month) - 1
    return {
      key: `${b.billing_period_year}-${b.billing_period_month}-${idx}`,
      label: monthIdx >= 0 ? MONTHS_ID[monthIdx].substring(0, 3) : '?',
      periodLabel: `${MONTHS_ID[monthIdx] || '-'} ${b.billing_period_year}`,
      total_amount: Number(b.total_amount || 0),
      penalty_amount: Number(b.penalty_amount || 0),
    }
  })

  return {
    name: found.name || 'Pelanggan Tidak Ditemukan',
    address: found.address || 'Alamat belum tercatat',
    region: found.village || 'Wilayah belum tercatat',
    noInduk: found.id || 'Tidak tersedia',
    nik: found.nik || 'Tidak tersedia',
    phone: found.phone || 'Tidak tersedia',
    paket: found.type || 'Belum ada paket',
    tglPasang: formatDate(found.orderDate || found.createdAt) || 'Belum tercatat',
    tglCabut: formatDate(found.updatedAt) || 'Belum tercatat',
    kodeInstalasi: found.id || 'Tidak tersedia',
    ticketId: found.ticketId,
    rawStatus: found.rawStatus,
    rawData: found.rawData,
    tunggakanList,
    totalTunggakan,
    totalDenda,
  }
})
</script>

<style scoped>
@reference "@/assets/css/main.css";

.info-item {
  @apply flex flex-col gap-0.5 bg-slate-50 rounded-lg px-3! py-2! border border-slate-100;
}

.info-label {
  @apply text-[9px] font-bold text-slate-400 uppercase tracking-wider;
}

.info-value {
  @apply text-[12px] font-bold text-slate-800 truncate;
}
</style>
