<script setup>
import { Button } from "@/Components/UI/button/index.js";
import { Pencil, Trash2 } from "lucide-vue-next"
import CreatedBy from "@/Components/CreatedBy.vue";

defineProps ( {
  note: { type: Object, required: true },
} )

defineEmits ( [ 'edit', 'remove' ] )
</script>

<template>
  <div>
    <div class="flex items-start justify-between gap-4">
      <div class="flex items-center justify-between gap-2">
        <div>
          <h3 class="font-bold">{{ note.title }}</h3>
          <span
              v-if="note.type"
              class="rounded-full bg-secondary px-2.5 py-0.5 text-xs font-bold text-secondary-foreground"
          >
          {{ note.type }}
        </span>
        </div>
        <div>
          <Button
              aria-label="Edit"
              size="icon-sm"
              title="Edit"
              variant="ghost"
              @click="$emit('edit', note)"
          >
            <Pencil />
          </Button>
          <Button
              aria-label="Remove"
              class="text-destructive hover:text-destructive"
              size="icon-sm"
              title="Remove"
              variant="ghost"
              @click="$emit('remove', note)"
          >
            <Trash2 />
          </Button>
        </div>
      </div>
    </div>
    <p class="mt-2 text-sm whitespace-pre-line">{{ note.content }}</p>
    <hr class="my-2" />
    <CreatedBy
        :date="note.created_at"
        :user="note.creation_activity?.causer"
    />
  </div>
</template>
