<template>
  <v-container class="d-flex align-center justify-center" style="min-height: 80vh">
    <div class="text-center">
      <v-icon icon="mdi-package-variant-closed-remove" size="120" color="primary" class="mb-6" />
      <h1 class="text-h3 font-weight-bold mb-2">404</h1>
      <p class="text-h6 text-medium-emphasis mb-6">Strona nie została znaleziona</p>
      <v-btn color="primary" :to="{ name: 'courier-dashboard' }" prepend-icon="mdi-home">
        Wróć do dashboardu
      </v-btn>
    </div>
  </v-container>
</template>
