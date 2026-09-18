<template>
  <BaseReportLayout :lembaga="payload?.lembaga" :config="payload?.config">
    <div ref="rootEl" class="calk-root">
      <div class="header-section" data-block="header" style="text-align:center;margin-bottom:15px;font-family:sans-serif;">
        <h2 style="margin:0;font-size:14pt;font-weight:bold;text-transform:uppercase;color:#000;">
          CATATAN ATAS LAPORAN KEUANGAN
        </h2>
        <h3 style="margin:-5px 0 0 0;font-size:12pt;font-weight:bold;color:#000;text-transform:uppercase;">
          PER {{ lastDay }} {{ payload?.periode?.bulan_name }} {{ payload?.periode?.tahun }}
        </h3>
      </div>

      <div class="page-content">
        <!-- HALAMAN PERTAMA: pakai <ol> dengan semua section -->
        <ol v-if="isFirstPage" style="list-style: upper-alpha; font-size: 12px;">
          <li data-block="sec-gambaran">
            <div style="text-transform: uppercase;">Gambaran Umum</div>
            <div style="text-align: justify">
{{ lembagaNama }} adalah Badan Usaha yang didirikan dari transformasi UPK PNPM-MPd dengan kegiatan
                    usaha Dana Bergulir Masyarakat (DBM) melalui produk usahanya SPP dan UEP. Dalam
                    perkembangannya sebagian dari laba DBM UPK PNPM-MPd kemudian sebelum ditetapkannya PP 11
                    tahun 2021 telah digunakan untuk membentuk unit usaha Perdagangan dan Produksi*. Bumdesma Lkd setelah didirikan sesuai ketentuan PP 11 tahun 2021 dilaksanakan transformasi
                    sesuai Permendesa PDTT Nomor 15 tahun 2021 yang meliputi pengalihan aset, pengalihan
                    kelembagaan, pengalihan personil, dan pengalihan kegiatan usaha. Modal awal Pendirian
                    Bumdesma Lkd sesuai dengan ketentuan tersebut adalah berasal dari keseluruhan pengalihan
                    keseluruhan aset DBM Eks PNPM MPd (Permendesa PDTT 15 tahun 2021 Pasal 5) yang dicatat
                    sebagai Ekuitas Bumdesma Lkd ditambah dengan Penyertaan Modal Desa. Yang kemudian didalam
                    laporan posisi keuangan ekuitas yang berasal dari Aset DBM Eks PNPM Mpd disebut Modal
                    Masyarakat Desa (Permendesa PDTT 15 tahun 2021 Pasal 6). Sesuai dengan ketentuan UU Cipta Kerja No 11 Tahun 2020 bahwa Menetapkan status Badan hukum
                    BUM Desa pada ketentuan Pasal 117 "bahwa Badan Usaha Milik Desa yang selanjutnya disebut BUM
                    Desa adalah Badan hukum yang didirikan oleh desa dan atau bersama desa-desa guna mengelola
                    usaha, memanfaatkan aset, mengembangkan investasi dan produktivitas, menyediakan jasa
                    pelayanan, dan atau jenis usaha lainnya untuk sebesar-besarnya kesejahteraan masyarakat desa." Status inilah yang menjadi dasar hukum pelaksanaan usaha didirikan dengan kegiatan Usaha Utama
                    DBM. {{ lembagaNama }} didirikan di {{ lembagaAlamat }} berdasarkan PERATURAN BERSAMA
                    KEPALA DESA NOMOR {{ peraturanDesa }} dan mendapatkan Sertifikat Badan Hukum dari Menteri
                    Hukum dan Hak Asasi Manusia No. {{ skKemenkumham }}. {{ lembagaNama }} menjalankan usaha
                    pinjaman Dana Bergulir Masyarakat yang masuk dalam kategori usaha mikrofinance dan berdomisili
                    di {{ lembagaAlamat }} dengan perangkat organisasi sebagai berikut:
            </div>
           
          </li>
          <li data-block="sec-kebijakan" style="margin-top: 12px;">
            <div style="text-transform: uppercase;">
                Ikhtisar Kebijakan Akuntansi
            </div>
            <ol style="list-style: none; padding-left: 0; text-align: justify;">
                <li>
                    1. Pernyataan Kepatuhan
                    <ol style="list-style: lower-alpha; padding-left: 25px; text-align: justify;">
                        <li>Laporan keuangan disusun menggunakan Standar Akuntansi Keuangan Perusahaan Jasa Keuangan</li>
                        <li>Dasar Penyusunan Kepmendesa 136 Tahun 2022</li>
                        <li>Dasar penyusunan laporan keuangan adalah biaya historis dan menggunakan asumsi dasar akrual. Mata uang penyajian yang digunakan untuk menyusun laporan keuangan ini adalah Rupiah.</li>
                    </ol>
                </li>
                <li style="margin-top: 6px;">2. Piutang Usaha
                    <ol style="list-style: lower-alpha; padding-left: 25px; text-align: justify;">
                        <li>Piutang usaha disajikan sebesar jumlah saldo pinjaman dikurangi dengan cadangan kerugian pinjaman</li>
                    </ol>
                </li>
                <li style="margin-top: 6px;">3. Aset Tetap (berwujud dan tidak berwujud)
                    <ol style="list-style: lower-alpha; padding-left: 25px; text-align: justify;">
                        <li>Aset tetap dicatat sebesar biaya perolehannya jika aset tersebut dimiliki secara hukum oleh Bumdesma Lkd. Aset tetap disusutkan menggunakan metode garis lurus tanpa nilai residu.</li>
                    </ol>
                </li>
                <li style="margin-top: 6px;">4. Pengakuan Pendapatan dan Beban
                    <ol style="list-style: lower-alpha; padding-left: 25px; text-align: justify;">
                        <li>Jasa piutang kelompok dan lembaga lain yang sudah memasuki jatuh tempo pembayaran diakui sebagai pendapatan meskipun tidak diterbitkan kuitansi sebagai bukti pembayaran jasa piutang. Whereas denda keterlambatan pembayaran/pinalti diakui sebagai pendapatan pada saat diterbitkan kuitansi pembayaran.</li>
                        <li>Adapun kewajiban bayar atas kebutuhan operasional, pemasaran maupun non operasional pada suatu periode operasi tertentu sebagai akibat telah menikmati manfaat/menerima fasilitas, maka hal tersebut sudah wajib diakui sebagai beban meskipun belum diterbitkan kuitansi pembayaran.</li>
                    </ol>
                </li>
                <li style="margin-top: 6px;">5. Pajak Penghasilan
                    <ol style="list-style: lower-alpha; padding-left: 25px; text-align: justify;">
                        <li>Pajak Penghasilan mengikuti ketentuan perpajakan yang berlaku di Indonesia</li>
                    </ol>
                </li>
            </ol>
          </li>
          <li data-block="sec-informasi" style="margin-top: 12px; break-inside: avoid;">
            <div style="text-transform: uppercase;">
                  Informasi Tambahan Laporan Keuangan
            </div>
              <calk-table :rows="rows" :show-table-header="showTableHeader" :is-last-page="isLastPage" :total-saldo="payload?.total_saldo" :format-currency="formatCurrency" variant="tutup-buku" />
          </li>
          <template v-if="isLastPage">
            <li data-block="sec-pembagian" style="margin-top: 12px;">
            <div style="text-transform: uppercase;">
                Pembagian Laba Usaha
            </div>
            <ol style="list-style: none; padding-left: 0; text-align: justify;">
                <li>
                    Pembagian atas laba usaha dibagi menjadi Laba dibagikan dan laba ditahan sesuai dengan ketentuan pada
                    Permendesa PDTT nomor 15 tahun 2021 yaitu:
                    <ol style="list-style: lower-latin; padding-left: 25px; text-align: justify;">
                        <li>Hasil usaha yang dibagikan paling sedikit terdiri atas: bagian milik bersama masyarakat Desa; dan bagian Desa;</li>
                        <li>Besaran masing-masing bagian dihitung berdasarkan persentase penyertaan modal dan dituangkan dalam anggaran dasar.</li>
                    </ol>
                </li>
                <li style="margin-top: 6px;">
                    Laba Ditahan Dari Laba Tahun
                    <ol style="list-style: lower-latin; padding-left: 25px; text-align: justify;">
                        <li>Laba Ditahan untuk Penambahan Modal Kegiatan DBM Rp.</li>
                        <li>Laba Ditahan untuk Penambahan Investasi Usaha Rp.</li>
                        <li>Laba Ditahan untuk Pendirian Unit Usaha Rp.</li>
                    </ol>
                </li>
            </ol>
            </li>
            <li data-block="sec-penutup" style="margin-top: 12px;">
            <div style="text-transform: uppercase;">
               Penutup
          </div>
              <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
                  <tr>
                      <td align="justify">
                          <div style="text-align: justify">
                              Laporan Keuangan ini disajikan dengan berpedoman pada Keputusan
                              Kementerian Desa Nomor 136/2022 Tentang Panduan Penyusunan Pelaporan Bumdes. dimana yang
                              dimaksud Bumdes yang dimaksud dalam Keputusan Kementerian Desa adalah meliputi Bumdes, Bumdesma
                              dan Bumdesma Lkd. Catatan atas Laporan Keuangan (CaLK) ini merupakan bagian tidak terpisahkan
                              dari Laporan Keuangan Badan Usaha Milik Desa Bersama.
                          </div>
                      </td>
                  </tr>
              </table>
            </li>
          </template>
        </ol>

        <!-- HALAMAN LANJUTAN (non-first): tabel informasi sebagai plain div -->
        <div v-else>
          <div data-block="sec-informasi-cont" style="margin-top: 12px; break-inside: avoid;">
            <calk-table :rows="rows" :show-table-header="showTableHeader" :is-last-page="isLastPage" :total-saldo="payload?.total_saldo" :format-currency="formatCurrency" variant="tutup-buku" />
          </div>

          <!-- Halaman terakhir: section pembagian & penutup dengan label D, E -->
          <ol v-if="isLastPage" :start="4" style="list-style: upper-alpha; font-size: 12px; margin-top: 12px;">
            <li data-block="sec-pembagian">
              <div style="text-transform: uppercase;">
                  Pembagian Laba Usaha
              </div>
              <ol style="list-style: none; padding-left: 0; text-align: justify;">
                  <li>
                      Pembagian atas laba usaha dibagi menjadi Laba dibagikan dan laba ditahan sesuai dengan ketentuan pada
                      Permendesa PDTT nomor 15 tahun 2021 yaitu:
                      <ol style="list-style: lower-latin; padding-left: 25px; text-align: justify;">
                          <li>Hasil usaha yang dibagikan paling sedikit terdiri atas: bagian milik bersama masyarakat Desa; dan bagian Desa;</li>
                          <li>Besaran masing-masing bagian dihitung berdasarkan persentase penyertaan modal dan dituangkan dalam anggaran dasar.</li>
                      </ol>
                  </li>
                  <li style="margin-top: 6px;">
                      Laba Ditahan Dari Laba Tahun
                      <ol style="list-style: lower-latin; padding-left: 25px; text-align: justify;">
                          <li>Laba Ditahan untuk Penambahan Modal Kegiatan DBM Rp.</li>
                          <li>Laba Ditahan untuk Penambahan Investasi Usaha Rp.</li>
                          <li>Laba Ditahan untuk Pendirian Unit Usaha Rp.</li>
                      </ol>
                  </li>
              </ol>
            </li>
            <li data-block="sec-penutup" style="margin-top: 12px;">
              <div style="text-transform: uppercase;">
                 Penutup
            </div>
                <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
                    <tr>
                        <td align="justify">
                            <div style="text-align: justify">
                                Laporan Keuangan ini disajikan dengan berpedoman pada Keputusan
                                Kementerian Desa Nomor 136/2022 Tentang Panduan Penyusunan Pelaporan Bumdes. dimana yang
                                dimaksud Bumdes yang dimaksud dalam Keputusan Kementerian Desa adalah meliputi Bumdes, Bumdesma
                                dan Bumdesma Lkd. Catatan atas Laporan Keuangan (CaLK) ini merupakan bagian tidak terpisahkan
                                dari Laporan Keuangan Badan Usaha Milik Desa Bersama.
                            </div>
                        </td>
                    </tr>
                </table>
            </li>
          </ol>
        </div>
      </div>
    </div>
  </BaseReportLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import BaseReportLayout from '../layouts/BaseReportLayout.vue'
