<script setup lang="ts">
import { watch } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Placeholder from '@tiptap/extension-placeholder'

const props = withDefaults(
  defineProps<{ modelValue: string; placeholder?: string }>(),
  { placeholder: 'Escribe el contenido de la lección...' },
)
const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>()

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit,
    Link.configure({ openOnClick: false }),
    Placeholder.configure({ placeholder: props.placeholder }),
  ],
  onUpdate: ({ editor }) => emit('update:modelValue', editor.getHTML()),
})

watch(
  () => props.modelValue,
  (value) => {
    if (editor.value && value !== editor.value.getHTML()) {
      editor.value.commands.setContent(value, { emitUpdate: false })
    }
  },
)

function toggle(action: () => boolean | undefined) {
  action()
}

function insertLink() {
  const url = window.prompt('URL del enlace')
  if (url) editor.value?.chain().focus().setLink({ href: url }).run()
}
</script>

<template>
  <div class="border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-brand-500">
    <div v-if="editor" class="flex flex-wrap gap-1 border-b border-gray-200 bg-gray-50 px-2 py-1.5">
      <button
        type="button"
        class="toolbar-btn"
        :class="{ 'is-active': editor.isActive('bold') }"
        @click="toggle(() => editor?.chain().focus().toggleBold().run())"
      >
        <strong>B</strong>
      </button>
      <button
        type="button"
        class="toolbar-btn italic"
        :class="{ 'is-active': editor.isActive('italic') }"
        @click="toggle(() => editor?.chain().focus().toggleItalic().run())"
      >
        I
      </button>
      <button
        type="button"
        class="toolbar-btn"
        :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }"
        @click="toggle(() => editor?.chain().focus().toggleHeading({ level: 2 }).run())"
      >
        H2
      </button>
      <button
        type="button"
        class="toolbar-btn"
        :class="{ 'is-active': editor.isActive('bulletList') }"
        @click="toggle(() => editor?.chain().focus().toggleBulletList().run())"
      >
        • Lista
      </button>
      <button
        type="button"
        class="toolbar-btn"
        :class="{ 'is-active': editor.isActive('orderedList') }"
        @click="toggle(() => editor?.chain().focus().toggleOrderedList().run())"
      >
        1. Lista
      </button>
      <button type="button" class="toolbar-btn" @click="insertLink">🔗 Enlace</button>
    </div>
    <EditorContent :editor="editor" />
  </div>
</template>

<style scoped>
@reference '@/assets/main.css';

.toolbar-btn {
  @apply text-sm px-2 py-1 rounded text-gray-600 hover:bg-gray-200;
}
.toolbar-btn.is-active {
  @apply bg-brand-100 text-brand-700;
}
</style>
