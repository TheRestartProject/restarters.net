<script setup>
import LoadingPlaceholder from '~/components/LoadingPlaceholder.vue'
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useSessionStore } from '~/stores/session.js'
import { useDashboardStore } from '~/stores/dashboard.js'
import DashboardYourGroups from '~/components/dashboard/DashboardYourGroups.vue'
import DashboardRightSidebar from '~/components/dashboard/DashboardRightSidebar.vue'
import DashboardWhatsHappening from '~/components/dashboard/DashboardWhatsHappening.vue'
import DashboardOnboardingModal from '~/components/dashboard/DashboardOnboardingModal.vue'

// resources/views/dashboard/index.blade.php + resources/js/components/
// DashboardPage.vue (+ DashboardYourGroups/DashboardNoGroups/DashboardGroup/
// DashboardEvent/DashboardRightSidebar/DiscourseDiscussion) are the
// functional spec (design.md §6.2 task brief). Section order (verified
// against the live legacy dashboard, both breakpoints): welcome header ->
// Getting started -> Your Groups (single panel; DashboardNoGroups nests
// inside it as the empty state, and Upcoming events nests inside it as the
// has-groups branch's right-hand column - legacy's dyg-layout, not separate
// panels) -> What's happening. Legacy's DashboardAddData.vue is deliberately
// not ported: it only renders for events with `attended` set, a field
// PartyController::expandEvent() never sets, so live never shows it. On
// desktop legacy sits Your Groups in a left column beside Getting started on the right -
// dashboard-grid below reproduces that with grid-template-areas while
// keeping DOM order the mobile (stacked) order.
definePageMeta({ auth: true })

const { t } = useI18n()
useHead({ title: t('dashboard.title') })

const sessionStore = useSessionStore()
const dashboardStore = useDashboardStore()

const yourGroups = computed(() => dashboardStore.data?.your_groups ?? [])
const nearbyGroups = computed(() => dashboardStore.data?.nearby_groups ?? [])
const newNearbyGroups = computed(() => dashboardStore.data?.new_nearby_groups ?? [])
const upcomingEvents = computed(() => dashboardStore.data?.upcoming_events ?? [])

// api-contracts-phase-b.md's B1 shape has no field distinguishing "no
// location set" from "no groups nearby" - recorded as a gap
// (docs/nuxt-migration/api-gaps.md). Until the dashboard response carries
// one, fall back to an optimistic `true` (i.e. don't show the "set your
// location" prompt) unless the backend explicitly says otherwise.
const hasLocation = computed(() => dashboardStore.data?.has_location ?? true)

const showOnboarding = computed(() => !!sessionStore.flags?.onboarding)

function retry() {
  dashboardStore.fetch()
}

function dismissOnboarding() {
  sessionStore.dismissOnboarding()
}

onMounted(() => {
  dashboardStore.fetch()
})
</script>

<template>
  <div class="container py-4" data-testid="dashboard-page">
    <!-- Legacy DashboardPage.vue h1 - a static hero, not personalised: there
         is no "returning user" variant in the legacy spec. -->
    <div class="d-flex justify-content-center align-items-center" data-testid="dashboard-hero">
      <img src="/images/arrows_doodle.svg" alt="" class="d-none d-md-block doodle doodle--arrows">
      <h1 class="mx-2 mb-0" data-testid="dashboard-welcome">
        {{ t('dashboard.title') }}
      </h1>
      <img src="/images/confetti_doodle.svg" alt="" class="d-none d-md-block doodle doodle--confetti">
    </div>

    <!-- dashboard/index.blade.php's .vue-placeholder-large: one 416px "Loading..." area. -->
    <div v-if="dashboardStore.loading" data-testid="dashboard-loading" class="mt-4">
      <LoadingPlaceholder height="416px" />
    </div>

    <BAlert v-else-if="dashboardStore.error" :model-value="true" variant="danger" data-testid="dashboard-error" class="mt-4">
      <p>{{ t('client.dashboard.load_error') }}</p>
      <BButton variant="danger" data-testid="dashboard-retry" @click="retry">
        {{ t('client.dashboard.retry') }}
      </BButton>
    </BAlert>

    <template v-else>
      <div class="dashboard-grid mt-4" data-testid="dashboard-content">
        <DashboardRightSidebar class="dashboard-grid__sidebar" />

        <div class="panel dashboard-grid__yourgroups" data-testid="dashboard-yourgroups-panel">
          <DashboardYourGroups
            :groups="yourGroups"
            :new-nearby-groups="newNearbyGroups"
            :nearby-groups="nearbyGroups"
            :has-location="hasLocation"
            :events="upcomingEvents"
          />
        </div>

        <DashboardWhatsHappening class="dashboard-grid__whatshappening" />
      </div>
    </template>

    <DashboardOnboardingModal :show="showOnboarding" @dismiss="dismissOnboarding" />
  </div>
</template>

<style scoped lang="scss">
.doodle--arrows {
  width: 44px;
  height: auto;
}

.doodle--confetti {
  width: 60px;
  height: auto;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2rem;

  @media (min-width: 768px) {
    grid-template-columns: 2fr 1fr;
    grid-template-areas:
      'yourgroups sidebar'
      'whatshappening whatshappening';

    &__yourgroups {
      grid-area: yourgroups;
    }

    &__sidebar {
      grid-area: sidebar;
    }

    &__whatshappening {
      grid-area: whatshappening;
    }
  }
}
</style>
