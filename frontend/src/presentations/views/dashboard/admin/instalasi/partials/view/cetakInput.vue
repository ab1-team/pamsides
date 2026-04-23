<template>
  <div class="min-h-screen bg-slate-100 p-0 md:p-8 flex justify-center no-print-bg">
    <div class="print-paper bg-white shadow-2xl relative">
      <div class="print-container bg-white p-4 font-sans text-slate-900 overflow-hidden">
        <div class="header text-center mb-6">
          <h3 class="text-xs font-bold tracking-[2px] mb-1">FORM INPUT CATER</h3>
          <h1 class="text-lg font-extrabold mb-1 uppercase tracking-tight">
            "TIRTO MULO" BUMDes BANGUN KENCANA
          </h1>
          <h2 class="text-xs font-semibold uppercase tracking-wide">
            KALURAHAN MULO KAPANEWON WONOSARI
          </h2>
          <div class="w-full h-[2px] bg-slate-800 mt-2 mb-4"></div>
        </div>

        <div class="metadata flex justify-between items-start text-xs font-medium mb-4 px-2">
          <div class="left-info space-y-1">
            <div class="flex gap-2">
              <span class="w-32">Bulan Pemakaian</span>
              <span>: {{ filter.bulan }} {{ filter.tahun }}</span>
            </div>
            <div class="flex gap-2">
              <span class="w-32">Cater</span>
              <span>: {{ filter.cater || '-' }}</span>
            </div>
          </div>
          <div class="right-info">
            <div class="flex gap-2">
              <span class="w-12">Dusun</span>
              <span>: {{ dusun || 'Kepil' }}</span>
            </div>
          </div>
        </div>

        <table class="w-full border-collapse border border-slate-800 text-[11px]">
          <thead>
            <tr class="bg-slate-50">
              <th
                class="border border-slate-800 px-2 py-2 w-8 text-center uppercase tracking-tighter"
              >
                No
              </th>
              <th
                class="border border-slate-800 px-3 py-2 text-center uppercase font-bold tracking-tight"
              >
                Nama
              </th>
              <th
                class="border border-slate-800 px-3 py-2 text-center uppercase font-bold tracking-tight"
              >
                No. Induk
              </th>
              <th class="border border-slate-800 px-2 py-2 w-10 text-center uppercase">RT</th>
              <th
                class="border border-slate-800 px-3 py-2 w-16 text-center uppercase font-bold tracking-tight"
              >
                Awal
              </th>
              <th
                class="border border-slate-800 px-3 py-2 w-16 text-center uppercase font-bold tracking-tight"
              >
                Akhir
              </th>
              <th
                class="border border-slate-800 px-3 py-2 text-center uppercase font-bold tracking-tight"
              >
                Keterangan
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in filteredData" :key="index" class="h-9">
              <td class="border border-slate-800 px-2 text-center">{{ index + 1 }}</td>
              <td class="border border-slate-800 px-3 text-left font-medium">{{ item.nama }}</td>
              <td class="border border-slate-800 px-3 text-center font-mono">{{ item.id }}</td>
              <td class="border border-slate-800 px-2 text-center">{{ item.rt || '-' }}</td>
              <td class="border border-slate-800 px-3 text-center">{{ item.meterAwal || 0 }}</td>
              <td class="border border-slate-800 px-3 text-center"></td>
              <td class="border border-slate-800 px-3 text-center"></td>
            </tr>

            <tr v-for="n in Math.max(0, 15 - filteredData.length)" :key="'empty-' + n" class="h-9">
              <td class="border border-slate-800 px-2 text-center">
                {{ filteredData.length + n }}
              </td>
              <td class="border border-slate-800 px-3"></td>
              <td class="border border-slate-800 px-3"></td>
              <td class="border border-slate-800 px-2"></td>
              <td class="border border-slate-800 px-3"></td>
              <td class="border border-slate-800 px-3"></td>
              <td class="border border-slate-800 px-3"></td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="fixed bottom-8 right-8 no-print">
        <button
          @click="triggerPrint"
          class="flex items-center gap-2! px-6! py-3! bg-amber-500 hover:bg-amber-600 text-white rounded-full font-bold shadow-2xl shadow-amber-500/40 transition-all active:scale-95"
        >
          <font-awesome-icon icon="print" />
          CETAK FORM
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { usePemakaianAir } from '@/composables/usePemakaianAir'

const { filteredData, filter } = usePemakaianAir()

const triggerPrint = () => {
  window.print()
}

onMounted(() => {
  setTimeout(() => {
    window.print()
  }, 500)
})
</script>

<style scoped>
.print-paper {
  width: 210mm;
  min-height: 297mm;
  padding: 10mm;
  margin: auto;
}

@media print {
  .no-print {
    display: none !important;
  }
  .min-h-screen {
    background: white !important;
    padding: 0 !important;
    display: block !important;
  }
  .print-paper {
    width: 100%;
    box-shadow: none !important;
    padding: 0 !important;
    margin: 0 !important;
  }
  .print-container {
    padding: 0;
    margin: 0;
    width: 100%;
  }
  @page {
    margin: 1cm;
    size: A4 portrait;
  }
}

@media (max-width: 210mm) {
  .print-paper {
    width: 100%;
    padding: 5mm;
  }
}

.header h1 {
  font-family: 'Plus Jakarta Sans', Arial, sans-serif;
}

table th {
  background-color: #f8fafc !important;
  -webkit-print-color-adjust: exact;
}

table td,
table th {
  border-width: 1px !important;
  border-color: #1e293b !important;
}
</style>
