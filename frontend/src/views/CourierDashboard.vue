<template>
  <v-container fluid class="pa-6">
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Panel Kuriera</h1>
        <p class="text-body-2 text-medium-emphasis">
          Zarządzaj paczkami i ich statusami
        </p>
      </div>
      <div class="d-flex gap-3">
        <v-btn
          color="primary"
          prepend-icon="mdi-refresh"
          variant="tonal"
          :loading="parcelStore.isLoading"
          @click="loadParcels"
        >
          Odśwież
        </v-btn>
        <v-btn
          color="primary"
          prepend-icon="mdi-plus"
          :to="{ name: 'parcel-create' }"
        >
          Nowa paczka
        </v-btn>
      </div>
    </div>

    <!-- Stats row -->
    <v-row class="mb-6" dense>
      <v-col v-for="stat in stats" :key="stat.label" cols="6" sm="3">
        <v-card rounded="xl">
          <v-card-text class="text-center pa-4">
            <v-icon :icon="stat.icon" :color="stat.color" size="32" class="mb-2" />
            <div class="text-h5 font-weight-bold">{{ stat.value }}</div>
            <div class="text-caption text-medium-emphasis">{{ stat.label }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Filter chips -->
    <div class="d-flex gap-2 mb-4 flex-wrap">
      <v-chip
        v-for="filter in statusFilters"
        :key="filter.value"
        :color="activeFilter === filter.value ? 'primary' : undefined"
        :variant="activeFilter === filter.value ? 'elevated' : 'tonal'"
        class="cursor-pointer"
        @click="setFilter(filter.value)"
      >
        {{ filter.label }}
        <v-badge
          v-if="getStatusCount(filter.value) > 0"
          :content="getStatusCount(filter.value)"
          color="error"
          inline
          class="ml-1"
        />
      </v-chip>
    </div>

    <!-- Main layout: list + map -->
    <v-row>
      <!-- Parcel list -->
      <v-col cols="12" md="5" lg="4">
        <div v-if="parcelStore.isLoading" class="d-flex justify-center py-10">
          <v-progress-circular indeterminate color="primary" size="48" />
        </div>

        <div
          v-else-if="filteredParcels.length === 0"
          class="text-center py-10 text-medium-emphasis"
        >
          <v-icon icon="mdi-package-variant-closed" size="64" class="mb-3" />
          <p>Brak paczek do wyświetlenia</p>
        </div>

        <div v-else class="parcel-list d-flex flex-column gap-3">
          <ParcelCard
            v-for="parcel in filteredParcels"
            :key="parcel.id"
            :parcel="parcel"
            :is-selected="parcelStore.selectedParcel?.id === parcel.id"
            data-cy="parcel-card"
            @select="selectParcel"
          />
        </div>
      </v-col>

      <!-- Detail panel + map -->
      <v-col cols="12" md="7" lg="8">
        <div v-if="!parcelStore.selectedParcel" class="empty-state text-center py-16 text-medium-emphasis">
          <v-icon icon="mdi-cursor-default-click" size="80" class="mb-4" />
          <p class="text-h6">Wybierz paczkę z listy</p>
          <p class="text-body-2">Kliknij na paczkę, aby zobaczyć szczegóły i mapę</p>
        </div>

        <template v-else>
          <!-- Parcel detail card -->
          <v-card class="mb-4" rounded="xl">
            <v-card-title class="d-flex align-center justify-space-between pa-4">
              <div class="d-flex align-center gap-3">
                <v-icon icon="mdi-package-variant" color="primary" />
                <span class="text-h6">{{ parcelStore.selectedParcel.trackingNumber }}</span>
              </div>
              <ParcelStatusChip
                :status="parcelStore.selectedParcel.status"
                :status-label="parcelStore.selectedParcel.statusLabel"
                :status-color="parcelStore.selectedParcel.statusColor"
              />
            </v-card-title>

            <v-divider />

            <v-card-text class="pa-4">
              <v-row dense>
                <v-col cols="12" sm="6">
                  <div class="d-flex align-center gap-2 mb-2">
                    <v-icon icon="mdi-map-marker-outline" color="primary" size="20" />
                    <div>
                      <div class="text-caption text-medium-emphasis">Nadawca</div>
                      <div class="text-body-2">{{ parcelStore.selectedParcel.senderAddress }}</div>
                    </div>
                  </div>
                </v-col>
                <v-col cols="12" sm="6">
                  <div class="d-flex align-center gap-2 mb-2">
                    <v-icon icon="mdi-map-marker-check" color="success" size="20" />
                    <div>
                      <div class="text-caption text-medium-emphasis">Odbiorca</div>
                      <div class="text-body-2">{{ parcelStore.selectedParcel.receiverAddress }}</div>
                    </div>
                  </div>
                </v-col>
                <v-col cols="6" sm="3">
                  <div class="text-caption text-medium-emphasis">Waga</div>
                  <div class="text-body-2 font-weight-medium">
                    {{ parcelStore.selectedParcel.weight }} kg
                  </div>
                </v-col>
                <v-col cols="6" sm="3">
                  <div class="text-caption text-medium-emphasis">Kurier</div>
                  <div class="text-body-2 font-weight-medium">
                    {{ parcelStore.selectedParcel.courierName ?? '—' }}
                  </div>
                </v-col>
              </v-row>
            </v-card-text>

            <!-- Transition buttons -->
            <v-card-actions class="pa-4 pt-0">
              <div class="w-100">
                <p class="text-caption text-medium-emphasis mb-2">Dostępne akcje:</p>
                <div v-if="parcelStore.availableTransitions.length === 0" class="text-caption text-medium-emphasis">
                  Brak dostępnych akcji dla tego statusu
                </div>
                <div class="d-flex flex-wrap gap-2">
                  <TransitionButton
                    v-for="transition in parcelStore.availableTransitions"
                    :key="transition.name"
                    :transition="transition"
                    :is-loading="parcelStore.isTransitioning"
                    :data-cy="`transition-btn-${transition.name}`"
                    @apply="applyTransition"
                  />
                </div>
              </div>
            </v-card-actions>
          </v-card>

          <!-- Map -->
          <v-card rounded="xl">
            <v-card-title class="pa-4 d-flex align-center gap-2">
              <v-icon icon="mdi-map" color="primary" />
              Trasa przesyłki
            </v-card-title>
            <v-card-text class="pa-2">
              <ParcelMap
                :sender="parcelStore.selectedParcel.coordinates.sender"
                :receiver="parcelStore.selectedParcel.coordinates.receiver"
                :tracking-number="parcelStore.selectedParcel.trackingNumber"
                height="380px"
              />
            </v-card-text>
          </v-card>
        </template>
      </v-col>
    </v-row>

    <!-- Error snackbar -->
    <v-snackbar
      v-model="showError"
      color="error"
      location="bottom right"
      :timeout="4000"
    >
      {{ parcelStore.error }}
      <template #actions>
        <v-btn variant="text" @click="parcelStore.clearError(); showError = false">
          Zamknij
        </v-btn>
      </template>
    </v-snackbar>

    <!-- Success snackbar -->
    <v-snackbar
      v-model="showSuccess"
      color="success"
      location="bottom right"
      :timeout="3000"
    >
      {{ successMessage }}
    </v-snackbar>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useParcelStore } from '@/stores/parcelStore'
