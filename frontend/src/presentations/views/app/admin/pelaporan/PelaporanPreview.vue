<template>
  <div class="preview-shell" :class="[reportConfig?.orientation || 'portrait']">
    <div class="preview-toolbar no-print">
      <div class="toolbar-left">
        <button class="toolbar-menu-btn" @click="showSidebar = !showSidebar" title="Tampilkan / sembunyikan thumbnail">
          <span></span><span></span><span></span>
        </button>
        <h3 class="title">
          {{ title }}
        </h3>
      </div>

      <div class="page-indicator" v-if="pages.length > 0">
        <span>Halaman {{ activePage + 1 }} / {{ pages.length }}</span>
      </div>
    </div>

    <div v-if="errorMsg" class="alert-error">{{ errorMsg }}</div>

    <div class="workspace-container">

      <div class="thumbnail-sidebar no-print" v-show="showSidebar">
        <div
          v-for="(page, i) in pages"
          :key="'thumb-' + i"
          class="thumb-wrapper"
          :class="[reportConfig?.orientation || 'portrait', { active: activePage === i }]"
          @click="scrollToPage(i)"
        >
          <div class="thumb-paper">
            <div class="thumb-scale-container">
              <component
                :is="resolvedView"
                :payload="page.payload"
                :meta="page.meta"
                class="thumb-real-component"
              />
            </div>
            <div class="thumb-overlay"></div>
          </div>
          <span class="thumb-number">{{ i + 1 }}</span>
        </div>
      </div>

      <div class="preview-stage" ref="stageEl" @scroll.passive="onStageScroll">
        <div ref="reportRoot" class="report-root">
          <div
            v-if="pages.length > 0"
            v-for="(page, i) in pages"
            :key="i"
            class="report-page-wrap"
            :style="{
              width: pageNaturalWidth(i) + 'px',
              transform: pageScale(i) < 1 ? 'scale(' + pageScale(i) + ')' : undefined,
              marginBottom: pageScale(i) < 1 ? (pageNaturalHeight(i) * (pageScale(i) - 1)) + 'px' : '24px',
            }"
          >
            <component
              :is="resolvedView"
              :id="'report-page-' + i"
              :payload="page.payload"
              :meta="page.meta"
              :ref="(el) => registerPageRef(el, i)"
            />
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, onBeforeUnmount, ref, shallowRef, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import pelaporanService from '@/services/pelaporan.service.js'
import { usePdfReport } from '@/composables/usePdfReport.js'

// Kelompok Utama
import CoverView from '@/presentations/views/app/admin/pelaporan/views/ReportCover.vue'
import SuratPengantarView from '@/presentations/views/app/admin/pelaporan/views/ReportSuratPengantar.vue'

// Kelompok Operasional
import DaftarPelangganView from '@/presentations/views/app/admin/pelaporan/views/ReportDaftarPelanggan.vue'
import DaftarTagihanView from '@/presentations/views/app/admin/pelaporan/views/ReportDaftarTagihan.vue'
import DaftarPiutangView from '@/presentations/views/app/admin/pelaporan/views/ReportDaftarPiutang.vue'
import PiutangKomisiView from '@/presentations/views/app/admin/pelaporan/views/ReportPiutangKomisi.vue' // ID 37

// Kelompok Akuntansi
import JurnalTransaksiView from '@/presentations/views/app/admin/pelaporan/views/ReportJurnalTransaksi.vue'
import BukuBesarView from '@/presentations/views/app/admin/pelaporan/views/ReportBukuBesar.vue'
import NeracaSaldoView from '@/presentations/views/app/admin/pelaporan/views/ReportNeracaSaldo.vue'
import NeracaView from '@/presentations/views/app/admin/pelaporan/views/ReportNeraca.vue'
import LabaRugiView from '@/presentations/views/app/admin/pelaporan/views/ReportLabaRugi.vue'
import ArusKasView from '@/presentations/views/app/admin/pelaporan/views/ReportArusKas.vue'
import PerubahanModalView from '@/presentations/views/app/admin/pelaporan/views/ReportPerubahanModal.vue'
import CalkView from '@/presentations/views/app/admin/pelaporan/views/ReportCalk.vue'

