<template>
    <BaseReportLayout :config="payload?.config">
        <div class="header-section"
            style="text-align: center; margin-bottom: 15px; font-family: sans-serif; color: #000;">
            <h2 style="margin: 0; font-size: 13pt; font-weight: bold; text-transform: uppercase; color: #000;">
                BUKU BESAR {{ payload?.nama_akun || '' }}
            </h2>
            <h3 style="margin: -5px 0 0 0; font-size: 12pt; font-weight: bold; color: #000;">
                BULAN {{ periodeText }}
            </h3>

        </div>
        <div style="text-align: right; font-size: 10pt; margin-top: 5px; margin-bottom: 0px; color: #000;">
            Kode Akun : {{ payload?.kode_akun || '-' }}
        </div>

        <table class="data-table report-table" style="margin-top: 0px;">
            <colgroup>
                <col style="width: 5%">
                <col style="width: 12%">
                <col style="width:8%">
                <col>
                <col style="width: 14%">
                <col style="width: 14%">
                <col style="width: 14%">
                <col style="width: 5%">
            </colgroup>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Ref ID.</th>
                    <th>Keterangan</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Kredit</th>
                    <th class="text-right">Saldo</th>
                    <th class="text-center">Ins</th>
                </tr>
            </thead>
            <tbody>
                <template v-if="showHeader">
                    <tr class="row-grey">
                        <td></td>
                        <td class="text-center">01/01/{{ payload.periode.tahun }}</td>
                        <td></td>
                        <td>Komulatif Transaksi Awal Tahun {{ payload.periode.tahun }}</td>
                        <td class="text-right">{{ format(saldoAwalTahun.debit) }}</td>
                        <td class="text-right">{{ format(saldoAwalTahun.kredit) }}</td>
                        <td class="text-right">{{ format(saldoAwalTahun.saldo) }}</td>
                        <td></td>
                    </tr>

                    <tr class="row-grey">
                        <td></td>
                        <td class="text-center">01/{{ pad(payload.periode.bulan) }}/{{ payload.periode.tahun }}</td>
                        <td></td>
                        <td>Komulatif Transaksi s/d Bulan Lalu</td>
                        <td class="text-right">{{ format(saldoBulanLalu.debit) }}</td>
                        <td class="text-right">{{ format(saldoBulanLalu.kredit) }}</td>
                        <td class="text-right">{{ format(saldoBulanLalu.saldo) }}</td>
                        <td></td>
                    </tr>
                </template>

                <tr v-for="(trx, index) in tableData" :key="trx.id + '-' + index">
                    <td class="text-center">{{ index + 1 }}</td>
                    <td class="text-center">{{ trx.tgl }}</td>
                    <td class="text-center">{{ trx.ref_id }}</td>
                    <td>{{ trx.keterangan }}</td>
                    <td class="text-right">{{ format(trx.debit) }}</td>
                    <td class="text-right">{{ format(trx.kredit) }}</td>
                    <td class="text-right">{{ format(trx.running_saldo) }}</td>
                    <td class="text-center"></td>
                </tr>

                <tr v-if="showFooter" class="row-total">
                    <td colspan="4">Total Transaksi Bulan {{ payload.periode.bulan_name }} {{ payload.periode.tahun }}
                    </td>
                    <td class="text-right">{{ format(totalDebitBulanIni) }}</td>
                    <td class="text-right">{{ format(totalKreditBulanIni) }}</td>
                    <td class="text-right" rowspan="3">{{ format(finalSaldo) }}</td>
                    <td></td>
                </tr>
                <tr v-if="showFooter" class="row-total">
                    <td colspan="4">Total Transaksi sampai dengan Bulan {{ payload.periode.bulan_name }}
                        {{ payload.periode.tahun }}</td>
                    <td class="text-right">{{ format(totalDebitBulanIni + saldoBulanLalu.debit) }}</td>
                    <td class="text-right">{{ format(totalKreditBulanIni + saldoBulanLalu.kredit) }}</td>
                    <td></td>
                </tr>
                <tr v-if="showFooter" class="row-total">
                    <td colspan="4">Total Transaksi Komulatif sampai dengan Tahun {{ payload.periode.tahun }}</td>
                    <td class="text-right">{{ format(totalDebitKumulatif) }}</td>
                    <td class="text-right">{{ format(totalKreditKumulatif) }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </BaseReportLayout>
</template>

<script setup>
    import {
        computed
    } from 'vue'
    import BaseReportLayout from '../layouts/BaseReportLayout.vue'

    const props = defineProps({
        payload: Object
    })

    const format = (val) => Number(val || 0).toLocaleString('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
    const pad = (n) => String(n || '').padStart(2, '0')

    const formatDate = (val) => {
        if (!val) return ''
        const d = new Date(val)
        if (isNaN(d)) return val
        return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()}`
    }

    const isKredit = computed(() => String(props.payload ? .jenis_mutasi || '').toLowerCase() === 'kredit')

    const showHeader = computed(() => props.payload ? .showHeader !== false)
    const showFooter = computed(() => props.payload ? .showFooter !== false)

    const saldoAwalTahun = computed(() => {
        const data = props.payload ? .saldo_awal_tahun || {
            debit: 0,
            kredit: 0
        }
        const debit = Number(data.debit || 0)
        const kredit = Number(data.kredit || 0)
        const saldo = isKredit.value ? (kredit - debit) : (debit - kredit)
        return {
            debit,
            kredit,
            saldo
        }
    })

    const saldoBulanLalu = computed(() => {
        const data = props.payload ? .saldo_bulan_lalu || {
            debit: 0,
            kredit: 0
        }
        const debit = Number(data.debit || 0)
        const kredit = Number(data.kredit || 0)
        const saldo = isKredit.value ? (kredit - debit) : (debit - kredit)
        return {
            debit,
            kredit,
            saldo
        }
    })

    // Logika: Jika bulan Januari (1), pakai saldo awal tahun. Jika bukan, pakai saldo bulan lalu.
    const initialSaldo = computed(() => {
        const bln = Number(props.payload ? .periode ? .bulan || 1)
        return bln === 1 ? saldoAwalTahun.value.saldo : saldoBulanLalu.value.saldo
    })

    const tableData = computed(() => {
        let currentSaldo = initialSaldo.value
        const kodeAkun = String(props.payload ? .kode_akun || '')

        return (props.payload ? .transactions || []).map((trx) => {
            const isDebitSide = String(trx.account_debet) === kodeAkun
            const nominal = Number(trx.saldo || trx.jumlah || 0)
            const debit = isDebitSide ? nominal : 0
            const kredit = !isDebitSide ? nominal : 0

            // Perhitungan mutasi berdasarkan jenis akun (Debit/Kredit)
            const mutasi = isKredit.value ? (kredit - debit) : (debit - kredit)
            currentSaldo += mutasi

            const tglRaw = trx.tgl_transaksi ? new Date(trx.tgl_transaksi) : null
            const tgl = tglRaw && !isNaN(tglRaw) ?
                `${pad(tglRaw.getDate())}/${pad(tglRaw.getMonth() + 1)}/${tglRaw.getFullYear()}` :
                (trx.tgl_transaksi || '-')

            const refParts = []
            if (trx.urutan != null) refParts.push(`1.1-${trx.urutan}`)
            if (trx.transaction_group) refParts.push(trx.transaction_group)
            const ref_id = refParts.join(' ') || trx.id || '-'

            return {
                id: trx.id,
                tgl,
                ref_id,
                keterangan: trx.keterangan_transaksi || '-',
                debit,
                kredit,
                running_saldo: currentSaldo,
            }
        })
    })

    const totalDebitBulanIni = computed(() => tableData.value.reduce((s, i) => s + i.debit, 0))
    const totalKreditBulanIni = computed(() => tableData.value.reduce((s, i) => s + i.kredit, 0))

    // Total kumulatif menggunakan saldo awal (tahun/lalu) + mutasi bulan ini
    // Ganti bagian computed ini agar sinkron dengan baris footer
    const totalDebitKumulatif = computed(() => {
        return Number(saldoAwalTahun.value.debit || 0) + Number(saldoBulanLalu.value.debit || 0) +
            totalDebitBulanIni.value;
    });

    const totalKreditKumulatif = computed(() => {
        return Number(saldoAwalTahun.value.kredit || 0) + Number(saldoBulanLalu.value.kredit || 0) +
            totalKreditBulanIni.value;
    });
    const finalSaldo = computed(() => tableData.value.length ?
        tableData.value[tableData.value.length - 1].running_saldo :
        initialSaldo.value)

    const periodeText = computed(() =>
        `${(props.payload?.periode?.bulan_name || '').toUpperCase()} ${props.payload?.periode?.tahun || ''}`)
</script>

<style scoped>
    /* Pengaturan dasar: Menghapus semua border */
    .report-table {
        width: 100%;
        border-collapse: collapse;
    }

    .report-table th,
    .report-table td {
        padding: 4px 6px;
        vertical-align: middle;
        line-height: 1.2;
        color: #000;
        font-size: 8.5pt !important;
        border: none !important;
        /* Menghilangkan semua garis */
    }

    /* Zebra Striping: Semua baris genap berwarna abu-abu muda */
    .report-table tbody tr:nth-child(even) {
        background-color: #f0f0f0 !important;
    }

    /* Header Tabel */
    .report-table thead th {
        background-color: #424242 !important;
        color: #fff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        font-weight: bold;
    }

    /* Styling Baris Khusus: Menimpa warna zebra agar punya identitas sendiri */
    /* row-grey (Saldo Awal) */
    .row-grey {
        background-color: #dbdbdb !important;
        color: #000 !important;
    }

    /* row-total (Total Akhir) */
    .row-total {
        background-color: #e6e6e6 !important;
        color: #000 !important;
        font-weight: 900 !important;

        -webkit-text-stroke: 0.2px #000;
    }

    /* Helper classes */
    .text-center {
        text-align: center;
    }

    .text-right {
        text-align: right;
    }
</style>