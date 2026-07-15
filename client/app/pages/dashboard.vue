<script setup>
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '~/stores/auth.js'
import { useSessionStore } from '~/stores/session.js'
import { useDashboardStore } from '~/stores/dashboard.js'
import DashboardYourGroups from '~/components/dashboard/DashboardYourGroups.vue'
import DashboardNearbyGroups from '~/components/dashboard/DashboardNearbyGroups.vue'
import DashboardUpcomingEvents from '~/components/dashboard/DashboardUpcomingEvents.vue'
import DashboardOnboardingModal from '~/components/dashboard/DashboardOnboardingModal.vue'

// resources/views/dashboard/index.blade.php + resources/js/components/
// DashboardPage.vue (+ DashboardYourGroups/DashboardNoGroups/DashboardGroup/
// DashboardEvent) are the functional spec (design.md §6.2 task brief).
definePageMeta({ auth: true })

const { t } = useI18n()
useHead({ title: t('dashboard.title') })

const authStore = useAuthStore()
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
    <h1 data-testid="dashboard-welcome">
      {{ t('client.dashboard.welcome', { name: authStore.user?.name }) }}
    </h1>

    <div v-if="dashboardStore.loading" data-testid="dashboard-loading">
      <div v-for="n in 3" :key="n" class="placeholder-glow mb-3">
        <span class="placeholder col-12" style="height: 6rem" />
      </div>
    </div>

    <BAlert v-else-if="dashboardStore.error" :model-value="true" variant="danger" data-testid="dashboard-error">
      <p>{{ t('client.dashboard.load_error') }}</p>
      <BButton variant="danger" data-testid="dashboard-retry" @click="retry">
        {{ t('client.dashboard.retry') }}
      </BButton>
    </BAlert>

    <div v-else class="dashboard-grid" data-testid="dashboard-content">
      <DashboardYourGroups :groups="yourGroups" />
      <DashboardNearbyGroups
        :nearby-groups="nearbyGroups"
        :new-nearby-groups="newNearbyGroups"
        :has-location="hasLocation"
      />
      <DashboardUpcomingEvents :events="upcomingEvents" class="dashboard-grid__events" />
    </div>

    <DashboardOnboardingModal :show="showOnboarding" @dismiss="dismissOnboarding" />
  </div>
</template>

<style scoped lang="scss">
.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2rem;

  @media (min-width: 768px) {
    grid-template-columns: 2fr 1fr;

    &__events {
      grid-column: 1 / -1;
    }
  }
}
</style>
