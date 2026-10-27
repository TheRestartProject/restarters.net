<script setup>
import { useI18n } from 'vue-i18n'

// "Groups near you" on /networks/{id}: the nearest groups in the network to the
// viewer's own groups in it, from GET /api/v2/networks/{id}/viewer's
// `nearby_groups` ({from, groups: [{id, name, location, distance}]}). Renders
// nothing when the viewer has no located group in the network (null).
defineProps({
  nearby: {
    type: Object,
    default: null,
  },
})

const { t } = useI18n()
</script>

<template>
  <section v-if="nearby && nearby.groups.length" class="mb-4" data-testid="network-nearby-groups">
    <h2>{{ t('networks.nearby.title') }}</h2>
    <p class="text-muted">{{ t('networks.nearby.intro', { group: nearby.from }) }}</p>
    <ul class="list-unstyled mb-0">
      <li
        v-for="group in nearby.groups"
        :key="group.id"
        class="d-flex justify-content-between py-1"
        :data-testid="`network-nearby-group-${group.id}`"
      >
        <span>
          <NuxtLink :to="`/group/view/${group.id}`">{{ group.name }}</NuxtLink>
          <span v-if="group.location" class="text-muted ms-2">{{ group.location }}</span>
        </span>
        <span class="text-muted">{{ t('networks.nearby.distance', { distance: group.distance }) }}</span>
      </li>
    </ul>
  </section>
</template>
