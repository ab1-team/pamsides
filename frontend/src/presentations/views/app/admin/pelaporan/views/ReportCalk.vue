<template>
    <BaseReportLayout :config="payload?.config">
        <div class="header-section" style="text-align:center;margin-bottom:15px;font-family:sans-serif;">
            <h2 style="margin:0;font-size:14pt;font-weight:bold;text-transform:uppercase;color:#000;">
                CATATAN ATAS LAPORAN KEUANGAN
            </h2>
            <h3 style="margin:-5px 0 0 0;font-size:12pt;font-weight:bold;color:#000;text-transform:uppercase;">
                PER {{ lastDay }} {{ payload?.periode?.bulan_name }} {{ payload?.periode?.tahun }}
            </h3>
        </div>

        <div class="page-content">
            <ol style="list-style: upper-alpha; font-size: 12px;">
                <li v-if="isFirstPage">
                    <div style="text-transform: uppercase;">Gambaran Umum</div>
                    <div style="text-align: justify">
                        ...adalah Badan Usaha yang didirikan dari transformasi UPK PNPM-MPd
                        dengan kegiatan usaha Dana Bergulir Masyarakat (DBM) melalui produk usahanya SPP dan UEP.
                        Dalam perkembangannya sebagian dari laba DBM UPK PNPM-MPd kemudian sebelum
                        ditetapkannya PP 11 tahun 2021 telah digunakan untuk membentuk unit usaha Perdagangan dan
                        Produksi*.
                    </div>
                    <p style="text-align: justify">
                        Bumdesma Lkd setelah didirikan sesuai ketentuan PP 11 tahun 2021 dilaksanakan transformasi
                        sesuai Permendesa PDTT Nomor 15 tahun 2021 yang meliputi pengalihan aset, pengalihan
                        kelembagaan, pengalihan personil, dan pengalihan kegiatan usaha.
                    </p>
                </li>
                <li style="margin-top: 12px;" v-if="isFirstPage">
                    <div style="text-transform: uppercase;">
                        Ikhtisar Kebijakan Akuntansi
                    </div>
                    <ol style="list-style: none; padding-left: 0; text-align: justify;">
                        <li>
                            1. Pernyataan Kepatuhan
                            <ol style="list-style: lower-alpha; padding-left: 25px; text-align: justify;">
                                <li>Laporan keuangan disusun menggunakan Standar Akuntansi Keuangan Perusahaan Jasa
                                    Keuangan</li>
                                <li>Dasar Penyusunan Kepmendesa 136 Tahun 2022</li>
                                <li>Dasar penyusunan laporan keuangan adalah biaya historis dan menggunakan asumsi dasar
                                    akrual. Mata uang penyajian yang digunakan untuk menyusun laporan keuangan ini
                                    adalah Rupiah.</li>
                            </ol>
                        </li>
                        <li style="margin-top: 6px;">2. Piutang Usaha
                            <ol style="list-style: lower-alpha; padding-left: 25px; text-align: justify;">
                                <li>Piutang usaha disajikan sebesar jumlah saldo pinjaman dikurangi dengan cadangan
                                    kerugian pinjaman</li>
                            </ol>
                        </li>
                        <li style="margin-top: 6px;">3. Aset Tetap (berwujud dan tidak berwujud)
                            <ol style="list-style: lower-alpha; padding-left: 25px; text-align: justify;">
                                <li>Aset tetap dicatat sebesar biaya perolehannya jika aset tersebut dimiliki secara
                                    hukum oleh Bumdesma Lkd. Aset tetap disusutkan menggunakan metode garis lurus tanpa
                                    nilai residu.</li>
                            </ol>
                        </li>
                        <li style="margin-top: 6px;">4. Pengakuan Pendapatan dan Beban
                            <ol style="list-style: lower-alpha; padding-left: 25px; text-align: justify;">
                                <li>Jasa piutang kelompok dan lembaga lain yang sudah memasuki jatuh tempo pembayaran
                                    diakui sebagai pendapatan meskipun tidak diterbitkan kuitansi sebagai bukti
                                    pembayaran jasa piutang. Whereas denda keterlambatan pembayaran/pinalti diakui
                                    sebagai pendapatan pada saat diterbitkan kuitansi pembayaran.</li>
                                <li>Adapun kewajiban bayar atas kebutuhan operasional, pemasaran maupun non operasional
                                    pada suatu periode operasi tertentu sebagai akibat telah menikmati manfaat/menerima
                                    fasilitas, maka hal tersebut sudah wajib diakui sebagai beban meskipun belum
                                    diterbitkan kuitansi pembayaran.</li>
                            </ol>
                        </li>
                        <li style="margin-top: 6px;">5. Pajak Penghasilan
                            <ol style="list-style: lower-alpha; padding-left: 25px; text-align: justify;">
                                <li>Pajak Penghasilan mengikuti ketentuan perpajakan yang berlaku di Indonesia</li>
                            </ol>
                        </li>
                    </ol>
                </li>
                <li style="margin-top: 12px; break-inside: avoid;">
                    <div style="text-transform: uppercase;">
                        Informasi Tambahan Laporan Keuangan
                    </div>
                    <table class="report-table">
                        <colgroup>
                            <col style="width: 15%">
                            <col style="width: 65%">
                            <col style="width: 20%">
                        </colgroup>
                        <thead v-if="showTableHeader">
                            <tr>
                                <th>Kode</th>
                                <th>Nama Akun</th>
                                <th class="text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="row in rows" :key="row.key">
                                <tr v-if="row.type === 'lev1'" class="header-row">
                                    <td colspan="3" class="text-center">{{ row.kode_akun }}. {{ row.nama_akun }}</td>
                                </tr>

                                <tr v-else-if="row.type === 'lev2'" class="sub-header-row">
                                    <td><strong>{{ row.kode_akun }}.</strong></td>
                                    <td colspan="2"><strong>{{ row.nama_akun }}</strong></td>
                                </tr>

                                <tr v-else-if="row.type === 'lev3'" class="detail-row"
                                    :class="{ 'zebra-bg': row.isEven }">
                                    <td>{{ row.kode_akun }}.</td>
                                    <td>{{ row.nama_akun }}</td>
                                    <td class="text-right">{{ formatCurrency(row.saldo) }}</td>
                                </tr>

                                <tr v-else-if="row.type === 'lev4'" class="detail-row"
                                    :class="{ 'zebra-bg': row.isEven }">
                                    <td style="padding-left: 4px;">{{ row.kode_akun }}.</td>
                                    <td style="padding-left: 4px;">{{ row.nama_akun }}</td>
                                    <td class="text-right">{{ formatCurrency(row.saldo) }}</td>
                                </tr>

                                <tr v-if="row.type === 'lev2'" style="height:2px;">
                                    <td colspan="3" style="padding:0;line-height:0;"></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot v-if="isLastPage">
                            <tr class="final-row">
                                <td colspan="2">Jumlah Aset / Kewajiban / Ekuitas</td>
                                <td class="text-right">{{ formatCurrency(payload?.total_saldo || 0) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </li>
                <li style="margin-top: 12px;" v-if="isLastPage">
                    <div style="text-transform: uppercase;">
                        Pembagian Laba Usaha
                    </div>
                    <ol style="list-style: none; padding-left: 0; text-align: justify;">
                        <li>
                            Pembagian atas laba usaha dibagi menjadi Laba dibagikan dan laba ditahan sesuai dengan
                            ketentuan pada
                            Permendesa PDTT nomor 15 tahun 2021 yaitu:
                            <ol style="list-style: lower-latin; padding-left: 25px; text-align: justify;">
                                <li>Hasil usaha yang dibagikan paling sedikit terdiri atas: bagian milik bersama
                                    masyarakat Desa; dan bagian Desa;</li>
                                <li>Besaran masing-masing bagian dihitung berdasarkan persentase penyertaan modal dan
                                    dituangkan dalam anggaran dasar.</li>
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
                <li style="margin-top: 12px;" v-if="isLastPage">
                    <div style="text-transform: uppercase;">
                        Penutup
                    </div>
                    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
                        <tr>
                            <td align="justify">
                                <div style="text-align: justify">
                                    Laporan Keuangan ini disajikan dengan berpedoman pada Keputusan
                                    Kementerian Desa Nomor 136/2022 Tentang Panduan Penyusunan Pelaporan Bumdes. dimana
                                    yang
                                    dimaksud Bumdes yang dimaksud dalam Keputusan Kementerian Desa adalah meliputi
                                    Bumdes, Bumdesma
                                    dan Bumdesma Lkd. Catatan atas Laporan Keuangan (CaLK) ini merupakan bagian tidak
                                    terpisahkan
                                    dari Laporan Keuangan Badan Usaha Milik Desa Bersama.
                                </div>
                            </td>
                        </tr>
                    </table>
                </li>
            </ol>
        </div>
    </BaseReportLayout>
</template>

<script setup>
    import {
        computed
    } from 'vue'
    import BaseReportLayout from '../layouts/BaseReportLayout.vue'

    const props = defineProps({
        payload: {
            type: Object,
            default: () => ({})
        },
        meta: {
            type: Object,
            default: () => ({})
        }
    })

    const isFirstPage = computed(() => {
        const info = props.payload ? .pageInfo
        if (info) return info.current === 1
        return props.payload ? .isFirstPage !== false
    })

    const isLastPage = computed(() => {
        const info = props.payload ? .pageInfo
        if (info) return info.current === info.total
        return props.payload ? .isLastPage !== false
    })

    const lastDay = computed(() => {
        const bulan = props.payload ? .periode ? .bulan
        const tahun = props.payload ? .periode ? .tahun
        if (!bulan || !tahun) return ''
        return new Date(tahun, bulan, 0).getDate()
    })

    const formatCurrency = (val) => {
        const num = parseFloat(val || 0)
        return num < 0 ?
            '(' + new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2
            }).format(Math.abs(num)) + ')' :
            new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2
            }).format(num)
    }

    const rows = computed(() => {
        const out = []
        let detailIndex = 0
        const items = props.payload ? .rows || []

        items.forEach((row, idx) => {
            if (row.type === 'lev1') {
                out.push({
                    ...row,
                    key: `L1-${idx}-h`
                })
            } else if (row.type === 'lev2') {
                out.push({
                    ...row,
                    key: `L2-${idx}-s`
                })
            } else if (row.type === 'lev3') {
                out.push({
                    ...row,
                    key: `L3-${idx}-d`,
                    isEven: detailIndex % 2 !== 0
                })
                detailIndex++
            } else if (row.type === 'lev4') {
                out.push({
                    ...row,
                    key: `L4-${idx}-a`,
                    isEven: detailIndex % 2 !== 0
                })
                detailIndex++
            }
        })
        return out
    })

    const showTableHeader = computed(() => props.payload ? .showTableHeader !== false)
