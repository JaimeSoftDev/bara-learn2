<script setup lang="ts">
import { onMounted, ref } from 'vue'
import axios from 'axios'

const props = defineProps<{ code?: string }>()

const code = ref(props.code ?? '')
const result = ref<null | { valid: boolean; student_name?: string; course_title?: string; issued_at?: string }>(null)
const loading = ref(false)

async function verify() {
  if (!code.value) return
  loading.value = true
  result.value = null
  try {
    const baseURL = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'
    const { data } = await axios.get(`${baseURL}/api/certificates/verify/${code.value}`)
    result.value = data
  } catch {
    result.value = { valid: false }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (code.value) verify()
})
</script>

<template>
  <div class="max-w-md mx-auto px-4 py-16">
    <h1 class="text-2xl font-bold mb-1">Verificar certificado</h1>
    <p class="text-gray-500 mb-6 text-sm">Introduce el código de verificación que aparece en el certificado.</p>

    <form class="flex gap-2 mb-6" @submit.prevent="verify">
      <input v-model="code" required class="flex-1 border border-gray-300 rounded-lg px-3 py-2" placeholder="Código del certificado" />
      <button :disabled="loading" class="bg-brand-600 text-white font-semibold px-5 py-2 rounded-lg hover:bg-brand-700">
        Verificar
      </button>
    </form>

    <div v-if="result?.valid" class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-sm text-emerald-800">
      <p class="font-semibold mb-1">✓ Certificado válido</p>
      <p>Alumno/a: {{ result.student_name }}</p>
      <p>Curso: {{ result.course_title }}</p>
      <p>Emitido: {{ new Date(result.issued_at!).toLocaleDateString() }}</p>
    </div>
    <div v-else-if="result && !result.valid" class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700">
      No hemos encontrado ningún certificado con ese código.
    </div>
  </div>
</template>
