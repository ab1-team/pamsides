<template>
    <div class="bukti-cell">
        <!-- Kop Surat Mengikuti Standar Base -->
        <div v-if="!noKop" class="surat-kop">
            <table class="kop-table">
                <tr>
                    <td width="70" class="logo-cell">
                        <img
                            v-if="lembaga?.logo"
                            :src="logoUrl"
                            alt="logo"
                            crossorigin="anonymous"
                        />
                        <div v-else class="kop-logo-fallback">
                            {{ initials }}
                        </div>
                    </td>

                    <td class="text-cell">
                        <div class="kop-nama-usaha">
                            {{ lembaga?.nama || 'UNIT USAHA ALIRAN AIR MASA DEPAN' }}
                        </div>
                        <div class="kop-nama-kec">
                            <b>{{ lembaga?.alamat_kab || 'MULO WONOSARI' }}</b>
                        </div>
                        
                        <div class="kop-info-sub">
                            <i>{{ lembaga?.alamat || '' }}<span v-if="lembaga?.telepon">, Telp.{{ lembaga.telepon }}</span></i>
                        </div>
                    </td>

                    <!-- Nomor & Tanggal di sebelah kanan sejajar kop -->
                    <td class="meta-cell-right">
                        <div class="meta-line">
                            <span class="mlbl">Nomor</span><span class="msep">:</span><span class="mval">{{ nomorDisplay }}</span>
                        </div>
                        <div class="meta-line">
                            <span class="mlbl">Tanggal</span><span class="msep">:</span><span class="mval">{{ formatDate(row.tgl_transaksi) }}</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="padding: 0;">
                        <hr class="kop-single-divider">
                    </td>
                </tr>
            </table>
        </div>

        <div class="bukti-content">
            <div class="bukti-title">{{ judul }}</div>

            <table class="bukti-info">
                <tr v-if="isKas">
                    <td class="lbl">{{ isKeluar ? 'Dibayar Kepada' : 'Diterima Dari' }}</td>
                    <td class="sep">:</td>
                    <td class="val">{{ dibayarKepada }}</td>
                </tr>
                <tr>
                    <td class="lbl">Keterangan</td>
                    <td class="sep">:</td>
                    <td class="val">{{ row.keterangan_transaksi || '-' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Jumlah</td>
                    <td class="sep">:</td>
                    <td class="val">{{ formatCurrency(row.saldo) }}</td>
                </tr>
                <tr>
                    <td class="lbl">Kode Akun (D/K)</td>
                    <td class="sep">:</td>
                    <td class="val">
                        <div>Debit {{ row.account_debet?.kode_akun || row.account_debet }} -
                            {{ row.account_debet?.nama_akun || row.account_debet?.nama || '' }}</div>
                        <div>Kredit {{ row.account_kredit?.kode_akun || row.account_kredit }} -
                            {{ row.account_kredit?.nama_akun || row.account_kredit?.nama || '' }}</div>
                    </td>
                </tr>
            </table>

            <div class="bukti-sign">
                <div class="sign-col">
                    <div class="sign-label">Disetujui,</div>
                    <div class="sign-name">{{ approver }}</div>
                </div>
                <div class="sign-col">
                    <div class="sign-label">Diverifikasi,</div>
                    <div class="sign-name">{{ verifier }}</div>
                </div>
                <div class="sign-col">
                    <div class="sign-label">Disiapkan Oleh :</div>
                    <div class="sign-name">{{ preparer }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    row: {
        type: Object,
        required: true
    },
    lembaga: {
        type: Object,
        default: () => ({})
    },
    config: {
        type: Object,
        default: () => ({
            paper_size: 'A4',
            orientation: 'landscape'
        })
    },
    noKop: {
        type: Boolean,
        default: false
    },
})

const formatCurrency = (amount) => {
    const n = Number(amount) || 0
    return 'Rp. ' + n.toLocaleString('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

const formatDate = (val) => {
    if (!val) return '-'
    const d = new Date(val)
    if (!Number.isNaN(d.getTime())) {
        return d.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        })
    }
    return String(val)
}

const buktiSuffix = computed(() => {
    if (judul.value === 'BUKTI KAS MASUK') return 'BKM'
    if (judul.value === 'BUKTI KAS KELUAR') return 'BKK'
    return 'BM'
})

const nomorDisplay = computed(() => {
    if (props.row?.nomor) return props.row.nomor
    const id = props.row?.id
    if (!id) return '-'
    return `${id}/${buktiSuffix.value}`
})

const isKeluar = computed(() => {
    const kredit = props.row?.account_kredit?.kode_akun || props.row?.account_kredit
    return String(kredit || '').startsWith('1.1.01') || String(kredit || '').includes('Kas Tunai')
})

const isKas = computed(() => {
    const debet = props.row?.account_debet?.kode_akun || props.row?.account_debet
    const kredit = props.row?.account_kredit?.kode_akun || props.row?.account_kredit
    const accStr = (v) => String(v || '')
    return accStr(debet).startsWith('1.1.01') || accStr(kredit).startsWith('1.1.01')
})

const judul = computed(() => {
    if (isKas.value) return isKeluar.value ? 'BUKTI KAS KELUAR' : 'BUKTI KAS MASUK'
    return 'BUKTI MEMORIAL'
})

const dibayarKepada = computed(() => props.row?.dibayar_kepada || props.row?.diterima_dari || props.row?.account_kredit?.nama_akun || props.row?.account_kredit?.nama || '-')

const approver = 'Bambang Sugeni , AKg'
const verifier = 'Rohayati, S.Akt ,'
const preparer = ''

const initials = computed(() => {
    const nama = props.lembaga?.nama || 'PAMSIDES'
    return nama.split(' ').slice(0, 2).map((s) => s[0]).join('').toUpperCase()
})

const logoUrl = computed(() => {
    const logo = props.lembaga?.logo
    if (!logo) return ''
    
    // Jika logo sudah berupa URL lengkap (http:// atau https://) atau base64
    if (logo.startsWith('http://') || logo.startsWith('https://') || logo.startsWith('data:image')) {
        return logo
    }

    // Jika path logo sudah mencakup direktori storage/ publik
    if (logo.startsWith('/storage/') || logo.startsWith('storage/')) {
        const cleanPath = logo.startsWith('/') ? logo : '/' + logo
        const baseUrl = (import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api').replace(/\/api\/?$/, '')
        return `${baseUrl}${cleanPath}`
    }

    // Penanganan path standar dari backend storage API
    const base =
        import.meta.env.VITE_API_STORAGE_URL ||
        (import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api').replace(/\/api\/?$/, '') +
        '/storage'
    
    const formattedLogo = logo.startsWith('/') ? logo : `/${logo}`
    if (formattedLogo.includes('/sop/logo/')) {
        return `${base}${formattedLogo}`
    }
    return `${base}/sop/logo${formattedLogo}`
})
</script>

<style scoped>
.bukti-cell {
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100%;
    padding: 20px 30px;
    box-sizing: border-box;
    background: #ffffff;
    font-family: Arial, Helvetica, sans-serif;
    color: #000;
    overflow: hidden;
}

.surat-kop {
    width: 100%;
    shrink: 0;
}

.kop-table {
    width: 100%;
    border-collapse: collapse;
    font-family: Arial, Helvetica, sans-serif;
    table-layout: fixed;
}

.logo-cell {
    vertical-align: top;
    width: 55px;
    padding-right: 12px;
}

.logo-cell img {
    height: 55px;
    object-fit: contain;
}

.kop-logo-fallback {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #0c79f5;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 800;
}

.text-cell {
    vertical-align: top;
    text-align: left;
}

.kop-nama-usaha {
    font-size: 10px;
    text-transform: uppercase;
    line-height: 1.2;
    font-weight: 700;
}

.kop-nama-kec {
    font-size: 10px;
    text-transform: uppercase;
    line-height: 1.2;
    margin-top: 1px;
}

.kop-info-sub {
    font-size: 10px;
    color: #000000;
    line-height: 1.2;
    margin-top: 1px;
}

.kop-single-divider {
    border: 0;
    border-top: 2px solid #888888;
    margin-top: 0px;
    margin-bottom: 10px;
    width: 10%;
width: calc(100% + 40px); 
        margin: 4px 0 4px -20px;
}

/* Styling untuk Nomor & Tanggal di sebelah kanan kop */
.meta-cell-right {
    vertical-align: top;
    text-align: right;
    width: 145px;
    font-size: 9px;
    padding-top: 10px;
}

.meta-line {
    display: flex;
    justify-content: flex-end;
    gap: 4px;
    line-height: 1.3;
}

.meta-line .mlbl {
    display: inline-block;
    min-width: 40px;
    text-align: left;
    font-weight: 300;
}

.meta-line .msep {
    font-weight: 700;
}

.meta-line .mval {
    text-align: left;
    width: 70px;
}

.bukti-content {
    display: flex;
    flex-direction: column;
    flex: 1 1 0;
    min-height: 0;
    justify-content: space-between;
}

.bukti-title {
    text-align: center;
    font-size: 15px;
    font-weight: bold;
    margin: 1px 0 3px;
    letter-spacing: 0.5px;
    shrink: 0;
}

.bukti-info {
    width: 100%;
    border-collapse: collapse;
    font-size: 9.5px;
    shrink: 0;
}

.bukti-info td {
    padding: 1px 2px;
    vertical-align: top;
}

.bukti-info .lbl {
    width: 26%;
}

.bukti-info .sep {
    width: 10px;
    text-align: center;
    font-weight: 700;
}

.bukti-info .val {
    width: auto;
}

.bukti-sign {
    margin-top: 4px;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 4px;
    padding-top: 4px;
    font-size: 8.5px;
    shrink: 0;
}

.sign-col {
    text-align: center;
}

.sign-label {
    margin-bottom: 40px;
}

.sign-name {
    font-weight: 500;
}
</style>