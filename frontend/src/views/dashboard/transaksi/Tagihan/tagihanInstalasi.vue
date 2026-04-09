<template>
  <ContentCard variant="elevated" padding="large" hoverable>
    <div
      class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4! mb-6! lg:mb-8!"
    >
      <div class="flex-1!">
        <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2!">
          BILLING STATEMENT
        </div>
        <div
          class="text-2xl! sm:text-3xl! lg:text-4xl! font-black text-slate-900 tracking-tight leading-tight"
        >
          {{ billingStore.selectedCustomer?.name || 'Budi Darmawan' }}
        </div>
        <div
          class="flex flex-col sm:flex-row sm:items-center gap-2! sm:gap-3! mt-2! text-sm! text-slate-600 font-medium"
        >
          <div class="flex items-center gap-2">
            <span class="text-slate-500">ID:</span>
            <span class="font-bold text-slate-800">{{
              billingStore.selectedCustomer?.id || '#MA-882109'
            }}</span>
          </div>
          <div class="hidden sm:block text-slate-300">•</div>
          <div class="flex items-center gap-2">
            <span class="text-slate-500">Zone:</span>
            <span class="font-bold text-slate-800">{{
              billingStore.selectedCustomer?.zone || 'Southern Spring'
            }}</span>
          </div>
        </div>
      </div>
      <div class="text-left! lg:text-right! w-full! lg:w-auto!">
        <BaseButton
          variant="info"
          class="px-5! py-2.5! rounded-full! shadow-lg! hover:shadow-xl! transition-all! font-bold! tracking-wide!"
        >
          <span>💧</span> Pelunasan Instalasi
        </BaseButton>

        <div class="text-xs! text-slate-500! mt-2!">
          Last synced: Today,
          {{ new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1! xl:grid-cols-3! gap-4! lg:gap-6! mb-6!">
      <div class="xl:col-span-2">
        <div class="bg-white rounded-xl p-6! border border-gray-200">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4! mb-4!">
            <div class="flex items-center gap-3!">
              <div class="w-10! h-10! bg-cyan-100 rounded-xl flex items-center justify-center">
                <font-awesome-icon icon="info-circle" class="text-cyan-600" />
              </div>
              <h3 class="text-base font-bold text-slate-800">Informasi Harga Paket</h3>
            </div>
          </div>

          <div class="mb-4!">
            <SelectSearch
              v-model="billingStore.selectedCustomer"
              :options="pelangganList"
              label="Pilih Pelanggan"
              placeholder="Cari nama pelanggan, ID, atau kode instalasi..."
              label-key="name"
              value-key="id"
              icon="user"
              class="w-full"
              @change="selectPelanggan"
            />
          </div>

          <div class="bg-white/80 backdrop-blur-sm rounded-xl p-4! border border-cyan-100">
            <h4 class="text-sm font-semibold text-cyan-800 mb-3!">📋 Informasi Harga Paket</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
              <div class="flex items-center gap-2! bg-cyan-50 rounded-lg p-2!">
                <div class="w-3! h-3! bg-cyan-500 rounded-full"></div>
                <span class="text-slate-700 font-medium">Paket Dasar</span>
                <span class="text-cyan-600 font-bold ml-auto">Rp 50.000</span>
              </div>
              <div class="flex items-center gap-2! bg-cyan-50 rounded-lg p-2!">
                <div class="w-3! h-3! bg-cyan-500 rounded-full"></div>
                <span class="text-slate-700 font-medium">Paket Silver</span>
                <span class="text-cyan-600 font-bold ml-auto">Rp 75.000</span>
              </div>
              <div class="flex items-center gap-2! bg-cyan-50 rounded-lg p-2!">
                <div class="w-3! h-3! bg-cyan-500 rounded-full"></div>
                <span class="text-slate-700 font-medium">Paket Gold</span>
                <span class="text-cyan-600 font-bold ml-auto">Rp 100.000</span>
              </div>
              <div class="flex items-center gap-2! bg-cyan-50 rounded-lg p-2!">
                <div class="w-3! h-3! bg-cyan-500 rounded-full"></div>
                <span class="text-slate-700 font-medium">Platinum</span>
                <span class="text-cyan-600 font-bold ml-auto">Rp 150.000</span>
              </div>
            </div>
            <p class="text-xs text-slate-500 mt-3! italic">
              *Harga dapat berubah sesuai lokasi dan ketentuan
            </p>
          </div>
        </div>
      </div>

      <div class="xl:col-span-1!">
        <div class="bill-card">
          <div
            class="text-xs text-white/60 font-semibold uppercase tracking-wider mb-3! flex items-center gap-2!"
          >
            <div class="w-2! h-2! bg-cyan-400 rounded-full animate-pulse"></div>
            Total Bill to Pay
          </div>
          <div
            class="text-2xl! sm:text-3xl! font-black text-white tracking-tight leading-tight mb-4!"
          >
            Rp {{ formatBillAmount(grandTotal) }}
          </div>

          <div class="text-xs text-white/70 mb-3!">
            Tanggal Transaksi:
            {{
              new Date().toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
              })
            }}
          </div>

          <div class="flex items-center justify-between mb-4!">
            <div class="text-xs text-white/50 font-medium">
              <span class="inline-flex items-center gap-1!">
                <svg class="w-3! h-3!" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                  />
                </svg>
                Due Date
              </span>
            </div>
            <div class="text-xs text-white/80 font-bold">15 May 2025</div>
          </div>
          <div class="px-3! py-1.5! bg-emerald-500/20 border border-emerald-400/30 rounded-full">
            <span
              class="text-xs text-emerald-300 font-semibold tracking-wide flex items-center gap-1!"
            >
              <div class="w-1.5! h-1.5! bg-emerald-400 rounded-full animate-pulse"></div>
              ACTIVE
            </span>
          </div>

          <div class="mt-3! text-xs text-white/70">
            <div class="font-medium mb-1!">Rincian Pembayaran:</div>
            <ul class="space-y-1!">
              <li v-for="item in billingItems" :key="item.id" class="flex justify-between">
                <span class="truncate flex-1! mr-2!">{{ item.name }}</span>
                <span class="font-mono whitespace-nowrap">{{
                  billingStore.formatAmount(item.subtotal)
                }}</span>
              </li>
            </ul>
          </div>
        </div>
        <BaseButton
          variant="secondary"
          class="mt-4! p-4! rounded-xl font-bold w-full shadow-lg shadow-cyan-200/50"
          @click="handlePayNow"
          icon="credit-card"
        >
          Bayar Sekarang
        </BaseButton>
      </div>
    </div>

    <div class="bg-white rounded-xl p-6! border border-gray-200">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4! mb-4!">
        <div class="flex items-center gap-3!">
          <div class="w-8 h-8 bg-slate-100 rounded-xl flex items-center justify-center">
            <font-awesome-icon icon="file-invoice" class="text-slate-600 text-sm" />
          </div>
          <h3 class="text-base font-bold text-slate-800">Detail Pembayaran</h3>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center gap-2!">
          <label class="text-sm font-medium text-slate-600 whitespace-nowrap"
            >Tanggal Transaksi</label
          >
          <AppDatePicker
            v-model="tanggalTransaksi"
            placeholder="Pilih tanggal transaksi"
            @date-select="(date) => (tanggalTransaksi = date)"
            class="w-full sm:w-auto"
          />
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full border-collapse min-w-[400px]!">
          <thead>
            <tr class="border-b-2! border-slate-200">
              <th
                class="px-4! py-3! text-xs font-bold text-slate-600 uppercase tracking-wide text-left"
              >
                Deskripsi
              </th>
              <th
                class="px-4! py-3! text-xs font-bold text-slate-600 uppercase tracking-wide text-right"
              >
                Jumlah
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in billingItems"
              :key="item.id"
              class="border-b border-slate-100 hover:bg-slate-50 transition-colors"
            >
              <td class="px-4! py-2!">
                <div class="font-semibold text-slate-800">{{ item.name }}</div>
              </td>
              <td class="px-4! py-2! text-right">
                <MaksMoneyInput
                  v-model="item.subtotal"
                  placeholder="0,00"
                  :show-helper="false"
                  class="text-right font-bold text-cyan-600"
                  @change="updateSubtotal(item.id, $event)"
                />
              </td>
            </tr>
            <tr class="border-t-2 border-slate-300 bg-gradient-to-r from-slate-100 to-slate-50">
              <td class="px-4! py-2! text-right">
                <span class="text-sm font-bold text-slate-700 uppercase tracking-wide">
                  Total Pembayaran
                </span>
              </td>
              <td class="px-4! py-2! text-right">
                <span class="text-lg sm:text-xl font-black text-cyan-800">
                  {{ billingStore.formatAmount(grandTotal) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </ContentCard>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useBillingStore } from '../../../../stores/billingStore.js'
import SelectSearch from '@/components/SelectSearch.vue'
import MaksMoneyInput from '@/components/MaksMoneyInput.vue'
import AppDatePicker from '@/components/AppDatePicker.vue'
import ContentCard from '@/components/ui/ContentCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const billingStore = useBillingStore()

onMounted(() => {
  billingStore.initializeStore()
})

const currentPeriod = computed(() => billingStore.currentPeriod)

const billingItems = computed(() => {
  const period = currentPeriod.value
  const pemakaian = period?.pemakaian || 23.25
  const ratePerM3 = 15000
  const maintenanceFee = 45000
  const adminFee = 64450

  return [
    {
      id: 1,
      name: 'Biaya sudah dibayar',
      subtotal: pemakaian * ratePerM3,
    },
    {
      id: 2,
      name: 'Tagihan',
      subtotal: maintenanceFee,
    },
    {
      id: 3,
      name: 'Pembayaran',
      subtotal: adminFee,
    },
  ]
})

const grandTotal = computed(() => billingItems.value.reduce((sum, item) => sum + item.subtotal, 0))

// Removed unused selectedPaket, paketSearchQuery, detailInstalasi
const pelangganSearchQuery = ref('')
const filteredPelangganList = ref([])

const tanggalTransaksi = ref(new Date().toISOString().split('T')[0])

const pelangganList = [
  { id: 'PAM-2025-09821', name: 'Bambang Susanto', installationCode: 'RT04-RW02', status: 'AKTIF' },
  { id: 'PAM-2025-09822', name: 'Siti Aminah', installationCode: 'RT03-RW01', status: 'AKTIF' },
  { id: 'PAM-2025-09823', name: 'Ahmad Wijaya', installationCode: 'RT05-RW02', status: 'AKTIF' },
  { id: 'PAM-2025-09824', name: 'Dewi Lestari', installationCode: 'RT02-RW01', status: 'AKTIF' },
  { id: 'PAM-2025-09825', name: 'Rudi Hartono', installationCode: 'RT06-RW03', status: 'AKTIF' },
]

filteredPelangganList.value = []

const updateSubtotal = (itemId, newSubtotal) => {
  const item = billingItems.value.find((item) => item.id === itemId)
  if (item) {
    item.subtotal = newSubtotal
    console.log(`Updated subtotal for item ${itemId}:`, newSubtotal)
  }
}

const selectPelanggan = (pelanggan) => {
  pelangganSearchQuery.value = pelanggan.name
  filteredPelangganList.value = []
  billingStore.selectedCustomer = pelanggan
  console.log('Pelanggan selected:', pelanggan)
}

// Remove unused resetDetailInstalasi

// Remove unused saveDetailInstalasi

// Remove unused formatRupiah

const formatBillAmount = (val) => {
  return Number(val).toLocaleString('id-ID')
}

// Remove unused handlePaketChange

// Remove unused filterPaketList

const handlePayNow = () => {
  console.log('Pay now clicked')
}
</script>

<style scoped>
.bill-card {
  background: linear-gradient(to bottom right, #0284c7, #0c4a6e);
  border-radius: 1rem;
  padding: 1.25rem 1.5rem;
  color: white;
  position: relative;
  overflow: hidden;
  box-shadow:
    0 10px 15px -3px rgba(0, 0, 0, 0.1),
    0 4px 6px -2px rgba(0, 0, 0, 0.05);
  transition: all 0.2s ease;
}

.bill-card::before {
  content: '';
  position: absolute;
  top: -2rem;
  right: -2rem;
  width: 6rem;
  height: 6rem;
  background: linear-gradient(to bottom right, rgba(255, 255, 255, 0.1), transparent);
  border-radius: 50%;
}

.bill-card:hover {
  transform: translateY(-2px);
  box-shadow:
    0 20px 25px -5px rgba(0, 0, 0, 0.1),
    0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
</style>
