<template>
  <div>
    <div class="grid! grid-cols-1! lg:grid-cols-[1fr_320px]! gap-4! lg:gap-5! items-start!">
      <ContentCard variant="elevated" padding="large" hoverable>
        <div class="flex! items-center! gap-3! mb-2!">
          <div
            class="w-9! h-9! bg-blue-400! rounded-full! flex! items-center! justify-center! flex-shrink-0!"
          >
            <span class="text-lg!">💧</span>
          </div>
          <h1 class="text-base! font-bold! text-gray-800!">Transaksi Komisi SPS</h1>
        </div>

        <div class="grid! grid-cols-1! md:grid-cols-2! gap-4! mb-2!">
          <div class="flex! flex-col! gap-0.5!">
            <AppDatePicker
              v-model="form.tanggal"
              label="Tanggal Transaksi"
              placeholder="Pilih tanggal transaksi"
              @date-select="(date) => (form.tanggal = date)"
            />
          </div>

          <div class="flex! flex-col! gap-0.5!">
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

        <BaseInput
          v-model="form.namaPembayar"
          label="Relasi / Nama Pembayar"
          placeholder="Masukkan nama instansi atau perorangan"
          class="mb-2!"
        />

        <div class="grid! grid-cols-1! md:grid-cols-2! gap-4! mb-2!">
          <div class="flex! flex-col! gap-0.5!">
            <MaksMoneyInput v-model="form.nominal" placeholder="0,00" label="Nominal" />
          </div>

          <div class="flex! flex-col! gap-0.5!">
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

        <div class="flex! justify-end! mt-4!">
          <BaseButton
            variant="secondary"
            size="md"
            @click="simpanTransaksi"
            :disabled="isProcessing"
            :loading="isProcessing"
            class="ml-auto! px-5! py-2! rounded-xl! shadow-lg! shadow-blue-200/50!"
          >
            Simpan Komisi SPS
          </BaseButton>
        </div>
      </ContentCard>

      <div class="flex! flex-col! gap-4! lg:sticky! lg:top-8!">
        <ContentCard variant="minimal" padding="normal" hoverable>
          <div class="flex! gap-3! items-start!">
            <div class="text-base! flex-shrink-0! mt-0.5!">ℹ️</div>
            <div class="text-xs! text-slate-600! leading-relaxed!">
              <strong>Bantuan Komisi SPS</strong><br />
              Pastikan data petugas dan nominal komisi sudah benar sebelum menyimpan transaksi.
            </div>
          </div>
        </ContentCard>
      </div>
    </div>

    <AppNotification
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
import AppNotification from '@/presentations/components/ui/AppNotification.vue'
import BaseInput from '@/presentations/components/ui/BaseInput.vue'
import SelectSearch from '@/presentations/components/SelectSearch.vue'
import AppDatePicker from '@/presentations/components/AppDatePicker.vue'
import MaksMoneyInput from '@/presentations/components/MaksMoneyInput.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'

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
