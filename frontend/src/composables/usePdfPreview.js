import { computed, nextTick, onMounted, onScopeDispose, ref } from 'vue'

export const THUMB_RENDER_BUFFER = 3
export const PAGE_RENDER_BUFFER = 2
const PX_PER_MM = 96 / 25.4

const resolveConfig = (cfg, fallback) => cfg || fallback

const pageDimsMm = (cfg, fallback) => {
  const orientation = (cfg?.orientation || fallback?.orientation || 'portrait')
  const paper = (cfg?.paper_size || fallback?.paper_size || 'A4').toUpperCase()
  const isLandscape = orientation === 'landscape'
  const w = paper === 'F4' ? 215 : 210
  const h = paper === 'F4' ? 330 : 297
  return isLandscape ? { w: h, h: w } : { w, h }
}

export function usePdfPreview(stageElRef, defaultPageConfig = { paper_size: 'A4', orientation: 'portrait' }) {
  const stageWidth = ref(0)
  const zoomLevel = ref(1)
  const minZoom = 0.5
  const maxZoom = 3
  const zoomStep = 0.1
  let resizeObserver = null

  const pageNaturalWidth = (cfg) => pageDimsMm(resolveConfig(cfg, defaultPageConfig), defaultPageConfig).w * PX_PER_MM
  const pageNaturalHeight = (cfg) => pageDimsMm(resolveConfig(cfg, defaultPageConfig), defaultPageConfig).h * PX_PER_MM

  const pageScale = (cfg) => {
    if (!stageWidth.value) return null
    const natural = pageNaturalWidth(cfg)
    if (!natural) return null
    const available = stageWidth.value - 48
    const baseFit = Math.max(0.9, Math.min(1, available / natural))
    return baseFit * zoomLevel.value
  }

  const pageScaledWidth = (cfg) => {
    const scale = pageScale(cfg)
    return scale ? pageNaturalWidth(cfg) * scale : pageNaturalWidth(cfg)
  }

  const pageScaledHeight = (cfg) => {
    const scale = pageScale(cfg)
    return scale ? pageNaturalHeight(cfg) * scale : pageNaturalHeight(cfg)
  }

  const pageScaleStyle = (cfg) => {
    const scale = pageScale(cfg)
    if (!scale) {
      return { width: pageNaturalWidth(cfg) + 'px', height: pageNaturalHeight(cfg) + 'px' }
    }
    return {
      width: pageScaledWidth(cfg) + 'px',
      height: pageScaledHeight(cfg) + 'px',
      transform: `scale(${scale})`,
      transformOrigin: 'top center',
      marginBottom: ((scale - 1) * pageNaturalHeight(cfg)) + 'px',
    }
  }

  const zoomPercent = computed(() => Math.round(zoomLevel.value * 100))

  const zoomIn = () => {
    if (zoomLevel.value < maxZoom) {
      zoomLevel.value = Math.min(maxZoom, +(zoomLevel.value + zoomStep).toFixed(2))
    }
  }

  const zoomOut = () => {
    if (zoomLevel.value > minZoom) {
      zoomLevel.value = Math.max(minZoom, +(zoomLevel.value - zoomStep).toFixed(2))
    }
  }

  const resetZoom = () => {
    zoomLevel.value = 1
    const stage = stageElRef?.value
    if (stage) stage.scrollLeft = 0
  }

  const setupResizeObserver = () => {
    const stage = stageElRef?.value
    if (!stage) return
    stageWidth.value = stage.clientWidth
    resizeObserver = new ResizeObserver((entries) => {
      for (const entry of entries) {
        stageWidth.value = entry.contentRect.width
      }
    })
    resizeObserver.observe(stage)
  }

  const dispose = () => {
    if (resizeObserver) {
      resizeObserver.disconnect()
      resizeObserver = null
    }
  }

  onMounted(async () => {
    await nextTick()
    setupResizeObserver()
  })

  onScopeDispose(() => {
    dispose()
  })

  return {
    stageWidth,
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
    pageScaleStyle,
    dispose,
  }
}
