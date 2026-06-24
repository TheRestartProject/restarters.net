<template>
  <div class="edit-panel">
    <div class="form-row">
      <div class="col-lg-12">
        <h3>{{ __('profile.calendars.title') }}</h3>
        <p>
          {{ __('profile.calendars.explainer') }}
          <a :href="findOutMoreUrl">{{ __('profile.calendars.find_out_more') }}</a>.
        </p>
      </div>
    </div>

    <div v-if="loading" class="text-center my-3">
      <b-spinner small />
    </div>

    <fieldset v-else class="listed-calendar-links">
      <h5 class="mb-3">{{ __('profile.calendars.my_events') }}</h5>
      <CopyLinkRow :url="data.user_url" />

      <h5 class="mb-3">{{ __('profile.calendars.group_calendars') }}</h5>
      <template v-for="g in data.groups">
        <p :key="`g-name-${g.id}`" class="mb-2">{{ g.name }}</p>
        <CopyLinkRow :key="`g-row-${g.id}`" :url="g.url" />
      </template>

      <template v-if="data.is_admin && data.admin_all_events_url">
        <h5 class="mb-3">
          <span class="span-vertically-align-middle">{{ __('profile.calendars.all_events') }}</span>
        </h5>
        <CopyLinkRow :url="data.admin_all_events_url" />
      </template>

      <h5 class="mb-3">{{ __('profile.calendars.events_by_area') }}</h5>
      <div class="input-group mb-3">
        <select
            v-model="selectedArea"
            class="form-control"
            data-testid="calendars-area-select"
        >
          <option v-for="area in data.group_areas" :key="area" :value="area">{{ area }}</option>
        </select>
        <input
            v-if="selectedArea"
            type="text"
            class="form-control"
            readonly
            :value="areaUrl"
        >
        <div class="input-group-append">
          <button
              type="button"
              class="btn btn-normal-padding btn-primary"
              :disabled="!selectedArea"
              @click="copy(areaUrl)"
          >
            {{ __('profile.calendars.copy_link') }}
          </button>
        </div>
      </div>
    </fieldset>
  </div>
</template>

<script>
import axios from 'axios'

const CopyLinkRow = {
  props: { url: { type: String, required: true } },
  template: `
    <div class="input-group mb-4">
      <input type="text" class="form-control" readonly :value="url">
      <div class="input-group-append">
        <button type="button" class="btn btn-normal-padding btn-primary" @click="copy">{{ $parent.__('profile.calendars.copy_link') }}</button>
      </div>
    </div>
  `,
  methods: {
    copy() {
      if (navigator.clipboard) {
        navigator.clipboard.writeText(this.url)
      }
    },
  },
}

export default {
  name: 'CalendarsTab',
  components: { CopyLinkRow },
  props: {
    findOutMoreUrl: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      data: {
        user_url: '',
        groups: [],
        is_admin: false,
        admin_all_events_url: null,
        group_areas: [],
      },
      selectedArea: '',
      loading: true,
    }
  },
  computed: {
    areaUrl() {
      return this.selectedArea ? `/calendar/group-area/${encodeURIComponent(this.selectedArea)}` : ''
    },
  },
  async mounted() {
    try {
      const { data } = await axios.get('/api/v2/users/me/calendars')
      this.data = data.data
      if (this.data.group_areas && this.data.group_areas.length) {
        this.selectedArea = this.data.group_areas[0]
      }
    } catch (e) {
      console.error('Failed to load calendars', e)
    } finally {
      this.loading = false
    }
  },
  methods: {
    copy(text) {
      if (navigator.clipboard) {
        navigator.clipboard.writeText(text)
      }
    },
  },
}
</script>
