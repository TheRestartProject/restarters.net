<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuth } from '~/composables/useAuth.js'
import { useSessionStore } from '~/stores/session.js'
import { useNetworksStore } from '~/stores/networks.js'
import { useGroupsStore } from '~/stores/groups.js'
import NetworkStats from '~/components/networks/NetworkStats.vue'
import AssociateGroupsModal from '~/components/networks/AssociateGroupsModal.vue'
import TusImageUpload from '~/components/forms/TusImageUpload.vue'
import AdminCrudTable from '~/components/admin/AdminCrudTable.vue'
import GroupsTable from '~/components/groups/GroupsTable.vue'
import ModerationQueue from '~/components/moderation/ModerationQueue.vue'

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
// Two features from the legacy page are deliberately NOT ported, both
// recorded in docs/nuxt-migration/api-gaps.md Phase E:
//  - Network coordinators list: GET /api/v2/networks/{id} has no
//    `coordinators` field (App\Http\Resources\Network) - the legacy Blade
//    controller built it directly from `$network->coordinators` server-side.
//  - "Export event list" link: the legacy href
//    (/export/networks/{id}/events) is a session-cookie-authenticated web
//    route; the SPA is pure Bearer-token auth (design.md §4.4) and has no
//    session cookie to send, so the link would just redirect to login.
//  - Groups/Events "requiring moderation" panels: no moderation-queue
//    component exists anywhere in the client yet (not built in any earlier
//    phase) - out of scope for this task, left for whichever phase adds a
//    moderation queue page.
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

const selectedTagFilter = ref('')
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

const candidateGroups = computed(() => {
  const inNetworkIds = new Set(networksStore.groups.data.map((g) => g.id))
  return groupsStore.names.filter((g) => !g.archived_at && !inNetworkIds.has(g.id)).map((g) => ({ id: g.id, name: g.name }))
})

function loadGroups() {
  const params = selectedTagFilter.value ? { group_tag: selectedTagFilter.value } : {}
  networksStore.fetchGroups(id.value, params)
}

watch(selectedTagFilter, loadGroups)

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

// AdminCrudTable's callback-prop contract (design.md's D4-established
// convention) - wraps stores/networks.js's tag actions with this page's
// network id.
function fetchTags() {
  return networksStore.fetchTags(id.value)
}
function createTag(payload) {
  return networksStore.createTag(id.value, payload)
}
function updateTag(tagId, payload) {
  return networksStore.updateTag(id.value, tagId, payload)
}
function deleteTag(tagId) {
  return networksStore.deleteTag(id.value, tagId)
}

const tagTableFields = computed(() => [
  { key: 'name', label: t('networks.tags.name_label'), sortable: true },
  { key: 'groups_count', label: t('networks.general.groups'), sortable: true },
])

const tagFormFields = computed(() => [
  { key: 'name', label: t('networks.tags.name_label'), type: 'text', required: true, maxLength: 255 },
  { key: 'description', label: t('networks.tags.description_label'), type: 'textarea', required: false, rows: 3, maxLength: 1000, nullIfEmpty: true },
])

const tagLabels = computed(() => ({
  title: t('networks.tags.title'),
  createButton: t('networks.tags.create'),
  editTitle: t('networks.tags.edit_title'),
  saveButton: t('admin.save-tag'),
  deleteButton: t('networks.tags.delete'),
  cancel: t('partials.cancel'),
  emptyText: t('networks.tags.no_tags'),
  confirmDeleteTitle: t('networks.tags.delete_confirm_title'),
  loadError: t('client.networks.tags_load_error'),
  retry: t('client.dashboard.retry'),
  createSuccess: t('group-tags.create_success'),
  updateSuccess: t('group-tags.update_success'),
  deleteSuccess: t('group-tags.delete_success'),
  createError: t('networks.tags.create_error'),
  updateError: t('networks.tags.edit_error'),
  deleteError: t('group-tags.delete_error'),
  formatConfirmDelete: (item) => t('networks.tags.delete_confirm_message', { name: item.name }),
  deleteWarning: (item) => (item.groups_count > 0 ? t('networks.tags.delete_warning', { count: item.groups_count }, item.groups_count) : ''),
}))
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
        <img v-if="network.logo" :src="network.logo" :alt="t('client.networks.logo_alt', { name: network.name })" class="me-4" style="max-height: 60px">
        <div class="flex-grow-1">
          <h1 data-testid="network-show-name">{{ network.name }}</h1>
          <a v-if="network.website" :href="network.website" target="_blank" rel="noopener noreferrer" class="text-muted" data-testid="network-show-website">
            {{ network.website }}
          </a>
        </div>
        <BButton v-if="canManage" variant="primary" data-testid="network-show-add-groups" @click="showAssociateModal = true">
          {{ t('networks.show.add_groups_menuitem') }}
        </BButton>
      </div>

      <!-- Groups/events awaiting moderation, for Administrators and this
           network's coordinators (legacy network page showed both queues). -->
      <template v-if="canManage">
        <ModerationQueue type="groups" />
        <ModerationQueue type="events" />
      </template>

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

      <section class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h2 class="mb-0">{{ t('networks.general.groups') }}</h2>
          <select
            v-if="networksStore.tags.data.length"
            v-model="selectedTagFilter"
            class="form-select w-auto"
            data-testid="network-show-tag-filter"
          >
            <option value="">{{ t('networks.tags.title') }}</option>
            <option v-for="tag in networksStore.tags.data" :key="tag.id" :value="tag.id">{{ tag.name }}</option>
          </select>
        </div>

        <div v-if="networksStore.groups.loading" data-testid="network-show-groups-loading">
          <div class="placeholder-glow">
            <span class="placeholder col-12" style="height: 4rem" />
          </div>
        </div>
        <BAlert v-else-if="networksStore.groups.error" :model-value="true" variant="danger" data-testid="network-show-groups-error">
          {{ t('client.networks.groups_load_error') }}
        </BAlert>
        <div v-else-if="!groupRows.length" class="text-muted" data-testid="network-show-groups-empty">
          {{ t('client.networks.no_groups') }}
        </div>
        <GroupsTable v-else :groups="groupRows" :show-join="false" :show-filters="true" />
      </section>

      <section v-if="canManage" data-testid="tags-management">
        <AdminCrudTable
          display-key="name"
          :table-fields="tagTableFields"
          :form-fields="tagFormFields"
          :labels="tagLabels"
          testid-prefix="tag"
          :items="networksStore.tags.data"
          :fetch-items="fetchTags"
          :create-item="createTag"
          :update-item="updateTag"
          :delete-item="deleteTag"
        />
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
