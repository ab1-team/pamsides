<template>
  <BaseReportLayout :config="payload?.config">
    <div class="header-section" style="text-align: center; margin-bottom: 15px; font-family: sans-serif;">
      <h2 style="margin: 0; font-size: 14pt; font-weight: bold; text-transform: uppercase; color: #000;">
        NERACA
      </h2>
      <h3 style="margin: -5px 0 0 0; font-size: 12pt; font-weight: bold; color: #000;">
        BULAN {{ periodeText }}
      </h3>
    </div>

    <table class="report-table">
      <thead>
        <tr>
          <th rowspan="2" style="width: 40%;">Rekening</th>
          <th colspan="2">Neraca Saldo</th>
          <th colspan="2">Laba Rugi</th>
          <th colspan="2">Neraca</th>
        </tr>
        <tr>
          <th>Debit</th>
          <th>Kredit</th>
          <th>Debit</th>
          <th>Kredit</th>
          <th>Debit</th>
          <th>Kredit</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="i in 5" :key="i">
          <td>1.1.01.0{{ i }}. Nama Akun</td>
          <td class="text-right">0.00</td>
          <td class="text-right">0.00</td>
          <td class="text-right">0.00</td>
          <td class="text-right">0.00</td>
          <td class="text-right">0.00</td>
          <td class="text-right">0.00</td>
        </tr>
      </tbody>
    </table>
  </BaseReportLayout>
</template>

<script setup>
  import { computed } from 'vue'
  import BaseReportLayout from '../layouts/BaseReportLayout.vue'

  const props = defineProps({
    payload: { type: Object, required: true }
  })

  const periodeText = computed(() => {
    const p = props.payload?.periode || {}
    return `${(p.bulan_name || '').toUpperCase()} ${p.tahun || ''}`
  })
</script>

<style scoped>
  .report-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 8.5pt;
  }
  .report-table th, .report-table td {
    padding: 6px 4px;
    border: 1px solid #000;
  }
  .report-table th {
    background-color: #d1d1d1; /* Sesuai warna abu-abu header di gambar */
    color: #000;
    font-weight: bold;
    text-align: center;
  }
  .text-right { text-align: right; }
</style>