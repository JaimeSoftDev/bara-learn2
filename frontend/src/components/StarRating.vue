<script setup lang="ts">
withDefaults(
  defineProps<{
    modelValue: number
    interactive?: boolean
    size?: string
  }>(),
  { interactive: false, size: 'w-4 h-4' },
)

const emit = defineEmits<{ (e: 'update:modelValue', value: number): void }>()
</script>

<template>
  <div class="flex items-center gap-0.5">
    <button
      v-for="star in 5"
      :key="star"
      type="button"
      :class="[interactive ? 'cursor-pointer' : 'cursor-default', size]"
      :disabled="!interactive"
      @click="interactive && emit('update:modelValue', star)"
    >
      <svg
        viewBox="0 0 20 20"
        :fill="star <= Math.round(modelValue) ? '#f59e0b' : '#e5e7eb'"
        class="w-full h-full"
      >
        <path
          d="M10 15.27L16.18 19l-1.64-7.03L20 7.24l-7.19-.61L10 0 7.19 6.63 0 7.24l5.46 4.73L3.82 19z"
        />
      </svg>
    </button>
  </div>
</template>
