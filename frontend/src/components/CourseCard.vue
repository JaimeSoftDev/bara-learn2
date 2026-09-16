<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { initialsFor, paletteForId } from '@/composables/useMonogram'
import type { CourseSummary } from '@/types'

const props = defineProps<{ course: CourseSummary }>()

const colors = computed(() => paletteForId(props.course.category?.id ?? props.course.id))
const initials = computed(() => initialsFor(props.course.title))
</script>

<template>
  <RouterLink
    :to="{ name: 'course-detail', params: { slug: course.slug } }"
    class="group flex flex-col card overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all"
  >
    <div
      class="aspect-video flex items-center justify-center bg-[repeating-linear-gradient(135deg,rgba(0,0,0,0.03)_0px,rgba(0,0,0,0.03)_1px,transparent_1px,transparent_10px)]"
      :style="{ backgroundColor: colors.bg }"
    >
      <span class="font-display text-4xl font-bold" :style="{ color: colors.fg }">{{ initials }}</span>
    </div>
    <div class="flex flex-col flex-1 p-5 gap-1.5">
      <span v-if="course.category" class="eyebrow text-ink-900/40">{{ course.category.name }}</span>
      <h3 class="font-display font-semibold text-lg text-ink-900 leading-snug line-clamp-2">
        {{ course.title }}
      </h3>
      <p v-if="course.subtitle" class="text-sm text-ink-900/55 line-clamp-2">{{ course.subtitle }}</p>
      <div class="flex items-center justify-between mt-3 pt-3 border-t border-ink-900/10">
        <span class="text-sm font-medium text-accent-600 group-hover:text-accent-500">Ver curso →</span>
        <span class="font-semibold text-ink-900 text-sm">
          {{ course.is_free ? 'Gratis' : `${course.price.toFixed(2)} €` }}
        </span>
      </div>
    </div>
  </RouterLink>
</template>
