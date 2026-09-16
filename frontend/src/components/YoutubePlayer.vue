<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps<{ videoId: string }>()
const emit = defineEmits<{ (e: 'ended'): void }>()

const containerId = `yt-player-${Math.random().toString(36).slice(2)}`
let player: any = null

declare global {
  interface Window {
    YT: any
    onYouTubeIframeAPIReady: (() => void) | undefined
  }
}

function loadApi(): Promise<void> {
  return new Promise((resolve) => {
    if (window.YT && window.YT.Player) {
      resolve()
      return
    }
    const existingCallback = window.onYouTubeIframeAPIReady
    window.onYouTubeIframeAPIReady = () => {
      existingCallback?.()
      resolve()
    }
    if (!document.querySelector('script[src="https://www.youtube.com/iframe_api"]')) {
      const tag = document.createElement('script')
      tag.src = 'https://www.youtube.com/iframe_api'
      document.head.appendChild(tag)
    }
  })
}

async function createPlayer() {
  await loadApi()
  player = new window.YT.Player(containerId, {
    videoId: props.videoId,
    playerVars: { rel: 0, modestbranding: 1 },
    events: {
      onStateChange: (event: { data: number }) => {
        if (event.data === window.YT.PlayerState.ENDED) {
          emit('ended')
        }
      },
    },
  })
}

onMounted(createPlayer)

watch(
  () => props.videoId,
  (id) => {
    if (player?.loadVideoById) {
      player.loadVideoById(id)
    }
  },
)

onBeforeUnmount(() => {
  player?.destroy?.()
})
</script>

<template>
  <div class="aspect-video w-full bg-black rounded-lg overflow-hidden">
    <div :id="containerId" class="w-full h-full" />
  </div>
</template>
