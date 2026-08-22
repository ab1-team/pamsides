<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4! md:p-8!">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" @click="close"></div>
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
                  Detail Transaksi Pemakaian
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
                  <th class="py-3! px-4! text-xs font-semibold uppercase tracking-wide border-b border-slate-700 text-center w-12">No</th>
                  <th class="py-3! px-4! text-xs font-semibold uppercase tracking-wide border-b border-slate-700 whitespace-nowrap">Tanggal</th>
                  <th class="py-3! px-4! text-xs font-semibold uppercase tracking-wide border-b border-slate-700 whitespace-nowrap">Kode Akun</th>
                  <th class="py-3! px-4! text-xs font-semibold uppercase tracking-wide border-b border-slate-700 min-w-[200px]">Keterangan</th>
                  <th class="py-3! px-4! text-xs font-semibold uppercase tracking-wide border-b border-slate-700 whitespace-nowrap">ID Trx</th>
                  <th class="py-3! px-4! text-xs font-semibold uppercase tracking-wide border-b border-slate-700 text-right">Debit</th>
                  <th class="py-3! px-4! text-xs font-semibold uppercase tracking-wide border-b border-slate-700 text-right">Kredit</th>
                  <th class="py-3! px-4! text-xs font-semibold uppercase tracking-wide border-b border-slate-700 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200">
                <template v-if="paidBills.length > 0">
                  <tr
                    v-for="(bill, index) in paidBills"
                    :key="bill.id"
                    class="hover:bg-slate-50 transition-colors"
                  >
                    <td class="py-4! px-4! text-center text-slate-600">{{ index + 1 }}</td>
                    <td class="py-4! px-4! text-slate-700">
                      {{ bill.payments?.[0]?.paidAt || bill.statusDate || '-' }}
                    </td>
                    <td class="py-4! px-4! font-mono text-xs text-slate-500">1101</td>
                    <td class="py-4! px-4! text-slate-700">
                      Pembayaran Air Periode {{ bill.period }} ({{ bill.customerName }})
                    </td>
                    <td class="py-4! px-4! font-mono text-xs text-slate-500">
                      BP-{{ bill.payments?.[0]?.id || bill.id }}
                    </td>
                    <td class="py-4! px-4! text-right font-mono text-slate-700">0,00</td>
                    <td class="py-4! px-4! text-right font-mono text-emerald-600 font-semibold">
                      {{ formatRupiah(bill.amount) }}
                    </td>
                    <td class="py-4! px-4! text-center">
                      <div class="flex items-center justify-center gap-2!">
                        <button
                          class="w-8! h-8! rounded-lg! bg-blue-50! text-blue-600! hover:bg-blue-100! transition-all active:scale-90"
                          title="Cetak Kuitansi"
                          @click="handlePrint(bill)"
                        >
                          <font-awesome-icon icon="print" />
                        </button>
                        <button
                          class="w-8! h-8! rounded-lg! bg-red-50! text-red-600! hover:bg-red-100! transition-all active:scale-90"
                          title="Rollback ke belum dibayar"
                          @click="handleDelete(bill)"
                        >
                          <font-awesome-icon icon="trash" />
                        </button>
                      </div>
                    </td>
                  </tr>
                </template>
                <tr v-else>
                  <td colspan="8" class="py-8! text-center text-slate-400 italic">
                    Belum ada riwayat transaksi pembayaran lunas untuk pelanggan ini.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div
            class="px-6! py-4! bg-white border-t border-slate-200 flex justify-end items-center gap-3!"
          >
            <button
              @click="close"
              class="bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 px-6! py-2.5! font-semibold transition-all active:scale-95 rounded-lg! text-sm"
            >
              Tutup
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Kuitansi Print (hidden, only for printing) -->
    <div ref="printArea" class="hidden">
      <div id="print-content" v-if="printBill">
        <div style="font-family: Arial, sans-serif; padding: 32px; max-width: 600px; margin: 0 auto;">
          <div style="text-align: center; border-bottom: 3px double #333; padding-bottom: 16px; margin-bottom: 20px;">
            <h2 style="margin: 0; font-size: 18px;">KUITANSI PEMBAYARAN</h2>
            <p style="margin: 4px 0 0; font-size: 12px; color: #666;">PAMSIDES - Layanan Air Bersih</p>
          </div>

          <table style="width: 100%; font-size: 13px; margin-bottom: 20px;">
            <tbody>
              <tr>
                <td style="padding: 4px 0; width: 130px; color: #666;">No. Kuitansi</td>
                <td style="padding: 4px 0;">: BP-{{ printBill.payments?.[0]?.id || printBill.id }}</td>
              </tr>
              <tr>
                <td style="padding: 4px 0; color: #666;">Tanggal</td>
                <td style="padding: 4px 0;">: {{ printBill.payments?.[0]?.paidAt || printBill.statusDate || '-' }}</td>
              </tr>
              <tr>
                <td style="padding: 4px 0; color: #666;">Pelanggan</td>
                <td style="padding: 4px 0;">: {{ printBill.customerName }}</td>
              </tr>
              <tr>
                <td style="padding: 4px 0; color: #666;">ID Pelanggan</td>
                <td style="padding: 4px 0;">: {{ printBill.customerId }}</td>
              </tr>
              <tr>
                <td style="padding: 4px 0; color: #666;">Periode</td>
                <td style="padding: 4px 0;">: {{ printBill.period }}</td>
              </tr>
            </tbody>
          </table>

          <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 20px;">
            <thead>
              <tr style="background: #f1f5f9;">
                <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Keterangan</th>
                <th style="padding: 8px; border: 1px solid #ddd; text-align: right;">Jumlah</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td style="padding: 8px; border: 1px solid #ddd;">Pemakaian Air ({{ printBill.pemakaian }} m³)</td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">{{ formatRupiah(printBill.usage_charge) }}</td>
              </tr>
              <tr>
                <td style="padding: 8px; border: 1px solid #ddd;">Abodemen</td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">{{ formatRupiah(printBill.abodemen) }}</td>
              </tr>
              <tr v-if="printBill.denda > 0">
                <td style="padding: 8px; border: 1px solid #ddd;">Denda</td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">{{ formatRupiah(printBill.denda) }}</td>
              </tr>
              <tr style="font-weight: bold; background: #f0fdf4;">
                <td style="padding: 8px; border: 1px solid #ddd;">TOTAL PEMBAYARAN</td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: right; color: #059669;">{{ formatRupiah(printBill.amount) }}</td>
              </tr>
            </tbody>
          </table>

          <div style="display: flex; justify-content: space-between; margin-top: 40px; font-size: 12px;">
            <div style="text-align: center; width: 45%;">
              <p style="margin: 0; color: #666;">Pelanggan</p>
              <br><br><br>
              <p style="margin: 0; border-top: 1px solid #333; padding-top: 4px;">{{ printBill.customerName }}</p>
            </div>
            <div style="text-align: center; width: 45%;">
              <p style="margin: 0; color: #666;">Petugas</p>
              <br><br><br>
              <p style="margin: 0; border-top: 1px solid #333; padding-top: 4px;">_________________</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue'
