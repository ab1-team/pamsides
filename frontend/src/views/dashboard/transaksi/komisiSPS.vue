<template>
  <div>
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-4 lg:gap-5 items-start">
      <ContentCard variant="elevated" padding="large" hoverable>
        <div class="flex items-center gap-3 mb-6">
          <div class="w-9 h-9 bg-blue-400 rounded-full flex items-center justify-center shrink-0">
            <span class="text-lg">💧</span>
          </div>
          <h1 class="text-base font-bold text-gray-800">Transaksi Komisi SPS</h1>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div class="flex flex-col gap-0.5">
            <label class="text-sm mb-1">Tanggal Transaksi</label>
            <AppDatePicker
              v-model="form.tanggal"
              placeholder="Pilih tanggal transaksi"
              @date-select="(date) => (form.tanggal = date)"
            />
          </div>

          <div class="flex flex-col gap-0.5">
            <SelectSearch
              v-model="form.petugas"
              :options="[
                { id: '', text: 'Pilih Petugas Lapangan' },
                { id: 1, text: 'Petugas A' },
                { id: 2, text: 'Petugas B' },
                { id: 3, text: 'Petugas C' },
              ]"
              label="Utang Komisi"
              placeholder="Pilih Petugas Lapangan"
              icon="user"
            />
          </div>
        </div>

        <div class="flex flex-col gap-0.5 mb-4">
          <label class="text-sm mb-1">Relasi / Nama Pembayar</label>
          <input
            v-model="form.namaPembayar"
            type="text"
            placeholder="Masukkan nama instansi atau perorangan"
            class="px-3 py-2 sm:py-2.5 border border-gray-300 bg-white text-sm text-gray-900 w-full rounded-md shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 placeholder:text-gray-400"
          />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div class="flex flex-col gap-0.5">
            <label class="text-sm mb-1">Nominal</label>
            <MaksMoneyInput
              v-model="form.nominal"
              placeholder="0,00"
              :show-helper="true"
              helper-text="Contoh: 100.000,00 untuk 100 ribu"
            />
          </div>

          <div class="flex flex-col gap-0.5">
            <SelectSearch
              v-model="form.metode"
              :options="[
                { id: '', text: 'Pilih Metode Pembayaran' },
                { id: 1, text: 'Tunai' },
                { id: 2, text: 'Transfer Bank' },
                { id: 3, text: 'QRIS' },
              ]"
              label="Metode Pembayaran"
              placeholder="Pilih Pembayaran"
              icon="credit-card"
            />
          </div>
        </div>

        <div class="flex">
          <button
            @click="simpanTransaksi"
            :disabled="isProcessing"
            class="ml-auto px-6 sm:px-8 py-2 bg-linear-to-r from-gray-500 to-gray-600 text-white rounded-lg font-semibold transition-all hover:from-gray-600 hover:to-gray-700 hover:shadow-lg hover:-translate-y-0.5 text-sm sm:text-base disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:transform-none disabled:hover:shadow-none"
          >
            {{ isProcessing ? 'Menyimpan...' : 'Simpan Komisi SPS' }}
          </button>
        </div>
      </ContentCard>

      <div class="flex flex-col gap-4 lg:sticky lg:top-8">
        <ContentCard variant="minimal" padding="normal" hoverable>
          <div class="flex gap-3 items-start">
            <div class="text-base shrink-0 mt-0.5">ℹ️</div>
            <div class="text-xs text-slate-600 leading-relaxed">
              <strong>Bantuan Komisi SPS</strong><br />
              Pastikan data petugas dan nominal komisi sudah benar sebelum menyimpan transaksi.
            </div>
          </div>
        </ContentCard>
      </div>
    </div>

    <NotificationDialog
      v-bind="notificationState"
      @close="() => {}"
      @confirm="() => {}"
      @cancel="() => {}"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useNotification } from '@/composables/useNotification'
import NotificationDialog from '@/components/ui/NotificationDialog.vue'
import SelectSearch from '@/components/SelectSearch.vue'
import AppDatePicker from '@/components/AppDatePicker.vue'
import MaksMoneyInput from '@/components/MaksMoneyInput.vue'
import ContentCard from '@/components/ui/ContentCard.vue'

const { notificationState, success, error } = useNotification()

const form = ref({
  tanggal: new Date(),
  petugas: '',
  namaPembayar: '',
  nominal: null,
  metode: '',
})
const isProcessing = ref(false)

const simpanTransaksi = async () => {
  if (
    !form.value.tanggal ||
    !form.value.petugas ||
    !form.value.namaPembayar ||
    !form.value.nominal ||
    !form.value.metode
  ) {
    error('Tidak Valid', 'Harap lengkapi semua field sebelum menyimpan.')
    return
  }

  try {
    isProcessing.value = true
    await new Promise((resolve) => setTimeout(resolve, 800))
    success('Berhasil!', 'Transaksi komisi SPS berhasil disimpan.')
  } catch (err) {
    console.error('Error menyimpan transaksi:', err)
    error('Kesalahan', 'Gagal menyimpan transaksi.')
  } finally {
    isProcessing.value = false
  }
}
</script>
