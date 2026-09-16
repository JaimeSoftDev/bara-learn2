<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'

const auth = useAuthStore()
const router = useRouter()
const search = ref('')

function submitSearch() {
  router.push({ name: 'courses', query: search.value ? { search: search.value } : {} })
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
  <header class="sticky top-0 z-40 bg-white border-b border-ink-900/10">
    <div class="max-w-7xl mx-auto px-4 h-16 flex items-center gap-6">
      <RouterLink to="/" class="flex items-center gap-2.5 shrink-0">
        <span
          class="w-8 h-8 rounded-lg bg-gradient-to-br from-accent-500 to-brand-600 text-white flex items-center justify-center font-display font-bold text-sm"
        >
          A
        </span>
        <span class="font-display text-xl font-semibold text-ink-900">ADNTrate</span>
      </RouterLink>

      <nav class="hidden md:flex items-center gap-6 text-sm">
        <RouterLink
          to="/"
          class="pb-1 border-b-2 border-transparent text-ink-900/60 hover:text-ink-900"
          exact-active-class="!border-brand-600 !text-ink-900 font-medium"
        >
          Inicio
        </RouterLink>
        <RouterLink
          to="/courses"
          class="pb-1 border-b-2 border-transparent text-ink-900/60 hover:text-ink-900"
          active-class="!border-brand-600 !text-ink-900 font-medium"
        >
          Cursos
        </RouterLink>
      </nav>

      <form class="hidden lg:flex flex-1 max-w-sm ml-2" @submit.prevent="submitSearch">
        <input
          v-model="search"
          type="search"
          placeholder="Buscar cursos..."
          class="w-full border border-ink-900/15 rounded-full px-4 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/40"
        />
      </form>

      <div class="ml-auto flex items-center gap-3">
        <template v-if="auth.isAuthenticated">
          <RouterLink
            v-if="auth.isStudent"
            to="/wishlist"
            class="hidden sm:inline text-sm text-ink-900/60 hover:text-ink-900"
          >
            Favoritos
          </RouterLink>
          <Menu as="div" class="relative">
            <MenuButton class="flex items-center gap-2 text-sm font-medium text-ink-900">
              <span
                class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-display font-bold"
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
                class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-ink-900/10 py-1 focus:outline-none"
              >
                <MenuItem v-slot="{ active }">
                  <RouterLink
                    :to="dashboardRoute()"
                    :class="[active ? 'bg-ink-50' : '', 'block px-4 py-2 text-sm text-ink-900']"
                  >
                    Mi panel
                  </RouterLink>
                </MenuItem>
                <MenuItem v-if="!auth.isAdmin" v-slot="{ active }">
                  <RouterLink
                    to="/account/profile"
                    :class="[active ? 'bg-ink-50' : '', 'block px-4 py-2 text-sm text-ink-900']"
                  >
                    Mi perfil
                  </RouterLink>
                </MenuItem>
                <MenuItem v-if="auth.isStudent" v-slot="{ active }">
                  <RouterLink
                    to="/account/certificates"
                    :class="[active ? 'bg-ink-50' : '', 'block px-4 py-2 text-sm text-ink-900']"
                  >
                    Mis certificados
                  </RouterLink>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                  <button
                    :class="[active ? 'bg-ink-50' : '', 'w-full text-left px-4 py-2 text-sm text-red-600']"
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
          <RouterLink to="/login" class="text-sm font-medium text-ink-900/70 hover:text-ink-900">
            Iniciar sesión
          </RouterLink>
          <RouterLink to="/register" class="btn-primary !px-4 !py-2 text-sm"> Crear cuenta </RouterLink>
        </template>
      </div>
    </div>
  </header>
</template>
