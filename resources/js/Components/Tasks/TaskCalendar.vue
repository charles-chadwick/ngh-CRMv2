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
  buttonText: { today: 'Today', month: 'Month', week: 'Week', day: 'Day' },
  height: 'auto',
  dayMaxEvents: 3,
  nowIndicator: true,
  editable: true,
  events: props.events,
  eventDrop: (info) => emit('reschedule', { id: info.event.id, due_at: info.event.start?.toISOString(), allDay: info.event.allDay }),
  dateClick: (info) => emit('select-date', info.dateStr),
  eventClick: (info) => emit('open-event', info.event.id),
  datesSet: (info) => emit('range-change', { start: info.startStr, end: info.endStr }),
}))
</script>

<template>
  <div class="task-calendar">
    <FullCalendar :options="options" />
  </div>
</template>

<style scoped>
.task-calendar :deep(.fc) {
  --fc-border-color: var(--border);
  --fc-page-bg-color: transparent;
  --fc-neutral-bg-color: var(--muted);
  --fc-today-bg-color: color-mix(in oklch, var(--primary) 12%, transparent);
  --fc-event-bg-color: var(--primary);
  --fc-event-border-color: var(--primary);
  --fc-event-text-color: var(--primary-foreground);
  --fc-now-indicator-color: var(--destructive);
  font-size: 0.875rem;
}

.task-calendar :deep(.fc .fc-toolbar-title) {
  font-size: 1.125rem;
  font-weight: 700;
}

.task-calendar :deep(.fc .fc-col-header-cell-cushion),
.task-calendar :deep(.fc .fc-daygrid-day-number) {
  color: var(--muted-foreground);
  font-weight: 600;
  text-decoration: none;
}

.task-calendar :deep(.fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number) {
  color: var(--primary);
}

/* Buttons styled to match the app's secondary/primary palette */
.task-calendar :deep(.fc .fc-button) {
  background-color: var(--secondary);
  border-color: var(--border);
  color: var(--secondary-foreground);
  font-weight: 600;
  text-transform: capitalize;
  box-shadow: none;
  cursor: pointer;
  border-radius: var(--radius);
  padding: 0.35rem 0.75rem;
}

.task-calendar :deep(.fc .fc-button:hover) {
  background-color: var(--accent);
  color: var(--accent-foreground);
}

.task-calendar :deep(.fc .fc-button:focus) {
  box-shadow: none;
}

.task-calendar :deep(.fc .fc-button-primary:not(:disabled).fc-button-active),
.task-calendar :deep(.fc .fc-button-primary:not(:disabled):active) {
  background-color: var(--primary);
  border-color: var(--primary);
  color: var(--primary-foreground);
}

.task-calendar :deep(.fc .fc-button:disabled) {
  opacity: 0.5;
}

.task-calendar :deep(.fc .fc-event) {
  border-radius: 0.375rem;
  padding: 1px 4px;
  font-weight: 600;
  cursor: pointer;
}

.task-calendar :deep(.fc .fc-scrollgrid) {
  border-radius: var(--radius);
  overflow: hidden;
}
</style>
