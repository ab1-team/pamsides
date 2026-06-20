<template>
  <div class="laporan-page">
    <div class="laporan-paper">
      <div class="laporan-container">
        <div class="laporan-header">
          <div class="laporan-header__brand">
            <div class="laporan-header__logo">
              <font-awesome-icon icon="water" />
            </div>
            <div class="laporan-header__org">
              <h2>BUMDes BANGUN KENCANA "TIRTO MULO"</h2>
              <p>Kalurahan Mulo, Kapanewon Wonosari, Kabupaten Gunungkidul</p>
            </div>
          </div>
          <div class="laporan-header__divider"></div>
        </div>

        <div class="surat-meta">
          <table class="surat-meta__table">
            <tr>
              <td style="width: 90px">Nomor</td>
              <td style="width: 8px">:</td>
              <td>{{ nomor || '___ / BUMDes / ' + bulanRomawi + ' / ' + tahun }}</td>
            </tr>
            <tr>
              <td>Lampiran</td>
              <td>:</td>
              <td>{{ lampiran || '-' }}</td>
            </tr>
            <tr>
              <td>Perihal</td>
              <td>:</td>
              <td class="surat-meta__perihal">{{ perihal }}</td>
            </tr>
          </table>
        </div>

        <div class="surat-tujuan">
          <p>Kepada Yth.</p>
          <p class="surat-tujuan__nama"><strong>{{ tujuanNama }}</strong></p>
          <p>di</p>
          <p>{{ tujuanTempat }}</p>
        </div>

        <div class="surat-isi">
          <p>Dengan hormat,</p>
          <p class="surat-isi__pembuka">
            {{ pembuka || `Sehubungan dengan berakhirnya periode pelaporan ${periode} pada unit usaha
            pengelolaan air minum "TIRTO MULO" BUMDes Bangun Kencana, maka bersama surat ini kami
            sampaikan laporan ${jenisLaporan} untuk dapat ditinjau dan didokumentasikan.` }}
          </p>

          <slot name="isi" />

          <p class="surat-isi__penutup">
            {{ penutup || 'Demikian surat pengantar ini kami sampaikan, atas perhatian dan kerja samanya kami ucapkan terima kasih.' }}
          </p>
        </div>

        <div class="surat-footer">
          <div class="surat-footer__ttd">
            <p>{{ tempat }}, {{ tanggalCetak }}</p>
            <p>Pengelola Pamsides</p>
            <p class="surat-footer__jabatan">{{ jabatan || 'Ketua BUMDes' }}</p>
            <div class="surat-footer__space"></div>
            <p class="surat-footer__name">{{ penandatangan }}</p>
            <p v-if="niy" class="surat-footer__niy">NIY. {{ niy }}</p>
          </div>
        </div>

        <div v-if="tembusan && tembusan.length" class="surat-tembusan">
          <p><strong>Tembusan:</strong></p>
          <ol>
            <li v-for="(item, idx) in tembusan" :key="idx">{{ item }}</li>
          </ol>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  nomor: { type: String, default: '' },
  lampiran: { type: String, default: '' },
  perihal: { type: String, required: true },
  tujuanNama: { type: String, default: 'Bapak/Ibu ____________________' },
  tujuanTempat: { type: String, default: 'Mulo, Wonosari, Gunungkidul' },
  pembuka: { type: String, default: '' },
  penutup: { type: String, default: '' },
  jenisLaporan: { type: String, default: 'keuangan' },
  tahun: { type: [String, Number], required: true },
  bulan: { type: String, default: '' },
  tempat: { type: String, default: 'Mulo' },
  tanggalCetak: { type: String, default: '' },
  jabatan: { type: String, default: '' },
  penandatangan: { type: String, default: '( ___________________ )' },
  niy: { type: String, default: '' },
  tembusan: { type: Array, default: () => [] },
})

