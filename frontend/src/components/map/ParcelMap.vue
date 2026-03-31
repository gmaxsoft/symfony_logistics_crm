<template>
  <div ref="mapContainer" class="parcel-map" :style="{ height }" />
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import type { Coordinates } from '@/types/parcel'

// Fix Leaflet default icon path in Vite builds
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

delete (L.Icon.Default.prototype as unknown as Record<string, unknown>)._getIconUrl
L.Icon.Default.mergeOptions({
  iconUrl: markerIcon,
  iconRetinaUrl: markerIcon2x,
  shadowUrl: markerShadow,
})

interface Props {
  sender: Coordinates | null
  receiver: Coordinates | null
  height?: string
  trackingNumber?: string
}

const props = withDefaults(defineProps<Props>(), {
  height: '400px',
  trackingNumber: '',
})

const mapContainer = ref<HTMLElement | null>(null)
let map: L.Map | null = null
let senderMarker: L.Marker | null = null
let receiverMarker: L.Marker | null = null
let routePolyline: L.Polyline | null = null

const senderIcon = L.divIcon({
  className: '',
  html: `<div style="
    background: #1565C0;
    width: 32px; height: 32px;
    border-radius: 50% 50% 50% 0;
    transform: rotate(-45deg);
    border: 3px solid white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.4);
    display: flex; align-items: center; justify-content: center;
  "><span style="transform: rotate(45deg); color: white; font-size: 14px;">📦</span></div>`,
  iconSize: [32, 32],
  iconAnchor: [16, 32],
  popupAnchor: [0, -36],
})

const receiverIcon = L.divIcon({
  className: '',
  html: `<div style="
    background: #388E3C;
    width: 32px; height: 32px;
    border-radius: 50% 50% 50% 0;
    transform: rotate(-45deg);
    border: 3px solid white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.4);
    display: flex; align-items: center; justify-content: center;
  "><span style="transform: rotate(45deg); color: white; font-size: 14px;">🏠</span></div>`,
  iconSize: [32, 32],
  iconAnchor: [16, 32],
  popupAnchor: [0, -36],
})

function initMap() {
  if (!mapContainer.value) return

  const defaultCenter: L.LatLngExpression = [52.0693, 19.4803] // Poland center
  const defaultZoom = 6

  map = L.map(mapContainer.value, {
    center: defaultCenter,
    zoom: defaultZoom,
    zoomControl: true,
  })

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19,
  }).addTo(map)

  renderMarkers()
}

function renderMarkers() {
  if (!map) return

  // Clear existing layers
  senderMarker?.remove()
  receiverMarker?.remove()
  routePolyline?.remove()
  senderMarker = null
  receiverMarker = null
  routePolyline = null

  const points: L.LatLng[] = []

  if (props.sender) {
    const latLng = L.latLng(props.sender.lat, props.sender.lng)
    senderMarker = L.marker(latLng, { icon: senderIcon })
      .addTo(map)
      .bindPopup(
        `<strong>📦 Nadawca</strong><br>Paczka: ${props.trackingNumber || 'N/A'}`,
        { maxWidth: 200 },
      )
    points.push(latLng)
  }

  if (props.receiver) {
    const latLng = L.latLng(props.receiver.lat, props.receiver.lng)
    receiverMarker = L.marker(latLng, { icon: receiverIcon })
      .addTo(map)
      .bindPopup(
        `<strong>🏠 Odbiorca</strong><br>Paczka: ${props.trackingNumber || 'N/A'}`,
        { maxWidth: 200 },
      )
    points.push(latLng)
  }

  // Draw route polyline between sender and receiver
  if (points.length === 2) {
    routePolyline = L.polyline(points, {
      color: '#1565C0',
      weight: 4,
      opacity: 0.8,
      dashArray: '10, 8',
    }).addTo(map)

    // Fit map to show both markers
    map.fitBounds(L.latLngBounds(points), { padding: [50, 50] })
  } else if (points.length === 1) {
    map.setView(points[0], 12)
  }
}

onMounted(() => {
  initMap()
})

onUnmounted(() => {
  map?.remove()
  map = null
})

watch(
  () => [props.sender, props.receiver],
  () => {
    renderMarkers()
  },
  { deep: true },
)
</script>

<style scoped>
.parcel-map {
  width: 100%;
  border-radius: 12px;
  overflow: hidden;
  z-index: 0;
}
</style>
