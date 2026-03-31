<template>
  <v-container class="pa-6" max-width="800">
    <div class="d-flex align-center gap-3 mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" @click="$router.back()" />
      <div>
        <h1 class="text-h5 font-weight-bold">Nowa paczka</h1>
        <p class="text-body-2 text-medium-emphasis">Zarejestruj nową przesyłkę w systemie</p>
      </div>
    </div>

    <v-card rounded="xl">
      <v-card-text class="pa-6">
        <v-form ref="formRef" v-model="formValid" @submit.prevent="submit">
          <v-row>
            <v-col cols="12">
              <p class="text-subtitle-1 font-weight-bold mb-3">
                <v-icon icon="mdi-map-marker-outline" color="primary" class="mr-2" />
                Dane nadawcy
              </p>
            </v-col>
            <v-col cols="12">
              <v-text-field
                v-model="form.senderAddress"
                label="Adres nadawcy"
                placeholder="ul. Przykładowa 1, 00-001 Warszawa"
                :rules="[required, maxLength(500)]"
                prepend-inner-icon="mdi-map-marker-outline"
                counter="500"
              />
            </v-col>
            <v-col cols="6">
              <v-text-field
                v-model.number="form.senderLatitude"
                label="Szerokość geograficzna (opcjonalnie)"
                type="number"
                step="0.0001"
                :rules="[latRule]"
                prepend-inner-icon="mdi-crosshairs-gps"
              />
            </v-col>
            <v-col cols="6">
              <v-text-field
                v-model.number="form.senderLongitude"
                label="Długość geograficzna (opcjonalnie)"
                type="number"
                step="0.0001"
                :rules="[lngRule]"
              />
            </v-col>

            <v-col cols="12">
              <v-divider class="mb-4" />
              <p class="text-subtitle-1 font-weight-bold mb-3">
                <v-icon icon="mdi-map-marker-check" color="success" class="mr-2" />
                Dane odbiorcy
              </p>
            </v-col>
            <v-col cols="12">
              <v-text-field
                v-model="form.receiverAddress"
                label="Adres odbiorcy"
                placeholder="ul. Odbiorcza 5, 31-001 Kraków"
                :rules="[required, maxLength(500)]"
                prepend-inner-icon="mdi-map-marker-check"
                counter="500"
              />
            </v-col>
            <v-col cols="6">
              <v-text-field
                v-model.number="form.receiverLatitude"
                label="Szerokość geograficzna (opcjonalnie)"
                type="number"
                step="0.0001"
                :rules="[latRule]"
                prepend-inner-icon="mdi-crosshairs-gps"
              />
            </v-col>
            <v-col cols="6">
              <v-text-field
                v-model.number="form.receiverLongitude"
                label="Długość geograficzna (opcjonalnie)"
                type="number"
                step="0.0001"
                :rules="[lngRule]"
              />
            </v-col>

            <v-col cols="12">
              <v-divider class="mb-4" />
              <p class="text-subtitle-1 font-weight-bold mb-3">
                <v-icon icon="mdi-package-variant" color="secondary" class="mr-2" />
                Szczegóły przesyłki
              </p>
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field
                v-model.number="form.weight"
                label="Waga (kg)"
                type="number"
                step="0.001"
                min="0.001"
                max="1000"
                :rules="[required, positiveRule]"
                prepend-inner-icon="mdi-weight-kilogram"
              />
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="form.courierName"
                label="Imię i nazwisko kuriera (opcjonalnie)"
                prepend-inner-icon="mdi-account"
              />
            </v-col>
            <v-col cols="12">
              <v-textarea
                v-model="form.notes"
                label="Notatki (opcjonalnie)"
                rows="3"
                prepend-inner-icon="mdi-note-text"
                auto-grow
              />
            </v-col>
          </v-row>

          <v-divider class="mb-4" />

          <div class="d-flex gap-3 justify-end">
            <v-btn variant="tonal" @click="$router.back()">Anuluj</v-btn>
            <v-btn
              type="submit"
              color="primary"
              :loading="isSubmitting"
              :disabled="!formValid"
              prepend-icon="mdi-content-save"
            >
              Utwórz paczkę
            </v-btn>
          </div>
        </v-form>
      </v-card-text>
    </v-card>

    <v-snackbar v-model="showError" color="error" :timeout="5000">
      {{ errorMessage }}
    </v-snackbar>
  </v-container>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { parcelsApi } from '@/api/parcels'

const router = useRouter()
const formRef = ref()
const formValid = ref(false)
const isSubmitting = ref(false)
const showError = ref(false)
const errorMessage = ref('')

const form = reactive({
  senderAddress: '',
  receiverAddress: '',
  weight: null as number | null,
  courierName: '',
  senderLatitude: null as number | null,
  senderLongitude: null as number | null,
  receiverLatitude: null as number | null,
  receiverLongitude: null as number | null,
  notes: '',
})

const required = (v: unknown) => (v !== null && v !== undefined && v !== '') || 'To pole jest wymagane'
const maxLength = (max: number) => (v: string) => (v?.length <= max) || `Maksymalnie ${max} znaków`
const positiveRule = (v: number | null) => (v !== null && v > 0) || 'Waga musi być większa niż 0'
const latRule = (v: number | null) => v === null || (v >= -90 && v <= 90) || 'Zakres: -90 do 90'
const lngRule = (v: number | null) => v === null || (v >= -180 && v <= 180) || 'Zakres: -180 do 180'

async function submit() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  isSubmitting.value = true
  try {
    const parcel = await parcelsApi.create({
      senderAddress: form.senderAddress,
      receiverAddress: form.receiverAddress,
      weight: form.weight!,
      courierName: form.courierName || null,
      senderLatitude: form.senderLatitude,
      senderLongitude: form.senderLongitude,
      receiverLatitude: form.receiverLatitude,
      receiverLongitude: form.receiverLongitude,
      notes: form.notes || null,
    })
    await router.push({ name: 'parcel-detail', params: { id: parcel.id } })
  } catch (e: unknown) {
    const error = e as { response?: { data?: { error?: string; errors?: Record<string, string[]> } } }
    const data = error.response?.data
    if (data?.errors) {
      const messages = Object.values(data.errors).flat()
      errorMessage.value = messages.join(', ')
    } else {
      errorMessage.value = data?.error ?? 'Błąd podczas tworzenia paczki'
    }
    showError.value = true
  } finally {
    isSubmitting.value = false
  }
}
</script>
