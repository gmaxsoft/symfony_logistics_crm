import http from './http'
import type {
  Parcel,
  CreateParcelPayload,
  ParcelTransitionsResponse,
  ApplyTransitionResponse,
} from '@/types/parcel'

export const parcelsApi = {
  /**
   * Fetch all parcels, optionally filtered by status or active flag
   */
  async getAll(params?: { status?: string; active?: boolean; courier?: string }): Promise<Parcel[]> {
    const response = await http.get<Parcel[]>('/parcels', { params })
    return response.data
  },

  /**
   * Fetch a single parcel by ID or tracking number
   */
  async getById(id: string): Promise<Parcel> {
    const response = await http.get<Parcel>(`/parcels/${id}`)
    return response.data
  },

  /**
   * Create a new parcel
   */
  async create(payload: CreateParcelPayload): Promise<Parcel> {
    const response = await http.post<Parcel>('/parcels', payload)
    return response.data
  },

  /**
   * Get available workflow transitions for a parcel
   */
  async getTransitions(id: string): Promise<ParcelTransitionsResponse> {
    const response = await http.get<ParcelTransitionsResponse>(`/parcels/${id}/transitions`)
    return response.data
  },

  /**
   * Apply a workflow transition to a parcel
   */
  async applyTransition(id: string, transition: string): Promise<ApplyTransitionResponse> {
    const response = await http.patch<ApplyTransitionResponse>(
      `/parcels/${id}/transition/${transition}`,
    )
    return response.data
  },

  /**
   * Delete a parcel (only allowed in draft status)
   */
  async delete(id: string): Promise<void> {
    await http.delete(`/parcels/${id}`)
  },
}
