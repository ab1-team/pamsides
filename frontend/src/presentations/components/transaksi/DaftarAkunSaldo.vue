<template>
  <div class="space-y-6!">
    <div class="flex! flex-col! lg:flex-row! lg:items-center! lg:justify-between! gap-4!">
      <div class="flex! items-center! gap-3!">
        <span class="text-lg!">📊</span>
        <div class="flex! flex-col! gap-1!">
          <span class="text-base! font-semibold! text-gray-900!">
            {{ title || `Tentukan Rencana Anggaran Tahun ${tahun}` }}
          </span>
        </div>
      </div>
      <div class="relative! w-full! lg:w-80!">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="🔍 Cari kode atau nama akun..."
          class="w-full! h-10! pl-3! pr-3! bg-white! border! border-slate-200! rounded-lg! text-sm! text-slate-700! transition-all! focus:outline-hidden! focus:border-blue-500! focus:ring-2! focus:ring-blue-500/10!"
        />
      </div>
    </div>

    <div v-if="loading" class="text-center! py-12!">
      <div class="animate-spin! w-10! h-10! border-4! border-blue-500! border-t-transparent! rounded-full! mx-auto! mb-3!"></div>
      <p class="text-slate-500! text-sm!">Memuat data akun...</p>
    </div>

    <div v-else class="overflow-x-auto! rounded-xl! border! border-slate-200! shadow-xs!">
      <table class="min-w-full! divide-y! divide-slate-200!">
        <thead class="bg-linear-to-r! from-slate-700! to-slate-800!">
          <tr>
            <th class="px-4! py-3! text-xs! sm:text-sm! font-semibold! text-white! uppercase! tracking-wider! text-left!">
              Kode Akun
            </th>
            <th class="px-4! py-3! text-xs! sm:text-sm! font-semibold! text-white! uppercase! tracking-wider! text-left!">
              Nama Akun
            </th>
            <th class="px-4! py-3! text-xs! sm:text-sm! font-semibold! text-white! uppercase! tracking-wider! text-right! w-80!">
              Saldo
            </th>
          </tr>
        </thead>
        <tbody class="divide-y! divide-slate-100! bg-white!">
          <tr
            v-for="(akun, idx) in filteredItems"
            :key="akun.account_id"
            :class="[idx % 2 === 0 ? 'bg-slate-50/40!' : 'bg-white!', 'hover:bg-slate-100!', 'transition-colors!']"
          >
            <td class="px-4! py-1.5! text-xs! sm:text-sm! text-gray-700! font-sans!">
              <span class="font-mono! text-[11px]! sm:text-xs! bg-blue-50! text-blue-700! px-3! py-1! rounded-full! border! border-blue-100! font-bold! tracking-tight!">
                {{ akun.kode_akun }}
              </span>
            </td>
            <td class="px-4! py-1.5! text-xs! sm:text-sm! font-semibold! text-gray-900! font-sans!">
              {{ akun.nama_akun }}
            </td>
            <td class="px-4! py-1! text-xs! sm:px-6! sm:py-1.5! sm:text-sm! text-right! border-b! border-gray-200! font-sans!">
              <div class="w-full! max-w-xs! ml-auto!">
                <MaksMoneyInput
                  v-model="akun.saldo"
                  placeholder="0,00"
                  :show-helper="false"
                  size="sm"
                  no-margin
                />
              </div>
            </td>
          </tr>
          <tr v-if="filteredItems.length === 0">
            <td colspan="3" class="text-center! py-8! text-slate-500! text-sm!">
              {{ searchQuery ? 'Tidak ada akun yang cocok dengan pencarian.' : 'Belum ada data akun.' }}
            </td>
          </tr>
        </tbody>
        <tfoot>
          <tr class="bg-gray-50!">
            <td colspan="2" class="px-4! py-3! text-xs! sm:text-sm! font-semibold! text-gray-700! uppercase! tracking-wider! border-t-2! border-gray-300! font-sans!">
              TOTAL SALDO KESELURUHAN
            </td>
            <td class="px-4! py-3! text-right! border-t-2! border-gray-300! font-sans!">
              <div class="flex! flex-col! items-end! leading-tight!">
                <span class="text-[11px]! sm:text-xs! font-bold! text-slate-500! uppercase!">Rp.</span>
                <span class="font-mono! font-bold! text-blue-600! text-base! sm:text-lg!">
                  {{ formatNumber(total) }}
                </span>
              </div>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import MaksMoneyInput from '@/presentations/components/MaksMoneyInput.vue'
import api from '@/utils/axios'

const props = defineProps({
  tahun: { type: [Number, String], default: () => new Date().getFullYear() },
  bulan: { type: [Number, String], default: '' },
  autoFetch: { type: Boolean, default: true },
  title: { type: String, default: '' },
})

const emit = defineEmits(['update:total', 'change'])

const items = ref([])
const total = ref(0)
const loading = ref(false)
const searchQuery = ref('')

const filteredItems = computed(() => {
  const q = searchQuery.value.toLowerCase().trim()
  if (!q) return items.value
  return items.value.filter(
    (a) => a.kode_akun.toLowerCase().includes(q) || a.nama_akun.toLowerCase().includes(q),
  )
})

const recomputeTotal = () => {
  const sum = filteredItems.value.reduce((s, a) => s + (Number(a.saldo) || 0), 0)
  total.value = sum
  emit('update:total', sum)
  emit('change', items.value)
}

watch(filteredItems, recomputeTotal, { deep: true })

const formatNumber = (val) =>
  Number(val || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const fetchAccounts = async () => {
  loading.value = true
  try {
    const params = { tahun: props.tahun }
    if (props.bulan) params.bulan = String(props.bulan).padStart(2, '0')
    const response = await api.get('/accounts-with-saldo', { params })
    if (response.data?.success) {
      items.value = response.data.data.items || []
      recomputeTotal()
    }
  } catch (err) {
    console.error('Gagal mengambil daftar akun:', err)
    items.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

defineExpose({ fetchAccounts, items, total })

watch(() => [props.tahun, props.bulan], () => { if (props.autoFetch) fetchAccounts() })

onMounted(() => { if (props.autoFetch) fetchAccounts() })
</script>