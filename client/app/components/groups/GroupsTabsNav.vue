<script setup>
import { useI18n } from 'vue-i18n'

// Tab nav shared by /group, /group/nearby, /group/all. The legacy
// GroupsPage.vue keeps all three as panels of one <b-tabs> and rewrites the
// URL on tab change; here each is its own Nuxt page/route (design.md §6.2 B4
// task brief), so this is just a small nav bar highlighting the active one.
const props = defineProps({
  active: {
    type: String,
    required: true,
    validator: (value) => ['mine', 'nearby', 'all'].includes(value),
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
