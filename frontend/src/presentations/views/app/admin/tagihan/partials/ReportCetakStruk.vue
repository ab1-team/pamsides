<template>
  <BaseReportLayout :lembaga="lembaga" :config="payload?.config" :no-kop="true">
    <div class="struk-page">
      <div v-for="(item, idx) in strukList" :key="idx" class="struk">
        <!-- HEADER -->
        <div class="header">
          <div class="meta-left">
            <div><span class="lbl-meta">TEKNISI</span><span>:</span><span>{{ (payload?.cater || '-').toString().toUpperCase() }}</span></div>
            <div><span class="lbl-meta">NO URUT</span><span>:</span><span>{{ item.customer.noUrut || '-' }}</span></div>
            <div><span class="lbl-meta">PEMAKAIAN</span><span>:</span><span>{{ periodeText }}</span></div>
          </div>

          <div class="header-center">
            <img class="logo-l" src="/logo2.png" alt="Logo Unit Pengelolaan Air" />
            <div class="t">
              <div class="tm">STRUK TAGIHAN PEMAKAIAN AIR</div>
              <div class="tm">BADAN USAHA MILIK DESA (BUMDes)</div>
              <div class="tm">UNIT AIR</div>
            </div>
            <img class="logo-r" src="/logo1.png" alt="Logo BUMDes Bangun Kencana" />
          </div>
        </div>

        <!-- TABEL PELANGGAN -->
        <table class="tbl">
          <thead>
            <tr>
              <th>NAMA PELANGGAN</th><th>NO INDUK</th><th>ALAMAT</th><th>METER AWAL</th><th>METER AKHIR</th><th>PEMAKAIAN</th>
            </tr>
          </thead>
          <tbody>
            <tr align="center">
              <td>{{ item.customer.nama }}</td>
              <td>{{ item.customer.noUrut }}</td>
              <td>{{ item.customer.alamat }}</td>
              <td>{{ Number(item.customer.meterAwal || 0).toLocaleString('id-ID') }}</td>
              <td>{{ Number(item.customer.meterAkhir || 0).toLocaleString('id-ID') }}</td>
              <td>{{ item.customer.pemakaian }}</td>
            </tr>
          </tbody>
        </table>

        <!-- RINCIAN & TTD -->
        <div class="c">
          <div class="r">
            <div class="rb">RINCIAN BIAYA</div>
            <div class="r-item"><span class="lbl-rincian">Pemakaian Air</span><span>:</span><span>Rp. {{ rupiah(item.rincian.pemakaianAir) }}</span></div>
            <div class="r-item"><span class="lbl-rincian">Beban Tetap</span><span>:</span><span>Rp. {{ rupiah(item.rincian.bebanTetap) }}</span></div>
            <div class="r-item"><span class="lbl-rincian">Denda</span><span>:</span><span>Rp. {{ rupiah(item.rincian.denda) }}</span></div>
            <div class="r-item tot"><span class="lbl-rincian">Total</span><span>:</span><span class="val-bold">Rp. {{ rupiah(item.rincian.total) }}</span></div>
            <div class="r-item terbilang-row"><span class="lbl-rincian">Terbilang</span><span>:</span><span class="i">{{ bilang(item.rincian.total) }}</span></div>
          </div>
          <div class="ttd">
            <div>{{ tempat }}, {{ tanggalCetak }}</div>
            <div class="sp">{{ payload?.bendahara?.nama_jabatan || 'Bendahara' }}</div>
            <!--
              Gambar tanda tangan bendahara.

              File: frontend/public/ttdbendahara.png
              Sumber: backend/storage/app/public/sop/logo/ttdbendahara.png

              Pakai <img>, bukan background-image, supaya Chrome tetap
              mencetaknya saat Ctrl+P tanpa perlu centang "Background graphics"
              (pola yang sama dipakai logo kop struk, lihat .logo-l/.logo-r).
            -->
            <img class="ttd-img" src="/ttdbendahara.png" alt="Tanda tangan Bendahara" />
            <div class="nb">{{ payload?.bendahara?.name || '_________________' }}</div>
          </div>
        </div>

        <!-- BAGIAN BAWAH: TRANSFER & CATATAN -->
        <div class="bottom-section">
          <div class="rek">
            <div class="rt">Pembayaran Via Transfer:</div>
            <div class="rek-bold">BRI No Rekening:</div>
            <div class="nr">0153-01-001906-56-9</div>
            <div class="an">a/n. BUMDES BANGUN KENCANA MULO</div>
          </div>

          <div class="cat">
            Seluruh pelanggan "Tirto Mulo" wajib mematuhi segala ketentuan manajemen pengelolaan oleh
            BUMDes BANGUN KENCANA MULO, sesuai dengan Peraturan Desa Mulo Nomor 3 Tahun 2018.<br>
            Keluhan pelanggan bisa WA Nomor 0882-1673-8479 (Iswanto) 0878-0484-5880 (Nurli).<br>
            NB. Terlambat 2 bulan akan diterbitkan Surat Peringatan, Terlambat 3 bulan akan diterbitkan Surat Pemutusan Sementara.
          </div>
        </div>
      </div>
    </div>
  </BaseReportLayout>
