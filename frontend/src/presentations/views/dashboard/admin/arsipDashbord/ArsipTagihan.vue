<template>
  <div class="p-6! space-y-6! bg-white! text-slate-700!">
    <!-- Blue Info Banner -->
    <div class="bg-blue-50! border! border-blue-200! rounded-xl! p-4! flex! items-start! gap-3! shadow-sm!">
      <div class="text-blue-500! mt-0.5! shrink-0!">
        <font-awesome-icon icon="info-circle" class="text-lg!" />
      </div>
      <span class="text-sm! text-blue-700! font-semibold! leading-relaxed!">
        Kirim Whatsapp Tagihan berdasarkan tanggal jatuh tempo. Pastikan nomor HP Pelanggan dapat menerima pesan Whatsapp.
      </span>
    </div>

    <!-- Date Fields Row -->
    <div class="grid! grid-cols-1! md:grid-cols-2! gap-6!">
      <!-- Tgl Jatuh Tempo -->
      <div class="flex! flex-col! gap-2!">
        <label class="text-xs! font-bold! text-slate-500! uppercase! tracking-wider!">Tgl Jatuh Tempo</label>
        <input
          v-model="waJatuhTempo"
          type="date"
          class="w-full! bg-slate-50! border! border-slate-200! rounded-xl! px-4! py-3! text-sm! text-slate-800! outline-none! focus:border-emerald-600! focus:bg-white! transition-all!"
        />
      </div>

      <!-- Tgl Pembayaran -->
      <div class="flex! flex-col! gap-2!">
        <label class="text-xs! font-bold! text-slate-500! uppercase! tracking-wider!">Tgl Pembayaran</label>
        <input
          v-model="waTglPembayaran"
          type="date"
          class="w-full! bg-slate-50! border! border-slate-200! rounded-xl! px-4! py-3! text-sm! text-slate-800! outline-none! focus:border-emerald-600! focus:bg-white! transition-all!"
        />
      </div>
    </div>

    <!-- Preview Action -->
    <div class="flex! justify-end!">
      <button
        @click="handlePreviewWA"
        class="px-6! py-2.5! bg-slate-800! text-white! font-bold! text-sm! uppercase! tracking-wider! rounded-xl! hover:bg-slate-900! transition-colors! shadow-md!"
      >
        PREVIEW
      </button>
    </div>

    <!-- Preview Data Table -->
    <div v-if="showWAPreview" class="mt-6! border-t! border-slate-100! pt-6! animate-[fade-in-up_0.3s_ease-out_forwards]!">
      <div class="flex! items-center! justify-between! mb-4!">
        <h4 class="text-xs! font-bold! text-slate-500! uppercase! tracking-wider!">Hasil Preview Tagihan</h4>
        <div class="text-xs! text-slate-600! font-semibold!">
          Terpilih: <strong class="text-emerald-600!">{{ selectedWARows.length }}</strong> dari <strong>{{ mockWADataset.length }}</strong> pelanggan
        </div>
      </div>

      <div class="border! border-slate-100! rounded-xl! overflow-hidden! shadow-sm!">
        <table class="w-full! text-left! border-collapse!">
          <thead class="bg-slate-50! border-b! border-slate-200!">
            <tr>
              <th class="px-4! py-3! w-12! text-center!">
                <input
                  type="checkbox"
                  :checked="isAllWASelected"
                  @change="toggleSelectAllWA"
                  class="w-4! h-4! rounded! border-slate-300! text-emerald-600! focus:ring-emerald-500! cursor-pointer!"
                />
              </th>
              <th class="px-4! py-3! text-xs! font-bold! text-slate-400! uppercase! tracking-wider!">ID Pelanggan</th>
              <th class="px-4! py-3! text-xs! font-bold! text-slate-400! uppercase! tracking-wider!">Nama Pelanggan</th>
              <th class="px-4! py-3! text-xs! font-bold! text-slate-400! uppercase! tracking-wider!">No. HP</th>
              <th class="px-4! py-3! text-xs! font-bold! text-slate-400! uppercase! tracking-wider!">Jumlah Tagihan</th>
              <th class="px-4! py-3! text-xs! font-bold! text-slate-400! uppercase! tracking-wider!">Jatuh Tempo</th>
            </tr>
          </thead>
          <tbody class="divide-y! divide-slate-100! bg-white!">
            <tr
              v-for="row in mockWADataset"
              :key="row.id"
              class="hover:bg-slate-50! transition-colors! cursor-pointer!"
              @click="toggleSelectWARow(row)"
            >
              <td class="px-4! py-3! text-center!" @click.stop>
                <input
                  type="checkbox"
                  :checked="isWARowSelected(row)"
                  @change="toggleSelectWARow(row)"
                  class="w-4! h-4! rounded! border-slate-300! text-emerald-600! focus:ring-emerald-500! cursor-pointer!"
                />
              </td>
              <td class="px-4! py-3! text-sm! font-semibold! text-slate-700!">{{ row.customerId }}</td>
              <td class="px-4! py-3! text-sm! font-semibold! text-slate-800!">{{ row.name }}</td>
              <td class="px-4! py-3! text-sm! text-slate-600! font-mono!">{{ row.phone }}</td>
              <td class="px-4! py-3! text-sm! font-bold! text-emerald-600!">{{ formatCurrency(row.amount) }}</td>
              <td class="px-4! py-3! text-sm! text-slate-500!">{{ row.dueDate }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Custom Footer aligned to Bottom Right inside ArsipTagihan.vue -->
    <div v-if="showWAPreview" class="pt-6! border-t! border-slate-100! flex! items-center! justify-end! gap-3!">
      <button
        @click="$emit('close')"
        class="px-5! py-2.5! text-sm! font-bold! text-slate-500! bg-white! border! border-slate-200! rounded-xl! hover:bg-slate-100! transition-all! shadow-sm!"
      >
        Tutup
      </button>
      <button
        :disabled="!showWAPreview || selectedWARows.length === 0"
        @click="sendWhatsAppMessages"
        class="px-6! py-2.5! text-sm! font-bold! text-white! bg-emerald-600! border! border-emerald-700! rounded-xl! hover:bg-emerald-700! transition-colors! disabled:opacity-50! disabled:cursor-not-allowed! flex! items-center! gap-2! shadow-md!"
      >
        <font-awesome-icon icon="paper-plane" />
        Kirim Pesan
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Toast } from '@/utils/swal.js'

