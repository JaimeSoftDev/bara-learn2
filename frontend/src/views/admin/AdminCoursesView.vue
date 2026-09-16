<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api } from '@/api/client'
import { useToastStore } from '@/stores/toast'
import { apiErrorMessage } from '@/composables/useApiError'
import type { CourseSummary } from '@/types'

const courses = ref<CourseSummary[]>([])
const loading = ref(true)
const toast = useToastStore()

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/courses')
    courses.value = data.data
  } finally {
    loading.value = false
  }
}

onMounted(load)

async function remove(course: CourseSummary) {
  if (!confirm(`¿Eliminar el curso "${course.title}"?`)) return
  try {
    await api.delete(`/admin/courses/${course.id}`)
    toast.success('Curso eliminado.')
    load()
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}
</script>

<template>
  <div>
    <h1 class="text-xl font-bold mb-6">Todos los cursos</h1>
    <div v-if="loading" class="text-gray-400">Cargando...</div>
    <table v-else class="w-full text-sm bg-white border border-gray-200 rounded-xl overflow-hidden">
      <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
        <tr>
          <th class="text-left px-4 py-3">Curso</th>
          <th class="text-left px-4 py-3">Profesor</th>
          <th class="text-left px-4 py-3">Estado</th>
          <th class="text-left px-4 py-3">Alumnos</th>
          <th class="text-left px-4 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        <tr v-for="course in courses" :key="course.id">
          <td class="px-4 py-3 font-medium">
            <RouterLink :to="{ name: 'course-detail', params: { slug: course.slug } }" class="hover:text-brand-600">
              {{ course.title }}
            </RouterLink>
          </td>
          <td class="px-4 py-3 text-gray-500">{{ course.teacher.name }}</td>
          <td class="px-4 py-3">
            <span
              class="text-xs px-2 py-0.5 rounded-full"
              :class="course.status === 'published' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
            >
              {{ course.status === 'published' ? 'Publicado' : 'Borrador' }}
            </span>
          </td>
          <td class="px-4 py-3 text-gray-500">{{ course.students_count }}</td>
          <td class="px-4 py-3 text-right">
            <button class="text-red-600 hover:text-red-700 text-sm font-medium" @click="remove(course)">Eliminar</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
