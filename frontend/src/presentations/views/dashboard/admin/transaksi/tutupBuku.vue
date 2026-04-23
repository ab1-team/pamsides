<template>
  <div class="space-y-6!">
    <ContentCard variant="bordered" padding="normal" hoverable>
      <div class="flex! flex-col! lg:flex-row! lg:items-end! lg:justify-between! gap-4!">
        <div class="w-full! lg:flex-1!">
          <SelectSearch
            v-model="selectedTahun"
            :options="tahunOptions"
            placeholder="Pilih Tahun"
            no-margin
          />
        </div>
        <div class="flex! flex-col! sm:flex-row! gap-3! w-full! lg:w-auto!">
          <BaseButton
            variant="info"
            @click="handleTutupBuku"
            :disabled="isProcessing"
            :loading="isProcessing"
            class="w-full! sm:w-auto! h-11! rounded-xl!"
          >
            1. Tutup Buku
          </BaseButton>
          <BaseButton
            href="/transaksi/alokasi-laba"
            variant="secondary"
            class="w-full! sm:w-auto! h-11! rounded-xl!"
          >
            2. Simpan Alokasi Laba
          </BaseButton>
        </div>
      </div>
    </ContentCard>

    <ContentCard
      variant="default"
      padding="normal"
      hoverable
      v-if="bookStatus !== 'open' || isProcessing"
    >
      <div class="flex! flex-col! lg:flex-row! lg:items-center! lg:justify-between! gap-4! mb-4!">
        <div
          class="flex! items-center! gap-3! text-base! font-semibold! text-gray-900!"
          v-if="bookStatus !== 'open' || isProcessing"
        >
          <span class="text-lg!">📊</span>
          <div class="flex! flex-col! gap-1!">
            <span class="text-base! font-semibold! text-gray-900!"
              >Daftar Akun — Tahun {{ selectedTahun }}</span
            >
            <span
              v-if="isProcessing || bookStatus === 'closed'"
              class="text-xs! font-semibold! px-2.5! py-1! rounded-full! inline-flex! items-center! gap-1.5! transition-all! duration-300!"
              :class="
                isProcessing
                  ? 'bg-amber-100! text-amber-700! border! border-amber-200! animate-pulse!'
                  : bookStatus === 'closed'
                    ? 'bg-emerald-100! text-emerald-700! border! border-emerald-200!'
                    : ''
              "
            >
              <span
                v-if="isProcessing"
                class="w-1.5! h-1.5! rounded-full! bg-amber-500! animate-ping!"
              ></span>
              <span v-else>✅</span>
              {{
                isProcessing
                  ? 'Sedang Memproses...'
                  : bookStatus === 'closed'
                    ? 'Buku Sudah Ditutup'
                    : ''
              }}
            </span>
          </div>
        </div>
        <div class="w-full! lg:w-auto!">
          <div class="relative!">
            <span class="absolute! left-3! top-1/2! -translate-y-1/2! text-sm!">🔍</span>
            <input
              v-model="searchQuery"
              type="text"
              class="w-full! h-11! pl-10! pr-4! rounded-xl! border! border-slate-200! bg-slate-50! font-inherit! text-sm! text-slate-700! outline-none! transition-all! duration-300! focus:bg-white! focus:border-blue-500! focus:ring-4! focus:ring-blue-500/10! focus:shadow-lg! focus:shadow-blue-500/5!"
              placeholder="Cari kode atau nama akun..."
            />
          </div>
        </div>
      </div>

      <div
        class="overflow-x-auto! -mx-4! px-4! sm:mx-0! sm:px-0! rounded-2xl! overflow-hidden! border! border-slate-200!"
      >
        <div class="min-w-[600px]!">
          <table class="w-full! border-collapse!">
            <thead>
              <tr class="bg-slate-800!">
                <th
                  class="px-4! py-3! text-xs! sm:px-6! sm:py-4! sm:text-sm! font-semibold! text-white! uppercase! tracking-wider! text-left! font-sans!"
                >
                  Kode Akun
                </th>
                <th
                  class="px-4! py-3! text-xs! sm:px-6! sm:py-4! sm:text-sm! font-semibold! text-white! uppercase! tracking-wider! text-left! font-sans!"
                >
                  Nama Akun
                </th>
                <th
                  class="px-4! py-3! text-xs! sm:px-6! sm:py-4! sm:text-sm! font-semibold! text-white! uppercase! tracking-wider! text-right! font-sans! w-48! sm:w-72!"
                >
                  Saldo
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(akun, idx) in filteredAkun"
                :key="akun.kode"
                :class="{
                  'bg-slate-50!': idx % 2 === 0,
                  'hover:bg-gray-100!': true,
                  'transition-colors!': true,
                }"
              >
                <td
                  class="px-4! py-1! text-xs! sm:px-6! sm:py-1.5! sm:text-sm! text-gray-600! border-b! border-gray-200! font-sans!"
                >
                  <span
                    class="font-mono! text-[11px]! sm:text-xs! bg-blue-50! text-blue-700! px-3! py-1! rounded-full! border! border-blue-100! font-bold! tracking-tight!"
                    >{{ akun.kode }}</span
                  >
                </td>
                <td
                  class="px-4! py-1! text-xs! sm:px-6! sm:py-1.5! sm:text-sm! font-semibold! text-gray-900! border-b! border-gray-200! font-sans!"
                >
                  {{ akun.nama }}
                </td>
                <td
                  class="px-4! py-1! text-xs! sm:px-6! sm:py-1.5! sm:text-sm! border-b! border-gray-200! font-sans! text-right!"
                >
                  <MaksMoneyInput
                    v-model="akun.saldo"
                    placeholder="0,00"
                    :show-helper="false"
                    size="sm"
                    no-margin
                  />
                </td>
              </tr>
              <tr v-if="filteredAkun.length === 0">
                <td
                  colspan="3"
                  class="text-center! py-6! sm:py-8! text-gray-500! text-xs! sm:text-sm! font-sans!"
                >
                  Tidak ada data ditemukan.
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="bg-gray-50!">
                <td
                  colspan="2"
                  class="px-4! py-3! text-xs! sm:px-6! sm:py-4! sm:text-sm! font-semibold! text-gray-700! uppercase! tracking-wider! border-t-2! border-gray-300! font-sans!"
                >
                  TOTAL SALDO KESELURUHAN
                </td>
                <td
                  class="px-4! py-3! text-xs! sm:px-6! sm:py-4! sm:text-sm! text-right! font-mono! font-bold! text-blue-600! border-t-2! border-gray-300!"
                >
                  {{ formatRp(grandTotal) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </ContentCard>

    <BaseButton
      variant="success-gradient"
      size="lg"
      class="fixed! bottom-6! right-6! z-50! rounded-full! shadow-2xl! scale-110! sm:scale-100!"
      @click="simpanPerubahanSaldo"
      v-if="bookStatus !== 'open' || isProcessing"
    >
      <span class="mr-2!">💾</span>
      <span class="hidden! sm:inline!">Simpan Tutup Buku</span>
      <span class="sm:hidden!">Simpan</span>
    </BaseButton>

    <AppNotification
      v-bind="notificationState"
      @close="() => {}"
      @confirm="() => {}"
      @cancel="() => {}"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useNotification } from '@/composables/useNotification'
import AppNotification from '@/presentations/components/ui/AppNotification.vue'
import SelectSearch from '@/presentations/components/SelectSearch.vue'
import MaksMoneyInput from '@/presentations/components/MaksMoneyInput.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'

const { notificationState, success, error } = useNotification()
const selectedTahun = ref('')
const searchQuery = ref('')
const isProcessing = ref(false)
const bookStatus = ref('open')

const tahunOptions = computed(() => {
  const current = new Date().getFullYear()
  return Array.from({ length: 10 }, (_, i) => ({
    id: current - i,
    text: (current - i).toString(),
  }))
})

const akunList = ref([
  { kode: '1.1.01.00', nama: 'Kas', saldo: 19973500 },
  { kode: '1.1.02.00', nama: 'Kas Setara Kas', saldo: 0 },
  { kode: '1.1.03.00', nama: 'Piutang', saldo: 105210000 },
  { kode: '1.1.04.00', nama: 'Cadangan Kerugian Piutang', saldo: 0 },
  { kode: '1.1.05.00', nama: 'Rekening antar Kantor', saldo: 0 },
  { kode: '1.1.06.00', nama: 'Investasi', saldo: 0 },
  { kode: '1.2.01.00', nama: 'Aktiva Tetap dan Inventaris', saldo: 0 },
  { kode: '1.2.02.00', nama: 'Akumulasi Penyusutan Aktiva Tetap dan Inventaris', saldo: 0 },
  { kode: '1.2.03.00', nama: 'Aset Tak Berwujud', saldo: 0 },
  { kode: '2.1.01.00', nama: 'Hutang Usaha', saldo: 0 },
  { kode: '2.1.02.00', nama: 'Hutang Pajak', saldo: 0 },
  { kode: '2.2.01.00', nama: 'Hutang Jangka Panjang', saldo: 0 },
  { kode: '3.1.01.00', nama: 'Modal Disetor', saldo: 0 },
  { kode: '3.1.02.00', nama: 'Laba Ditahan', saldo: 0 },
  { kode: '4.1.01.00', nama: 'Pendapatan Air', saldo: 0 },
  { kode: '4.1.02.00', nama: 'Pendapatan Sambungan Baru', saldo: 0 },
  { kode: '5.1.01.00', nama: 'Beban Operasional', saldo: 0 },
  { kode: '5.1.02.00', nama: 'Beban Gaji', saldo: 0 },
  { kode: '5.1.03.00', nama: 'Beban Penyusutan', saldo: 0 },
])

const filteredAkun = computed(() => {
  const q = searchQuery.value.toLowerCase()
  if (!q) return akunList.value
  return akunList.value.filter(
    (a) => a.kode.toLowerCase().includes(q) || a.nama.toLowerCase().includes(q),
  )
})

const grandTotal = computed(() => akunList.value.reduce((s, a) => s + a.saldo, 0))

const formatRp = (val) =>
  'Rp. ' +
  Number(val).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const simpanPerubahanSaldo = async () => {
  try {
    console.log('Menyimpan perubahan saldo:', akunList.value)
    success('Berhasil!', 'Perubahan saldo berhasil disimpan.')
  } catch (error) {
    console.error('Error menyimpan saldo:', error)
    error('Kesalahan', 'Gagal menyimpan perubahan saldo.')
  }
}

const handleTutupBuku = async () => {
  const currentYear = new Date().getFullYear()
  const yearToClose = selectedTahun.value

  if (yearToClose > currentYear) {
    error('Tidak Valid', 'Tidak dapat menutup buku untuk tahun mendatang!')
    return
  }

  const isAlreadyClosed = await checkIfBookClosed(yearToClose)
  if (isAlreadyClosed) {
    bookStatus.value = 'closed'
    error('Sudah Ditutup', `Buku untuk tahun ${yearToClose} sudah ditutup!`)
    return
  }

  bookStatus.value = 'input'
}

const checkIfBookClosed = async (year) => {
  try {
    console.log(`Checking if book for year ${year} is closed...`)
    await new Promise((resolve) => setTimeout(resolve, 500))
    return false
  } catch (error) {
    console.error('Error checking book status:', error)
    return false
  }
}
</script>
