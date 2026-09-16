<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { watchDebounced } from '@vueuse/core'
import { api } from '@/api/client'
import CourseCard from '@/components/CourseCard.vue'
import type { Category, CourseSummary, Paginated } from '@/types'

const route = useRoute()
const router = useRouter()

const categories = ref<Category[]>([])
const courses = ref<CourseSummary[]>([])
const meta = ref<Paginated<CourseSummary>['meta']>()
const loading = ref(true)

const filters = ref({
  search: (route.query.search as string) ?? '',
  category: (route.query.category as string) ?? '',
  level: (route.query.level as string) ?? '',
  price: (route.query.price as string) ?? '',
  sort: (route.query.sort as string) ?? 'newest',
  page: Number(route.query.page ?? 1),
})

async function fetchCourses() {
  loading.value = true
  try {
    const params: Record<string, string | number> = { per_page: 12, page: filters.value.page }
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.category) params.category = filters.value.category
    if (filters.value.level) params.level = filters.value.level
    if (filters.value.price) params.price = filters.value.price
    if (filters.value.sort) params.sort = filters.value.sort

    const { data } = await api.get('/courses', { params })
    courses.value = data.data
    meta.value = data.meta
  } finally {
    loading.value = false
  }
}

function updateQuery() {
  router.replace({
    query: Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v)) as Record<string, string>,
  })
}

watch(
  () => [filters.value.category, filters.value.level, filters.value.price, filters.value.sort, filters.value.page],
  () => {
    updateQuery()
    fetchCourses()
  },
)

watchDebounced(
  () => filters.value.search,
  () => {
    filters.value.page = 1
    updateQuery()
    fetchCourses()
  },
  { debounce: 400 },
)

onMounted(async () => {
  const { data } = await api.get('/categories')
  categories.value = data
  fetchCourses()
})
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Explora nuestros cursos</h1>

    <div class="flex flex-col md:flex-row gap-4 mb-8">
      <input
        v-model="filters.search"
        type="search"
        placeholder="¿Qué quieres aprender hoy?"
        class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500"
      />
      <select v-model="filters.category" class="border border-gray-300 rounded-lg px-3 py-2.5">
        <option value="">Todas las categorías</option>
        <option v-for="c in categories" :key="c.id" :value="c.slug">{{ c.name }}</option>
      </select>
      <select v-model="filters.level" class="border border-gray-300 rounded-lg px-3 py-2.5">
        <option value="">Cualquier nivel</option>
        <option value="beginner">Principiante</option>
        <option value="intermediate">Intermedio</option>
        <option value="advanced">Avanzado</option>
      </select>
      <select v-model="filters.price" class="border border-gray-300 rounded-lg px-3 py-2.5">
        <option value="">Cualquier precio</option>
        <option value="free">Gratis</option>
        <option value="paid">De pago</option>
      </select>
      <select v-model="filters.sort" class="border border-gray-300 rounded-lg px-3 py-2.5">
        <option value="newest">Más recientes</option>
        <option value="rating">Mejor valorados</option>
        <option value="price_asc">Precio: menor a mayor</option>
        <option value="price_desc">Precio: mayor a menor</option>
      </select>
    </div>

    <div v-if="loading" class="text-gray-400 py-12 text-center">Cargando cursos...</div>
    <div v-else-if="courses.length === 0" class="text-gray-400 py-12 text-center">
      No se encontraron cursos con esos filtros.
    </div>
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <CourseCard v-for="course in courses" :key="course.id" :course="course" />
    </div>

    <div v-if="meta && meta.last_page > 1" class="flex justify-center gap-2 mt-10">
      <button
        v-for="page in meta.last_page"
        :key="page"
        class="w-9 h-9 rounded-full text-sm font-medium"
        :class="page === filters.page ? 'bg-brand-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
        @click="filters.page = page"
      >
        {{ page }}
      </button>
    </div>
  </div>
</template>
