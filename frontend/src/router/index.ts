import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    redirect: '/courier',
  },
  {
    path: '/courier',
    name: 'courier-dashboard',
    component: () => import('@/views/CourierDashboard.vue'),
    meta: { title: 'Panel Kuriera' },
  },
  {
    path: '/parcels/:id',
    name: 'parcel-detail',
    component: () => import('@/views/ParcelDetail.vue'),
    meta: { title: 'Szczegóły paczki' },
    props: true,
  },
  {
    path: '/parcels/new',
    name: 'parcel-create',
    component: () => import('@/views/ParcelCreate.vue'),
    meta: { title: 'Nowa paczka' },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFound.vue'),
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.afterEach((to) => {
  document.title = `${to.meta.title as string ?? 'Logistics CRM'} | Logistics CRM`
})

export default router
