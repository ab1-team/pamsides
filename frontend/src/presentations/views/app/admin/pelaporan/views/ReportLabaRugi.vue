<template>
    <BaseReportLayout :lembaga="payload?.lembaga" :config="payload?.config" :no-meta-header="!isFirstPage">
        <div v-if="isFirstPage" class="header-section" style="text-align:center; margin-bottom:12px; font-family:sans-serif;">
            <h2 style="margin:0; font-size:14pt; font-weight:bold; text-transform:uppercase; color:#000;">
                LAPORAN LABA RUGI
            </h2>
            <h3 style="margin:-5px 0 0 0; font-size:12pt; font-weight:bold; color:#000; text-transform:uppercase;">
                PERIODE {{ periodeText }}
            </h3>
        </div>

        <table class="report-table">
            <colgroup>
                <col style="width: 50%">
                <col style="width: 17%">
                <col style="width: 15%">
                <col style="width: 15%">
            </colgroup>
            <thead>
                <tr style="font-weight: bold;">
                    <th class="text-center">Rekening</th>
                    <th class="text-center">s.d. Bulan Lalu</th>
                    <th class="text-center">Bulan Ini</th>
                    <th class="text-center">s.d. Bulan Ini</th>
                </tr>
            </thead>
            <tbody>
                <template v-for="(row, rIndex) in rows" :key="rIndex">
                    <tr v-if="row.isHeader" :class="row.type === 'main' ? 'summary-main' : 'summary-sub'">
                        <td colspan="4" class="text-left" style="font-weight: bold;">
                            {{ row.label }}
                        </td>
                    </tr>
                    <tr v-else-if="row.isTotal" class="total-row" style="font-weight: bold;">
                        <td class="text-left">{{ row.nama_akun }}</td>
                        <td class="text-right">{{ format(row.sd_bulan_lalu) }}</td>
                        <td class="text-right">{{ format(row.bulan_ini) }}</td>
                        <td class="text-right">{{ format(row.sd_bulan_ini) }}</td>
                    </tr>
                    <tr v-else :class="rIndex % 2 === 0 ? 'row-white' : 'row-grey'">
                        <td class="text-left">{{ row.nama_akun }}</td>
                        <td class="text-right">{{ format(row.sd_bulan_lalu) }}</td>
                        <td class="text-right">{{ format(row.bulan_ini) }}</td>
                        <td class="text-right">{{ format(row.sd_bulan_ini) }}</td>
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
        payload: Object
    })

    const rows = computed(() => {
        if (Array.isArray(props.payload?.flatRows) && props.payload.flatRows.length > 0) {
            return props.payload.flatRows
        }
        if (Array.isArray(props.payload?.groups)) {
            const out = []
            props.payload.groups.forEach((g) => {
                out.push({
                    isHeader: true,
                    type: g.type,
                    label: g.label
                })
                if (Array.isArray(g.items)) {
                    g.items.forEach((it) => out.push(it))
                }
            })
            return out
        }
        return []
    })

    const periodeText = computed(() => {
        const p = props.payload?.periode || {}
        if (p.periode_text) return p.periode_text
        // Laba Rugi bulanan = kumulatif Januari s.d bulan yang dipilih
        // Contoh buka bulan Agustus -> "PERIODE 01 JANUARI 2026 S.D 31 AGUSTUS 2026"
        const bulan = p.bulan || p.bulan_index
        const tahun = p.tahun
        if (!bulan || !tahun) return ''
        const bulanNames = [
            '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ]
        const bulanInt = parseInt(bulan, 10)
        const bulanName = bulanNames[bulanInt] || p.bulan_name?.toUpperCase() || ''
        const tahunInt = parseInt(tahun, 10)
        const lastDay = new Date(tahunInt, bulanInt, 0).getDate()
        return `01 Januari ${tahunInt} S.D ${lastDay} ${bulanName} ${tahunInt}`
    })

    const isFirstPage = computed(() => {
        const info = props.payload?.pageInfo
        if (info) return info.current === 1
        return props.payload?.isFirstPage !== false
    })

    const format = (value) => {
        const angka = Number(value || 0)
        return angka.toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })
    }
</script>

<style>
    .report-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        /* Disamakan dengan Neraca */
        table-layout: fixed;
        font-family: sans-serif;
    }

    /* Disamakan padding-nya dengan Neraca (4px 4px) */
    .report-table th,
    .report-table td {
        border: 0px solid #000;
        padding: 2px 4px;
        line-height: 1.2;
    }

    .report-table th {
        background: #e6e6e6;
        /* Warna header disamakan dengan Neraca */
        text-align: center;
        font-weight: bold;
        padding: 8px 4px;
    }

    .text-left {
        text-align: left !important;
    }

    .text-right {
        text-align: right !important;
    }

    .text-center {
        text-align: center !important;
    }

    .summary-main {
        background: #c9c9c9 !important;
        color: #000000 !important;
        font-weight: bold;
        text-transform: uppercase;
    }

    .summary-sub {
        background: #b1b1b1 !important;
        color: #000000 !important;
        font-weight: bold;
        text-transform: uppercase;
    }

    .total-row {
        background: #888888 !important;
        color: #000000 !important;
        font-weight: bold;
    }


    .row-white {
        background-color: #e6e6e6;
    }

    .row-grey {
        background-color: #ffffff;
    }
</style>