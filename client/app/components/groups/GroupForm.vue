<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { LMap, LMarker, LTileLayer } from '@vue-leaflet/vue-leaflet'
import 'leaflet/dist/leaflet.css'
import markerIconUrl from 'leaflet/dist/images/marker-icon.png'
import markerIcon2xUrl from 'leaflet/dist/images/marker-icon-2x.png'
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png'
// Side-effecting: sets window.L before <LMap use-global-leaflet> reads it -
// see the file's own comment and GroupMap.vue, which does the same.
import L from '../../utils/leafletGlobal.js'
import { LEAFLET_ATTRIBUTION } from '../../utils/mapConstants.js'
import { useLeafletTiles } from '~/composables/useLeafletTiles.js'

const leafletTiles = useLeafletTiles()
import { useGroupsStore } from '../../stores/groups.js'
import { useUploadedImageUrl } from '../../composables/useUploadedImageUrl.js'
import RichTextEditor from '../forms/RichTextEditor.vue'
import LocationPicker from '../forms/LocationPicker.vue'
import TusImageUpload from '../forms/TusImageUpload.vue'
import GroupMultiSelect from './GroupMultiSelect.vue'
import GroupNetworkData from './GroupNetworkData.vue'

// Shared by /group/create and /group/edit/[id.vue] (design.md §6.2 B6 task
// brief). Functional spec: resources/views/group/create.blade.php +
// edit.blade.php, resources/js/components/GroupAddEdit.vue +
// GroupAddEditPage.vue.
//
// Field names in the submitted payload match
// GroupController::validateGroupParams exactly (name/location/description/
// website/phone/email/timezone/postcode/area, +archived_at/networks/tags/
// moderate on edit) - createGroupv2/updateGroupv2 read $request->input(...)
// directly, so extra/renamed keys are silently ignored, not rejected.
const props = defineProps({
  groupId: {
    type: Number,
    default: null,
  },
  initialGroup: {
    type: Object,
    default: null,
  },
  permissions: {
    type: Object,
    default: () => ({}),
  },
  isAdmin: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['created', 'updated'])

const { t } = useI18n()
const groupsStore = useGroupsStore()
const { uploadedImageUrl } = useUploadedImageUrl()

const creating = computed(() => !props.groupId)
const canModerate = computed(() => !creating.value && !!props.permissions.can_demote)
const canNetwork = computed(() => !creating.value && props.isAdmin)

const form = reactive({
  name: props.initialGroup?.name || '',
  website: props.initialGroup?.website || '',
  email: props.initialGroup?.email || '',
  phone: props.initialGroup?.phone || '',
  description: props.initialGroup?.description || '',
  location: props.initialGroup?.location?.location || '',
  postcode: props.initialGroup?.location?.postcode || '',
  lat: props.initialGroup?.location?.lat ?? null,
  lng: props.initialGroup?.location?.lng ?? null,
  timezone: props.initialGroup?.timezone || '',
  area: props.initialGroup?.location?.area || '',
  moderate: '',
  networkIds: (props.initialGroup?.networks || []).map((n) => n.id),
  tagIds: (props.initialGroup?.tags || []).map((tg) => tg.id),
})

// Small static preview of the geocoded location, once LocationPicker has
// set lat/lng - ports develop's GroupLocationMap.vue (200px single-marker
// map at zoom 11; shown once `lat || lng`). Reuses GroupMap.vue's
// global-Leaflet setup so this map and the full group-finder map share one
// Leaflet instance.
const hasLocationPreview = computed(() => form.lat !== null && form.lat !== undefined && form.lng !== null && form.lng !== undefined)
const previewCenter = computed(() => [form.lat, form.lng])
const previewIcon = L.icon({
  iconUrl: markerIconUrl,
  iconRetinaUrl: markerIcon2xUrl,
  shadowUrl: markerShadowUrl,
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41],
})

// Admin-only network-specific custom fields (findings/parity-v2/
// group-forms.md #5 - ports NetworkData.vue). Round-tripped into the
// `network_data` payload key unchanged unless edited via GroupNetworkData
// below - updateGroupv2 unconditionally overwrites network_data with
// whatever's submitted (unlike networks/tags, which are only synced when
// present).
const networkData = ref({ ...(props.initialGroup?.network_data || {}) })
const approved = ref(!!props.initialGroup?.approved)

