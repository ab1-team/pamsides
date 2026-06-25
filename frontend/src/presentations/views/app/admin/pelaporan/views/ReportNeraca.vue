<template>
  <BaseReportLayout :config="payload?.config">
    <div class="header-section" style="text-align: center; margin-bottom: 15px; font-family: sans-serif;">
      <h2 style="margin: 0; font-size: 14pt; font-weight: bold; text-transform: uppercase; color: #000;">
        NERACA
      </h2>
      <h3 style="margin: -5px 0 0 0; font-size: 12pt; font-weight: bold; color: #000;">
        PER {{ periodeText }}
      </h3>
    </div>

    <table class="report-table">
      <thead>
        <tr>
          <th style="width: 15%;">Kode</th>
          <th style="width: 55%;">Nama Akun</th>
          <th style="width: 30%;">Saldo</th>
        </tr>
      </thead>
      <tbody>
        <tr class="header-row">
          <td colspan="3">1.0.00.00. Aset</td>
        </tr>
        <tr class="sub-header">
          <td colspan="3">1.1.00.00. Aset Lancar</td>
        </tr>
        
        <tr v-for="(item, index) in (payload?.items || [])" :key="index">
          <td class="text-center">{{ item.kode }}</td>
          <td>{{ item.nama_akun }}</td>
          <td class="text-right">{{ formatCurrency(item.saldo) }}</td>
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
    // Mengambil tanggal terakhir dari periode jika tersedia, atau default ke akhir bulan
    return `${p.tanggal || '30'} ${(p.bulan_name || '').toUpperCase()} ${p.tahun || ''}`
  })

  const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2 }).format(val || 0)
  }
</script>

<style scoped>
  .report-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 9pt;
  }
  .report-table th, .report-table td {
    padding: 6px 8px;
    border: 1px solid #000;
  }
  .report-table th {
    background-color: #424242;
    color: #fff;
    text-align: center;
  }
  .header-row {
    background-color: #4b4b4b;
    color: #fff;
    font-weight: bold;
  }
  .sub-header {
    background-color: #d1d1d1;
    font-weight: bold;
  }
  .text-center { text-align: center; }
  .text-right { text-align: right; }
</style>