<template>
  <BaseReportLayout :config="payload?.config">
    <div class="header-section" style="text-align: center; margin-bottom: 15px; font-family: sans-serif;">
      <h2 style="margin: 0; font-size: 14pt; font-weight: bold; text-transform: uppercase; color: #000;">
        BUKU BESAR {{ payload?.nama_akun || '' }}
      </h2>
      <h3 style="margin: -5px 0 0 0; font-size: 12pt; font-weight: bold; color: #000;">
        BULAN {{ periodeText }}
      </h3>
      <div style="text-align: right; font-size: 10pt; font-weight: bold; margin-top: 5px;">
        Kode Akun : {{ payload?.kode_akun || '-' }}
      </div>
    </div>

    <table class="report-table">
      <thead>
        <tr>
          <th style="width: 5%;">No</th>
          <th style="width: 12%;">Tanggal</th>
          <th style="width: 10%;">Ref ID.</th>
          <th style="width: 35%;">Keterangan</th>
          <th style="width: 12%;">Debit</th>
          <th style="width: 12%;">Kredit</th>
          <th style="width: 12%;">Saldo</th>
          <th style="width: 4%;">Ins</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="i in 5" :key="i">
          <td class="text-center">{{ i }}</td>
          <td class="text-center">-</td>
          <td class="text-center">-</td>
          <td>-</td>
          <td class="text-right">-</td>
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
  .report-table th {
    background-color: #424242;
    color: #fff;
    padding: 8px 4px;
    border: 1px solid #000;
  }
  .report-table td {
    padding: 6px 5px;
    border: 1px solid #000;
  }
  .text-center { text-align: center; }
  .text-right { text-align: right; }
</style>