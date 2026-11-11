<template>
  <Teleport to="body">
    <div class="toast-host" role="status" aria-live="polite">
      <TransitionGroup name="toast">
        <div v-for="t in toasts" :key="t.id" class="toast" :class="'toast--' + t.type" @click="dismiss(t.id)">
          <component :is="icon(t.type)" :size="18" class="toast__icon" />
          <span class="toast__msg">{{ t.message }}</span>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { CheckCircle2, AlertCircle, Info } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

const { toasts, dismiss } = useToast()
function icon(type) { return type === 'success' ? CheckCircle2 : type === 'error' ? AlertCircle : Info }
</script>

<style scoped>
.toast-host {
  position: fixed;
  top: 74px;
  right: 20px;
  z-index: 10000;
  display: flex;
  flex-direction: column;
  gap: 10px;
  pointer-events: none;
}
.toast {
  pointer-events: auto;
  display: flex;
  align-items: center;
  gap: 0.6rem;
  min-width: 240px;
  max-width: 360px;
  padding: 0.8rem 1rem;
  border-radius: 12px;
  background: #fff;
  border: 1px solid #E5E7EB;
  box-shadow: 0 8px 28px rgba(15, 23, 42, 0.14);
  font-size: 0.86rem;
  font-weight: 600;
  color: #1f2937;
  cursor: pointer;
}
.toast__icon { flex-shrink: 0; }
.toast__msg { line-height: 1.35; }
.toast--success { border-left: 4px solid #16a34a; }
.toast--success .toast__icon { color: #16a34a; }
.toast--error { border-left: 4px solid #ef4444; }
.toast--error .toast__icon { color: #ef4444; }
.toast--info { border-left: 4px solid #2563EB; }
.toast--info .toast__icon { color: #2563EB; }

.toast-enter-active, .toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from { opacity: 0; transform: translateX(20px); }
.toast-leave-to { opacity: 0; transform: translateX(20px); }
@media (max-width: 640px) {
  .toast-host { top: auto; bottom: 20px; left: 16px; right: 16px; }
  .toast { max-width: 100%; }
}
</style>
