<template>
  <BaseReportLayout :lembaga="payload?.lembaga" :config="payload?.config">
      <div
      class="header-section"
      style="text-align:center;margin-bottom:15px;font-family:sans-serif;"
    >
      <h2
        style="margin:0;font-size:14pt;font-weight:bold;text-transform:uppercase;color:#000;"
      >
        NERACA
      </h2>
        <h3
          style="margin:-5px 0 0 0; font-size:12pt; font-weight:bold; color:#000; text-transform: uppercase;"
        >
          PER {{ lastDay }} {{ payload?.periode?.bulan_name }} {{ payload?.periode?.tahun }}
        </h3>
    </div>
    <table class="report-table">
      <colgroup>
        <col style="width: 10%">
        <col style="width: 60%">
        <col style="width: 15%">
      </colgroup>
      <thead>
        <tr>
          <th>Kode</th>
          <th>Nama Akun</th>
          <th class="text-right">Saldo</th>
        </tr>
      </thead>
      <tbody>
        <template v-for="row in rows" :key="row.key">
          <tr v-if="row.type === 'header'" class="header-row">
            <td colspan="3" class="text-center">{{ row.kode_akun }}. {{ row.nama_akun }}</td>
          </tr>

          <tr v-else-if="row.type === 'sub'" class="sub-header-row">
            <td><strong>{{ row.kode_akun }}.</strong></td>
            <td colspan="2"><strong>{{ row.nama_akun }}</strong></td>
          </tr>

          <tr v-else-if="row.type === 'detail'" class="detail-row" :class="{ 'zebra-bg': row.isEven }">
            <td>{{ row.kode_akun }}.</td>
            <td>{{ row.nama_akun }}</td>
            <td class="text-right">{{ formatCurrency(row.total_saldo) }}</td>
          </tr>

          <tr v-else-if="row.type === 'total'" class="total-row">
            <td colspan="2" class="text-left"><strong>Jumlah {{ row.nama_akun }}</strong></td>
            <td class="text-right"><strong>{{ formatCurrency(row.total_saldo) }}</strong></td>
          </tr>
  
          <tr v-if="row.type === 'total'" style="height: 5px;">
            <td colspan="3" style="padding: 0; line-height: 0;"></td>
          </tr>
        </template>
      </tbody>
      <tfoot>
        <tr class="final-row">
          <td colspan="2">Jumlah Liabilitas + Ekuitas</td>
          <td class="text-right">{{ formatCurrency(payload?.total_liabilitas_equitas) }}</td>
        </tr>
      </tfoot>
    </table>
  </BaseReportLayout>
</template>

<script setup>
import { computed } from 'vue'
import BaseReportLayout from '../layouts/BaseReportLayout.vue'

const props = defineProps({ payload: Object })

const lastDay = computed(() => {
  const bulan = props.payload?.periode?.bulan
  const tahun = props.payload?.periode?.tahun
  if (!bulan || !tahun) return ''
  return new Date(tahun, bulan, 0).getDate()
})

const formatCurrency = (val) => {
  const num = parseFloat(val || 0)
  // Menangani format negatif dengan tanda kurung sesuai logika lama
  return num < 0 
    ? '(' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2 }).format(Math.abs(num)) + ')' 
    : new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2 }).format(num)
}

const rows = computed(() => {
  const out = []
  let detailIndex = 0
  const items = props.payload?.items || []

  items.forEach((lev1, i1) => {
    const k1 = `L1-${i1}`
    out.push({ type: 'header', kode_akun: lev1.kode_akun, nama_akun: lev1.nama_akun, key: k1 + '-h' })

    const lev2s = lev1.akun_level2 || lev1.akunLevel2 || []
    lev2s.forEach((lev2, i2) => {
      const k2 = `${k1}-${i2}`
      out.push({ type: 'sub', kode_akun: lev2.kode_akun, nama_akun: lev2.nama_akun, key: k2 + '-s' })

      const lev3s = lev2.akun_level3 || lev2.akunLevel3 || []
      lev3s.forEach((lev3, i3) => {
        out.push({ 
          type: 'detail', 
          kode_akun: lev3.kode_akun, 
          nama_akun: lev3.nama_akun, 
          total_saldo: lev3.total_saldo || 0, 
          key: `${k2}-${i3}-d`,
          isEven: detailIndex % 2 !== 0 
        })
        detailIndex++
      })
    })

    out.push({ type: 'total', nama_akun: lev1.nama_akun, total_saldo: lev1.total_saldo_lev1 || 0, key: k1 + '-t' })
  })
  return out
})
</script>
<style scoped>
  .report-table { 
    width: 100%; 
    border-collapse: collapse; 
    font-size: 9pt; 
    table-layout: fixed; 
  }
  .report-table th, .report-table td { 
    padding: 4px 6px; 
    border: 0px solid #000; 
    vertical-align: middle; 
    line-height: 1.2; 
  }
  .report-table th { 
    background-color: #000; 
    color: #fff; 
    text-align: left; 
    padding: 4px 3px; 
    border-bottom: 5px solid #ffffff; 
  }
  .report-table th.text-right { text-align: right; padding-right: 8px; }
  .header-row td { background: #5e5a5a; color: #fff; padding: 4px; font-weight: normal; }
  .sub-header-row td { background: rgb(184, 184, 184); padding: 4px; }
  .total-row td { background: #888787; padding: 4px; }
  .final-row td { background: #3a3838; color: #fff; font-weight: bold; padding: 6px; }
  .detail-row td { padding: 3px 4px; }
  .detail-row { background-color: #e4e4e4; }
  .zebra-bg { background-color: #ffffff; } 
  .text-right { text-align: right; }
  .text-center { text-align: center; }
</style>