// Kelompok Aset & Budgeting
import AsetTetapView from '@/presentations/views/app/admin/pelaporan/views/ReportAsetTetap.vue'
import AsetTakBerwujudView from '@/presentations/views/app/admin/pelaporan/views/ReportAsetTakBerwujud.vue'
import EbudgetingView from '@/presentations/views/app/admin/pelaporan/views/ReportEbudgeting.vue'
import AwalTahunView from '@/presentations/views/app/admin/pelaporan/views/ReportAwalTahun.vue'
import TutupBukuAlokasiLabaView from '@/presentations/views/app/admin/pelaporan/views/ReportTutupBukuAlokasiLaba.vue'
import TutupBukuNeracaView from '@/presentations/views/app/admin/pelaporan/views/ReportTutupBukuNeraca.vue'
import TutupBukuLabaRugiView from '@/presentations/views/app/admin/pelaporan/views/ReportTutupBukuLabaRugi.vue'
import TutupBukuJurnalView from '@/presentations/views/app/admin/pelaporan/views/ReportTutupBukuJurnal.vue'
import TutupBukuCalkView from '@/presentations/views/app/admin/pelaporan/views/ReportTutupBukuCalk.vue'





const reportComponents = {
  'cover': CoverView,
  'surat_pengantar': SuratPengantarView,
  'daftar_pelanggan': DaftarPelangganView,
  'tagihan_pelanggan': DaftarTagihanView,
  'piutang_pelanggan': DaftarPiutangView,
  'piutang_komisi': PiutangKomisiView,
  'jurnal_transaksi': JurnalTransaksiView,
  'buku_besar': BukuBesarView,
  'neraca_saldo': NeracaSaldoView,
  'neraca': NeracaView,
  'laba_rugi': LabaRugiView,
  'arus_kas': ArusKasView,
  'perubahan_modal': PerubahanModalView,
  'calk': CalkView,
  'calkk': CalkView,
  'ati': AsetTetapView,
  'atb': AsetTakBerwujudView,
  'e_budgeting': EbudgetingView,
  'tutup_buku': AwalTahunView,
  'tutup_buku_alokasi_laba': TutupBukuAlokasiLabaView,
  'tutup_buku_neraca': TutupBukuNeracaView,
  'tutup_buku_laba_rugi': TutupBukuLabaRugiView,
  'tutup_buku_jurnal': TutupBukuJurnalView,
  'tutup_buku_calk': TutupBukuCalkView
};

const router = useRouter()
const route = useRoute()
const { isGenerating, downloadPdf, bulanNama } = usePdfReport()

const loading = ref(true)
const errorMsg = ref('')
const showSidebar = ref(true)
const response = ref(null)
const pages = ref([])
const reportRoot = ref(null)
const stageEl = ref(null)
const pageRefs = shallowRef([])
const activePage = ref(0)
const stageWidth = ref(0)
let resizeObserver = null

const pageDimsMm = (page) => {
  const cfg = page?.payload?.config || page?.config || {}
  const isLandscape = cfg.orientation === 'landscape'
  const w = (cfg.paper_size || 'A4').toUpperCase() === 'F4' ? 215 : 210
  const h = (cfg.paper_size || 'A4').toUpperCase() === 'F4' ? 330 : 297
  return isLandscape ? { w: h, h: w } : { w, h }
}

const PX_PER_MM = 96 / 25.4

const pageNaturalWidth = (i) => {
  const p = pages.value[i]
  if (!p) return 0
  return pageDimsMm(p).w * PX_PER_MM
}

const pageNaturalHeight = (i) => {
  const p = pages.value[i]
  if (!p) return 0
  return pageDimsMm(p).h * PX_PER_MM
}

const pageScale = (i) => {
  if (!stageWidth.value) return 1
  const natural = pageNaturalWidth(i)
  if (!natural) return 1
  const available = stageWidth.value - 48
  
  // Jangan biarkan lebih kecil dari 0.8 (80%)
  // Jika rasio lebih kecil dari 0.8, maka biarkan 0.8 (pengguna bisa scroll)
  return Math.max(0.9, Math.min(1, available / natural))
}

// MODIFIKASI: Menyimpan backup path favicon utama aplikasi Anda
const originalFavicon = '/favicon.ico'

const meta = computed(() => response.value?.meta || {})
const viewTarget = computed(() => response.value?.view_target || '')
const resolvedView = computed(() => reportComponents[viewTarget.value] || CoverView)
// config tetap
const reportConfig = computed(() => response.value?.payload?.config || {})

