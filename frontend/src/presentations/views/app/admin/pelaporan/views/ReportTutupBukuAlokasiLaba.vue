<template>
    <BaseReportLayout :config="payload?.config">
        <div class="header-section" style="text-align:center; margin-bottom:10px; font-family:sans-serif;">
            <h2 style="margin:0; font-size:14pt; font-weight:bold; text-transform:uppercase; color:#000;">
                ALOKASI PEMBAGIAN LABA
            </h2>
            <h3 style="margin:0; font-size:12pt; font-weight:bold; color:#000; text-transform:uppercase;">
                TAHUN {{ payload?.periode?.tahun || '' }}
            </h3>
        </div>

        <table class="report-table">
            <thead>
                <tr>
                    <th style="text-align: left;">Keterangan</th>
                    <th style="text-align: right;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <tr class="header-row">
                    <td colspan="2">LABA RUGI PERIODE INI</td>
                </tr>
                <tr>
                    <td>Surplus / (Defisit)</td>
                    <td style="text-align: right;">{{ formatCurrency(payload?.surplus || 0) }}</td>
                </tr>
                <tr class="total-row">
                    <td><strong>TOTAL LABA</strong></td>
                    <td style="text-align: right;"><strong>{{ formatCurrency(payload?.surplus || 0) }}</strong></td>
                </tr>

                <tr class="header-row">
                    <td colspan="2">PENGALOKASIAN LABA</td>
                </tr>

                <tr v-for="(row, index) in daftarAlokasi" :key="index" :class="index % 2 === 0 ? 'zebra-row' : ''">
                    <td>{{ row.label }}</td>
                    <td style="text-align: right;">{{ formatCurrency(row.jumlah) }}</td>
                </tr>

                <tr class="total-row">
                    <td><strong>TOTAL PENGALOKASIAN</strong></td>
                    <td style="text-align: right;"><strong>{{ formatCurrency(payload?.surplus || 0) }}</strong></td>
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
            required: true
        }
    })

    const hitungSaldo = (account) => {
        if (!account.amount || !Array.isArray(account.amount)) return 0
        return account.amount.reduce((sum, a) => sum + parseFloat(a.kredit || 0), 0)
    }

    const totalAlokasi = computed(() => {
        return props.payload.alokasi?.reduce((sum, item) => sum + hitungSaldo(item), 0) || 0
    })

    const labaDitahan = computed(() => {
        return (props.payload.surplus || 0) - totalAlokasi.value
    })

    const daftarAlokasi = computed(() => {
        const list = (props.payload.alokasi || []).map(item => ({
            label: item.nama_akun,
            jumlah: hitungSaldo(item)
        }))

        list.push({
            label: 'Laba Ditahan',
            jumlah: labaDitahan.value
        })
        return list
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
</script>

<style scoped>
    .report-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
        /* Sedikit dikecilkan agar lebih kompak */
    }

    .report-table th,
    .report-table td {
        padding: 3px 8px;
        /* Padding atas-bawah diperkecil menjadi 3px */
        border: 0px solid #000;
    }

    .report-table th {
        background-color: #000;
        color: #fff;
    }

    .header-row {
        background-color: #5e5a5a;
        color: #fff;
        font-weight: bold;
    }

    .total-row {
        background-color: #888787;
        color: #000000;
        font-weight: bold;
    }

    .zebra-row {
        background-color: #dbdbdb;
    }
</style>