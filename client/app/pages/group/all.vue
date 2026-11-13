<script setup>
// PR 887 (RES-1995) reworks /group to two tabs, dropping the separate All
// Groups list - every group is on the map, with the list panel below it.
// The route survives only as a forward so old links and bookmarks keep
// working. What this page's list had beyond the map's panel (full-list
// filter bar, tag badges, archived groups) is 887 GroupMapAndList list
// functionality still to be ported into pages/group/map.vue.
definePageMeta({ auth: true })

// Query preserved: the networks page links here as /group/all?network=N,
// and the filter must survive the forward.
navigateTo({ path: '/group/map', query: useRoute().query }, { replace: true })
</script>

<template>
  <div data-testid="group-all-redirect" />
</template>