// title tetap (tidak dihapus)
const title = computed(() => {
  return response.value?.title || ''
})

const fetchPreview = async () => {
  loading.value = true
  errorMsg.value = ''
  try {
    const payload = {
      tahun: route.query.tahun || '',
      bulan: route.query.bulan || '',
      tanggal: route.query.tanggal || '',
      nama_laporan: route.query.nama_laporan || '',
      nama_sub_laporan: route.query.nama_sub_laporan || '',
    }

    const res = await pelaporanService.getPreview(payload)

    if (!res?.success) {
      throw new Error(res?.message || 'Gagal memuat preview')
    }

    response.value = res
    buildPages(res)

    // FIX TAMBAHAN: SET TITLE TAB BROWSER
    document.title = res?.title || 'LAPORAN'

    // MODIFIKASI: Ubah favicon menjadi transparan/kosong saat data berhasil dimuat
    const faviconLink = document.querySelector("link[rel~='icon']")
    if (faviconLink) {
      faviconLink.href = 'data:image/x-icon;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQEAYAAAB1RHTAAAABGdBTUEAALGPC/xhBQAAAAlwSFlzAAAOwgAADsIBFShKgAAAABl0RVh0U29mdHdhcmUAcGFpbnQubmV0IDQuMC4yMTnTabrAAAAnSURBVDhPY2AgDTAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwYAAAAE0AZfsc4sYAAAAASUVORK5CYII='
    }

  } catch (err) {
    errorMsg.value = err.message || 'Terjadi kesalahan'
  } finally {
    loading.value = false
  }
}

