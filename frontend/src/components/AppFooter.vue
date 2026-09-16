<script setup lang="ts"></script>

<template>
  <footer class="bg-ink-950 text-white mt-16">
    <div class="max-w-7xl mx-auto px-4 py-14 grid grid-cols-1 sm:grid-cols-3 gap-10">
      <div>
        <div class="flex items-center gap-2.5 mb-3">
          <span
            class="w-8 h-8 rounded-lg bg-gradient-to-br from-accent-500 to-brand-600 flex items-center justify-center font-display font-bold text-sm"
          >
            A
          </span>
          <span class="font-display text-xl font-semibold">ADNTrate</span>
        </div>
        <p class="text-ink-100/60 text-sm max-w-xs">
          Genética online para estudiantes de grado. Aprende a tu ritmo, con rigor académico y
          acceso de por vida.
        </p>
      </div>

      <div>
        <p class="eyebrow text-ink-100/40 mb-3">Plataforma</p>
        <ul class="space-y-2 text-sm text-ink-100/80">
          <li><RouterLink to="/courses" class="hover:text-white">Cursos</RouterLink></li>
          <li><RouterLink to="/register" class="hover:text-white">Crear cuenta</RouterLink></li>
          <li><RouterLink to="/login" class="hover:text-white">Iniciar sesión</RouterLink></li>
          <li><RouterLink to="/verify-certificate" class="hover:text-white">Verificar certificado</RouterLink></li>
        </ul>
      </div>

      <div>
        <p class="eyebrow text-ink-100/40 mb-3">Contacto</p>
        <ul class="space-y-2 text-sm text-ink-100/80">
          <li>hola@adntrate.com</li>
          <li>Lun–Vie · 9:00–18:00</li>
          <li>Madrid, España</li>
        </ul>
      </div>
    </div>
    <div class="border-t border-white/10 max-w-7xl mx-auto px-4 py-5 flex flex-col sm:flex-row justify-between gap-2 text-xs text-ink-100/40">
      <p>© {{ new Date().getFullYear() }} ADNTrate · Todos los derechos reservados</p>
      <p>Hecho con ADN y café</p>
    </div>
  </footer>
</template>
