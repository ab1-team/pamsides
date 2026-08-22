<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4! md:p-8!">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="close"></div>

        <div
          class="relative w-full! h-full! max-w-7xl! bg-white rounded-2xl! shadow-xl! border border-slate-200 flex flex-col overflow-hidden animate-slide-up"
        >
          <div
            class="flex items-center justify-between px-6! py-4! border-b border-slate-200 bg-white"
          >
            <div class="flex items-center gap-3!">
              <div
                class="w-10! h-10! rounded-full! bg-slate-700 text-white flex items-center justify-center"
              >
                <font-awesome-icon icon="file-alt" />
              </div>
              <div>
                <h2 class="text-lg! font-semibold! text-slate-800 leading-tight">
                  {{ title }}
                </h2>
              </div>
            </div>
            <button
              @click="close"
              class="w-9! h-9! hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-all active:scale-95 rounded-md!"
            >
              <font-awesome-icon icon="times" />
            </button>
          </div>

          <div class="flex-1 overflow-auto px-2! py-2! scrollbar-custom">
            <table class="w-full border-collapse text-sm">
              <thead>
                <tr class="bg-slate-800 text-white sticky top-0 z-10">
                  <th
                    class="py-3! px-4! text-xs font-semibold uppercase tracking-wide border-b border-slate-700 text-center w-10"
                  >
                    <input
                      type="checkbox"
                      :checked="isAllSelected"
                      :indeterminate.prop="isIndeterminate"
                      @change="toggleAll"
                      class="h-4 w-4 rounded border-slate-300 bg-white text-slate-700 focus:ring-slate-500"
                    />
                  </th>
                  <th
                    class="py-3! px-4! text-xs font-semibold uppercase tracking-wide border-b border-slate-700 text-center w-12"
                  >
                    No
                  </th>
                  <th
                    class="py-3! px-4! text-xs font-semibold uppercase tracking-wide border-b border-slate-700 whitespace-nowrap"
                  >
                    Tanggal
                  </th>
                  <th
                    class="py-3! px-4! text-xs font-semibold uppercase tracking-wide border-b border-slate-700 whitespace-nowrap"
                  >
                    Kode Akun
                  </th>
                  <th
                    class="py-3! px-4! text-xs font-semibold uppercase tracking-wide border-b border-slate-700 min-w-[200px]"
                  >
                    Keterangan
                  </th>
                  <th
                    class="py-3! px-4! text-xs font-semibold uppercase tracking-wide border-b border-slate-700 whitespace-nowrap"
                  >
                    ID Trx
                  </th>
                  <th
                    class="py-3! px-4! text-xs font-semibold uppercase tracking-wide border-b border-slate-700 text-right"
                  >
                    Debit
                  </th>
                  <th
                    class="py-3! px-4! text-xs font-semibold uppercase tracking-wide border-b border-slate-700 text-right"
                  >
                    Kredit
                  </th>
                  <th
                    class="py-3! px-4! text-xs font-semibold uppercase tracking-wide border-b border-slate-700 text-center"
                  >
                    Saldo
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200">
                <tr v-if="loading">
                  <td colspan="9" class="py-8 text-center text-slate-500">
                    <div class="flex justify-center">
                      <div
                        class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"
                      ></div>
                    </div>
                  </td>
                </tr>
                <tr v-else-if="rows.length === 0">
                  <td colspan="9" class="py-8 text-center text-slate-500">
                    Tidak ada data transaksi ditemukan.
                  </td>
                </tr>
                <template v-else>
                  <tr
                    v-for="(trx, index) in rows"
                    :key="trx._isHeader ? `hdr-${index}` : trx.id"
                    :class="trx._isHeader ? 'bg-slate-50 font-semibold' : 'hover:bg-slate-50 transition-colors'"
                  >
                    <td class="py-4! px-4! text-center">
                      <template v-if="!trx._isHeader">
                        <input
                          type="checkbox"
                          :checked="isSelected(trx.id)"
                          @change="toggleOne(trx.id)"
                          class="h-4 w-4 rounded border-slate-300 bg-white text-slate-700 focus:ring-slate-500"
                        />
                      </template>
                    </td>
                    <td class="py-4! px-4! text-center text-slate-600">
                      {{ trx._isHeader ? '' : index + 1 }}
                    </td>
                    <td class="py-4! px-4! text-slate-700">
                      <template v-if="trx._isHeader">
                        {{ trx.tanggalLabel }}
                      </template>
                      <template v-else>{{ formatDate(trx.tgl_transaksi) }}</template>
                    </td>
                    <td class="py-4! px-4! font-mono text-xs text-slate-500">
                      {{ trx._isHeader ? '' : kodeAkunTrx(trx) }}
                    </td>
                    <td class="py-4! px-4! text-slate-700">
                      {{ trx._isHeader ? trx.label : (trx.keterangan_transaksi || '-') }}
                    </td>
                    <td class="py-4! px-4! font-mono text-xs text-slate-500">
                      {{ trx._isHeader ? '' : trx.id }}
                    </td>
                    <td class="py-4! px-4! text-right font-mono text-slate-700">
                      {{ formatCurrency(trx._isHeader ? trx.debit : debitTrx(trx)) }}
                    </td>
                    <td class="py-4! px-4! text-right font-mono text-slate-700">
                      {{ formatCurrency(trx._isHeader ? trx.kredit : kreditTrx(trx)) }}
                    </td>
                    <td class="py-4! px-4! text-right font-mono text-slate-700">
                      {{ formatCurrency(runningBalance[index]) }}
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td colspan="6" class="py-4! px-6! font-semibold text-slate-800 text-right">
                      Total Terpilih
                    </td>
                    <td class="py-4! px-4! text-right font-mono text-slate-800">
                      {{ formatCurrency(totalSelectedDebit) }}
                    </td>
                    <td class="py-4! px-4! text-right font-mono text-slate-800">
                      {{ formatCurrency(totalSelectedKredit) }}
                    </td>
                    <td></td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>

          <div
            class="px-6! py-6! bg-white border-t border-slate-200 flex justify-end items-center gap-3! min-h-[80px]!"
          >
            <button
              @click="handleCetak"
              :disabled="selectedCount === 0"
              :title="selectedCount === 0 ? 'Pilih minimal satu transaksi' : 'Cetak ' + selectedCount + ' transaksi terpilih'"
              class="flex items-center gap-2! bg-slate-700 hover:bg-slate-800 disabled:opacity-50! disabled:cursor-not-allowed! text-white px-6! py-3! font-semibold transition-all active:scale-95 rounded-lg!"
            >
              <font-awesome-icon icon="print" />
              Cetak Bukti Transaksi ({{ selectedCount }})
            </button>
            <button
              @click="close"
              class="bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 px-6! py-3! font-semibold transition-all active:scale-95 rounded-lg!"
            >
              Tutup
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

