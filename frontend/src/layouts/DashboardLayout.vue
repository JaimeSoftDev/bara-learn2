<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import NavBar from '@/components/NavBar.vue'
import ToastContainer from '@/components/ToastContainer.vue'

const auth = useAuthStore()

const links = computed(() => {
  if (auth.isAdmin) {
    return [
      { to: { name: 'admin-overview' }, label: 'Resumen' },
      { to: { name: 'admin-users' }, label: 'Usuarios' },
      { to: { name: 'admin-courses' }, label: 'Cursos' },
      { to: { name: 'admin-categories' }, label: 'Categorías' },
    ]
  }
  if (auth.isTeacher) {
    return [
      { to: { name: 'teacher-overview' }, label: 'Resumen' },
      { to: { name: 'teacher-courses' }, label: 'Mis cursos' },
      { to: { name: 'teacher-orders' }, label: 'Pedidos e ingresos' },
      { to: { name: 'account-profile' }, label: 'Mi perfil' },
    ]
  }
  return [
    { to: { name: 'my-courses' }, label: 'Mis cursos' },
    { to: { name: 'wishlist' }, label: 'Favoritos' },
    { to: { name: 'certificates' }, label: 'Certificados' },
    { to: { name: 'account-profile' }, label: 'Mi perfil' },
  ]
})
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <NavBar />
    <div class="flex-1 max-w-7xl w-full mx-auto px-4 py-8 flex flex-col md:flex-row gap-8">
      <aside class="md:w-56 shrink-0">
        <nav class="flex md:flex-col gap-1 overflow-x-auto">
          <RouterLink
            v-for="link in links"
            :key="link.label"
            :to="link.to"
            class="px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 whitespace-nowrap"
            active-class="bg-brand-50 text-brand-700"
          >
            {{ link.label }}
          </RouterLink>
        </nav>
      </aside>
      <div class="flex-1 min-w-0">
        <RouterView />
      </div>
    </div>
    <ToastContainer />
  </div>
</template>
