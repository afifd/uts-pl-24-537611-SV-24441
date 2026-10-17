<template>
  <div class="modal-backdrop" @click.self="$emit('close')">
    <div class="modal-card">
      <div class="modal-header">
        <h3>{{ isEdit ? 'Edit Item' : 'Add New Item' }}</h3>
        <button class="close-btn" @click="$emit('close')">&times;</button>
      </div>

      <form @submit.prevent="handleSubmit" class="modal-body">
        <div v-if="generalError" class="alert alert-danger">
          <span>{{ generalError }}</span>
        </div>

        <div class="form-group">
          <label for="item-title">Item Title *</label>
          <input
            id="item-title"
            v-model="form.title"
            type="text"
            placeholder="Item title..."
            maxlength="50"
            required
          />
          <div v-if="errors.title" class="invalid-feedback">
            {{ errors.title[0] }}
          </div>
          <small>{{ form.title.length }}/50</small>
        </div>

        <div class="form-group">
          <label for="item-description">Description (Optional)</label>
          <textarea
            id="item-description"
            v-model="form.description"
            rows="3"
            placeholder="Item description..."
          ></textarea>
          <div v-if="errors.description" class="invalid-feedback">
            {{ errors.description[0] }}
          </div>
        </div>

        <div class="form-group">
          <label for="item-amount">Amount *</label>
          <input
            id="item-amount"
            v-model.number="form.amount"
            type="number"
            min="0"
            placeholder="0"
            required
          />
          <div v-if="errors.amount" class="invalid-feedback">
            {{ errors.amount[0] }}
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn" @click="$emit('close')">
            Cancel
          </button>
          <button type="submit" class="btn" :disabled="submitting">
            {{ submitting ? 'Saving...' : (isEdit ? 'Update' : 'Create') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import apiClient from '../api/client'

const props = defineProps({
  item: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close', 'saved'])

const isEdit = computed(() => !!props.item)
const submitting = ref(false)
const generalError = ref('')
const errors = ref({})

const form = reactive({
  title: '',
  description: '',
  amount: 0,
})

onMounted(() => {
  if (props.item) {
    form.title = props.item.title || ''
    form.description = props.item.description || ''
    form.amount = props.item.amount !== undefined ? props.item.amount : 0
  }
})

async function handleSubmit() {
  submitting.value = true
  generalError.value = ''
  errors.value = {}

  try {
    let res
    if (isEdit.value) {
      res = await apiClient.put(`/items/${props.item.id}`, form)
    } else {
      res = await apiClient.post('/items', form)
    }
    emit('saved', res.data.data || res.data)
  } catch (err) {
    if (err.response && err.response.status === 422) {
      errors.value = err.response.data.errors || {}
      generalError.value = err.response.data.message || 'Validation failed.'
    } else {
      generalError.value = err.response?.data?.message || 'Failed to save item.'
    }
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
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
  max-width: 440px;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid black;
  padding-bottom: 0.5rem;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.4rem;
  cursor: pointer;
  line-height: 1;
}

.modal-body {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding-top: 0.75rem;
  border-top: 1px solid black;
}
</style>
