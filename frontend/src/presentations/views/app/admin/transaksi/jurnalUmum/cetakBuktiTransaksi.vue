<template>
  <div class="preview-shell" :class="orientationClass">
    <div class="preview-toolbar no-print">
      <div class="toolbar-left">
        <button class="toolbar-menu-btn" @click="toggleSidebar" title="Tampilkan / sembunyikan thumbnail">
          <span></span><span></span><span></span>
        </button>
        <h3 class="title">{{ title }}</h3>
      </div>

      <div class="toolbar-right">
        <div class="zoom-controls no-print" v-if="pages.length > 0">
          <button class="zoom-btn" @click="zoomOut" title="Zoom out">−</button>
          <span class="zoom-value">{{ Math.round(zoom * 100) }}%</span>
          <button class="zoom-btn" @click="zoomIn" title="Zoom in">+</button>
          <button class="zoom-btn zoom-reset" @click="resetZoom" title="Reset">Reset</button>
        </div>
        <div class="page-indicator" v-if="pages.length > 0">
          <span>Halaman {{ activePage + 1 }} / {{ pages.length }}</span>
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
            <div class="thumb-scale-container" :style="thumbScaleStyle(i)">
              <div class="thumb-cells">
                <div
                  v-for="(row, j) in page.payload.items"
                  :key="'thumb-cell-' + i + '-' + j"
                  class="thumb-cell"
                >
                  <ReportCellView
                    :row="row"
                    :lembaga="page.payload.lembaga"
                    :config="page.payload.config"
                  />
                </div>
              </div>
            </div>
            <div class="thumb-overlay"></div>
          </div>
          <span class="thumb-number">{{ i + 1 }}</span>
        </div>
        <div v-if="pages.length === 0 && !isLoading" class="thumb-empty">
          Belum ada bukti transaksi
        </div>
      </div>

      <div class="preview-stage" ref="stageEl" @scroll.passive="onStageScroll" @wheel.ctrl.prevent="onWheelZoom">
        <div ref="reportRoot" class="report-root" :style="reportRootStyle">
          <div
            v-for="(page, i) in pages"
            :key="i"
            class="report-page-wrap"
            :id="'report-page-' + i"
            :style="{
              width: (pageNaturalWidth() * zoom) + 'px',
              height: (pageNaturalHeight() * zoom) + 'px',
              transform: zoom !== 1 ? 'scale(' + zoom + ')' : undefined,
              transformOrigin: zoom !== 1 ? 'top center' : undefined,
              marginBottom: zoom !== 1 ? (pageNaturalHeight() * (zoom - 1)) + 'px' : '24px',
            }"
          >
            <div class="cells" :id="'page-' + i" :style="{ width: '100%', height: '100%' }">
              <div
                v-for="(row, j) in page.payload.items"
                :key="'cell-' + i + '-' + j"
                class="cell"
              >
                <ReportCellView :row="row" :lembaga="page.payload.lembaga" :config="page.payload.config" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onBeforeUnmount, ref } from 'vue'
import ReportCellView from '@/presentations/views/app/admin/transaksi/jurnalUmum/partials/ReportCetakBuktiCell.vue'
import { sopService } from '@/services/sop.service'

// ===== KONFIGURASI CETAK =====
const PAGE_CONFIG = { paper_size: 'A4', orientation: 'landscape' }
const PX_PER_MM = 96 / 25.4
const ITEMS_PER_PAGE = 4
// =============================

const isLoading = ref(true)
const errorMsg = ref('')
const pages = ref([])
const reportRoot = ref(null)
const stageEl = ref(null)
const activePage = ref(0)
const stageWidth = ref(0)
const showSidebar = ref(true)
const zoom = ref(1)
const ZOOM_MIN = 0.5
const ZOOM_MAX = 3
const ZOOM_STEP = 0.1

const zoomIn = () => { zoom.value = Math.min(ZOOM_MAX, +(zoom.value + ZOOM_STEP).toFixed(2)) }
const zoomOut = () => { zoom.value = Math.max(ZOOM_MIN, +(zoom.value - ZOOM_STEP).toFixed(2)) }
const resetZoom = () => { zoom.value = 1; nextTick(() => stageEl.value?.scrollTo({ top: 0 })) }
const onWheelZoom = (e) => {
  if (!e.ctrlKey) return
  e.preventDefault()
  if (e.deltaY < 0) zoomIn(); else zoomOut()
}

const reportRootStyle = computed(() => ({
  width: (zoom.value * 100) + '%',
  minWidth: (zoom.value > 1 ? stageWidth.value * zoom.value : 0) + 'px',
}))
let resizeObserver = null

