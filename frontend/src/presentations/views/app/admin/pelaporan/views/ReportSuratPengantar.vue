<template>
  <BaseReportLayout :lembaga="payload?.lembaga" :config="payload?.config">    
    
    <table border="0" width="100%" class="surat-meta">
      <tr>
        <td width="15%">Nomor</td>
        <td width="45%">:  ______________________</td>
        <td width="40%" align="right">
          Tirto Mulo {{ payload?.sub_judul || tanggalSurat }}
        </td>
      </tr>
      <tr>
        <td>Lampiran</td>
        <td>: 1 Bendel</td>
      </tr>
      <tr>
        <td>Perihal</td>
        <td>: Laporan Keuangan</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td colspan="2" style="padding-top: 5px;">
          <u>Sampai Dengan {{ payload?.sub_judul || tanggalSurat }}</u>
        </td>
      </tr>
      
      <tr><td colspan="3" height="15"></td></tr>
      
      <tr>
        <td>&nbsp;</td>
        <td colspan="2">
          <div><b>Kepada Yth.</b></div>
          <div><b>Pihak Manajemen / Pengawas {{ payload?.nama_kabupaten || '' }}</b></div>
          <div>Di {{ payload?.kab?.alamat_kab || 'Tempat' }}.</div>
        </td>
      </tr>
      
      <tr><td colspan="3" height="15"></td></tr>
      
      <tr>
        <td>&nbsp;</td>
        <td colspan="2" style="text-align: justify; line-height: 1.5;">
          <div>Dengan Hormat,</div>
          
          <div style="margin-top: 4px;">
            Bersama ini kami sampaikan Laporan Keuangan BUMDes BANGUN KENCANA MULO, Sampai dengan {{ payload?.sub_judul || tanggalSurat }}
            sebagai berikut:
          </div>
          
          <ol style="margin: 6px 0; padding-left: 24px; list-style-type: decimal !important;">
            <li>Laporan Neraca</li>
            <li>Laporan Rugi/Laba</li>
            <li>Neraca Saldo</li>
            <li>Laporan Perubahan Ekuitas</li>
            <li>Catatan Atas Laporan Keuangan (CALK)</li>
          </ol>
          
          <div>
            Demikian laporan kami sampaikan, atas perhatiannya kami ucapkan terima kasih.
          </div>
        </td>
      </tr>
      
      <tr><td colspan="3" height="25"></td></tr>
      
      <tr>
        <td colspan="1"></td>
        <td colspan="2">
          <table width="100%">
            <tr>
              <td width="40%"></td>
              <td width="60%" align="center">
                <div>
                  {{ payload?.usaha?.nama_usaha ||'BUMDes BANGUN KENCANA MULO'}}
                </div>
                <div style="margin-bottom: 65px;">
                  {{ payload?.direktur?.nama_jabatan || payload?.dir_utama?.j?.nama_jabatan || payload?.dir?.j?.nama_jabatan || 'Direktur' }},
                </div>
                <div>
                  <b>{{ payload?.direktur?.name || payload?.penandatangan || '_________________' }}</b>
                </div>
              </td>
            </tr>
          </table>
        </td>
      </tr>
      
      <tr>
        <td colspan="3" style="padding-top: 15px;">
          <div style="font-size: 11px; color: #000000;">
            Tembusan :
            <ol style="margin-top: 2px; padding-left: 20px;">
              <li>Arsip</li>
            </ol>
          </div>
        </td>
      </tr>
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
  return `Bulan ${(p.bulan_name || '').toUpperCase()} ${p.tahun || ''}`
})

const tanggalSurat = computed(() => {
  const raw = props.payload?.periode?.tanggal_surat
  if (!raw) return '-'
  const d = new Date(raw)
  if (Number.isNaN(d.getTime())) return raw
  return d.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
})

const penandatangan = computed(() => {
  return props.payload?.penandatangan || '_________________'
})

const tempat = computed(() => {
  const a = props.payload?.lembaga?.alamat || ''
  if (!a) return 'Tempat'
  return a.split(',')[0]?.trim() || a
})
</script>

<style scoped>
/* Paksa font yang sama di seluruh elemen surat */
:deep(*) {
  font-family: Arial, Helvetica, sans-serif !important;
}

.surat-meta {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px; /* Ukuran font standar */
  color: #000000;
  line-height: 1.4; /* Menambah ruang antar baris agar lebih nyaman dibaca */
}

.surat-meta td {
  vertical-align: top;
  padding: 4px 4px; /* Padding seragam dengan tabel laporan */
}

/* Memastikan elemen div/ol di dalam surat mengikuti font yang sama */
.surat-meta div, 
.surat-meta ol, 
.surat-meta li {
  font-size: 12px;
}
</style>