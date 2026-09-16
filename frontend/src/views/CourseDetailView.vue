<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { api } from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { apiErrorMessage } from '@/composables/useApiError'
import StarRating from '@/components/StarRating.vue'
import type { Course, Review } from '@/types'

const props = defineProps<{ slug: string }>()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()

const course = ref<Course | null>(null)
const reviews = ref<Review[]>([])
const loading = ref(true)
const enrolling = ref(false)
const wishlisted = ref(false)
const openSections = ref<Set<number>>(new Set())

const myReview = ref({ rating: 5, comment: '' })
const submittingReview = ref(false)

async function load() {
  loading.value = true
  try {
    const [courseRes, reviewsRes] = await Promise.all([
      api.get(`/courses/${props.slug}`),
      api.get(`/courses/${props.slug}/reviews`),
    ])
    course.value = courseRes.data.data
    reviews.value = reviewsRes.data.data
    if (course.value && course.value.sections.length > 0) {
      openSections.value.add(course.value.sections[0]!.id)
    }

    if (auth.isAuthenticated && course.value) {
      const { data } = await api.get('/my/wishlist')
      wishlisted.value = data.data.some((c: { id: number }) => c.id === course.value?.id)
    }
  } finally {
    loading.value = false
  }

  if (route.query.checkout === 'success') {
    toast.success('¡Pago recibido! Estamos confirmando tu inscripción...')
    setTimeout(load, 1500)
  } else if (route.query.checkout === 'cancelled') {
    toast.info('Has cancelado el proceso de pago.')
  }
}

onMounted(load)

const totalLessons = computed(() => course.value?.sections.flatMap((s) => s.lessons).length ?? 0)

function toggleSection(id: number) {
  if (openSections.value.has(id)) openSections.value.delete(id)
  else openSections.value.add(id)
}

function requireAuth(): boolean {
  if (!auth.isAuthenticated) {
    router.push({ name: 'login', query: { redirect: route.fullPath } })
    return false
  }
  return true
}

async function enrollFree() {
  if (!requireAuth() || !course.value) return
  enrolling.value = true
  try {
    await api.post(`/courses/${course.value.slug}/enroll`)
    toast.success('¡Te has inscrito correctamente!')
    router.push({ name: 'learn', params: { slug: course.value.slug } })
  } catch (e) {
    toast.error(apiErrorMessage(e))
  } finally {
    enrolling.value = false
  }
}

async function checkout() {
  if (!requireAuth() || !course.value) return
  enrolling.value = true
  try {
    const { data } = await api.post(`/courses/${course.value.slug}/checkout`)
    window.location.href = data.checkout_url
  } catch (e) {
    toast.error(apiErrorMessage(e))
  } finally {
    enrolling.value = false
  }
}

async function toggleWishlist() {
  if (!requireAuth() || !course.value) return
  const { data } = await api.post(`/courses/${course.value.slug}/wishlist`)
  wishlisted.value = data.wishlisted
  toast.success(wishlisted.value ? 'Añadido a favoritos.' : 'Eliminado de favoritos.')
}

async function submitReview() {
  if (!course.value) return
  submittingReview.value = true
  try {
    await api.post(`/courses/${course.value.slug}/reviews`, myReview.value)
    toast.success('¡Gracias por tu valoración!')
    const { data } = await api.get(`/courses/${props.slug}/reviews`)
    reviews.value = data.data
  } catch (e) {
    toast.error(apiErrorMessage(e))
  } finally {
    submittingReview.value = false
  }
}

function formatDuration(seconds: number | null) {
  if (!seconds) return ''
  const m = Math.round(seconds / 60)
  return `${m} min`
}
</script>

