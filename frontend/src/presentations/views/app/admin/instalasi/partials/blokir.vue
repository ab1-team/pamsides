<template>
  <div class="max-w-6xl! mx-auto grid! grid-cols-1! lg:grid-cols-5! gap-6!">
    <div class="lg:col-span-3! flex! flex-col! gap-6!">
      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-center! gap-3!">
          <div
            class="w-12! h-12! rounded-full! flex! items-center! justify-center! text-white! text-sm! font-bold! shrink-0! shadow-xs!"
            :style="{ backgroundColor: avatarColor }"
          >
            {{ customerInitials }}
          </div>
          <div class="flex-1! min-w-0!">
            <p class="text-[10px]! font-bold! text-orange-500! uppercase! tracking-widest!">
              Customer Diblokir
            </p>
            <h1 class="text-lg! font-bold! text-slate-800! truncate!">{{ customer.name }}</h1>
            <div class="flex! items-center! gap-1.5! text-slate-500!">
              <font-awesome-icon
                icon="map-marker-alt"
                class="text-orange-400! text-[10px]! shrink-0!"
              />
              <p class="text-[11px]! truncate!">{{ customer.address }}</p>
            </div>
          </div>
          <span
            class="inline-flex! items-center! gap-1! px-2! py-0.5! rounded-full! text-[10px]! font-bold! uppercase! tracking-wider! shrink-0! bg-orange-100! text-orange-700!"
          >
            <span class="w-1.5! h-1.5! rounded-full! bg-current! opacity-60!"></span>
            Blokir
          </span>
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
            <span class="info-label">Abodemen / bln</span>
            <span class="info-value">Rp {{ formatRibuan(customer.abodemen) }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Tgl Blokir</span>
            <span class="info-value text-orange-600!">{{ customer.tglBlokir }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">No. Telepon</span>
            <span class="info-value">{{ customer.phone }}</span>
          </div>
        </div>
      </ContentCard>

      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-center! gap-2! mb-3!">
          <div
            class="w-7! h-7! rounded-lg! bg-orange-100! flex! items-center! justify-center! shrink-0!"
          >
            <font-awesome-icon
              icon="exclamation-triangle"
              class="text-orange-600! text-xs!"
            />
          </div>
          <h3 class="text-sm! font-bold! text-slate-800!">Akun Diblokir</h3>
        </div>
        <div class="rounded-xl! bg-orange-50! border! border-orange-200! p-3! text-[11px]! text-orange-700! leading-relaxed!">
          Layanan air untuk pelanggan ini sedang terputus sementara.
        </div>
        <div class="rounded-xl! bg-emerald-50! border! border-emerald-200! p-3! mt-2! text-[11px]! text-emerald-700! leading-relaxed! flex! items-start! gap-2!">
          <font-awesome-icon icon="info-circle" class="text-emerald-600! mt-0.5! shrink-0!" />
          <div>
            <p class="font-bold! text-emerald-800! mb-0.5!">Syarat Kembali Aktif</p>
            <p>
              Status dapat dikembalikan ke <strong>Aktif</strong> setelah
              <strong>semua tunggakan</strong> pada pelanggan ini dilunasi terlebih dahulu.
            </p>
          </div>
        </div>
      </ContentCard>

      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-center! gap-2! mb-4!">
          <div class="w-7! h-7! rounded-lg! bg-rose-100! flex! items-center! justify-center!">
            <font-awesome-icon icon="file-invoice-dollar" class="text-rose-600! text-xs!" />
          </div>
          <h3 class="text-sm! font-bold! text-slate-800!">Rincian Tunggakan</h3>
        </div>

        <div class="grid! grid-cols-3! gap-2! mb-4!">
          <div class="text-center! p-3! rounded-xl! bg-rose-50! border! border-rose-100!">
            <p class="text-[9px]! font-bold! text-rose-700! uppercase! tracking-wider! mb-1!">
              Total Tunggakan
            </p>
            <p class="text-base! font-black! text-rose-700! leading-tight!">
              Rp {{ formatRibuan(customer.totalTunggakan) }}
            </p>
          </div>
          <div class="text-center! p-3! rounded-xl! bg-orange-50! border! border-orange-100!">
            <p class="text-[9px]! font-bold! text-orange-700! uppercase! tracking-wider! mb-1!">
              Bulan Menunggak
            </p>
            <p class="text-xl! font-black! text-orange-700!">{{ customer.bulanTunggakan }}</p>
            <p class="text-[9px]! text-slate-400! mt-0.5!">bulan</p>
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

        <div v-if="customer.tunggakanList.length === 0" class="text-center! py-4! text-xs! text-slate-400!">
          Tidak ada tagihan tertunggak.
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
                <div class="text-[10px]! text-slate-400!">
                  {{ formatMeter(b.usage_m3) }} m³ · abodemen Rp {{ formatRibuan(b.abodemen) }}
                  <span v-if="b.penalty_amount > 0">
                    · denda Rp {{ formatRibuan(b.penalty_amount) }}
                  </span>
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

    <div class="lg:col-span-2! flex! flex-col! gap-6!">
      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-center! gap-2! mb-3!">
          <div class="w-7! h-7! bg-orange-100! rounded-lg! flex! items-center! justify-center!">
            <font-awesome-icon icon="ban" class="text-orange-500! text-xs!" />
          </div>
          <h3 class="text-sm! font-bold! text-slate-800!">Kelola Blokir</h3>
        </div>

        <div class="space-y-3! mb-4!">
          <div>
            <label
              class="text-[10px]! font-bold! text-slate-500! uppercase! tracking-wider! block! mb-1!"
            >
              Kode Instalasi
            </label>
            <input
              type="text"
              :value="customer.kodeInstalasi"
              readonly
              class="w-full! border! border-slate-200! rounded-lg! px-3! py-2! text-xs! text-slate-700! bg-slate-50! focus:outline-hidden!"
            />
          </div>
          <div>
            <label
              class="text-[10px]! font-bold! text-slate-500! uppercase! tracking-wider! block! mb-1!"
            >
              Catatan
            </label>
            <textarea
              v-model="catatan"
              rows="3"
              placeholder="Tambah catatan blokir..."
              class="w-full! border! border-slate-200! rounded-lg! px-3! py-2! text-xs! text-slate-700! focus:outline-hidden! focus:ring-2! focus:ring-orange-300! focus:border-orange-400! transition-all! resize-none!"
            ></textarea>
          </div>
        </div>

        <div class="space-y-2!">
          <div
            v-if="customer.tunggakanList.length > 0"
            class="rounded-lg! bg-rose-50! border! border-rose-200! p-2.5! text-[10px]! text-rose-700! leading-relaxed! flex! items-start! gap-1.5!"
          >
            <font-awesome-icon icon="lock" class="text-rose-600! mt-0.5! shrink-0!" />
            <span>
              Tombol aktifkan kembali terkunci. Terdapat
              <strong>{{ customer.tunggakanList.length }} tunggakan</strong>
              yang belum dilunasi.
            </span>
          </div>
          <button
            @click="handleAktifkanKembali"
            :disabled="!customer.ticketId || customer.tunggakanList.length > 0"
            class="w-full! flex! items-center! justify-center! gap-2! bg-linear-to-r! from-emerald-500! to-green-600! hover:from-emerald-600! hover:to-green-700! text-white! font-bold! py-2.5! rounded-xl! shadow-lg! shadow-emerald-200/50! transition-all! active:scale-95! disabled:opacity-50! disabled:cursor-not-allowed!"
          >
            <font-awesome-icon icon="check-circle" />
            Aktifkan Kembali
          </button>
          <button
            @click="handleCabut"
            :disabled="!customer.ticketId"
            class="w-full! flex! items-center! justify-center! gap-2! bg-linear-to-r! from-red-500! to-rose-600! hover:from-red-600! hover:to-rose-700! text-white! font-bold! py-2.5! rounded-xl! shadow-lg! shadow-red-200/50! transition-all! active:scale-95! disabled:opacity-50! disabled:cursor-not-allowed!"
          >
            <font-awesome-icon icon="times-circle" />
            Cabut Instalasi
          </button>
          <div class="grid! grid-cols-2! gap-2! pt-1!">
            <button
              @click="handlePrint"
              class="flex! items-center! justify-center! gap-2! border! border-slate-200! hover:bg-slate-50! text-slate-600! font-semibold! py-2! rounded-lg! text-sm! transition-all!"
            >
              <font-awesome-icon icon="print" />
              Cetak
            </button>
            <button
              @click="$router.push({ path: '/app/instalasi/status', query: { filter: 'blokir' } })"
              class="flex! items-center! justify-center! gap-2! border! border-slate-200! hover:bg-slate-50! text-slate-600! font-semibold! py-2! rounded-lg! text-sm! transition-all!"
            >
              <font-awesome-icon icon="arrow-left" />
              Kembali
            </button>
          </div>
        </div>
      </ContentCard>

      <div
        class="rounded-2xl! bg-cyan-50! border! border-cyan-200! p-4! text-[11px]! text-cyan-700! leading-relaxed!"
      >
        <div class="flex! items-start! gap-2.5!">
          <font-awesome-icon icon="info-circle" class="text-cyan-500! mt-0.5! shrink-0! text-sm!" />
          <div>
            <p class="font-bold! text-cyan-800! text-xs! mb-1!">Pencatatan Meter</p>
            <p>
              Pelanggan dalam status <strong>Blokir</strong> tidak perlu dilakukan pencatatan meter.
              Meteran akan mulai dicatat kembali setelah status pelanggan berubah menjadi
              <strong>Aktif</strong>.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineOptions({ name: 'BlokirDetail' })
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Swal from 'sweetalert2'
import { useInstalasiStatus } from '@/composables/useInstalasiStatus'
import { useInstalasiActions } from '@/composables/useInstalasiActions'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'

const route = useRoute()
const router = useRouter()
const { dataMap, fetchData } = useInstalasiStatus()
const { transitionStatus, printDetail } = useInstalasiActions()
const id = decodeURIComponent(route.params.id)
const catatan = ref('')

const MONTHS_ID = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
]

const formatRibuan = (val) => {
  if (val === null || val === undefined || val === '') return '0'
  return Number(val).toLocaleString('id-ID')
}

const formatMeter = (val) => {
  const n = Number(val || 0)
  return n.toLocaleString('id-ID')
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  if (isNaN(d.getTime())) return '-'
  return `${d.getDate()} ${MONTHS_ID[d.getMonth()]} ${d.getFullYear()}`
}

const customer = computed(() => {
  const empty = {
    name: 'Pelanggan Tidak Ditemukan',
    address: 'Alamat belum tercatat',
    region: 'Wilayah belum tercatat',
    noInduk: 'Tidak tersedia',
    nik: 'Tidak tersedia',
    phone: 'Tidak tersedia',
    abodemen: 0,
    tglBlokir: 'Belum tercatat',
    kodeInstalasi: 'Tidak tersedia',
    meterAwal: 0,
    meterAkhir: 0,
    totalPemakaian: 0,
    totalTunggakan: 0,
    totalDenda: 0,
    bulanTunggakan: 0,
    tunggakanList: [],
    paket: 'Belum ada paket',
    ticketId: null,
    rawStatus: null,
  }

  const found = dataMap.value.blokir?.find((r) => r.id === id)
  if (!found) return empty

  const customerRecord = Array.isArray(found.rawData?.customer)
    ? found.rawData.customer[0]
    : found.rawData?.customer

  const meterAwal = Number(customerRecord?.initial_meter_reading || 0)

  const readingsRaw = Array.isArray(customerRecord?.meter_readings)
    ? customerRecord.meter_readings
    : []
  // Dedup by (year, month) — pertahankan 1 (yang paling akhir dicatat)
  const readingsMap = new Map()
  for (const r of readingsRaw) {
    const key = `${r.reading_year}-${r.reading_month}`
    const prev = readingsMap.get(key)
    if (
      !prev ||
      new Date(r.recorded_at || r.created_at || 0).getTime() >
        new Date(prev.recorded_at || prev.created_at || 0).getTime()
    ) {
      readingsMap.set(key, r)
    }
  }
  const readings = Array.from(readingsMap.values())
    .map((r) => ({
      year: Number(r.reading_year),
      month: Number(r.reading_month),
      meter_value: Number(r.meter_value || 0),
    }))
    .sort((a, b) => (a.year - b.year) * 12 + (a.month - b.month))
  const lastReading = readings[readings.length - 1]
  const meterAkhir = lastReading ? lastReading.meter_value : meterAwal
  const totalPemakaian = Math.max(0, meterAkhir - meterAwal)

  // Hitung tunggakan dari monthly_bills (dedup by period — pertahankan 1 per periode)
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
      usage_m3: Number(b.usage_m3 || 0),
      abodemen: Number(b.abodemen || 0),
      penalty_amount: Number(b.penalty_amount || 0),
      total_amount: Number(b.total_amount || 0),
      due_date: formatDate(b.due_date),
    }
  })

  return {
    name: found.name || 'Pelanggan Tidak Ditemukan',
    address: found.address || 'Alamat belum tercatat',
    region: found.village || 'Wilayah belum tercatat',
    noInduk: found.id || 'Tidak tersedia',
    nik: found.nik || 'Tidak tersedia',
    phone: found.phone || 'Tidak tersedia',
    abodemen: found.rawData?.package?.monthly_abodemen || 0,
    tglBlokir: formatDate(found.updatedAt) || 'Belum tercatat',
    kodeInstalasi: found.id || 'Tidak tersedia',
    meterAwal,
    meterAkhir,
    totalPemakaian,
    totalTunggakan,
    totalDenda,
    bulanTunggakan: unpaidBills.length,
    tunggakanList,
    paket: found.type || 'Belum ada paket',
    ticketId: found.ticketId,
    rawStatus: found.rawStatus,
    rawData: found.rawData,
  }
})

