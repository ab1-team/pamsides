<template>
  <BaseReportLayout :config="payload?.config">
    
    <div class="header-section" style="text-align: center; margin-bottom: 10px; font-family: sans-serif;">
  <h2 style="margin: 0; font-size: 14pt; font-weight: bold; text-transform: uppercase; color: #000;">
    JURNAL TRANSAKSI
  </h2>
  <h3 style="margin: -5px 0 0 0; font-size: 12pt; font-weight: bold; color: #000;">
    BULAN {{ periodeText }}
  </h3>
</div>

    <table class="data-table">
      <thead>
        <tr>
          <th style="width: 5%;">No</th>
          <th style="width: 12%;">Tanggal</th>
          <th style="width: 10%;">Ref ID.</th>
          <th style="width: 10%;">Kd. Rek</th>
          <th style="width: 35%;">Keterangan</th>
          <th style="width: 12%;">Debit</th>
          <th style="width: 12%;">Kredit</th>
          <th style="width: 4%;">Ins</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="i in 10" :key="i">
          <td class="text-center">{{ i }}</td>
          <td class="text-center">-</td>
          <td class="text-center">-</td>
          <td class="text-center">-</td>
          <td>-</td>
          <td class="text-right">-</td>
          <td class="text-right">-</td>
          <td class="text-center">NA</td>
        </tr>
      </tbody>
    </table>

  </BaseReportLayout>
</template>

<script setup>
  import { computed } from 'vue'
  import BaseReportLayout from '../layouts/BaseReportLayout.vue'

  const props = defineProps({
    payload: { type: Object, default: () => ({ config: {}, items: [] }) },
    meta: { type: Object, default: () => ({}) },
  })

  const periodeText = computed(() => {
    const m = props.meta || {}
    return `${(m.bulan_name || '').toUpperCase()} ${m.tahun || ''}`
  })
</script>

<style scoped>
  .header-section {
    text-align: center;
    margin-bottom: 20px;
  }
  .header-section .title {
    margin: 0;
    font-size: 14pt;
    font-weight: bold;
    text-transform: uppercase;
  }
  .header-section .period {
    margin: 5px 0 0 0;
    font-size: 12pt;
  }
  .data-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
  }
  .data-table th {
    background-color: #424242; /* Warna header gelap sesuai gambar */
    color: #ffffff;
    font-weight: bold;
    padding: 8px 4px;
    font-size: 9pt;
    text-align: center;
    border: 1px solid #000;
  }
  .data-table td {
    padding: 6px 5px;
    border: 1px solid #000;
    font-size: 8.5pt;
    color: #000;
  }
  .text-center { text-align: center; }
  .text-right { text-align: right; }
</style>