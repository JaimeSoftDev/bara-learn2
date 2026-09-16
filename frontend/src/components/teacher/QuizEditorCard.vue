<script setup lang="ts">
import { ref } from 'vue'
import { api } from '@/api/client'
import { useToastStore } from '@/stores/toast'
import { apiErrorMessage } from '@/composables/useApiError'
import type { Quiz, QuizQuestion } from '@/types'

const props = defineProps<{
  courseSlug: string
  quiz: Quiz | null
  sectionId: number | null
  label: string
}>()

const emit = defineEmits<{ changed: [] }>()

const toast = useToastStore()

const creating = ref(false)
const createForm = ref({ title: '', passing_score: 70 })

const editingMeta = ref(false)
const metaForm = ref({ title: '', passing_score: 70 })

const addingQuestion = ref(false)
const editingQuestionId = ref<number | null>(null)
const questionForm = ref<{ question: string; options: { option_text: string; is_correct: boolean }[] }>({
  question: '',
  options: [
    { option_text: '', is_correct: true },
    { option_text: '', is_correct: false },
  ],
})
const savingQuestion = ref(false)

function startCreate() {
  creating.value = true
  createForm.value = { title: props.sectionId ? 'Examen de la sección' : 'Examen final', passing_score: 70 }
}

async function submitCreate() {
  try {
    await api.post(`/courses/${props.courseSlug}/quizzes`, {
      section_id: props.sectionId,
      title: createForm.value.title,
      passing_score: createForm.value.passing_score,
    })
    creating.value = false
    toast.success('Examen creado.')
    emit('changed')
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}

function startEditMeta() {
  if (!props.quiz) return
  metaForm.value = { title: props.quiz.title, passing_score: props.quiz.passing_score }
  editingMeta.value = true
}

async function submitMeta() {
  if (!props.quiz) return
  try {
    await api.put(`/courses/${props.courseSlug}/quizzes/${props.quiz.id}`, metaForm.value)
    editingMeta.value = false
    toast.success('Examen actualizado.')
    emit('changed')
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}

async function deleteQuiz() {
  if (!props.quiz || !confirm(`¿Eliminar "${props.quiz.title}" y todas sus preguntas?`)) return
  try {
    await api.delete(`/courses/${props.courseSlug}/quizzes/${props.quiz.id}`)
    toast.success('Examen eliminado.')
    emit('changed')
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}

function startAddQuestion() {
  editingQuestionId.value = null
  questionForm.value = {
    question: '',
    options: [
      { option_text: '', is_correct: true },
      { option_text: '', is_correct: false },
    ],
  }
  addingQuestion.value = true
}

function startEditQuestion(question: QuizQuestion) {
  editingQuestionId.value = question.id
  questionForm.value = {
    question: question.question,
    options: question.options.map((o) => ({ option_text: o.option_text, is_correct: !!o.is_correct })),
  }
  addingQuestion.value = true
}

function addOption() {
  if (questionForm.value.options.length < 8) {
    questionForm.value.options.push({ option_text: '', is_correct: false })
  }
}

function removeOption(index: number) {
  if (questionForm.value.options.length > 2) {
    questionForm.value.options.splice(index, 1)
  }
}

function markCorrect(index: number) {
  questionForm.value.options.forEach((o, i) => (o.is_correct = i === index))
}

async function submitQuestion() {
  if (!props.quiz) return
  savingQuestion.value = true
  try {
    const payload = { question: questionForm.value.question, options: questionForm.value.options }
    if (editingQuestionId.value) {
      await api.put(`/courses/${props.courseSlug}/quizzes/${props.quiz.id}/questions/${editingQuestionId.value}`, payload)
    } else {
      await api.post(`/courses/${props.courseSlug}/quizzes/${props.quiz.id}/questions`, payload)
    }
    addingQuestion.value = false
    toast.success('Pregunta guardada.')
    emit('changed')
  } catch (e) {
    toast.error(apiErrorMessage(e))
  } finally {
    savingQuestion.value = false
  }
}

async function deleteQuestion(questionId: number) {
  if (!props.quiz || !confirm('¿Eliminar esta pregunta?')) return
  try {
    await api.delete(`/courses/${props.courseSlug}/quizzes/${props.quiz.id}/questions/${questionId}`)
    toast.success('Pregunta eliminada.')
    emit('changed')
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}
</script>

<template>
  <div class="border border-gray-200 rounded-xl bg-white overflow-hidden">
    <div class="flex items-center justify-between px-4 py-3 bg-gray-50">
      <p class="font-semibold text-sm">{{ label }}</p>
      <span v-if="quiz" class="text-xs text-gray-500">{{ quiz.questions.length }} pregunta(s) · aprobado ≥ {{ quiz.passing_score }}%</span>
    </div>

    <div class="p-4" v-if="!quiz">
      <button v-if="!creating" class="text-sm font-medium text-brand-600 hover:text-brand-700" @click="startCreate">
        + Crear examen
      </button>
      <form v-else class="space-y-3" @submit.prevent="submitCreate">
        <input
          v-model="createForm.title"
          required
          placeholder="Título del examen"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
        />
        <div>
          <label class="text-xs text-gray-600">Nota mínima para aprobar (%)</label>
          <input
            v-model.number="createForm.passing_score"
            type="number"
            min="1"
            max="100"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mt-1"
          />
        </div>
        <div class="flex gap-2">
          <button class="bg-brand-600 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-brand-700">Crear</button>
          <button type="button" class="text-sm text-gray-500" @click="creating = false">Cancelar</button>
        </div>
      </form>
    </div>

    <div v-else class="p-4 space-y-4">
      <div v-if="editingMeta" class="border border-gray-200 rounded-lg p-3 space-y-2">
        <input v-model="metaForm.title" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
        <div>
          <label class="text-xs text-gray-600">Nota mínima para aprobar (%)</label>
          <input
            v-model.number="metaForm.passing_score"
            type="number"
            min="1"
            max="100"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mt-1"
          />
        </div>
        <div class="flex gap-2">
          <button class="bg-brand-600 text-white text-sm font-semibold px-3 py-1.5 rounded-lg hover:bg-brand-700" @click="submitMeta">
            Guardar
          </button>
          <button type="button" class="text-sm text-gray-500" @click="editingMeta = false">Cancelar</button>
        </div>
      </div>
      <div v-else class="flex gap-3">
        <button class="text-xs text-gray-500 hover:text-brand-600" @click="startEditMeta">Editar examen</button>
        <button class="text-xs text-red-500 hover:text-red-700" @click="deleteQuiz">Eliminar examen</button>
      </div>

      <ul class="space-y-2">
        <li v-for="question in quiz.questions" :key="question.id" class="border border-gray-100 rounded-lg p-3">
          <div class="flex items-start justify-between gap-2">
            <p class="text-sm font-medium">{{ question.question }}</p>
            <div class="flex gap-2 shrink-0">
              <button class="text-xs text-gray-500 hover:text-brand-600" @click="startEditQuestion(question)">Editar</button>
              <button class="text-xs text-red-500 hover:text-red-700" @click="deleteQuestion(question.id)">Eliminar</button>
            </div>
          </div>
          <ul class="mt-2 space-y-1">
            <li
              v-for="option in question.options"
              :key="option.id"
              class="text-xs px-2 py-1 rounded"
              :class="option.is_correct ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-gray-600'"
            >
              {{ option.is_correct ? '✓' : '·' }} {{ option.option_text }}
            </li>
          </ul>
        </li>
      </ul>

      <div v-if="!addingQuestion">
        <button class="text-sm font-medium text-brand-600 hover:text-brand-700" @click="startAddQuestion">+ Añadir pregunta</button>
      </div>
      <form v-else class="border border-dashed border-gray-300 rounded-lg p-3 space-y-3" @submit.prevent="submitQuestion">
        <input
          v-model="questionForm.question"
          required
          placeholder="Enunciado de la pregunta"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
        />
        <div class="space-y-2">
          <div v-for="(option, i) in questionForm.options" :key="i" class="flex items-center gap-2">
            <input
              type="radio"
              :name="'correct-option'"
              :checked="option.is_correct"
              @change="markCorrect(i)"
            />
            <input
              v-model="option.option_text"
              required
              placeholder="Texto de la opción"
              class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm"
            />
            <button
              v-if="questionForm.options.length > 2"
              type="button"
              class="text-red-500 text-sm"
              @click="removeOption(i)"
            >
              ✕
            </button>
          </div>
        </div>
        <button v-if="questionForm.options.length < 8" type="button" class="text-sm text-brand-600 font-medium" @click="addOption">
          + Añadir opción
        </button>
        <p class="text-xs text-gray-500">Marca con el botón de radio cuál es la opción correcta.</p>
        <div class="flex gap-2">
          <button :disabled="savingQuestion" class="bg-brand-600 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-brand-700 disabled:opacity-50">
            Guardar pregunta
          </button>
          <button type="button" class="text-sm text-gray-500" @click="addingQuestion = false">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</template>