const customerInitials = computed(() => {
  const name = customer.value?.name || '?'
  const parts = String(name).trim().split(/\s+/).filter(Boolean)
  if (parts.length === 0) return '?'
  if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})

const avatarColor = computed(() => {
  const palette = ['#f97316', '#ea580c', '#dc2626', '#0ea5e9', '#6366f1', '#8b5cf6', '#14b8a6']
  const name = customer.value?.name || ''
  let hash = 0
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash)
  }
  return palette[Math.abs(hash) % palette.length]
})

const handleAktifkanKembali = async () => {
  if (!customer.value.ticketId) return
  if (customer.value.tunggakanList.length > 0) {
    await Swal.fire({
      title: 'Tunggakan Belum Lunas',
      html: `
        <div style="text-align:left; font-size:13px; color:#475569;">
          <p>Tidak dapat mengaktifkan kembali layanan. Syarat kembali aktif adalah
          <strong style="color:#dc2626;">melunasi semua tunggakan</strong> terlebih dahulu.</p>
          <p style="margin-top:8px;">Sisa tunggakan:
            <strong style="color:#dc2626;">${customer.value.tunggakanList.length} periode</strong>
            (Rp ${formatRibuan(customer.value.totalTunggakan + customer.value.totalDenda)}).
          </p>
        </div>
      `,
      icon: 'warning',
      confirmButtonColor: '#3b82f6',
      confirmButtonText: 'Mengerti',
    })
    return
  }
  const kodeInstalasi = customer.value.kodeInstalasi
  const result = await transitionStatus(
    customer.value.ticketId,
    'completed',
    `Aktifkan kembali layanan untuk pelanggan "${customer.value.name}"?`,
  )
  if (result.success) {
    await fetchData()
    router.push({
      path: `/app/instalasi/status/aktif/${encodeURIComponent(kodeInstalasi)}`,
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
      path: `/app/instalasi/status/cabut/${encodeURIComponent(kodeInstalasi)}`,
    })
  }
}

const handlePrint = () => {
  printDetail({ ...customer.value, tglOrder: customer.value.tglBlokir }, 'Blokir')
}

onMounted(async () => {
  await fetchData()
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
