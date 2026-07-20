<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import DashboardNearbyGroups from '~/components/dashboard/DashboardNearbyGroups.vue'
import DashboardUpcomingEvents from '~/components/dashboard/DashboardUpcomingEvents.vue'
import CollapsibleSection from '~/components/CollapsibleSection.vue'

// Host role int per app/Role.php::HOST - gates the "Add" (event) button
// DashboardUpcomingEvents shows next to its heading, matching legacy
// DashboardYourGroups.vue's amAHost computed.
const HOST_ROLE = 3

const props = defineProps({
  groups: {
    type: Array,
    default: () => [],
  },
  // Legacy DashboardYourGroups.vue shows the "newly added" banner in the
  // panel header regardless of whether the user already has groups - it's
  // the same newNearbyGroups data DashboardNearbyGroups uses for its own
  // (empty-state) highlight.
  newNearbyGroups: {
    type: Array,
    default: () => [],
  },
  // Legacy DashboardYourGroups.vue's `v-else` (has-groups) branch is the
  // only one with an events section - DashboardNoGroups (nearbyGroups/
  // hasLocation, rendered below via DashboardNearbyGroups) is the empty-state
  // fallback, not a standalone panel of its own.
  nearbyGroups: {
    type: Array,
    default: () => [],
  },
  hasLocation: {
    type: Boolean,
    default: true,
  },
  // Upcoming events for the has-groups branch's right-hand column. Legacy
  // renders Groups + Upcoming events inside ONE panel (its `dyg-layout`
  // two-column grid), not two separate panels side by side.
  events: {
    type: Array,
    default: () => [],
  },
})

const { t } = useI18n()

const sortedGroups = computed(() =>
  [...props.groups].sort((a, b) => a.name.localeCompare(b.name))
)

const amAHost = computed(() => props.groups.some((g) => g.role === HOST_ROLE))
</script>

<template>
  <CollapsibleSection data-testid="dashboard-your-groups">
    <template #title>
      <div class="d-flex justify-content-between flex-wrap align-items-center">
        <div class="d-flex align-items-center">
          <h2 class="mb-0">{{ t('dashboard.your_groups_heading') }}</h2>
          <img src="/images/group_doodle_ico.svg" alt="" class="group-doodle ms-3">
        </div>
        <NuxtLink
          v-if="newNearbyGroups.length"
          to="/group/nearby"
          class="new-highlight px-2"
          data-testid="your-groups-new-highlight"
        >
          <img src="/images/arrow-right-doodle-white.svg" alt="" class="me-1">
          {{ t('dashboard.newly_added', { count: newNearbyGroups.length }, newNearbyGroups.length) }}
        </NuxtLink>
      </div>
    </template>

    <div class="content-divider">
      <div v-if="!sortedGroups.length" data-testid="your-groups-empty">
        <DashboardNearbyGroups :nearby-groups="nearbyGroups" :has-location="hasLocation" />
      </div>

      <div v-else class="dyg-layout">
        <div class="dyg-layout__groups">
          <h3>{{ t('dashboard.groups_heading') }}</h3>
          <p>{{ t('dashboard.catch_up') }}</p>

          <ul class="list-unstyled" data-testid="your-groups-list">
            <li
              v-for="group in sortedGroups"
              :key="group.id"
              class="d-flex justify-content-between align-items-center py-2 border-bottom"
              :data-testid="`your-group-${group.id}`"
            >
              <div class="d-flex align-items-center">
                <img
                  :src="group.image_url || '/images/placeholder-avatar.webp'"
                  alt=""
                  width="48"
                  height="48"
                  class="group-avatar me-2"
                >
                <div>
                  <NuxtLink :to="`/group/view/${group.id}`" :data-testid="`your-group-link-${group.id}`">
                    {{ group.name }}
                  </NuxtLink>
                  <div>
                    <!-- :title omitted when archived_at is unavailable (the
                         dashboard API's your_groups[] doesn't expose it yet -
                         see docs/nuxt-migration/api-gaps.md) rather than
                         interpolating an "undefined" date. -->
                    <BBadge
                      v-if="group.archived"
                      variant="secondary"
                      pill
                      :title="group.archived_at ? t('groups.archived_group_title', { date: group.archived_at }) : null"
                      :data-testid="`your-group-archived-${group.id}`"
                    >
                      {{ t('groups.archived_group') }}
                    </BBadge>
                  </div>
                </div>
              </div>
            </li>
          </ul>

          <div class="d-flex justify-content-end">
            <NuxtLink to="/group" data-testid="your-groups-see-all">
              {{ t('dashboard.see_all_groups') }}
            </NuxtLink>
          </div>
        </div>

        <DashboardUpcomingEvents :events="events" :am-a-host="amAHost" class="dyg-layout__events" />
      </div>
    </div>
  </CollapsibleSection>
</template>

<style scoped lang="scss">
h2 {
  font-size: 1.1rem;
  font-weight: bold;
}

.dyg-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;

  &__groups h3 {
    font-size: 1rem;
    font-weight: bold;
  }

  @media (min-width: 768px) {
    grid-template-columns: 1fr 1fr;
  }
}

.group-doodle {
  height: 40px;
}

.group-avatar {
  border: 1px solid #222;
}

.new-highlight {
  font-size: 0.85rem;
  background-color: #222;
  color: #fff;
  text-decoration: none;
  line-height: 2.5rem;
  align-self: center;

  &:hover {
    color: #fff;
  }
}
</style>
