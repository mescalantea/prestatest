import { createRouter, createWebHashHistory } from 'vue-router'
import GeneralView from '@/views/GeneralView.vue'
import HomeView from '@/views/HomeView.vue'
import PaymentMethodsView from '@/views/PaymentMethodsView.vue'
import WidgetsView from '@/views/WidgetsView.vue'
import MiniWidgetsView from '@/views/MiniWidgetsView.vue'
import { useConfigStore } from '@/store/configStore'

const routes = [
  {
    path: '/',
    name: 'setup',
    meta: { requiresValidCredentials: false },
    component: HomeView
  },
  {
    path: '/general',
    name: 'general',
    meta: { requiresValidCredentials: true },
    component: GeneralView
  },
  {
    path: '/payment_methods',
    name: 'payment_methods',
    meta: { requiresValidCredentials: true },
    component: PaymentMethodsView
  },
  {
    path: '/widgets',
    name: 'widgets',
    meta: { requiresValidCredentials: true },
    component: WidgetsView
  },
  {
    path: '/miniwidgets',
    name: 'miniwidgets',
    meta: { requiresValidCredentials: true },
    component: MiniWidgetsView
  }
]

const router = createRouter({
  history: createWebHashHistory(),
  routes,
})


router.beforeEach((to) => {
  const config = useConfigStore()
  if (
    to.meta.requiresValidCredentials &&
    !config.valid_credentials
  ) return '/'
})

export default router
