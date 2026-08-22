<template>
  <BaseReportLayout :lembaga="payload?.lembaga" :config="payload?.config">
      <div
      class="header-section"
      style="text-align:center;margin-bottom:15px;font-family:sans-serif;"
    >
      <h2
        style="margin:0;font-size:14pt;font-weight:bold;color:#000;"
      >
        Daftar {{ pageTitle }}
      </h2>

      <h3
        style="margin:-5px 0 0 0;font-size:12pt;font-weight:bold;color:#000;"
      >
       BULAN {{ payload?.periode?.bulan_name || '' }} {{ payload?.periode?.tahun || '' }}
      </h3>
    </div>

    <table class="data-table">
      <thead>
        <tr>
          <th rowspan="2" style="width: 3%;">No</th>
          <th rowspan="2" style="width: 8%;">Tgl Beli</th>
          <th rowspan="2" style="width: 20%;">Nama Barang</th>
          <th rowspan="2" style="width: 3%;">Id</th>
          <th rowspan="2" style="width: 6%;">Kondisi</th>
          <th rowspan="2" style="width: 4%;">Unit</th>
          <th rowspan="2" style="width: 10%;">Harga Satuan</th>
          <th rowspan="2" style="width: 10%;">Harga Perolehan</th>
          <th rowspan="2" style="width: 6%;">Umur Eko.</th>
          <th rowspan="2" style="width: 9%;">Satuan Susut</th>
          <th colspan="2" style="width: 15%;">Tahun Ini</th>
          <th colspan="2" style="width: 15%;">s.d. Tahun Ini</th>
          <th rowspan="2" style="width: 11%;">Nilai Buku</th>
        </tr>
        <tr>
          <th style="width: 8%;">Umur</th>
          <th style="width: 8%;">Biaya</th>
          <th style="width: 8%;">Umur</th>
          <th style="width: 8%;">Biaya</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, i) in rows" :key="row.id">
          <td class="text-center">{{ i + 1 }}</td>
          <td class="text-center">{{ formatDate(row.tgl_beli) }}</td>
          <td>{{ row.nama_barang }}</td>
          <td class="text-center">{{ row.id }}</td>
          <td class="text-center">{{ row.status }}</td>
          <td class="text-center">{{ row.unit }}</td>
          <td class="text-right">{{ formatRupiah(row.harsat) }}</td>
          <td class="text-right">{{ formatRupiah(row.harga_perolehan) }}</td>
          <td class="text-center">{{ formatUmurEkonomis(row.umur_ekonomis) }}</td>
          <td class="text-right">{{ formatRupiah(row.satuan_susut) }}</td>
          <td class="text-center">{{ row.umur_tahun_ini }}</td>
          <td class="text-right">{{ formatRupiah(row.detail_susut?.penyusutan) }}</td>
          <td class="text-center">{{ row.umur_sd_tahun_ini }}</td>
          <td class="text-right">{{ formatRupiah(row.detail_susut?.akum_susut) }}</td>
          <td class="text-right">{{ formatRupiah(row.detail_susut?.nilai_buku) }}</td>
        </tr>

        <tr v-if="rows.length === 0">
          <td colspan="15" class="text-center" style="padding: 20px;">Tidak ada data pada akun ini.</td>
        </tr>

        <tr class="total-row">
          <td colspan="5" class="text-center"><b>Jumlah {{ pageTitle }}</b></td>
          <td class="text-center"><b>{{ pageTotals.unit }}</b></td>
          <td></td>
          <td class="text-right"><b>{{ formatRupiah(pageTotals.harga_perolehan) }}</b></td>
          <td colspan="2"></td>
          <td  class="text-right" colspan="2"><b>{{ formatRupiah(pageTotals.penyusutan) }}</b></td>
          <td  class="text-right" colspan="2"><b>{{ formatRupiah(pageTotals.akum_susut) }}</b></td>
          <td class="text-right"><b>{{ formatRupiah(pageTotals.nilai_buku) }}</b></td>
        </tr>
      </tbody>
    </table>

  </BaseReportLayout>
</template>

<script setup>
import { computed } from 'vue'
import BaseReportLayout from '../layouts/BaseReportLayout.vue'

const props = defineProps({
  payload: { type: Object, default: () => ({ config: {}, periode: {} }) }
})

