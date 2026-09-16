<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api } from '@/api/client'
import { useToastStore } from '@/stores/toast'
import { apiErrorMessage } from '@/composables/useApiError'
import type { Category } from '@/types'

const categories = ref<Category[]>([])
const loading = ref(true)
const newName = ref('')
const toast = useToastStore()

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/categories')
    categories.value = data
  } finally {
    loading.value = false
  }
}

onMounted(load)

async function create() {
  if (!newName.value.trim()) return
  try {
    await api.post('/categories', { name: newName.value })
    newName.value = ''
    toast.success('Categoría creada.')
    load()
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}

async function remove(category: Category) {
  if (!confirm(`¿Eliminar la categoría "${category.name}"?`)) return
  try {
    await api.delete(`/categories/${category.id}`)
    toast.success('Categoría eliminada.')
    load()
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}
</script>

<template>
  <div>
    <h1 class="text-xl font-bold mb-6">Categorías</h1>

    <form class="flex gap-2 mb-6 max-w-md" @submit.prevent="create">
      <input v-model="newName" placeholder="Nueva categoría" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm" />
      <button class="bg-brand-600 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-brand-700">Añadir</button>
    </form>

    <div v-if="loading" class="text-gray-400">Cargando...</div>
    <div v-else class="bg-white border border-gray-200 rounded-xl divide-y divide-gray-100 max-w-md">
      <div v-for="category in categories" :key="category.id" class="flex items-center justify-between px-4 py-3">
        <span class="text-sm font-medium">{{ category.name }}</span>
        <div class="flex items-center gap-3">
          <span class="text-xs text-gray-400">{{ category.courses_count ?? 0 }} cursos</span>
          <button class="text-xs text-red-500 hover:text-red-700" @click="remove(category)">Eliminar</button>
        </div>
      </div>
    </div>
  </div>
</template>
