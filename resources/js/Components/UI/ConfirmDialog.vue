<script setup>
import {
  AlertDialogRoot,
  AlertDialogPortal,
  AlertDialogOverlay,
  AlertDialogContent,
  AlertDialogTitle,
  AlertDialogDescription,
} from "reka-ui";
import { Button } from "@/Components/UI/button/index.js";
import { confirm_state, resolveConfirm } from "@/composables/useConfirm.js";

// We drive resolution exclusively through our own handlers so the result is
// unambiguous. The Confirm/Cancel buttons are plain Buttons (NOT
// AlertDialogAction/Cancel) — those auto-close and emit update:open, which
// could race our explicit resolve and settle the wrong value. Clicking our
// buttons calls resolveConfirm(), which flips the controlled `open` prop;
// changing a controlled prop externally does not echo update:open.
//
// reka-ui still emits update:open(false) for genuine dismissals (ESC / overlay
// click); we treat those as cancel. resolveConfirm is idempotent (guarded by
// the pending resolve fn), so a redundant call is a harmless no-op.
const onUpdateOpen = (value) => {
  if (!value) {
    resolveConfirm(false);
  }
};
</script>

<template>
  <AlertDialogRoot :open="confirm_state.open" @update:open="onUpdateOpen">
    <AlertDialogPortal>
      <AlertDialogOverlay
        class="fixed inset-0 z-50 bg-black/50 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0"
      />
      <AlertDialogContent
        class="fixed left-1/2 top-1/2 z-50 grid w-full max-w-md -translate-x-1/2 -translate-y-1/2 gap-4 rounded-lg border bg-background p-6 shadow-lg data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95"
      >
        <div class="flex flex-col gap-2">
          <AlertDialogTitle class="text-lg font-bold">
            {{ confirm_state.options.title }}
          </AlertDialogTitle>
          <AlertDialogDescription
            v-if="confirm_state.options.message"
            class="text-sm text-muted-foreground"
          >
            {{ confirm_state.options.message }}
          </AlertDialogDescription>
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
      </AlertDialogContent>
    </AlertDialogPortal>
  </AlertDialogRoot>
</template>
