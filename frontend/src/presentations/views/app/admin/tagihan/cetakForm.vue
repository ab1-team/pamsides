<template>
  <div class="preview-shell" :class="orientationClass">
    <div class="preview-toolbar no-print">
      <div class="toolbar-left">
        <h3 class="title">
          Form Input {{ filter.bulan }} {{ filter.tahun }}
        </h3>
      </div>

      <div class="toolbar-right">
        <div class="page-indicator" v-if="pages.length > 0">
          <span>Halaman {{ activePage + 1 }} / {{ pages.length }}</span>
        </div>
      </div>
    </div>

    <div v-if="errorMsg" class="alert-error no-print">{{ errorMsg }}</div>

    <div class="workspace-container">
      <div class="thumbnail-sidebar no-print">
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
                :is="ReportView"
                :payload="page.payload"
                :meta="page.meta"
                class="thumb-real-component"
              />
            </div>
            <div class="thumb-overlay"></div>
          </div>
          <span class="thumb-number">{{ i + 1 }}</span>
        </div>
        <div v-if="pages.length === 0 && !isLoading" class="thumb-empty">
          Belum ada data form input
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
              width: pageNaturalWidth(i) + 'px',
              transform: pageScale(i) < 1 ? 'scale(' + pageScale(i) + ')' : undefined,
              marginBottom: pageScale(i) < 1 ? (pageNaturalHeight(i) * (pageScale(i) - 1)) + 'px' : '24px',
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
import { computed, nextTick, onMounted, onBeforeUnmount, ref, shallowRef } from 'vue'
import { useRoute } from 'vue-router'
import ReportView from '@/presentations/views/app/admin/tagihan/partials/ReportCetakForm.vue'
import { usePemakaianAir } from '@/composables/usePemakaianAir'

const route = useRoute()
const { tableData, filter, refreshData, groupedData } = usePemakaianAir()

const isLoading = ref(true)
const errorMsg = ref('')
const pages = ref([])
const reportRoot = ref(null)
const stageEl = ref(null)
const activePage = ref(0)
const stageWidth = ref(0)
let resizeObserver = null

const PAGE_CONFIG = { paper_size: 'A4', orientation: 'portrait' }
const PX_PER_MM = 96 / 25.4

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

const defaultLembaga = () => ({
  nama: '"TIRTO MULO" BUMDes BANGUN KENCANA',
  alamat: 'KALURAHAN MULO KAPANEWON WONOSARI',
  alamat_kab: 'KABUPATEN GUNUNGKIDUL',
})

const buildPages = () => {
  const groups = groupedData.value || {}
  const entries = Object.entries(groups)
    .map(([dusun, members]) => [dusun, members])
    .filter(([, members]) => members.length > 0)

  if (entries.length === 0) {
    pages.value = []
    return
  }

  pages.value = entries.map(([dusun, members], i) => ({
    payload: {
      config: PAGE_CONFIG,
      dusun,
      items: members,
      filter: { ...filter.value },
      lembaga: defaultLembaga(),
    },
    meta: {
      dusun,
      page: i + 1,
      total: entries.length,
    },
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

onMounted(async () => {
  try {
    if (route.query.tahun) filter.value.tahun = parseInt(route.query.tahun)
    if (route.query.bulan) filter.value.bulan = route.query.bulan

    document.title = `Cetak Form Input`

    await refreshData()
    buildPages()

    if (pages.value.length === 0) {
      errorMsg.value = 'Tidak ada data form input untuk periode ini.'
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
  padding-left: 8px;
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
