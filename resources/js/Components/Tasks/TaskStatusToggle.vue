<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Button } from '@/Components/UI/button/index.js'
import { Check, RotateCcw, X } from 'lucide-vue-next'

const props = defineProps({ task: { type: Object, required: true } })
const logging = ref(false)
const logNote = ref('')

const setStatus = (status, extra = {}) => {
  router.patch(route('tasks.updateStatus', props.task.id), { status, ...extra }, { preserveScroll: true })
}

const complete = () => {
  if (logging.value) {
    setStatus('Completed', { log_note: logNote.value })
    logging.value = false
    logNote.value = ''
  } else {
    logging.value = true
  }
}
</script>

<template>
  <div class="flex items-center gap-2">
    <template v-if="task.status === 'Open'">
      <input
          v-if="logging"
          v-model="logNote"
          type="text"
          placeholder="Log a note (optional)"
          class="rounded-md border border-border bg-background px-2 py-1 text-sm"
      >
      <Button size="icon-sm" variant="stone" title="Complete" aria-label="Complete" @click="complete"><Check /></Button>
      <Button size="icon-sm" variant="ghost" title="Cancel task" aria-label="Cancel task" @click="setStatus('Canceled')"><X /></Button>
    </template>
    <Button v-else size="icon-sm" variant="ghost" title="Reopen" aria-label="Reopen" @click="setStatus('Open')"><RotateCcw /></Button>
  </div>
</template>
