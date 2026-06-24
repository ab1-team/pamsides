<template>
  <div class="space-y-6!">
    <ContentCard variant="bordered" padding="normal" hoverable>
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
            variant="primary"
            @click="tampilkanAnggaran"
            :disabled="loading"
            class="w-full! sm:w-auto! h-11! rounded-xl!"
          >
            Lihat Anggaran
          </BaseButton>
        </div>
      </div>
    </ContentCard>

    <Transition name="fade-slide">
      <ContentCard
        v-if="showTable"
        variant="default"
        padding="normal"
        hoverable
      >
        <div class="flex! flex-col! lg:flex-row! lg:items-center! lg:justify-between! gap-4! mb-4!">
          <div class="flex! items-center! gap-3! text-base! font-semibold! text-gray-900!">
            <span class="text-lg!">📊</span>
            <div class="flex! flex-col! gap-1!">
              <span class="text-base! font-semibold! text-gray-900!">
                Rencana Anggaran — {{ bulanName }} {{ filter.tahun }}
              </span>
            </div>
          </div>
        </div>

        <div v-if="loading" class="text-center py-12">
          <div class="animate-spin w-10 h-10 border-4 border-blue-500 border-t-transparent rounded-full mx-auto mb-3"></div>
          <p class="text-slate-500">Memuat data...</p>
        </div>

        <div v-else class="space-y-6">
          <div v-for="group in groupedAccounts" :key="group.id" class="bg-slate-50 rounded-xl p-4">
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-200">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                     :class="group.lev1 === 4 ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'">
                  <font-awesome-icon :icon="group.lev1 === 4 ? 'arrow-up' : 'arrow-down'" />
                </div>
                <div>
                  <p class="font-bold text-slate-700">{{ group.nama_akun }}</p>
                  <p class="text-xs text-slate-400">{{ group.kode_akun }}</p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-sm font-semibold text-slate-400">Subtotal</p>
                <p class="font-bold" :class="group.lev1 === 4 ? 'text-emerald-600' : 'text-rose-600'">
                  {{ formatCurrency(group.subtotal) }}
                </p>
              </div>
            </div>

            <div class="space-y-2">
              <div
                v-for="child in group.children"
                :key="child.id"
                class="flex items-center gap-4 p-2 hover:bg-white rounded-lg transition-colors"
              >
                <span class="w-24 text-xs text-slate-500 font-mono">{{ child.kode_akun }}</span>
                <span class="flex-1 text-sm text-slate-700">{{ child.nama_akun }}</span>
                <div class="w-48">
                  <input
                    v-model.number="child.jumlah"
                    type="text"
                    class="w-full h-9 px-3 text-right text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    :placeholder="formatCurrency(0)"
                    @blur="formatOnBlur(child)"
                  />
                </div>
              </div>
            </div>
          </div>

          <div class="bg-gradient-to-r from-slate-800 to-slate-900 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-slate-300">Total Rencana Anggaran</p>
                <p class="text-2xl font-black">{{ formatCurrency(totalRencana) }}</p>
              </div>
              <BaseButton
                variant="secondary"
                size="md"
                icon="save"
                @click="simpanRencana"
              >
                Simpan
              </BaseButton>
            </div>
          </div>
        </div>
      </ContentCard>
    </Transition>

    <AppNotification
      v-bind="notificationState"
      @close="notificationState.show = false"
      @confirm="notificationState.show = false"
      @cancel="notificationState.show = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useNotification } from '@/composables/useNotification'
import AppNotification from '@/presentations/components/ui/AppNotification.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import api from '@/utils/axios'

const { notificationState, success, error } = useNotification()

const loading = ref(false)
const showTable = ref(false)
const accounts = ref([])
const ebudgetingData = ref({})

const filter = ref({
  tahun: new Date().getFullYear(),
  bulan: new Date().getMonth() + 1,
})

const bulanNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']

const bulanName = computed(() => bulanNames[filter.value.bulan] || '')

const groupedAccounts = computed(() => {
  const result = []

  const parentPendapatan = accounts.value.find(acc => acc.lev1 === 4 && acc.lev2 === 0)
  const parentBeban = accounts.value.find(acc => acc.lev1 === 5 && acc.lev2 === 0)

  if (parentPendapatan) {
    const childrenPendapatan = accounts.value.filter(acc => acc.lev1 === 4 && acc.lev4 > 0)
    const childrenData = childrenPendapatan.map(c => ({
      ...c,
      jumlah: ebudgetingData.value[c.id] || 0
    }))
    result.push({
      ...parentPendapatan,
      children: childrenData,
      subtotal: childrenData.reduce((sum, c) => sum + (c.jumlah || 0), 0)
    })
  }

  if (parentBeban) {
    const childrenBeban = accounts.value.filter(acc => acc.lev1 === 5 && acc.lev4 > 0)
    const childrenData = childrenBeban.map(c => ({
      ...c,
      jumlah: ebudgetingData.value[c.id] || 0
    }))
    result.push({
      ...parentBeban,
      children: childrenData,
      subtotal: childrenData.reduce((sum, c) => sum + (c.jumlah || 0), 0)
    })
  }

  return result
})

const totalRencana = computed(() => {
  let total = 0
  groupedAccounts.value.forEach(group => {
    total += group.subtotal
  })
  return total
})

const fetchAccounts = async () => {
  try {
    const response = await api.get('/accounts')
    if (response.data.success) {
      accounts.value = response.data.data
    }
  } catch (err) {
    console.error('Gagal mengambil akun:', err)
  }
}

const fetchEbudgeting = async () => {
  try {
    const response = await api.get('/ebudgeting', {
      params: {
        tahun: filter.value.tahun,
        bulan: filter.value.bulan
      }
    })
    if (response.data.success) {
      const data = {}
      response.data.data.forEach(item => {
        data[item.account_id] = parseFloat(item.jumlah)
      })
      ebudgetingData.value = data
    }
  } catch (err) {
    console.error('Gagal mengambil ebudgeting:', err)
  }
}

const tampilkanAnggaran = async () => {
  if (!filter.value.tahun || !filter.value.bulan) {
    error('Tidak Valid', 'Isi tahun dan bulan terlebih dahulu!')
    return
  }

  loading.value = true
  showTable.value = true
  try {
    await fetchEbudgeting()
  } finally {
    loading.value = false
  }
}

const formatOnBlur = (item) => {
  if (item.jumlah) {
    const val = parseInt(String(item.jumlah).replace(/[^\d]/g, '')) || 0
    item.jumlah = val
  }
}

const simpanRencana = async () => {
  try {
    for (const group of groupedAccounts.value) {
      for (const child of group.children) {
        if (child.jumlah > 0) {
          await api.post('/ebudgeting', {
            account_id: child.id,
            tahun: filter.value.tahun,
            bulan: filter.value.bulan,
            jumlah: child.jumlah,
          })
        }
      }
    }
    success('Berhasil!', 'Rencana anggaran berhasil disimpan.')
    fetchEbudgeting()
  } catch (err) {
    console.error('Error:', err)
    error('Kesalahan', 'Gagal menyimpan rencana anggaran.')
  }
}

function formatCurrency(amount) {
  if (!amount) return '0'
  return new Intl.NumberFormat('id-ID').format(amount)
}

onMounted(() => {
  fetchAccounts()
})
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
