<script setup lang="ts">
import { computed } from 'vue'

const ROWS = 11
const WIDTH = 200
const AMPLITUDE = 60
const ROW_HEIGHT = 24

const rungs = computed(() =>
  Array.from({ length: ROWS }, (_, i) => {
    const t = (i / (ROWS - 1)) * Math.PI * 2
    const offset = Math.sin(t) * AMPLITUDE
    const y = i * ROW_HEIGHT + 10
    const pairAT = i % 2 === 0
    return {
      y,
      leftX: WIDTH / 2 - offset,
      rightX: WIDTH / 2 + offset,
      color: pairAT ? '#4d7897' : '#367d54',
    }
  }),
)

const height = ROWS * ROW_HEIGHT + 20
</script>

<template>
  <div class="card p-6 max-w-xs mx-auto lg:mx-0">
    <div class="flex items-baseline justify-between mb-2">
      <p class="font-display italic text-ink-800">Doble hélice</p>
      <p class="text-xs text-ink-800/40 font-mono">5' → 3'</p>
    </div>
    <svg :viewBox="`0 0 ${WIDTH} ${height}`" class="w-full h-auto max-h-96" aria-hidden="true">
      <line
        v-for="(r, i) in rungs"
        :key="i"
        :x1="r.leftX"
        :y1="r.y"
        :x2="r.rightX"
        :y2="r.y"
        :stroke="r.color"
        stroke-width="2"
        opacity="0.85"
      />
      <path
        :d="rungs.map((r, i) => `${i === 0 ? 'M' : 'L'} ${r.leftX} ${r.y}`).join(' ')"
        fill="none"
        stroke="#4d7897"
        stroke-width="2"
      />
      <path
        :d="rungs.map((r, i) => `${i === 0 ? 'M' : 'L'} ${r.rightX} ${r.y}`).join(' ')"
        fill="none"
        stroke="#367d54"
        stroke-width="2"
      />
      <circle v-for="(r, i) in rungs" :key="'l' + i" :cx="r.leftX" :cy="r.y" r="3.5" fill="#4d7897" />
      <circle v-for="(r, i) in rungs" :key="'r' + i" :cx="r.rightX" :cy="r.y" r="3.5" fill="#367d54" />
    </svg>
    <div class="flex items-center justify-center gap-6 mt-2 text-xs text-ink-800/60">
      <span class="flex items-center gap-1.5"><span class="w-3 h-0.5 bg-[#4d7897]"></span>A–T</span>
      <span class="flex items-center gap-1.5"><span class="w-3 h-0.5 bg-[#367d54]"></span>G–C</span>
    </div>
  </div>
</template>
