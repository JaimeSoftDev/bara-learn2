<script setup lang="ts">
import { ref } from 'vue'
import { api } from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { apiErrorMessage } from '@/composables/useApiError'

const auth = useAuthStore()
const toast = useToastStore()

const profile = ref({
  name: auth.user?.name ?? '',
  email: auth.user?.email ?? '',
  headline: auth.user?.headline ?? '',
  bio: auth.user?.bio ?? '',
  avatar_url: auth.user?.avatar_url ?? '',
})

const passwordForm = ref({ current_password: '', password: '', password_confirmation: '' })
const savingProfile = ref(false)
const savingPassword = ref(false)

async function saveProfile() {
  savingProfile.value = true
  try {
    const { data } = await api.put('/me', profile.value)
    auth.user = data.data ?? data
    toast.success('Perfil actualizado.')
  } catch (e) {
    toast.error(apiErrorMessage(e))
  } finally {
    savingProfile.value = false
  }
}

async function savePassword() {
  savingPassword.value = true
  try {
    await api.put('/me/password', passwordForm.value)
    passwordForm.value = { current_password: '', password: '', password_confirmation: '' }
    toast.success('Contraseña actualizada.')
  } catch (e) {
    toast.error(apiErrorMessage(e))
  } finally {
    savingPassword.value = false
  }
}
</script>

<template>
  <div class="max-w-2xl space-y-10">
    <div>
      <h1 class="text-xl font-bold mb-4">Mi perfil</h1>
      <form class="space-y-4 bg-white border border-gray-200 rounded-xl p-6" @submit.prevent="saveProfile">
        <div>
          <label class="text-sm font-medium text-gray-700">Nombre</label>
          <input v-model="profile.name" class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1" />
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700">Correo electrónico</label>
          <input v-model="profile.email" type="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1" />
        </div>
        <div v-if="auth.isTeacher">
          <label class="text-sm font-medium text-gray-700">Titular profesional</label>
          <input v-model="profile.headline" class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1" />
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700">URL de avatar</label>
          <input v-model="profile.avatar_url" class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1" />
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700">Biografía</label>
          <textarea v-model="profile.bio" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1" />
        </div>
        <button
          :disabled="savingProfile"
          class="bg-brand-600 text-white font-semibold px-5 py-2 rounded-lg hover:bg-brand-700 disabled:opacity-50"
        >
          Guardar cambios
        </button>
      </form>
    </div>

    <div>
      <h2 class="text-xl font-bold mb-4">Cambiar contraseña</h2>
      <form class="space-y-4 bg-white border border-gray-200 rounded-xl p-6" @submit.prevent="savePassword">
        <div>
          <label class="text-sm font-medium text-gray-700">Contraseña actual</label>
          <input
            v-model="passwordForm.current_password"
            type="password"
            required
            class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1"
          />
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700">Nueva contraseña</label>
          <input
            v-model="passwordForm.password"
            type="password"
            required
            minlength="8"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1"
          />
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700">Confirma la nueva contraseña</label>
          <input
            v-model="passwordForm.password_confirmation"
            type="password"
            required
            class="w-full border border-gray-300 rounded-lg px-3 py-2 mt-1"
          />
        </div>
        <button
          :disabled="savingPassword"
          class="bg-brand-600 text-white font-semibold px-5 py-2 rounded-lg hover:bg-brand-700 disabled:opacity-50"
        >
          Actualizar contraseña
        </button>
      </form>
    </div>
  </div>
</template>
