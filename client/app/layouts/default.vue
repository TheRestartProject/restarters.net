<script setup>
import AppNavbar from '~/components/AppNavbar.vue'
import AppFooter from '~/components/AppFooter.vue'
import ToastContainer from '~/components/ToastContainer.vue'
</script>

<template>
  <div class="d-flex flex-column min-vh-100">
    <AppNavbar />
    <main class="flex-grow-1">
      <slot />
    </main>
    <AppFooter />
    <ToastContainer />
  </div>
</template>
