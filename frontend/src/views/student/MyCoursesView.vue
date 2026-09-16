<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api } from '@/api/client'
import type { Enrollment } from '@/types'

const enrollments = ref<Enrollment[]>([])
const loading = ref(true)

const sourceLabels: Record<string, string> = {
  stripe: 'Comprado online',
  cash: 'Pagado en efectivo',
  free: 'Curso gratuito',
}

onMounted(async () => {
  try {
    const { data } = await api.get('/my/enrollments')
    enrollments.value = data.data
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <h1 class="text-xl font-bold mb-6">Mis cursos</h1>

    <div v-if="loading" class="text-gray-400">Cargando...</div>
    <div v-else-if="enrollments.length === 0" class="text-gray-500">
      Todavía no te has inscrito en ningún curso.
      <RouterLink to="/courses" class="text-brand-600 font-medium hover:text-brand-700">Explora el catálogo →</RouterLink>
    </div>
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <RouterLink
        v-for="enrollment in enrollments"
        :key="enrollment.id"
        :to="{ name: 'learn', params: { slug: enrollment.course.slug } }"
        class="border border-gray-200 rounded-xl bg-white overflow-hidden hover:shadow-md transition-shadow"
      >
        <div class="aspect-video bg-gray-100">
          <img v-if="enrollment.course.thumbnail_url" :src="enrollment.course.thumbnail_url" class="w-full h-full object-cover" />
        </div>
        <div class="p-4">
          <p class="font-semibold text-gray-900 line-clamp-2">{{ enrollment.course.title }}</p>
          <p class="text-xs text-gray-400 mt-1">{{ sourceLabels[enrollment.source] }}</p>
          <div class="w-full bg-gray-100 rounded-full h-2 mt-3">
            <div class="bg-brand-600 h-2 rounded-full" :style="{ width: `${enrollment.progress_percent ?? 0}%` }" />
          </div>
          <p class="text-xs text-gray-500 mt-1">{{ enrollment.progress_percent ?? 0 }}% completado</p>
        </div>
      </RouterLink>
    </div>
  </div>
</template>
