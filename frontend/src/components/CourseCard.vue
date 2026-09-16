<script setup lang="ts">
import { RouterLink } from 'vue-router'
import StarRating from './StarRating.vue'
import type { CourseSummary } from '@/types'

defineProps<{ course: CourseSummary }>()

const levelLabels: Record<string, string> = {
  beginner: 'Principiante',
  intermediate: 'Intermedio',
  advanced: 'Avanzado',
}
</script>

<template>
  <RouterLink
    :to="{ name: 'course-detail', params: { slug: course.slug } }"
    class="group flex flex-col rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm hover:shadow-md transition-shadow"
  >
    <div class="aspect-video bg-gray-100 overflow-hidden">
      <img
        v-if="course.thumbnail_url"
        :src="course.thumbnail_url"
        :alt="course.title"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
      />
    </div>
    <div class="flex flex-col flex-1 p-4 gap-1.5">
      <span v-if="course.category" class="text-xs font-medium text-brand-600">{{ course.category.name }}</span>
      <h3 class="font-semibold text-gray-900 line-clamp-2 leading-snug">{{ course.title }}</h3>
      <p class="text-sm text-gray-500">{{ course.teacher.name }}</p>
      <div class="flex items-center gap-1.5 text-sm">
        <span class="font-semibold text-amber-600">{{ course.average_rating.toFixed(1) }}</span>
        <StarRating :model-value="course.average_rating" size="w-3.5 h-3.5" />
        <span class="text-gray-400">({{ course.reviews_count }})</span>
      </div>
      <div class="flex items-center justify-between mt-auto pt-2">
        <span class="text-xs bg-gray-100 text-gray-600 rounded-full px-2 py-0.5">{{ levelLabels[course.level] }}</span>
        <span class="font-bold text-gray-900">
          {{ course.is_free ? 'Gratis' : `${course.price.toFixed(2)} €` }}
        </span>
      </div>
    </div>
  </RouterLink>
</template>
