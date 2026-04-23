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
                  2.1.01.01 - Utang Dividen Pemdes Bulan April 2026
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
                  <td class="py-4! px-4! font-mono text-xs text-slate-500">{{ item.kodeAkun }}</td>
                  <td class="py-4! px-4! text-slate-700">{{ item.keterangan }}</td>
                  <td class="py-4! px-4! font-mono text-xs text-slate-500">{{ item.idTrx }}</td>
                  <td class="py-4! px-4! text-right font-mono text-slate-700">{{ item.debit }}</td>
                  <td class="py-4! px-4! text-right font-mono text-slate-700">{{ item.kredit }}</td>
                  <td class="py-4! px-4! text-center font-mono text-slate-700">{{ item.saldo }}</td>
                </tr>

                <tr class="bg-slate-100">
                  <td colspan="6" class="py-4! px-6! font-semibold text-slate-800">
                    Total Transaksi Bulan April 2026
                  </td>
                  <td class="py-4! px-4! text-right font-mono text-slate-800">0.00</td>
                  <td class="py-4! px-4! text-right font-mono text-slate-800">0.00</td>
                  <td></td>
                </tr>

                <tr class="bg-white">
                  <td colspan="6" class="py-4! px-6! font-semibold text-slate-800">
                    Total Transaksi sampai dengan Bulan April 2026
                  </td>
                  <td class="py-4! px-4! text-right font-mono text-slate-800">0.00</td>
                  <td class="py-4! px-4! text-right font-mono text-slate-800">0.00</td>
                  <td class="py-4! px-4! text-center font-mono text-slate-800">0.00</td>
                </tr>

                <tr class="bg-slate-100 border-b border-slate-200">
                  <td colspan="6" class="py-4! px-6! font-semibold text-slate-800">
                    Total Transaksi Komulatif sampai dengan Tahun 2026
                  </td>
                  <td class="py-4! px-4! text-right font-mono text-slate-800">0.00</td>
                  <td class="py-4! px-4! text-right font-mono text-slate-800">0.00</td>
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
})

const emit = defineEmits(['close'])

const items = ref([
  {
    no: 1,
    tanggal: '01/01/2026',
    kodeAkun: '',
    keterangan: 'Komulatif Transaksi Awal Tahun 2026',
    idTrx: '',
    debit: '0.00',
    kredit: '0.00',
    saldo: '0.00',
    selected: false,
  },
  {
    no: 2,
    tanggal: '01/04/2026',
    kodeAkun: '',
    keterangan: 'Komulatif Transaksi s/d Bulan Lalu',
    idTrx: '',
    debit: '0.00',
    kredit: '0.00',
    saldo: '0.00',
    selected: false,
  },
])

const isAllSelected = computed({
  get: () => items.value.length > 0 && items.value.every((item) => item.selected),
  set: (value) => {
    items.value.forEach((item) => (item.selected = value))
  },
})

const close = () => {
  emit('close')
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
