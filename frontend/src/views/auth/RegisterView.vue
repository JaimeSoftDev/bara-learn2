<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { apiErrorMessage } from '@/composables/useApiError'

const auth = useAuthStore()
const router = useRouter()

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'student' as 'student' | 'teacher',
})
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.register(form.value)
    router.push(form.value.role === 'teacher' ? { name: 'teacher-overview' } : { name: 'my-courses' })
  } catch (e) {
    error.value = apiErrorMessage(e, 'No se pudo completar el registro.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="max-w-md mx-auto px-4 py-16">
    <h1 class="text-2xl font-bold mb-1">Crea tu cuenta</h1>
    <p class="text-gray-500 mb-6 text-sm">Únete como alumno o comparte tu conocimiento como profesor.</p>

    <div class="flex gap-2 mb-6">
      <button
        type="button"
        class="flex-1 border rounded-lg py-2 text-sm font-medium"
        :class="form.role === 'student' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-gray-300 text-gray-600'"
        @click="form.role = 'student'"
      >
        Quiero aprender
      </button>
      <button
        type="button"
        class="flex-1 border rounded-lg py-2 text-sm font-medium"
        :class="form.role === 'teacher' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-gray-300 text-gray-600'"
        @click="form.role = 'teacher'"
      >
        Quiero enseñar
      </button>
    </div>

    <form class="space-y-4" @submit.prevent="submit">
      <div>
        <label class="text-sm font-medium text-gray-700">Nombre completo</label>
        <input v-model="form.name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1" />
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700">Correo electrónico</label>
        <input
          v-model="form.email"
          type="email"
          required
          class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1"
        />
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700">Contraseña</label>
        <input
          v-model="form.password"
          type="password"
          required
          minlength="8"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1"
        />
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700">Confirma tu contraseña</label>
        <input
          v-model="form.password_confirmation"
          type="password"
          required
          class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1"
        />
      </div>

      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

      <button
        type="submit"
        :disabled="loading"
        class="w-full bg-brand-600 text-white font-semibold py-2.5 rounded-lg hover:bg-brand-700 disabled:opacity-50"
      >
        Crear cuenta
      </button>
    </form>

    <p class="text-sm text-gray-500 mt-6 text-center">
      ¿Ya tienes cuenta?
      <RouterLink to="/login" class="text-brand-600 font-medium hover:text-brand-700">Inicia sesión</RouterLink>
    </p>
  </div>
</template>
