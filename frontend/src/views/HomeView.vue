<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api } from '@/api/client'
import CourseCard from '@/components/CourseCard.vue'
import type { Category, CourseSummary } from '@/types'

const featured = ref<CourseSummary[]>([])
const categories = ref<Category[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    const [coursesRes, categoriesRes] = await Promise.all([
      api.get('/courses', { params: { per_page: 8, sort: 'newest' } }),
      api.get('/categories'),
    ])
    featured.value = coursesRes.data.data
    categories.value = categoriesRes.data
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <section class="bg-gradient-to-br from-brand-700 to-brand-900 text-white">
      <div class="max-w-7xl mx-auto px-4 py-20 flex flex-col items-center text-center gap-6">
        <h1 class="text-4xl md:text-5xl font-extrabold max-w-3xl leading-tight">
          Aprende de profesores reales, a tu propio ritmo
        </h1>
        <p class="text-lg text-brand-100 max-w-2xl">
          Vídeos de YouTube seleccionados por el profesor, acompañados de material escrito, ejercicios y
          certificados. Todo en un mismo lugar.
        </p>
        <div class="flex gap-3">
          <RouterLink
            to="/courses"
            class="bg-white text-brand-700 font-semibold px-6 py-3 rounded-full hover:bg-brand-50"
          >
            Explorar cursos
          </RouterLink>
          <RouterLink
            to="/register"
            class="border border-white/60 text-white font-semibold px-6 py-3 rounded-full hover:bg-white/10"
          >
            Enseña en Bara Learn
          </RouterLink>
        </div>
      </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 py-12">
      <h2 class="text-xl font-bold mb-4">Explora por categoría</h2>
      <div class="flex flex-wrap gap-3">
        <RouterLink
          v-for="category in categories"
          :key="category.id"
          :to="{ name: 'courses', query: { category: category.slug } }"
          class="px-4 py-2 rounded-full border border-gray-200 text-sm font-medium text-gray-700 hover:border-brand-400 hover:text-brand-700"
        >
          {{ category.name }}
        </RouterLink>
      </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 py-8">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold">Cursos destacados</h2>
        <RouterLink to="/courses" class="text-sm font-medium text-brand-600 hover:text-brand-700">
          Ver todos →
        </RouterLink>
      </div>
      <div v-if="loading" class="text-gray-400">Cargando cursos...</div>
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <CourseCard v-for="course in featured" :key="course.id" :course="course" />
      </div>
    </section>
  </div>
</template>
