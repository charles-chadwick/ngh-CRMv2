<script setup>
import { useForm } from '@inertiajs/vue3'
import { Label } from "@/Components/UI/label/index.js";
import { Input } from "@/Components/UI/input/index.js";
import { Button } from "@/Components/UI/button/index.js";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/UI/select/index.js";
import { Card, CardContent } from "@/Components/UI/card/index.js";

const props = defineProps({
  notable_type: { type: String, required: true },
  notable_id: { type: [Number, String], required: true },
  note: { type: Object, default: null },
  types: { type: Array, default: () => [] },
})

const emits = defineEmits(['saved', 'cancel'])

const form = useForm({
  model_type: props.notable_type,
  model_id: props.notable_id,
  type: props.note?.type || props.types[0] || '',
  title: props.note?.title || '',
  content: props.note?.content || '',
})

const submit = () => {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      if (!props.note) {
        form.reset('type', 'title', 'content')
      }
      emits('saved')
    },
  }

  if (props.note) {
    form.put(route('notes.update', props.note.id), options)
  } else {
    form.post(route('notes.store'), options)
  }
}
</script>

<template>
  <Card>
    <CardContent>
      <form
          class="space-y-3"
          @submit.prevent="submit"
      >
        <div class="grid gap-3 sm:grid-cols-3">
          <div class="space-y-2 sm:col-span-2">
            <Label for="note_title">Title</Label>
            <Input
                id="note_title"
                type="text"
                placeholder="Note title"
                v-model="form.title"
            />
            <p
                v-if="form.errors.title"
                class="text-sm text-destructive"
            >
              {{ form.errors.title }}
            </p>
          </div>
          <div class="space-y-2">
            <Label for="note_type">Type</Label>
            <Select v-model="form.type">
              <SelectTrigger
                  id="note_type"
                  class="w-full"
              >
                <SelectValue placeholder="Select a type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                    v-for="type in types"
                    :key="type"
                    :value="type"
                >
                  {{ type }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p
                v-if="form.errors.type"
                class="text-sm text-destructive"
            >
              {{ form.errors.type }}
            </p>
          </div>
        </div>

        <div class="space-y-2">
          <Label for="note_content">Content</Label>
          <textarea
              id="note_content"
              rows="3"
              placeholder="Write a note…"
              v-model="form.content"
              class="placeholder:text-muted-foreground dark:bg-input/30 border-input w-full min-w-0 rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-3 md:text-sm"
          />
          <p
              v-if="form.errors.content"
              class="text-sm text-destructive"
          >
            {{ form.errors.content }}
          </p>
        </div>

        <div class="flex justify-end gap-3">
          <Button
              type="button"
              variant="ghost"
              size="sm"
              @click="emits('cancel')"
          >
            Cancel
          </Button>
          <Button
              type="submit"
              size="sm"
              :disabled="form.processing"
          >
            {{ note ? 'Update Note' : 'Save Note' }}
          </Button>
        </div>
      </form>
    </CardContent>
  </Card>
</template>