const formatRupiah = (val) => {
  const n = Number(val || 0)
  return n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatDate = (val) => {
  if (!val) return '-'
  const d = new Date(val)
  if (Number.isNaN(d.getTime())) return val
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const formatUmurEkonomis = (bulan) => {
  const n = Number(bulan || 0)
  if (n <= 0) return '-'
  const tahun = Math.floor(n / 12)
  const sisaBulan = n % 12
  if (sisaBulan === 0) return `${tahun} `
  return `${tahun} ${sisaBulan} Bln`
}

const hitungBulan = (start, end) => {
  if (!start || !end) return 0
  const d1 = new Date(start)
  const d2 = new Date(end)
  if (Number.isNaN(d1.getTime()) || Number.isNaN(d2.getTime())) return 0
  const diff = d1.getTime() - d2.getTime()
  if (diff >= 0) return 0
  const months = (d2.getFullYear() - d1.getFullYear()) * 12 + (d2.getMonth() - d1.getMonth())
  return Math.max(0, months)
}

const currentGroup = computed(() => {
  const groups = props.payload?.items || []
  return groups[0] || null
})

const pageTitle = computed(() => {
  // Jika API mengirimkan 'view_target', gunakan itu sebagai fallback
  const label = props.payload?.view_target === 'atb' ? 'ASET TAK BERWUJUD' : '';
  return currentGroup.value?.nama_akun || label;
})

const rows = computed(() => {
  const group = currentGroup.value
  if (!group) return []
  const invList = group.inventory || []
  const tahun = Number(props.payload?.periode?.tahun) || new Date().getFullYear()
  const bulan = Number(props.payload?.periode?.bulan) || 12
  const tglKondisi = props.payload?.meta?.tgl_kondisi
    || props.payload?.tgl_kondisi
    || `${tahun}-${String(bulan).padStart(2, '0')}-${new Date(tahun, bulan, 0).getDate()}`
  const tahunLaluEnd = `${tahun - 1}-12-31`

  return invList.map((inv) => {
    const detail = inv.detail_susut || { penyusutan: 0, akum_susut: 0, nilai_buku: 0 }
    const hargaPerolehan = Number(inv.harsat || 0) * Number(inv.unit || 0)
    const satuanSusut = Number(inv.umur_ekonomis || 0) > 0
      ? (Number(inv.harsat || 0) / Number(inv.umur_ekonomis || 1)) * 12
      : 0
    const umurSd = hitungBulan(inv.tgl_beli, tglKondisi)
    const umurSdLalu = hitungBulan(inv.tgl_beli, tahunLaluEnd)
    const umurTahunIni = Math.max(0, umurSd - umurSdLalu)
    return {
      id: inv.id,
      tgl_beli: inv.tgl_beli,
      nama_barang: inv.nama_barang,
      status: inv.status,
      unit: inv.unit,
      harsat: inv.harsat,
      harga_perolehan: hargaPerolehan,
      umur_ekonomis: inv.umur_ekonomis,
      satuan_susut: satuanSusut,
      umur_tahun_ini: `${umurTahunIni} `,
      umur_sd_tahun_ini: `${umurSd} `,
      detail_susut: detail,
    }
  })
})

const pageTotals = computed(() => {
  const sum = { unit: 0, harga_perolehan: 0, penyusutan: 0, akum_susut: 0, nilai_buku: 0 }
  rows.value.forEach((r) => {
    sum.unit += Number(r.unit || 0)
    sum.harga_perolehan += Number(r.harga_perolehan || 0)
    sum.penyusutan += Number(r.detail_susut?.penyusutan || 0)
    sum.akum_susut += Number(r.detail_susut?.akum_susut || 0)
    sum.nilai_buku += Number(r.detail_susut?.nilai_buku || 0)
  })
  return sum
})
</script>

<style scoped>
.main-report-header {
  text-align: center;
  margin-bottom: 15px;
}
.main-report-header h2 {
  margin: 0;
  font-size: 11pt;
  font-weight: bold;
  color: #000000;
}
.data-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}
.data-table th {
  border: 1px solid #000000;
  padding: 4px;
  font-size: 7.5pt;
  text-align: center;
  background-color: #f2f2f2;
}
.data-table td {
  padding: 4px;
  border: 1px solid #000000;
  font-size: 7.5pt;
  height: 20px;
  vertical-align: middle;
}
.total-row {
  background-color: #f9f9f9;
  font-weight: bold;
}
.text-center { text-align: center; }
.text-right { text-align: right; }
.text-left { text-align: left; }

.footer-container {
  width: 100%;
  margin-top: 30px;
  display: flex;
  justify-content: space-between;
}
.footer-sign {
  width: 40%;
  text-align: center;
  font-size: 8.5pt;
}
.mt-0 { margin-top: 0px !important; }
.mb-0 { margin-bottom: 0px !important; }
.page-info {
  text-align: right;
  font-size: 8pt;
  color: #64748b;
  margin-top: 8px;
}

@media print {
  .page-info { display: none !important; }
}
</style>