import ParcelCard from '@/components/parcel/ParcelCard.vue'
import ParcelStatusChip from '@/components/parcel/ParcelStatusChip.vue'
import TransitionButton from '@/components/parcel/TransitionButton.vue'
import ParcelMap from '@/components/map/ParcelMap.vue'
import type { Parcel } from '@/types/parcel'

const parcelStore = useParcelStore()
const activeFilter = ref<string>('all')
const showError = ref(false)
const showSuccess = ref(false)
const successMessage = ref('')

const statusFilters = [
  { label: 'Wszystkie', value: 'all' },
  { label: 'Robocze', value: 'draft' },
  { label: 'Odebrane', value: 'picked_up' },
  { label: 'Sortowanie', value: 'in_sorting_center' },
  { label: 'W doręczeniu', value: 'out_for_delivery' },
  { label: 'Dostarczone', value: 'delivered' },
  { label: 'Nieudane', value: 'failed' },
]

const filteredParcels = computed(() => {
  if (activeFilter.value === 'all') return parcelStore.parcels
  return parcelStore.parcels.filter((p) => p.status === activeFilter.value)
})

const stats = computed(() => [
  {
    label: 'Wszystkich paczek',
    value: parcelStore.parcels.length,
    icon: 'mdi-package-variant',
    color: 'primary',
  },
  {
    label: 'Aktywnych',
    value: parcelStore.activeParcels.length,
    icon: 'mdi-truck-delivery',
    color: 'orange',
  },
  {
    label: 'Dostarczonych',
    value: parcelStore.deliveredCount,
    icon: 'mdi-check-circle',
    color: 'success',
  },
  {
    label: 'W doręczeniu',
    value: parcelStore.parcels.filter((p) => p.status === 'out_for_delivery').length,
    icon: 'mdi-map-marker-path',
    color: 'purple',
  },
])

function getStatusCount(status: string): number {
  if (status === 'all') return 0
  return parcelStore.parcels.filter((p) => p.status === status).length
}

function setFilter(value: string) {
  activeFilter.value = value
  parcelStore.selectParcel(null)
}

async function loadParcels() {
  await parcelStore.fetchParcels()
}

async function selectParcel(parcel: Parcel) {
  parcelStore.selectParcel(parcel)
}

async function applyTransition(transitionName: string) {
  if (!parcelStore.selectedParcel) return
  try {
    const updated = await parcelStore.applyTransition(parcelStore.selectedParcel.id, transitionName)
    successMessage.value = `Status zmieniony na: ${updated.statusLabel}`
    showSuccess.value = true
  } catch {
    showError.value = true
  }
}

watch(
  () => parcelStore.error,
  (val) => {
    if (val) showError.value = true
  },
)

onMounted(() => {
  loadParcels()
})
</script>

<style scoped>
.parcel-list {
  max-height: calc(100vh - 320px);
  overflow-y: auto;
  padding-right: 4px;
}
.parcel-list::-webkit-scrollbar {
  width: 4px;
}
.parcel-list::-webkit-scrollbar-track {
  background: transparent;
}
.parcel-list::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.15);
  border-radius: 2px;
}
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
}
</style>
