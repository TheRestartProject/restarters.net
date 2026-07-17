<script setup>
import { useI18n } from 'vue-i18n'

// Tab nav shared by /group, /group/nearby, /group/all, /group/map. The
// legacy GroupsPage.vue keeps "mine"/"nearby" as panels of one <b-tabs> (the
// map lived inside the "nearby" panel - GroupMapAndList.vue) and rewrites the
// URL on tab change; here each is its own Nuxt page/route (design.md §6.2 B4
// task brief; B7 adds "map" as a route of its own rather than a panel, since
// /group/nearby was already ported as a plain list - see stores/groups.js's
// B7 doc comment), so this is just a small nav bar highlighting the active
// one.
const props = defineProps({
  active: {
    type: String,
    required: true,
    validator: (value) => ['mine', 'nearby', 'all', 'map'].includes(value),
  },
})

const { t } = useI18n()

function isActive(tab) {
  return props.active === tab
}
</script>

<template>
  <nav class="groups-tabs" data-testid="groups-tabs">
    <NuxtLink
      to="/group"
      class="groups-tab"
      :class="{ active: isActive('mine') }"
      data-testid="groups-tab-mine"
      :aria-current="isActive('mine') ? 'page' : undefined"
    >
      {{ t('groups.groups_title1') }}
    </NuxtLink>
    <NuxtLink
      to="/group/nearby"
      class="groups-tab"
      :class="{ active: isActive('nearby') }"
      data-testid="groups-tab-nearby"
      :aria-current="isActive('nearby') ? 'page' : undefined"
    >
      {{ t('groups.groups_title2') }}
    </NuxtLink>
    <NuxtLink
      to="/group/all"
      class="groups-tab"
      :class="{ active: isActive('all') }"
      data-testid="groups-tab-all"
      :aria-current="isActive('all') ? 'page' : undefined"
    >
      {{ t('groups.all_groups') }}
    </NuxtLink>
    <NuxtLink
      to="/group/map"
      class="groups-tab"
      :class="{ active: isActive('map') }"
      data-testid="groups-tab-map"
      :aria-current="isActive('map') ? 'page' : undefined"
    >
      {{ t('client.groups.map_tab') }}
    </NuxtLink>
  </nav>
</template>

<style scoped lang="scss">
.groups-tabs {
  display: flex;
  gap: 1rem;
  border-bottom: 2px solid #dee2e6;
  margin-bottom: 1rem;
}

.groups-tab {
  padding: 0.5rem 0.25rem;
  text-transform: uppercase;
  font-weight: bold;

  &.active {
    border-bottom: 2px solid var(--bs-primary, #0394a6);
    margin-bottom: -2px;
  }
}
</style>
