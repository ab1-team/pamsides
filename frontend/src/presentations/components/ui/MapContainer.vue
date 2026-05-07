<template>
  <div class="map-wrapper" :style="height ? { height: height } : {}">
    <div ref="mapContainer" class="map-container"></div>

    <!-- Map Type Toggle -->
    <div class="map-controls">
      <button 
        @click="toggleMapType" 
        class="map-control-btn shadow-lg"
        :title="isSatellite ? 'Switch to Roadmap' : 'Switch to Satellite'"
      >
        <font-awesome-icon :icon="isSatellite ? 'map' : 'satellite'" />
        <span>{{ isSatellite ? 'Peta' : 'Satelit' }}</span>
      </button>
    </div>

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
    default: '',
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
const tileLayer = ref(null)
const loading = ref(true)
const isSatellite = ref(false)

// Fix Leaflet Default Icon issue
const fixLeafletIcon = () => {
  delete L.Icon.Default.prototype._getIconUrl
  L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
  })
}

const toggleMapType = () => {
  if (map.value && tileLayer.value) {
    map.value.removeLayer(tileLayer.value)
    
    isSatellite.value = !isSatellite.value
    const layerType = isSatellite.value ? 's' : 'm'
    
    tileLayer.value = L.tileLayer('https://{s}.google.com/vt/lyrs=' + layerType + '&x={x}&y={y}&z={z}', {
      maxZoom: 20,
      subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
      attribution: '&copy; Google Maps',
    })
    
    tileLayer.value.addTo(map.value)
  }
}

onMounted(() => {
  fixLeafletIcon()

  map.value = L.map(mapContainer.value).setView(
    [props.modelValue.lat, props.modelValue.lng],
    props.zoom,
  )

  // Initial Google Maps Layer
  tileLayer.value = L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
    maxZoom: 20,
    subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
    attribution: '&copy; Google Maps',
  })
  tileLayer.value.addTo(map.value)

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
    if (map.value && !props.readOnly && marker.value) {
      marker.value.setLatLng([newVal.lat, newVal.lng])
      map.value.panTo([newVal.lat, newVal.lng])
    }
  },
  { deep: true },
)
</script>

<style>
/* Global styles for custom marker */
.custom-marker {
  display: flex;
  align-items: center;
  justify-content: center;
}

.marker-pin {
  width: 24px;
  height: 24px;
  background: white;
  border: 4px solid #f97316;
  border-radius: 50%;
  position: relative;
  box-shadow: 0 4px 10px rgba(249, 115, 22, 0.4);
  z-index: 2;
}

.marker-inner {
  width: 6px;
  height: 6px;
  background: #f97316;
  border-radius: 50%;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.marker-pulse {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 48px;
  height: 48px;
  background: rgba(249, 115, 22, 0.2);
  border-radius: 50%;
  z-index: 1;
  animation: marker-pulse 2s infinite;
}

@keyframes marker-pulse {
  0% { transform: translate(-50%, -50%) scale(0.5); opacity: 0.8; }
  100% { transform: translate(-50%, -50%) scale(2); opacity: 0; }
}

.leaflet-container {
  background: #f8fafc !important;
}

.leaflet-fade-anim .leaflet-tile,.leaflet-zoom-anim .leaflet-tile {
  transition-duration: 500ms;
}
</style>

<style scoped>
.map-wrapper {
  position: relative;
  width: 100%;
  border-radius: 1.5rem;
  overflow: hidden;
  background: #f1f5f9;
}

.map-container {
  width: 100%;
  height: 100%;
  z-index: 1;
}

.map-controls {
  position: absolute;
  top: 1rem;
  right: 1rem;
  z-index: 1000;
}

.map-control-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 1rem;
  font-size: 0.75rem;
  font-weight: 800;
  color: #1e293b;
  cursor: pointer;
  transition: all 0.2s;
}

.map-control-btn:hover {
  background: #f8fafc;
  transform: scale(1.05);
}

.map-loading {
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(8px);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 2000;
}
</style>
