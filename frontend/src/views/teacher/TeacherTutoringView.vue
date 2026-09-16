<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { api } from '@/api/client'
import { useToastStore } from '@/stores/toast'
import { apiErrorMessage } from '@/composables/useApiError'
import type { TutoringThread } from '@/types'

const toast = useToastStore()
const threads = ref<TutoringThread[]>([])
const selected = ref<TutoringThread | null>(null)
const loading = ref(true)
const loadingThread = ref(false)
const sending = ref(false)
const granting = ref(false)
const body = ref('')

let pollTimer: ReturnType<typeof setInterval> | null = null

async function loadThreads(silent = false) {
  if (!silent) loading.value = true
  try {
    const { data } = await api.get('/teacher/tutoring')
    threads.value = data.data
  } finally {
    loading.value = false
  }
}

async function selectThread(thread: TutoringThread) {
  loadingThread.value = true
  try {
    const { data } = await api.get(`/teacher/tutoring/${thread.id}`)
    selected.value = data.data
    const inList = threads.value.find((t) => t.id === thread.id)
    if (inList) inList.unread_count = 0
  } finally {
    loadingThread.value = false
  }
}

async function send() {
  if (!selected.value || !body.value.trim()) return
  sending.value = true
  try {
    await api.post(`/teacher/tutoring/${selected.value.id}/messages`, { body: body.value })
    body.value = ''
    await selectThread(selected.value)
    await loadThreads(true)
  } catch (e) {
    toast.error(apiErrorMessage(e))
  } finally {
    sending.value = false
  }
}

async function grantExtra() {
  if (!selected.value) return
  const input = prompt('¿Cuántas preguntas extra quieres conceder a este alumno?', '5')
  if (!input) return
  const amount = parseInt(input, 10)
  if (!amount || amount < 1) return

  granting.value = true
  try {
    const { data } = await api.post(`/teacher/tutoring/${selected.value.id}/grant`, { amount })
    selected.value = { ...selected.value, ...data.data }
    toast.success(`Se han concedido ${amount} preguntas extra.`)
    await loadThreads(true)
  } catch (e) {
    toast.error(apiErrorMessage(e))
  } finally {
    granting.value = false
  }
}

onMounted(() => {
  loadThreads()
  pollTimer = setInterval(() => loadThreads(true), 15000)
})

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
})
</script>

<template>
  <div>
    <h1 class="text-xl font-bold mb-6">Tutorías</h1>

    <div v-if="loading" class="text-gray-400">Cargando...</div>
    <div v-else-if="threads.length === 0" class="text-gray-500">
      Todavía no tienes conversaciones de tutoría con tus alumnos.
    </div>
    <div v-else class="flex gap-6 h-[70vh]">
      <div class="w-80 shrink-0 border border-gray-200 rounded-xl bg-white overflow-y-auto">
        <button
          v-for="thread in threads"
          :key="thread.id"
          class="w-full text-left px-4 py-3 border-b border-gray-100 hover:bg-gray-50"
          :class="selected?.id === thread.id ? 'bg-brand-50' : ''"
          @click="selectThread(thread)"
        >
          <div class="flex items-center justify-between gap-2">
            <p class="text-sm font-semibold truncate">{{ thread.student.name }}</p>
            <span v-if="thread.unread_count > 0" class="text-[10px] bg-brand-600 text-white rounded-full px-1.5 py-0.5 shrink-0">
              {{ thread.unread_count }}
            </span>
          </div>
          <p class="text-xs text-gray-500 truncate">{{ thread.course.title }}</p>
          <p class="text-xs text-gray-400 truncate mt-0.5">
            {{ thread.last_message?.body ?? 'Sin mensajes todavía' }}
          </p>
        </button>
      </div>

      <div class="flex-1 border border-gray-200 rounded-xl bg-white flex flex-col overflow-hidden">
        <div v-if="!selected" class="flex-1 flex items-center justify-center text-gray-400 text-sm">
          Selecciona una conversación
        </div>
        <template v-else>
          <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <div>
              <p class="font-semibold text-sm">{{ selected.student.name }}</p>
              <p class="text-xs text-gray-500">{{ selected.course.title }}</p>
            </div>
            <div class="flex items-center gap-3">
              <span class="text-xs text-gray-500">{{ selected.questions_used }} / {{ selected.questions_limit }} preguntas usadas</span>
              <button
                :disabled="granting"
                class="text-xs font-medium text-brand-600 hover:text-brand-700 border border-brand-200 rounded-lg px-2 py-1 disabled:opacity-50"
                @click="grantExtra"
              >
                + Conceder preguntas
              </button>
            </div>
          </div>

          <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
            <div v-if="loadingThread" class="text-center text-gray-400 text-sm py-8">Cargando...</div>
            <template v-else>
              <div
                v-for="message in selected.messages"
                :key="message.id"
                class="flex"
                :class="message.is_mine ? 'justify-end' : 'justify-start'"
              >
                <div
                  class="max-w-[75%] rounded-2xl px-4 py-2 text-sm"
                  :class="message.is_mine ? 'bg-brand-600 text-white rounded-br-sm' : 'bg-white border border-gray-200 text-gray-800 rounded-bl-sm'"
                >
                  <p class="whitespace-pre-wrap">{{ message.body }}</p>
                  <p class="text-[10px] mt-1 opacity-70">{{ new Date(message.created_at).toLocaleString('es-ES') }}</p>
                </div>
              </div>
            </template>
          </div>

          <form class="p-3 border-t border-gray-200 flex gap-2" @submit.prevent="send">
            <textarea
              v-model="body"
              rows="2"
              placeholder="Escribe una respuesta..."
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
        </template>
      </div>
    </div>
  </div>
</template>
