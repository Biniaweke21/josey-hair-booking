import { ref } from 'vue'

// Single shared dialog state; the <ConfirmDialog> host renders it.
const state = ref(null)

export function useConfirmState() {
  return state
}

/**
 * Promise-based confirmation. Resolves:
 *   - `false` if cancelled/dismissed
 *   - `true` if confirmed (no input)
 *   - the input string (may be '') if `withInput` is set and confirmed
 *
 * @returns {(opts: object) => Promise<boolean|string>}
 */
export function useConfirm() {
  return (opts = {}) =>
    new Promise((resolve) => {
      state.value = {
        title: 'Are you sure?',
        message: '',
        confirmText: 'Confirm',
        cancelText: 'Cancel',
        danger: false,
        withInput: false,
        inputLabel: '',
        inputPlaceholder: '',
        inputValue: '',
        ...opts,
        resolve
      }
    })
}

export function resolveConfirm(value) {
  if (state.value) {
    state.value.resolve(value)
    state.value = null
  }
}
