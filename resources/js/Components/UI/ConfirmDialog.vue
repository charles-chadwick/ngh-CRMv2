<script setup>
import { watch, nextTick, ref } from "vue";
import { Button } from "@/Components/UI/button/index.js";
import { confirm_state, resolveConfirm } from "@/composables/useConfirm.js";

// Self-contained modal (Teleport + plain elements). We deliberately avoid
// reka-ui's AlertDialog here: its modal DismissableLayer sets
// `body { pointer-events: none }` and only re-enables the content layer when
// its internal layer-index accounting agrees. When another reka layer is in
// play, the dialog can render visible but unclickable (ESC still works). A
// plain Teleport modal sidesteps that entirely while keeping full control of
// dismissal and accessibility.
const panel = ref(null);

const onKeydown = (event) => {
  if (event.key === "Escape") {
    resolveConfirm(false);
  }
};

watch(
  () => confirm_state.open,
  (open) => {
    if (open) {
      document.addEventListener("keydown", onKeydown);
      nextTick(() => panel.value?.focus());
    } else {
      document.removeEventListener("keydown", onKeydown);
    }
  },
);
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="confirm_state.open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="resolveConfirm(false)"
      >
        <div
          ref="panel"
          role="alertdialog"
          aria-modal="true"
          tabindex="-1"
          class="grid w-full max-w-md gap-4 rounded-lg border bg-background p-6 shadow-lg outline-none"
        >
          <div class="flex flex-col gap-2">
            <h2 class="text-lg font-bold">{{ confirm_state.options.title }}</h2>
            <p
              v-if="confirm_state.options.message"
              class="text-sm text-muted-foreground"
            >
              {{ confirm_state.options.message }}
            </p>
          </div>

          <div class="flex justify-end gap-2">
            <Button variant="outline" @click="resolveConfirm(false)">
              {{ confirm_state.options.cancelLabel }}
            </Button>
            <Button
              :variant="confirm_state.options.variant"
              @click="resolveConfirm(true)"
            >
              {{ confirm_state.options.confirmLabel }}
            </Button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
