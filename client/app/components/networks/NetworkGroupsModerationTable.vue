<script setup>
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useModerationStore } from '~/stores/moderation.js'
import { useUploadedImageUrl } from '~/composables/useUploadedImageUrl.js'

// Groups-awaiting-moderation table for /networks/{id}'s "Groups to moderate"
// section (parity-v2/networks.md gap #2 + #11). Legacy NetworkPage.vue
// renders <GroupsRequiringModeration :networks="[id]" /> - the FULL
// GroupsTable(approve) (logo / name+archived-badge+tags / location / hosts /
// restarters / next-event, plus a trailing "Group requires moderation" link
// column) - not the plain name-only <ul> the shared components/moderation/
// ModerationQueue.vue renders on /party and /group/map. Built as a
// standalone table here rather than reusing components/groups/GroupsTable.
// vue (which has no "approve" trailing-column mode and is out of scope for
// this pass - see parity-v2/networks.md gap #2's write-up) or extending
// ModerationQueue.vue (shared with /party and /group/map, also out of
// scope). GET /api/v2/moderate/groups already returns the full Group
// resource (image/location/hosts/restarters/next_event/tags/archived_at/
// networks), so every legacy column is available here.
const props = defineProps({
  networkId: {
    type: Number,
    required: true,
  },
})

const { t, locale } = useI18n()
const moderationStore = useModerationStore()
const { uploadedImageUrl } = useUploadedImageUrl()

const DEFAULT_PROFILE = '/images/placeholder-avatar.webp'

// Client-side network scoping, same approach as ModerationQueue.vue - the
// /moderate/groups endpoint returns every group the caller's role can see,
// there's no server-side network filter (matching legacy).
const groups = computed(() =>
  moderationStore.groups.data.filter(
    (g) => Array.isArray(g.networks) && g.networks.some((n) => n.id === props.networkId)
  )
)

function imageSrc(row) {
  return uploadedImageUrl(row.image) || DEFAULT_PROFILE
}

function dateLabel(iso) {
  return new Date(iso).toLocaleDateString(locale.value, { day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(() => {
  moderationStore.fetchGroups().catch(() => {})
})
</script>

<template>
  <div data-testid="network-groups-moderation-table">
    <div v-if="!groups.length" class="text-muted" data-testid="network-groups-moderation-empty">
      {{ t('networks.show.none') }}
    </div>
    <table v-else class="table network-moderation-table">
      <thead>
        <tr>
          <th class="network-moderation-photo-col"><span class="visually-hidden">{{ t('groups.group_image') }}</span></th>
          <th>{{ t('groups.groups_name') }}</th>
          <th>{{ t('client.groups.column_location') }}</th>
          <th>{{ t('client.groups.column_hosts') }}</th>
          <th>{{ t('client.groups.column_restarters') }}</th>
          <th>{{ t('client.groups.column_next_event') }}</th>
          <th />
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in groups" :key="row.id" :data-testid="`network-groups-moderation-row-${row.id}`">
          <td class="network-moderation-photo-col">
            <img :src="imageSrc(row)" alt="" class="network-moderation-photo">
          </td>
          <td>
            <NuxtLink :to="`/group/view/${row.id}`" :data-testid="`network-groups-moderation-link-${row.id}`">
              {{ row.name }}
            </NuxtLink>
            <div>
              <BBadge v-if="row.archived_at" variant="secondary" pill>{{ t('groups.archived_group') }}</BBadge>
            </div>
            <div v-if="row.tags && row.tags.length" class="mt-1">
              <BBadge v-for="tag in row.tags" :key="tag.id" variant="secondary" class="me-1 tag-badge">{{ tag.name }}</BBadge>
            </div>
          </td>
          <td>
            <template v-if="row.location">
              {{ row.location.location }}
              <span v-if="row.location.country" class="text-muted small">{{ row.location.country }}</span>
            </template>
          </td>
          <td>{{ row.hosts ?? '' }}</td>
          <td>{{ row.restarters ?? '' }}</td>
          <td>
            <template v-if="row.next_event">{{ dateLabel(row.next_event.start) }}</template>
            <template v-else>{{ t('groups.upcoming_none_planned') }}</template>
          </td>
          <td>
            <NuxtLink
              :to="`/group/edit/${row.id}`"
              class="network-moderation-flag"
              :data-testid="`network-groups-moderation-flag-${row.id}`"
            >
              {{ t('networks.moderation.group_requires_moderation') }}
            </NuxtLink>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped lang="scss">
.network-moderation-photo-col {
  width: 90px;
}

.network-moderation-photo {
  width: 50px;
  height: 50px;
  object-fit: cover;
  border: 1px solid #000;
}

.tag-badge {
  font-size: 0.75rem;
  font-weight: normal;
  padding: 0.2em 0.5em;
}

.network-moderation-flag {
  white-space: nowrap;
}
</style>
