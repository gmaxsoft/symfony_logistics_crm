// TypeScript interfaces mirroring backend Parcel entity and API responses

export type ParcelStatusValue =
  | 'draft'
  | 'picked_up'
  | 'in_sorting_center'
  | 'out_for_delivery'
  | 'delivered'
  | 'failed'

export interface Coordinates {
  lat: number
  lng: number
}

export interface ParcelCoordinates {
  sender: Coordinates | null
  receiver: Coordinates | null
}

export interface Parcel {
  id: string
  trackingNumber: string
  status: ParcelStatusValue
  statusLabel: string
  statusColor: string
  senderAddress: string
  receiverAddress: string
  weight: number
  courierName: string | null
  notes: string | null
  coordinates: ParcelCoordinates
  createdAt: string
  updatedAt: string
  deliveredAt: string | null
}

export interface WorkflowTransition {
  name: string
  froms: string[]
  tos: string[]
  label: string
}

export interface ParcelTransitionsResponse {
  parcel_id: string
  current_status: ParcelStatusValue
  available_transitions: WorkflowTransition[]
}

export interface CreateParcelPayload {
  senderAddress: string
  receiverAddress: string
  weight: number
  courierName?: string | null
  senderLatitude?: number | null
  senderLongitude?: number | null
  receiverLatitude?: number | null
  receiverLongitude?: number | null
  notes?: string | null
}

export interface ApplyTransitionResponse {
  message: string
  parcel: Parcel
}

export interface ApiError {
  error?: string
  errors?: Record<string, string[]>
  available_transitions?: string[]
}
