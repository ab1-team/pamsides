<template>
  <BaseReportLayout :lembaga="lembaga" :config="payload?.config" :no-kop="true">
    <div class="bukti-grid">
      <div
        v-for="(row, idx) in items"
        :key="idx"
        class="bukti-cell"
      >
        <div class="page-header">
          <h2>BUKTI TRANSAKSI</h2>
          <h2 class="mt-1 mb-0 leading-tight" style="font-size: 16px;">
            {{ title }}
          </h2>
          <p class="page-subtitle" style="font-size: 13px;">
            Periode {{ periodeLabel }}
          </p>
        </div>

        <div class="meta-grid">
          <div class="meta-col">
            <div class="meta-row">
              <span class="meta-label">Kode Akun</span>
              <span class="meta-sep">:</span>
              <span class="meta-value">{{ selectedAccount || '-' }}</span>
            </div>
          </div>
          <div class="meta-col meta-col-right">
            <div class="meta-row">
              <span class="meta-label">Saldo Awal</span>
              <span class="meta-sep">:</span>
              <span class="meta-value">{{ formatCurrency(saldoAwal) }}</span>
            </div>
          </div>
        </div>

        <table class="data-table">
          <thead>
            <tr>
              <th width="4%" class="text-center">No</th>
              <th width="12%" class="text-center">Tanggal</th>
              <th width="14%" class="text-center">Kode Akun</th>
              <th width="32%" class="text-left">Keterangan</th>
              <th width="10%" class="text-center">ID Trx</th>
              <th width="14%" class="text-right">Debit</th>
              <th width="14%" class="text-right">Kredit</th>
              <th width="14%" class="text-right">Saldo</th>
            </tr>
          </thead>
          <tbody>
            <tr :class="{ 'header-row': row._isHeader }">
              <td class="text-center">{{ row._isHeader ? '' : idx + 1 }}</td>
              <td class="text-center">{{ formatDateId(row._isHeader ? row.tanggalLabel : row.tgl_transaksi) }}</td>
              <td class="text-center">{{ row._isHeader ? '' : kodeAkun(row) }}</td>
              <td class="text-left">{{ row._isHeader ? row.label : (row.keterangan_transaksi || '-') }}</td>
              <td class="text-center">{{ row._isHeader ? '' : row.id }}</td>
              <td class="text-right">{{ formatCurrency(row._isHeader ? row.debit : debitOf(row)) }}</td>
              <td class="text-right">{{ formatCurrency(row._isHeader ? row.kredit : kreditOf(row)) }}</td>
              <td class="text-right">{{ formatCurrency(row._saldo) }}</td>
            </tr>
          </tbody>
        </table>

        <!-- BAGIAN TANDA TANGAN YANG KURANG -->
        <div class="bukti-sign">
          <div class="sign-item">
            <div class="sign-label">Disetujui,</div>
            <div class="sign-space"></div>
            <div class="sign-name">Bambang Sugeni , AKg</div>
          </div>
          <div class="sign-item">
            <div class="sign-label">Diverifikasi,</div>
            <div class="sign-space"></div>
            <div class="sign-name">Rohayati, S.Akt</div>
          </div>
          <div class="sign-item">
            <div class="sign-label">Disiapkan Oleh :</div>
            <div class="sign-space"></div>
            <div class="sign-name"></div>
          </div>
        </div>
        <!-- AKHIR BAGIAN TANDA TANGAN -->

      </div>
    </div>
  </BaseReportLayout>
</template>