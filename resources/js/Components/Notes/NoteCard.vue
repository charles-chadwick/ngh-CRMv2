<script setup>
import { computed } from 'vue'
import { Card, CardContent, CardFooter, CardHeader } from "@/Components/UI/card/index.js";
import { Button } from "@/Components/UI/button/index.js";

const props = defineProps({
  note: { type: Object, required: true },
})

defineEmits(['edit', 'remove'])

const formatted_date = computed(() => {
  if (!props.note.created_at) {
    return null
  }

  return new Date(props.note.created_at).toLocaleString(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
  })
})
</script>

<template>
  <Card>
    <CardHeader>
      <div class="flex items-start justify-between gap-4">
        <h3 class="font-bold">{{ note.title }}</h3>
        <span
            v-if="note.type"
            class="shrink-0 rounded-full bg-secondary px-2.5 py-0.5 text-xs font-bold text-secondary-foreground"
        >
          {{ note.type }}
        </span>
      </div>
      <p
          v-if="formatted_date"
          class="text-xs text-muted-foreground"
      >
        {{ formatted_date }}
      </p>
    </CardHeader>
    <CardContent>
      <p class="text-sm whitespace-pre-line">{{ note.content }}</p>
    </CardContent>
    <CardFooter class="justify-end gap-2">
      <Button
          variant="ghost"
          size="sm"
          @click="$emit('edit', note)"
      >
        Edit
      </Button>
      <Button
          variant="ghost"
          size="sm"
          class="text-destructive hover:text-destructive"
          @click="$emit('remove', note)"
      >
        Remove
      </Button>
    </CardFooter>
  </Card>
</template>
