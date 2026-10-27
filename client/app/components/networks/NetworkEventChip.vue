<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import moment from 'moment-timezone'
import { useUploadedImageUrl } from '~/composables/useUploadedImageUrl.js'
import { groupColour } from '~/utils/networkEvents.js'

// One event as a link-chip in the network calendar and its "+N more"
// popover: start time, the group's logo (when it has one) and the group's
// name, in a colour that is the same for every event of that group.
// Cancelled events are struck through; events on a group that is not
// approved or is archived are dashed (outlined rather than filled).
const props = defineProps({
  event: {
    type: Object,
    required: true,
  },
})

const { t } = useI18n()
const { uploadedImageUrl } = useUploadedImageUrl()

const group = computed(() => props.event.group || {})
const unapproved = computed(() => group.value.approved === false)
const archived = computed(() => !!group.value.archived_at)

const time = computed(() => moment.tz(props.event.start, props.event.timezone || 'UTC').format('HH:mm'))
const avatar = computed(() => uploadedImageUrl(group.value.image))

// The chip only has room for the time and group, so say the rest on hover.
const tooltip = computed(() => {
  const notes = []

  if (props.event.cancelled) notes.push(t('networks.events.cancelled'))
  if (unapproved.value) notes.push(t('networks.events.group_unapproved'))
  if (archived.value) notes.push(t('networks.events.group_archived'))

  return notes.length ? `${props.event.title} (${notes.join(', ')})` : props.event.title
})
</script>

<template>
  <NuxtLink
    :to="`/party/view/${event.id}`"
    class="network-event-chip"
    :class="{
      cancelled: event.cancelled,
      online: event.online,
      unapproved,
      archived,
      dashed: unapproved || archived,
    }"
    :style="{ '--chip-colour': groupColour(group.id) }"
    :title="tooltip"
    :data-testid="`network-event-chip-${event.id}`"
  >
    <img v-if="avatar" :src="avatar" alt="" width="18" height="18" class="chip-avatar">
    <span class="chip-time">{{ time }}</span>
    <span class="chip-name">{{ group.name }}</span>
  </NuxtLink>
</template>

<style scoped lang="scss">
.network-event-chip {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.8rem;
  line-height: 1.3;
  padding: 0.2rem 0.4rem;
  margin-bottom: 0.2rem;
  border-radius: 4px;
  border: 1px solid var(--chip-colour);
  background: var(--chip-colour);
  color: #fff;
  white-space: nowrap;
  overflow: hidden;
  // The site underlines links in tables; a chip is a block, so it doesn't need it.
  text-decoration: none !important;

  &:hover {
    text-decoration: none;
    filter: brightness(1.15);
  }

  &.cancelled .chip-name,
  &.cancelled .chip-time {
    text-decoration: line-through;
  }

  // Not approved / archived: dashed outline on white, so it reads as
  // "not a normal event" without relying on colour alone.
  &.dashed {
    background: #fff;
    color: var(--chip-colour);
    border-style: dashed;
  }
}

.chip-avatar {
  flex: none;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  object-fit: cover;
  background: #fff;
}

.chip-time {
  flex: none;
  font-weight: 600;
}

.chip-name {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
