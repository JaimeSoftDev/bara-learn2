<script setup lang="ts"></script>

<template>
  <footer class="border-t border-gray-200 bg-white mt-16">
    <div class="max-w-7xl mx-auto px-4 py-10 grid grid-cols-2 md:grid-cols-4 gap-8 text-sm text-gray-600">
      <div class="col-span-2 md:col-span-1">
        <p class="font-extrabold text-brand-700 text-lg mb-2">Bara Learn</p>
        <p>Aprende de profesores reales con vídeos y contenido escrito, a tu ritmo.</p>
      </div>
      <div>
        <p class="font-semibold text-gray-900 mb-2">Plataforma</p>
        <ul class="space-y-1">
          <li><RouterLink to="/courses" class="hover:text-brand-600">Explorar cursos</RouterLink></li>
          <li><RouterLink to="/register" class="hover:text-brand-600">Conviértete en profesor</RouterLink></li>
        </ul>
      </div>
      <div>
        <p class="font-semibold text-gray-900 mb-2">Certificados</p>
        <ul class="space-y-1">
          <li><RouterLink to="/verify-certificate" class="hover:text-brand-600">Verificar certificado</RouterLink></li>
        </ul>
      </div>
      <div>
        <p class="font-semibold text-gray-900 mb-2">Legal</p>
        <ul class="space-y-1">
          <li>Términos de uso</li>
          <li>Privacidad</li>
        </ul>
      </div>
    </div>
    <div class="border-t border-gray-100 py-4 text-center text-xs text-gray-400">
      © {{ new Date().getFullYear() }} Bara Learn. Todos los derechos reservados.
    </div>
  </footer>
</template>
