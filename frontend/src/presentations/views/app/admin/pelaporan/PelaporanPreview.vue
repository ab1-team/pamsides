<template>
  <div class="preview-shell" :class="[reportConfig?.orientation || 'portrait']">
    <div class="preview-toolbar no-print">
      <div class="toolbar-left">
        <h3 class="title">
          {{ title }}
        </h3>
      </div>
      
      <div class="page-indicator" v-if="pages.length > 0">
        <span>Halaman 1 / {{ pages.length }}</span>
      </div>
    </div>

    <div v-if="errorMsg" class="alert-error">{{ errorMsg }}</div>

    <div class="workspace-container">
      
      <div class="thumbnail-sidebar no-print">
        <div 
          v-for="(page, i) in pages" 
          :key="'thumb-' + i" 
          class="thumb-wrapper"
          :class="[reportConfig?.orientation || 'portrait']"
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

      <div class="preview-stage">
        <div ref="reportRoot" class="report-root">
          <component
            v-if="pages.length > 0"
            :is="resolvedView"
            v-for="(page, i) in pages"
            :key="i"
            :id="'report-page-' + i"
            :payload="page.payload"
            :meta="page.meta"
            :ref="(el) => registerPageRef(el, i)"
          />
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, shallowRef, watch } from 'vue'
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
  'calkk': CalkView,
  'ati': AsetTetapView,
  'atb': AsetTakBerwujudView,
  'e_budgeting': EbudgetingView,
  'tutup_buku': AwalTahunView
};

const router = useRouter()
const route = useRoute()
const { isGenerating, downloadPdf, bulanNama } = usePdfReport()

const loading = ref(true)
const errorMsg = ref('')
const response = ref(null)
const pages = ref([])
const reportRoot = ref(null)
const pageRefs = shallowRef([])

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

  if (res.view_target === 'cover' || res.view_target === 'surat_pengantar') {
    pages.value = [{ payload: data, meta: baseMeta }]
  } else if (
    ['daftar_pelanggan', 'tagihan_pelanggan', 'piutang_pelanggan'].includes(res.view_target)
  ) {
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
  } else {
    pages.value = [{ payload: data, meta: baseMeta }]
  }
}

const registerPageRef = (el, idx) => {
  if (el) pageRefs.value[idx] = el.$el || el
}

const scrollToPage = (idx) => {
  const element = document.getElementById(`report-page-${idx}`)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

const collectPageElements = async () => {
  await nextTick()
  await new Promise((r) => setTimeout(r, 250))
  const els = pageRefs.value.filter(Boolean)
  return els
}

onMounted(fetchPreview)

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
  min-height: 100vh;
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
  flex: 1;
  overflow: auto; 
  
  padding: 5px 5px 5px 5px; 
  
  display: flex;
  
  justify-content: flex-start; 
  align-items: flex-start;
  background: #2c2e31;
}
.report-root {
  display: flex;
  flex-direction: column;
  gap: 24px;

  width: 100%;
  align-items: center;

  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
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