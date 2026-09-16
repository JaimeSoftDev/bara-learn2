<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api } from '@/api/client'
import { useToastStore } from '@/stores/toast'
import { apiErrorMessage } from '@/composables/useApiError'
import type { Enrollment } from '@/types'

const props = defineProps<{ slug: string }>()
const toast = useToastStore()

const enrollments = ref<Enrollment[]>([])
const loading = ref(true)
const granting = ref(false)
const courseTitle = ref('')

const grantForm = ref({ email: '', amount_paid: '', notes: '' })

const sourceLabels: Record<string, string> = {
  stripe: 'Stripe',
  cash: 'Efectivo',
  free: 'Gratuito',
}

async function load() {
  loading.value = true
  try {
    const [studentsRes, courseRes] = await Promise.all([
      api.get(`/courses/${props.slug}/students`),
      api.get(`/courses/${props.slug}`),
    ])
    enrollments.value = studentsRes.data.data
    courseTitle.value = courseRes.data.data.title
  } finally {
    loading.value = false
  }
}

onMounted(load)

async function grantCash() {
  granting.value = true
  try {
    const payload: Record<string, unknown> = { email: grantForm.value.email, notes: grantForm.value.notes || undefined }
    if (grantForm.value.amount_paid !== '') payload.amount_paid = Number(grantForm.value.amount_paid)

    await api.post(`/courses/${props.slug}/students/grant-cash`, payload)
    toast.success('Acceso concedido. El alumno ya puede ver el curso como si lo hubiera comprado.')
    grantForm.value = { email: '', amount_paid: '', notes: '' }
    await load()
  } catch (e) {
    toast.error(apiErrorMessage(e))
  } finally {
    granting.value = false
  }
}
</script>

<template>
  <div>
    <h1 class="text-xl font-bold mb-1">Alumnos de "{{ courseTitle }}"</h1>
    <p class="text-sm text-gray-500 mb-6">{{ enrollments.length }} alumnos inscritos</p>

    <div class="bg-brand-50 border border-brand-100 rounded-xl p-5 mb-8 max-w-xl">
      <h2 class="font-semibold mb-1">Dar acceso por pago en efectivo</h2>
      <p class="text-sm text-gray-600 mb-4">
        Si un alumno te ha pagado en persona o por otro medio fuera de la plataforma, puedes concederle
        acceso manualmente. Aparecerá en su cuenta exactamente igual que si lo hubiera comprado online.
      </p>
      <form class="space-y-3" @submit.prevent="grantCash">
        <input
          v-model="grantForm.email"
          type="email"
          required
          placeholder="Correo electrónico del alumno (debe tener cuenta creada)"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
        />
        <input
          v-model="grantForm.amount_paid"
          type="number"
          min="0"
          step="0.01"
          placeholder="Importe pagado en € (opcional, por defecto el precio del curso)"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
        />
        <input
          v-model="grantForm.notes"
          placeholder="Notas (opcional): p. ej. 'Pagado en clase el 3 de septiembre'"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
        />
        <button :disabled="granting" class="bg-brand-600 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-brand-700 disabled:opacity-50">
          Conceder acceso
        </button>
      </form>
    </div>

    <div v-if="loading" class="text-gray-400">Cargando...</div>
    <div v-else-if="enrollments.length === 0" class="text-gray-500">Todavía no hay alumnos inscritos.</div>
    <table v-else class="w-full text-sm bg-white border border-gray-200 rounded-xl overflow-hidden">
      <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
        <tr>
          <th class="text-left px-4 py-3">Alumno</th>
          <th class="text-left px-4 py-3">Origen</th>
          <th class="text-left px-4 py-3">Progreso</th>
          <th class="text-left px-4 py-3">Inscrito el</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        <tr v-for="enrollment in enrollments" :key="enrollment.id">
          <td class="px-4 py-3">
            <p class="font-medium">{{ enrollment.user?.name }}</p>
            <p class="text-xs text-gray-400">{{ enrollment.user?.email }}</p>
          </td>
          <td class="px-4 py-3">
            <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100">{{ sourceLabels[enrollment.source] }}</span>
            <p v-if="enrollment.granted_by" class="text-xs text-gray-400 mt-1">por {{ enrollment.granted_by.name }}</p>
          </td>
          <td class="px-4 py-3 w-40">
            <div class="w-full bg-gray-100 rounded-full h-2">
              <div class="bg-brand-600 h-2 rounded-full" :style="{ width: `${enrollment.progress_percent ?? 0}%` }" />
            </div>
            <span class="text-xs text-gray-500">{{ enrollment.progress_percent ?? 0 }}%</span>
          </td>
          <td class="px-4 py-3 text-gray-500">{{ new Date(enrollment.enrolled_at).toLocaleDateString() }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
