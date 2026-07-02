<template>
  <BaseReportLayout :config="payload?.config">
    <div class="header-section">
      <h2>JURNAL TRANSAKSI</h2>
      <h3>BULAN {{ periodeText }}</h3>
    </div>

    <table class="data-table">
      <thead>
        <tr>
          <th style="width: 5%;">No</th>
          <th style="width: 17%;">Tanggal</th>
          <th style="width: 8%;">Ref ID.</th>
          <th style="width: 11%;">Kd. Rek</th>
          <th style="width: 27%;">Keterangan</th>
          <th style="width: 15%;">Debit</th>
          <th style="width: 15%;">Kredit</th>
          <th style="width:8%;">Ins</th>
        </tr>
      </thead>
      <tbody>
        <template v-for="(item, index) in payload.items" :key="item.id">
          <tr>
            <td class="text-center">{{ index + 1 }}</td>
            <td class="text-center">{{ formatDate(item.tgl) }}</td>
            <td class="text-center">{{ item.id }}</td>
            <td class="text-center">{{ item.debet.kode }}</td>
            <td class="text-left">{{ item.debet.nama }}</td>
            <td class="text-right">{{ formatCurrency(item.debet.jumlah) }}</td>
            <td class="text-right"></td>
            <td class="text-center"></td>
          </tr>
          <tr>
            <td class="text-center"></td>
            <td class="text-center"></td>
            <td class="text-center">{{ item.id }}</td>
            <td class="text-center">{{ item.kredit.kode }}</td>
            <td class="text-left">{{ item.kredit.nama }}</td>
            <td class="text-right"></td>
            <td class="text-right">{{ formatCurrency(item.kredit.jumlah) }}</td>
            <td class="text-center"></td>
          </tr>
        </template>
      </tbody>
      <tfoot>
        <tr class="total">
          <td colspan="5" class="text-center" style="font-weight: bold;">Total Transaksi</td>
          <td class="text-right" style="font-weight: bold;">{{ totalDebit }}</td>
          <td class="text-right" style="font-weight: bold;">{{ totalKredit }}</td>
          <td class="text-center"></td>
        </tr>
      </tfoot>
    </table>
  </BaseReportLayout>
</template>

<script setup>
    import { computed } from 'vue'
    import BaseReportLayout from '../layouts/BaseReportLayout.vue'

    const props = defineProps({
      payload: { type: Object, default: () => ({ config: {}, items: [], periode: {} }) },
      meta: { type: Object, default: () => ({}) },
    })

    const periodeText = computed(() => {
      const p = props.payload.periode || {}
      return `${(p.bulan_name || '').toUpperCase()} ${p.tahun || ''}`
    })

    // Fungsi bantu untuk membersihkan string angka agar kalkulasi akurat
    const parseNumber = (val) => parseFloat(String(val).replace(/[^0-9.-]+/g, "")) || 0;

    const totalDebit = computed(() => {
      const total = props.payload.items.reduce((sum, item) => sum + parseNumber(item.debet.jumlah), 0);
      return total.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    });

    const totalKredit = computed(() => {
      const total = props.payload.items.reduce((sum, item) => sum + parseNumber(item.kredit.jumlah), 0);
      return total.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    });

    const formatCurrency = (val) => {
      return parseNumber(val).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    const formatDate = (dateString) => {
      if (!dateString) return '-';
      const date = new Date(dateString);
      
      // Mengambil komponen tanggal, bulan, dan tahun
      const day = String(date.getDate()).padStart(2, '0');
      const month = String(date.getMonth() + 1).padStart(2, '0'); // Bulan dimulai dari 0
      const year = date.getFullYear();
      
      return `${day}/${month}/${year}`;
    };
</script>

<style scoped>
    .header-section { text-align: center; margin-top: 5px; margin-bottom: 15px; font-family: sans-serif; line-height: 1.0; }
    .header-section h2 { margin: 0; font-size: 13pt; font-weight: bold; text-transform: uppercase; margin-bottom: 0px; padding: 0; }
    .header-section h3 { margin: 0; font-size: 12pt; font-weight: bold; padding: 0; }

    .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; table-layout: fixed; }
    .data-table th { border: 0px solid #000000; color: #f0f0f0; font-weight: bold; padding: 4px; font-size: 12px; text-align: center; background: #5c5c5c; }
    .data-table td { padding: 4px; border: 0px solid #000000; vertical-align: middle; font-size: 12px; color: #000000; }

    /* Zebra striping (transaksi 2, 4, 6... berwarna abu-abu) */
    .data-table tbody tr:nth-child(4n-1),
    .data-table tbody tr:nth-child(4n) { background-color: #e8e8e8; }

 
    .total {
      /* Kita gunakan warna yang sama dengan warna baris zebra data (#e8e8e8) */
      background-color: #e8e8e8 !important; 
      color: #000000; /* Teks hitam agar mudah dibaca */
      font-weight: bold;
    }

    /* Pastikan border tetap terlihat */
    .total td {
      border: 0px solid #000000 !important;
    }
    .text-left { text-align: left; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
</style>