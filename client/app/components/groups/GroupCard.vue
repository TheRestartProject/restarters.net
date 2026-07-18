<script setup>
import { useI18n } from 'vue-i18n'
import GroupJoinButton from './GroupJoinButton.vue'

// Single-group summary card for /group/nearby (DashboardNearbyGroups.vue's
// list item is the styling precedent - card form factor rather than
// GroupsTable's table rows, since the nearby list only ever carries
// {id, name, distance, location, image_url}, not the table's fuller column
// set).
defineProps({
  group: {
    type: Object,
    required: true,
  },
  isMember: {
    type: Boolean,
    default: false,
  },
})

const { t } = useI18n()
</script>

<template>
  <div
    class="group-card d-flex justify-content-between align-items-center py-2 border-bottom"
    :data-testid="`group-card-${group.id}`"
  >
    <div class="d-flex align-items-center">
      <img
        :src="group.image_url || '/images/placeholder-avatar.webp'"
        alt=""
        width="56"
        height="56"
        class="rounded-circle me-3"
      >
      <div>
        <NuxtLink :to="`/group/view/${group.id}`" :data-testid="`group-card-link-${group.id}`">
          {{ group.name }}
        </NuxtLink>
        <div class="small text-muted">
          <span v-if="group.location" :data-testid="`group-card-location-${group.id}`">
            {{ group.location }}
          </span>
          <span v-if="group.distance != null" :data-testid="`group-card-distance-${group.id}`">
            {{ t('client.groups.distance_km', { distance: group.distance }) }}
          </span>
        </div>
      </div>
    </div>
    <GroupJoinButton :group-id="group.id" :group-name="group.name" :is-member="isMember" />
  </div>
</template>
