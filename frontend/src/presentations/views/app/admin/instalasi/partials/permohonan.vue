<template>
  <div class="max-w-6xl! mx-auto">
    <ContentCard variant="bordered" padding="normal" rounded="2xl">
      <div class="flex! items-center! justify-between! mb-3!">
        <p class="text-[10px]! font-bold! text-indigo-500! uppercase! tracking-widest!">
          Permohonan Baru
        </p>
        <span
          class="inline-flex! items-center! gap-1! px-2.5! py-1! rounded-full! text-[10px]! font-bold! uppercase! tracking-wider! bg-indigo-100! text-indigo-700!"
        >
          <span class="w-1.5! h-1.5! rounded-full! bg-current! opacity-60!"></span>
          Menunggu
        </span>
      </div>
      <h1 class="text-lg! font-bold! text-slate-800! truncate! mb-1!">{{ customer.name }}</h1>
      <div class="flex! items-center! gap-1.5! text-slate-500!">
        <font-awesome-icon icon="map-marker-alt" class="text-indigo-400! text-[10px]! shrink-0!" />
        <p class="text-[11px]! truncate!">{{ customer.address }}, {{ customer.region }}</p>
      </div>

      <div class="grid! grid-cols-2! lg:grid-cols-3! gap-2! mt-4! pt-4! border-t! border-slate-100!">
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
          <span class="info-label">Abodemen</span>
          <span class="info-value">Rp {{ customer.abodemen }}</span>
        </div>
        <div class="info-item">
          <span class="info-label">Tgl Order</span>
          <span class="info-value">{{ customer.tglOrder }}</span>
        </div>
        <div class="info-item">
          <span class="info-label">No. Telepon</span>
          <span class="info-value">{{ customer.phone }}</span>
        </div>
      </div>

      <div class="grid! grid-cols-3! gap-2! mt-4!">
        <button
          @click="$router.push({ path: '/app/instalasi/status', query: { filter: 'permohonan' } })"
          class="flex! items-center! justify-center! gap-1.5! border! border-slate-200! hover:bg-slate-50! text-slate-600! font-semibold! py-2.5! rounded-xl! text-[11px]! sm:text-sm! transition-all! bg-white! whitespace-nowrap!"
        >
          <font-awesome-icon icon="arrow-left" />
          Kembali
        </button>
        <button
          @click="handlePrint"
          class="flex! items-center! justify-center! gap-1.5! bg-red-600! hover:bg-red-700! text-white! font-semibold! py-2.5! rounded-xl! text-[11px]! sm:text-sm! transition-all! whitespace-nowrap!"
        >
          <font-awesome-icon icon="print" />
          Cetak
        </button>
        <button
          @click="handleProcess"
          :disabled="!customer.ticketId || isSubmitting"
          class="flex! items-center! justify-center! gap-1.5! bg-gradient-to-r! from-indigo-500! to-blue-600! hover:from-indigo-600! hover:to-blue-700! text-white! font-bold! py-2.5! rounded-xl! shadow-lg! shadow-indigo-200/50! transition-all! active:scale-95! disabled:opacity-50! disabled:cursor-not-allowed! text-[11px]! sm:text-sm! whitespace-nowrap!"
        >
          <font-awesome-icon
            :icon="isSubmitting ? 'spinner' : 'arrow-right'"
            :class="isSubmitting ? 'animate-spin!' : ''"
          />
          {{ isSubmitting ? 'Memproses...' : 'Proses ke Pasang Baru' }}
        </button>
      </div>
    </ContentCard>
  </div>
</template>

<script setup>
defineOptions({ name: 'PermohonanDetail' })
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useInstalasiStatus } from '@/composables/useInstalasiStatus'
import { useInstalasiActions } from '@/composables/useInstalasiActions'
import { useUiStore } from '@/stores/uiStore'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import ticketService from '@/services/ticket.service'

const route = useRoute()
const router = useRouter()
const uiStore = useUiStore()
const { dataMap, fetchData } = useInstalasiStatus()
const { printDetail } = useInstalasiActions()

const decodeId = (raw) => {
  let prev = raw
  let curr = raw
  for (let i = 0; i < 3; i++) {
    try {
      curr = decodeURIComponent(curr)
    } catch {
      break
    }
    if (curr === prev) break
    prev = curr
  }
  return curr
}
const id = decodeId(String(route.params.id))

const isSubmitting = ref(false)

const customer = computed(() => {
  const empty = {
    name: 'Pelanggan Tidak Ditemukan',
    address: 'Alamat belum tercatat',
    region: 'Wilayah belum tercatat',
    noInduk: 'Tidak tersedia',
    nik: 'Tidak tersedia',
    phone: 'Tidak tersedia',
    abodemen: '0',
    tglOrder: 'Belum tercatat',
    paket: 'Belum ada paket',
    kodeInstalasi: 'Tidak tersedia',
    isPaid: false,
    ticketId: null,
    rawStatus: null,
  }

  const found = dataMap.value.permohonan?.find((r) => r.id === id || String(r.ticketId) === id)
  if (!found) return empty
  return {
    name: found.name || 'Pelanggan Tidak Ditemukan',
    address: found.address || 'Alamat belum tercatat',
    region: found.village || 'Wilayah belum tercatat',
    noInduk: found.id || 'Tidak tersedia',
    nik: found.nik || 'Tidak tersedia',
    phone: found.phone || 'Tidak tersedia',
    abodemen: found.rawData?.package?.installation_fee || '0',
    tglOrder: found.orderDate || found.createdAt || 'Belum tercatat',
    paket: found.type || 'Belum ada paket',
    kodeInstalasi: found.id || 'Tidak tersedia',
    isPaid:
      found.rawStatus === 'unpaid' ||
      found.rawStatus === 'processing' ||
      found.rawStatus === 'completed',
    ticketId: found.ticketId,
    rawStatus: found.rawStatus,
    rawData: found.rawData,
  }
})

const handleProcess = async () => {
  if (!customer.value.ticketId || isSubmitting.value) return

  try {
    isSubmitting.value = true
    uiStore.setLoading(true)

    await ticketService.transitionStatus(customer.value.ticketId, 'surveyed')
    uiStore.success('Permohonan berhasil diproses ke Pasang Baru.')

    const kodeInstalasi = customer.value.kodeInstalasi || String(customer.value.ticketId)
    await fetchData()
    router.push({
      path: `/app/instalasi/status/pasang-baru/${encodeURIComponent(kodeInstalasi)}`,
    })
  } catch (err) {
    uiStore.error(err?.response?.data?.message || 'Gagal memproses permohonan.')
  } finally {
    isSubmitting.value = false
    uiStore.setLoading(false)
  }
}

const handlePrint = () => {
  printDetail(customer.value, 'Permohonan')
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
