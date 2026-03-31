<template>
  <v-app :theme="'logistics'">
    <v-navigation-drawer :rail="rail" permanent color="primary">
      <v-list-item
        prepend-icon="mdi-truck-fast"
        title="Logistics CRM"
        nav
      >
        <template #append>
          <v-btn
            :icon="rail ? 'mdi-chevron-right' : 'mdi-chevron-left'"
            variant="text"
            @click="rail = !rail"
          />
        </template>
      </v-list-item>

      <v-divider />

      <v-list density="compact" nav>
        <v-list-item
          v-for="item in navItems"
          :key="item.to"
          :prepend-icon="item.icon"
          :title="item.title"
          :to="item.to"
          active-class="bg-white text-primary"
          rounded="lg"
        />
      </v-list>
    </v-navigation-drawer>

    <v-main>
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const rail = ref(false)

const navItems = [
  { title: 'Panel Kuriera', icon: 'mdi-view-dashboard', to: '/courier' },
  { title: 'Nowa paczka', icon: 'mdi-plus-circle', to: '/parcels/new' },
]
</script>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
