<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuth } from '~/composables/useAuth.js'
import { useSessionStore } from '~/stores/session.js'
import { useNetworksStore } from '~/stores/networks.js'
import { useGroupsStore } from '~/stores/groups.js'
import NetworkStats from '~/components/networks/NetworkStats.vue'
import AssociateGroupsModal from '~/components/networks/AssociateGroupsModal.vue'
import NetworkGroupsModerationTable from '~/components/networks/NetworkGroupsModerationTable.vue'
import NetworkEventsModerationTable from '~/components/networks/NetworkEventsModerationTable.vue'
import NetworkTagsManager from '~/components/networks/NetworkTagsManager.vue'
import NetworkGroupsMapList from '~/components/networks/NetworkGroupsMapList.vue'
import NetworkEvents from '~/components/networks/NetworkEvents.vue'
import PeriodToggle from '~/components/networks/PeriodToggle.vue'

// /networks/{id} - resources/views/networks/show.blade.php +
// resources/js/components/NetworkPage.vue (design.md §6.2 Phase E task E1).
// Administrator/coordinator-of-this-network only (NetworkPolicy@view -
// there is no plain "Host can view" case at all, unlike the grouptags
// suite's group-page permission matrix). Since view access and manage
// access (tags CRUD, associate groups - NetworkPolicy@associateGroups) are
// governed by the exact same condition, reaching this page already implies
// "can manage" - there is no separate reduced-permission view here (unlike
// e.g. /group/view/[id] where hosts see a read-only page).
//
// "Network coordinators" section (avatar + name cards), matching legacy
// resources/js/components/NetworkPage.vue: GET /api/v2/networks/{id}'s
// `coordinators` field (App\Http\Resources\Network) now carries
// {id, name, avatar_url} - the App-Coordinators-only gap recorded in
// docs/nuxt-migration/api-gaps.md Phase E has been closed.
//
// "Download event list" links to /export/networks/{id}/events - a PUBLIC
// (anonymous-access) web route in routes/web.php's /export group (the same kind
// of link the fixometer uses for /export/devices), so a plain <a href> works
// from the SPA without a session cookie.
//
// Groups/Events "requiring moderation" sections use the network-specific
// NetworkGroupsModerationTable / NetworkEventsModerationTable components
// (parity-v2/networks.md gap #2 + #11), NOT the shared components/
// moderation/ModerationQueue.vue (since deleted - it rendered a plain
// name-only list develop never had anywhere) - legacy's
// GroupsRequiringModeration/EventsRequiringModeration render the FULL
// GroupsTable/GroupEventScrollTable here, not a bare name list. Section
// ORDER (network page rework): Header -> Groups/Events requiring moderation
// (managers only, at the top because they are the things to act on) -> Impact
// (with the All time / Last 12 months toggle) -> About -> Coordinators (one
// row, "Show N more") -> Upcoming events (calendar by default) -> Groups map
// and list (5 groups, then "Show all") -> Tags (full width, create inputs
// behind "Add tag"). Logo management is NOT here: develop puts it on
// networks/{id}/edit (networks/edit.blade.php), which now exists as
// pages/networks/[id]/edit.vue.
definePageMeta({ auth: true })

const { t, locale } = useI18n()
const route = useRoute()
const { hasRole } = useAuth()
const sessionStore = useSessionStore()
const networksStore = useNetworksStore()
const groupsStore = useGroupsStore()

const id = computed(() => Number(route.params.id))

const isAdministrator = computed(() => hasRole('Administrator'))
const isCoordinatorHere = computed(() => (sessionStore.user?.networks || []).some((n) => n.id === id.value))
const canManage = computed(() => isAdministrator.value || isCoordinatorHere.value)

const network = computed(() => networksStore.current.data)

useHead({ title: computed(() => network.value?.name || t('networks.general.networks')) })

// Impact figures: all time (already embedded in the network) or the last 12
// completed calendar months (GET /api/v2/networks/{id}/stats?period=
// last_12_months, which also says which months it covered). Fetched once per
// period, so switching back is instant.
const period = ref('all')
const statsByPeriod = ref({})
const periodLoading = ref(false)
const periodError = ref(false)

const displayedStats = computed(() =>
  period.value === 'all' ? network.value?.stats : statsByPeriod.value[period.value]
)

const periodLabel = computed(() => {
  const covered = period.value !== 'all' && statsByPeriod.value[period.value]?.period
  if (!covered) return ''
  const fmt = (d) => new Date(`${d}T00:00:00Z`).toLocaleDateString(locale.value, { month: 'long', year: 'numeric', timeZone: 'UTC' })
  return t('networks.stats.period_range', { start: fmt(covered.start), end: fmt(covered.end) })
})

async function setPeriod(next) {
  period.value = next
  periodError.value = false
  if (next === 'all' || statsByPeriod.value[next]) return

  periodLoading.value = true
  try {
    const stats = await networksStore.fetchStats(id.value, { period: next })
    statsByPeriod.value = { ...statsByPeriod.value, [next]: stats }
  } catch {
    periodError.value = true
  } finally {
    // A later toggle may have moved on; only the current period's request
    // controls the spinner.
    if (period.value === next) periodLoading.value = false
  }
}

