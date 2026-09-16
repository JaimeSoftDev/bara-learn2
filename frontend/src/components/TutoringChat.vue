<script setup lang="ts">
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { api } from '@/api/client'
import { useToastStore } from '@/stores/toast'
import { apiErrorMessage } from '@/composables/useApiError'
import type { TutoringThread } from '@/types'

const props = defineProps<{ courseSlug: string }>()

const toast = useToastStore()
const loading = ref(true)
const sending = ref(false)
const thread = ref<TutoringThread | null>(null)
const body = ref('')
const scrollEl = ref<HTMLElement | null>(null)

let pollTimer: ReturnType<typeof setInterval> | null = null

async function load(silent = false) {
  if (!silent) loading.value = true
  try {
    const { data } = await api.get(`/courses/${props.courseSlug}/tutoring`)
    thread.value = data.data
    await nextTick()
    scrollToBottom()
  } finally {
    loading.value = false
  }
}

function scrollToBottom() {
  if (scrollEl.value) scrollEl.value.scrollTop = scrollEl.value.scrollHeight
}

async function send() {
  if (!body.value.trim()) return
  sending.value = true
  try {
    await api.post(`/courses/${props.courseSlug}/tutoring/messages`, { body: body.value })
    body.value = ''
    await load(true)
  } catch (e) {
    toast.error(apiErrorMessage(e))
  } finally {
    sending.value = false
  }
}

onMounted(() => {
  load()
  pollTimer = setInterval(() => load(true), 8000)
})

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
})

watch(() => props.courseSlug, () => load())
</script>

<template>
  <div v-if="loading" class="text-gray-400 text-sm py-8 text-center">Cargando tutorías...</div>
  <div v-else-if="thread" class="flex flex-col h-[70vh]">
    <div class="flex items-center justify-between mb-3">
      <h1 class="text-xl font-bold">Tutorías con el profesor</h1>
      <span
        class="text-xs px-2 py-1 rounded-full"
        :class="thread.questions_remaining > 0 ? 'bg-brand-50 text-brand-700' : 'bg-amber-50 text-amber-700'"
      >
        {{ thread.questions_used }} / {{ thread.questions_limit }} preguntas usadas
      </span>
    </div>

    <div ref="scrollEl" class="flex-1 overflow-y-auto border border-gray-200 rounded-xl p-4 space-y-3 bg-gray-50">
      <p v-if="thread.messages.length === 0" class="text-sm text-gray-400 text-center py-8">
        Aún no has escrito a tu profesor. Tienes {{ thread.questions_remaining }} preguntas incluidas en este curso.
      </p>
      <div v-for="message in thread.messages" :key="message.id" class="flex" :class="message.is_mine ? 'justify-end' : 'justify-start'">
        <div
          class="max-w-[75%] rounded-2xl px-4 py-2 text-sm"
          :class="message.is_mine ? 'bg-brand-600 text-white rounded-br-sm' : 'bg-white border border-gray-200 text-gray-800 rounded-bl-sm'"
        >
          <p v-if="!message.is_mine" class="text-xs font-semibold mb-0.5 text-brand-600">{{ message.sender.name }}</p>
          <p class="whitespace-pre-wrap">{{ message.body }}</p>
          <p class="text-[10px] mt-1 opacity-70">{{ new Date(message.created_at).toLocaleString('es-ES') }}</p>
        </div>
      </div>
    </div>

    <form v-if="thread.questions_remaining > 0" class="mt-3 flex gap-2" @submit.prevent="send">
      <textarea
        v-model="body"
        rows="2"
        placeholder="Escribe tu pregunta al profesor..."
        class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none"
        @keydown.enter.exact.prevent="send"
      />
      <button
        :disabled="sending || !body.trim()"
        class="bg-brand-600 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-brand-700 disabled:opacity-50 self-end"
      >
        Enviar
      </button>
    </form>
    <p v-else class="mt-3 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
      Has usado tus {{ thread.questions_limit }} preguntas incluidas en este curso. Puedes seguir leyendo las
      respuestas del profesor; si necesitas hacer más preguntas, contacta con él para que te amplíe el cupo.
    </p>
  </div>
</template>
