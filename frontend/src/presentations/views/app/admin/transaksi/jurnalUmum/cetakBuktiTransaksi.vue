<template>
  <div class="preview-shell" :class="orientationClass">
    <div class="preview-toolbar no-print">
      <div class="toolbar-left">
        <button
          class="hamburger-btn"
          @click="sidebarOpen = !sidebarOpen"
          :title="sidebarOpen ? 'Tutup sidebar' : 'Buka sidebar'"
          aria-label="Toggle sidebar"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
          </svg>
        </button>
        <h3 class="title">
          {{ title }}
        </h3>
      </div>

      <div class="toolbar-right">
        <div class="page-indicator" v-if="pages.length > 0">
          <span>Halaman {{ activePage + 1 }} / {{ pages.length }}</span>
        </div>
        <button
          class="pdf-btn"
          :disabled="isGenerating || pages.length === 0"
          @click="handleDownloadPdf"
          :title="isGenerating ? 'Membuat PDF...' : 'Unduh PDF'"
        >
          <font-awesome-icon icon="file-pdf" />
          <span>{{ isGenerating ? 'Membuat...' : 'Unduh PDF' }}</span>
        </button>
      </div>
    </div>

    <div v-if="errorMsg" class="alert-error no-print">{{ errorMsg }}</div>

    <div class="workspace-container" :class="{ 'sidebar-closed': !sidebarOpen }">
      <div class="thumbnail-sidebar no-print" v-show="sidebarOpen">
        <div
          v-for="(page, i) in pages"
          :key="'thumb-' + i"
          class="thumb-wrapper"
          :class="[orientationClass, { active: activePage === i }]"
          @click="scrollToPage(i)"
        >
          <div class="thumb-paper">
            <div class="thumb-scale-container">
              <div class="thumb-grid">
                <div
                  v-for="(row, j) in page.payload.items"
                  :key="'thumb-cell-' + i + '-' + j"
                  class="thumb-cell"
                >
                  <ReportCellView :row="row" :lembaga="page.payload.lembaga" :config="page.payload.config" />
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

      <div class="preview-stage" ref="stageEl" @scroll.passive="onStageScroll">
        <div ref="reportRoot" class="report-root">
          <div
            v-for="(page, i) in pages"
            :key="i"
            class="report-page-wrap"
            :id="'report-page-' + i"
            :ref="'pageEl-' + i"
            :data-page-index="i"
            :style="{
              width: pageNaturalWidth() + 'px',
              height: pageNaturalHeight() + 'px',
              transform: pageScale() < 1 ? 'scale(' + pageScale() + ')' : undefined,
              marginBottom: pageScale() < 1 ? (pageNaturalHeight() * (pageScale() - 1)) + 'px' : '24px',
            }"
          >
            <div
              class="page-cells"
              :id="'page-' + i"
            >
              <div
                v-for="(row, j) in page.payload.items"
                :key="'cell-' + i + '-' + j"
                class="page-cell"
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
import { usePdfReport } from '@/composables/usePdfReport'
import { sopService } from '@/services/sop.service'

const isLoading = ref(true)
const errorMsg = ref('')
const pages = ref([])
const reportRoot = ref(null)
const stageEl = ref(null)
const activePage = ref(0)
const stageWidth = ref(0)
const sidebarOpen = ref(false)
let resizeObserver = null

const PAGE_CONFIG = { paper_size: 'A4', orientation: 'landscape' }
const PX_PER_MM = 96 / 25.4
const ITEMS_PER_PAGE = 4

const pageDimsMm = () => {
  const cfg = PAGE_CONFIG
  const isLandscape = cfg.orientation === 'landscape'
  const w = (cfg.paper_size || 'A4').toUpperCase() === 'F4' ? 215 : 210
  const h = (cfg.paper_size || 'A4').toUpperCase() === 'F4' ? 330 : 297
  return isLandscape ? { w: h, h: w } : { w, h }
}

const pageNaturalWidth = () => pageDimsMm().w * PX_PER_MM
const pageNaturalHeight = () => pageDimsMm().h * PX_PER_MM

const pageScale = () => {
  if (!stageWidth.value) return 1
  const available = stageWidth.value - 48
  return Math.max(0.9, Math.min(1, available / pageNaturalWidth()))
}

const orientationClass = computed(() => PAGE_CONFIG.orientation)

const loadFromStorage = () => {
  const raw = localStorage.getItem('cetak_print_ids_bukti_trx')
  if (!raw) return null
  localStorage.removeItem('cetak_print_ids_bukti_trx')
  try {
    const parsed = JSON.parse(raw)
    if (!parsed || typeof parsed !== 'object') return null
    return parsed
  } catch {
    return null
  }
}