// Coordinators sit in one row; the rest are behind "Show N more".
const COORDINATORS_SHOWN = 4
const showAllCoordinators = ref(false)
const coordinators = computed(() => network.value?.coordinators || [])
const visibleCoordinators = computed(() =>
  showAllCoordinators.value ? coordinators.value : coordinators.value.slice(0, COORDINATORS_SHOWN)
)

const showDescriptionModal = ref(false)
const showAssociateModal = ref(false)

const truncatedDescription = computed(() => {
  const description = network.value?.description
  if (!description) return ''
  const stripped = stripHtml(description)
  if (stripped.length <= 160) return description
  return stripped.substring(0, 160) + '...'
})

const showReadMore = computed(() => {
  const description = network.value?.description
  if (!description) return false
  return stripHtml(description).length > 160
})

function stripHtml(value) {
  return String(value).replace(/<[^>]*>/g, '')
}

// The tally comes from the network's own stats (App\Network::stats()), which
// counts only non-archived groups. Until they arrive, fall back to the
// non-archived rows of the groups list.
const groupsCount = computed(() => {
  const fromStats = network.value?.stats?.groups
  if (fromStats !== undefined && fromStats !== null) return fromStats
  if (networksStore.groups.loading) return null
  return networksStore.groups.data.filter((g) => !g.archived_at).length
})

// Mirrors app/Network.php#groupsNotIn() exactly (parity-v2/networks.md gap
// #9): every group not already in the network, archived or not - archived
// groups are NOT excluded server-side, so they stay selectable here too.
const candidateGroups = computed(() => {
  const inNetworkIds = new Set(networksStore.groups.data.map((g) => g.id))
  return groupsStore.names.filter((g) => !inNetworkIds.has(g.id)).map((g) => ({ id: g.id, name: g.name }))
})

function loadGroups() {
  const params = {}
  networksStore.fetchGroups(id.value, params)
}


function loadNetwork() {
  networksStore.fetchCurrent(id.value)
  networksStore.fetchTags(id.value)
  loadGroups()
}

onMounted(() => {
  if (!isAdministrator.value && !isCoordinatorHere.value) {
    navigateTo('/forbidden')
    return
  }

  loadNetwork()
  groupsStore.fetchNames().catch(() => {})
})

function retry() {
  loadNetwork()
}
</script>

