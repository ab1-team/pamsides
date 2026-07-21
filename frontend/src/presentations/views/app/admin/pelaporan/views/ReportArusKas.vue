<template>
    <BaseReportLayout :lembaga="payload?.lembaga" :config="payload?.config">
        <div class="header-section" style="text-align:center;margin-bottom:15px;font-family:sans-serif;">
            <h2 style="margin:0;font-size:14pt;font-weight:bold;text-transform:uppercase;color:#000;">
                ARUS KAS
            </h2>
            <h3 style="margin:-5px 0 0 0; font-size:12pt; font-weight:bold; color:#000; text-transform: uppercase;">
                BULAN {{ periodeText }}
            </h3>
        </div>

        <table class="report-table">
            <colgroup>
                <col style="width: 8%">
                <col style="width: 62%">
                <col style="width: 30%">
            </colgroup>
            <thead>
                <tr>
                    <th></th>
                    <th>Nama Akun</th>
                    <th class="text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <template v-for="(item, index) in (payload?.items || [])" :key="index">
                    <tr v-if="item.type === 'section-header'" class="header-row">
                        <td class="roman-cell">{{ item.roman }}</td>
                        <td>{{ item.label }}</td>
                        <td class="text-right">
                            {{ item.jumlah !== null && item.jumlah !== undefined ? format(item.jumlah) : '' }}</td>
                    </tr>

                    <tr v-else-if="item.type === 'spacer'" class="spacer-row">
                        <td colspan="3">&nbsp;</td>
                    </tr>

                    <tr v-else-if="item.type === 'item-row'" class="detail-row"
                        :class="{ 'zebra-bg': index % 2 !== 0 }">
                        <td></td>
                        <td>{{ item.label }}</td>
                        <td class="text-right">{{ format(item.jumlah) }}</td>
                    </tr>

                    <tr v-else-if="item.type === 'total-row'" class="total-row">
                        <td></td>
                        <td>{{ item.label }}</td>
                        <td class="text-right">{{ format(item.jumlah) }}</td>
                    </tr>

                    <tr v-else-if="item.type === 'sub-header'" class="sub-header-row">
                        <td></td>
                        <td>{{ item.label }}</td>
                        <td></td>
                    </tr>

                    <tr v-else-if="item.type === 'sub-total'" class="sub-total-row">
                        <td></td>
                        <td>{{ item.label }}</td>
                        <td class="text-right">{{ format(item.jumlah) }}</td>
                    </tr>
                </template>
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
            required: true
        }
    })

    const periodeText = computed(() => {
        const p = props.payload?.periode || {}
        return `${(p.bulan_name || '').toUpperCase()} ${p.tahun || ''}`
    })

    const format = (value) => {
        const angka = Number(value || 0)
        return angka < 0 ?
            `(${Math.abs(angka).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })})` :
            angka.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })
    }
</script>

<style scoped>
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
        background-color: #b1b1b1;
        color: #000000;
        text-align: center;
        padding: 4px 8px;
        border-bottom: 5px solid #ffffff;
    }

    .report-table th.text-right {
        text-align: right;
        padding-right: 8px;
    }

    .roman-cell {
        text-align: center;
    }

    .header-row td {
        background: #949090;
        color: #000000;
    }

    .spacer-row td {
        background: #f0f0f0;
        padding: 0px;
        height: 8px;
        border: 0;
    }

    .total-row td {
        background: #888787;
        padding: 4px;
    }

    .sub-header-row td {
        background: #b8b8b8;
        font-weight: bold;
        padding: 4px 6px;
    }

    .sub-total-row td {
        background: #a0a0a0;
        font-weight: bold;
        padding: 4px 6px;
    }

    .detail-row td {
        padding: 3px 4px;
    }

    .detail-row {
        background-color: #dadada;
    }

    .zebra-bg {
        background-color: #f0f0f0;
    }

    .text-right {
        text-align: right;
    }
</style>