<template>
  <div class="preview-shell" :class="orientationClass">
    <div class="preview-toolbar no-print">
      <div class="toolbar-left">
        <button class="toolbar-menu-btn no-print" @click="showSidebar = !showSidebar" title="Tampilkan / sembunyikan thumbnail">
          <span></span><span></span><span></span>
        </button>
        <h3 class="title">
          Daftar Tagihan {{ filter.bulan }} {{ filter.tahun }}
        </h3>
      </div>

      <div class="toolbar-right">
        <div class="page-indicator" v-if="pages.length > 0">
          <span>Halaman {{ activePage + 1 }} / {{ pages.length }}</span>
        </div>

        <div class="zoom-controls no-print" v-if="pages.length > 0">
          <button class="zoom-btn" @click="zoomOut" :disabled="zoomLevel <= minZoom" title="Zoom Out">
            <span>âˆ’</span>
          </button>
          <span
            class="zoom-percent active"
            @click="resetZoom"
            title="Reset zoom"
          >{{ zoomPercent }}%</span>
          <button class="zoom-btn" @click="zoomIn" :disabled="zoomLevel >= maxZoom" title="Zoom In">
            <span>+</span>
          </button>
        </div>
      </div>
    </div>

    <div v-if="errorMsg" class="alert-error no-print">{{ errorMsg }}</div>

    <div class="workspace-container">
      <div class="thumbnail-sidebar no-print" v-show="showSidebar">
        <div
          v-for="(page, i) in pages"
          :key="'thumb-' + i"
          class="thumb-wrapper"
          :class="[orientationClass, { active: activePage === i }]"
          @click="scrollToPage(i)"
        >
          <div class="thumb-paper">
            <div class="thumb-scale-container">
              <component
                v-if="shouldRenderThumb(i)"
                :is="ReportView"
                :payload="page.payload"
                :meta="page.meta"
                class="thumb-real-component"
              />
              <div v-else class="thumb-placeholder">
                <span>{{ i + 1 }}</span>
              </div>
            </div>
            <div class="thumb-overlay"></div>
          </div>
          <span class="thumb-number">{{ i + 1 }}</span>
        </div>
        <div v-if="pages.length === 0 && !isLoading" class="thumb-empty">
          Belum ada data tagihan
        </div>
      </div>

      <div class="preview-stage" ref="stageEl" @scroll.passive="onStageScroll">
        <div ref="reportRoot" class="report-root">
          <div
            v-for="(page, i) in pages"
            :key="i"
            class="report-page-wrap"
            :id="'report-page-' + i"
            :style="{
              width: pageNaturalWidth(PAGE_CONFIG) + 'px',
              zoom: pageScale(PAGE_CONFIG) !== 1 ? pageScale(PAGE_CONFIG) : undefined,
            }"
          >
            <component
              :is="ReportView"
              :id="'page-' + i"
              :payload="page.payload"
              :meta="page.meta"
            />

          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import ReportView from '@/presentations/views/app/admin/tagihan/partials/ReportCetakDaftarTagihan.vue'
import { usePemakaianAir } from '@/composables/usePemakaianAir'
import { usePdfPreview, THUMB_RENDER_BUFFER } from '@/composables/usePdfPreview'
import { PER_PAGE_ROWS, FIRST_PAGE_ROWS } from '@/utils/reportConfig'

const route = useRoute()
const { tableData, filter, refreshData, groupedData, resolveCaterLabel } = usePemakaianAir()

const PAGE_CONFIG = { paper_size: 'A4', orientation: 'portrait' }

const isLoading = ref(true)
const errorMsg = ref('')
const pages = ref([])
const reportRoot = ref(null)
const stageEl = ref(null)
const activePage = ref(0)
const showSidebar = ref(true)

const shouldRenderThumb = (i) => {
  return Math.abs(i - activePage.value) <= THUMB_RENDER_BUFFER
}

const {
  zoomLevel,
  zoomPercent,
  minZoom,
  maxZoom,
  zoomIn,
  zoomOut,
  resetZoom,
  pageNaturalWidth,
  pageNaturalHeight,
  pageScale,
  pageScaledWidth,
  pageScaledHeight,
} = usePdfPreview(stageEl, PAGE_CONFIG)

const selectedIdSet = computed(() => {
  const fromStorage = sessionStorage.getItem('cetak_print_ids_daftar')
  const raw = fromStorage || route.query.ids
  if (!raw || typeof raw !== 'string') return null
  const ids = raw.split(',').filter(Boolean).map((v) => String(v))
  if (fromStorage) sessionStorage.removeItem('cetak_print_ids_daftar')
  return ids.length > 0 ? new Set(ids) : null
})

const orientationClass = computed(() => PAGE_CONFIG.orientation)

