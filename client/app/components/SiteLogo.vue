<script setup>
import { computed } from 'vue'
import { useSessionStore } from '~/stores/session.js'
import IconLogo from '~/components/icons/IconLogo.vue'

// Live's includes/logo.blade.php: a network's own site (repairtogether.restarters.net
// etc.) shows that network's logo in place of the Restarters wordmark. The
// session's `site` says which site this is (SessionController::sitePayload).
const sessionStore = useSessionStore()
const site = computed(() => sessionStore.site)
</script>

<template>
  <img
    v-if="site?.logo"
    :src="site.logo"
    :alt="site.name"
    height="49"
    data-testid="site-logo-network"
  >
  <IconLogo v-else />
</template>
