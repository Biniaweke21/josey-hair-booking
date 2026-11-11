import { ref } from 'vue'

// Module-level queue shared across the app.
const toasts = ref([])
let seq = 0

function push(type, message, timeout = 3200) {
  const id = ++seq
  toasts.value.push({ id, type, message })
  setTimeout(() => dismiss(id), timeout)
  return id
}

function dismiss(id) {
  toasts.value = toasts.value.filter((t) => t.id !== id)
}

export function useToast() {
  return {
    toasts,
    dismiss,
    success: (msg) => push('success', msg),
    error: (msg) => push('error', msg, 5000),
    info: (msg) => push('info', msg)
  }
}
