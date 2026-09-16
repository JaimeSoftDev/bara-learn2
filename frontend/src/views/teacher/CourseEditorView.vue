<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/api/client'
import { useToastStore } from '@/stores/toast'
import { apiErrorMessage } from '@/composables/useApiError'
import RichTextEditor from '@/components/RichTextEditor.vue'
import QuizEditorCard from '@/components/teacher/QuizEditorCard.vue'
import type { Category, Course, Quiz, Section } from '@/types'

const props = defineProps<{ slug: string }>()
const router = useRouter()
const toast = useToastStore()

const course = ref<Course | null>(null)
const categories = ref<Category[]>([])
const quizzes = ref<Quiz[]>([])
const loading = ref(true)
const savingDetails = ref(false)
const tab = ref<'details' | 'curriculum' | 'quizzes'>('details')

const details = ref({
  title: '',
  subtitle: '',
  description: '',
  category_id: '' as number | string,
  level: 'beginner',
  price: 0,
  thumbnail_url: '',
  requirements: [] as string[],
  what_you_will_learn: [] as string[],
})

const newSectionTitle = ref('')
const lessonForms = ref<Record<number, { title: string; youtube_url: string; content: string; is_preview: boolean }>>({})
const addingLessonTo = ref<number | null>(null)

async function load() {
  loading.value = true
  try {
    const [courseRes, categoriesRes] = await Promise.all([
      api.get(`/courses/${props.slug}`),
      api.get('/categories'),
    ])
    course.value = courseRes.data.data
    categories.value = categoriesRes.data
    if (course.value) {
      details.value = {
        title: course.value.title,
        subtitle: course.value.subtitle ?? '',
        description: course.value.description ?? '',
        category_id: course.value.category?.id ?? '',
        level: course.value.level,
        price: course.value.price,
        thumbnail_url: course.value.thumbnail_url ?? '',
        requirements: course.value.requirements?.length ? [...course.value.requirements] : [''],
        what_you_will_learn: course.value.what_you_will_learn?.length ? [...course.value.what_you_will_learn] : [''],
      }
    }
  } finally {
    loading.value = false
  }
}

async function loadQuizzes() {
  if (!course.value) return
  const { data } = await api.get(`/courses/${course.value.slug}/quizzes`)
  quizzes.value = data.data
}

function finalQuiz(): Quiz | null {
  return quizzes.value.find((q) => q.is_final_exam) ?? null
}

function sectionQuiz(sectionId: number): Quiz | null {
  return quizzes.value.find((q) => q.section_id === sectionId) ?? null
}

onMounted(async () => {
  await load()
  await loadQuizzes()
})

async function saveDetails() {
  if (!course.value) return
  savingDetails.value = true
  try {
    const payload = {
      ...details.value,
      requirements: details.value.requirements.filter((r) => r.trim()),
      what_you_will_learn: details.value.what_you_will_learn.filter((r) => r.trim()),
    }
    const { data } = await api.put(`/courses/${course.value.slug}`, payload)
    course.value = { ...course.value, ...data.data }
    toast.success('Cambios guardados.')
    if (data.data.slug !== props.slug) {
      router.replace({ name: 'teacher-course-edit', params: { slug: data.data.slug } })
    }
  } catch (e) {
    toast.error(apiErrorMessage(e))
  } finally {
    savingDetails.value = false
  }
}

