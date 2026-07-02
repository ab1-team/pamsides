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
        <tr v-for="(item, index) in payload?.items" :key="index">
          <td class="text-center">{{ index + 1 }}</td>
          <td>{{ item.nama_akun }}</td>
          <td class="text-right">{{ formatCurrency(item.saldo) }}</td>
        </tr>
        
        <tr v-for="i in (5 - (payload?.items?.length || 0))" :key="'empty-'+i" v-if="(payload?.items?.length || 0) < 5">
          <td class="text-center">{{ (payload?.items?.length || 0) + i }}</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr>
        
        <tr class="total-row">
          <td colspan="2" class="text-center"><b>TOTAL</b></td>
          <td class="text-right"><b>{{ formatCurrency(payload?.total_saldo || 0) }}</b></td>
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
  payload: { 
    type: Object, 
    default: () => ({ 
      config: {}, 
      periode: {}, 
      items: [], 
      total_saldo: 0 
    }) 
  }
})

// Fungsi pemformat angka agar tidak error dan sesuai standar laporan
const formatCurrency = (val) => {
  if (val === null || val === undefined) return '0,00';
  const number = parseFloat(val);
  const formatted = Math.abs(number).toLocaleString('id-ID', { 
    minimumFractionDigits: 2, 
    maximumFractionDigits: 2 
  });
  return number < 0 ? `(${formatted})` : formatted;
}

const tanggalCetak = computed(() => {
  return new Date().toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
})
</script>

<style scoped>
/* Style sudah optimal sesuai kebutuhan Anda */
.main-report-header { text-align: center; margin-top: 5px; margin-bottom: 15px; }
.main-report-header h2 { margin: 0; font-size: 11pt; font-weight: bold; color: #000000; line-height: 1.2; }
.data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.data-table th { border: 1px solid #000000; color: #000000; font-weight: bold; padding: 8px 4px; font-size: 8.5pt; text-align: center; background-color: #d9d9d9; }
.data-table td { padding: 6px 8px; border: 1px solid #000000; font-size: 8.5pt; color: #000000; height: 20px; }
.total-row { background-color: #f9f9f9; }
.text-center { text-align: center; }
.text-right { text-align: right; }
.footer-container { width: 100%; margin-top: 30px; display: flex; justify-content: space-between; }
.footer-sign { width: 40%; text-align: center; font-size: 8.5pt; }
.mt-0 { margin-top: 0px !important; }
.mb-0 { margin-bottom: 0px !important; }
</style>