const timezones = ref([])
const timezoneValid = computed(() => !form.timezone || !timezones.value.length || timezones.value.includes(form.timezone))

const networkOptions = ref([])
// Grouped under network-name headings, "Global" first - matches
// GroupAddEdit.vue's groupedTagOptions (findings/parity-v2/
// group-forms.md #6).
const tagOptions = computed(() =>
  groupsStore.tags.data.map((tag) => ({
    value: tag.id,
    text: tag.name,
    group: tag.network_name || 'Global',
  })),
)

// Client-side duplicate-group-name pre-check (findings/parity-v2/
// group-forms.md #7) - ports GroupAddEdit.vue's duplicateName/duplicateError
// computed. `names` is the same lightweight {id, name, archived_at} index
// used by /group/all, fetched here (not awaited - the check can happen once
// it lands, same as legacy) purely for this comparison.
const duplicateName = computed(() => {
  const name = form.name.trim().toLowerCase()
  if (!name || !groupsStore.names.length) return false

  return groupsStore.names.some((g) => (creating.value || g.id !== props.groupId) && g.name.toLowerCase() === name)
})

const submitting = ref(false)
const generalError = ref('')
const fieldErrors = ref({})

function fieldError(field) {
  return fieldErrors.value[field]?.[0] || ''
}

// Group photo (findings/parity-v2/group-forms.md #8 - positioned after
// Description, before Location, in both create and edit flows; previously
// create-only, rendered above the whole form in edit mode). Create mode
// doesn't have a group id yet, so the tus upload key is held until submit()
// has one; edit mode uploads immediately, same as legacy GroupImage.vue's
// dropzone-on-select behaviour.
const pendingImageUploadKey = ref(null)
const imageError = ref('')
const groupImageUrl = computed(() => uploadedImageUrl(props.initialGroup?.image))

async function onImageUploaded({ uploadKey }) {
  imageError.value = ''

  if (creating.value) {
    pendingImageUploadKey.value = uploadKey
    return
  }

  try {
    await groupsStore.uploadGroupImage(props.groupId, uploadKey)
  } catch {
    imageError.value = t('client.groups.image_upload_error')
  }
}

function onImageUploadError(message) {
  imageError.value = message
}

onMounted(async () => {
  const { $api } = useNuxtApp()

  groupsStore.fetchNames().catch(() => {
    // Non-critical: the duplicate-name check just doesn't fire until/unless
    // it succeeds.
  })

  if ($api?.config) {
    try {
      const zones = await $api.config.timezones()
      timezones.value = (zones || []).map((z) => z.name)
    } catch {
      // Non-critical: the field still works as free text without the
      // client-side validity check.
    }
  }

  if (canNetwork.value && $api?.network) {
    try {
      const { data } = await $api.network.list()
      networkOptions.value = (data || []).map((n) => ({ value: n.id, text: n.name }))
    } catch {
      networkOptions.value = []
    }
  }

  if (canModerate.value) {
    groupsStore.fetchTags().catch(() => {})
  }
})

function validate() {
  const errors = {}

  if (!form.name.trim()) {
    errors.name = [t('client.groups.name_required')]
  }
  if (!form.description || !form.description.trim()) {
    errors.description = [t('client.groups.description_required')]
  }
  if (!form.location.trim()) {
    errors.location = [t('client.groups.location_required')]
  }
  if (form.website && !/^https?:\/\/.+/i.test(form.website)) {
    errors.website = [t('groups.groups_website_invalid')]
  }
  if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = [t('client.groups.email_invalid')]
  }
  if (!timezoneValid.value) {
    errors.timezone = [t('partials.validate_timezone')]
  }

  return errors
}

