<template>
  <div class="space-y-3!">
    <ContentCard variant="bordered" padding="small" hoverable>
      <div class="flex! flex-col! lg:flex-row! lg:items-end! lg:justify-between! gap-4!">
        <div class="flex-1">
          <div class="relative w-full max-w-md">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-500 uppercase tracking-wide pointer-events-none">Tahun</span>
            <input
              v-model.number="filter.tahun"
              type="number"
              min="2000"
              max="2100"
              class="w-full h-12 pl-24 pr-12 bg-white border border-slate-200 rounded-xl text-center font-bold text-lg text-slate-700 transition-all duration-300 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 focus:bg-white focus:shadow-lg focus:shadow-blue-500/5 hover:border-blue-400"
              placeholder=""
            />
            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
              <font-awesome-icon icon="calendar" />
            </span>
          </div>
        </div>
        <div class="flex-1">
          <div class="relative w-full max-w-md">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-500 uppercase tracking-wide pointer-events-none">Bulan</span>
            <input
              v-model.number="filter.bulan"
              type="number"
              min="1"
              max="12"
              class="w-full h-12 pl-20 pr-12 bg-white border border-slate-200 rounded-xl text-center font-bold text-lg text-slate-700 transition-all duration-300 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 focus:bg-white focus:shadow-lg focus:shadow-blue-500/5 hover:border-blue-400"
              placeholder=""
            />
            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
              <font-awesome-icon icon="calendar" />
            </span>
          </div>
        </div>
        <div class="flex! flex-col! sm:flex-row! gap-3! lg:ml-4!">
          <BaseButton
            variant="secondary"
            @click="tampilkanDaftarAkun"
            :disabled="loading"
            class="w-full! sm:w-auto! h-11! rounded-xl!"
          >
            Tentukan Rencana Anggaran
          </BaseButton>
        </div>
      </div>
    </ContentCard>

    <ContentCard v-if="showTable" variant="default" padding="small" hoverable>
      <DaftarAkunSaldo
        ref="daftarAkunRef"
        :tahun="filter.tahun"
        :bulan="filter.bulan"
        :title="`Tentukan Rencana Anggaran — ${bulanName} ${filter.tahun}`"
      />
    </ContentCard>

    <BaseButton
      v-if="showTable"
      variant="success-gradient"
      size="lg"
      class="fixed! bottom-6! right-6! z-50! rounded-full! shadow-2xl!"
      :disabled="saving || alreadySaved || checkingExisting"
      :loading="saving || checkingExisting"
      @click="simpanAnggaran"
    >
      <span class="mr-2!">💾</span>
      <span class="hidden! sm:inline!">
        {{ checkingExisting ? 'Memeriksa...' : alreadySaved ? 'Sudah Disimpan' : 'Simpan Anggaran' }}
      </span>
      <span class="sm:hidden!">
        {{ checkingExisting ? '...' : alreadySaved ? 'Tersimpan' : 'Simpan' }}
      </span>
    </BaseButton>

    <AppToast
      v-model="toastVisible"
      title="Berhasil!"
      :message="`Rencana anggaran ${bulanName} ${filter.tahun} berhasil disimpan.`"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import DaftarAkunSaldo from '@/presentations/components/transaksi/DaftarAkunSaldo.vue'
import AppToast from '@/presentations/components/ui/AppToast.vue'
import api from '@/utils/axios'

const loading = ref(false)
const saving = ref(false)
const showTable = ref(false)
const daftarAkunRef = ref(null)
const alreadySaved = ref(false)
const savedCount = ref(0)
const toastVisible = ref(false)
const checkingExisting = ref(false)

const bulanNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']

const filter = ref({
  tahun: new Date().getFullYear(),
  bulan: new Date().getMonth() + 1,
})

const bulanName = computed(() => bulanNames[filter.value.bulan] || '')

const checkExisting = async () => {
  if (!filter.value.tahun || !filter.value.bulan) return
  checkingExisting.value = true
  try {
    const response = await api.get('/ebudgeting/check-exists', {
      params: { tahun: filter.value.tahun, bulan: filter.value.bulan },
    })
    if (response.data?.success) {
      alreadySaved.value = !!response.data.exists
      savedCount.value = response.data.count || 0
    }
  } catch (err) {
    console.error('Gagal cek ebudgeting existing:', err)
    alreadySaved.value = false
    savedCount.value = 0
  } finally {
    checkingExisting.value = false
  }
}

watch(() => [filter.value.tahun, filter.value.bulan], () => {
  checkExisting()
}, { immediate: true })

const tampilkanDaftarAkun = async () => {
  if (!filter.value.tahun || !filter.value.bulan) return
  loading.value = true
  try {
    await checkExisting()
    if (daftarAkunRef.value?.fetchAccounts) {
      await daftarAkunRef.value.fetchAccounts()
    }
    showTable.value = true
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

const simpanAnggaran = async () => {
  if (alreadySaved.value) return

  const items = (daftarAkunRef.value?.items || [])
    .map((a) => ({
      account_id: a.account_id,
      jumlah: Number(a.saldo) || 0,
    }))

  if (items.length === 0) return

  saving.value = true
  try {
    await api.post('/ebudgeting/bulk', {
      tahun: filter.value.tahun,
      bulan: filter.value.bulan,
      items,
    })
    alreadySaved.value = true
    savedCount.value = items.length
    toastVisible.value = true
  } catch (err) {
    console.error('Gagal menyimpan anggaran:', err)
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.3s ease;
}
.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(20px);
}
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>