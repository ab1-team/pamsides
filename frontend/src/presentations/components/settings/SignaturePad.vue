<template>
  <div class="signature-pad">
    <div class="signature-pad__canvas-wrapper">
      <canvas
        ref="canvasRef"
        :width="width"
        :height="height"
        class="signature-pad__canvas"
        @mousedown="startDrawing"
        @mousemove="draw"
        @mouseup="stopDrawing"
        @mouseleave="stopDrawing"
        @touchstart.prevent="startDrawing"
        @touchmove.prevent="draw"
        @touchend="stopDrawing"
      ></canvas>
      <div v-if="!hasDrawn" class="signature-pad__placeholder">
        <font-awesome-icon icon="pen-nib" />
        <span>Goreskan tanda tangan di area ini</span>
      </div>
    </div>

    <div class="signature-pad__actions">
      <div class="signature-pad__actions-left">
        <BaseButton
          type="button"
          variant="ghost"
          size="sm"
          icon="refresh"
          :disabled="!hasDrawn"
          @click="clear"
        >
          Hapus
        </BaseButton>
        <BaseButton
          type="button"
          variant="secondary"
          size="sm"
          icon="upload"
          @click="triggerFile"
        >
          Upload File
        </BaseButton>
        <input
          ref="fileInputRef"
          type="file"
          accept="image/png,image/jpeg,image/webp"
          style="display:none"
          @change="onFileSelected"
        />
      </div>
      <div class="signature-pad__actions-right">
        <BaseButton
          type="button"
          variant="ghost"
          size="sm"
          @click="$emit('cancel')"
        >
          Batal
        </BaseButton>
        <BaseButton
          type="button"
          variant="primary"
          size="sm"
          icon="check"
          :disabled="!hasDrawn"
          @click="save"
        >
          Gunakan
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'

const props = defineProps({
  width: { type: Number, default: 560 },
  height: { type: Number, default: 220 },
  strokeColor: { type: String, default: '#0f172a' },
  strokeWidth: { type: Number, default: 2.5 },
  /** Optional initial image (data URI) untuk pre-load canvas */
  initialDataUri: { type: String, default: '' },
})

const emit = defineEmits(['save', 'cancel'])

const canvasRef = ref(null)
const fileInputRef = ref(null)
const isDrawing = ref(false)
const hasDrawn = ref(false)
let ctx = null

function getCoordinates(event) {
  const canvas = canvasRef.value
  if (!canvas) return { x: 0, y: 0 }
  const rect = canvas.getBoundingClientRect()
  const scaleX = canvas.width / rect.width
  const scaleY = canvas.height / rect.height

  if (event.touches && event.touches.length > 0) {
    return {
      x: (event.touches[0].clientX - rect.left) * scaleX,
      y: (event.touches[0].clientY - rect.top) * scaleY,
    }
  }
  return {
    x: (event.clientX - rect.left) * scaleX,
    y: (event.clientY - rect.top) * scaleY,
  }
}

function startDrawing(event) {
  if (!ctx) return
  event.preventDefault?.()
  isDrawing.value = true
  const { x, y } = getCoordinates(event)
  ctx.beginPath()
  ctx.moveTo(x, y)
}

function draw(event) {
  if (!isDrawing.value || !ctx) return
  event.preventDefault?.()
  const { x, y } = getCoordinates(event)
  ctx.lineTo(x, y)
  ctx.stroke()
  hasDrawn.value = true
}

function stopDrawing() {
  if (!isDrawing.value || !ctx) return
  isDrawing.value = false
  ctx.closePath()
}

function clear() {
  if (!ctx || !canvasRef.value) return
  ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height)
  hasDrawn.value = false
}

function save() {
  if (!canvasRef.value || !hasDrawn.value) return
  // Export transparent PNG
  const dataUrl = canvasRef.value.toDataURL('image/png')
  emit('save', dataUrl)
}

function triggerFile() {
  fileInputRef.value?.click()
}

function onFileSelected(e) {
  const file = e.target.files?.[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = (ev) => {
    const dataUri = String(ev.target?.result || '')
    loadImageToCanvas(dataUri)
    // Auto-save setelah upload file
    emit('save', dataUri)
  }
  reader.readAsDataURL(file)
  // Reset supaya bisa pilih file yang sama lagi nanti
  e.target.value = ''
}

function loadImageToCanvas(dataUri) {
  if (!ctx || !canvasRef.value) return
  const img = new Image()
  img.onload = () => {
    ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height)
    // Fit image sambil menjaga aspect ratio
    const ratio = Math.min(
      canvasRef.value.width / img.width,
      canvasRef.value.height / img.height,
    )
    const w = img.width * ratio
    const h = img.height * ratio
    const x = (canvasRef.value.width - w) / 2
    const y = (canvasRef.value.height - h) / 2
    ctx.drawImage(img, x, y, w, h)
    hasDrawn.value = true
  }
  img.src = dataUri
}

function initCanvas() {
  const canvas = canvasRef.value
  if (!canvas) return
  ctx = canvas.getContext('2d')
  if (!ctx) return

  ctx.lineCap = 'round'
  ctx.lineJoin = 'round'
  ctx.strokeStyle = props.strokeColor
  ctx.lineWidth = props.strokeWidth

  if (props.initialDataUri) {
    loadImageToCanvas(props.initialDataUri)
  }
}

onMounted(() => {
  initCanvas()
})

watch(() => props.strokeColor, (c) => {
  if (ctx) ctx.strokeStyle = c
})

defineExpose({ clear, save })
</script>

<style scoped>
.signature-pad {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.signature-pad__canvas-wrapper {
  position: relative;
  border: 2px dashed #cbd5e1;
  border-radius: 12px;
  background: #ffffff;
  padding: 8px;
  overflow: hidden;
}

.signature-pad__canvas {
  width: 100%;
  height: auto;
  display: block;
  touch-action: none;
  cursor: crosshair;
  background: transparent;
}

.signature-pad__placeholder {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: #94a3b8;
  font-size: 0.85rem;
  pointer-events: none;
  user-select: none;
}

.signature-pad__actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.signature-pad__actions-left,
.signature-pad__actions-right {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
</style>
