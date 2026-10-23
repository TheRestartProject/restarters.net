<script setup>
// PR 887 (RES-1995) reworks /group to two tabs, folding "Find a group"
// into the map tab - the plain nearby list this page used to render (see
// its history) has no place in that layout. The route survives only as a
// forward, so old links and bookmarks (and map.vue's group_count_none
// inline link) keep working.
definePageMeta({ auth: true })

// Query preserved: e.g. ?network=N must survive the forward.
navigateTo({ path: '/group/map', query: useRoute().query }, { replace: true })
</script>

<template>
  <div data-testid="group-nearby-redirect" />
</template>
