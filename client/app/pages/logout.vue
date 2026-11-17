<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth.js'

// /logout was a Laravel route on live. The wiki's user menu links to it (UserController::getUserMenus), and so
// does the navbar's Logout, which handles a plain click itself but not one opened in a new tab. Logs out and goes
// to the login page, as the navbar does.
const authStore = useAuthStore()

onMounted(async () => {
  await authStore.logout()
  await navigateTo('/login', { replace: true })
})
</script>

<template>
  <div data-testid="logout-page" />
</template>