</script>

<style scoped>
    .header-section {
        text-align: center;
        margin-top: 5px;
        margin-bottom: 15px;
    }

    .header-section h2 {
        margin: 0;
        font-size: 14pt;
        font-weight: bold;
        color: #000000;
        line-height: 1.2;
    }

    .header-section h3 {
        margin: -5px 0 0 0;
        font-size: 12pt;
        font-weight: bold;
        color: #000000;
        text-transform: uppercase;
    }

    .report-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9pt;
        table-layout: fixed;
    }

    .report-table th,
    .report-table td {
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
    }

    .report-table th.text-right {
        text-align: right;
        padding-right: 8px;
    }

    .header-row td {
        background: #5e5a5a;
        color: #fff;
        padding: 4px;
        font-weight: normal;
    }

    .sub-header-row td {
        background: rgb(184, 184, 184);
        padding: 4px;
    }

    .detail-row td {
        padding: 3px 4px;
    }

    .detail-row {
        background-color: #e4e4e4;
        page-break-inside: avoid;
        break-inside: avoid;
    }

    .zebra-bg {
        background-color: #ffffff;
    }

    .text-right {
        text-align: right;
    }

    .text-center {
        text-align: center;
    }

    .final-row td {
        background: #3a3838;
        color: #fff;
        font-weight: bold;
        padding: 6px;
    }
</style>