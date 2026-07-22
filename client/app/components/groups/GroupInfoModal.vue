<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useUploadedImageUrl } from '~/composables/useUploadedImageUrl.js'

// The group-info popup a map marker opens (port of develop's GroupInfoModal.vue,
// PR 887 / RES-1995): the group's name and location linked to its page, its
// next event, and a "Go to group" action.
//
// develop reaches this from a Vue-rendered marker; 898's markers are imperative
// Leaflet layers (for clustering), so GroupMap emits `select` on click and the
// page renders this with the selected group's summary - the "extra plumbing"
// the map's own comment referred to. Controlled by the `group` prop: non-null
// shows it, null hides it.
const props = defineProps({
  // The selected group's summary: { id, name, location, image, nextEvent }.
  // null when nothing is selected.
  group: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close'])

const { t, locale } = useI18n()
const { uploadedImageUrl } = useUploadedImageUrl()

const viewPath = computed(() => (props.group ? `/group/view/${props.group.id}` : ''))

const imageUrl = computed(() =>
  props.group?.image ? uploadedImageUrl(`mid_${props.group.image}`) : '/images/placeholder-avatar.png'
)

// Same format as GroupsTable's dateLabel, so the map and the list agree.
const nextEventDate = computed(() =>
  props.group?.nextEvent
    ? new Date(props.group.nextEvent.start).toLocaleDateString(locale.value, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
      })
    : null
)

function close() {
  emit('close')
}
</script>

<template>
  <BModal
    :model-value="!!group"
    hide-footer
    :title="null"
    data-testid="group-info-modal"
    @hide="close"
  >
    <template v-if="group" #title>
      <!-- Logo and name navigate to the group, same as Go to group. -->
      <NuxtLink :to="viewPath" class="d-flex align-items-center text-reset text-decoration-none" data-testid="group-info-title-link">
        <img :src="imageUrl" alt="" width="67" height="67" class="me-3 rounded" style="object-fit: cover">
        <span>
          <span class="d-block fw-bold">{{ group.name }}</span>
          <span v-if="group.location" class="small text-muted">{{ group.location }}</span>
        </span>
      </NuxtLink>
    </template>

    <div v-if="group" class="d-flex flex-wrap" data-testid="group-info-next-event">
      {{ t('groups.next_event') }}:&nbsp;
      <span v-if="nextEventDate">{{ nextEventDate }} {{ group.nextEvent.title }}</span>
      <span v-else>{{ t('groups.upcoming_none_planned') }}</span>
    </div>

    <template #footer>
      <div class="w-100 d-flex justify-content-between">
        <BButton variant="primary" :to="viewPath" data-testid="group-info-goto">
          {{ t('groups.goto_group') }}
        </BButton>
        <BButton variant="secondary" data-testid="group-info-close" @click="close">
          {{ t('partials.close') }}
        </BButton>
      </div>
    </template>
  </BModal>
</template>
