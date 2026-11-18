<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useGroupsStore } from '~/stores/groups.js'

// Legacy source: resources/js/components/DashboardNoGroups.vue. Nested
// inside DashboardYourGroups.vue's own "Your Groups" panel as the empty
// state (no heading/divider of its own - the parent already supplies
// "Your Groups" + the dashed rule above this content), not a standalone
// panel of its own. Verified against the live legacy dashboard: with no
// location set, the photo never renders at all (a CSS class bug collapses
// it to zero height), so that's replicated here as a straight v-if rather
// than an invisible box.
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
const groupsStore = useGroupsStore()

// Which row is mid-request, so its button can be disabled. The store handles
// the optimistic update, revert and error toast.
const joining = ref(null)

async function join(id) {
  joining.value = id
  try {
    await groupsStore.join(id)
  } catch {
    // groupsStore.join has already toasted and reverted.
  } finally {
    joining.value = null
  }
}
</script>

<template>
  <div data-testid="dashboard-nearby-groups">
    <template v-if="hasLocation">
      <div class="nearby-layout">
        <div class="nearby-layout__pic" />

        <div class="nearby-layout__overlay">
          <!-- eslint-disable-next-line vue/no-v-html -->
          <p v-if="!nearbyGroups.length" data-testid="nearby-groups-empty" v-html="t('dashboard.no_groups')" />
          <!-- eslint-disable-next-line vue/no-v-html -->
          <p v-html="t('dashboard.no_groups_intro')" />
        </div>

        <template v-if="nearbyGroups.length">
          <div class="nearby-layout__groups">
            <h3>{{ t('dashboard.groups_near_you_header') }}</h3>
            <hr>
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
                <!-- DashboardGroup.vue's button hits the join action directly
                     (`/group/join/{id}`). This sent the member to the group
                     page instead, so the button labelled "Join" did not join -
                     a pixel-identical control pointing somewhere else. The
                     endpoint it needs (POST /api/v2/groups/{id}/members/me)
                     has existed since B2; the comment claiming otherwise was
                     stale. -->
                <BButton
                  variant="primary"
                  :disabled="joining === group.id"
                  :data-testid="`nearby-group-join-${group.id}`"
                  @click="join(group.id)"
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
    </template>

    <template v-else>
      <div data-testid="nearby-groups-no-location">
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div v-html="t('groups.no_groups_nearest_no_location')" />
      </div>
    </template>

    <div class="mt-3">
      <strong>{{ t('dashboard.interested_starting') }}</strong>
      <!-- eslint-disable-next-line vue/no-v-html -->
      <div v-html="t('dashboard.interested_details')" />
    </div>
  </div>
</template>

<style scoped lang="scss">
h3 {
  font-size: 1rem;
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