async function togglePublish() {
  if (!course.value) return
  const newStatus = course.value.status === 'published' ? 'draft' : 'published'
  try {
    const { data } = await api.put(`/courses/${course.value.slug}`, { status: newStatus })
    course.value = { ...course.value, ...data.data }
    toast.success(newStatus === 'published' ? 'Curso publicado.' : 'Curso pasado a borrador.')
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}

async function addSection() {
  if (!course.value || !newSectionTitle.value.trim()) return
  try {
    await api.post(`/courses/${course.value.slug}/sections`, { title: newSectionTitle.value })
    newSectionTitle.value = ''
    await load()
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}

async function renameSection(section: Section) {
  const title = prompt('Nuevo título de la sección', section.title)
  if (!title || !course.value) return
  await api.put(`/courses/${course.value.slug}/sections/${section.id}`, { title })
  await load()
}

async function deleteSection(section: Section) {
  if (!course.value || !confirm(`¿Eliminar la sección "${section.title}" y todas sus lecciones?`)) return
  await api.delete(`/courses/${course.value.slug}/sections/${section.id}`)
  await load()
}

function startAddLesson(sectionId: number) {
  addingLessonTo.value = sectionId
  lessonForms.value[sectionId] = { title: '', youtube_url: '', content: '', is_preview: false }
}

async function submitLesson(sectionId: number) {
  if (!course.value) return
  const form = lessonForms.value[sectionId]
  try {
    await api.post(`/courses/${course.value.slug}/sections/${sectionId}/lessons`, form)
    addingLessonTo.value = null
    toast.success('Lección añadida.')
    await load()
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}

async function deleteLesson(sectionId: number, lessonId: number) {
  if (!course.value || !confirm('¿Eliminar esta lección?')) return
  await api.delete(`/courses/${course.value.slug}/sections/${sectionId}/lessons/${lessonId}`)
  await load()
}
</script>

<template>
  <div v-if="loading" class="text-gray-400">Cargando...</div>
  <div v-else-if="course">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-xl font-bold">{{ course.title }}</h1>
        <span
          class="text-xs px-2 py-0.5 rounded-full"
          :class="course.status === 'published' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
        >
          {{ course.status === 'published' ? 'Publicado' : 'Borrador' }}
        </span>
      </div>
      <div class="flex gap-2">
        <RouterLink
          :to="{ name: 'teacher-course-students', params: { slug: course.slug } }"
          class="text-sm font-medium text-gray-600 border border-gray-300 rounded-lg px-3 py-2 hover:bg-gray-50"
        >
          Alumnos
        </RouterLink>
        <button
          class="text-sm font-semibold px-4 py-2 rounded-lg"
          :class="course.status === 'published' ? 'bg-gray-100 text-gray-700' : 'bg-brand-600 text-white'"
          @click="togglePublish"
        >
          {{ course.status === 'published' ? 'Pasar a borrador' : 'Publicar curso' }}
        </button>
      </div>
    </div>

    <div class="flex gap-4 border-b border-gray-200 mb-6">
      <button
        class="pb-2 text-sm font-medium"
        :class="tab === 'details' ? 'border-b-2 border-brand-600 text-brand-700' : 'text-gray-500'"
        @click="tab = 'details'"
      >
        Detalles del curso
      </button>
      <button
        class="pb-2 text-sm font-medium"
        :class="tab === 'curriculum' ? 'border-b-2 border-brand-600 text-brand-700' : 'text-gray-500'"
        @click="tab = 'curriculum'"
      >
        Contenido ({{ course.lessons_count }} lecciones)
      </button>
      <button
        class="pb-2 text-sm font-medium"
        :class="tab === 'quizzes' ? 'border-b-2 border-brand-600 text-brand-700' : 'text-gray-500'"
        @click="tab = 'quizzes'"
      >
        Exámenes
      </button>
    </div>

    <div v-if="tab === 'details'" class="max-w-2xl space-y-4 bg-white border border-gray-200 rounded-xl p-6">
      <div>
        <label class="text-sm font-medium text-gray-700">Título</label>
        <input v-model="details.title" class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1" />
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700">Subtítulo</label>
        <input v-model="details.subtitle" class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1" />
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium text-gray-700">Categoría</label>
          <select v-model="details.category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1">
            <option value="">Sin categoría</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700">Nivel</label>
          <select v-model="details.level" class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1">
            <option value="beginner">Principiante</option>
            <option value="intermediate">Intermedio</option>
            <option value="advanced">Avanzado</option>
          </select>
        </div>
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700">URL de la miniatura</label>
        <input v-model="details.thumbnail_url" class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1" />
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700">Precio (€) — 0 para curso gratuito</label>
        <input v-model.number="details.price" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1" />
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700 block mb-1">Descripción</label>
        <RichTextEditor v-model="details.description" placeholder="Describe tu curso en detalle..." />
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700 block mb-1">Lo que aprenderán</label>
        <div v-for="(item, i) in details.what_you_will_learn" :key="i" class="flex gap-2 mb-2">
          <input v-model="details.what_you_will_learn[i]" class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
          <button type="button" class="text-red-500 text-sm" @click="details.what_you_will_learn.splice(i, 1)">✕</button>
        </div>
        <button type="button" class="text-sm text-brand-600 font-medium" @click="details.what_you_will_learn.push('')">
          + Añadir punto
        </button>
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700 block mb-1">Requisitos</label>
        <div v-for="(item, i) in details.requirements" :key="i" class="flex gap-2 mb-2">
          <input v-model="details.requirements[i]" class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm" />
          <button type="button" class="text-red-500 text-sm" @click="details.requirements.splice(i, 1)">✕</button>
        </div>
        <button type="button" class="text-sm text-brand-600 font-medium" @click="details.requirements.push('')">
          + Añadir requisito
        </button>
      </div>
      <button
        :disabled="savingDetails"
        class="bg-brand-600 text-white font-semibold px-5 py-2.5 rounded-lg hover:bg-brand-700 disabled:opacity-50"
        @click="saveDetails"
      >
        Guardar cambios
      </button>
    </div>

    <div v-else-if="tab === 'curriculum'" class="max-w-3xl space-y-6">
      <div v-for="section in course.sections" :key="section.id" class="border border-gray-200 rounded-xl bg-white overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 bg-gray-50">
          <p class="font-semibold text-sm">{{ section.title }}</p>
          <div class="flex gap-3">
            <button class="text-xs text-gray-500 hover:text-brand-600" @click="renameSection(section)">Renombrar</button>
            <button class="text-xs text-red-500 hover:text-red-700" @click="deleteSection(section)">Eliminar</button>
          </div>
        </div>
        <ul class="divide-y divide-gray-100">
          <li v-for="lesson in section.lessons" :key="lesson.id" class="flex items-center justify-between px-4 py-2.5 text-sm">
            <span>
              {{ lesson.title }}
              <span v-if="lesson.is_preview" class="text-xs text-brand-600 ml-1">(vista previa)</span>
            </span>
            <button class="text-xs text-red-500 hover:text-red-700" @click="deleteLesson(section.id, lesson.id)">
              Eliminar
            </button>
          </li>
        </ul>

        <div class="p-4 border-t border-gray-100">
          <button
            v-if="addingLessonTo !== section.id"
            class="text-sm font-medium text-brand-600 hover:text-brand-700"
            @click="startAddLesson(section.id)"
          >
            + Añadir lección
          </button>
          <form v-else class="space-y-3" @submit.prevent="submitLesson(section.id)">
            <input
              v-model="lessonForms[section.id]!.title"
              required
              placeholder="Título de la lección"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
            />
            <input
              v-model="lessonForms[section.id]!.youtube_url"
              required
              placeholder="URL del vídeo de YouTube (https://www.youtube.com/watch?v=...)"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
            />
            <RichTextEditor v-model="lessonForms[section.id]!.content" placeholder="Contenido escrito que acompaña al vídeo..." />
            <label class="flex items-center gap-2 text-sm text-gray-600">
              <input v-model="lessonForms[section.id]!.is_preview" type="checkbox" />
              Permitir vista previa gratuita (visible sin inscribirse)
            </label>
            <div class="flex gap-2">
              <button class="bg-brand-600 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-brand-700">
                Guardar lección
              </button>
              <button type="button" class="text-sm text-gray-500" @click="addingLessonTo = null">Cancelar</button>
            </div>
          </form>
        </div>
      </div>

      <div class="border border-dashed border-gray-300 rounded-xl p-4 flex gap-2">
        <input
          v-model="newSectionTitle"
          placeholder="Título de la nueva sección"
          class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm"
          @keyup.enter="addSection"
        />
        <button class="bg-gray-900 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-gray-800" @click="addSection">
          + Añadir sección
        </button>
      </div>
    </div>

    <div v-else-if="tab === 'quizzes'" class="max-w-3xl space-y-6">
      <p class="text-sm text-gray-500">
        Los exámenes son de tipo test y se corrigen automáticamente. Aprobar los exámenes de sección y el examen final es
        necesario para que el alumno obtenga el certificado del curso; no bloquean el avance de las lecciones.
      </p>

      <QuizEditorCard
        :course-slug="course.slug"
        :quiz="finalQuiz()"
        :section-id="null"
        label="Examen final del curso"
        @changed="loadQuizzes"
      />

      <div v-if="course.sections.length" class="space-y-4">
        <p class="text-sm font-semibold text-gray-700">Exámenes por sección</p>
        <QuizEditorCard
          v-for="section in course.sections"
          :key="section.id"
          :course-slug="course.slug"
          :quiz="sectionQuiz(section.id)"
          :section-id="section.id"
          :label="`Examen de: ${section.title}`"
          @changed="loadQuizzes"
        />
      </div>
      <p v-else class="text-sm text-gray-400">Añade secciones en la pestaña "Contenido" para poder crear exámenes por sección.</p>
    </div>
  </div>
</template>