<template>
  <div v-if="loading" class="max-w-5xl mx-auto px-4 py-16 text-center text-gray-400">Cargando curso...</div>
  <div v-else-if="!course" class="max-w-5xl mx-auto px-4 py-16 text-center text-gray-400">Curso no encontrado.</div>
  <div v-else>
    <section class="bg-gray-900 text-white">
      <div class="max-w-7xl mx-auto px-4 py-10 grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
          <span v-if="course.category" class="text-brand-300 text-sm font-medium">{{ course.category.name }}</span>
          <h1 class="text-3xl font-extrabold mt-1">{{ course.title }}</h1>
          <p class="text-gray-300 mt-2">{{ course.subtitle }}</p>
          <div class="flex items-center gap-2 mt-4 text-sm">
            <span class="font-bold text-amber-400">{{ course.average_rating.toFixed(1) }}</span>
            <StarRating :model-value="course.average_rating" />
            <span class="text-gray-400">({{ course.reviews_count }} valoraciones)</span>
            <span class="text-gray-400">· {{ course.students_count }} alumnos</span>
          </div>
          <p class="text-sm text-gray-300 mt-3">
            Creado por <span class="font-semibold text-white">{{ course.teacher.name }}</span>
          </p>
        </div>

        <div class="bg-white text-gray-900 rounded-xl shadow-xl p-5 h-fit">
          <img
            v-if="course.thumbnail_url"
            :src="course.thumbnail_url"
            class="w-full aspect-video object-cover rounded-lg mb-4"
          />
          <p class="text-3xl font-extrabold mb-4">{{ course.is_free ? 'Gratis' : `${course.price.toFixed(2)} €` }}</p>

          <RouterLink
            v-if="course.is_enrolled || course.is_owner"
            :to="{ name: 'learn', params: { slug: course.slug } }"
            class="block text-center bg-brand-600 text-white font-semibold py-3 rounded-lg hover:bg-brand-700"
          >
            {{ course.is_owner ? 'Ver contenido' : 'Ir al curso' }}
          </RouterLink>
          <template v-else>
            <button
              v-if="course.is_free"
              :disabled="enrolling"
              class="w-full bg-brand-600 text-white font-semibold py-3 rounded-lg hover:bg-brand-700 disabled:opacity-50"
              @click="enrollFree"
            >
              Inscribirme gratis
            </button>
            <button
              v-else
              :disabled="enrolling"
              class="w-full bg-brand-600 text-white font-semibold py-3 rounded-lg hover:bg-brand-700 disabled:opacity-50"
              @click="checkout"
            >
              Comprar ahora
            </button>
            <p class="text-xs text-gray-400 mt-2 text-center">
              ¿Pagas en efectivo o en persona? Pide a tu profesor que te dé acceso directamente.
            </p>
          </template>

          <button class="w-full mt-3 border border-gray-300 rounded-lg py-2.5 text-sm font-medium hover:bg-gray-50" @click="toggleWishlist">
            {{ wishlisted ? '♥ En favoritos' : '♡ Añadir a favoritos' }}
          </button>

          <ul class="mt-5 text-sm text-gray-600 space-y-1">
            <li>{{ totalLessons }} lecciones en vídeo</li>
            <li>Contenido escrito en cada lección</li>
            <li>Acceso de por vida</li>
            <li>Certificado al completar el curso</li>
          </ul>
        </div>
      </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 py-10 grid grid-cols-1 lg:grid-cols-3 gap-10">
      <div class="lg:col-span-2 space-y-10">
        <div v-if="course.what_you_will_learn?.length">
          <h2 class="text-xl font-bold mb-3">Lo que aprenderás</h2>
          <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <li v-for="(item, i) in course.what_you_will_learn" :key="i" class="flex gap-2 text-sm text-gray-700">
              <span class="text-emerald-600">✓</span> {{ item }}
            </li>
          </ul>
        </div>

        <div>
          <h2 class="text-xl font-bold mb-3">Contenido del curso</h2>
          <p class="text-sm text-gray-500 mb-3">
            {{ course.sections.length }} secciones · {{ totalLessons }} lecciones
          </p>
          <div class="border border-gray-200 rounded-lg divide-y divide-gray-200 overflow-hidden">
            <div v-for="section in course.sections" :key="section.id">
              <button
                class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 text-left"
                @click="toggleSection(section.id)"
              >
                <span class="font-semibold text-sm">{{ section.title }}</span>
                <span class="text-xs text-gray-500">{{ section.lessons.length }} lecciones</span>
              </button>
              <ul v-if="openSections.has(section.id)">
                <li
                  v-for="lesson in section.lessons"
                  :key="lesson.id"
                  class="flex items-center justify-between px-4 py-2.5 text-sm border-t border-gray-100"
                >
                  <span class="flex items-center gap-2 text-gray-700">
                    <span v-if="lesson.locked">🔒</span>
                    <span v-else>▶</span>
                    {{ lesson.title }}
                    <span v-if="lesson.is_preview" class="text-xs text-brand-600 font-medium">(vista previa)</span>
                  </span>
                  <span class="text-gray-400 text-xs">{{ formatDuration(lesson.duration_seconds) }}</span>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <div v-if="course.requirements?.length">
          <h2 class="text-xl font-bold mb-3">Requisitos</h2>
          <ul class="list-disc pl-5 text-sm text-gray-700 space-y-1">
            <li v-for="(item, i) in course.requirements" :key="i">{{ item }}</li>
          </ul>
        </div>

        <div>
          <h2 class="text-xl font-bold mb-3">Descripción</h2>
          <div class="prose-content" v-html="course.description" />
        </div>

        <div>
          <h2 class="text-xl font-bold mb-3">Instructor</h2>
          <div class="flex gap-4 items-start">
            <div class="w-14 h-14 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center text-xl font-bold shrink-0">
              {{ course.teacher.name.charAt(0) }}
            </div>
            <div>
              <p class="font-semibold">{{ course.teacher.name }}</p>
              <p class="text-sm text-gray-500">{{ course.teacher.headline }}</p>
              <p class="text-sm text-gray-600 mt-2">{{ course.teacher.bio }}</p>
            </div>
          </div>
        </div>

        <div>
          <h2 class="text-xl font-bold mb-3">Valoraciones ({{ reviews.length }})</h2>

          <form v-if="course.is_enrolled" class="border border-gray-200 rounded-lg p-4 mb-6" @submit.prevent="submitReview">
            <p class="text-sm font-medium mb-2">Deja tu valoración</p>
            <StarRating v-model="myReview.rating" interactive size="w-6 h-6" />
            <textarea
              v-model="myReview.comment"
              rows="3"
              placeholder="Cuenta tu experiencia con este curso (opcional)"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-3 text-sm"
            />
            <button
              type="submit"
              :disabled="submittingReview"
              class="mt-2 bg-brand-600 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-brand-700 disabled:opacity-50"
            >
              Publicar valoración
            </button>
          </form>

          <div v-if="reviews.length === 0" class="text-sm text-gray-400">
            Este curso todavía no tiene valoraciones.
          </div>
          <div v-else class="space-y-4">
            <div v-for="review in reviews" :key="review.id" class="border-b border-gray-100 pb-4">
              <div class="flex items-center gap-2 mb-1">
                <span class="font-semibold text-sm">{{ review.user.name }}</span>
                <StarRating :model-value="review.rating" size="w-3.5 h-3.5" />
              </div>
              <p class="text-sm text-gray-600">{{ review.comment }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
