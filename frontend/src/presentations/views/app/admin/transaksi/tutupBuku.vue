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
            :disabled="isProcessing || !selectedTahun"
            :loading="isProcessing"
            class="w-full! sm:w-auto! h-11! rounded-xl!"
          >
            1. Tutup Buku
          </BaseButton>
          <BaseButton
            :href="`/app/transaksi/alokasi-laba?tahun=${selectedTahun}`"
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
                    :readonly="bookStatus === 'closed'"
                  />
                </td>
              </tr>
              <tr v-if="isLoadingAkun">
                <td
                  colspan="3"
                  class="text-center! py-6! sm:py-8! text-gray-500! text-xs! sm:text-sm! font-sans!"
                >
                  <span class="inline-flex! items-center! gap-2!">
                    <span
                      class="w-4! h-4! rounded-full! border-2! border-blue-500! border-t-transparent! animate-spin!"
                    ></span>
                    Memuat data akun...
                  </span>
                </td>
              </tr>
              <tr v-else-if="filteredAkun.length === 0">
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
      :disabled="isSaving || bookStatus === 'closed' || akunList.length === 0"
      :loading="isSaving"
      v-if="bookStatus !== 'open' || isProcessing"
    >
      <span class="mr-2!">💾</span>
      <span class="hidden! sm:inline!">Simpan Tutup Buku</span>
      <span class="sm:hidden!">Simpan</span>
    </BaseButton>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useUiStore } from '@/stores/uiStore'
import SelectSearch from '@/presentations/components/SelectSearch.vue'
import MaksMoneyInput from '@/presentations/components/MaksMoneyInput.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import { accountingService } from '@/services/accounting.service'

const uiStore = useUiStore()
const selectedTahun = ref('')
const searchQuery = ref('')
const isProcessing = ref(false)
const isLoadingAkun = ref(false)
const isSaving = ref(false)
const bookStatus = ref('open')
const akunList = ref([])

const tahunOptions = computed(() => {
  const current = new Date().getFullYear()
  return Array.from({ length: 10 }, (_, i) => ({
    id: current - i,
    text: (current - i).toString(),
  }))
})

const filteredAkun = computed(() => {
  const q = searchQuery.value.toLowerCase().trim()
  if (!q) return akunList.value
  return akunList.value.filter(
    (a) => a.kode.toLowerCase().includes(q) || a.nama.toLowerCase().includes(q),
  )
})

const grandTotal = computed(() =>
  akunList.value.reduce((s, a) => s + (Number(a.saldo) || 0), 0),
)

const formatRp = (val) =>
  'Rp. ' +
  Number(val || 0).toLocaleString('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  })

const loadAkunList = async (year) => {
  if (!year) {
    akunList.value = []
    return
  }
  isLoadingAkun.value = true
  try {
    const res = await accountingService.getAccountsWithSaldo(year)
    if (res.success && res.data) {
      akunList.value = (res.data.accounts || []).map((a) => ({
        account_id: a.account_id,
        kode: a.kode,
        nama: a.nama,
        lev1: a.lev1,
        lev2: a.lev2,
        lev3: a.lev3,
        lev4: a.lev4,
        jenis_mutasi: a.jenis_mutasi,
        saldo: Number(a.saldo) || 0,
      }))
    } else {
      akunList.value = []
    }
  } catch (e) {
    console.error('Gagal memuat daftar akun:', e)
    uiStore.error(e?.response?.data?.message || 'Gagal memuat daftar akun.')
    akunList.value = []
  } finally {
    isLoadingAkun.value = false
  }
}

const checkBookStatus = async (year) => {
  try {
    const res = await accountingService.checkBookClosed(year)
    if (res.success && res.data) {
      return !!res.data.closed
    }
    return false
  } catch (e) {
    console.error('Gagal cek status buku:', e)
    return false
  }
}

const simpanPerubahanSaldo = async () => {
  if (!selectedTahun.value) {
    uiStore.error('Pilih tahun terlebih dahulu.')
    return
  }
  isSaving.value = true
  try {
    const overrides = akunList.value
      .filter((a) => Number(a.saldo) !== 0)
      .map((a) => ({ kode: a.kode, saldo: Number(a.saldo) || 0 }))

    const res = await accountingService.closeBook(selectedTahun.value, { overrides })
    if (res.success) {
      bookStatus.value = 'closed'
      uiStore.success(res.data?.message || `Buku tahun ${selectedTahun.value} berhasil ditutup.`)
      await loadAkunList(selectedTahun.value)
    } else {
      uiStore.error(res.message || 'Gagal menutup buku.')
    }
  } catch (e) {
    console.error('Error simpan tutup buku:', e)
    uiStore.error(e?.response?.data?.message || 'Gagal menyimpan perubahan saldo.')
  } finally {
    isSaving.value = false
  }
}

const handleTutupBuku = async () => {
  const yearToClose = Number(selectedTahun.value)
  if (!yearToClose) {
    uiStore.error('Pilih tahun terlebih dahulu.')
    return
  }

  const currentYear = new Date().getFullYear()
  if (yearToClose > currentYear) {
    uiStore.error('Tidak dapat menutup buku untuk tahun mendatang!')
    return
  }

  isProcessing.value = true
  try {
    const isClosed = await checkBookStatus(yearToClose)
    if (isClosed) {
      bookStatus.value = 'closed'
      uiStore.warn(`Buku untuk tahun ${yearToClose} sudah ditutup.`)
    } else {
      bookStatus.value = 'input'
      uiStore.info(`Silakan periksa dan simpan saldo akun tahun ${yearToClose}.`)
    }
    await loadAkunList(yearToClose)
  } catch (e) {
    console.error('Error handleTutupBuku:', e)
    uiStore.error('Terjadi kesalahan saat memproses tutup buku.')
  } finally {
    isProcessing.value = false
  }
}

watch(selectedTahun, (val) => {
  if (val) {
    bookStatus.value = 'open'
    akunList.value = []
  }
})
</script>
