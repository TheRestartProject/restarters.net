<script setup>
import { useI18n } from 'vue-i18n'

defineProps({
  nearbyGroups: {
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
    <div class="d-flex align-items-center">
      <h2 class="mb-0">{{ t('dashboard.groups_near_you_header') }}</h2>
    </div>

    <div class="content-divider">
      <div class="nearby-layout">
        <div class="nearby-layout__pic" />

        <div class="nearby-layout__overlay">
          <div v-if="!hasLocation" data-testid="nearby-groups-no-location">
            <!-- eslint-disable-next-line vue/no-v-html -->
            <div v-html="t('groups.no_groups_nearest_no_location')" />
          </div>

          <div v-else-if="!nearbyGroups.length" data-testid="nearby-groups-empty">
            <!-- eslint-disable-next-line vue/no-v-html -->
            <p v-html="t('dashboard.no_groups')" />
            <!-- eslint-disable-next-line vue/no-v-html -->
            <p v-html="t('dashboard.no_groups_intro')" />
          </div>
        </div>

        <template v-if="hasLocation && nearbyGroups.length">
          <div class="nearby-layout__groups">
            <ul class="list-unstyled" data-testid="nearby-groups-list">
              <li
                v-for="group in nearbyGroups"
                :key="group.id"
                class="d-flex justify-content-between align-items-center py-2 border-bottom"
                :data-testid="`nearby-group-${group.id}`"
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
          </div>
        </template>
      </div>

      <div class="mt-3">
        <strong>{{ t('dashboard.interested_starting') }}</strong>
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div v-html="t('dashboard.interested_details')" />
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
h2 {
  font-size: 1.1rem;
  font-weight: bold;
}

.group-avatar {
  border: 1px solid #222;
}

.nearby-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;

  &__pic {
    min-height: 160px;
    background-image: url('/images/no_groups.png');
    background-size: cover;
    background-position: center;
  }

  @media (min-width: 768px) {
    grid-template-columns: 1fr 1fr;
    align-items: center;

    &__overlay {
      grid-column: 1 / 2;
      grid-row: 1;
    }

    &__pic {
      grid-column: 2 / 3;
      grid-row: 1;
      min-height: unset;
      height: 100%;
    }

    &__groups {
      grid-column: 1 / 3;
      grid-row: 2;
    }
  }
}
</style>