const props = defineProps({
  show: { type: Boolean, default: false },
  transactions: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  title: { type: String, default: 'Cetak Bukti Transaksi' },
  selectedAccount: { type: String, default: '' },
  saldoAwalTahun: { type: Number, default: 0 },
  saldoAwalBulan: { type: Number, default: null },
  filterTahun: { type: [Number, String], default: '' },
  filterBulan: { type: [Number, String], default: '' },
})

const emit = defineEmits(['close'])
const router = useRouter()

const selectedIds = ref(new Set())

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const d = new Date(dateString)
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const formatCurrency = (amount) => {
  if (!amount) return '0'
  return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(amount)
}

const close = () => emit('close')

const handleCetak = () => {
  if (selectedCount.value === 0) return
  const selectedOrdered = selectableRows.value.filter((r) => selectedIds.value.has(r.id))
  const selected = selectedOrdered.map((r, idx, arr) => {
    const saldo = arr.slice(0, idx + 1).reduce((s, row) => {
      const d = debitTrx(row)
      const k = kreditTrx(row)
      return s + d - k
    }, saldoAwal.value)
    return {
      id: r.id,
      tgl_transaksi: r.tgl_transaksi,
      account_debet: r.account_debet,
      account_kredit: r.account_kredit,
      keterangan_transaksi: r.keterangan_transaksi,
      saldo: r.saldo,
      _saldo: saldo,
    }
  })
  const payload = {
    title: props.title,
    periodeLabel: props.filterBulan
      ? `${props.filterBulan} ${props.filterTahun}`
      : `${props.filterTahun}`,
    selectedAccount: props.selectedAccount,
    saldoAwal: saldoAwal.value,
    items: selected,
  }
  localStorage.setItem('cetak_print_ids_bukti_trx', JSON.stringify(payload))
  const route = router.resolve({ path: '/usages/cetak_bukti_transaksi' })
  window.open(route.href, '_blank', 'noopener')
}

const deleteTransaction = () => {}