const buildPages = () => {
  const groups = groupedData.value || {}
  const ids = selectedIdSet.value
  const entries = Object.entries(groups)
    .map(([dusun, members]) => {
      let filtered = members.filter((m) => m.jatuhTempo != null)
      if (ids) filtered = filtered.filter((m) => ids.has(String(m.id)))
      return [dusun, filtered]
    })
    .filter(([, members]) => members.length > 0)

  if (entries.length === 0) {
    pages.value = []
    return
  }

  const groupedChunks = []
  entries.forEach(([dusun, members]) => {
    const totalTagihan = members.reduce((sum, it) => sum + Number(it.tagihan || 0), 0)
    const totalPemakaian = members.reduce((sum, it) => sum + Number(it.pemakaian || 0), 0)
    const totalDibayar = members.reduce((sum, it) => sum + Number(it.paid_amount || 0), 0)
    const dusunChunks = []
    let cursor = 0
    const firstEnd = Math.min(FIRST_PAGE_ROWS, members.length)
    dusunChunks.push({
      dusun,
      items: members.slice(0, firstEnd),
      startIndex: 0,
      isLast: firstEnd >= members.length,
      totalTagihan,
      totalPemakaian,
      totalDibayar,
    })
    cursor = firstEnd
    while (cursor < members.length) {
      const endIndex = Math.min(cursor + PER_PAGE_ROWS, members.length)
      const isLast = endIndex >= members.length
      dusunChunks.push({
        dusun,
        items: members.slice(cursor, endIndex),
        startIndex: cursor,
        isLast,
        totalTagihan,
        totalPemakaian,
        totalDibayar,
      })
      cursor = endIndex
    }
    groupedChunks.push({ dusun, chunks: dusunChunks, total: members.length })
  })

  const flatChunks = groupedChunks.flatMap((g) => g.chunks)

  pages.value = flatChunks.map((chunk, i) => ({
    payload: {
      config: PAGE_CONFIG,
      dusun: chunk.dusun,
      items: chunk.items,
      filter: { ...filter.value },
      lembaga: defaultLembaga(),
      startIndex: chunk.startIndex,
      showMeta: chunk.startIndex === 0,
      isLastPage: chunk.isLast,
      totalTagihan: chunk.totalTagihan,
      totalPemakaian: chunk.totalPemakaian,
      totalDibayar: chunk.totalDibayar,
    },
    meta: {
      dusun: chunk.dusun,
      page: i + 1,
      total: flatChunks.length,
    },
  }))
}

const defaultLembaga = () => ({
  nama: '"TIRTO MULO" BUMDes BANGUN KENCANA',
  alamat: 'KALURAHAN MULO KAPANEWON WONOSARI',
  alamat_kab: 'KABUPATEN GUNUNGKIDUL',
})

const scrollToPage = (idx) => {
  const element = document.getElementById(`report-page-${idx}`)
  const stage = stageEl.value
  if (!element || !stage) return
  const stageRect = stage.getBoundingClientRect()
  const elRect = element.getBoundingClientRect()
  stage.scrollTo({
    top: elRect.top - stageRect.top + stage.scrollTop,
    behavior: 'smooth',
  })
  activePage.value = idx
}

const onStageScroll = () => {
  const stage = stageEl.value
  if (!stage) return
  const center = stage.scrollTop + stage.clientHeight / 2
  let nearest = 0
  let nearestDist = Infinity
  for (let i = 0; i < pages.value.length; i++) {
    const el = document.getElementById(`report-page-${i}`)
    if (!el) continue
    const dist = Math.abs(el.offsetTop - center)
    if (dist < nearestDist) {
      nearestDist = dist
      nearest = i
    }
  }
  if (activePage.value !== nearest) activePage.value = nearest
}

