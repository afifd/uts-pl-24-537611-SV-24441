<template>
  <div class="items-page">
    <Navbar />

    <main class="main-container">
      <div v-if="successMessage" class="alert alert-success">
        <span>{{ successMessage }}</span>
        <button @click="successMessage = ''">&times;</button>
      </div>
      <div v-if="errorMessage" class="alert alert-danger">
        <span>{{ errorMessage }}</span>
        <button @click="errorMessage = ''">&times;</button>
      </div>

      <div class="page-header">
        <div>
          <h2>Items Management</h2>
          <p>Manage store products, stock levels, and descriptions</p>
        </div>
        <button class="btn" @click="openCreateModal">
          + Add New Item
        </button>
      </div>

      <div class="toolbar">
        <div>
          <span>Total Items: {{ items.length }} | Total Units: {{ totalUnits }}</span>
        </div>
        <div style="width: 250px;">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search items..."
          />
        </div>
      </div>

      <div v-if="loading">
        <p>Loading items...</p>
      </div>

      <div v-else-if="filteredItems.length === 0">
        <p v-if="searchQuery">No items matched "{{ searchQuery }}".</p>
        <p v-else>No inventory items found.</p>
        <button v-if="!searchQuery" class="btn" @click="openCreateModal" style="margin-top: 0.5rem;">
          + Add First Item
        </button>
      </div>

      <table v-else>
        <thead>
          <tr>
            <th style="width: 60px;">ID</th>
            <th>Title</th>
            <th>Description</th>
            <th style="width: 100px;">Amount</th>
            <th style="width: 150px;">Created At</th>
            <th style="width: 140px; text-align: center;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in filteredItems" :key="item.id">
            <td>#{{ item.id }}</td>
            <td><strong>{{ item.title }}</strong></td>
            <td>{{ item.description || '-' }}</td>
            <td>{{ item.amount }} units</td>
            <td>{{ formatDate(item.created_at) }}</td>
            <td style="text-align: center;">
              <div class="actions">
                <button class="btn" @click="openEditModal(item)">Edit</button>
                <button class="btn btn-danger" @click="confirmDelete(item)">Delete</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </main>

    <!-- Create / Edit Modal -->
    <ItemModal
      v-if="showModal"
      :item="editingItem"
      @close="closeModal"
      @saved="handleItemSaved"
    />

    <!-- Delete Confirmation Modal -->
    <div v-if="deletingItem" class="modal-backdrop" @click.self="deletingItem = null">
      <div class="modal-card">
        <h3>Confirm Delete</h3>
        <p>Are you sure you want to delete <strong>"{{ deletingItem.title }}"</strong>?</p>
        <p>This action cannot be undone.</p>
        <div class="modal-footer">
          <button class="btn" @click="deletingItem = null">Cancel</button>
          <button class="btn btn-danger" :disabled="isDeleting" @click="handleDelete">
            {{ isDeleting ? 'Deleting...' : 'Yes, Delete' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Navbar from '../components/Navbar.vue'
import ItemModal from '../components/ItemModal.vue'
import apiClient from '../api/client'

const items = ref([])
const loading = ref(true)
const searchQuery = ref('')
const successMessage = ref('')
const errorMessage = ref('')

// Modal state
const showModal = ref(false)
const editingItem = ref(null)

// Delete state
const deletingItem = ref(null)
const isDeleting = ref(false)

const filteredItems = computed(() => {
  if (!searchQuery.value.trim()) return items.value
  const q = searchQuery.value.toLowerCase()
  return items.value.filter(
    (item) =>
      item.title.toLowerCase().includes(q) ||
      (item.description && item.description.toLowerCase().includes(q)),
  )
})

const totalUnits = computed(() => {
  return items.value.reduce((sum, item) => sum + (Number(item.amount) || 0), 0)
})

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('en-GB', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

async function fetchItems() {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await apiClient.get('/items')
    items.value = res.data.data || []
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to fetch items.'
  } finally {
    loading.value = false
  }
}

function openCreateModal() {
  editingItem.value = null
  showModal.value = true
}

function openEditModal(item) {
  editingItem.value = item
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingItem.value = null
}

function handleItemSaved(savedItem) {
  closeModal()
  if (editingItem.value) {
    const idx = items.value.findIndex((i) => i.id === savedItem.id)
    if (idx !== -1) {
      items.value[idx] = savedItem
    }
    showToast(`"${savedItem.title}" updated successfully.`)
  } else {
    items.value.unshift(savedItem)
    showToast(`"${savedItem.title}" created successfully.`)
  }
}

function confirmDelete(item) {
  deletingItem.value = item
}

async function handleDelete() {
  if (!deletingItem.value) return
  isDeleting.value = true
  const title = deletingItem.value.title
  try {
    await apiClient.delete(`/items/${deletingItem.value.id}`)
    items.value = items.value.filter((i) => i.id !== deletingItem.value.id)
    deletingItem.value = null
    showToast(`"${title}" deleted.`)
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to delete item.'
  } finally {
    isDeleting.value = false
  }
}

function showToast(msg) {
  successMessage.value = msg
  setTimeout(() => {
    if (successMessage.value === msg) {
      successMessage.value = ''
    }
  }, 3000)
}

onMounted(() => {
  fetchItems()
})
</script>

<style scoped>
.main-container {
  max-width: 1000px;
  margin: 1.5rem auto;
  padding: 0 1rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.toolbar {
  border: 1px solid black;
  border-radius: 8px;
  padding: 0.75rem 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.actions {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.modal-card {
  background: white;
  border: 1px solid black;
  border-radius: 8px;
  width: 100%;
  max-width: 400px;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 0.5rem;
}
</style>
