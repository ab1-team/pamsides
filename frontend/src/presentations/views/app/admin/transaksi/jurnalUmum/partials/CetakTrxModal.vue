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
                    class="py-3! px-4! text-xs font-semibold uppercase tracking-wide border-b border-slate-700 text-center w-12"
                  >
                    <input
                      type="checkbox"
                      v-model="isAllSelected"
                      class="form-checkbox h-4 w-4 text-slate-600 rounded border-slate-300 focus:ring-slate-500"
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
                    Kode Akun Debet
                  </th>
                  <th
                    class="py-3! px-4! text-xs font-semibold uppercase tracking-wide border-b border-slate-700 whitespace-nowrap"
                  >
                    Kode Akun Kredit
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
                  <th
                    class="py-3! px-4! text-xs font-semibold uppercase tracking-wide border-b border-slate-700 text-center"
                  >
                    Aksi
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200">
                <tr
                  v-for="(item, index) in items"
                  :key="index"
                  class="hover:bg-slate-50 transition-colors"
                >
                  <td class="py-4! px-4! text-center">
                    <input
                      type="checkbox"
                      v-model="item.selected"
                      class="form-checkbox h-4 w-4 text-slate-600 rounded border-slate-300 focus:ring-slate-500"
                    />
                  </td>
                  <td class="py-4! px-4! text-center text-slate-600">{{ item.no }}</td>
                  <td class="py-4! px-4! text-slate-700">{{ item.tanggal }}</td>
                  <td class="py-4! px-4! font-mono text-xs text-slate-500">{{ item.kodeAkunDebet }}</td>
                  <td class="py-4! px-4! font-mono text-xs text-slate-500">{{ item.kodeAkunKredit }}</td>
                  <td class="py-4! px-4! text-slate-700">{{ item.keterangan }}</td>
                  <td class="py-4! px-4! font-mono text-xs text-slate-500">{{ item.idTrx }}</td>
                  <td class="py-4! px-4! text-right font-mono text-slate-700">{{ item.debit }}</td>
                  <td class="py-4! px-4! text-right font-mono text-slate-700">{{ item.kredit }}</td>
                  <td class="py-4! px-4! text-center font-mono text-slate-700">{{ item.saldo }}</td>
                  <td class="py-4! px-4! text-center">
                    <div class="flex items-center justify-center gap-2!">
                      <button
                        class="w-8! h-8! rounded-lg! bg-blue-50! text-blue-600! hover:bg-blue-100! transition-all active:scale-90"
                        title="Cetak"
                      >
                        <font-awesome-icon icon="print" />
                      </button>
                      <button
                        class="w-8! h-8! rounded-lg! bg-red-50! text-red-600! hover:bg-red-100! transition-all active:scale-90"
                        title="Hapus"
                        @click="deleteTransaction(item.idTrx)"
                      >
                        <font-awesome-icon icon="trash" />
                      </button>
                    </div>
                  </td>
                </tr>

                <tr class="bg-slate-100">
                  <td colspan="8" class="py-4! px-6! font-semibold text-slate-800">
                    Total Transaksi
                  </td>
                  <td class="py-4! px-4! text-right font-mono text-slate-800">{{ formatCurrency(totalDebit) }}</td>
                  <td class="py-4! px-4! text-right font-mono text-slate-800">{{ formatCurrency(totalKredit) }}</td>
                  <td></td>
                </tr>
              </tbody>
            </table>
          </div>

          <div
            class="px-6! py-6! bg-white border-t border-slate-200 flex justify-end items-center gap-3! min-h-[80px]!"
          >
            <button
              class="flex items-center gap-2! bg-slate-700 hover:bg-slate-800 text-white px-6! py-3! font-semibold transition-all active:scale-95 rounded-lg!"
            >
              <font-awesome-icon icon="print" />
              Cetak
            </button>
            <button
              @click="close"
              class="bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 px-6! py-3! font-semibold transition-all active:scale-95 rounded-lg!"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  transactions: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Cetak Bukti Transaksi',
  },
})

const emit = defineEmits(['close', 'delete'])

const items = computed(() => {
  return props.transactions.map((trx, index) => ({
    no: index + 1,
    tanggal: formatDate(trx.tgl_transaksi),
    kodeAkunDebet: (trx.account_debet?.kode_akun || trx.account_debet) + (trx.account_debet?.nama_akun ? ` (${trx.account_debet.nama_akun})` : ''),
    kodeAkunKredit: (trx.account_kredit?.kode_akun || trx.account_kredit) + (trx.account_kredit?.nama_akun ? ` (${trx.account_kredit.nama_akun})` : ''),
    keterangan: trx.keterangan_transaksi || '-',
    idTrx: trx.id,
    debit: formatCurrency(trx.saldo),
    kredit: formatCurrency(trx.saldo),
    saldo: '-',
    selected: false,
  }))
})

const isAllSelected = computed({
  get: () => items.value.length > 0 && items.value.every((item) => item.selected),
  set: (value) => {
    items.value.forEach((item) => (item.selected = value))
  },
})

const totalDebit = computed(() => {
  return props.transactions.reduce((sum, trx) => sum + (parseFloat(trx.saldo) || 0), 0)
})

const totalKredit = computed(() => {
  return props.transactions.reduce((sum, trx) => sum + (parseFloat(trx.saldo) || 0), 0)
})

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

const formatCurrency = (amount) => {
  if (!amount) return '0'
  return new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}

const close = () => {
  emit('close')
}

const deleteTransaction = (id) => {
  emit('delete', id)
}

const handleKeydown = (e) => {
  if (e.key === 'Escape' && props.show) {
    close()
  }
}

watch(
  () => props.show,
  (val) => {
    if (val) {
      document.body.style.overflow = 'hidden'
    } else {
      document.body.style.overflow = ''
    }
  },
)

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

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
  from {
    transform: translateY(30px) scale(0.98);
    opacity: 0;
  }
  to {
    transform: translateY(0) scale(1);
    opacity: 1;
  }
}

.animate-slide-up {
  animation: slide-up 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

.scrollbar-custom::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

.scrollbar-custom::-webkit-scrollbar-track {
  background: #f8fafc;
}

.scrollbar-custom::-webkit-scrollbar-thumb {
  background: #e2e8f0;
}

.scrollbar-custom::-webkit-scrollbar-thumb:hover {
  background: #cbd5e1;
}
</style>