const kodeAkunTrx = (trx) => props.selectedAccount || trx.account_debet?.kode_akun || trx.account_debet

const debitTrx = (trx) => {
  if (!props.selectedAccount) return Number(trx.saldo) || 0
  const debet = trx.account_debet?.kode_akun || trx.account_debet
  return debet === props.selectedAccount ? Number(trx.saldo) || 0 : 0
}

const kreditTrx = (trx) => {
  if (!props.selectedAccount) return 0
  const kredit = trx.account_kredit?.kode_akun || trx.account_kredit
  return kredit === props.selectedAccount ? Number(trx.saldo) || 0 : 0
}

const saldoAwalBulanRow = computed(() => {
  if (!props.filterBulan) return null
  const saldoBulan = props.saldoAwalBulan !== null ? props.saldoAwalBulan : props.saldoAwalTahun
  const delta = saldoBulan - props.saldoAwalTahun
  return {
    _isHeader: true,
    label: 'Komulatif Transaksi s/d Bulan Lalu',
    tanggalLabel: `01/${String(props.filterBulan).padStart(2, '0')}/${props.filterTahun}`,
    debit: delta > 0 ? delta : 0,
    kredit: delta < 0 ? Math.abs(delta) : 0,
    saldo: saldoBulan,
  }
})

const saldoAwalTahunRow = computed(() => ({
  _isHeader: true,
  label: 'Komulatif Transaksi Awal Tahun',
  tanggalLabel: `01/01/${props.filterTahun}`,
  debit: props.saldoAwalTahun,
  kredit: 0,
  saldo: props.saldoAwalTahun,
}))

const rows = computed(() => {
  const out = [saldoAwalTahunRow.value]
  if (saldoAwalBulanRow.value) out.push(saldoAwalBulanRow.value)
  return [...out, ...props.transactions]
})

const isSelected = (id) => selectedIds.value.has(id)

const toggleOne = (id) => {
  const next = new Set(selectedIds.value)
  if (next.has(id)) next.delete(id)
  else next.add(id)
  selectedIds.value = next
}

const saldoAwal = computed(() => {
  if (props.filterBulan && props.saldoAwalBulan === null) return props.saldoAwalTahun
  return props.saldoAwalBulan !== null ? props.saldoAwalBulan : props.saldoAwalTahun
})

const runningBalance = computed(() => {
  let bal = saldoAwal.value
  return rows.value.map((row) => {
    if (row._isHeader) {
      bal = row.saldo
      return bal
    }
    bal += debitTrx(row) - kreditTrx(row)
    return bal
  })
})

const selectableRows = computed(() => rows.value.filter((r) => !r._isHeader))

const selectedCount = computed(() => selectedIds.value.size)

const isAllSelected = computed(() =>
  selectableRows.value.length > 0 &&
  selectableRows.value.every((r) => selectedIds.value.has(r.id)),
)

const isIndeterminate = computed(() => {
  const n = selectedCount.value
  return n > 0 && n < selectableRows.value.length
})

const toggleAll = () => {
  if (isAllSelected.value) {
    selectedIds.value = new Set()
  } else {
    selectedIds.value = new Set(selectableRows.value.map((r) => r.id))
  }
}

const totalSelectedDebit = computed(() =>
  selectableRows.value.filter((r) => selectedIds.value.has(r.id)).reduce((s, r) => s + debitTrx(r), 0),
)
const totalSelectedKredit = computed(() =>
  selectableRows.value.filter((r) => selectedIds.value.has(r.id)).reduce((s, r) => s + kreditTrx(r), 0),
)

const handleKeydown = (e) => {
  if (e.key === 'Escape' && props.show) close()
}

watch(
  () => props.show,
  (val) => {
    document.body.style.overflow = val ? 'hidden' : ''
    if (!val) selectedIds.value = new Set()
  },
)

onMounted(() => window.addEventListener('keydown', handleKeydown))
onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.4s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

@keyframes slide-up {
  from { transform: translateY(30px) scale(0.98); opacity: 0; }
  to { transform: translateY(0) scale(1); opacity: 1; }
}
.animate-slide-up {
  animation: slide-up 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

.scrollbar-custom::-webkit-scrollbar { width: 8px; height: 8px; }
.scrollbar-custom::-webkit-scrollbar-track { background: #f8fafc; }
.scrollbar-custom::-webkit-scrollbar-thumb { background: #e2e8f0; }
.scrollbar-custom::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>
