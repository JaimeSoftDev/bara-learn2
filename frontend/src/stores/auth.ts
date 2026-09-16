import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api, ensureCsrfCookie } from '@/api/client'
import type { User } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const ready = ref(false)

  const isAuthenticated = computed(() => user.value !== null)
  const isTeacher = computed(() => user.value?.role === 'teacher')
  const isAdmin = computed(() => user.value?.role === 'admin')
  const isStudent = computed(() => user.value?.role === 'student')

  async function fetchMe() {
    try {
      const { data } = await api.get('/me')
      user.value = data.user
    } catch {
      user.value = null
    } finally {
      ready.value = true
    }
  }

  async function login(email: string, password: string) {
    await ensureCsrfCookie()
    const { data } = await api.post('/login', { email, password })
    user.value = data.user
  }

  async function register(payload: {
    name: string
    email: string
    password: string
    password_confirmation: string
    role: 'student' | 'teacher'
  }) {
    await ensureCsrfCookie()
    const { data } = await api.post('/register', payload)
    user.value = data.user
  }

  async function logout() {
    try {
      await api.post('/logout')
    } finally {
      user.value = null
    }
  }

  return { user, ready, isAuthenticated, isTeacher, isAdmin, isStudent, fetchMe, login, register, logout }
})
