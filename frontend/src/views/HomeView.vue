<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api } from '@/api/client'
import CourseCard from '@/components/CourseCard.vue'
import DnaHelix from '@/components/DnaHelix.vue'
import type { CourseSummary } from '@/types'

const featured = ref<CourseSummary[]>([])
const loading = ref(true)

const testimonials = [
  {
    quote:
      'Por fin entendí la genética de poblaciones. Las explicaciones son claras y los ejercicios, muy parecidos a los de mi facultad.',
    name: 'Lucía Fernández',
    role: '3.º de Biología · Univ. de Valencia',
  },
  {
    quote:
      'Llegué sin saber casi nada de biología molecular y terminé montando mi propio protocolo de PCR para el laboratorio.',
    name: 'Marcos Iglesias',
    role: '2.º de Bioquímica · Univ. Complutense',
  },
  {
    quote:
      'El curso de genética mendeliana me salvó el cuatrimestre. Vídeos cortos, apuntes claros y ejercicios con solución.',
    name: 'Nora Ibáñez',
    role: '1.º de Medicina · Univ. de Sevilla',
  },
]

onMounted(async () => {
  try {
    const { data } = await api.get('/courses', { params: { per_page: 4, sort: 'newest' } })
    featured.value = data.data
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <!-- Hero -->
    <section class="max-w-7xl mx-auto px-4 pt-16 pb-20 grid grid-cols-1 lg:grid-cols-2 gap-14 items-center">
      <div>
        <p class="eyebrow text-accent-600 mb-4">Plataforma de genética · Nivel universitario</p>
        <h1 class="font-display text-4xl sm:text-5xl font-semibold text-ink-900 leading-[1.1] mb-6">
          Aprende <span class="italic text-brand-600">genética</span> como se estudia en un
          laboratorio de verdad.
        </h1>
        <p class="text-ink-900/60 text-lg mb-8 max-w-lg">
          Cursos online rigurosos para estudiantes de grado — de las leyes de Mendel a la edición
          con CRISPR. Avanza a tu ritmo, con acceso de por vida.
        </p>
        <div class="flex flex-wrap gap-3">
          <RouterLink to="/register" class="btn-primary">Crear cuenta gratis</RouterLink>
          <RouterLink to="/courses" class="btn-outline">Explorar cursos</RouterLink>
        </div>
        <p class="eyebrow text-ink-900/35 mt-6">Gratis para empezar · Certificado al finalizar</p>
      </div>

      <DnaHelix />
    </section>

    <!-- Featured courses -->
    <section class="max-w-7xl mx-auto px-4 py-16">
      <p class="eyebrow text-brand-600 mb-2">Empieza por aquí</p>
      <div class="flex items-end justify-between mb-8">
        <h2 class="font-display text-3xl font-semibold text-ink-900">Cursos destacados</h2>
        <RouterLink to="/courses" class="text-sm font-medium text-accent-600 hover:text-accent-500">
          Ver todos los cursos →
        </RouterLink>
      </div>
      <div v-if="loading" class="text-ink-900/40">Cargando cursos...</div>
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <CourseCard v-for="course in featured" :key="course.id" :course="course" />
      </div>
    </section>

    <!-- Testimonials -->
    <section class="bg-ink-50/60 border-y border-ink-900/5">
      <div class="max-w-7xl mx-auto px-4 py-16">
        <h2 class="font-display text-3xl font-semibold text-ink-900 mb-10">
          Lo que dicen nuestros estudiantes
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div v-for="t in testimonials" :key="t.name" class="card p-6 flex flex-col">
            <span class="font-display text-4xl text-brand-300 leading-none mb-2">&ldquo;</span>
            <p class="text-ink-900/75 flex-1 mb-5">{{ t.quote }}</p>
            <div class="flex items-center gap-3">
              <span
                class="w-10 h-10 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-display font-semibold shrink-0"
              >
                {{ t.name.charAt(0) }}
              </span>
              <div>
                <p class="font-medium text-ink-900 text-sm">{{ t.name }}</p>
                <p class="text-xs text-ink-900/50">{{ t.role }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