<template>
  <div class="container py-4" data-testid="network-show-page">
    <div v-if="networksStore.current.loading" data-testid="network-show-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 6rem" />
      </div>
    </div>

    <BAlert
      v-else-if="networksStore.current.error?.status === 404"
      :model-value="true"
      variant="warning"
      data-testid="network-show-not-found"
    >
      {{ t('client.networks.not_found') }}
    </BAlert>

    <BAlert v-else-if="networksStore.current.error" :model-value="true" variant="danger" data-testid="network-show-load-error">
      <p>{{ t('client.networks.load_error') }}</p>
      <BButton variant="danger" data-testid="network-show-retry" @click="retry">{{ t('client.dashboard.retry') }}</BButton>
    </BAlert>

    <template v-else-if="network">
      <div class="d-flex align-items-center mb-4">
        <img v-if="network.logo" :src="network.logo" :alt="t('client.networks.logo_alt', { name: network.name })" class="me-4 img-fluid" style="max-height: 60px">
        <div class="flex-grow-1">
          <h1 data-testid="network-show-name">{{ network.name }}</h1>
          <a v-if="network.website" :href="network.website" target="_blank" rel="noopener noreferrer" class="text-muted" data-testid="network-show-website">
            {{ network.website }}
          </a>
        </div>
        <!-- NetworkPage.vue: the dropdown itself is gated on being logged
             in, only its add-groups item on canAssociateGroups. develop
             dropped its "View groups" item when the Groups section became an
             inline map and list. -->
        <BDropdown variant="primary" placement="bottom-end" :text="t('networks.general.actions')" data-testid="network-show-actions">
          <BDropdownItem v-if="canManage" data-testid="network-show-add-groups" @click="showAssociateModal = true">
            {{ t('networks.show.add_groups_menuitem') }}
          </BDropdownItem>
          <BDropdownItem
            v-if="network.reporting_url"
            :href="network.reporting_url"
            target="_blank"
            rel="noopener"
            link-class="network-reporting-link"
            data-testid="network-show-reports"
          >
            {{ t('networks.general.view_reports') }}
          </BDropdownItem>
          <BDropdownItem :href="`/export/networks/${network.id}/events`" data-testid="network-show-export-events">
            {{ t('groups.export_event_list') }}
          </BDropdownItem>
        </BDropdown>
      </div>

      <!-- Groups/events awaiting moderation, for Administrators and this
           network's coordinators, at the top: they are what needs acting on.
           The network-specific rich tables (gap #2/#11), not a plain
           name-only list. Always-visible heading + "None" fallback,
           matching legacy NetworkPage.vue. -->
      <template v-if="canManage">
        <section class="mb-4" data-testid="network-groups-moderation">
          <h2>{{ t('networks.moderation.groups_title') }}</h2>
          <NetworkGroupsModerationTable :network-id="id">
            <template #empty>
              <div class="text-muted" data-testid="network-groups-moderation-empty">
                {{ t('networks.show.none') }}
              </div>
            </template>
          </NetworkGroupsModerationTable>
        </section>
        <section class="mb-4" data-testid="network-events-moderation">
          <h2>{{ t('networks.moderation.events_title') }}</h2>
          <NetworkEventsModerationTable :network-id="id" />
        </section>
      </template>

      <section class="mb-4" data-testid="network-show-impact">
        <h2>{{ t('networks.general.impact') }}</h2>
        <PeriodToggle class="mb-2" :model-value="period" @update:model-value="setPeriod" />
        <p v-if="periodLabel" class="text-muted small" data-testid="network-show-period-label">{{ periodLabel }}</p>
        <BAlert v-if="periodError" :model-value="true" variant="danger" data-testid="network-show-period-error">
          {{ t('client.networks.stats_unavailable') }}
        </BAlert>
        <NetworkStats :stats="displayedStats" :groups-count="groupsCount" :loading="periodLoading" />
        <p v-if="network.reporting_url" class="mt-3 mb-0 text-end">
          <a
            :href="network.reporting_url"
            target="_blank"
            rel="noopener"
            class="network-reporting-inline"
            data-testid="network-show-reports-link"
          >{{ t('networks.general.see_network_reports') }}</a>
        </p>
      </section>

      <section v-if="network.description" class="mb-4">
        <h2>{{ t('networks.general.about') }}</h2>
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div v-html="truncatedDescription" />
        <button v-if="showReadMore" type="button" class="btn btn-link p-0" data-testid="network-show-read-more" @click="showDescriptionModal = true">
          {{ t('partials.read_more') }}
        </button>
      </section>

      <section v-if="coordinators.length" class="mb-4" data-testid="network-show-coordinators">
        <h2>{{ t('networks.general.coordinators') }}</h2>
        <div class="d-flex flex-wrap align-items-center gap-3">
          <NuxtLink
            v-for="coordinator in visibleCoordinators"
            :key="coordinator.id"
            :to="`/profile/${coordinator.id}`"
            class="d-flex align-items-center p-2 pe-3 text-decoration-none coordinator-card"
            :data-testid="`network-coordinator-${coordinator.id}`"
          >
            <img
              :src="coordinator.avatar_url || '/images/placeholder-avatar.webp'"
              alt=""
              width="40"
              height="40"
              class="rounded-circle coordinator-avatar"
            >
            <span class="coordinator-name">{{ coordinator.name }}</span>
          </NuxtLink>
          <button
            v-if="coordinators.length > COORDINATORS_SHOWN"
            type="button"
            class="btn btn-link"
            data-testid="network-show-coordinators-toggle"
            @click="showAllCoordinators = !showAllCoordinators"
          >
            {{ showAllCoordinators ? t('networks.show.coordinators_fewer') : t('networks.show.coordinators_more', { count: coordinators.length - COORDINATORS_SHOWN }) }}
          </button>
        </div>
      </section>

      <section class="mb-4" data-testid="network-show-events">
        <h2>{{ t('networks.events.title') }}</h2>
        <NetworkEvents :network-id="id" />
      </section>

      <!-- NetworkPage.vue: a map and filterable list of the network's
           groups, starting zoomed out so every group is in view. -->
      <section class="groups-section mb-4">
        <h2>{{ t('networks.general.groups') }}</h2>
        <NetworkGroupsMapList :network-id="id" :can-manage-tags="canManage" />
      </section>

      <!-- Tag management uses the full width (legacy NetworkPage.vue
           confined it to half at >=992px). -->
      <section v-if="canManage" class="mb-4" data-testid="tags-management">
        <h2>{{ t('networks.tags.title') }}</h2>
        <NetworkTagsManager :network-id="id" />
      </section>

      <BModal :model-value="showDescriptionModal" :title="network.name" size="lg" ok-only data-testid="network-description-modal" @hide="showDescriptionModal = false">
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div v-html="network.description" />
      </BModal>

      <AssociateGroupsModal
        :show="showAssociateModal"
        :network-id="id"
        :network-name="network.name"
        :candidates="candidateGroups"
        @close="showAssociateModal = false"
      />
    </template>
  </div>
</template>

<style scoped lang="scss">
// Coordinator pill cards (parity-v2/networks.md gap #7): matches legacy
// NetworkPage.vue's .coordinator-card exactly - 2px solid black border,
// pill radius, hover border-color/box-shadow, 0.75rem internal gap, cover-
// fit avatar and bold nowrap name - rather than bare Bootstrap utility
// classes with no hover affordance.
.coordinator-card {
  gap: 0.75rem;
  border: 2px solid #222;
  border-radius: 50px;
  background: #fff;
  color: inherit;
  transition: box-shadow 0.2s, border-color 0.2s;

  &:hover {
    border-color: #4aaebc;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    text-decoration: none;
  }
}

.coordinator-avatar {
  object-fit: cover;
}

.coordinator-name {
  font-weight: 500;
  white-space: nowrap;
}
</style>
