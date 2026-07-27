<template>
  <BaseReportLayout :lembaga="lembaga" :config="config">
    <div class="bukti-meta-row">
      <div></div>
      <div class="bukti-meta-right">
        <div><span class="mlbl">Nomor</span><span class="msep">:</span><span class="mval">{{ nomorDisplay }}</span></div>
        <div><span class="mlbl">Tanggal</span><span class="msep">:</span><span class="mval">{{ formatDate(row.tgl_transaksi) }}</span></div>
      </div>
    </div>

    <div class="bukti-content">
      <div class="bukti-title">{{ judul }}</div>

      <table class="bukti-info">
        <tr v-if="isKas">
          <td class="lbl">{{ isKeluar ? 'Dibayar Kepada' : 'Diterima Dari' }}</td>
          <td class="sep">:</td>
          <td class="val">{{ dibayarKepada }}</td>
        </tr>
        <tr>
          <td class="lbl">Keterangan</td>
          <td class="sep">:</td>
          <td class="val">{{ row.keterangan_transaksi || '-' }}</td>
        </tr>
        <tr>
          <td class="lbl">Jumlah</td>
          <td class="sep">:</td>
          <td class="val">{{ formatCurrency(row.saldo) }}</td>
        </tr>
        <tr>
          <td class="lbl">Kode Akun (D/K)</td>
          <td class="sep">:</td>
          <td class="val">
            <div>Debit {{ row.account_debet?.kode_akun || row.account_debet }} - {{ row.account_debet?.nama_akun || row.account_debet?.nama || '' }}</div>
            <div>Kredit {{ row.account_kredit?.kode_akun || row.account_kredit }} - {{ row.account_kredit?.nama_akun || row.account_kredit?.nama || '' }}</div>
          </td>
        </tr>
      </table>

      <div class="bukti-sign">
        <div class="sign-col">
          <div class="sign-label">Disetujui,</div>
          <div class="sign-space"></div>
          <div class="sign-name">{{ approver }}</div>
        </div>
        <div class="sign-col">
          <div class="sign-label">Diverifikasi,</div>
          <div class="sign-space"></div>
          <div class="sign-name">{{ verifier }}</div>
        </div>
        <div class="sign-col">
          <div class="sign-label">Disiapkan Oleh :</div>
          <div class="sign-space"></div>
          <div class="sign-name">{{ preparer }}</div>
        </div>
      </div>
    </div>
  </BaseReportLayout>
</template>

<script setup>
import { computed } from 'vue'
import BaseReportLayout from '@/presentations/views/app/admin/pelaporan/layouts/BaseReportLayout.vue'

const props = defineProps({
  row: { type: Object, required: true },
  lembaga: { type: Object, default: () => ({}) },
  config: { type: Object, default: () => ({ paper_size: 'A4', orientation: 'landscape' }) },
})

const formatCurrency = (amount) => {
  const n = Number(amount) || 0
  return 'Rp. ' + n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatDate = (val) => {
  if (!val) return '-'
  const d = new Date(val)
  if (!Number.isNaN(d.getTime())) {
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' })
  }
  return String(val)
}

const buktiSuffix = computed(() => {
  if (judul.value === 'BUKTI KAS MASUK') return 'BKM'
  if (judul.value === 'BUKTI KAS KELUAR') return 'BKK'
  return 'BM'
})

const nomorDisplay = computed(() => {
  if (props.row?.nomor) return props.row.nomor
  const id = props.row?.id
  if (!id) return '-'
  return `${id}/${buktiSuffix.value}`
})

const isKeluar = computed(() => {
  const debet = props.row?.account_debet?.kode_akun || props.row?.account_debet
  const kredit = props.row?.account_kredit?.kode_akun || props.row?.account_kredit
  return String(kredit || '').startsWith('1.1.01') || String(kredit || '').includes('Kas Tunai')
})

const isKas = computed(() => {
  const debet = props.row?.account_debet?.kode_akun || props.row?.account_debet
  const kredit = props.row?.account_kredit?.kode_akun || props.row?.account_kredit
  const accStr = (v) => String(v || '')
  return accStr(debet).startsWith('1.1.01') || accStr(kredit).startsWith('1.1.01')
})

const judul = computed(() => {
  if (isKas.value) return isKeluar.value ? 'BUKTI KAS KELUAR' : 'BUKTI KAS MASUK'
  return 'BUKTI MEMORIAL'
})

const dibayarKepada = computed(() => props.row?.dibayar_kepada || props.row?.diterima_dari || props.row?.account_kredit?.nama_akun || props.row?.account_kredit?.nama || '-')

const approver = 'Bambang Sugeni , AKg'
const verifier = 'Rohayati, S.Akt ,'
const preparer = ''
</script>

<style scoped>
.bukti-content {
  display: flex;
  flex-direction: column;
  height: 100%;
  font-family: Arial, Helvetica, sans-serif;
  color: #000;
}

.bukti-meta-row {
  display: flex;
  justify-content: flex-end;
  font-size: 9px;
  margin-top: 2px;
  margin-bottom: 2px;
}

.bukti-meta-right > div {
  display: flex;
  gap: 6px;
  line-height: 1.4;
}

.bukti-meta-row .mlbl {
  display: inline-block;
  min-width: 56px;
  font-weight: 600;
}

.bukti-meta-row .msep {
  font-weight: 700;
}

.bukti-title {
  text-align: center;
  font-size: 12px;
  font-weight: bold;
  margin: 2px 0 8px;
  letter-spacing: 0.5px;
}

.bukti-info {
  width: 100%;
  border-collapse: collapse;
  font-size: 9px;
}

.bukti-info td {
  padding: 2px 3px;
  vertical-align: top;
}

.bukti-info .lbl {
  width: 28%;
}

.bukti-info .sep {
  width: 12px;
  text-align: center;
  font-weight: 700;
}

.bukti-info .val {
  width: auto;
}

.bukti-sign {
  margin-top: auto;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  padding-top: 8px;
  font-size: 9px;
}

.sign-col {
  text-align: center;
}

.sign-label {
  margin-bottom: 24px;
}

.sign-space {
  border-bottom: 1px solid transparent;
  height: 18px;
}

.sign-name {
  font-weight: 500;
}

:deep(.report-page.surat-page) {
  padding: 12px 14px !important;
  width: 100% !important;
  height: 100% !important;
  min-height: 0 !important;
  max-height: 100% !important;
  overflow: hidden !important;
}

:deep(.report-page.surat-page.size-a4.landscape),
:deep(.report-page.surat-page.size-a4.portrait),
:deep(.report-page.surat-page.size-f4.landscape),
:deep(.report-page.surat-page.size-f4.portrait) {
  width: 100% !important;
  min-height: 0 !important;
  max-height: 100% !important;
}

:deep(.kop-table),
:deep(.kop-info-sub) {
  font-size: 8px !important;
}

:deep(.kop-nama-usaha) {
  font-size: 9px !important;
}

:deep(.kop-nama-kec) {
  font-size: 9px !important;
}

:deep(.logo-cell img),
:deep(.kop-logo-fallback) {
  height: 30px !important;
  width: 30px !important;
  font-size: 10px !important;
}

:deep(.kop-single-divider) {
  margin-bottom: 6px !important;
  border-top: 1.5px solid #888 !important;
}
</style>