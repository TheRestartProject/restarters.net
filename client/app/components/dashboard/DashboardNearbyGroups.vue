<script setup>
import { useI18n } from 'vue-i18n'

defineProps({
  nearbyGroups: {
    type: Array,
    default: () => [],
  },
  newNearbyGroups: {
    type: Array,
    default: () => [],
  },
  // Whether the user has a location set (lat/lng). api-contracts-phase-b.md's
  // B1 nearby_groups is [] both when the user has no location AND when they
  // simply have no groups nearby - the two states need different copy/CTAs,
  // so a `location` field is a recorded gap (docs/nuxt-migration/api-gaps.md)
  // on the dashboard response; this prop defaults to true (i.e. "assume they
  // have a location, so an empty list means truly empty") until that lands.
  hasLocation: {
    type: Boolean,
    default: true,
  },
})

const { t } = useI18n()
</script>

<template>
  <div data-testid="dashboard-nearby-groups">
    <h2>{{ t('dashboard.groups_near_you_header') }}</h2>

    <div v-if="!hasLocation" data-testid="nearby-groups-no-location">
      <!-- eslint-disable-next-line vue/no-v-html -->
      <div v-html="t('groups.no_groups_nearest_no_location')" />
    </div>

    <template v-else>
      <div v-if="newNearbyGroups.length" class="mb-2" data-testid="nearby-groups-new-highlight">
        <NuxtLink to="/group/nearby">
          {{ t('dashboard.newly_added', { count: newNearbyGroups.length }, newNearbyGroups.length) }}
        </NuxtLink>
      </div>

      <div v-if="!nearbyGroups.length" data-testid="nearby-groups-empty">
        <!-- eslint-disable-next-line vue/no-v-html -->
        <p v-html="t('dashboard.no_groups')" />
        <!-- eslint-disable-next-line vue/no-v-html -->
        <p v-html="t('dashboard.no_groups_intro')" />
      </div>

      <template v-else>
        <ul class="list-unstyled" data-testid="nearby-groups-list">
          <li
            v-for="group in nearbyGroups"
            :key="group.id"
            class="d-flex justify-content-between align-items-center py-2 border-bottom"
            :data-testid="`nearby-group-${group.id}`"
          >
            <div class="d-flex align-items-center">
              <img
                :src="group.image_url || '/images/placeholder-avatar.png'"
                alt=""
                width="48"
                height="48"
                class="rounded-circle me-2"
              >
              <div>
                <NuxtLink :to="`/group/view/${group.id}`" :data-testid="`nearby-group-link-${group.id}`">
                  {{ group.name }}
                </NuxtLink>
                <div class="small">
                  <span v-if="group.location" :data-testid="`nearby-group-location-${group.id}`">
                    {{ group.location }}
                  </span>
                  <span v-if="group.distance != null" :data-testid="`nearby-group-distance-${group.id}`">
                    ({{ group.distance }} km)
                  </span>
                </div>
              </div>
            </div>
            <!-- The real self-join action lands with B2/B4-B5 (POST
                 /api/v2/groups/{id}/members/me); until then this CTA sends
                 the member to the group page where joining will happen. -->
            <BButton
              variant="primary"
              :to="`/group/view/${group.id}`"
              :data-testid="`nearby-group-join-${group.id}`"
            >
              {{ t('groups.join_group_button') }}
            </BButton>
          </li>
        </ul>

        <div class="d-flex justify-content-end">
          <NuxtLink to="/group/nearby" data-testid="nearby-groups-see-all">
            {{ t('dashboard.see_all_groups_near_you') }}
          </NuxtLink>
        </div>
      </template>
    </template>

    <div class="mt-3">
      <strong>{{ t('dashboard.interested_starting') }}</strong>
      <!-- eslint-disable-next-line vue/no-v-html -->
      <div v-html="t('dashboard.interested_details')" />
    </div>
  </div>
</template>

<style scoped lang="scss">
h2 {
  font-size: 1.1rem;
  font-weight: bold;
}
</style>
