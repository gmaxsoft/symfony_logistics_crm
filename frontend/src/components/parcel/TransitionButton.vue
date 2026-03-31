<template>
  <v-btn
    :color="buttonColor"
    :loading="isLoading"
    :prepend-icon="transitionIcon"
    variant="elevated"
    @click="$emit('apply', transition.name)"
  >
    {{ transition.label }}
  </v-btn>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { WorkflowTransition } from '@/types/parcel'

interface Props {
  transition: WorkflowTransition
  isLoading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isLoading: false,
})

defineEmits<{
  apply: [transitionName: string]
}>()

const transitionIcon = computed(() => {
  const icons: Record<string, string> = {
    pick_up: 'mdi-package-up',
    sort: 'mdi-sort-variant',
    deliver_start: 'mdi-truck-delivery',
    confirm_delivery: 'mdi-check-circle',
    mark_failed: 'mdi-close-circle',
  }
  return icons[props.transition.name] ?? 'mdi-arrow-right'
})

const buttonColor = computed(() => {
  const colors: Record<string, string> = {
    pick_up: 'primary',
    sort: 'orange',
    deliver_start: 'purple',
    confirm_delivery: 'success',
    mark_failed: 'error',
  }
  return colors[props.transition.name] ?? 'primary'
})
</script>
