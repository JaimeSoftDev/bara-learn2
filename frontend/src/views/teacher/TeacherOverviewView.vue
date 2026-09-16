<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api } from '@/api/client'

interface Overview {
  courses_count: number
  published_courses_count: number
  students_count: number
  revenue_stripe: number
  revenue_cash: number
  top_courses: { id: number; title: string; slug: string; students: number }[]
}

const overview = ref<Overview | null>(null)
const loading = ref(true)

onMounted(async () => {
  try {
    const { data } = await api.get('/teacher/overview')
    overview.value = data
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl font-bold">Resumen</h1>
      <RouterLink
        :to="{ name: 'teacher-course-new' }"
        class="bg-brand-600 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-brand-700"
      >
        + Crear curso
      </RouterLink>
    </div>

    <div v-if="loading" class="text-gray-400">Cargando...</div>
    <template v-else-if="overview">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white border border-gray-200 rounded-xl p-4">
          <p class="text-xs text-gray-400">Cursos publicados</p>
          <p class="text-2xl font-bold">{{ overview.published_courses_count }} / {{ overview.courses_count }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
          <p class="text-xs text-gray-400">Alumnos totales</p>
          <p class="text-2xl font-bold">{{ overview.students_count }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
          <p class="text-xs text-gray-400">Ingresos online (Stripe)</p>
          <p class="text-2xl font-bold">{{ overview.revenue_stripe.toFixed(2) }} €</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
          <p class="text-xs text-gray-400">Ingresos en efectivo</p>
          <p class="text-2xl font-bold">{{ overview.revenue_cash.toFixed(2) }} €</p>
        </div>
      </div>

      <h2 class="font-bold mb-3">Cursos con más alumnos</h2>
      <div class="bg-white border border-gray-200 rounded-xl divide-y divide-gray-100">
        <RouterLink
          v-for="course in overview.top_courses"
          :key="course.id"
          :to="{ name: 'teacher-course-students', params: { slug: course.slug } }"
          class="flex items-center justify-between px-4 py-3 hover:bg-gray-50"
        >
          <span class="text-sm font-medium">{{ course.title }}</span>
          <span class="text-sm text-gray-500">{{ course.students }} alumnos</span>
        </RouterLink>
        <p v-if="overview.top_courses.length === 0" class="px-4 py-6 text-sm text-gray-400">
          Todavía no tienes cursos con alumnos inscritos.
        </p>
      </div>
    </template>
  </div>
</template>
