<script setup>
import { useToastStore } from '~/stores/toast.js'

// Renders the toast queue (stores/toast.js) as bootstrap-vue-next BToasts.
// Kept deliberately simple: fixed position, one BToast per queued entry,
// auto-dismiss driven by BToast's own :model-value/@close.
const toastStore = useToastStore()
</script>

<template>
  <div class="toast-container position-fixed bottom-0 end-0 p-3" data-testid="toast-container" style="z-index: 2000;">
    <BToast
      v-for="toast in toastStore.toasts"
      :key="toast.id"
      :model-value="true"
      :variant="toast.variant"
      :data-testid="`toast-${toast.id}`"
      @close="toastStore.dismiss(toast.id)"
    >
      {{ toast.message }}
    </BToast>
  </div>
</template>
