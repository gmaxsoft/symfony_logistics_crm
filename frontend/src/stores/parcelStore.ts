import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { parcelsApi } from '@/api/parcels'
import type { Parcel, WorkflowTransition, ParcelStatusValue } from '@/types/parcel'

export const useParcelStore = defineStore('parcel', () => {
  // ─── State ────────────────────────────────────────────────────────────────
  const parcels = ref<Parcel[]>([])
  const selectedParcel = ref<Parcel | null>(null)
  const availableTransitions = ref<WorkflowTransition[]>([])
  const isLoading = ref(false)
  const isTransitioning = ref(false)
  const error = ref<string | null>(null)

  // ─── Getters ──────────────────────────────────────────────────────────────
  const parcelsByStatus = computed(() => {
    const groups: Record<string, Parcel[]> = {}
    for (const parcel of parcels.value) {
      if (!groups[parcel.status]) {
        groups[parcel.status] = []
      }
      groups[parcel.status].push(parcel)
    }
    return groups
  })

  const activeParcels = computed(() =>
    parcels.value.filter(
      (p) => p.status !== 'delivered' && p.status !== 'failed',
    ),
  )

  const deliveredCount = computed(
    () => parcels.value.filter((p) => p.status === 'delivered').length,
  )

  // ─── Actions ──────────────────────────────────────────────────────────────
  async function fetchParcels(params?: { status?: string; active?: boolean; courier?: string }) {
    isLoading.value = true
    error.value = null
    try {
      parcels.value = await parcelsApi.getAll(params)
    } catch (e) {
      error.value = 'Błąd podczas pobierania paczek'
      console.error(e)
    } finally {
      isLoading.value = false
    }
  }

  async function fetchParcel(id: string) {
    isLoading.value = true
    error.value = null
    try {
      selectedParcel.value = await parcelsApi.getById(id)
      return selectedParcel.value
    } catch (e) {
      error.value = 'Nie znaleziono paczki'
      console.error(e)
      return null
    } finally {
      isLoading.value = false
    }
  }

  async function fetchTransitions(id: string) {
    try {
      const result = await parcelsApi.getTransitions(id)
      availableTransitions.value = result.available_transitions
      return result
    } catch (e) {
      console.error('Failed to fetch transitions', e)
      availableTransitions.value = []
      return null
    }
  }

  async function applyTransition(id: string, transition: string) {
    isTransitioning.value = true
    error.value = null
    try {
      const result = await parcelsApi.applyTransition(id, transition)
      const updatedParcel = result.parcel

      // Update in list
      const index = parcels.value.findIndex((p) => p.id === id)
      if (index !== -1) {
        parcels.value[index] = updatedParcel
      }

      // Update selected parcel
      if (selectedParcel.value?.id === id) {
        selectedParcel.value = updatedParcel
      }

      // Refresh transitions for new status
      await fetchTransitions(id)

      return updatedParcel
    } catch (e: unknown) {
      const axiosError = e as { response?: { data?: { error?: string } } }
      error.value = axiosError.response?.data?.error ?? 'Błąd podczas zmiany statusu'
      throw e
    } finally {
      isTransitioning.value = false
    }
  }

  async function deleteParcel(id: string) {
    try {
      await parcelsApi.delete(id)
      parcels.value = parcels.value.filter((p) => p.id !== id)
      if (selectedParcel.value?.id === id) {
        selectedParcel.value = null
      }
    } catch (e) {
      error.value = 'Nie można usunąć paczki'
      throw e
    }
  }

  function selectParcel(parcel: Parcel | null) {
    selectedParcel.value = parcel
    if (parcel) {
      fetchTransitions(parcel.id)
    } else {
      availableTransitions.value = []
    }
  }

  function clearError() {
    error.value = null
  }

  return {
    // state
    parcels,
    selectedParcel,
    availableTransitions,
    isLoading,
    isTransitioning,
    error,
    // getters
    parcelsByStatus,
    activeParcels,
    deliveredCount,
    // actions
    fetchParcels,
    fetchParcel,
    fetchTransitions,
    applyTransition,
    deleteParcel,
    selectParcel,
    clearError,
  }
})