async function submit() {
  generalError.value = ''
  fieldErrors.value = {}

  // Matches GroupAddEdit.vue's submit(): a duplicate name blocks submission
  // entirely (the inline warning under the Name field is the only
  // feedback), rather than surfacing through the normal field-error path.
  if (duplicateName.value) {
    return
  }

  const errors = validate()
  if (Object.keys(errors).length) {
    fieldErrors.value = errors
    return
  }

  submitting.value = true

  const payload = {
    name: form.name,
    website: form.website || null,
    email: form.email || null,
    phone: form.phone || null,
    description: form.description,
    location: form.location,
    timezone: form.timezone || null,
  }

  // Always sent, including when blank. PATCH only writes fields the request
  // actually carries, so omitting a blank postcode would leave the stored one
  // untouched and make it unclearable. groups.postcode is NOT NULL and
  // ConvertEmptyStringsToNull rewrites '' to null in transit, so the server
  // coerces a sent null back to '' (validateGroupParams).
  payload.postcode = form.postcode || null

  if (!creating.value) {
    payload.network_data = JSON.stringify(networkData.value)

    if (canModerate.value) {
      payload.area = form.area || null
      if (!approved.value && form.moderate === 'approve') {
        payload.moderate = 'approve'
      }
      payload.tags = JSON.stringify(form.tagIds)
    }

    if (canNetwork.value) {
      payload.networks = JSON.stringify(form.networkIds)
    }
  }

  try {
    if (creating.value) {
      const id = await groupsStore.createGroup(payload)

      if (pendingImageUploadKey.value) {
        try {
          await groupsStore.uploadGroupImage(id, pendingImageUploadKey.value)
        } catch {
          // The group itself was created fine - the store already toasted
          // the upload failure, so don't block navigation over a photo that
          // can still be added from the edit page afterwards.
        }
      }

      emit('created', id)
    } else {
      const id = await groupsStore.updateGroup(props.groupId, payload)
      approved.value = approved.value || form.moderate === 'approve'
      emit('updated', id)
    }
  } catch (err) {
    if (err?.status === 422) {
      fieldErrors.value = err.data?.errors || {}
    }
    generalError.value = creating.value ? t('groups.create_failed') : t('groups.edit_failed')
  } finally {
    submitting.value = false
  }
}

defineExpose({ submit })
</script>

