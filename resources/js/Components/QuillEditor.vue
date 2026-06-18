<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import Quill from 'quill'
import 'quill/dist/quill.snow.css'

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  editorStyle: { type: [String, Object], default: 'height: 200px' },
  invalid: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const editor_ref = ref(null)
let quill = null
let skip_next_change = false

onMounted(() => {
  quill = new Quill(editor_ref.value, {
    theme: 'snow',
    placeholder: props.placeholder,
  })

  if (props.modelValue) {
    quill.root.innerHTML = props.modelValue
  }

  quill.on('text-change', () => {
    if (skip_next_change) {
      skip_next_change = false
      return
    }
    const content = quill.root.innerHTML
    emit('update:modelValue', content === '<p><br></p>' ? '' : content)
  })
})

onUnmounted(() => {
  quill = null
})

watch(() => props.modelValue, (value) => {
  if (quill && quill.root.innerHTML !== value) {
    skip_next_change = true
    const selection = quill.getSelection()
    quill.root.innerHTML = value || ''
    if (selection) {
      quill.setSelection(selection)
    }
  }
})
</script>

<template>
  <div class="quill-wrapper" :class="{ 'rounded-md ring-1 ring-destructive': invalid }">
    <div ref="editor_ref" :style="editorStyle" />
  </div>
</template>
