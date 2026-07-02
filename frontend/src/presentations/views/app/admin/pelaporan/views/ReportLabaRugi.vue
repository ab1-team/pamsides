<template>
  <BaseReportLayout :config="payload?.config">
    <div style="text-align: center; margin-bottom: 20px;">
      <h2 style="margin: 0;">LAPORAN LABA RUGI</h2>
      <h3 style="margin: 5px 0;">PERIODE {{ payload?.periode?.bulan_name }} {{ payload?.periode?.tahun }}</h3>
    </div>

    <table class="report-table">
      <thead>
        <tr style="background: rgb(235, 234, 234);">
          <th width="55%">Rekening</th>
          <th width="15%">s.d. Bulan Lalu</th>
          <th width="15%">Bulan Ini</th>
          <th width="15%">s.d. Bulan Ini</th>
        </tr>
      </thead>
      
      <tbody v-for="(group, index) in (payload?.groups || [])" :key="index">
        <tr :class="group.type === 'main' ? 'header-main' : 'header-sub'">
          <td colspan="4">{{ group.label }}</td>
        </tr>
        
        <tr v-for="(item, i) in group.items" :key="i" :class="i % 2 === 1 ? 'row-alt' : 'row-std'">
          <td style="padding-left: 20px;">{{ item.nama_akun }}</td>
          <td class="text-right">{{ formatCurrency(item.sd_bulan_lalu) }}</td>
          <td class="text-right">{{ formatCurrency(item.bulan_ini) }}</td>
          <td class="text-right">{{ formatCurrency(item.sd_bulan_ini) }}</td>
        </tr>
      </tbody>
    </table>
  </BaseReportLayout>
</template>

<script setup>
  import BaseReportLayout from '../layouts/BaseReportLayout.vue'
  const props = defineProps({ payload: Object })
  const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0 }).format(val || 0)
</script>

<style scoped>
  .report-table { width: 100%; border-collapse: collapse; font-size: 12px; }
  .report-table th { padding: 8px; border: 1px solid #ccc; }
  .report-table td { padding: 4px 8px; border: none; }
  
  .header-main { background: rgb(200, 200, 200); font-weight: bold; text-transform: uppercase; }
  .header-sub { background: rgb(150, 150, 150); font-weight: bold; text-transform: uppercase; }
  
  .row-std { background: #ffffff; }
  .row-alt { background: #f9f9f9; }
  .text-right { text-align: right; }
</style>