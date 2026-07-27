<template>
  <BaseReportLayout :lembaga="lembaga" :config="payload?.config" :no-kop="true">
    <div class="bukti-grid">
      <div
        v-for="(row, idx) in items"
        :key="idx"
        class="bukti-cell"
      >
        <div class="page-header">
          <h2>BUKTI TRANSAKSI</h2>
          <h2 class="mt-1 mb-0 leading-tight" style="font-size: 16px;">
            {{ title }}
          </h2>
          <p class="page-subtitle" style="font-size: 13px;">
            Periode {{ periodeLabel }}
          </p>
        </div>

        <div class="meta-grid">
          <div class="meta-col">
            <div class="meta-row">
              <span class="meta-label">Kode Akun</span>
              <span class="meta-sep">:</span>
              <span class="meta-value">{{ selectedAccount || '-' }}</span>
            </div>
          </div>
          <div class="meta-col meta-col-right">
            <div class="meta-row">
              <span class="meta-label">Saldo Awal</span>
              <span class="meta-sep">:</span>
              <span class="meta-value">{{ formatCurrency(saldoAwal) }}</span>
            </div>
          </div>
        </div>

        <table class="data-table">
          <thead>
            <tr>
              <th width="4%" class="text-center">No</th>
              <th width="12%" class="text-center">Tanggal</th>
              <th width="14%" class="text-center">Kode Akun</th>
              <th width="32%" class="text-left">Keterangan</th>
              <th width="10%" class="text-center">ID Trx</th>
              <th width="14%" class="text-right">Debit</th>
              <th width="14%" class="text-right">Kredit</th>
              <th width="14%" class="text-right">Saldo</th>
            </tr>
          </thead>
          <tbody>
            <tr :class="{ 'header-row': row._isHeader }">
              <td class="text-center">{{ row._isHeader ? '' : idx + 1 }}</td>
              <td class="text-center">{{ formatDateId(row._isHeader ? row.tanggalLabel : row.tgl_transaksi) }}</td>
              <td class="text-center">{{ row._isHeader ? '' : kodeAkun(row) }}</td>
              <td class="text-left">{{ row._isHeader ? row.label : (row.keterangan_transaksi || '-') }}</td>
              <td class="text-center">{{ row._isHeader ? '' : row.id }}</td>
              <td class="text-right">{{ formatCurrency(row._isHeader ? row.debit : debitOf(row)) }}</td>
              <td class="text-right">{{ formatCurrency(row._isHeader ? row.kredit : kreditOf(row)) }}</td>
              <td class="text-right">{{ formatCurrency(row._saldo) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </BaseReportLayout>
</template>

<script setup>
import { computed } from 'vue'
import BaseReportLayout from '@/presentations/views/app/admin/pelaporan/layouts/BaseReportLayout.vue'

const props = defineProps({
  payload: { type: Object, default: () => ({}) },
  meta: { type: Object, default: () => ({}) },
})

const items = computed(() => props.payload?.items || [])
const title = computed(() => props.payload?.title || 'Bukti Transaksi')
const selectedAccount = computed(() => props.payload?.selectedAccount || '')
const saldoAwal = computed(() => Number(props.payload?.saldoAwal) || 0)
const lembaga = computed(() => props.payload?.lembaga || {})
const periodeLabel = computed(() => props.payload?.periodeLabel || '-')

const formatDateId = (val) => {
  if (!val) return '-'
  const d = new Date(val)
  if (!Number.isNaN(d.getTime())) {
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' })
  }
  return String(val)
}

const formatCurrency = (amount) => {
  const n = Number(amount) || 0
  return n.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}

const kodeAkun = (row) => selectedAccount.value || row.account_debet?.kode_akun || row.account_debet || ''

const debitOf = (row) => {
  if (!selectedAccount.value) return Number(row.saldo) || 0
  const debet = row.account_debet?.kode_akun || row.account_debet
  return debet === selectedAccount.value ? Number(row.saldo) || 0 : 0
}

const kreditOf = (row) => {
  if (!selectedAccount.value) return 0
  const kredit = row.account_kredit?.kode_akun || row.account_kredit
  return kredit === selectedAccount.value ? Number(row.saldo) || 0 : 0
}
</script>

<style scoped>
.bukti-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  grid-auto-rows: 1fr;
  gap: 12px;
  width: 100%;
  height: 100%;
}

.bukti-cell {
  border: 1px solid #000000;
  padding: 10px 12px;
  display: flex;
  flex-direction: column;
  background: #ffffff;
  overflow: hidden;
  min-height: 0;
}

.bukti-cell :deep(.report-page.surat-page) {
  padding: 8px 10px !important;
}

.bukti-cell :deep(.kop-table),
.bukti-cell :deep(.kop-info-sub) {
  font-size: 9px !important;
}

.bukti-cell :deep(.kop-nama-usaha) {
  font-size: 10px !important;
}

.bukti-cell :deep(.kop-nama-kec) {
  font-size: 11px !important;
}

.bukti-cell :deep(.logo-cell img),
.bukti-cell :deep(.kop-logo-fallback) {
  height: 40px !important;
  width: 40px !important;
  font-size: 12px !important;
}

.bukti-cell .page-header {
  margin-bottom: 6px;
}

.bukti-cell .meta-grid {
  margin: 4px 0 8px;
}

.bukti-cell .data-table {
  font-size: 10px;
}

.bukti-cell .data-table th,
.bukti-cell .data-table td {
  padding: 3px 4px !important;
}

.meta-grid {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin: 8px 0 12px;
  font-size: 12px;
}

.meta-col {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.meta-col-right {
  text-align: left;
}

.meta-row {
  display: flex;
  align-items: center;
  gap: 6px;
}

.meta-label {
  min-width: 95px;
  display: inline-block;
}

.meta-sep {
  font-weight: 700;
}

.meta-value {
  font-weight: 600;
}

.text-right {
  text-align: right !important;
  padding-right: 6px !important;
}

.text-center {
  text-align: center !important;
}

.text-left {
  text-align: left !important;
}

.header-row td {
  background: #f1f5f9 !important;
  font-weight: 600;
}

.kop-single-divider {
  border: 0;
  border-top: 2.5px solid #000000;
  margin-top: 4px;
  margin-bottom: 15px;
  width: 100%;
}

.empty {
  text-align: center;
  font-style: italic;
  padding: 16px !important;
  color: #000000;
}
</style>