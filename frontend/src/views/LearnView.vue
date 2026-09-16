<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/api/client'
import { useToastStore } from '@/stores/toast'
import { apiErrorMessage } from '@/composables/useApiError'
import YoutubePlayer from '@/components/YoutubePlayer.vue'
import QuizPlayer from '@/components/QuizPlayer.vue'
import type { Course, Lesson, Question, QuizSummary } from '@/types'

const props = defineProps<{ slug: string }>()
const router = useRouter()
const toast = useToastStore()

const course = ref<Course | null>(null)
const activeLesson = ref<Lesson | null>(null)
const activeQuiz = ref<QuizSummary | null>(null)
const loading = ref(true)
const marking = ref(false)
const questions = ref<Question[]>([])
const newQuestion = ref({ title: '', body: '' })
const newAnswer = ref<Record<number, string>>({})
const tab = ref<'content' | 'qa'>('content')

const allLessons = computed(() => course.value?.sections.flatMap((s) => s.lessons) ?? [])
const progressPercent = computed(() => {
  const lessons = allLessons.value
  if (!lessons.length) return 0
  return Math.round((lessons.filter((l) => l.completed).length / lessons.length) * 100)
})

async function load() {
  loading.value = true
  try {
    const { data } = await api.get(`/courses/${props.slug}`)
    course.value = data.data

    if (!course.value?.is_enrolled && !course.value?.is_owner) {
      toast.error('Debes inscribirte en el curso para acceder al contenido.')
      router.replace({ name: 'course-detail', params: { slug: props.slug } })
      return
    }

    const firstIncomplete = allLessons.value.find((l) => !l.completed) ?? allLessons.value[0]
    if (firstIncomplete) await selectLesson(firstIncomplete)
  } finally {
    loading.value = false
  }
}

onMounted(load)

async function selectLesson(lesson: Lesson) {
  activeQuiz.value = null
  activeLesson.value = lesson
  tab.value = 'content'
  if (lesson.locked) return
  const { data } = await api.get(`/lessons/${lesson.id}/questions`)
  questions.value = data.data
}

function selectQuiz(quiz: QuizSummary) {
  activeLesson.value = null
  activeQuiz.value = quiz
}

async function refreshCourse() {
  const { data } = await api.get(`/courses/${props.slug}`)
  course.value = data.data
}

function onQuizGraded() {
  refreshCourse()
}

async function toggleComplete() {
  if (!activeLesson.value) return
  marking.value = true
  try {
    if (activeLesson.value.completed) {
      await api.delete(`/lessons/${activeLesson.value.id}/complete`)
      activeLesson.value.completed = false
    } else {
      const { data } = await api.post(`/lessons/${activeLesson.value.id}/complete`)
      activeLesson.value.completed = true
      if (data.certificate_issued) {
        toast.success('🎉 ¡Enhorabuena! Has completado el curso y tu certificado ya está disponible.')
      }
    }
  } catch (e) {
    toast.error(apiErrorMessage(e))
  } finally {
    marking.value = false
  }
}

async function onVideoEnded() {
  if (activeLesson.value && !activeLesson.value.completed) {
    await toggleComplete()
  }
}

function goToNextLesson() {
  const lessons = allLessons.value
  const idx = lessons.findIndex((l) => l.id === activeLesson.value?.id)
  const next = idx >= 0 ? lessons[idx + 1] : undefined
  if (next) {
    selectLesson(next)
  }
}