const bulanLabel = {
  '01': 'Januari', '02': 'Februari', '03': 'Maret', '04': 'April',
  '05': 'Mei', '06': 'Juni', '07': 'Juli', '08': 'Agustus',
  '09': 'September', '10': 'Oktober', '11': 'November', '12': 'Desember',
}

const bulanRomawiMap = {
  '01': 'I', '02': 'II', '03': 'III', '04': 'IV',
  '05': 'V', '06': 'VI', '07': 'VII', '08': 'VIII',
  '09': 'IX', '10': 'X', '11': 'XI', '12': 'XII',
}

const bulanRomawi = computed(() => (props.bulan ? bulanRomawiMap[props.bulan] || '' : ''))

const periode = computed(() => {
  if (props.bulan) return `${bulanLabel[props.bulan] || props.bulan} ${props.tahun}`
  return `Tahun ${props.tahun}`
})
</script>

<style scoped>
.laporan-page {
  background: #f1f5f9;
  padding: 24px;
  display: flex;
  justify-content: center;
  font-family: 'Inter', Arial, sans-serif;
}

.laporan-paper {
  width: 210mm;
  min-height: 297mm;
  background: #ffffff;
  box-shadow: 0 8px 32px rgba(15, 23, 42, 0.12);
}

.laporan-container {
  padding: 22mm 22mm 18mm;
}

.laporan-header {
  text-align: center;
  margin-bottom: 24px;
}

.laporan-header__brand {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
  margin-bottom: 6px;
}

.laporan-header__logo {
  width: 50px;
  height: 50px;
  border-radius: 10px;
  background: linear-gradient(135deg, #1e40af 0%, #06b6d4 100%);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}

.laporan-header__org h2 {
  font-size: 15px;
  font-weight: 800;
  margin: 0;
  letter-spacing: 0.5px;
  color: #0f172a;
}

.laporan-header__org p {
  font-size: 10px;
  color: #475569;
  margin: 2px 0 0;
}

.laporan-header__divider {
  height: 2px;
  background: #1e3a8a;
  margin: 10px 0 4px;
}

.surat-meta {
  margin-bottom: 18px;
}

.surat-meta__table {
  font-size: 11px;
}

.surat-meta__table td {
  padding: 2px 4px;
  vertical-align: top;
}

.surat-meta__perihal {
  font-weight: 700;
  text-decoration: underline;
}

.surat-tujuan {
  margin-bottom: 18px;
  font-size: 11px;
  margin-left: 40px;
}

.surat-tujuan p {
  margin: 0;
  line-height: 1.6;
}

.surat-tujuan__nama {
  margin-top: 4px !important;
}

.surat-isi {
  font-size: 11px;
  line-height: 1.7;
  text-align: justify;
  margin-bottom: 18px;
}

.surat-isi p {
  margin: 0 0 10px;
}

.surat-isi__pembuka {
  text-indent: 30px;
}

.surat-footer {
  margin-top: 28px;
  display: flex;
  justify-content: flex-end;
}

.surat-footer__ttd {
  text-align: center;
  font-size: 11px;
  width: 220px;
}

.surat-footer__ttd p {
  margin: 0 0 2px;
}

.surat-footer__jabatan {
  margin-bottom: 0 !important;
}

.surat-footer__space {
  height: 60px;
}

.surat-footer__name {
  font-weight: 700;
  text-decoration: underline;
}

.surat-footer__niy {
  font-size: 10px !important;
  color: #475569;
}

.surat-tembusan {
  margin-top: 20px;
  font-size: 10px;
  border-top: 1px dashed #cbd5e1;
  padding-top: 8px;
}

.surat-tembusan p {
  margin: 0 0 4px;
}

.surat-tembusan ol {
  margin: 0;
  padding-left: 20px;
}

@media print {
  .laporan-page {
    background: #fff !important;
    padding: 0 !important;
  }
  .laporan-paper {
    box-shadow: none !important;
    width: 100% !important;
  }
  @page {
    margin: 0;
    size: A4 portrait;
  }
}
</style>