</template>

<script setup>
import { computed } from 'vue'
import BaseReportLayout from '@/presentations/views/app/admin/pelaporan/layouts/BaseReportLayout.vue'

const props = defineProps({
  payload: { type: Object, default: () => ({}) },
  meta: { type: Object, default: () => ({}) },
})

const lembaga = computed(() => props.payload?.lembaga || {})

const buildRincian = (c) => {
  const tagihan = Number(c.tagihan || 0)
  const abodemen = Number(c.abodemen || 0)
  const denda = Number(c.denda || 0)
  const pemakaianAir = Number(c.pemakaian_charge ?? c.pemakaianCharge ?? 0)
  return {
    pemakaianAir,
    bebanTetap: abodemen,
    denda,
    total: tagihan,
  }
}

const strukList = computed(() => {
  const list = Array.isArray(props.payload?.customers)
    ? props.payload.customers
    : (props.payload?.customer ? [props.payload.customer] : [])
  return list.map((c) => ({ customer: c, rincian: buildRincian(c) }))
})

const periodeText = computed(() => {
  const f = props.payload?.filter || {}
  return f.bulan ? `${f.bulan} ${f.tahun}` : '-'
})

const rupiah = (val) => Number(val || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const tempat = 'Mulo'

const bulanId = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']

const tanggalCetak = computed(() => {
  const f = props.payload?.filter || {}
  const tahun = Number(f.tahun) || new Date().getFullYear()
  const bulanStr = String(f.bulan || '').trim()
  const bulanIdx = bulanId.findIndex((b) => b.toLowerCase() === bulanStr.toLowerCase())
  if (bulanIdx === -1) {
    return new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
  }
  const lastDay = new Date(tahun, bulanIdx + 1, 0).getDate()
  return `${lastDay} ${bulanId[bulanIdx]} ${tahun}`
})

const SATUAN = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan']
const BELASAN = ['Sepuluh', 'Sebelas', 'Dua Belas', 'Tiga Belas', 'Empat Belas', 'Lima Belas', 'Enam Belas', 'Tujuh Belas', 'Delapan Belas', 'Sembilan Belas']

const tigaDigit = (n) => {
  if (n === 0) return ''
  if (n < 10) return SATUAN[n]
  if (n < 20) return BELASAN[n - 10]
  if (n < 100) {
    const puluh = SATUAN[Math.floor(n / 10)]
    const sisa = n % 10
    return sisa === 0 ? `${puluh} Puluh` : `${puluh} Puluh ${SATUAN[sisa]}`
  }
  const ratus = Math.floor(n / 100) === 1 ? 'Seratus' : `${SATUAN[Math.floor(n / 100)]} Ratus`
  const sisa = n % 100
  return sisa === 0 ? ratus : `${ratus} ${tigaDigit(sisa)}`
}

const bilang = (val) => {
  const n = Math.floor(Number(val) || 0)
  if (n === 0) return 'Nol Rupiah'
  const bagian = []
  const juta = Math.floor(n / 1000000)
  const ribu = Math.floor((n % 1000000) / 1000)
  const sisa = n % 1000
  if (juta > 0) bagian.push((juta === 1 ? 'Satu Juta' : `${tigaDigit(juta)} Juta`).trim())
  if (ribu > 0) {
    if (ribu === 1) {
      bagian.push('Seribu')
    } else {
      bagian.push((tigaDigit(ribu) + ' Ribu').trim())
    }
  }
  if (sisa > 0) bagian.push(tigaDigit(sisa).trim())
  return (bagian.join(' ') + ' Rupiah').trim()
}
</script>

<style scoped>
.struk-page { display: flex; flex-direction: column; gap: 22px; }
.struk { font: 11px Arial; padding: 3px; border: 0.75px solid #6d6969; background: #fff; color: #575757; width: 100%; box-sizing: border-box; }

/* Garis pemisah dibuat tipis dan pas melebar */
.div { border: none; border-top: 0.75px solid #666; margin: 8px 0; width: 100%; }

/* Header Layout */
.header { display: flex; justify-content: space-between; align-items: center; }
.meta-left { font-size: 10px; line-height: 1.4; }
.meta-left div { display: flex; gap: 4px; }
.lbl-meta { font-weight: normal !important; width: 65px; }

.header-center { display: flex; align-items: center; gap: 12px; flex: 1; justify-content: flex-start; padding-left: 90px; }
/* Logo pakai elemen <img>, bukan background-image.
   Chrome TIDAK mencetak background image kecuali user mencentang
   "Background graphics" di dialog print -> logo hilang saat Ctrl+P.
   Dengan <img>, logo ikut tercetak tanpa-settingan tambahan. */
.logo-l, .logo-r { width: 44px; height: 44px; object-fit: contain; flex-shrink: 0; display: block; }

.t { text-align: center; font-size: 9.5px; font-weight: 700; text-transform: uppercase; }
.tm { 
  font-size: 12px; 
  text-transform: uppercase; 
  line-height: 1.0; 
  color: #353535; 
}

/* Table */
.tbl { width: 100%; border-collapse: collapse; font-size: 10px; margin-top: 9px; }
.tbl th, .tbl td { border: 1px solid #6d6969; padding: 2px 6px; text-align: center; }
.tbl th { font-weight: 700; text-transform: uppercase; background: #fdfdfd; }
.tbl td { text-transform: uppercase; }

/* Content (Rincian & TTD) */
.c { display: flex; justify-content: space-between; margin-top: 5px; align-items: flex-start; }
.rb { 
  font-weight: 700; 
  text-transform: uppercase;
  margin-bottom: 4px; 
  display: inline-block; 
  border-bottom: 1px solid #6d6969; 
  padding-bottom: 1px; 
}
.r {
  margin-left: 30px; 
}
.r-item { 
  display: flex; 
  gap: 9px; 
  line-height: 1.4; 
  font-size: 11px; 
  margin-left: 0px; 
}
.lbl-rincian { font-weight: normal !important; width: 95px; }
.val-bold { font-weight: 700; }
.tot { font-weight: 700; }
.terbilang-row { border-top: none !important; }
.i { font-style: italic; text-transform: capitalize; }

/* Blok TTD (Bendahara).
   `padding-top` 4px: blok TTD turun, ruang TTD dikecilkan.
   PENTING: tinggi blok ini harus DIBAWAH atau SAMA dengan tinggi blok
   RINCIAN BIAYA (~101px). Kalau lebih tinggi, tiap struk ikut tumbuh dan
   4 struk tidak muat satu halaman A4. */
.ttd { width: 180px; text-align: center; font-size: 11px; margin-left: auto; padding-top: 2px; }
/* Ruang kosong antara jabatan & nama. Dikecilkan dari 20px -> 4px karena
   gambar tanda tangan sekarang mengisi ruang itu sendiri. */
.sp { margin-bottom: 4px; }
/* Gambar tanda tangan bendahara.
   `width` + `height: auto` menjaga rasio (400x407 di file sumber).
   51px -> tinggi 52px. `display: block` supaya tidak ada space baseline
   yang menambah tinggi baris. `background: #fff` menyatu dengan kertas. */
.ttd-img { display: block; width: 51px; height: auto; margin: 0 auto 2px; object-fit: contain; background: #fff; }
.nb { font-weight: 700; }

/* Bottom Section (Transfer & Catatan) */
.bottom-section { display: flex; justify-content: space-between; align-items: flex-end; gap: 15px; margin-top: 2px; }
.rek { border: 1px solid #585858; padding: 4px 8px; min-width: 200px; line-height: 1.15; }

.rt { 
  font-size: 8px; 
  font-weight: 700; 
  margin-bottom: 1px; 
  text-align: left; 
}

.rek-bold { 
  font-size: 10px; 
  font-weight: 700; 
  text-align: center; 
  line-height: 1.1;
}

.nr { 
  font-family: monospace; 
  font-size: 13px; 
  font-weight: 800;  
  text-align: center; 
  line-height: 1.1;
  margin: 1px 0;
}

.an { 
  font-size: 8px; 
  margin-top: 1px; 
  font-weight: 700; 
  text-align: center; 
}

.cat { font-size: 8.5px; line-height: 1.3; flex: 1; text-align: center; align-self: flex-end; margin-bottom: 2px; }

/* Override padding BaseReportLayout untuk struk.
   Selector dipindah ke scoped block (bukan global) supaya TIDAK bocor ke
   laporan lain (daftar tagihan / form input) yang punya kebutuhan margin
   berbeda. Nilai padding tetap sama seperti sebelumnya: 17px 30px.

   Catatan: `.report-page` adalah elemen milik BaseReportLayout (parent).
   Komponen child (file ini) tidak bisa menambah class ke sana, dan
   `:deep()` juga tidak cocok karena elemen itu ROOT (bukan descendant).
   Yang bekerja: selector `.report-page.surat-page` di scoped block ini,
   karena root child mewarisi scope-id parent. */
.report-page.surat-page {
  padding: 20px 20px !important;
}
</style>

<style>
.struk { break-inside: avoid; page-break-inside: avoid; }

/* Aturan cetak struk.
   1) `@page margin` WAJIB 0. BaseReportLayout meng-imposed `margin: 15mm`
      pada @page, yang BERTAHAP dengan padding di dalam elemen. Hasilnya
      area cetak jadi sempit dan muncul ruang kosong di kiri/kanan.
   2) `break-after` TIDAK dipasang di .report-page: pemenggalan halaman
      dilakukan oleh .report-page-wrap di cetakStruk.vue. Kalau dua-duanya
      dipasang, Chrome menyisipkan halaman kosong di antar halaman. */
@media print {
  @page {
    size: A4 portrait;
    margin: 0;
  }
  .struk {
    break-inside: avoid;
    page-break-inside: avoid;
  }
}
</style>