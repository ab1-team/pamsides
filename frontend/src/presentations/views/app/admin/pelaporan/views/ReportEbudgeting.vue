<template>
  <BaseReportLayout :lembaga="payload?.lembaga" :config="payload?.config">
    <div class="header-section" style="text-align:center;margin-bottom:15px;font-family:sans-serif;">
      <h2 style="margin:0;font-size:14pt;font-weight:bold;text-transform:uppercase;color:#000;">
        LAPORAN PENGGUNAAN DANA (E-BUDGETING)
      </h2>
      <h3 style="margin:-5px 0 0 0;font-size:12pt;font-weight:bold;color:#000;">
        TAHUN {{ payload?.thn }}
      </h3>
    </div>

    <table class="data-table">
      <thead>
        <tr>
          <th rowspan="3" width="29%">Rekening</th>
          <th rowspan="3" width="10%">Komulatif Bulan Lalu</th>
          <th :colspan="payload.bulan_tampil.length * 2" class="text-center">Detail Bulan</th>
        </tr>
        <tr>
          <th v-for="bln in payload.bulan_tampil" :key="bln" colspan="2" class="text-center">
            {{ formatBulan(bln) }}
          </th>
        </tr>
        <tr>
          <template v-for="bln in payload.bulan_tampil" :key="'h-' + bln">
            <th class="text-center">Rencana</th>
            <th class="text-center">Realisasi</th>
          </template>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in payload.items" :key="index">
          <td class="text-left">{{ item.nama }}</td>
          <td class="text-right">{{ formatNumber(item.komulatif) }}</td>
          <td class="text-right">{{ formatNumber(item.rencana1) }}</td>
          <td class="text-right">{{ formatNumber(item.realisasi1) }}</td>
          <td class="text-right">{{ formatNumber(item.rencana2) }}</td>
          <td class="text-right">{{ formatNumber(item.realisasi2) }}</td>
          <td class="text-right">{{ formatNumber(item.rencana3) }}</td>
          <td class="text-right">{{ formatNumber(item.realisasi3) }}</td>
        </tr>
      </tbody>
      <tfoot>
        <tr class="summary-row">
          <td class="text-center"><b>TOTAL</b></td>
          <td class="text-right"><b>{{ formatNumber(totalColumn('komulatif')) }}</b></td>
          <td class="text-right"><b>{{ formatNumber(totalColumn('rencana1')) }}</b></td>
          <td class="text-right"><b>{{ formatNumber(totalColumn('realisasi1')) }}</b></td>
          <td class="text-right"><b>{{ formatNumber(totalColumn('rencana2')) }}</b></td>
          <td class="text-right"><b>{{ formatNumber(totalColumn('realisasi2')) }}</b></td>
          <td class="text-right"><b>{{ formatNumber(totalColumn('rencana3')) }}</b></td>
          <td class="text-right"><b>{{ formatNumber(totalColumn('realisasi3')) }}</b></td>
        </tr>
      </tfoot>
    </table>
  </BaseReportLayout>
</template>

<script setup>
import BaseReportLayout from '../layouts/BaseReportLayout.vue'

const props = defineProps({
  payload: { type: Object, required: true }
})

const formatNumber = (val) => {
  return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val || 0);
}

const formatBulan = (angka) => {
  const bulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
  return bulan[parseInt(angka)];
}

const totalColumn = (key) => {
  return props.payload.items.reduce((sum, item) => sum + (Number(item[key]) || 0), 0);
}
</script>

<style scoped>
.data-table, .data-table th, .data-table td {
  font-size: 12px !important; 
  font-family: sans-serif !important;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
}

.data-table th, .data-table td {
  border: 1px solid #000 !important;
  padding: 4px 4px !important;
  line-height: 1.2 !important;
}

.data-table th {
  background: #d9d9d9 !important;
  text-align: center;
  font-weight: bold;
}

.summary-row {
  background: #efefef !important;
}

.text-right { text-align: right; }
.text-left { text-align: left; }
.text-center { text-align: center; }
</style>