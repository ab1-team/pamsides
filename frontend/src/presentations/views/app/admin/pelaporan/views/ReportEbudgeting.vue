<template>
  <BaseReportLayout :config="payload?.config">
    <div style="text-align: center; margin-bottom: 20px;">
      <h2 style="margin: 0; text-transform: uppercase;">ARUS KAS</h2>
      <h3 style="margin: 0;">BULAN {{ payload?.periode?.bulan_name || '' }} {{ payload?.periode?.tahun || '' }}</h3>
    </div>

    <table class="report-table">
      <thead>
        <tr>
          <th width="70%" style="text-align: left;">Nama Akun</th>
          <th width="30%" style="text-align: right;">Jumlah</th>
        </tr>
      </thead>
      <tbody>
        <tr class="header-row">
          <td>I. SALDO KAS SETARA KAS</td>
          <td style="text-align: right;">0.00</td>
        </tr>

        <tr class="header-row">
          <td colspan="2">II. A. PENERIMAAN OPERASI</td>
        </tr>
        <tr><td>Piutang</td><td style="text-align: right;">0.00</td></tr>
        <tr><td>Pendapatan Usaha Utama</td><td style="text-align: right;">0.00</td></tr>
        <tr class="total-row">
          <td>Jumlah Penerimaan Operasi</td>
          <td style="text-align: right;">0.00</td>
        </tr>

        <tr class="header-row">
          <td colspan="2">PENGELUARAN</td>
        </tr>
        <tr class="header-row">
          <td colspan="2">B. Pencairan Piutang Masyarakat Dan Rekening Antar</td>
        </tr>
        <tr><td>Piutang</td><td style="text-align: right;">0.00</td></tr>
        <tr class="total-row">
          <td>Jumlah Pencairan Piutang</td>
          <td style="text-align: right;">0.00</td>
        </tr>
        
        <tr class="header-row">
          <td colspan="2">C. Pengeluaran Operasi</td>
        </tr>
        <tr><td>Beban Gaji dan Honor</td><td style="text-align: right;">0.00</td></tr>
      </tbody>
    </table>
  </BaseReportLayout>
</template>

<script setup>
defineProps({
  payload: { type: Object, required: true }
})
</script>

<style scoped>
.report-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}
.report-table th, .report-table td {
  padding: 8px;
  border: 1px solid #ddd;
}
.header-row {
  background-color: #e0e0e0;
  font-weight: bold;
}
.total-row {
  background-color: #f5f5f5;
  font-weight: bold;
}
</style>