async function submitQuestion() {
  if (!activeLesson.value) return
  try {
    await api.post(`/lessons/${activeLesson.value.id}/questions`, newQuestion.value)
    newQuestion.value = { title: '', body: '' }
    const { data } = await api.get(`/lessons/${activeLesson.value.id}/questions`)
    questions.value = data.data
    toast.success('Pregunta publicada.')
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}

async function submitAnswer(questionId: number) {
  const body = newAnswer.value[questionId]
  if (!body) return
  try {
    await api.post(`/questions/${questionId}/answers`, { body })
    newAnswer.value[questionId] = ''
    if (activeLesson.value) {
      const { data } = await api.get(`/lessons/${activeLesson.value.id}/questions`)
      questions.value = data.data
    }
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}
</script>

<template>
  <div v-if="loading" class="text-center py-20 text-gray-400">Cargando curso...</div>
  <div v-else-if="course" class="flex flex-col lg:flex-row min-h-[calc(100vh-4rem)]">
    <div v-if="activeQuiz" class="flex-1 min-w-0 bg-white p-6">
      <button class="text-xs text-gray-500 hover:text-gray-800 mb-4" @click="activeQuiz = null">
        ← Volver a las lecciones
      </button>
      <QuizPlayer :course-slug="course.slug" :quiz-id="activeQuiz.id" @graded="onQuizGraded" />
    </div>
    <div v-else class="flex-1 min-w-0 bg-black">
      <YoutubePlayer
        v-if="activeLesson?.youtube_video_id"
        :key="activeLesson.id"
        :video-id="activeLesson.youtube_video_id"
        @ended="onVideoEnded"
      />
      <div v-else class="aspect-video flex items-center justify-center text-gray-400">Selecciona una lección</div>

      <div class="bg-white p-6">
        <div class="flex items-center justify-between gap-4 mb-4">
          <h1 class="text-xl font-bold">{{ activeLesson?.title }}</h1>
          <div class="flex gap-2 shrink-0">
            <button
              :disabled="marking"
              class="text-sm font-semibold px-4 py-2 rounded-lg border"
              :class="activeLesson?.completed ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'border-gray-300 hover:bg-gray-50'"
              @click="toggleComplete"
            >
              {{ activeLesson?.completed ? '✓ Completada' : 'Marcar como completada' }}
            </button>
            <button
              class="text-sm font-semibold px-4 py-2 rounded-lg bg-brand-600 text-white hover:bg-brand-700"
              @click="goToNextLesson"
            >
              Siguiente →
            </button>
          </div>
        </div>

        <div class="flex gap-4 border-b border-gray-200 mb-4">
          <button
            class="pb-2 text-sm font-medium"
            :class="tab === 'content' ? 'border-b-2 border-brand-600 text-brand-700' : 'text-gray-500'"
            @click="tab = 'content'"
          >
            Contenido de la lección
          </button>
          <button
            class="pb-2 text-sm font-medium"
            :class="tab === 'qa' ? 'border-b-2 border-brand-600 text-brand-700' : 'text-gray-500'"
            @click="tab = 'qa'"
          >
            Preguntas y respuestas ({{ questions.length }})
          </button>
        </div>

        <div v-if="tab === 'content'" class="prose-content" v-html="activeLesson?.content || '<p>Sin contenido adicional para esta lección.</p>'" />

        <div v-else class="space-y-6">
          <form class="border border-gray-200 rounded-lg p-4 space-y-2" @submit.prevent="submitQuestion">
            <input
              v-model="newQuestion.title"
              required
              placeholder="Título de tu pregunta"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
            />
            <textarea
              v-model="newQuestion.body"
              required
              rows="3"
              placeholder="Escribe tu pregunta con detalle..."
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
            />
            <button class="bg-brand-600 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-brand-700">
              Publicar pregunta
            </button>
          </form>

          <div v-for="question in questions" :key="question.id" class="border-b border-gray-100 pb-4">
            <p class="font-semibold text-sm">{{ question.title }}</p>
            <p class="text-xs text-gray-400 mb-1">{{ question.user.name }}</p>
            <p class="text-sm text-gray-700 mb-2">{{ question.body }}</p>

            <div v-for="answer in question.answers" :key="answer.id" class="ml-4 mt-2 bg-gray-50 rounded-lg p-3">
              <p class="text-xs font-semibold text-gray-700">
                {{ answer.user.name }}
                <span v-if="answer.is_instructor_answer" class="text-brand-600">· Instructor/a</span>
              </p>
              <p class="text-sm text-gray-600">{{ answer.body }}</p>
            </div>

            <form class="ml-4 mt-2 flex gap-2" @submit.prevent="submitAnswer(question.id)">
              <input
                v-model="newAnswer[question.id]"
                placeholder="Escribe una respuesta..."
                class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm"
              />
              <button class="text-sm font-medium text-brand-600 hover:text-brand-700">Responder</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <aside class="lg:w-96 shrink-0 bg-white border-l border-gray-200 flex flex-col">
      <div class="p-4 border-b border-gray-200">
        <RouterLink
          :to="{ name: 'course-detail', params: { slug: course.slug } }"
          class="text-xs text-gray-500 hover:text-gray-800"
        >
          ← Volver a la página del curso
        </RouterLink>
        <h2 class="font-bold mt-1">{{ course.title }}</h2>
        <div class="w-full bg-gray-100 rounded-full h-2 mt-3">
          <div class="bg-brand-600 h-2 rounded-full transition-all" :style="{ width: `${progressPercent}%` }" />
        </div>
        <p class="text-xs text-gray-500 mt-1">{{ progressPercent }}% completado</p>
      </div>

      <div class="overflow-y-auto flex-1">
        <div v-for="section in course.sections" :key="section.id">
          <p class="px-4 py-2 bg-gray-50 text-sm font-semibold sticky top-0">{{ section.title }}</p>
          <button
            v-for="lesson in section.lessons"
            :key="lesson.id"
            class="w-full text-left px-4 py-3 flex items-center gap-3 border-b border-gray-100 hover:bg-gray-50"
            :class="activeLesson?.id === lesson.id ? 'bg-brand-50' : ''"
            @click="selectLesson(lesson)"
          >
            <span
              class="w-5 h-5 rounded-full border flex items-center justify-center text-xs shrink-0"
              :class="lesson.completed ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-gray-300 text-transparent'"
            >
              ✓
            </span>
            <span class="text-sm flex-1" :class="lesson.locked ? 'text-gray-400' : 'text-gray-800'">
              {{ lesson.title }}
            </span>
            <span v-if="lesson.locked">🔒</span>
          </button>
          <button
            v-if="section.quiz"
            class="w-full text-left px-4 py-3 flex items-center gap-3 border-b border-gray-100 hover:bg-gray-50"
            :class="activeQuiz?.id === section.quiz.id ? 'bg-brand-50' : ''"
            @click="selectQuiz(section.quiz)"
          >
            <span
              class="w-5 h-5 rounded-full border flex items-center justify-center text-xs shrink-0"
              :class="section.quiz.passed ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-gray-300 text-transparent'"
            >
              ✓
            </span>
            <span class="text-sm flex-1 text-gray-800">📝 {{ section.quiz.title }}</span>
          </button>
        </div>

        <div v-if="course.final_exam">
          <p class="px-4 py-2 bg-gray-50 text-sm font-semibold sticky top-0">Examen final</p>
          <button
            class="w-full text-left px-4 py-3 flex items-center gap-3 border-b border-gray-100 hover:bg-gray-50"
            :class="activeQuiz?.id === course.final_exam.id ? 'bg-brand-50' : ''"
            @click="selectQuiz(course.final_exam)"
          >
            <span
              class="w-5 h-5 rounded-full border flex items-center justify-center text-xs shrink-0"
              :class="course.final_exam.passed ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-gray-300 text-transparent'"
            >
              ✓
            </span>
            <span class="text-sm flex-1 text-gray-800">📝 {{ course.final_exam.title }}</span>
          </button>
        </div>
      </div>
    </aside>
  </div>
</template>
