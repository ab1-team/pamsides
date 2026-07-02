<template>
  <BaseReportLayout :config="payload?.config">
    <div
      class="header-section"
      style="text-align:center;margin-bottom:15px;font-family:sans-serif;"
    >
      <h2
        style="margin:0;font-size:14pt;font-weight:bold;text-transform:uppercase;color:#000;"
      >
        NERACA
      </h2>

      <h3
        style="margin:-5px 0 0 0;font-size:12pt;font-weight:bold;color:#000;"
      >
        BULAN {{ periodeText }}
      </h3>
    </div>

    <table class="report-table">
      <colgroup>
        <col style="width:30%">
        <col style="width:11.66%">
        <col style="width:11.66%">
        <col style="width:11.66%">
        <col style="width:11.66%">
        <col style="width:11.68%">
        <col style="width:11.68%">
      </colgroup>
      <thead>
        <tr>
          <th rowspan="2">Rekening</th>
          <th colspan="2">Neraca Saldo</th>
          <th colspan="2">Laba Rugi</th>
          <th colspan="2">Neraca</th>
        </tr>

        <tr>
          <th>Debit</th>
          <th>Kredit</th>
          <th>Debit</th>
          <th>Kredit</th>
          <th>Debit</th>
          <th>Kredit</th>
        </tr>
      </thead>

      <tbody>

        <tr
          v-for="item in payload?.items"
          :key="item.kode_akun"
        >
          <td>
            {{ item.kode_akun }}. {{ item.nama_akun }}
          </td>

          <td class="text-right">
            {{ format(item.saldo_debit) }}
          </td>

          <td class="text-right">
            {{ format(item.saldo_kredit) }}
          </td>

          <td class="text-right">
            {{ format(item.saldo_laba_rugi_debit) }}
          </td>

          <td class="text-right">
            {{ format(item.saldo_laba_rugi_kredit) }}
          </td>

          <td class="text-right">
            {{ format(item.saldo_neraca_debit) }}
          </td>

          <td class="text-right">
            {{ format(item.saldo_neraca_kredit) }}
          </td>
        </tr>

        <!-- Surplus Defisit -->

        <tr class="summary-dark">
          <td align="center">
            <b>Surplus / Defisit</b>
          </td>

          <td></td>
          <td></td>

          <td class="text-right">
            {{ format(payload?.summary?.surplus_defisit) }}
          </td>

          <td></td>

          <td></td>

          <td class="text-right">
            {{ format(payload?.summary?.surplus_defisit) }}
          </td>
        </tr>

        <!-- Jumlah -->

        <tr class="summary-light">
          <td align="center">
            <b>Jumlah</b>
          </td>

          <td class="text-right">
            {{ format(payload?.summary?.jumlah_saldo_debit) }}
          </td>

          <td class="text-right">
            {{ format(payload?.summary?.jumlah_saldo_kredit) }}
          </td>

          <td class="text-right">
            {{
              format(
                Number(payload?.summary?.jumlah_laba_rugi_debit ?? 0) +
                Number(payload?.summary?.surplus_defisit ?? 0)
              )
            }}
          </td>

          <td class="text-right">
            {{ format(payload?.summary?.jumlah_laba_rugi_kredit) }}
          </td>

          <td class="text-right">
            {{ format(payload?.summary?.jumlah_neraca_debit) }}
          </td>

          <td class="text-right">
            {{
              format(
                Number(payload?.summary?.jumlah_neraca_kredit ?? 0) +
                Number(payload?.summary?.surplus_defisit ?? 0)
              )
            }}
          </td>
        </tr>

      </tbody>
    </table>
  </BaseReportLayout>
</template>

<script setup>
import { computed } from 'vue'
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

  return angka < 0
    ? `(${Math.abs(angka).toLocaleString('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      })})`
    : angka.toLocaleString('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      })
}
</script>

<style scoped>
.report-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
  table-layout: fixed;
}

.report-table th,
.report-table td {
  border: 1px solid #000;
  /* padding diperkecil menjadi 1px 4px agar lebih ringkas */
  padding: 4px 4px;
  /* Menambahkan line-height agar teks tetap berada di tengah secara vertikal */
  line-height: 1.2;
}

.report-table th {
  background: #d9d9d9;
  text-align: center;
  font-weight: bold;
}

.text-right {
  text-align: right;
}

.summary-dark {
  background: #a7a7a7;
  font-weight: bold;
}

.summary-light {
  background: #efefef;
  font-weight: bold;
}
</style>