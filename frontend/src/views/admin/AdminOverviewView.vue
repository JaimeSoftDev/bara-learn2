<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api } from '@/api/client'

interface Overview {
  users_count: number
  teachers_count: number
  students_count: number
  courses_count: number
  published_courses_count: number
  revenue_total: number
  revenue_stripe: number
  revenue_cash: number
}

const overview = ref<Overview | null>(null)
const loading = ref(true)

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/overview')
    overview.value = data
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <h1 class="text-xl font-bold mb-6">Resumen de la plataforma</h1>
    <div v-if="loading" class="text-gray-400">Cargando...</div>
    <div v-else-if="overview" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white border border-gray-200 rounded-xl p-4">
        <p class="text-xs text-gray-400">Usuarios totales</p>
        <p class="text-2xl font-bold">{{ overview.users_count }}</p>
      </div>
      <div class="bg-white border border-gray-200 rounded-xl p-4">
        <p class="text-xs text-gray-400">Profesores</p>
        <p class="text-2xl font-bold">{{ overview.teachers_count }}</p>
      </div>
      <div class="bg-white border border-gray-200 rounded-xl p-4">
        <p class="text-xs text-gray-400">Alumnos</p>
        <p class="text-2xl font-bold">{{ overview.students_count }}</p>
      </div>
      <div class="bg-white border border-gray-200 rounded-xl p-4">
        <p class="text-xs text-gray-400">Cursos publicados</p>
        <p class="text-2xl font-bold">{{ overview.published_courses_count }} / {{ overview.courses_count }}</p>
      </div>
      <div class="bg-white border border-gray-200 rounded-xl p-4">
        <p class="text-xs text-gray-400">Ingresos totales</p>
        <p class="text-2xl font-bold">{{ overview.revenue_total.toFixed(2) }} €</p>
      </div>
      <div class="bg-white border border-gray-200 rounded-xl p-4">
        <p class="text-xs text-gray-400">Vía Stripe</p>
        <p class="text-2xl font-bold">{{ overview.revenue_stripe.toFixed(2) }} €</p>
      </div>
      <div class="bg-white border border-gray-200 rounded-xl p-4">
        <p class="text-xs text-gray-400">En efectivo</p>
        <p class="text-2xl font-bold">{{ overview.revenue_cash.toFixed(2) }} €</p>
      </div>
    </div>
  </div>
</template>