const buildPages = (res) => {
  const data = res?.payload || {}
  const baseMeta = res?.meta || meta.value
  const baseConfig = data?.config || {}
  const baseLembaga = data?.lembaga || null

  if (res.view_target === 'cover' || res.view_target === 'surat_pengantar') {
    pages.value = [{ payload: data, meta: baseMeta }]
  } 
  else if (res.view_target === 'calk' || res.view_target === 'calkk' || res.view_target === 'tutup_buku_calk') {
    const rows = Array.isArray(data?.rows) ? data.rows : []
    const calkContent = data?.calk_content || ''
    const totalSaldo = data?.total_saldo || 0
    const chunkSize = 35

    if (rows.length === 0) {
      pages.value = [{ payload: { ...data, config: baseConfig, rows: [], pageInfo: { current: 1, total: 1 }, isFirstPage: true, isLastPage: true }, meta: baseMeta }]
    } else {
      pages.value = []
      const totalChunks = Math.ceil(rows.length / chunkSize)
      for (let i = 0; i < rows.length; i += chunkSize) {
        const chunkIndex = Math.floor(i / chunkSize)
        const isFirst = chunkIndex === 0
        const isLast = chunkIndex === totalChunks - 1
        pages.value.push({
          payload: {
            ...data,
            config: baseConfig,
            rows: rows.slice(i, i + chunkSize),
            calk_content: calkContent,
            total_saldo: totalSaldo,
            pageInfo: { current: chunkIndex + 1, total: totalChunks },
            isFirstPage: isFirst,
            isLastPage: isLast,
            showTableHeader: isFirst,
          },
          meta: baseMeta,
        })
      }
    }
  } 
  else if (['daftar_pelanggan', 'tagihan_pelanggan', 'piutang_pelanggan'].includes(res.view_target)) {
    const items = Array.isArray(data) ? data : data?.items || []
    const chunkSize = 25

    if (items.length === 0) {
      pages.value = [{ payload: { config: baseConfig, items: [] }, meta: baseMeta }]
    } else {
      pages.value = []
      for (let i = 0; i < items.length; i += chunkSize) {
        pages.value.push({
          payload: {
            config: baseConfig,
            items: items.slice(i, i + chunkSize)
          },
          meta: baseMeta,
        })
      }
    }
  } 
  else if (res.view_target === 'neraca_saldo') {
    const items = Array.isArray(data?.items) ? data.items : []
    const summary = data?.summary || {}
    const chunkSize = 30

    if (items.length === 0) {
      pages.value = [{ payload: { ...data, config: baseConfig, items: [] }, meta: baseMeta }]
    } else {
      pages.value = []
      for (let i = 0; i < items.length; i += chunkSize) {
        const isLastChunk = i + chunkSize >= items.length
        pages.value.push({
          payload: {
            ...data,
            config: baseConfig,
            items: items.slice(i, i + chunkSize),
            summary: isLastChunk ? summary : null,
          },
          meta: baseMeta,
        })
      }
    }
  } 
  else if (res.view_target === 'buku_besar') {
    const transactions = Array.isArray(data?.transactions) ? data.transactions : []
    const dataChunkSize = 20

    if (transactions.length === 0) {
      pages.value = [{ payload: { ...data, config: baseConfig, transactions: [], showHeader: true, showFooter: true }, meta: baseMeta }]
    } else {
      pages.value = []
      const totalChunks = Math.ceil(transactions.length / dataChunkSize)
      for (let i = 0; i < transactions.length; i += dataChunkSize) {
        const chunkIndex = Math.floor(i / dataChunkSize)
        const chunk = transactions.slice(i, i + dataChunkSize)
        const isLast = chunkIndex === totalChunks - 1
        pages.value.push({
          payload: {
            ...data,
            config: baseConfig,
            transactions: chunk,
            showHeader: true,
            showFooter: isLast,
            pageInfo: { current: chunkIndex + 1, total: totalChunks }
          },
          meta: baseMeta,
        })
      }
    }
  } 
  else if (res.view_target === 'e_budgeting') {
    const items = Array.isArray(data?.items) ? data.items : [];
    const chunkSize = 25; // Sesuaikan jumlah baris per halaman agar tidak terpotong

    if (items.length === 0) {
      pages.value = [{ payload: { ...data, config: baseConfig, items: [] }, meta: baseMeta }];
    } else {
      pages.value = [];
      for (let i = 0; i < items.length; i += chunkSize) {
        pages.value.push({
          payload: {
            ...data,
            config: baseConfig,
            items: items.slice(i, i + chunkSize), // Potong data per halaman
          },
          meta: baseMeta,
        });
      }
    }
  }
  else if (res.view_target === 'laba_rugi') {
    const rawGroups = Array.isArray(data?.groups) ? data.groups : []
    
    // 1. Bongkar semua grup & items menjadi satu array baris flat
    let flatRows = []
    rawGroups.forEach((group) => {
      // Masukkan baris judul grupnya dulu
      flatRows.push({
        isHeader: true,
        type: group.type,
        label: group.label
      })
      
      // Masukkan anak-anak akun di dalamnya jika ada
      if (Array.isArray(group.items)) {
        group.items.forEach((item) => {
          flatRows.push({
            isHeader: false,
            ...item
          })
        })
      }
    })

    // 2. Tentukan ukuran baris per halaman (Sesuaikan angka 25 ini jika kurang penuh/kebanyakan)
    const chunkSize = 40 

    if (flatRows.length === 0) {
      pages.value = [{ payload: { ...data, config: baseConfig, flatRows: [] }, meta: baseMeta }]
    } else {
      pages.value = []
      for (let i = 0; i < flatRows.length; i += chunkSize) {
        pages.value.push({
          payload: {
            ...data,
            config: baseConfig,
            // Kirim potongan baris flat untuk halaman ini
            flatRows: flatRows.slice(i, i + chunkSize) 
          },
          meta: baseMeta,
        })
      }
    }
  }
  else if (res.view_target === 'jurnal_transaksi') {
    const transactions = Array.isArray(data?.transactions) ? data.transactions : [];
    const chunkSize = 20; // Sesuaikan jumlah baris per halaman

    if (transactions.length === 0) {
      pages.value = [{ 
        payload: { ...data, config: baseConfig, transactions: [], showHeader: true, showFooter: true }, 
        meta: baseMeta 
      }];
    } else {
      pages.value = [];
      const totalChunks = Math.ceil(transactions.length / chunkSize);
      
      for (let i = 0; i < transactions.length; i += chunkSize) {
        const chunkIndex = Math.floor(i / chunkSize);
        const isLast = chunkIndex === totalChunks - 1;
        
        pages.value.push({
          payload: {
            ...data,
            config: baseConfig,
            transactions: transactions.slice(i, i + chunkSize),
            showHeader: true,
            showFooter: isLast, // Footer (Total/Tanda tangan) hanya muncul di halaman terakhir
            pageInfo: { current: chunkIndex + 1, total: totalChunks }
          },
          meta: baseMeta,
        });
      }
    }
  }
  else if (res.view_target === 'piutang_komisi') {
    const items = Array.isArray(data?.items) ? data.items : [];
    const chunkSize = 25; // Sesuaikan dengan tinggi tabel Anda

    if (items.length === 0) {
      pages.value = [{ 
        payload: { ...data, config: baseConfig, items: [] }, 
        meta: baseMeta 
      }];
    } else {
      pages.value = [];
      for (let i = 0; i < items.length; i += chunkSize) {
        pages.value.push({
          payload: {
            ...data,
            config: baseConfig,
            items: items.slice(i, i + chunkSize),
            // Opsional: tambahkan flag jika perlu membedakan halaman awal/akhir
            isFirstPage: i === 0,
            isLastPage: (i + chunkSize) >= items.length
          },
          meta: baseMeta,
        });
      }
    }
  }
  else if (res.view_target === 'ati' || res.view_target === 'atb') {
    const groups = Array.isArray(data?.items) ? data.items : []

    if (groups.length === 0) {
      pages.value = [{ payload: { ...data, config: baseConfig, items: [] }, meta: baseMeta }]
    } else {
      pages.value = []
      groups.forEach((group, idx) => {
        pages.value.push({
          payload: {
            ...data,
            config: baseConfig,
            items: [group],
            pageInfo: { current: idx + 1, total: groups.length },
            isFirstPage: idx === 0,
            isLastPage: idx === groups.length - 1,
          },
          meta: baseMeta,
        })
      })
    }
  }
  else {
    pages.value = [{ payload: data, meta: baseMeta }]
  }

  pages.value = pages.value.map((p) => ({
    ...p,
    payload: {
      ...p.payload,
      lembaga: p.payload?.lembaga || baseLembaga,
    },
  }))
}

