<template>
  <BaseReportLayout :lembaga="lembaga" :config="payload?.config" :no-kop="true">
    <div class="struk-page">
      <div v-for="(item, idx) in strukList" :key="idx" class="struk">
        <!-- HEADER -->
        <div class="header">
          <div class="meta-left">
            <div><span class="lbl-meta">CATER</span><span>:</span><span>{{ (payload?.cater || '-').toString().toUpperCase() }}</span></div>
            <div><span class="lbl-meta">NO URUT</span><span>:</span><span>{{ item.customer.noUrut || '-' }}</span></div>
            <div><span class="lbl-meta">PEMAKAIAN</span><span>:</span><span>{{ periodeText }}</span></div>
          </div>

          <div class="header-center">
            <div class="logo-l"></div>
            <div class="t">
              <div class="tm">STRUK TAGIHAN PEMAKAIAN AIR</div>
              <div class="tm">BADAN USAHA MILIK DESA (BUMDes)</div>
              <div class="tm">UNIT AIR</div>
            </div>
            <div class="logo-r"></div>
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
              <td>{{ item.customer.customer_code || item.customer.id }}</td>
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
            <div class="sp">Bendahara</div>
            <div class="nb">Puput Wening Ngati, S.IP</div>
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
            SELURUH PELANGGAN AIR "Tirto Mulo" WAJIB MEMATUHI SEGALA KETENTUAN MANAJEMEN PENGELOLAAN OLEH
            BUMDes BANGUN KENCANA MULO, SESUAI DENGAN PERATURAN DESA MULO NOMOR 3 TAHUN 2018.<br>
            KELUHAN PELANGGAN HUBUNGI WA 0882-1673-8479 (ISWANTO) 0878-0484-5880 (NURLI).<br>
            NB. TERLAMBAT 2 BULAN AKAN DITERBITKAN SURAT PERINGATAN, TERLAMBAT 3 BULAN AKAN DITERBITKAN SURAT PEMUTUSAN SEMENTARA.
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
  return {
    pemakaianAir: Math.max(0, tagihan - abodemen - denda),
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
const tanggalCetak = new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })

const SATUAN = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas']
const BELASAN = ['Sepuluh', 'Sebelas', 'Dua Belas', 'Tiga Belas', 'Empat Belas', 'Lima Belas', 'Enam Belas', 'Tujuh Belas', 'Delapan Belas', 'Sembilan Belas']

const tigaDigit = (n) => {
  if (n === 0) return ''
  if (n < 12) return SATUAN[n]
  if (n < 20) return BELASAN[n - 10]
  if (n < 100) {
    const puluh = SATUAN[Math.floor(n / 10)]
    const sisa = n % 10
    return sisa === 0 ? `${puluh} Puluh` : `${puluh} Puluh ${SATUAN[sisa]}`
  }
  const ratus = SATUAN[Math.floor(n / 100)]
  const sisa = n % 100
  return sisa === 0 ? `${ratus} Ratus` : `${ratus} Ratus ${tigaDigit(sisa)}`
}

const bilang = (val) => {
  const n = Math.floor(Number(val) || 0)
  if (n === 0) return 'Nol Rupiah'
  const ribu = Math.floor(n / 1000)
  const sisa = n % 1000
  const bagian = []
  if (ribu > 0) bagian.push((tigaDigit(ribu) + ' Ribu').trim())
  if (sisa > 0) bagian.push(tigaDigit(sisa).trim())
  return (bagian.join(' ') + ' Rupiah').trim()
}
</script>

<style scoped>
.struk-page { display: flex; flex-direction: column; gap: 24px; }
.struk { font: 11px Arial; padding: 3px; border: 0.75px solid #6d6969; background: #fff; color: #575757; width: 100%; box-sizing: border-box; }

/* Garis pemisah dibuat tipis dan pas melebar */
.div { border: none; border-top: 0.75px solid #666; margin: 8px 0; width: 100%; }

/* Header Layout */
.header { display: flex; justify-content: space-between; align-items: center; }
.meta-left { font-size: 10px; line-height: 1.4; }
.meta-left div { display: flex; gap: 4px; }
.lbl-meta { font-weight: normal !important; width: 65px; }

.header-center { display: flex; align-items: center; gap: 12px; flex: 1; justify-content: flex-start; padding-left: 90px; }
.logo-l { width: 35px; height: 28px; background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%230284c7"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>'); background-size: contain; background-repeat: no-repeat; flex-shrink: 0; }
.logo-r { width: 35px; height: 28px; background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23f59e0b"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>'); background-size: contain; background-repeat: no-repeat; flex-shrink: 0; }

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

.ttd { width: 180px; text-align: center; font-size: 11px; margin-left: auto; }
.sp { margin-bottom: 35px; }
.nb { font-weight: 700; text-decoration: underline; }

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
</style>

<style>
/* Override padding BaseReportLayout untuk struk (global agar menembus scoped parent) */
.report-page.surat-page {
  padding: 17px 30px !important;
}
.struk { break-inside: avoid; page-break-inside: avoid; }
</style>