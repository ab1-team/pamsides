<template>
    <BaseReportLayout :config="payload?.config">

        <div class="main-report-header">
            <h2>
                DAFTAR PELANGGAN
                <span v-if="meta?.nama_teknisi" style="font-weight: bold;"> {{ meta.nama_teknisi }}</span>
            </h2>
            <h2 class="mt-0 mb-0 leading-tight uppercase">
                BULAN {{ periodeText }}
            </h2>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 16%;">No. Induk</th>
                    <th style="width: 11%;">Tgl Pasang</th>
                    <th style="width: 16%;">Nama</th>
                    <th style="width: 13%;">NIK</th>
                    <th style="width: 20%;">Alamat</th>
                    <th style="width: 11%;">No. Telp</th>
                    <th style="width: 12%;">Status</th>
                </tr>
            </thead>
            <tbody>
                <template v-if="rawItems.length > 0">
                    <template v-for="(dusunGroup, namaDesa) in hierarchicalRows" :key="namaDesa">
                        <template v-for="(customers, namaDusun) in dusunGroup" :key="namaDusun">

                            <tr class="wilayah-header-gabung">
                                <td colspan="8">
                                    <span class="text-format-normal"><b>Desa {{ namaDesa.toLowerCase() }} Dusun
                                            {{ namaDusun.toLowerCase() }}</b></span>
                                </td>
                            </tr>

                            <tr v-for="(row, idx) in customers" :key="row.id ?? idx">
                                <td class="text-center">{{ idx + 1 }}</td>
                                <td class="text-center">{{ row.customer_code }}</td>
                                <td class="text-center">{{ formatDate(row.activated_at) }}</td>
                                <td>{{ row.name }}</td>
                                <td class="text-center">{{ row.nik }}</td>
                                <td class="text-wrap">{{ row.address }}</td>
                                <td class="text-center">{{ row.phone }}</td>
                                <td class="text-center" style="text-transform: uppercase;">{{ row.status }}</td>
                            </tr>

                        </template>
                    </template>
                </template>
                <tr v-else>
                    <td colspan="8" class="empty-state">Tidak ada data pelanggan pada periode ini.</td>
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

    const rawItems = computed(() => {
        return props.payload?.items || []
    })

    const hierarchicalRows = computed(() => {
        const tree = {}
        rawItems.value.forEach(item => {
            const desa = item.nama_desa || 'BELUM DISET'
            const dusun = item.nama_dusun || 'BELUM DISET'

            if (!tree[desa]) tree[desa] = {}
            if (!tree[desa][dusun]) tree[desa][dusun] = []

            tree[desa][dusun].push(item)
        })
        return tree
    })

    const periodeText = computed(() => {
        const m = props.meta || {}
        return `${m.bulan_name || '-'} ${m.tahun || ''}`
    })

    const formatDate = (val) => {
        if (!val) return '-'
        const d = new Date(val)
        if (Number.isNaN(d.getTime())) return val
        return d.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        })
    }

    const tanggalCetak = computed(() => {
        return new Date().toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        })
    })

    const tempat = 'Tempat'
</script>

<style scoped>
    .main-report-header {
        text-align: center;
        margin-top: 5px;
        margin-bottom: 15px;
    }

    .main-report-header h2 {
        margin: 0;
        font-size: 14pt;
        /* Disesuaikan agar lebih proporsional */
        font-weight: bold;
        color: #000000;
        line-height: 1.2;
        text-transform: uppercase;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        table-layout: fixed;
    }

    /* Header Utama - Disamakan dengan Neraca */
    .data-table th {
        border: 1px solid #000000;
        color: #000000;
        font-weight: bold;
        padding: 4px 4px;
        /* Diseragamkan */
        font-size: 12px;
        text-align: center;
        background: #d9d9d9;
        /* Warna background disamakan */
    }

    /* Data Baris Pelanggan - Disamakan dengan Neraca */
    .data-table td {
        padding: 4px 4px;
        /* Diseragamkan (jarak atas-bawah & kiri-kanan) */
        border: 1px solid #000000;
        vertical-align: middle;
        font-size: 12px;
        color: #000000;
        word-wrap: break-word;
        line-height: 1.2;
        /* Diseragamkan */
    }

    /* Baris Wilayah */
    .wilayah-header-gabung td {
        background-color: #f3f3f3 !important;
        font-size: 12px;
        padding: 4px 8px;
        text-align: left;
        border: 1px solid #000000;
        font-weight: bold;
    }

    .text-format-normal {
        text-transform: capitalize;
    }

    .text-center {
        text-align: center;
    }

    .text-wrap {
        white-space: normal;
        line-height: 1.2;
    }

    /* Perbaikan typo font-size */
    .empty-state {
        text-align: center;
        padding: 20px;
        font-style: italic;
        font-size: 12px;
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

    .mt-0 {
        margin-top: 0px !important;
    }

    .mb-0 {
        margin-bottom: 0px !important;
    }
</style>