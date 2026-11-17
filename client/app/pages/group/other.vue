<script setup>
// Live's /group/other (the "Find a group" tab, which groups.no_groups_mine
// links to). In the client that tab is /group/map, so this forwards there,
// as /group/nearby does.
definePageMeta({ auth: true })

// Query preserved: e.g. ?network=N must survive the forward.
navigateTo({ path: '/group/map', query: useRoute().query }, { replace: true })
</script>

<template>
  <div data-testid="group-other-redirect" />
</template>
