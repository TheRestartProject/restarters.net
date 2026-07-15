<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useGroupsStore } from '../../stores/groups.js'
import RichTextEditor from '../forms/RichTextEditor.vue'
import LocationPicker from '../forms/LocationPicker.vue'

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
//
// Scope cuts vs the legacy form (documented here, not in api-gaps.md - these
// are UI simplifications, not missing endpoints):
//  - No client-side "duplicate group name" pre-check (legacy fetched every
//    group name to compare locally). The server's own `unique:groups`
//    validation on create returns the same information as a 422 on `name`,
//    rendered the same way as any other field error below.
//  - No NetworkData.vue custom-field editor (admin-only, network-specific
//    arbitrary key/value pairs). Because updateGroupv2 unconditionally
//    overwrites `network_data` with whatever's submitted (unlike
//    networks/tags, which are only synced when present), this component
//    round-trips the group's existing network_data unchanged on every save
//    rather than dropping it silently.
//  - Tags/networks are flat BFormCheckboxGroup lists rather than legacy's
//    network-grouped vue-multiselect with live intersection filtering -
//    server-side updateGroupv2 already re-validates tag ids against the
//    networks the editor can actually edit, so an over-permissive client
//    list can't grant anything the server wouldn't already reject.
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

// Round-tripped, never edited here - see the NetworkData scope-cut note
// above.
const networkData = props.initialGroup?.network_data || {}
const approved = ref(!!props.initialGroup?.approved)

const timezones = ref([])
const timezoneValid = computed(() => !form.timezone || !timezones.value.length || timezones.value.includes(form.timezone))

const networkOptions = ref([])
const tagOptions = computed(() =>
  groupsStore.tags.data.map((tag) => ({
    value: tag.id,
    text: tag.network_name ? `${tag.name} (${tag.network_name})` : tag.name,
  })),
)

const submitting = ref(false)
const generalError = ref('')
const fieldErrors = ref({})

function fieldError(field) {
  return fieldErrors.value[field]?.[0] || ''
}

onMounted(async () => {
  const { $api } = useNuxtApp()

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

  // Unlike its siblings above, groups.postcode is NOT NULL in the schema,
  // and Laravel's global ConvertEmptyStringsToNull middleware turns a
  // submitted '' into null before validateGroupParams ever sees it - its
  // `input('postcode', '')` default only kicks in when the key is absent
  // from the request entirely. So a blank postcode must be omitted here,
  // not sent as '' or null, or group creation 500s with a SQL
  // integrity-constraint error.
  if (form.postcode) {
    payload.postcode = form.postcode
  }

  if (!creating.value) {
    payload.network_data = JSON.stringify(networkData)

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
    <BAlert v-if="generalError" :model-value="true" variant="danger" data-testid="group-form-error">
      <!-- eslint-disable-next-line vue/no-v-html -->
      <span v-html="generalError" />
    </BAlert>

    <BFormGroup :label="`${t('groups.groups_name_of')}:`" label-for="group-form-name">
      <input
        id="group-form-name"
        v-model="form.name"
        type="text"
        class="form-control"
        :class="{ 'is-invalid': fieldError('name') }"
        data-testid="group-form-name"
      >
      <div v-if="fieldError('name')" class="invalid-feedback d-block" data-testid="group-form-name-error">
        {{ fieldError('name') }}
      </div>
      <small v-else class="form-text text-muted">{{ t('groups.groups_group_small') }}</small>
    </BFormGroup>

    <BFormGroup :label="`${t('groups.groups_website')}:`" label-for="group-form-website">
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

    <BFormGroup :label="`${t('groups.groups_email')}:`" label-for="group-form-email">
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

    <BFormGroup :label="`${t('groups.field_phone')}:`" label-for="group-form-phone">
      <input
        id="group-form-phone"
        v-model="form.phone"
        type="text"
        class="form-control"
        data-testid="group-form-phone"
      >
      <small class="form-text text-muted">{{ t('groups.phone_small') }}</small>
    </BFormGroup>

    <BFormGroup :label="`${t('groups.groups_about_group')}:`" label-for="group-form-description">
      <RichTextEditor
        v-model="form.description"
        testid="group-form-description"
        :has-error="!!fieldError('description')"
      />
      <div v-if="fieldError('description')" class="invalid-feedback d-block" data-testid="group-form-description-error">
        {{ fieldError('description') }}
      </div>
    </BFormGroup>

    <LocationPicker
      v-model:location="form.location"
      v-model:postcode="form.postcode"
      v-model:lat="form.lat"
      v-model:lng="form.lng"
      :has-error="!!fieldError('location')"
      :can-edit-postcode="canModerate || creating"
    />

    <BFormGroup :label="`${t('groups.timezone')}:`" label-for="group-form-timezone">
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

    <BCard v-if="canModerate || canNetwork" no-body class="group-admin mt-3">
      <BCardHeader>{{ t('groups.group_admin_only') }}</BCardHeader>
      <BCardBody>
        <BFormGroup v-if="canNetwork" :label="`${t('networks.networks')}:`" label-for="group-form-networks">
          <BFormCheckboxGroup
            id="group-form-networks"
            v-model="form.networkIds"
            :options="networkOptions"
            data-testid="group-form-networks"
          />
        </BFormGroup>

        <BFormGroup v-if="canModerate" :label="`${t('groups.group_tags')}:`" label-for="group-form-tags" class="mt-2">
          <BFormCheckboxGroup
            id="group-form-tags"
            v-model="form.tagIds"
            :options="tagOptions"
            data-testid="group-form-tags"
          />
        </BFormGroup>

        <BFormGroup v-if="canModerate" :label="`${t('groups.area')}:`" label-for="group-form-area" class="mt-2">
          <input id="group-form-area" v-model="form.area" type="text" class="form-control" data-testid="group-form-area">
        </BFormGroup>

        <BFormGroup v-if="canModerate && !approved" :label="`${t('groups.approve_group')}:`" label-for="group-form-moderate" class="mt-2">
          <select id="group-form-moderate" v-model="form.moderate" class="form-select" data-testid="group-form-moderate">
            <option value="" />
            <option value="approve">{{ t('client.groups.approve_option') }}</option>
          </select>
        </BFormGroup>
      </BCardBody>
    </BCard>

    <div class="d-flex justify-content-end mt-3">
      <BButton type="submit" variant="primary" :disabled="submitting" data-testid="group-form-submit">
        {{ creating ? t('groups.create_group') : t('groups.edit_group_save_changes') }}
      </BButton>
    </div>
  </BForm>
</template>
