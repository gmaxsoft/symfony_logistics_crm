<template>
  <v-card
    :class="['parcel-card', { 'parcel-card--selected': isSelected }]"
    @click="$emit('select', parcel)"
  >
    <v-card-text class="pa-4">
      <div class="d-flex align-start justify-space-between mb-2">
        <div>
          <p class="text-subtitle-2 font-weight-bold mb-0">
            {{ parcel.trackingNumber }}
          </p>
          <p class="text-caption text-medium-emphasis mt-0">
            {{ formatDate(parcel.createdAt) }}
          </p>
        </div>
        <ParcelStatusChip
          :status="parcel.status"
          :status-label="parcel.statusLabel"
          :status-color="parcel.statusColor"
        />
      </div>

      <v-divider class="mb-3" />

      <div class="d-flex flex-column gap-1">
        <div class="d-flex align-center gap-2">
          <v-icon icon="mdi-map-marker-outline" size="16" color="primary" />
          <span class="text-caption text-truncate">{{ parcel.senderAddress }}</span>
        </div>
        <div class="d-flex align-center gap-2">
          <v-icon icon="mdi-map-marker-check" size="16" color="success" />
          <span class="text-caption text-truncate">{{ parcel.receiverAddress }}</span>
        </div>
        <div class="d-flex align-center gap-2">
          <v-icon icon="mdi-weight-kilogram" size="16" color="secondary" />
          <span class="text-caption">{{ parcel.weight }} kg</span>
          <v-icon v-if="parcel.courierName" icon="mdi-account" size="16" color="secondary" class="ml-2" />
          <span v-if="parcel.courierName" class="text-caption">{{ parcel.courierName }}</span>
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import ParcelStatusChip from './ParcelStatusChip.vue'
import type { Parcel } from '@/types/parcel'

interface Props {
  parcel: Parcel
  isSelected?: boolean
}

withDefaults(defineProps<Props>(), {
  isSelected: false,
})

defineEmits<{
  select: [parcel: Parcel]
}>()

function formatDate(iso: string): string {
  return new Intl.DateTimeFormat('pl-PL', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(iso))
}
</script>

<style scoped>
.parcel-card {
  cursor: pointer;
  transition: all 0.2s ease;
  border: 2px solid transparent;
}
.parcel-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12) !important;
}
.parcel-card--selected {
  border-color: rgb(var(--v-theme-primary));
}
</style>
