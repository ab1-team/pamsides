<template>
  <BaseReportLayout :config="payload?.config">
    
    <div class="main-report-header">
      <h2>LAPORAN PERUBAHAN MODAL</h2>
      <h2 class="mt-0 mb-0 leading-tight uppercase">
        BULAN {{ payload?.periode?.bulan_name || '' }} {{ payload?.periode?.tahun || '' }}
      </h2>
    </div>

    <table class="data-table">
      <thead>
        <tr>
          <th style="width: 8%;">No</th>
          <th style="width: 62%;">Rekening Modal</th>
          <th style="width: 30%;">Jumlah</th>
        </tr>
      </thead>
      <tbody>
        <!-- Baris Kosong untuk Data -->
        <tr v-for="i in 5" :key="i">
          <td class="text-center">{{ i }}</td>
          <td>&nbsp;</td>
          <td class="text-right">&nbsp;</td>
        </tr>
        
        <!-- Baris Total -->
        <tr class="total-row">
          <td colspan="2" class="text-center"><b>TOTAL</b></td>
          <td class="text-right"><b>&nbsp;</b></td>
        </tr>
      </tbody>
    </table>

    <div class="footer-container">
      <div class="footer-sign">
        <p>Diperiksa oleh,</p>
        <p style="margin-bottom: 50px;">Direktur</p>
        <p>( ........................... )</p>
      </div>
      <div class="footer-sign">
        <p>Kedung, {{ tanggalCetak }}</p>
        <p style="margin-bottom: 50px;">Dilaporkan oleh,</p>
        <p>( ........................... )</p>
      </div>
    </div>

  </BaseReportLayout>
</template>

<script setup>
import { computed } from 'vue'
import BaseReportLayout from '../layouts/BaseReportLayout.vue'

defineProps({
  payload: { type: Object, default: () => ({ config: {}, periode: {} }) }
})

const tanggalCetak = computed(() => {
  return new Date().toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
})
</script>

<style scoped>
.main-report-header {
  text-align: center;
  margin-top: 5px;
  margin-bottom: 15px;
}
.main-report-header h2 {
  margin: 0;
  font-size: 11pt;
  font-weight: bold;
  color: #000000;
  line-height: 1.2;
}
.data-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}
.data-table th {
  border: 1px solid #000000;
  color: #000000;
  font-weight: bold;
  padding: 8px 4px; 
  font-size: 8.5pt;
  text-align: center;
  background-color: #f2f2f2;
}
.data-table td {
  padding: 6px 8px; /* Diberi sedikit tinggi agar baris kosong terlihat jelas */
  border: 1px solid #000000;
  font-size: 8.5pt;
  color: #000000;
  height: 20px;
}
.total-row {
  background-color: #f9f9f9;
}
.text-center { text-align: center; }
.text-right { text-align: right; }

.footer-container {
  width: 100%;
  margin-top: 30px;
  display: flex;
  justify-content: space-between;
}
.footer-sign {
  width: 40%;
  text-align: center;
  font-size: 8.5pt;
}
.mt-0 { margin-top: 0px !important; }
.mb-0 { margin-bottom: 0px !important; }
</style>