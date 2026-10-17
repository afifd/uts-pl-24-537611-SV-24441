<template>
  <header class="navbar">
    <h2>My Items</h2>
    <div class="user-menu" v-if="user">
      <span>{{ user.name }} ({{ user.email }})</span>
      <button class="btn" @click="handleLogout" :disabled="loggingOut">
        {{ loggingOut ? 'Logging out...' : 'Logout' }}
      </button>
    </div>
  </header>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import apiClient from '../api/client'

const router = useRouter()
const loggingOut = ref(false)

const user = computed(() => {
  const stored = localStorage.getItem('user')
  return stored ? JSON.parse(stored) : null
})

async function handleLogout() {
  loggingOut.value = true
  try {
    await apiClient.post('/logout')
  } catch (err) {
    console.error('Logout error:', err)
  } finally {
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    loggingOut.value = false
    router.push('/login')
  }
}
</script>

<style scoped>
.navbar {
  border-bottom: 1px solid black;
  padding: 1rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.user-menu {
  display: flex;
  align-items: center;
  gap: 1rem;
}
</style>
