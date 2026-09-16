<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api } from '@/api/client'
import CourseCard from '@/components/CourseCard.vue'
import type { CourseSummary } from '@/types'

const courses = ref<CourseSummary[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    const { data } = await api.get('/my/wishlist')
    courses.value = data.data
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <h1 class="text-xl font-bold mb-6">Mis favoritos</h1>
    <div v-if="loading" class="text-gray-400">Cargando...</div>
    <div v-else-if="courses.length === 0" class="text-gray-500">
      Todavía no has añadido cursos a favoritos.
    </div>
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-6">
      <CourseCard v-for="course in courses" :key="course.id" :course="course" />
    </div>
  </div>
</template>
