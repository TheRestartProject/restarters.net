<script setup>
// The icon at the start of a save button, driven by useSpinButton(): the save
// (floppy disk) icon, a spinner while saving, then a tick once saved.
defineProps({
  state: {
    type: String,
    default: 'idle',
  },
})
</script>

<template>
  <span v-if="state === 'spinning'" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" data-testid="spin-icon-spinning" />
  <svg v-else-if="state === 'done'" width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1" aria-hidden="true" data-testid="spin-icon-done">
    <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z" />
  </svg>
  <svg v-else width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1" aria-hidden="true" data-testid="spin-icon-idle">
    <path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4.414a1 1 0 0 0-.293-.707l-2.414-2.414A1 1 0 0 0 11.586 1H2zm0 1h9.586L14 4.414V14H2V2zm2 0h5v3H4V2zm-1 5h10v7H3V7z" />
  </svg>
</template>
