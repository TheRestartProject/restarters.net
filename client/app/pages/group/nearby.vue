<script setup>
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useGroupsStore } from '~/stores/groups.js'
import { useDashboardStore } from '~/stores/dashboard.js'
import { useProfileStore } from '~/stores/profile.js'
import { useAuth } from '~/composables/useAuth.js'
import GroupsTabsNav from '~/components/groups/GroupsTabsNav.vue'
import GroupsTable from '~/components/groups/GroupsTable.vue'
import ModerationQueue from '~/components/moderation/ModerationQueue.vue'

// /group/nearby - resources/views/group/index.blade.php (tab="nearby") +
// resources/js/components/GroupsPage.vue's "Other groups nearby" tab is the
// functional spec. Uses the same GroupsTable as /group and /group/all
// (parity-v2/groups-lists.md gap #5) rather than a bespoke card list.
definePageMeta({ auth: true })

const { t } = useI18n()
useHead({ title: t('groups.groups') })

const groupsStore = useGroupsStore()
const dashboardStore = useDashboardStore()
const profileStore = useProfileStore()

// Groups-requiring-moderation queue for Administrators / NetworkCoordinators
// (legacy: GroupsRequiringModeration rendered once, above the shared tabs,
// on every tab of /group - parity-v2/groups-lists.md gap #2).
const { hasRole } = useAuth()
const showModeration = computed(() => hasRole('Administrator') || hasRole('NetworkCoordinator'))

// Whether the user has a town/city set. Legacy's "Other groups nearby" tab
// shows a distinct "set a location in your profile" prompt when they don't,
// vs the plain "nothing nearby" copy when they do. GET /api/v2/groups/nearby
// carries no such flag, but GET /api/v2/dashboard does (`has_location`, the
// same source the dashboard + /party pages use). Default true so we never
// flash the location prompt while the dashboard is still loading.
const hasLocation = computed(() => dashboardStore.data?.has_location ?? true)
const groups = computed(() => groupsStore.nearby.data ?? [])

// The user's own town/city text for the "within 50 km of :location" heading
// (gap #12) - not carried by dashboard's has_location or GET
// /api/v2/groups/nearby, so sourced from the same profile-info endpoint
// /profile/edit itself reads (ProfileInfoTab.vue's `data.location`).
const userLocation = computed(() => profileStore.info.data?.location ?? '')

// GET /api/v2/groups/nearby's Group::toNearbySummary() only returns
// {id, name, distance, location, image_url} (app/Group.php) - no
// hosts/restarters/next_event, the same acknowledged gap as /group (mine)'s
// dashboard-sourced rows (see index.vue's own doc comment) - those columns
// stay off here for the same reason. `location` here is a bare string, not
// the {location, country} shape GroupsTable's other pages hydrate from the
// Group/GroupSummary resources, so it's wrapped to match.
const rows = computed(() =>
  groups.value.map((group) => ({
    id: group.id,
    name: group.name,
    image: group.image_url,
    location: group.location ? { location: group.location, country: null } : null,
    isMember: groupsStore.isMember(group.id),
  }))
)

function retry() {
  groupsStore.fetchNearby()
}

onMounted(() => {
  groupsStore.fetchNearby()
  dashboardStore.fetch().catch(() => {})
  profileStore.fetchProfileInfo().catch(() => {})
})
</script>

<template>
  <div class="container py-4" data-testid="group-nearby-page">
    <!-- Legacy's group.index.blade.php renders GroupsRequiringModeration
         BEFORE GroupsPage (whose own template opens with this h1) - not
         after it. See all.vue's matching comment. -->
    <ModerationQueue v-if="showModeration" type="groups" />

    <h1 class="d-flex justify-content-between align-items-start">
      <span class="d-flex align-items-center">
        {{ t('groups.groups') }}
        <img src="/images/group_doodle_ico.svg" alt="" class="ms-3" style="height: 76px">
      </span>
      <NuxtLink to="/group/create" class="btn btn-primary" data-testid="group-create-link">
        <span class="d-block d-lg-none">{{ t('groups.create_groups_mobile2') }}</span>
        <span class="d-none d-lg-block">{{ t('groups.create_groups') }}</span>
      </NuxtLink>
    </h1>

    <GroupsTabsNav active="nearby">
      <div v-if="groupsStore.nearby.loading" data-testid="group-nearby-loading">
        <div class="placeholder-glow mb-3">
          <span class="placeholder col-12" style="height: 6rem" />
        </div>
      </div>

      <BAlert
        v-else-if="groupsStore.nearby.error"
        :model-value="true"
        variant="danger"
        data-testid="group-nearby-error"
      >
        <p>{{ t('client.groups.load_error') }}</p>
        <BButton variant="danger" data-testid="group-nearby-retry" @click="retry">
          {{ t('client.dashboard.retry') }}
        </BButton>
      </BAlert>

      <template v-else>
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div v-if="!hasLocation" data-testid="group-nearby-no-location" v-html="t('groups.no_groups_nearest_no_location')" />
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div v-else-if="!groups.length" data-testid="group-nearby-empty" v-html="t('groups.no_groups_nearest_with_location')" />
        <div v-else data-testid="group-nearby-list">
          <p class="mt-1" data-testid="group-nearby-heading">
            {{ t('groups.nearest_groups', { location: userLocation }) }}
            <NuxtLink to="/profile/edit" class="small" data-testid="group-nearby-location-change">
              {{ t('groups.nearest_groups_change') }}
            </NuxtLink>.
          </p>
          <GroupsTable :groups="rows" :optional-columns="{ location: true, hosts: false, restarters: false, next_event: false }" />
        </div>
      </template>
    </GroupsTabsNav>
  </div>
</template>
