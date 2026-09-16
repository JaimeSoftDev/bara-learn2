<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { watchDebounced } from '@vueuse/core'
import { api } from '@/api/client'
import { useToastStore } from '@/stores/toast'
import { apiErrorMessage } from '@/composables/useApiError'
import type { User } from '@/types'

const users = ref<User[]>([])
const loading = ref(true)
const search = ref('')
const roleFilter = ref('')
const toast = useToastStore()

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/users', {
      params: { search: search.value || undefined, role: roleFilter.value || undefined },
    })
    users.value = data.data
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(roleFilter, load)
watchDebounced(search, load, { debounce: 400 })

async function updateRole(user: User, role: string) {
  try {
    await api.put(`/admin/users/${user.id}/role`, { role })
    toast.success(`Rol de ${user.name} actualizado a ${role}.`)
    user.role = role as User['role']
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}
</script>

<template>
  <div>
    <h1 class="text-xl font-bold mb-6">Usuarios</h1>

    <div class="flex gap-3 mb-4">
      <input v-model="search" placeholder="Buscar por nombre o email" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm" />
      <select v-model="roleFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
        <option value="">Todos los roles</option>
        <option value="student">Alumnos</option>
        <option value="teacher">Profesores</option>
        <option value="admin">Administradores</option>
      </select>
    </div>

    <div v-if="loading" class="text-gray-400">Cargando...</div>
    <table v-else class="w-full text-sm bg-white border border-gray-200 rounded-xl overflow-hidden">
      <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
        <tr>
          <th class="text-left px-4 py-3">Nombre</th>
          <th class="text-left px-4 py-3">Email</th>
          <th class="text-left px-4 py-3">Rol</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        <tr v-for="user in users" :key="user.id">
          <td class="px-4 py-3 font-medium">{{ user.name }}</td>
          <td class="px-4 py-3 text-gray-500">{{ user.email }}</td>
          <td class="px-4 py-3">
            <select
              :value="user.role"
              class="border border-gray-300 rounded-lg px-2 py-1 text-sm"
              @change="updateRole(user, ($event.target as HTMLSelectElement).value)"
            >
              <option value="student">Alumno</option>
              <option value="teacher">Profesor</option>
              <option value="admin">Administrador</option>
            </select>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
