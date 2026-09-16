<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { apiErrorMessage } from '@/composables/useApiError'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const form = ref({ email: '', password: '' })
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(form.value.email, form.value.password)
    const redirect = (route.query.redirect as string) || '/'
    router.push(redirect)
  } catch (e) {
    error.value = apiErrorMessage(e, 'No se pudo iniciar sesión.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="max-w-md mx-auto px-4 py-16">
    <h1 class="text-2xl font-bold mb-1">Inicia sesión</h1>
    <p class="text-gray-500 mb-6 text-sm">Accede a tus cursos y continúa aprendiendo.</p>

    <form class="space-y-4" @submit.prevent="submit">
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
          class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1"
        />
      </div>

      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

      <button
        type="submit"
        :disabled="loading"
        class="w-full bg-brand-600 text-white font-semibold py-2.5 rounded-lg hover:bg-brand-700 disabled:opacity-50"
      >
        Entrar
      </button>
    </form>

    <p class="text-sm text-gray-500 mt-6 text-center">
      ¿No tienes cuenta?
      <RouterLink to="/register" class="text-brand-600 font-medium hover:text-brand-700">Regístrate</RouterLink>
    </p>

    <div class="mt-8 p-4 bg-gray-50 rounded-lg text-xs text-gray-500 space-y-1">
      <p class="font-semibold text-gray-600">Usuarios de demostración (contraseña: password)</p>
      <p>Profesor: laura@baralearn.test</p>
      <p>Alumno: ana@baralearn.test</p>
      <p>Admin: admin@baralearn.test</p>
    </div>
  </div>
</template>