import { useBillingStore } from '@/stores/billingStore'
import { formatRupiah } from '@/composables/useFormatCurrency'
import { MySwal } from '@/utils/swal.js'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
})

const billingStore = useBillingStore()
const printArea = ref(null)
const printBill = ref(null)

const paidBills = computed(() => {
  return billingStore.billingPeriods.filter((p) => p.type === 'paid' || p.status === 'LUNAS')
})

const emit = defineEmits(['close'])

const close = () => {
  emit('close')
}

const handleDelete = async (bill) => {
  const confirm = await MySwal.fire({
    title: 'Rollback Pembayaran?',
    text: `Pembayaran ${bill.period} (${bill.customerName}) akan dikembalikan ke status belum dibayar.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Rollback',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#EF4444',
    cancelButtonColor: '#64748b',
    customClass: {
      popup: 'rounded-2xl!',
      confirmButton: 'rounded-xl! px-4! py-2! font-bold!',
      cancelButton: 'rounded-xl! px-4! py-2! font-medium!',
    },
  })

  if (!confirm.isConfirmed) return

  const result = await billingStore.deleteBill(bill.id)
  if (result.success) {
    MySwal.fire({
      title: 'Terhapus!',
      text: result.message,
      icon: 'success',
      confirmButtonText: 'OK',
      confirmButtonColor: '#10B981',
      customClass: {
        popup: 'rounded-2xl!',
        confirmButton: 'rounded-xl! px-5! py-2.5! font-bold!',
      },
    })
  } else {
    MySwal.fire({
      title: 'Gagal',
      text: result.message,
      icon: 'error',
      confirmButtonText: 'OK',
      confirmButtonColor: '#EF4444',
    })
  }
}

const handlePrint = (bill) => {
  printBill.value = bill
  setTimeout(() => {
    const content = document.getElementById('print-content')
    if (!content) return
    const printWindow = window.open('', '_blank')
    printWindow.document.write(`
      <html>
        <head>
          <title>Kuitansi Pembayaran - BP-${bill.payments?.[0]?.id || bill.id}</title>
          <style>
            @media print {
              body { margin: 0; }
            }
          </style>
        </head>
        <body>${content.innerHTML}</body>
      </html>
    `)
    printWindow.document.close()
    printWindow.focus()
    printWindow.print()
    printWindow.close()
    printBill.value = null
  }, 100)
}

const handleKeydown = (e) => {
  if (e.key === 'Escape' && props.show) {
    close()
  }
}

watch(
  () => props.show,
  (val) => {
    document.body.style.overflow = val ? 'hidden' : ''
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
