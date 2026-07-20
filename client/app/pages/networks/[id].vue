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
import TusImageUpload from '~/components/forms/TusImageUpload.vue'

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
// moderation/ModerationQueue.vue used by /party and /group/map - legacy's
// GroupsRequiringModeration/EventsRequiringModeration render the FULL
// GroupsTable/GroupEventScrollTable here, not a bare name list. Section
// ORDER also matches legacy exactly (gap #6): Header -> Impact -> About ->
// Coordinators -> Groups-requiring-moderation -> Events-requiring-moderation
// -> Groups -> Tags -> (Network logo management, appended at the very end -
// see the section's own comment for why).
definePageMeta({ auth: true })

const { t } = useI18n()
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

const showDescriptionModal = ref(false)
const showAssociateModal = ref(false)
const logoError = ref('')

async function onLogoUploaded({ uploadKey }) {
  logoError.value = ''
  try {
    await networksStore.uploadLogo(id.value, uploadKey)
  } catch {
    logoError.value = t('client.networks.logo_upload_error')
  }
}

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

const groupRows = computed(() =>
  networksStore.groups.data.map((g) => ({
    id: g.id,
    name: g.name,
    archivedAt: g.archived_at,
    location: g.location,
    hosts: g.hosts,
    restarters: g.restarters,
    nextEvent: g.next_event,
  }))
)

const groupsCount = computed(() => (networksStore.groups.loading ? null : networksStore.groups.data.length))

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
        <!-- Single "Network Actions" dropdown (parity-v2/networks.md gap
             #1), matching legacy's b-dropdown, not two always-visible
             loose buttons. Legacy's third item, "View groups", targets
             /group/network/{id} (GroupController@network, an "All Groups"
             index scoped by network) - Nuxt's /group/all has no equivalent
             network-scoping query param (out of scope: it's a group* page)
             and this page's own Groups section below already lists every
             group in the network inline (an intentional, richer
             replacement - see that section's comment), so "View groups"
             would be a dead link here and is deliberately dropped rather
             than ported to a non-functional target. -->
        <BDropdown v-if="canManage" variant="primary" placement="bottom-end" :text="t('networks.general.actions')" data-testid="network-show-actions">
          <BDropdownItem data-testid="network-show-add-groups" @click="showAssociateModal = true">
            {{ t('networks.show.add_groups_menuitem') }}
          </BDropdownItem>
          <BDropdownItem :href="`/export/networks/${network.id}/events`" data-testid="network-show-export-events">
            {{ t('groups.export_event_list') }}
          </BDropdownItem>
        </BDropdown>
      </div>

      <section class="mb-4">
        <h2>{{ t('networks.general.impact') }}</h2>
        <NetworkStats :stats="network.stats" :groups-count="groupsCount" />
      </section>

      <section v-if="network.description" class="mb-4">
        <h2>{{ t('networks.general.about') }}</h2>
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div v-html="truncatedDescription" />
        <button v-if="showReadMore" type="button" class="btn btn-link p-0" data-testid="network-show-read-more" @click="showDescriptionModal = true">
          {{ t('partials.read_more') }}
        </button>
      </section>

      <section v-if="(network.coordinators || []).length" class="mb-4" data-testid="network-show-coordinators">
        <h2>{{ t('networks.general.coordinators') }}</h2>
        <div class="d-flex flex-wrap gap-3">
          <NuxtLink
            v-for="coordinator in network.coordinators"
            :key="coordinator.id"
            :to="`/profile/${coordinator.id}`"
            class="d-flex align-items-center gap-2 p-2 pe-3 text-decoration-none coordinator-card"
            :data-testid="`network-coordinator-${coordinator.id}`"
          >
            <img
              :src="coordinator.avatar_url || '/images/placeholder-avatar.webp'"
              alt=""
              width="40"
              height="40"
              class="rounded-circle"
            >
            <span>{{ coordinator.name }}</span>
          </NuxtLink>
        </div>
      </section>

      <!-- Groups/events awaiting moderation, for Administrators and this
           network's coordinators - the network-specific rich tables (gap
           #2/#11), not the shared components/moderation/ModerationQueue.vue
           bare-link list used elsewhere. Always-visible heading + "None"
           fallback, matching legacy NetworkPage.vue exactly. -->
      <template v-if="canManage">
        <section class="mb-4" data-testid="network-groups-moderation">
          <h2>{{ t('networks.moderation.groups_title') }}</h2>
          <NetworkGroupsModerationTable :network-id="id" />
        </section>
        <section class="mb-4" data-testid="network-events-moderation">
          <h2>{{ t('networks.moderation.events_title') }}</h2>
          <NetworkEventsModerationTable :network-id="id" />
        </section>
      </template>

      <!-- NetworkPage.vue:80-86 - a count sentence and a link out to the
           groups list filtered to this network. This used to render the full
           inline groups browser (search, country/network filters, sortable
           table), justified in a comment as a "functionally-superior
           divergence flagged for explicit sign-off". It was neither signed off
           nor parity, and the stated blocker - that develop's
           /group/network/{id} has no Nuxt equivalent - is handled by pointing
           at /group/all with a network query param, which GroupsTableFilters
           now seeds from. -->
      <section class="groups-section mb-4">
        <h2>{{ t('networks.general.groups') }}</h2>
        <div class="groups-info border p-3" data-testid="network-show-groups-info">
          {{ t('networks.show.groups_count', { count: groupRows.length, name: network.name }, groupRows.length) }}
          <NuxtLink :to="`/group/all?network=${network.id}`" data-testid="network-show-groups-link">
            {{ t('networks.show.view_groups_link') }}
          </NuxtLink>
        </div>
      </section>

      <section v-if="canManage" class="mb-4" data-testid="tags-management">
        <h2>{{ t('networks.tags.title') }}</h2>
        <NetworkTagsManager :network-id="id" />
      </section>

      <!-- Network logo management: legacy has no such section on the show
           page at all (logo upload is a separate, primitive /networks/
           {id}/edit page - a single <input type=file> + full-page-reload
           form, resources/views/networks/edit.blade.php). Kept inline here
           instead (parity-v2/networks.md gap #8), using the same TUS
           upload infrastructure every other image upload on the Nuxt app
           already uses, rather than porting a strictly worse primitive
           file-input page - but appended at the very end, after Tags, so
           it doesn't disturb the legacy section order above (gap #6). -->
      <section v-if="canManage" class="mb-4" data-testid="network-logo-manage">
        <h2>{{ t('client.networks.logo_heading') }}</h2>
        <TusImageUpload
          :current-image-url="network.logo || ''"
          data-testid="network-logo-upload"
          @uploaded="onLogoUploaded"
          @upload-error="logoError = $event"
        />
        <BAlert v-if="logoError" :model-value="true" variant="danger" class="mt-2" data-testid="network-logo-error">
          {{ logoError }}
        </BAlert>
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
// pill radius, hover border-color/box-shadow - rather than bare Bootstrap
// utility classes with no hover affordance.
.coordinator-card {
  border: 2px solid #000;
  border-radius: 50px;
  background: #fff;
  color: inherit;
  transition: box-shadow 0.2s, border-color 0.2s;

  &:hover {
    border-color: var(--bs-primary, #0394a6);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    text-decoration: none;
  }
}
</style>
