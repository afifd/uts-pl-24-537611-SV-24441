import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import ItemsView from '../views/ItemsView.vue'

const routes = [
  {
    path: '/',
    redirect: () => {
      const token = localStorage.getItem('token')
      return token ? '/items' : '/login'
    },
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { guestOnly: true },
  },
  {
    path: '/items',
    name: 'items',
    component: ItemsView,
    meta: { requiresAuth: true },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')

  if (to.meta.requiresAuth && !token) {
    next({ name: 'login' })
  } else if (to.meta.guestOnly && token) {
    next({ name: 'items' })
  } else {
    next()
  }
})

export default router
