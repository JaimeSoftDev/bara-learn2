<script setup lang="ts">
import { useToastStore } from '@/stores/toast'

const toasts = useToastStore()

const styles: Record<string, string> = {
  success: 'bg-emerald-600',
  error: 'bg-red-600',
  info: 'bg-gray-900',
}
</script>

<template>
  <div class="fixed top-4 right-4 z-50 flex flex-col gap-2 w-80 max-w-[90vw]">
    <TransitionGroup name="toast">
      <div
        v-for="toast in toasts.toasts"
        :key="toast.id"
        :class="[styles[toast.type], 'text-white rounded-lg shadow-lg px-4 py-3 text-sm flex items-start gap-2']"
      >
        <span class="flex-1">{{ toast.message }}</span>
        <button class="opacity-70 hover:opacity-100" @click="toasts.remove(toast.id)">✕</button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.25s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateX(20px);
}
</style>
