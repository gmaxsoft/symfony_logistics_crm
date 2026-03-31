<template>
  <v-chip
    :color="statusColor"
    :text="statusLabel"
    size="small"
    label
    class="font-weight-medium"
  >
    <template #prepend>
      <v-icon :icon="statusIcon" size="14" class="mr-1" />
    </template>
  </v-chip>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { ParcelStatusValue } from '@/types/parcel'

interface Props {
  status: ParcelStatusValue
  statusLabel: string
  statusColor: string
}

const props = defineProps<Props>()

const statusIcon = computed(() => {
  const icons: Record<ParcelStatusValue, string> = {
    draft: 'mdi-pencil-outline',
    picked_up: 'mdi-package-up',
    in_sorting_center: 'mdi-sort-variant',
    out_for_delivery: 'mdi-truck-delivery',
    delivered: 'mdi-check-circle',
    failed: 'mdi-close-circle',
  }
  return icons[props.status] ?? 'mdi-help-circle'
})
</script>
