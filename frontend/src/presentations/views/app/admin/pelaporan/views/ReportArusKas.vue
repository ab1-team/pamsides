<template>
  <BaseReportLayout :config="payload?.config">
    <div class="header-section" style="text-align: center; margin-bottom: 15px; font-family: sans-serif;">
      <h2 style="margin: 0; font-size: 14pt; font-weight: bold; text-transform: uppercase; color: #000;">
        ARUS KAS
      </h2>
      <h3 style="margin: -5px 0 0 0; font-size: 12pt; font-weight: bold; color: #000;">
        BULAN {{ periodeText }}
      </h3>
    </div>

    <table class="report-table">
      <thead>
        <tr>
          <th width="70%" style="text-align: left;">Nama Akun</th>
          <th width="30%" style="text-align: right;">Jumlah</th>
        </tr>
      </thead>
      <tbody>
        <template v-for="(item, index) in (payload?.items || [])" :key="index">
          <tr :class="item.type">
            <td :colspan="item.type === 'header-row' ? 2 : 1" 
                :style="item.type !== 'header-row' && item.type !== 'total-row' ? '' : 'font-weight: bold;'">
              {{ item.label }}
            </td>
            <td v-if="item.type !== 'header-row'" style="text-align: right;">
              {{ formatCurrency(item.jumlah) }}
            </td>
          </tr>
        </template>
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

  const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0 }).format(val || 0)
  }
</script>

<style scoped>
  .report-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 9.5pt;
  }
  .report-table th, .report-table td {
    padding: 6px 8px;
    border: 1px solid #000; /* Border hitam konsisten */
  }
  .report-table th {
    background-color: #424242;
    color: #fff;
    text-align: center;
  }
  .header-row {
    background-color: #e0e0e0;
    font-weight: bold;
  }
  .total-row {
    background-color: #f0f0f0;
    font-weight: bold;
  }
</style>