const loadLembaga = async () => {
  try {
    const res = await sopService.getAll()
    const data = res?.data ?? res?.payload ?? res
    return data?.lembaga || data || {}
  } catch {
    return {}
  }
}

const title = ref('Bukti Transaksi')

const buildPages = async () => {
  const stored = loadFromStorage()
  if (!stored || !Array.isArray(stored.items) || stored.items.length === 0) {
    pages.value = []
    return
  }

  title.value = stored.title || 'Bukti Transaksi'
  const lembagaData = await loadLembaga()

  const items = stored.items
  const chunks = []
  for (let i = 0; i < items.length; i += ITEMS_PER_PAGE) {
    chunks.push(items.slice(i, i + ITEMS_PER_PAGE))
  }
  pages.value = chunks.map((chunk, i) => ({
    payload: {
      config: PAGE_CONFIG,
      items: chunk,
      lembaga: lembagaData,
    },
    meta: { page: i + 1, total: chunks.length },
  }))
}

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

const { isGenerating, downloadPdf } = usePdfReport()

const handleDownloadPdf = async () => {
  const nodes = []
  for (let i = 0; i < pages.value.length; i++) {
    const wrap = document.getElementById('report-page-' + i)
    const cells = document.getElementById('page-' + i)
    if (!wrap || !cells) continue
    const clone = cells.cloneNode(true)
    const w = pageNaturalWidth()
    const h = pageNaturalHeight()
    clone.style.width = w + 'px'
    clone.style.height = h + 'px'
    clone.style.position = 'fixed'
    clone.style.top = '-99999px'
    clone.style.left = '0'
    clone.style.transform = 'none'
    document.body.appendChild(clone)
    nodes.push(clone)
  }
  if (nodes.length === 0) return
  try {
    await downloadPdf(nodes, { filename: `bukti-transaksi-${Date.now()}.pdf`, orientation: PAGE_CONFIG.orientation })
  } catch (err) {
    errorMsg.value = err?.message || 'Gagal membuat PDF'
  } finally {
    nodes.forEach((n) => n.remove())
  }
}

onMounted(async () => {
  try {
    document.title = `Cetak Bukti Transaksi`
    await buildPages()

    if (pages.value.length === 0) {
      errorMsg.value = 'Tidak ada bukti transaksi untuk dicetak. Silakan pilih dari modal terlebih dahulu.'
    }

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
  } catch (err) {
    console.error(err)
    errorMsg.value = err?.message || 'Gagal memuat data'
  } finally {
    isLoading.value = false
  }
})

onBeforeUnmount(() => {
  if (resizeObserver) resizeObserver.disconnect()
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
  gap: 12px;
  padding-left: 8px;
}

.hamburger-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: 1px solid #64748b;
  color: #cbd5e1;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.15s;
}

.hamburger-btn:hover {
  background: #2c2e31;
  color: #f8fafc;
  border-color: #94a3b8;
}

.toolbar-right {
  display: flex;
  align-items: center;
  gap: 14px;
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
  background: #2c2e31;
  padding: 4px 12px;
  border-radius: 6px;
}

.pdf-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #dc2626;
  color: #fff;
  border: none;
  padding: 8px 14px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  transition: background 0.15s;
}

.pdf-btn:hover:not(:disabled) {
  background: #b91c1c;
}

.pdf-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
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
  user-select: none;
  flex-shrink: 0;
  transition: width 0.2s ease;
}

.thumb-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  cursor: pointer;
}

.thumb-wrapper.landscape .thumb-paper {
  width: 195px;
  height: 140px;
}

.thumb-scale-container {
  position: absolute;
  top: 0;
  left: 0;
  transform-origin: top left;
  pointer-events: none;
  width: 1123px;
  height: 794px;
  transform: scale(0.174);
}

.thumb-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  grid-template-rows: repeat(2, 1fr);
  gap: 2px;
  width: 1123px;
  height: 794px;
  border: 1px solid #000;
}

.thumb-cell {
  overflow: hidden;
  background: #fff;
  border: 1px solid #000;
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
  overflow-y: auto;
  overflow-x: hidden;
  padding: 10px 0;
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
}

.report-page-wrap {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  width: 100%;
  transform-origin: top center;
  height: auto;
}

.page-cells {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  grid-template-rows: repeat(2, 1fr);
  gap: 0;
  width: 100%;
  height: 100%;
  background: #ffffff;
  border: 1px solid #000;
  overflow: hidden;
}

.page-cell {
  border: 1px solid #000;
  overflow: hidden;
  min-height: 0;
  min-width: 0;
  height: 100%;
  display: flex;
  flex-direction: column;
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
  .page-cells {
    page-break-after: always;
  }
}
</style>