<template>
  <BaseReportLayout :config="payload?.config">
    
    <div class="main-report-header">
      <h2>DAFTAR LISENSI</h2>
      <h2 class="mt-0 mb-0 leading-tight uppercase">
        BULAN {{ payload?.periode?.bulan_name || '' }} {{ payload?.periode?.tahun || '' }}
      </h2>
    </div>

    <table class="data-table">
      <thead>
        <tr>
          <th rowspan="2">No</th>
          <th rowspan="2">Tgl Beli</th>
          <th rowspan="2">Nama Barang</th>
          <th rowspan="2">Id</th>
          <th rowspan="2">Kondisi</th>
          <th rowspan="2">Unit</th>
          <th rowspan="2">Harga Satuan</th>
          <th rowspan="2">Harga Perolehan</th>
          <th rowspan="2">Umur Eko.</th>
          <th rowspan="2">Amortisasi</th>
          <th colspan="2">Tahun Ini</th>
          <th colspan="2">s.d. Tahun Ini</th>
          <th rowspan="2">Nilai Buku</th>
        </tr>
        <tr>
          <th>Umur</th>
          <th>Biaya</th>
          <th>Umur</th>
          <th>Biaya</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="i in 6" :key="i">
          <td class="text-center">{{ i }}</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr>
        <tr class="total-row">
          <td colspan="5" class="text-right"><b>Jumlah</b></td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
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
  margin-bottom: 15px;
}
.main-report-header h2 {
  margin: 0;
  font-size: 11pt;
  font-weight: bold;
  color: #000000;
}
.data-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}
.data-table th {
  border: 1px solid #000000;
  padding: 4px; 
  font-size: 7.5pt;
  text-align: center;
  background-color: #f2f2f2;
}
.data-table td {
  padding: 4px;
  border: 1px solid #000000;
  font-size: 7.5pt;
  height: 20px;
}
.total-row {
  background-color: #f9f9f9;
  font-weight: bold;
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