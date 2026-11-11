<template>
  <button
    :class="['btn', `btn--${variant}`, { 'btn--disabled': disabled }]"
    :disabled="disabled"
    @click="$emit('click', $event)"
  >
    <slot />
  </button>
</template>

<script setup>
defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'secondary', 'danger'].includes(v)
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

defineEmits(['click'])
</script>

<style scoped>
.btn {
  padding: 0.5rem 1.25rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 1rem;
  font-weight: 500;
  transition: opacity 0.2s;
}

.btn--primary {
  background-color: #42b883;
  color: white;
}

.btn--secondary {
  background-color: #6c757d;
  color: white;
}

.btn--danger {
  background-color: #dc3545;
  color: white;
}

.btn--disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
