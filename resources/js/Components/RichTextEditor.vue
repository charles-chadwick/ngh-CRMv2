<!--
  RichTextEditor
  --------------
  Quill 2 editor with @mention autocomplete (quill-mention). v-model-compatible:
  emits the editor's HTML on every change. Mentions insert
  <span class="mention" data-id="{userId}">@Name</span> tokens, which the server
  parses to resolve who was mentioned.
-->
<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import Quill from 'quill'
import { Mention, MentionBlot } from 'quill-mention'
import 'quill/dist/quill.snow.css'
import 'quill-mention/dist/quill.mention.css'

Quill.register({ 'blots/mention': MentionBlot, 'modules/mention': Mention })

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Write a note…' },
})

const emit = defineEmits(['update:modelValue'])

const editor_el = ref(null)
let quill = null

onMounted(() => {
    quill = new Quill(editor_el.value, {
        theme: 'snow',
        placeholder: props.placeholder,
        modules: {
            toolbar: [['bold', 'italic', 'underline', 'strike'], [{ list: 'ordered' }, { list: 'bullet' }], ['link']],
            mention: {
                allowedChars: /^[A-Za-z0-9\s._-]*$/,
                mentionDenotationChars: ['@'],
                dataAttributes: ['id', 'value'],
                source: async (search_term, render_list) => {
                    const res = await fetch(route('users.mentionable', { q: search_term ?? '' }), { headers: { Accept: 'application/json' } })
                    const users = await res.json()
                    render_list(users.map((user) => ({ id: user.id, value: user.full_name })), search_term)
                },
            },
        },
    })

    if (props.modelValue) {
        quill.clipboard.dangerouslyPasteHTML(props.modelValue)
    }

    quill.on('text-change', () => {
        const html = quill.getLength() > 1 ? quill.root.innerHTML : ''
        emit('update:modelValue', html)
    })
})

// Allow external resets (e.g. form.reset()) to clear the editor.
watch(
    () => props.modelValue,
    (value) => {
        if (quill && value === '' && quill.getLength() > 1) {
            quill.setText('')
        }
    },
)

onBeforeUnmount(() => {
    if (quill) {
        quill.off('text-change')
    }
    quill = null
})
</script>

<template>
    <div class="rounded-md border border-input bg-transparent">
        <div ref="editor_el" />
    </div>
</template>
