<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api } from '@/api/client'
import type { Certificate } from '@/types'

const certificates = ref<Certificate[]>([])
const loading = ref(true)
const apiUrl = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

onMounted(async () => {
  try {
    const { data } = await api.get('/my/certificates')
    certificates.value = data
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <h1 class="text-xl font-bold mb-6">Mis certificados</h1>
    <div v-if="loading" class="text-gray-400">Cargando...</div>
    <div v-else-if="certificates.length === 0" class="text-gray-500">
      Completa un curso al 100% para obtener tu primer certificado.
    </div>
    <div v-else class="space-y-3">
      <div
        v-for="cert in certificates"
        :key="cert.id"
        class="flex items-center justify-between border border-gray-200 rounded-xl p-4 bg-white"
      >
        <div>
          <p class="font-semibold">{{ cert.course.title }}</p>
          <p class="text-xs text-gray-400">
            Emitido el {{ new Date(cert.issued_at).toLocaleDateString() }} · Código: {{ cert.code }}
          </p>
        </div>
        <a
          :href="`${apiUrl}/api/certificates/${cert.id}/download`"
          class="text-sm font-semibold text-brand-600 hover:text-brand-700"
        >
          Descargar PDF
        </a>
      </div>
    </div>
  </div>
</template>
