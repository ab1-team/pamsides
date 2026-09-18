<template>
    <table class="report-table" :class="`variant-${variant}`">
        <colgroup>
            <col style="width: 15%">
            <col style="width: 65%">
            <col style="width: 20%">
        </colgroup>
        <thead v-if="showTableHeader">
            <tr data-block="thead">
                <th>Kode</th>
                <th>Nama Akun</th>
                <th class="text-right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <template v-for="row in rows" :key="row.key">
                <tr v-if="row.type === 'lev1'" class="header-row" :data-block="row.key">
                    <td colspan="3" class="text-center">{{ row.kode_akun }}. {{ row.nama_akun }}</td>
                </tr>

                <tr v-else-if="row.type === 'lev2'" class="sub-header-row" :data-block="row.key">
                    <td><strong>{{ row.kode_akun }}.</strong></td>
                    <td colspan="2"><strong>{{ row.nama_akun }}</strong></td>
                </tr>

                <tr v-else-if="row.type === 'lev3'" class="detail-row group-row"
                    :class="{ 'zebra-bg': row.isEven }" :data-block="row.key">
                    <td><strong>{{ row.kode_akun }}.</strong></td>
                    <td><strong>{{ row.nama_akun }}</strong></td>
                    <td class="text-right"><strong>{{ formatCurrency(row.saldo) }}</strong></td>
                </tr>

                <tr v-else-if="row.type === 'lev4'" class="detail-row"
                    :class="{ 'zebra-bg': row.isEven }" :data-block="row.key">
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
            <tr data-block="tfoot" class="final-row">
                <td colspan="2">Jumlah Aset / Kewajiban / Ekuitas</td>
                <td class="text-right">{{ formatCurrency(totalSaldo || 0) }}</td>
            </tr>
        </tfoot>
    </table>
</template>

<script setup>
defineProps({
    rows: { type: Array, default: () => [] },
    showTableHeader: { type: Boolean, default: false },
    isLastPage: { type: Boolean, default: false },
    totalSaldo: { type: [Number, String], default: 0 },
    formatCurrency: { type: Function, required: true },
    /**
     * Variant styling:
     *   - 'calk'         : ReportCalk.vue style (font 9pt, group-row abu-abu gelap)
     *   - 'tutup-buku'   : ReportTutupBukuCalk.vue style (font 8pt, group-row biru muda)
     */
    variant: { type: String, default: 'calk' },
})
</script>

<style scoped>
.report-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.report-table th,
.report-table td {
    border: 0px solid #000;
    vertical-align: middle;
}

.report-table th {
    background-color: #000;
    color: #fff;
    text-align: left;
}

.report-table th.text-right {
    text-align: right;
    padding-right: 8px;
}

.report-table tr.zebra-bg {
    background-color: #ffffff !important;
}

.report-table.variant-calk tr.zebra-bg,
.report-table.variant-tutup-buku tr.zebra-bg {
    background-color: #ffffff !important;
}

.text-right {
    text-align: right;
}

.text-center {
    text-align: center;
}

/* ============================================
   Variant 'calk' (ReportCalk)
   ============================================ */
.report-table.variant-calk {
    font-size: 9pt;
}
.report-table.variant-calk th,
.report-table.variant-calk td {
    padding: 4px 6px;
    line-height: 1.2;
}
.report-table.variant-calk th {
    padding: 4px 3px;
}
.report-table.variant-calk .header-row td {
    background: #5e5a5a;
    color: #fff;
    padding: 4px;
    font-weight: normal;
}
.report-table.variant-calk .sub-header-row td {
    background: rgb(184, 184, 184);
    padding: 4px;
}
.report-table.variant-calk .detail-row td {
    padding: 3px 4px;
}
.report-table.variant-calk .detail-row {
    background-color: #e4e4e4;
    page-break-inside: avoid;
    break-inside: avoid;
}
.report-table.variant-calk .group-row td {
    background-color: #a8a8a8 !important;
}

.report-table.variant-calk .final-row td {
    background: #918e8e;
    color: #1a1818;
    font-weight: bold;
    padding: 6px;
}

/* ============================================
   Variant 'tutup-buku' (ReportTutupBukuCalk)
   ============================================ */
.report-table.variant-tutup-buku {
    font-size: 8pt;
}
.report-table.variant-tutup-buku th,
.report-table.variant-tutup-buku td {
    padding: 2px 4px;
    line-height: 1.15;
}
.report-table.variant-tutup-buku th {
    padding: 3px 4px;
}
.report-table.variant-tutup-buku .header-row td {
    background: #5e5a5a;
    color: #fff;
    padding: 3px;
    font-weight: normal;
}
.report-table.variant-tutup-buku .sub-header-row td {
    background: rgb(184, 184, 184);
    padding: 3px;
}
.report-table.variant-tutup-buku .detail-row td {
    padding: 2px 4px;
}
.report-table.variant-tutup-buku .detail-row {
    background-color: #e4e4e4;
    page-break-inside: avoid;
    break-inside: avoid;
}
.report-table.variant-tutup-buku .group-row td {
    background-color: #a8a8a8 !important;
}

.report-table.variant-tutup-buku .final-row td {
    background: #3a3838;
    color: #fff;
    font-weight: bold;
    padding: 6px;
}
</style>
