<template>
  <Teleport to="body">
    <Transition name="cf-fade">
      <div v-if="state" class="cf-overlay" @click.self="cancel">
        <div class="cf-box" role="dialog" aria-modal="true">
          <div class="cf-icon" :class="{ 'cf-icon--danger': state.danger }">
            <component :is="state.danger ? AlertTriangle : HelpCircle" :size="22" />
          </div>
          <h3 class="cf-title">{{ state.title }}</h3>
          <p v-if="state.message" class="cf-message">{{ state.message }}</p>

          <div v-if="state.withInput" class="cf-field">
            <label v-if="state.inputLabel" class="cf-label">{{ state.inputLabel }}</label>
            <input
              ref="inputEl"
              v-model="state.inputValue"
              type="text"
              class="cf-input"
              :placeholder="state.inputPlaceholder"
              @keyup.enter="confirm"
            />
          </div>

          <div class="cf-actions">
            <button class="ap-btn-outline" @click="cancel">{{ state.cancelText }}</button>
            <button class="ap-btn-primary" :class="{ 'cf-btn--danger': state.danger }" @click="confirm">{{ state.confirmText }}</button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { nextTick, watch, ref } from 'vue'
import { AlertTriangle, HelpCircle } from 'lucide-vue-next'
import { useConfirmState, resolveConfirm } from '@/composables/useConfirm'

const state = useConfirmState()
const inputEl = ref(null)

function confirm() { resolveConfirm(state.value.withInput ? state.value.inputValue : true) }
function cancel() { resolveConfirm(false) }

watch(state, async (v) => {
  if (v?.withInput) { await nextTick(); inputEl.value?.focus() }
})
</script>

<style scoped>
.cf-overlay {
  position: fixed; inset: 0; z-index: 10001;
  background: rgba(15, 23, 42, 0.5);
  display: flex; align-items: center; justify-content: center;
  padding: 1rem;
}
.cf-box {
  background: #fff;
  border-radius: 16px;
  width: 100%; max-width: 400px;
  padding: 1.75rem;
  text-align: center;
  box-shadow: 0 20px 50px rgba(15, 23, 42, 0.28);
}
.cf-icon {
  width: 52px; height: 52px; border-radius: 50%;
  background: #EFF6FF; color: #2563EB;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 1rem;
}
.cf-icon--danger { background: #fef2f2; color: #ef4444; }
.cf-title { font-family: 'Outfit', sans-serif; font-size: 1.15rem; font-weight: 700; color: #1f2937; margin-bottom: 0.4rem; }
.cf-message { font-size: 0.88rem; color: #64748B; line-height: 1.6; }
.cf-field { text-align: left; margin-top: 1rem; }
.cf-label { display: block; font-size: 0.8rem; font-weight: 600; color: #1f2937; margin-bottom: 0.4rem; }
.cf-input {
  width: 100%; font-family: 'Outfit', sans-serif; font-size: 0.9rem;
  padding: 0.7rem 0.9rem; border: 1.5px solid #DBEAFE; border-radius: 8px;
  color: #1f2937; outline: none; transition: border-color 0.2s;
}
.cf-input:focus { border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); }
.cf-actions { display: flex; gap: 0.6rem; justify-content: center; margin-top: 1.5rem; }
.cf-actions .ap-btn-outline, .cf-actions .ap-btn-primary { padding: 0.6rem 1.4rem; }
.cf-btn--danger { background: #ef4444; }
.cf-btn--danger:hover:not(:disabled) { background: #dc2626; }

.cf-fade-enter-active, .cf-fade-leave-active { transition: opacity 0.2s ease; }
.cf-fade-enter-from, .cf-fade-leave-to { opacity: 0; }
</style>
