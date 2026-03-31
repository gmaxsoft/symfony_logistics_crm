<template>
  <v-container fluid class="pa-6">
    <div class="d-flex align-center gap-3 mb-6">
      <v-btn
        icon="mdi-arrow-left"
        variant="text"
        @click="$router.back()"
      />
      <div>
        <h1 class="text-h5 font-weight-bold">Szczegóły paczki</h1>
        <p v-if="parcel" class="text-body-2 text-medium-emphasis">
          {{ parcel.trackingNumber }}
        </p>
      </div>
    </div>

    <div v-if="isLoading" class="d-flex justify-center py-16">
      <v-progress-circular indeterminate color="primary" size="64" />
    </div>

    <div v-else-if="!parcel" class="text-center py-16">
      <v-icon icon="mdi-package-variant-closed" size="80" class="mb-4" />
      <p class="text-h6">Paczka nie znaleziona</p>
    </div>

    <template v-else>
      <v-row>
        <v-col cols="12" md="6">
          <v-card rounded="xl" class="mb-4">
            <v-card-title class="pa-4 d-flex align-center justify-space-between">
              <span>Informacje</span>
              <ParcelStatusChip
                :status="parcel.status"
                :status-label="parcel.statusLabel"
                :status-color="parcel.statusColor"
              />
            </v-card-title>
            <v-divider />
            <v-list>
              <v-list-item>
                <template #prepend>
                  <v-icon icon="mdi-barcode" color="primary" />
                </template>
                <v-list-item-title>Numer śledzenia</v-list-item-title>
                <v-list-item-subtitle>{{ parcel.trackingNumber }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <template #prepend>
                  <v-icon icon="mdi-map-marker-outline" color="primary" />
                </template>
                <v-list-item-title>Nadawca</v-list-item-title>
                <v-list-item-subtitle>{{ parcel.senderAddress }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <template #prepend>
                  <v-icon icon="mdi-map-marker-check" color="success" />
                </template>
                <v-list-item-title>Odbiorca</v-list-item-title>
                <v-list-item-subtitle>{{ parcel.receiverAddress }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <template #prepend>
                  <v-icon icon="mdi-weight-kilogram" color="secondary" />
                </template>
                <v-list-item-title>Waga</v-list-item-title>
                <v-list-item-subtitle>{{ parcel.weight }} kg</v-list-item-subtitle>
              </v-list-item>
              <v-list-item v-if="parcel.courierName">
                <template #prepend>
                  <v-icon icon="mdi-account" color="secondary" />
                </template>
                <v-list-item-title>Kurier</v-list-item-title>
                <v-list-item-subtitle>{{ parcel.courierName }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item v-if="parcel.notes">
                <template #prepend>
                  <v-icon icon="mdi-note-text" color="secondary" />
                </template>
                <v-list-item-title>Notatki</v-list-item-title>
                <v-list-item-subtitle>{{ parcel.notes }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item v-if="parcel.deliveredAt">
                <template #prepend>
                  <v-icon icon="mdi-clock-check" color="success" />
                </template>
                <v-list-item-title>Data dostarczenia</v-list-item-title>
                <v-list-item-subtitle>{{ formatDate(parcel.deliveredAt) }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-card>

          <!-- Available transitions -->
          <v-card v-if="availableTransitions.length > 0" rounded="xl">
            <v-card-title class="pa-4">Zmień status</v-card-title>
            <v-card-text class="pa-4 pt-0">
              <div class="d-flex flex-wrap gap-2">
                <TransitionButton
                  v-for="transition in availableTransitions"
                  :key="transition.name"
                  :transition="transition"
                  :is-loading="isTransitioning"
                  @apply="applyTransition"
                />
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card rounded="xl">
            <v-card-title class="pa-4 d-flex align-center gap-2">
              <v-icon icon="mdi-map" color="primary" />
              Trasa przesyłki
            </v-card-title>
            <v-card-text class="pa-2">
              <ParcelMap
                :sender="parcel.coordinates.sender"
                :receiver="parcel.coordinates.receiver"
                :tracking-number="parcel.trackingNumber"
                height="500px"
              />
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </template>

    <v-snackbar v-model="showSuccess" color="success" :timeout="3000">
      {{ successMessage }}
    </v-snackbar>
    <v-snackbar v-model="showError" color="error" :timeout="4000">
      {{ errorMessage }}
    </v-snackbar>
  </v-container>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { parcelsApi } from '@/api/parcels'
import ParcelStatusChip from '@/components/parcel/ParcelStatusChip.vue'
import TransitionButton from '@/components/parcel/TransitionButton.vue'
import ParcelMap from '@/components/map/ParcelMap.vue'
import type { Parcel, WorkflowTransition } from '@/types/parcel'

interface Props {
  id: string
}

const props = defineProps<Props>()

const parcel = ref<Parcel | null>(null)
const availableTransitions = ref<WorkflowTransition[]>([])
const isLoading = ref(true)
const isTransitioning = ref(false)
const showSuccess = ref(false)
const showError = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

async function loadParcel() {
  isLoading.value = true
  try {
    parcel.value = await parcelsApi.getById(props.id)
    const transitions = await parcelsApi.getTransitions(props.id)
    availableTransitions.value = transitions.available_transitions
  } catch {
    parcel.value = null
  } finally {
    isLoading.value = false
  }
}

async function applyTransition(transitionName: string) {
  if (!parcel.value) return
  isTransitioning.value = true
  try {
    const result = await parcelsApi.applyTransition(parcel.value.id, transitionName)
    parcel.value = result.parcel
    const transitions = await parcelsApi.getTransitions(parcel.value.id)
    availableTransitions.value = transitions.available_transitions
    successMessage.value = `Status zmieniony na: ${result.parcel.statusLabel}`
    showSuccess.value = true
  } catch (e: unknown) {
    const error = e as { response?: { data?: { error?: string } } }
    errorMessage.value = error.response?.data?.error ?? 'Błąd podczas zmiany statusu'
    showError.value = true
  } finally {
    isTransitioning.value = false
  }
}

function formatDate(iso: string): string {
  return new Intl.DateTimeFormat('pl-PL', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(iso))
}

onMounted(() => {
  loadParcel()
})
</script>
