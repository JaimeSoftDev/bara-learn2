<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/api/client'
import { useToastStore } from '@/stores/toast'
import { apiErrorMessage } from '@/composables/useApiError'
import type { Category } from '@/types'

const router = useRouter()
const toast = useToastStore()
const categories = ref<Category[]>([])
const saving = ref(false)

const form = ref({
  title: '',
  subtitle: '',
  category_id: '',
  level: 'beginner',
  price: 0,
  thumbnail_url: '',
});

onMounted(async () => {
  const { data } = await api.get('/categories')
  categories.value = data
})

async function submit() {
  saving.value = true
  try {
    const { data } = await api.post('/courses', form.value)
    toast.success('Curso creado. Ahora añade el contenido.')
    router.push({ name: 'teacher-course-edit', params: { slug: data.data.slug } })
  } catch (e) {
    toast.error(apiErrorMessage(e))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="max-w-2xl">
    <h1 class="text-xl font-bold mb-6">Crear nuevo curso</h1>
    <form class="space-y-4 bg-white border border-gray-200 rounded-xl p-6" @submit.prevent="submit">
      <div>
        <label class="text-sm font-medium text-gray-700">Título del curso</label>
        <input v-model="form.title" required class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1" />
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700">Subtítulo</label>
        <input v-model="form.subtitle" class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1" />
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium text-gray-700">Categoría</label>
          <select v-model="form.category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1">
            <option value="">Sin categoría</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700">Nivel</label>
          <select v-model="form.level" class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1">
            <option value="beginner">Principiante</option>
            <option value="intermediate">Intermedio</option>
            <option value="advanced">Avanzado</option>
          </select>
        </div>
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700">URL de la miniatura</label>
        <input v-model="form.thumbnail_url" placeholder="https://..." class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1" />
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700">Precio (€) — pon 0 para un curso gratuito</label>
        <input v-model.number="form.price" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1" />
      </div>
      <button :disabled="saving" class="bg-brand-600 text-white font-semibold px-5 py-2.5 rounded-lg hover:bg-brand-700 disabled:opacity-50">
        Crear curso y continuar
      </button>
    </form>
  </div>
</template>