<template>
  <BForm data-testid="group-form" @submit.prevent="submit">
    <div class="group-form-layout">
      <BFormGroup class="group-form-name" :label="`${t('groups.groups_name_of')}:`" label-for="group-form-name">
        <input
          id="group-form-name"
          v-model="form.name"
          type="text"
          class="form-control"
          :class="{ 'is-invalid': fieldError('name') || duplicateName }"
          data-testid="group-form-name"
        >
        <div v-if="fieldError('name')" class="invalid-feedback d-block" data-testid="group-form-name-error">
          {{ fieldError('name') }}
        </div>
        <small v-else class="form-text text-muted">{{ t('groups.groups_group_small') }}</small>
        <p v-if="duplicateName" class="text-danger fw-bold mb-0 mt-1" data-testid="group-form-duplicate-name">
          {{ t('groups.duplicate', { name: form.name }) }}
        </p>
      </BFormGroup>

      <BFormGroup class="group-form-website" :label="`${t('groups.groups_website')}:`" label-for="group-form-website">
        <input
          id="group-form-website"
          v-model="form.website"
          type="url"
          class="form-control"
          :class="{ 'is-invalid': fieldError('website') }"
          data-testid="group-form-website"
        >
        <div v-if="fieldError('website')" class="invalid-feedback d-block" data-testid="group-form-website-error">
          {{ fieldError('website') }}
        </div>
        <small v-else class="form-text text-muted">{{ t('groups.groups_website_small') }}</small>
      </BFormGroup>

      <BFormGroup class="group-form-email" :label="`${t('groups.groups_email')}:`" label-for="group-form-email">
        <input
          id="group-form-email"
          v-model="form.email"
          type="email"
          class="form-control"
          :class="{ 'is-invalid': fieldError('email') }"
          data-testid="group-form-email"
        >
        <div v-if="fieldError('email')" class="invalid-feedback d-block" data-testid="group-form-email-error">
          {{ fieldError('email') }}
        </div>
        <small v-else class="form-text text-muted">{{ t('groups.groups_email_small') }}</small>
      </BFormGroup>

      <BFormGroup class="group-form-phone" :label="`${t('groups.field_phone')}:`" label-for="group-form-phone">
        <input
          id="group-form-phone"
          v-model="form.phone"
          type="text"
          class="form-control"
          data-testid="group-form-phone"
        >
        <small class="form-text text-muted">{{ t('groups.phone_small') }}</small>
      </BFormGroup>

      <BFormGroup class="group-form-description" :label="`${t('groups.groups_about_group')}:`" label-for="group-form-description">
        <RichTextEditor
          v-model="form.description"
          testid="group-form-description"
          :has-error="!!fieldError('description')"
        />
        <div v-if="fieldError('description')" class="invalid-feedback d-block" data-testid="group-form-description-error">
          {{ fieldError('description') }}
        </div>
      </BFormGroup>

      <BFormGroup class="group-form-image" :label="`${t('groups.group_image')}:`">
        <TusImageUpload compact :current-image-url="groupImageUrl" @uploaded="onImageUploaded" @upload-error="onImageUploadError" />
        <div v-if="imageError" class="text-danger" data-testid="group-form-image-error">{{ imageError }}</div>
      </BFormGroup>

      <LocationPicker
        v-model:location="form.location"
        v-model:postcode="form.postcode"
        v-model:lat="form.lat"
        v-model:lng="form.lng"
        class="group-form-location"
        :has-error="!!fieldError('location')"
        :can-edit-postcode="canModerate"
      />

      <div v-if="hasLocationPreview" class="group-form-locationmap mb-3" data-testid="group-form-map-preview">
        <LMap
          :zoom="11"
          :center="previewCenter"
          use-global-leaflet
          style="width: 100%; height: 200px"
        >
          <LTileLayer :url="leafletTiles" :attribution="LEAFLET_ATTRIBUTION" />
          <LMarker :lat-lng="previewCenter" :icon="previewIcon" />
        </LMap>
      </div>

      <BFormGroup class="group-form-timezone" :label="`${t('groups.timezone')}:`" label-for="group-form-timezone">
        <input
          id="group-form-timezone"
          v-model="form.timezone"
          type="text"
          list="group-form-timezones"
          class="form-control"
          :class="{ 'is-invalid': !timezoneValid }"
          data-testid="group-form-timezone"
        >
        <datalist id="group-form-timezones">
          <option v-for="zone in timezones" :key="zone" :value="zone" />
        </datalist>
        <small class="form-text text-muted">{{ t('groups.timezone_placeholder') }}</small>
      </BFormGroup>

      <BCard v-if="canModerate || canNetwork" no-body class="group-admin group-form-admin mt-3">
        <BCardHeader class="d-flex align-items-center gap-2">
          <svg width="18" height="18" viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" aria-hidden="true">
            <g fill="#0394a6">
              <path d="M7.5 1.58a5.941 5.941 0 0 1 5.939 5.938A5.942 5.942 0 0 1 7.5 13.457a5.942 5.942 0 0 1-5.939-5.939A5.941 5.941 0 0 1 7.5 1.58zm0 3.04a2.899 2.899 0 1 1-2.898 2.899A2.9 2.9 0 0 1 7.5 4.62z" />
              <ellipse cx="6.472" cy=".217" rx=".274" ry=".217" />
              <ellipse cx="8.528" cy=".217" rx=".274" ry=".217" />
              <path d="M6.472 0h2.056v1.394H6.472z" />
              <path d="M8.802.217H6.198l-.274 1.562h3.152L8.802.217z" />
              <ellipse cx="8.528" cy="14.783" rx=".274" ry=".217" />
              <ellipse cx="6.472" cy="14.783" rx=".274" ry=".217" />
              <path d="M6.472 13.606h2.056V15H6.472z" />
              <path d="M6.198 14.783h2.604l.274-1.562H5.924l.274 1.562zM1.47 2.923c.107-.106.262-.125.347-.04.084.085.066.24-.041.347-.107.107-.262.125-.346.04-.085-.084-.067-.24.04-.347zM2.923 1.47c.107-.107.263-.125.347-.04.085.084.067.239-.04.346-.107.107-.262.125-.347.041-.085-.085-.066-.24.04-.347z" />
              <path d="M2.923 1.47L1.47 2.923l.986.986 1.453-1.453-.986-.986z" />
              <path d="M3.27 1.43L1.43 3.27l.91 1.299L4.569 2.34 3.27 1.43zm10.26 10.647c-.107.106-.262.125-.347.04-.084-.085-.066-.24.041-.347.107-.107.262-.125.346-.04.085.084.067.24-.04.347zm-1.453 1.453c-.107.107-.263.125-.347.04-.085-.084-.067-.239.04-.346.107-.107.262-.125.347-.041.085.085.066.24-.04.347z" />
              <path d="M12.077 13.53l1.453-1.453-.986-.986-1.453 1.453.986.986z" />
              <path d="M11.73 13.57l1.84-1.84-.91-1.299-2.229 2.229 1.299.91zM0 8.528c0-.151.097-.274.217-.274.119 0 .216.123.216.274 0 .151-.097.274-.216.274-.12 0-.217-.123-.217-.274zm0-2.056c0-.151.097-.274.217-.274.119 0 .216.123.216.274 0 .151-.097.274-.216.274-.12 0-.217-.123-.217-.274z" />
              <path d="M0 6.472v2.056h1.394V6.472H0z" />
              <path d="M.217 6.198v2.604l1.562.274V5.924l-1.562.274zM15 6.472c0 .151-.097.274-.217.274-.119 0-.216-.123-.216-.274 0-.151.097-.274.216-.274.12 0 .217.123.217.274zm0 2.056c0 .151-.097.274-.217.274-.119 0-.216-.123-.216-.274 0-.151.097-.274.216-.274.12 0 .217.123.217.274z" />
              <path d="M15 8.528V6.472h-1.394v2.056H15z" />
              <path d="M14.783 8.802V6.198l-1.562-.274v3.152l1.562-.274zM2.923 13.53c-.106-.107-.125-.262-.04-.347.085-.084.24-.066.347.041.107.107.125.262.04.346-.084.085-.24.067-.347-.04zM1.47 12.077c-.107-.107-.125-.263-.04-.347.084-.085.239-.067.346.04.107.107.125.262.041.347-.085.085-.24.066-.347-.04z" />
              <path d="M1.47 12.077l1.453 1.453.986-.986-1.453-1.453-.986.986z" />
              <path d="M1.43 11.73l1.84 1.84 1.299-.91-2.229-2.229-.91 1.299zM12.077 1.47c.106.107.125.262.04.347-.085.084-.24.066-.347-.041-.107-.107-.125-.262-.04-.346.084-.085.24-.067.347.04zm1.453 1.453c.107.107.125.263.04.347-.084.085-.239.067-.346-.04-.107-.107-.125-.262-.041-.347.085-.085.24-.066.347.04z" />
              <path d="M13.53 2.923L12.077 1.47l-.986.986 1.453 1.453.986-.986z" />
              <path d="M13.57 3.27l-1.84-1.84-1.299.91 2.229 2.229.91-1.299z" />
            </g>
          </svg>
          {{ t('groups.group_admin_only') }}
        </BCardHeader>
        <BCardBody>
          <BFormGroup v-if="canNetwork" :label="`${t('networks.networks')}:`">
            <GroupMultiSelect v-model="form.networkIds" :options="networkOptions" testid="group-form-networks" />
          </BFormGroup>

          <BFormGroup v-if="canModerate" :label="`${t('groups.group_tags')}:`" class="mt-2">
            <GroupMultiSelect v-model="form.tagIds" :options="tagOptions" testid="group-form-tags" />
          </BFormGroup>

          <BFormGroup v-if="canModerate" :label="`${t('groups.area')}:`" label-for="group-form-area" class="mt-2">
            <input id="group-form-area" v-model="form.area" type="text" class="form-control" data-testid="group-form-area">
          </BFormGroup>

          <BFormGroup v-if="canModerate && !approved" :label="`${t('groups.approve_group')}:`" label-for="group-form-moderate" class="mt-2">
            <select id="group-form-moderate" v-model="form.moderate" class="form-select" data-testid="group-form-moderate">
              <option value="" />
              <!-- Hardcoded, untranslated - matches develop's GroupAddEdit.vue
                   <option value="approve">Approve</option> verbatim (no lang
                   key exists for it there either). -->
              <option value="approve">Approve</option>
            </select>
          </BFormGroup>

          <GroupNetworkData v-model="networkData" class="mt-2" />
        </BCardBody>
      </BCard>

      <div class="group-form-buttons" data-testid="group-form-buttons">
        <p v-if="generalError" class="text-danger fw-bold" data-testid="group-form-error">
          <!-- eslint-disable-next-line vue/no-v-html -->
          <span v-html="generalError" />
        </p>

        <div class="d-flex flex-wrap align-items-center gap-3" :class="creating ? 'justify-content-between' : 'justify-content-end'">
          <div v-if="creating" class="flex-grow-1">
            {{ t('groups.groups_approval_text') }}
          </div>
          <BButton type="submit" variant="primary" :disabled="submitting" data-testid="group-form-submit">
            <svg v-if="!submitting" width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1" aria-hidden="true">
              <path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4.414a1 1 0 0 0-.293-.707l-2.414-2.414A1 1 0 0 0 11.586 1H2zm0 1h9.586L14 4.414V14H2V2zm2 0h5v3H4V2zm-1 5h10v7H3V7z" />
            </svg>
            <span v-else class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" />
            {{ creating ? t('groups.create_group') : t('groups.edit_group_save_changes') }}
          </BButton>
        </div>
      </div>
    </div>
  </BForm>
