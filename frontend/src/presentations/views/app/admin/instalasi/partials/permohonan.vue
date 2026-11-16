<template>
  <div class="max-w-6xl! mx-auto grid! grid-cols-1! lg:grid-cols-5! gap-6!">
    <div class="lg:col-span-2! flex! flex-col! gap-6!">
      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-start! gap-3!">
          <div
            class="w-12! h-12! rounded-full! flex! items-center! justify-center! text-white! text-sm! font-bold! shrink-0! shadow-sm!"
            :style="{ backgroundColor: avatarColor }"
          >
            {{ customerInitials }}
          </div>
          <div class="flex-1! min-w-0!">
            <p class="text-[10px]! font-bold! text-indigo-500! uppercase! tracking-widest!">
              Permohonan Baru
            </p>
            <h1 class="text-lg! font-bold! text-slate-800! truncate!">
              {{ customer.name }}
            </h1>
            <div class="flex! items-center! gap-1.5! text-slate-500!">
              <font-awesome-icon
                icon="map-marker-alt"
                class="text-indigo-400! text-[10px]! shrink-0!"
              />
              <p class="text-[11px]! truncate!">{{ customer.address }}, {{ customer.region }}</p>
            </div>
          </div>
          <span
            class="inline-flex! items-center! gap-1! px-2! py-0.5! rounded-full! text-[10px]! font-bold! uppercase! tracking-wider! shrink-0! bg-indigo-100! text-indigo-700!"
          >
            <span class="w-1.5! h-1.5! rounded-full! bg-current! opacity-60!"></span>
            Menunggu
          </span>
        </div>

        <div class="grid! grid-cols-2! gap-2! mt-4! pt-4! border-t! border-slate-100!">
          <div class="info-item">
            <span class="info-label">No. Induk</span>
            <span class="info-value">{{ customer.noInduk }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Tgl Order</span>
            <span class="info-value">{{ customer.tglOrder }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Paket</span>
            <span class="info-value paket">{{ customer.paket }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Abodemen</span>
            <span class="info-value">Rp {{ customer.abodemen }}</span>
          </div>
        </div>

        <div
          v-if="customer.ticketId"
          class="mt-3! pt-3! border-t! border-slate-100! flex! items-center! justify-between!"
        >
          <span class="text-[9px]! font-bold! text-slate-400! uppercase! tracking-widest!"
            >Tiket</span
          >
          <span class="text-[10px]! text-slate-600! font-mono! font-semibold!">
            {{ customer.ticketId }}
          </span>
        </div>
      </ContentCard>

      <ContentCard variant="bordered" padding="small" rounded="2xl">
        <div class="grid! grid-cols-2! gap-2!">
          <button
            @click="goBack"
            class="flex! items-center! justify-center! gap-2! bg-slate-100! hover:bg-slate-200! text-slate-600! font-semibold! py-2.5! rounded-lg! text-sm! transition-all!"
          >
            <font-awesome-icon icon="arrow-left" />
            Kembali
          </button>
          <button
            @click="handlePrint"
            class="flex! items-center! justify-center! gap-2! bg-sky-600! hover:bg-sky-700! text-white! font-semibold! py-2.5! rounded-lg! text-sm! transition-all!"
          >
            <font-awesome-icon icon="print" />
            Cetak
          </button>
        </div>
      </ContentCard>
    </div>

    <div class="lg:col-span-3! flex! flex-col! gap-6!">
      <ContentCard variant="bordered" padding="small" rounded="2xl">
        <div class="flex! items-center! justify-between! gap-1!">
          <div
            v-for="(step, idx) in steps"
            :key="step.key"
            class="flex-1! flex! flex-col! items-center! relative!"
          >
            <div
              class="w-7! h-7! rounded-full! flex! items-center! justify-center! text-white! text-[10px]! shadow-sm! z-10! transition-all!"
              :class="
                step.state === 'done'
                  ? 'bg-emerald-500!'
                  : step.state === 'current'
                    ? 'bg-sky-500! ring-2! ring-sky-100!'
                    : 'bg-slate-300!'
              "
            >
              <font-awesome-icon :icon="step.state === 'done' ? 'check' : step.icon" />
            </div>
            <p
              class="text-[9px]! font-semibold! mt-1! text-center! leading-tight!"
              :class="
                step.state === 'done'
                  ? 'text-emerald-600!'
                  : step.state === 'current'
                    ? 'text-sky-600!'
                    : 'text-slate-400!'
              "
            >
              {{ step.label }}
            </p>
            <div
              v-if="idx < steps.length - 1"
              class="absolute! top-3.5! left-1/2! w-full! h-0.5! -z-0!"
              :class="step.state === 'done' ? 'bg-emerald-400!' : 'bg-slate-200!'"
            ></div>
          </div>
        </div>
      </ContentCard>

      <ContentCard
        variant="bordered"
        padding="normal"
        rounded="2xl"
        class="stage-card flex-1! flex! flex-col!"
      >
        <div class="flex! items-center! gap-2! mb-4!">
          <div class="w-7! h-7! rounded-lg! flex! items-center! justify-center! bg-indigo-100!">
            <font-awesome-icon icon="clipboard-list" class="text-xs! text-indigo-600!" />
          </div>
          <h3 class="text-sm! font-bold! text-slate-800!">Detail Permohonan</h3>
        </div>

        <div class="grid! grid-cols-1! sm:grid-cols-2! gap-2!">
          <div class="detail-row">
            <span class="detail-label">NIK</span>
            <span class="detail-value">{{ customer.nik }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">No. Telepon</span>
            <span class="detail-value">{{ customer.phone }}</span>
          </div>
          <div class="detail-row sm:col-span-2!">
            <span class="detail-label">Alamat</span>
            <span class="detail-value">{{ customer.address }}</span>
          </div>
          <div class="detail-row sm:col-span-2!">
            <span class="detail-label">Status</span>
            <span class="detail-value text-indigo-600!">Menunggu Verifikasi</span>
          </div>
        </div>

        <div class="mt-auto! pt-4!">
          <button
            @click="handleProcess"
            :disabled="!customer.ticketId || isSubmitting"
            class="w-full! flex! items-center! justify-center! gap-2! bg-gradient-to-r! from-indigo-500! to-blue-600! hover:from-indigo-600! hover:to-blue-700! text-white! font-bold! py-2.5! rounded-xl! shadow-lg! shadow-indigo-200/50! transition-all! active:scale-95! disabled:opacity-50! disabled:cursor-not-allowed!"
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

const steps = [
  { key: 'verifikasi', label: 'Verifikasi', icon: 'clipboard-check', state: 'current' },
  { key: 'pembayaran', label: 'Pembayaran', icon: 'money-bill-wave', state: 'upcoming' },
  { key: 'pemasangan', label: 'Pemasangan', icon: 'tools', state: 'upcoming' },
]

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

const customerInitials = computed(() => {
  const name = customer.value?.name || '?'
  const parts = String(name).trim().split(/\s+/).filter(Boolean)
  if (parts.length === 0) return '?'
  if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})

const avatarColor = computed(() => {
  const palette = ['#6366f1', '#0ea5e9', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316', '#10b981']
  const name = customer.value?.name || ''
  let hash = 0
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash)
  }
  return palette[Math.abs(hash) % palette.length]
})

const goBack = () => {
  router.push({ path: '/app/instalasi/status', query: { filter: 'permohonan' } })
}

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

.detail-row {
  @apply flex items-center justify-between gap-3 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2;
}

.detail-label {
  @apply text-[10px] font-bold text-slate-400 uppercase tracking-wider shrink-0;
}

.detail-value {
  @apply text-[12px] font-bold text-slate-800 truncate text-right;
}

.stage-card :deep([class*='content-card__body']) {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
}
</style>