const orientationClass = computed(() => PAGE_CONFIG.orientation)
const title = ref('Cetak Bukti Transaksi')

const pageDimsMm = () => {
  const isLandscape = PAGE_CONFIG.orientation === 'landscape'
  const w = (PAGE_CONFIG.paper_size || 'A4').toUpperCase() === 'F4' ? 215 : 210
  const h = (PAGE_CONFIG.paper_size || 'A4').toUpperCase() === 'F4' ? 330 : 297
  return isLandscape ? { w: h, h: w } : { w, h }
}
const pageNaturalWidth = () => pageDimsMm().w * PX_PER_MM
const pageNaturalHeight = () => pageDimsMm().h * PX_PER_MM

const THUMB_TOTAL_W = PAGE_CONFIG.orientation === 'landscape' ? 1120 : 790
const THUMB_TOTAL_H = PAGE_CONFIG.orientation === 'landscape' ? 790 : 1120
const thumbPaper = { landscape: { w: 195, h: 140 }, portrait: { w: 140, h: 195 } }[PAGE_CONFIG.orientation]
const thumbScale = computed(() => thumbPaper.w / THUMB_TOTAL_W)
const thumbScaleStyle = () => ({
  width: THUMB_TOTAL_W + 'px',
  height: THUMB_TOTAL_H + 'px',
  transform: `scale(${thumbScale.value})`,
})

const loadStorage = () => {
  const raw = localStorage.getItem('cetak_print_ids_bukti_trx')
  if (!raw) return null
  localStorage.removeItem('cetak_print_ids_bukti_trx')
  try {
    const parsed = JSON.parse(raw)
    return parsed && typeof parsed === 'object' ? parsed : null
  } catch { return null }
}

const loadLembaga = async () => {
  try {
    const res = await sopService.getAll()
    const data = res?.data ?? res?.payload ?? res
    const lembaga = { ...data?.lembaga }
    const logo = data?.logo?.logo
    if (logo) lembaga.logo = logo
    return lembaga
  } catch { return {} }
}

const buildPages = async () => {
  const stored = loadStorage()
  if (!stored || !Array.isArray(stored.items) || stored.items.length === 0) {
    pages.value = []
    return
  }
  title.value = 'Cetak Bukti Transaksi'
  const lembaga = await loadLembaga()
  const chunks = []
  for (let i = 0; i < stored.items.length; i += ITEMS_PER_PAGE) {
    chunks.push(stored.items.slice(i, i + ITEMS_PER_PAGE))
  }
  pages.value = chunks.map((chunk, i) => ({
    payload: { items: chunk, lembaga, config: PAGE_CONFIG },
    meta: { page: i + 1, total: chunks.length },
  }))
}

const scrollToPage = (idx) => {
  const el = document.getElementById(`report-page-${idx}`)
  const stage = stageEl.value
  if (!el || !stage) return
  const stageRect = stage.getBoundingClientRect()
  const elRect = el.getBoundingClientRect()
  stage.scrollTo({ top: elRect.top - stageRect.top + stage.scrollTop, behavior: 'smooth' })
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
    if (dist < nearestDist) { nearestDist = dist; nearest = i }
  }
  if (activePage.value !== nearest) activePage.value = nearest
}

onMounted(async () => {
  try {
    document.title = 'Cetak Bukti Transaksi'
    await buildPages()
    if (pages.value.length === 0) {
      errorMsg.value = 'Tidak ada bukti transaksi untuk dicetak.'
    }
    await nextTick()
    if (stageEl.value) {
      stageWidth.value = stageEl.value.clientWidth
      resizeObserver = new ResizeObserver((entries) => {
        for (const entry of entries) stageWidth.value = entry.contentRect.width
      })
      resizeObserver.observe(stageEl.value)
    }
  } catch (err) {
    console.error(err)
    errorMsg.value = err?.message || 'Gagal memuat data'
  } finally {
    isLoading.value = false
  }
})

onBeforeUnmount(() => { if (resizeObserver) resizeObserver.disconnect() })