</template>

<style scoped lang="scss">
// Grid layout matching GroupAddEdit.vue's .layout (findings/parity-v2/
// group-forms.md #1): single column on mobile (following DOM order, which
// is already the develop row order 1-11 - name/website/email/phone/
// description/image, then location/map/timezone, then admin/buttons); two
// columns from the lg breakpoint (992px) up, with location+map+timezone
// forming a tall right-hand column beside the contact-detail fields, and
// the admin card + button row spanning both columns underneath.
.group-form-layout {
  display: grid;
  grid-template-columns: 1fr;
}

// Explicit placement for every area, mirroring GroupAddEdit.vue's .layout
// exactly (rather than leaning on CSS Grid's implicit auto-placement to
// land in the right cells) - matches the develop stylesheet's own approach
// of naming every grid-row/grid-column, and keeps this robust against a
// future field being added/reordered.
.group-form-name {
  grid-row: 1 / 2;
  grid-column: 1 / 2;
}

.group-form-website {
  grid-row: 2 / 3;
  grid-column: 1 / 2;
}

.group-form-email {
  grid-row: 3 / 4;
  grid-column: 1 / 2;
}

.group-form-phone {
  grid-row: 4 / 5;
  grid-column: 1 / 2;
}

.group-form-description {
  grid-row: 5 / 6;
  grid-column: 1 / 2;
}

.group-form-image {
  grid-row: 6 / 7;
  grid-column: 1 / 2;
}

.group-form-location {
  grid-row: 7 / 8;
  grid-column: 1 / 2;
}

.group-form-locationmap {
  grid-row: 8 / 9;
  grid-column: 1 / 2;
}

.group-form-timezone {
  grid-row: 9 / 10;
  grid-column: 1 / 2;
}

.group-form-admin {
  grid-row: 10 / 11;
  grid-column: 1 / 2;
}

.group-form-buttons {
  grid-row: 11 / 12;
  grid-column: 1 / 2;
}

@media (min-width: 992px) {
  .group-form-layout {
    grid-template-columns: 1fr 1fr;
    column-gap: 20px;
  }

  .group-form-location {
    grid-row: 1 / 4;
    grid-column: 2 / 3;
  }

  .group-form-locationmap {
    grid-row: 3 / 5;
    grid-column: 2 / 3;
  }

  .group-form-timezone {
    grid-row: 5 / 6;
    grid-column: 2 / 3;
  }

  .group-form-admin {
    grid-row: 7 / 8;
    grid-column: 1 / 3;
  }

  .group-form-buttons {
    grid-row: 8 / 9;
    grid-column: 1 / 3;
  }
}
</style>
