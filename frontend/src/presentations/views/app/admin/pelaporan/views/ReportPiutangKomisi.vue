<template>
    <BaseReportLayout :lembaga="payload?.lembaga" :config="payload?.config">
        <div class="page-header">
            <h2>DAFTAR UTANG KOMISI SPS</h2>
            <h3 class="mt-1 mb-0 leading-tight uppercase">BULAN {{ payload?.periode?.bulan_name }}
                {{ payload?.periode?.tahun }}</h3>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th width="4%" style="text-align: center; vertical-align: middle;">No</th>
                    <th width="18%" style="text-align: center; vertical-align: middle;">Nama Pelanggan</th>
                    <th width="12%" style="text-align: center; vertical-align: middle;">No. Induk</th>
                    <th width="16%" style="text-align: center; vertical-align: middle;">Penerima Komisi</th>
                    <th width="18%" style="text-align: center; vertical-align: middle;">Jumlah Tagihan</th>
                    <th width="10%" style="text-align: center; vertical-align: middle;">Komisi</th>
                    <th width="10%" style="text-align: center; vertical-align: middle;">Dibayar</th>
                </tr>
            </thead>
            <tbody>
                <template v-if="rawItems.length > 0">
                    <tr v-for="(item, index) in rawItems" :key="item.bill_payment_id || index">
                        <td class="text-center">{{ index + 1 }}</td>
                        <td>{{ item.nama_pelanggan }}</td>
                        <td class="text-center">{{ item.kode_pelanggan }}</td>
                        <td>{{ item.penerima_komisi_name || '-' }}</td>
                        <td class="text-right">{{ formatCurrency(item.total_tagihan) }}</td>
                        <td class="text-right">{{ formatCurrency(item.komisi_total) }}</td>
                        <td class="text-right">{{ formatCurrency(item.dibayar) }}</td>
                    </tr>
                    <tr class="summary-row">
                        <td colspan="4" class="text-center font-bold">TOTAL</td>
                        <td class="text-right font-bold">{{ formatCurrency(totalTagihan) }}</td>
                        <td class="text-right font-bold">{{ formatCurrency(totalKomisi) }}</td>
                        <td class="text-right font-bold">{{ formatCurrency(totalDibayar) }}</td>
                    </tr>
                </template>
                <tr v-else>
                    <td colspan="7" class="empty-state">Tidak ada data piutang komisi pada periode ini.</td>
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
        payload: {
            type: Object,
            default: () => ({
                config: {},
                items: []
            })
        },
        meta: {
            type: Object,
            default: () => ({})
        },
    })

    const rawItems = computed(() => props.payload?.items || [])

    const totalTagihan = computed(() =>
        rawItems.value.reduce((sum, item) => sum + Number(item.total_tagihan || 0), 0)
    )
    const totalKomisi = computed(() =>
        rawItems.value.reduce((sum, item) => sum + Number(item.komisi_total || 0), 0)
    )
    const totalDibayar = computed(() =>
        rawItems.value.reduce((sum, item) => sum + Number(item.dibayar || 0), 0)
    )

    const formatCurrency = (val) => {
        if (val === null || val === undefined || isNaN(val)) return '0,00'
        return Number(val).toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })
    }

</script>

<style scoped>
    .page-header {
        text-align: center;
        margin-bottom: 15px;
    }

    .page-header h2 {
        font-size: 14pt;
        font-weight: bold;
        text-transform: uppercase;
        color: #000000;
        margin: 0;
    }

    .page-header h3 {
        font-size: 13pt;
        font-weight: bold;
        text-transform: uppercase;
        color: #000000;
        margin: 0;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        table-layout: fixed;
    }

    .data-table th {
        border: 1px solid #000000;
        padding: 4px;
        font-size: 12px;
        font-weight: bold;
        background: #d9d9d9;
        text-align: center;
    }

    .data-table td {
        border: 1px solid #000000;
        padding: 4px;
        font-size: 12px;
        vertical-align: middle;
        line-height: 1.2;
        color: #000000;
        word-wrap: break-word;
    }

    .text-center {
        text-align: center !important;
    }

    .text-right {
        text-align: right !important;
        padding-right: 8px !important;
    }

    .empty-state {
        text-align: center;
        padding: 20px;
        font-style: italic;
        font-size: 12px;
        border: 1px solid #000000;
    }

    .summary-row td {
        background: #e8e8e8 !important;
        font-weight: bold;
        border-top: 1px solid #000000;
    }

    .footer-container {
        width: 100%;
        margin-top: 25px;
        display: flex;
        justify-content: flex-end;
    }

    .footer-sign {
        width: 30%;
        text-align: center;
        font-size: 12px;
    }
</style>