onMounted(async () => {
  try {
    if (route.query.tahun) filter.value.tahun = parseInt(route.query.tahun)
    if (route.query.bulan) filter.value.bulan = route.query.bulan
    if (route.query.teknisi) filter.value.teknisi = route.query.teknisi
    if (route.query.cater) filter.value.cater = resolveCaterLabel(route.query.cater)

    document.title = `Cetak Daftar Tagihan`

    await refreshData()
    buildPages()

    if (pages.value.length === 0) {
      errorMsg.value = 'Tidak ada data tagihan untuk periode ini.'
    }
  } catch (err) {
    errorMsg.value = err?.message || 'Gagal memuat data'
  } finally {
    isLoading.value = false
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

.preview-toolbar {
  position: sticky;
  top: 0;
  z-index: 50;
  background: #424242;
  border-bottom: 1px solid #2c2e31;
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

.toolbar-right {
  display: flex;
  align-items: center;
  gap: 14px;
}

.zoom-controls {
  display: flex;
  align-items: center;
  background: #2c2e31;
  padding: 4px;
  border-radius: 9px;
  user-select: none;
}

.zoom-btn {
  width: 30px;
  height: 30px;
  border-radius: 77px;
  color: #f8fafc;
  border: none;
  font-size: 1.2rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s ease;
}

.zoom-btn:hover {
  background: #525050;
}

.zoom-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.zoom-percent {
  min-width: 56px;
  text-align: center;
  color: #f8fafc;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  padding: 0px 10px;
  border-radius: 999px;
  background: transparent;
  transition: background 0.15s ease, color 0.15s ease;
}

.zoom-percent.active {
  background: #242424;
  color: #ffffff;
  border-radius: 0;
}

.title {
  font-size: 0.95rem;
  font-weight: 600;
  margin: 0;
  color: #f8fafc;
  letter-spacing: 0.5px;
}

.toolbar-menu-btn {
  display: inline-flex;
  flex-direction: column;
  justify-content: space-between;
  width: 28px;
  height: 22px;
  padding: 4px 4px;
  background: transparent;
  border: 1px solid #475569;
  border-radius: 4px;
  cursor: pointer;
  margin-right: 12px;
}
.toolbar-menu-btn span {
  display: block;
  width: 100%;
  height: 2px;
  background: #f8fafc;
  border-radius: 1px;
  transition: background 0.15s ease;
}
.toolbar-menu-btn:hover {
  background: #525050;
  border-color: #94a3b8;
}

.thumb-placeholder {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f1f5f9;
  color: #64748b;
  font-size: 24px;
  font-weight: 700;
}

.page-indicator {
  color: #cbd5e1;
  font-size: 0.85rem;
  background: #2c2e31;
  padding: 4px 12px;
  border-radius: 6px;
}

.workspace-container {
  display: flex;
  flex: 1;
  height: calc(100vh - 57px);
  overflow: hidden;
}

.thumbnail-sidebar {
  width: 240px;
  background: #2c2e31;
  border-right: 1px solid #979797;
  padding: 20px 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 18px;
  overflow-y: auto;
  overflow-x: hidden;
  user-select: none;
  scrollbar-width: thin;
  scrollbar-color: #5a5e63 #1f2123;
}

.thumbnail-sidebar::-webkit-scrollbar {
  width: 14px;
  height: 14px;
}

.thumbnail-sidebar::-webkit-scrollbar-track {
  background: #1f2123;
  border-radius: 8px;
  margin: 4px 0;
}

.thumbnail-sidebar::-webkit-scrollbar-thumb {
  background: #5a5e63;
  border-radius: 8px;
  border: 3px solid #1f2123;
  min-height: 40px;
}

.thumbnail-sidebar::-webkit-scrollbar-thumb:hover {
  background: #7a7e83;
}

.thumbnail-sidebar::-webkit-scrollbar-thumb:active {
  background: #38bdf8;
}

.thumb-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  cursor: pointer;
}

.thumb-wrapper.portrait .thumb-paper {
  width: 140px;
  height: 195px;
}

.thumb-wrapper.portrait .thumb-scale-container {
  width: 790px;
  height: 1120px;
  transform: scale(0.177);
}

.thumb-wrapper.landscape .thumb-paper {
  width: 195px;
  height: 140px;
}

.thumb-wrapper.landscape .thumb-scale-container {
  width: 1120px;
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
  font-size: 0.78rem;
  font-weight: 600;
  max-width: 140px;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.thumb-empty {
  color: #94a3b8;
  font-size: 0.8rem;
  padding: 24px 12px;
  text-align: center;
  font-style: italic;
}

.preview-stage {
  flex: 1 1 0;
  min-width: 0;
  height: 100%;
  overflow: auto;
  padding: 10px 0;
  background: #2c2e31;
  scroll-behavior: smooth;
  position: relative;
}

.preview-stage::-webkit-scrollbar {
  width: 14px;
  height: 14px;
}
.preview-stage::-webkit-scrollbar-track {
  background: #1f2123;
  border-radius: 8px;
  margin: 4px 0;
}
.preview-stage::-webkit-scrollbar-track:horizontal {
  margin: 0 4px;
}
.preview-stage::-webkit-scrollbar-thumb {
  background: #5a5e63;
  border-radius: 8px;
  border: 3px solid #1f2123;
  min-height: 40px;
  min-width: 40px;
}
.preview-stage::-webkit-scrollbar-thumb:hover {
  background: #7a7e83;
}
.preview-stage::-webkit-scrollbar-thumb:active {
  background: #38bdf8;
}
.preview-stage {
  scrollbar-width: thin;
  scrollbar-color: #5a5e63 #1f2123;
}

.report-root {
  display: flex;
  flex-direction: column;
  gap: 24px;
  align-items: center;
  padding: 0 16px;
  width: max-content;
  min-width: 100%;
  margin: 0 auto;
}

.report-page-wrap {
  display: block;
  position: relative;
  flex-shrink: 0;
  margin-bottom: 24px;
  overflow: hidden;
  page-break-after: always;
  break-after: page;
}

.report-page-wrap:last-child {
  margin-bottom: 0;
  page-break-after: auto;
  break-after: auto;
}

.report-page-wrap :deep(.report-page) {
  margin: 0 auto !important;
}

.report-page-wrap :deep(.report-page.surat-page.size-a4.portrait) {
  min-height: 0;
  height: 297mm;
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
  .report-root {
    gap: 0 !important;
    padding: 0 !important;
  }
  .report-page-wrap {
    zoom: 1 !important;
  }
  .report-page-wrap :deep(.report-page.surat-page.size-a4.portrait) {
    margin: 0 !important;
    width: 210mm !important;
    height: auto !important;
  }
  @page {
    size: A4 portrait;
    margin: 0;
  }
}
</style>
