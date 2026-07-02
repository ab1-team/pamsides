<template>
  <BaseReportLayout :config="payload?.config">
    <div class="page-header">
        <h2>
            PIUTANG PELANGGAN 
            <span v-if="meta?.nama_teknisi" style="font-weight: bold;">{{ meta.nama_teknisi }}</span>
        </h2>
      <h2 class="mt-1 mb-0 leading-tight uppercase">BULAN {{ periodeText }}</h2>
    </div>

    <table class="data-table" v-if="rawItems.length > 0">
      <thead>
        <tr style="background-color: rgb(230, 230, 230); font-weight: bold; text-align: center;">
          <th width="4%" class="t l b" rowspan="2" style="text-align: center; vertical-align: middle;">No</th>
          <th width="21%" class="t l b" rowspan="2" style="text-align: center; vertical-align: middle;">Nama</th>
          <th width="15%" class="t l b" rowspan="2" style="text-align: center; vertical-align: middle;">No. Induk</th>
          <th width="30%" class="t l b" colspan="3" style="text-align: center; vertical-align: middle;">Tunggakan</th>
          <th width="10%" class="t l b" rowspan="2" style="text-align: center; vertical-align: middle;">Jumlah Tunggakan</th>
          <th width="10%" class="t l b" rowspan="2" style="text-align: center; vertical-align: middle;">Dibayar</th>
          <th width="10%" class="t l b r" rowspan="2" style="text-align: center; vertical-align: middle;">Kategori</th>
        </tr>
        <tr style="background-color: rgb(230, 230, 230); font-weight: bold; text-align: center;">
          <th width="10%" class="t l b" style="text-align: center; vertical-align: middle;">s/d 3 Bulan Lalu</th>
          <th width="10%" class="t l b" style="text-align: center; vertical-align: middle;">Bulan Lalu</th>
          <th width="10%" class="t l b" style="text-align: center; vertical-align: middle;">Bulan Ini</th>
        </tr>
      </thead>
      <tbody>
        <template v-for="(dusunGroup, namaDesa) in hierarchicalRows" :key="namaDesa">
          <template v-for="(customers, namaDusun) in dusunGroup" :key="namaDusun">
            
            <tr class="wilayah-header-gabung">
              <td colspan="9">
                <span class="text-format-normal"><b> Desa {{ namaDesa.toLowerCase() }} Dusun {{ namaDusun.toLowerCase() }}</b></span>
                
              </td>
            </tr>

            <tr v-for="(row, idx) in customers" :key="idx">
              <td class="text-center">{{ idx + 1 }}</td>
              <td>{{ row.name }}</td>
              <td class="text-center">{{ row.customer_code }}</td>
              <td class="text-right">{{ formatCurrency(row.sd_3_bulan_lalu) }}</td>
              <td class="text-right">{{ formatCurrency(row.bulan_lalu) }}</td>
              <td class="text-right">{{ formatCurrency(row.bulan_ini) }}</td>
              <td class="text-right" style="font-weight: bold;">{{ formatCurrency(row.total_tunggakan) }}</td>
              <td class="text-right">{{ formatCurrency(row.dibayar) }}</td>
              <td class="text-center">
                <span class="badge-kategori">{{ row.kategori }}</span>
              </td>
            </tr>

          </template>
        </template>
      </tbody>
    </table>

    <div v-if="rawItems.length === 0" class="empty-state">
      Tidak ada data piutang/tunggakan pelanggan pada periode ini.
    </div>

    <div class="footer-container">
      <div class="footer-sign">
        <p>{{ tempat }}, {{ tanggalCetak }}</p>
        <p style="margin-bottom: 55px;">Petugas,</p>
        <p><b>( ........................... )</b></p>
      </div>
    </div>
  </BaseReportLayout>
</template>

<script setup>
    import { computed } from 'vue'
    import BaseReportLayout from '../layouts/BaseReportLayout.vue'

    const props = defineProps({
    payload: { type: Object, default: () => ({ config: {}, items: [] }) },
    meta: { type: Object, default: () => ({}) },
    })

    const rawItems = computed(() => props.payload?.items || [])

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

    const formatCurrency = (val) => {
    if (val === null || val === undefined || isNaN(val)) return '0'
    return Number(val).toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
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
    .page-header {
    text-align: center;
    margin-bottom: 15px;
    }
    .page-header h2 {
    font-size: 11pt;
    font-weight: bold;
    }
    .data-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    }
    .data-table th {
    border: 1px solid #1e1e1e;
    padding: 5px 4px;
    font-size: 8.5pt;
    }

    /* Mengurangi tinggi baris data agar lebih rapat & tidak melar */
    .data-table td {
    border: 1px solid #1e1e1e;
    padding: 2.5px 5px;
    font-size: 8.5pt;
    vertical-align: middle;
    line-height: 1.1;
    }

    .wilayah-header-gabung td {
    background-color: #ffffff !important;
    font-size: 8.5pt;
    font-weight: bold;
    padding: 3px 8px;
    text-align: left;
    border: 1px solid #1e1e1e;
    }
    .pembatas-spasi {
    margin-left: 20px;
    }
    .text-format-normal {
    text-transform: capitalize;
    }
    .text-center {
    text-align: center !important;
    }
    .text-right {
    text-align: right !important;
    padding-right: 8px !important;
    }
    .badge-kategori {
    font-weight: 500;
    font-size: 8pt;
    text-transform: uppercase;
    }
    .empty-state {
    text-align: center;
    padding: 20px;
    font-style: italic;
    font-size: 8.5pt;
    border: 1px solid #1e1e1e;
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
    font-size: 8.5pt;
    }
</style>