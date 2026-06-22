import { reactive } from "vue";

const default_options = {
  title: "Are you sure?",
  message: "",
  confirmLabel: "Confirm",
  cancelLabel: "Cancel",
  variant: "default",
};

// Module-level singleton: one dialog instance for the whole app.
const confirm_state = reactive({
  open: false,
  options: { ...default_options },
});

let resolve_fn = null;

/**
 * Settle the pending confirm() promise and close the dialog.
 *
 * @param {boolean} result
 */
function resolveConfirm(result) {
  confirm_state.open = false;
  if (resolve_fn) {
    resolve_fn(result);
    resolve_fn = null;
  }
}

/**
 * @typedef {object} ConfirmOptions
 * @property {string} title
 * @property {string} [message]
 * @property {string} [confirmLabel]
 * @property {string} [cancelLabel]
 * @property {'default'|'destructive'} [variant]
 */

/**
 * Open the global confirmation dialog.
 *
 * @param {ConfirmOptions} options
 * @returns {Promise<boolean>} resolves true on confirm, false on cancel/dismiss
 */
function confirm(options) {
  confirm_state.options = { ...default_options, ...options };
  confirm_state.open = true;

  return new Promise((resolve) => {
    resolve_fn = resolve;
  });
}

export function useConfirm() {
  return confirm;
}

export { confirm_state, resolveConfirm };