const registerPageRef = (el, idx) => {
  if (!el) return
  const node = el.$el || el
  const cfg = pages.value[idx]?.payload?.config || pages.value[idx]?.config || {}
  node._pdfConfig = cfg
  pageRefs.value[idx] = node
}

const scrollToPage = (idx) => {
  const element = document.getElementById(`report-page-${idx}`)
  const stage = stageEl.value || document.querySelector('.preview-stage')
  if (!element) return
  if (stage) {
    const stageRect = stage.getBoundingClientRect()
    const elRect = element.getBoundingClientRect()
    const offset = elRect.top - stageRect.top + stage.scrollTop
    stage.scrollTo({ top: offset, behavior: 'smooth' })
    activePage.value = idx
    nextTick(() => scrollThumbIntoView(idx))
  } else {
    element.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

const scrollThumbIntoView = (idx) => {
  const thumb = document.querySelectorAll('.thumb-wrapper')[idx]
  const sidebar = document.querySelector('.thumbnail-sidebar')
  if (!thumb || !sidebar) return
  const tRect = thumb.getBoundingClientRect()
  const sRect = sidebar.getBoundingClientRect()
  if (tRect.top < sRect.top || tRect.bottom > sRect.bottom) {
    sidebar.scrollTo({ top: thumb.offsetTop - 24, behavior: 'smooth' })
  }
}

const onStageScroll = () => {
  const stage = stageEl.value
  if (!stage) return
  const stageCenter = stage.scrollTop + stage.clientHeight / 2
  let nearest = 0
  let nearestDist = Infinity
  for (let i = 0; i < pages.value.length; i++) {
    const el = document.getElementById(`report-page-${i}`)
    if (!el) continue
    const dist = Math.abs(el.offsetTop - stageCenter)
    if (dist < nearestDist) {
      nearestDist = dist
      nearest = i
    }
  }
  if (activePage.value !== nearest) {
    activePage.value = nearest
    nextTick(() => scrollThumbIntoView(nearest))
  }
}

const collectPageElements = async () => {
  await nextTick()
  await new Promise((r) => setTimeout(r, 250))
  const els = pageRefs.value.filter(Boolean)
  return els
}

onMounted(async () => {
  await fetchPreview()
  await nextTick()
  if (stageEl.value) {
    stageWidth.value = stageEl.value.clientWidth
    resizeObserver = new ResizeObserver((entries) => {
      for (const entry of entries) {
        stageWidth.value = entry.contentRect.width
      }
    })
    resizeObserver.observe(stageEl.value)
  }
})

onBeforeUnmount(() => {
  if (resizeObserver) resizeObserver.disconnect()
})

onUnmounted(() => {
  const faviconLink = document.querySelector("link[rel~='icon']")
  if (faviconLink) {
    faviconLink.href = originalFavicon
  }
})
</script>

<style scoped>
.preview-shell {
  background: #312c2c; 
  height: 100vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

/* TOP TOOLBAR */
.preview-toolbar {
  position: sticky;
  top: 0;
  z-index: 50;
  background: #424242;
  border-bottom: 1px solid #424242; 
  padding: 12px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.toolbar-left {
  display: flex;
  align-items: center;
  padding-left: 8px; 
}

.toolbar-menu-btn {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 4px;
  width: 34px;
  height: 34px;
  padding: 8px;
  margin-right: 12px;
  background: transparent;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.toolbar-menu-btn:hover {
  background: rgba(255, 255, 255, 0.12);
}

.toolbar-menu-btn span {
  display: block;
  height: 2px;
  width: 100%;
  background: #f8fafc;
  border-radius: 2px;
}

.title {
  font-size: 0.95rem;
  font-weight: 600;
  margin: 0;
  color: #f8fafc;
  letter-spacing: 0.5px;
}

.page-indicator {
  color: #cbd5e1;
  font-size: 0.85rem;
  background: #424242;
  padding: 4px 12px;
  border-radius: 6px;
}

/* WORKSPACE */
.workspace-container {
  display: flex;
  flex: 1;
  height: calc(100vh - 57px);
  overflow: hidden;
}
.thumbnail-sidebar {
  width: 300px; 
  background: #2c2e31;
  border-right: 1px solid #979797;
  padding: 20px 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
  overflow-y: auto;
  user-select: none;
}

.thumb-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.thumb-wrapper.portrait .thumb-paper {
  width: 140px; 
  height: 195px; 
}
.thumb-wrapper.portrait .thumb-scale-container {
  width: 790px;  /* Sesuai A4/F4 lebar portrait */
  height: 1120px;
  transform: scale(0.177); 
}

.thumb-wrapper.landscape .thumb-paper {
  width: 195px; /* Dibalik mendatar */
  height: 140px; 
}
.thumb-wrapper.landscape .thumb-scale-container {
  width: 1120px; /* Sesuai A4/F4 lebar landscape */
  height: 790px;
  transform: scale(0.174); 
}

.thumb-paper {
  position: relative;
  background: #ffffff;
  border: 2px solid #475569;
  border-radius: 3px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.4);
  overflow: hidden; 
}

.thumb-wrapper:hover .thumb-paper {
  border-color: #38bdf8;
}

.thumb-wrapper.active .thumb-paper {
  border-color: #38bdf8;
  box-shadow: 0 0 0 2px #38bdf8, 0 4px 10px rgba(0, 0, 0, 0.4);
}

.thumb-wrapper.active .thumb-number {
  color: #38bdf8;
}

.thumb-scale-container {
  position: absolute;
  top: 0;
  left: 0;
  transform-origin: top left;
  pointer-events: none; 
}

.thumb-real-component {
  width: 100% !important;
  height: 100% !important;
  background: #ffffff !important;
  overflow: hidden !important;
}

.thumb-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 10;
  background: transparent;
}

.thumb-number {
  color: #94a3b8;
  font-size: 0.8rem;
  font-weight: 600;
}

.preview-stage {
  flex: 1 1 0;
  min-width: 0;
  height: 100%;
  overflow-y: auto;
  overflow-x: hidden;

  padding: 10px 0px 10px;

  display: flex;
  flex-direction: column;
  align-items: center;
  background: #2c2e31;
  scroll-behavior: smooth;
}
.report-root {
  display: flex;
  flex-direction: column;
  gap: 24px;

  width: 100%;
  align-items: center;

  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
}

.report-page-wrap {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  width: 100%;
  transform-origin: top center;
}

.report-page-wrap :deep(.report-page) {
  margin: 0 auto !important;
}
.alert-error {
  background: #fee2e2;
  color: #991b1b;
  border-radius: 12px;
  padding: 10px 14px;
  margin: 16px;
  font-size: 0.9rem;
}

@media print {
  .no-print {
    display: none !important;
  }
  .preview-shell {
    background: #ffffff;
    padding: 0;
  }
  .workspace-container {
    height: auto;
    overflow: visible;
  }
  .preview-stage {
    padding: 0;
    overflow: visible;
  }
}
</style>