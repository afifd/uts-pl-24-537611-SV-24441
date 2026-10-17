<template>
  <div class="login-page">
    <div class="login-card">
      <div class="login-header">
        <h1 class="login-title">My Items</h1>
      </div>

      <div v-if="errorMessage" class="alert alert-danger">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label for="email" class="form-label">Email Address</label>
          <input
            id="email"
            v-model="email"
            type="email"
            class="form-control"
            :class="{ 'is-invalid': errors.email }"
            placeholder="me@afif.dev"
            required
            autocomplete="email"
          />
          <div v-if="errors.email" class="invalid-feedback">
            {{ errors.email[0] }}
          </div>
        </div>

        <div class="form-group">
          <label for="password" class="form-label">Password</label>
          <input
            id="password"
            v-model="password"
            type="password"
            class="form-control"
            :class="{ 'is-invalid': errors.password }"
            placeholder="password"
            required
            autocomplete="current-password"
          />
          <div v-if="errors.password" class="invalid-feedback">
            {{ errors.password[0] }}
          </div>
        </div>

        <button type="submit" class="btn" :disabled="loading">
          {{ loading ? 'Signing in...' : 'Sign In' }}
        </button>
      </form>

      <div class="demo-credentials">
        <p>Default Demo Credentials:</p>
        <p>email: me@afif.dev</p>
        <p>password: password</p>
        <button type="button" class="btn" @click="fillDemo">
          Fill Credentials
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import apiClient from '../api/client'

const router = useRouter()
const email = ref('')
const password = ref('')
const loading = ref(false)
const errorMessage = ref('')
const errors = reactive({})

function fillDemo() {
  email.value = 'me@afif.dev'
  password.value = 'password'
}

async function handleLogin() {
  loading.value = true
  errorMessage.value = ''
  Object.keys(errors).forEach((k) => delete errors[k])

  try {
    const response = await apiClient.post('/login', {
      email: email.value,
      password: password.value,
    })

    const { token, user } = response.data
    localStorage.setItem('token', token)
    localStorage.setItem('user', JSON.stringify(user))

    router.push('/items')
  } catch (err) {
    if (err.response) {
      if (err.response.status === 401) {
        errorMessage.value = err.response.data.message || 'Invalid email or password.'
      } else if (err.response.status === 422) {
        Object.assign(errors, err.response.data.errors || {})
        errorMessage.value = err.response.data.message || 'Validation error.'
      } else {
        errorMessage.value = `Server error (${err.response.status}). Please try again.`
      }
    } else {
      errorMessage.value = 'Network error. Please ensure the backend server is running.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.login-card {
  border-radius: 16px;
  width: 100%;
  max-width: 440px;
  padding: 2.25rem;
  border: 1px solid black;
}

.login-header {
  text-align: center;
  margin-bottom: 2rem;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.demo-credentials {
  margin-top: 1.75rem;
  padding: 1rem;
  border: 1px solid black;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.btn {
    border: 1px solid black;
    cursor: pointer;
}
</style>
