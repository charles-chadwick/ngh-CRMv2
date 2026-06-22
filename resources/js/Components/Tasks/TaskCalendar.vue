<script setup>
import { computed } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'

const props = defineProps({ events: { type: Array, default: () => [] } })
const emit = defineEmits(['reschedule', 'select-date', 'open-event', 'range-change'])

const options = computed(() => ({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
  editable: true,
  events: props.events,
  eventDrop: (info) => emit('reschedule', { id: info.event.id, due_at: info.event.start?.toISOString(), allDay: info.event.allDay }),
  dateClick: (info) => emit('select-date', info.dateStr),
  eventClick: (info) => emit('open-event', info.event.id),
  datesSet: (info) => emit('range-change', { start: info.startStr, end: info.endStr }),
}))
</script>

<template>
  <FullCalendar :options="options" />
</template>
