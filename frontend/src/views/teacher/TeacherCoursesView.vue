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
    const { data } = await api.get('/my/courses')
    courses.value = data.data
  } finally {
    loading.value = false
  }
}

onMounted(load)

async function remove(course: CourseSummary) {
  if (!confirm(`¿Eliminar el curso "${course.title}"? Esta acción no se puede deshacer.`)) return
  try {
    await api.delete(`/courses/${course.slug}`)
    toast.success('Curso eliminado.')
    load()
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl font-bold">Mis cursos</h1>
      <RouterLink
        :to="{ name: 'teacher-course-new' }"
        class="bg-brand-600 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-brand-700"
      >
        + Crear curso
      </RouterLink>
    </div>

    <div v-if="loading" class="text-gray-400">Cargando...</div>
    <div v-else-if="courses.length === 0" class="text-gray-500">Todavía no has creado ningún curso.</div>
    <div v-else class="space-y-3">
      <div
        v-for="course in courses"
        :key="course.id"
        class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border border-gray-200 rounded-xl bg-white p-4"
      >
        <div class="flex items-center gap-4 min-w-0">
          <img
            v-if="course.thumbnail_url"
            :src="course.thumbnail_url"
            class="w-24 aspect-video object-cover rounded-lg shrink-0"
          />
          <div class="min-w-0">
            <p class="font-semibold truncate">{{ course.title }}</p>
            <span
              class="text-xs px-2 py-0.5 rounded-full"
              :class="course.status === 'published' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
            >
              {{ course.status === 'published' ? 'Publicado' : 'Borrador' }}
            </span>
            <span class="text-xs text-gray-400 ml-2">{{ course.students_count }} alumnos</span>
          </div>
        </div>
        <div class="flex gap-2 shrink-0">
          <RouterLink
            :to="{ name: 'teacher-course-edit', params: { slug: course.slug } }"
            class="text-sm font-medium text-gray-600 hover:text-brand-600 border border-gray-300 rounded-lg px-3 py-1.5"
          >
            Editar
          </RouterLink>
          <RouterLink
            :to="{ name: 'teacher-course-students', params: { slug: course.slug } }"
            class="text-sm font-medium text-gray-600 hover:text-brand-600 border border-gray-300 rounded-lg px-3 py-1.5"
          >
            Alumnos
          </RouterLink>
          <button
            class="text-sm font-medium text-red-600 hover:text-red-700 border border-red-200 rounded-lg px-3 py-1.5"
            @click="remove(course)"
          >
            Eliminar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
