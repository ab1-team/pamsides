<template>
  <div class="map-wrapper" :style="{ height: height }">
    <div ref="mapContainer" class="map-container"></div>

    <!-- Info Overlay -->
    <div v-if="loading" class="map-loading">
      <font-awesome-icon icon="spinner" spin />
      <span>Loading Maps...</span>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ lat: -6.1754, lng: 106.8272 }), // Default Jakarta
  },
  zoom: {
    type: Number,
    default: 13,
  },
  height: {
    type: String,
    default: '400px',
  },
  readOnly: {
    type: Boolean,
    default: false,
  },
  markers: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['update:modelValue', 'marker-click'])

const mapContainer = ref(null)
const map = ref(null)
const marker = ref(null)
const loading = ref(true)

// Fix Leaflet Default Icon issue
const fixLeafletIcon = () => {
  delete L.Icon.Default.prototype._getIconUrl
  L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
  })
}

onMounted(() => {
  fixLeafletIcon()

  map.value = L.map(mapContainer.value).setView(
    [props.modelValue.lat, props.modelValue.lng],
    props.zoom,
  )

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map.value)

  // Add primary marker if not readOnly
  if (!props.readOnly) {
    marker.value = L.marker([props.modelValue.lat, props.modelValue.lng], {
      draggable: true,
    }).addTo(map.value)

    marker.value.on('dragend', () => {
      const { lat, lng } = marker.value.getLatLng()
      emit('update:modelValue', { lat, lng })
    })

    map.value.on('click', (e) => {
      const { lat, lng } = e.latlng
      marker.value.setLatLng([lat, lng])
      emit('update:modelValue', { lat, lng })
    })
  } else if (props.markers.length === 0) {
    // Static marker for readOnly mode
    L.marker([props.modelValue.lat, props.modelValue.lng]).addTo(map.value)
  }

  // Handle multiple markers
  props.markers.forEach((m) => {
    L.marker([m.lat, m.lng])
      .addTo(map.value)
      .on('click', () => emit('marker-click', m))
  })

  loading.value = false

  // Force resize after mount
  setTimeout(() => {
    map.value.invalidateSize()
  }, 100)
})

onBeforeUnmount(() => {
  if (map.value) {
    map.value.remove()
  }
})

watch(
  () => props.modelValue,
  (newVal) => {
    if (map.value && !props.readOnly) {
      marker.value.setLatLng([newVal.lat, newVal.lng])
      map.value.panTo([newVal.lat, newVal.lng])
    }
  },
  { deep: true },
)
</script>

<style scoped>
.map-wrapper {
  position: relative;
  width: 100%;
  border-radius: 1rem;
  overflow: hidden;
  border: 1px solid #e2e8f0;
}

.map-container {
  width: 100%;
  height: 100%;
  z-index: 1;
}

.map-loading {
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  z-index: 10;
  font-size: 0.875rem;
  font-weight: 600;
  color: #64748b;
}
</style>