defineEmits(['close'])

const waJatuhTempo = ref('2026-05-08')
const waTglPembayaran = ref('2026-05-08')
const showWAPreview = ref(false)
const selectedWARows = ref([])

const mockWADataset = ref([
  { id: 1, customerId: 'PAM-00001', name: 'Ahmad Faisal', phone: '081234567890', amount: 64000, dueDate: '08/05/2026' },
  { id: 2, customerId: 'PAM-00002', name: 'Siti Rahma', phone: '081234567891', amount: 82000, dueDate: '08/05/2026' },
  { id: 3, customerId: 'PAM-00003', name: 'Bambang Triyono', phone: '081234567892', amount: 55000, dueDate: '08/05/2026' },
  { id: 4, customerId: 'PAM-00004', name: 'Dewi Lestari', phone: '081234567893', amount: 110000, dueDate: '08/05/2026' },
  { id: 5, customerId: 'PAM-00005', name: 'Rian Hidayat', phone: '081234567894', amount: 45000, dueDate: '08/05/2026' },
  { id: 6, customerId: 'PAM-00006', name: 'Lia Anisa', phone: '081234567895', amount: 95000, dueDate: '08/05/2026' },
])

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(amount)
}

const handlePreviewWA = () => {
  showWAPreview.value = true
  selectedWARows.value = [...mockWADataset.value]
}

const isWARowSelected = (row) => {
  return selectedWARows.value.some((r) => r.id === row.id)
}

const toggleSelectWARow = (row) => {
  const index = selectedWARows.value.findIndex((r) => r.id === row.id)
  if (index > -1) {
    selectedWARows.value.splice(index, 1)
  } else {
    selectedWARows.value.push(row)
  }
}

const isAllWASelected = computed(() => {
  return selectedWARows.value.length === mockWADataset.value.length
})

const toggleSelectAllWA = () => {
  if (isAllWASelected.value) {
    selectedWARows.value = []
  } else {
    selectedWARows.value = [...mockWADataset.value]
  }
}

const sendWhatsAppMessages = () => {
  Toast.fire({
    icon: 'success',
    title: `Pesan WhatsApp berhasil dikirim ke ${selectedWARows.value.length} pelanggan!`
  })
  showWAPreview.value = false
}
</script>
