<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useEventsStore } from '~/stores/events.js'
import { useAuth } from '~/composables/useAuth.js'
import { useEventPermissions } from '~/composables/useEventPermissions.js'
import EventForm from '~/components/events/EventForm.vue'
import TusImageUpload from '~/components/forms/TusImageUpload.vue'

// /party/edit/[id] - resources/views/events/edit.blade.php +
// EventAddEdit.vue (design.md §6.2 section 4 C4 task brief). Adds, over the
// create page: the "photos" tab (TusImageUpload, C1f - write-only, since no
// endpoint lists an event's existing photos, api-gaps.md Phase C item 3)
// and the moderation approve select (EventForm.vue, gated on
// data-testid=event-approve).
//
// The legacy page's admin-only "Event log" audit tab
// (App\Helpers\Fixometer::hasRole($user,'Administrator') && $audits,
// resources/views/partials/log-accordion.blade.php) is deliberately
// omitted, same rationale as group/edit/[id].vue's B6 scope cut: there is
// no API endpoint at all for an event's audit trail.
definePageMeta({ auth: true })

const { t } = useI18n()
const route = useRoute()
const eventsStore = useEventsStore()

const id = computed(() => Number(route.params.id))
const event = computed(() => eventsStore.current.data)

// No permissions object on the v2 Event resource (unlike Group's) - approximate
// canedit via useEventPermissions: Administrator (and Root) always qualify;
// a Host qualifies for their own group's events. Host status comes from the
// UNCAPPED GET /api/v2/users/me/groups list (not the dashboard's your_groups,
// which is capped at 5 alphabetically - a host of a 6th+ group was wrongly
// denied edit). NetworkCoordinator's per-network match still can't be checked
// client-side, so remains a safe false-negative.
const { hasRole } = useAuth()
const isAdmin = computed(() => hasRole('Administrator'))
const { canManageEventForGroup, ensureLoaded: ensureEventPerms } = useEventPermissions()
const canEdit = computed(() => canManageEventForGroup(event.value?.group?.id))

const updatedMessage = ref('')
const imageMessage = ref('')
const imageError = ref('')

useHead({ title: computed(() => (event.value ? `${t('events.editing', { event: event.value.title })}` : t('events.editing', { event: '' }))) })

function load() {
  eventsStore.fetchEvent(id.value)
  ensureEventPerms().catch(() => {})
}

onMounted(load)

function retry() {
  load()
}

function onUpdated() {
  updatedMessage.value = t('events.save_event')
  load()
}

async function onImageUploaded({ uploadKey }) {
  imageError.value = ''
  imageMessage.value = ''

  try {
    await eventsStore.uploadEventImage(id.value, uploadKey)
    imageMessage.value = t('events.field_event_images')
  } catch {
    imageError.value = t('client.events.image_upload_error')
  }
}

function onImageUploadError(message) {
  imageError.value = message
}
</script>

<template>
  <div class="container py-4" data-testid="event-edit-page">
    <div v-if="eventsStore.current.loading" data-testid="event-edit-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 6rem" />
      </div>
    </div>

    <BAlert v-else-if="eventsStore.current.error" :model-value="true" variant="danger" data-testid="event-edit-error">
      <p>{{ t('client.events.view_load_error') }}</p>
      <BButton variant="danger" data-testid="event-edit-retry" @click="retry">
        {{ t('client.dashboard.retry') }}
      </BButton>
    </BAlert>

    <BAlert v-else-if="event && !canEdit" :model-value="true" variant="danger" data-testid="event-edit-forbidden">
      {{ t('client.events.edit_forbidden') }}
    </BAlert>

    <template v-else-if="event">
      <h1>{{ t('events.editing', { event: event.title }) }}</h1>
      <p>
        <NuxtLink :to="`/party/view/${id}`" class="headlink" data-testid="event-edit-view-link">
          {{ t('client.events.view_event') }}
        </NuxtLink>
      </p>

      <BAlert v-if="updatedMessage" :model-value="true" variant="success" dismissible data-testid="event-edit-success" @dismissed="updatedMessage = ''">
        {{ updatedMessage }}
      </BAlert>

      <EventForm :event-id="id" :initial-event="event" :is-admin="isAdmin" @updated="onUpdated" />

      <div class="mt-4 pt-3 border-top">
        <h2>{{ t('events.event_photos') }}</h2>
        <TusImageUpload @uploaded="onImageUploaded" @upload-error="onImageUploadError" />
        <div v-if="imageMessage" class="text-success" data-testid="event-edit-image-success">{{ imageMessage }}</div>
        <div v-if="imageError" class="text-danger" data-testid="event-edit-image-error">{{ imageError }}</div>
      </div>
    </template>
  </div>
</template>