const toggleSidebar = () => { showSidebar.value = !showSidebar.value }
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
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
.toolbar-left { display: flex; align-items: center; padding-left: 8px; }
.toolbar-menu-btn {
  display: flex; flex-direction: column; justify-content: center; gap: 4px;
  width: 34px; height: 34px; padding: 8px;
  margin-right: 12px; background: transparent; border: none;
  border-radius: 6px; cursor: pointer;
}
.toolbar-menu-btn:hover { background: rgba(255, 255, 255, 0.12); }
.toolbar-menu-btn span {
  display: block; height: 2px; width: 100%;
  background: #f8fafc; border-radius: 2px;
}
.toolbar-right { display: flex; align-items: center; gap: 14px; }
.title { font-size: 0.95rem; font-weight: 600; margin: 0; color: #f8fafc; letter-spacing: 0.5px; }
.page-indicator {
  color: #cbd5e1; font-size: 0.85rem; background: #2c2e31;
  padding: 4px 12px; border-radius: 6px;
}

.zoom-controls {
  display: flex; align-items: center; gap: 6px;
  background: #2c2e31; padding: 3px 8px; border-radius: 6px;
}
.zoom-btn {
  width: 26px; height: 26px; padding: 0;
  background: #424242; color: #f8fafc;
  border: 1px solid #555; border-radius: 4px;
  font-size: 0.9rem; font-weight: 700; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
}
.zoom-btn:hover { background: #525252; }
.zoom-reset { width: auto; padding: 0 10px; font-size: 0.75rem; font-weight: 600; }
.zoom-value {
  color: #f8fafc; font-size: 0.8rem; min-width: 42px; text-align: center;
  font-variant-numeric: tabular-nums;
}

.workspace-container { display: flex; flex: 1; height: calc(100vh - 57px); overflow: hidden; }

.thumbnail-sidebar {
  width: 240px; background: #2c2e31; border-right: 1px solid #979797;
  padding: 20px 0; display: flex; flex-direction: column; align-items: center;
  gap: 18px; overflow-y: auto; user-select: none; shrink: 0;
}
.thumb-wrapper { display: flex; flex-direction: column; align-items: center; gap: 6px; cursor: pointer; }
.thumb-wrapper.landscape .thumb-paper { width: 195px; height: 140px; }
.thumb-wrapper.portrait .thumb-paper { width: 140px; height: 195px; }
.thumb-scale-container {
  position: absolute; top: 0; left: 0;
  transform-origin: top left; pointer-events: none;
}
.thumb-paper {
  position: relative; background: #ffffff; border: 2px solid #475569;
  border-radius: 3px; box-shadow: 0 4px 10px rgba(0,0,0,0.4); overflow: hidden;
}
.thumb-cells {
  width: 100%; height: 100%;
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  grid-template-rows: repeat(2, 1fr);
  row-gap: 2mm;
  column-gap: 2mm;
  padding: 13mm;
  background: #ffffff;
  box-sizing: border-box;
}
.thumb-cell {
  border: 2px solid #727070;
  overflow: hidden;
  display: flex; flex-direction: column;
  box-sizing: border-box;
}
.thumb-wrapper:hover .thumb-paper { border-color: #38bdf8; }
.thumb-wrapper.active .thumb-paper { border-color: #38bdf8; box-shadow: 0 0 0 2px #38bdf8, 0 4px 10px rgba(0,0,0,0.4); }
.thumb-wrapper.active .thumb-number { color: #38bdf8; }
.thumb-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 10; background: transparent; }
.thumb-number { color: #94a3b8; font-size: 0.78rem; font-weight: 600; max-width: 140px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.thumb-empty { color: #94a3b8; font-size: 0.8rem; padding: 24px 12px; text-align: center; font-style: italic; }

.preview-stage {
  flex: 1 1 0; min-width: 0; height: 100%;
  overflow: auto; padding: 10px 0;
  display: flex; flex-direction: column; align-items: center;
  background: #2c2e31; scroll-behavior: smooth;
}
.report-root { display: flex; flex-direction: column; gap: 24px; align-items: center; transition: width 0.15s ease; box-sizing: border-box; }
.report-page-wrap {
  display: flex; justify-content: center; align-items: flex-start;
  width: 100%; transform-origin: top center;
}

.cells {
  width: 100%; 
  height: 100%;
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  grid-template-rows: repeat(2, 1fr);
  row-gap: 2mm;        
  column-gap: 2mm;    
  padding: 13mm;
  background: #ffffff;
  border: 0;
  overflow: hidden;
  box-sizing: border-box;
}

.cell {
  border: 2px solid #727070;
  overflow: hidden;
  display: flex; flex-direction: column;
  box-sizing: border-box;
}

.alert-error {
  background: #fee2e2; color: #991b1b; border-radius: 12px;
  padding: 10px 14px; margin: 16px; font-size: 0.9rem;
}

@media print {
  .no-print { display: none !important; }
  .preview-shell { background: #ffffff; padding: 0; }
  .workspace-container { height: auto; overflow: visible; }
  .preview-stage { padding: 0; overflow: visible; }
  .cells { page-break-after: always; }
}
</style>
