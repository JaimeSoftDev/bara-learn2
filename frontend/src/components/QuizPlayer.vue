<script setup lang="ts">
import { ref, watch } from 'vue'
import { api } from '@/api/client'
import { useToastStore } from '@/stores/toast'
import { apiErrorMessage } from '@/composables/useApiError'

interface TakeOption {
  id: number
  option_text: string
}

interface TakeQuestion {
  id: number
  question: string
  options: TakeOption[]
}

interface QuizTake {
  id: number
  title: string
  passing_score: number
  passed: boolean
  best_attempt: { score: number; passed: boolean; submitted_at: string } | null
  questions: TakeQuestion[]
}

interface AttemptAnswer {
  question_id: number
  question: string
  selected_option_id: number | null
  is_correct: boolean
  correct_option_id: number | null
  options: { id: number; option_text: string; is_correct: boolean }[]
}

interface AttemptResult {
  id: number
  score: number
  passed: boolean
  answers: AttemptAnswer[]
}

const props = defineProps<{ courseSlug: string; quizId: number }>()
const emit = defineEmits<{ graded: [passed: boolean, certificateIssued: boolean] }>()

const toast = useToastStore()
const loading = ref(true)
const submitting = ref(false)
const quiz = ref<QuizTake | null>(null)
const result = ref<AttemptResult | null>(null)
const answers = ref<Record<number, number | null>>({})

async function load() {
  loading.value = true
  result.value = null
  try {
    const { data } = await api.get(`/courses/${props.courseSlug}/quizzes/${props.quizId}/take`)
    quiz.value = data.data
    answers.value = Object.fromEntries((quiz.value?.questions ?? []).map((q) => [q.id, null]))
  } finally {
    loading.value = false
  }
}

watch(() => props.quizId, load, { immediate: true })

async function submit() {
  if (!quiz.value) return
  const unanswered = quiz.value.questions.some((q) => answers.value[q.id] == null)
  if (unanswered && !confirm('Tienes preguntas sin responder. ¿Enviar de todas formas?')) return

  submitting.value = true
  try {
    const payload = {
      answers: Object.entries(answers.value).map(([questionId, optionId]) => ({
        question_id: Number(questionId),
        option_id: optionId,
      })),
    }
    const { data } = await api.post(`/courses/${props.courseSlug}/quizzes/${props.quizId}/attempts`, payload)
    result.value = data.data
    emit('graded', data.data.passed, data.certificate_issued)
    if (data.certificate_issued) {
      toast.success('🎉 ¡Enhorabuena! Has completado el curso y tu certificado ya está disponible.')
    } else if (data.data.passed) {
      toast.success('¡Examen aprobado!')
    } else {
      toast.error('No has alcanzado la nota mínima. Puedes volver a intentarlo.')
    }
  } catch (e) {
    toast.error(apiErrorMessage(e))
  } finally {
    submitting.value = false
  }
}

function retake() {
  result.value = null
  answers.value = Object.fromEntries((quiz.value?.questions ?? []).map((q) => [q.id, null]))
}
</script>

<template>
  <div v-if="loading" class="text-gray-400 text-sm py-8 text-center">Cargando examen...</div>
  <div v-else-if="quiz" class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold">{{ quiz.title }}</h1>
      <span class="text-xs text-gray-500">Aprobado ≥ {{ quiz.passing_score }}%</span>
    </div>

    <div v-if="quiz.best_attempt" class="text-sm rounded-lg px-3 py-2 border" :class="quiz.passed ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-amber-50 border-amber-200 text-amber-700'">
      Tu mejor intento: {{ quiz.best_attempt.score }}% — {{ quiz.passed ? 'Aprobado' : 'No aprobado todavía' }}
    </div>

    <div v-if="!result" class="space-y-5">
      <div v-for="(question, qi) in quiz.questions" :key="question.id" class="border border-gray-200 rounded-xl p-4">
        <p class="font-semibold text-sm mb-3">{{ qi + 1 }}. {{ question.question }}</p>
        <div class="space-y-2">
          <label
            v-for="option in question.options"
            :key="option.id"
            class="flex items-center gap-2 text-sm border rounded-lg px-3 py-2 cursor-pointer"
            :class="answers[question.id] === option.id ? 'border-brand-600 bg-brand-50' : 'border-gray-200 hover:bg-gray-50'"
          >
            <input
              type="radio"
              :name="`question-${question.id}`"
              :value="option.id"
              v-model="answers[question.id]"
            />
            {{ option.option_text }}
          </label>
        </div>
      </div>

      <button
        :disabled="submitting"
        class="bg-brand-600 text-white font-semibold px-5 py-2.5 rounded-lg hover:bg-brand-700 disabled:opacity-50"
        @click="submit"
      >
        Enviar respuestas
      </button>
    </div>

    <div v-else class="space-y-5">
      <div
        class="rounded-lg px-4 py-3 border text-sm font-semibold"
        :class="result.passed ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-red-50 border-red-200 text-red-700'"
      >
        Nota: {{ result.score }}% — {{ result.passed ? 'Examen aprobado' : 'Examen no aprobado' }}
      </div>

      <div v-for="(answer, qi) in result.answers" :key="answer.question_id" class="border border-gray-200 rounded-xl p-4">
        <p class="font-semibold text-sm mb-3">{{ qi + 1 }}. {{ answer.question }}</p>
        <div class="space-y-2">
          <div
            v-for="option in answer.options"
            :key="option.id"
            class="flex items-center gap-2 text-sm border rounded-lg px-3 py-2"
            :class="[
              option.is_correct ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : '',
              !option.is_correct && option.id === answer.selected_option_id ? 'border-red-300 bg-red-50 text-red-700' : '',
              !option.is_correct && option.id !== answer.selected_option_id ? 'border-gray-200 text-gray-600' : '',
            ]"
          >
            <span>{{ option.is_correct ? '✓' : option.id === answer.selected_option_id ? '✕' : '·' }}</span>
            {{ option.option_text }}
            <span v-if="option.id === answer.selected_option_id" class="text-xs ml-auto">Tu respuesta</span>
          </div>
        </div>
      </div>

      <button class="text-sm font-semibold text-brand-600 hover:text-brand-700" @click="retake">
        Volver a intentarlo
      </button>
    </div>
  </div>
</template>
