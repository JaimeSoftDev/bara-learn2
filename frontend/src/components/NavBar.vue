<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'

const auth = useAuthStore()
const router = useRouter()
const search = ref('')
const mobileOpen = ref(false)

function submitSearch() {
  router.push({ name: 'courses', query: search.value ? { search: search.value } : {} })
  mobileOpen.value = false
}

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'home' })
}

const dashboardRoute = () => {
  if (auth.isAdmin) return { name: 'admin-overview' }
  if (auth.isTeacher) return { name: 'teacher-overview' }
  return { name: 'my-courses' }
}
</script>

<template>
  <header class="sticky top-0 z-40 bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 h-16 flex items-center gap-4">
      <RouterLink to="/" class="font-extrabold text-xl text-brand-700 shrink-0">Bara Learn</RouterLink>

      <RouterLink to="/courses" class="hidden md:inline text-sm text-gray-600 hover:text-gray-900 shrink-0">
        Explorar cursos
      </RouterLink>

      <form class="hidden md:flex flex-1 max-w-md" @submit.prevent="submitSearch">
        <input
          v-model="search"
          type="search"
          placeholder="Buscar cursos..."
          class="w-full border border-gray-300 rounded-full px-4 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
        />
      </form>

      <div class="ml-auto flex items-center gap-3">
        <template v-if="auth.isAuthenticated">
          <RouterLink
            v-if="auth.isStudent"
            to="/wishlist"
            class="hidden sm:inline text-sm text-gray-600 hover:text-gray-900"
          >
            Favoritos
          </RouterLink>
          <Menu as="div" class="relative">
            <MenuButton class="flex items-center gap-2 text-sm font-medium text-gray-700">
              <span
                class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-bold"
              >
                {{ auth.user?.name?.charAt(0) }}
              </span>
              <span class="hidden sm:inline">{{ auth.user?.name }}</span>
            </MenuButton>
            <transition
              enter-active-class="transition duration-100 ease-out"
              enter-from-class="opacity-0 scale-95"
              enter-to-class="opacity-100 scale-100"
            >
              <MenuItems
                class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-100 py-1 focus:outline-none"
              >
                <MenuItem v-slot="{ active }">
                  <RouterLink
                    :to="dashboardRoute()"
                    :class="[active ? 'bg-gray-50' : '', 'block px-4 py-2 text-sm text-gray-700']"
                  >
                    Mi panel
                  </RouterLink>
                </MenuItem>
                <MenuItem v-if="!auth.isAdmin" v-slot="{ active }">
                  <RouterLink
                    to="/account/profile"
                    :class="[active ? 'bg-gray-50' : '', 'block px-4 py-2 text-sm text-gray-700']"
                  >
                    Mi perfil
                  </RouterLink>
                </MenuItem>
                <MenuItem v-if="auth.isStudent" v-slot="{ active }">
                  <RouterLink
                    to="/account/certificates"
                    :class="[active ? 'bg-gray-50' : '', 'block px-4 py-2 text-sm text-gray-700']"
                  >
                    Mis certificados
                  </RouterLink>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                  <button
                    :class="[active ? 'bg-gray-50' : '', 'w-full text-left px-4 py-2 text-sm text-red-600']"
                    @click="handleLogout"
                  >
                    Cerrar sesión
                  </button>
                </MenuItem>
              </MenuItems>
            </transition>
          </Menu>
        </template>
        <template v-else>
          <RouterLink to="/login" class="text-sm font-medium text-gray-700 hover:text-gray-900">
            Iniciar sesión
          </RouterLink>
          <RouterLink
            to="/register"
            class="text-sm font-semibold bg-brand-600 text-white px-4 py-2 rounded-full hover:bg-brand-700"
          >
            Regístrate
          </RouterLink>
        </template>
      </div>
    </div>
  </header>
</template>