import CalkTable from './CalkTable.vue'

const props = defineProps({
  payload: { type: Object, default: () => ({}) },
  meta: { type: Object, default: () => ({}) }
})

const rootEl = ref(null)

defineExpose({ rootEl })

const isFirstPage = computed(() => {
  const info = props.payload?.pageInfo
  if (info) return info.current === 1
  return props.payload?.isFirstPage !== false
})

const isLastPage = computed(() => {
  const info = props.payload?.pageInfo
  if (info) return info.current === info.total
  return props.payload?.isLastPage !== false
})

const lastDay = computed(() => {
  const bulan = props.payload?.periode?.bulan
  const tahun = props.payload?.periode?.tahun
  if (!bulan || !tahun) return ''
  return new Date(tahun, bulan, 0).getDate()
})

const formatCurrency = (val) => {
  const num = parseFloat(val || 0)
  return num < 0
    ? '(' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2 }).format(Math.abs(num)) + ')'
    : new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2 }).format(num)
}

const rows = computed(() => {
  const out = []
  let detailIndex = 0
  const items = props.payload?.rows || []

  items.forEach((row, idx) => {
    if (row.type === 'lev1') {
      out.push({ ...row, key: `L1-${idx}-h` })
    } else if (row.type === 'lev2') {
      out.push({ ...row, key: `L2-${idx}-s` })
    } else if (row.type === 'lev3') {
      out.push({ ...row, key: `L3-${idx}-d`, isEven: detailIndex % 2 !== 0 })
      detailIndex++
    } else if (row.type === 'lev4') {
      out.push({ ...row, key: `L4-${idx}-a`, isEven: detailIndex % 2 !== 0 })
      detailIndex++
    }
  })
  return out
})

const showTableHeader = computed(() => props.payload?.showTableHeader !== false)

const lembagaNama = computed(() => {
  const nama = props.payload?.lembaga?.nama
  return nama && String(nama).trim() ? nama : 'Badan Usaha'
})

const lembagaAlamat = computed(() => {
  const alamat = props.payload?.lembaga?.alamat
  return alamat && String(alamat).trim() ? alamat : 'Alamat belum tersedia'
})

const peraturanDesa = computed(() => {
  const v = props.payload?.lembaga?.peraturan_desa
  return v && String(v).trim() ? v : '—'
})

const skKemenkumham = computed(() => {
  const v = props.payload?.lembaga?.sk_kemenkumham
  return v && String(v).trim() ? v : '—'
})
</script>

<style scoped>
.calk-root {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  min-height: 0;
}
.header-section { text-align: center; margin-top: 5px; margin-bottom: 15px; }
.header-section h2 { margin: 0; font-size: 14pt; font-weight: bold; color: #000000; line-height: 1.2; }
.header-section h3 { margin: -5px 0 0 0; font-size: 12pt; font-weight: bold; color: #000000; text-transform: uppercase; }
</style>