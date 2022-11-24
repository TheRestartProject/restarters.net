<template>
  <div>
    TODO Postcode/area for admins only
    <p v-if="creating">
      {{ __('groups.add_groups_content') }}
    </p>
    <p v-else>
      {{ __('groups.edit_group_text') }}
    </p>

    <div class="layout" v-if="ready">
      <div class="flex-grow-1 group-name">
        <GroupName
            class=""
            :name.sync="name"
            :has-error="$v.name.$error || duplicateName"
            ref="name"/>
        <p v-if="duplicateName" class="text-danger font-weight-bold">
          {{ duplicateError }}
        </p>
      </div>
      <GroupWebsite
          class="flex-grow-1 group-website"
          :website.sync="website"
          :has-error="$v.website.$error"
          ref="website"/>
      <div class="form-group group-description">
        <b-form-group>
          <label for="group_desc">{{ __('groups.groups_about_group') }}:</label>
          <RichTextEditor
              id="group_desc"
              name="description"
              class="moveright"
              :value.sync="description"
              :has-error="$v.description.$error"
              ref="free_text"/>
        </b-form-group>
      </div>
      <!-- These are inputs for playwright testing. -->
      <input type="text" id="lat" name="lat" v-model="lat" style="width: 1px; height: 1px; position: fixed; bottom: 0px; left: 0px;" />
      <input type="text" id="lng" name="lng" v-model="lng"  style="width: 1px; height: 1px; position: fixed; bottom: 0px; left: 2px;" />
      <input type="text" id="location" name="location" v-model="location"  style="width: 1px; height: 1px; position: fixed; bottom: 0px; left: 3px;" />

      <GroupLocation
          :all-groups="groups"
          :value.sync="location"
          :lat.sync="lat"
          :lng.sync="lng"
          :postcode.sync="postcode"
          :area.sync="area"
          class="group-location"
          :has-error="$v.location.$error"
          ref="location"
      />
      <GroupLocationMap
          :lat="lat"
          :lng="lng"
          class="group-locationmap"
          ref="locationmap"
          :id="lat + ',' + lng"
          v-if="lat || lng"
      />
      <GroupTimeZone
          :timezone.sync="timezone"
          class="group-timezone"
          :has-error="!timezoneValid"
          ref="timezone"
      />
      <GroupPhone
          :phone.sync="phone"
          class="group-phone"
          ref="phone"
      />
      <GroupImage
          :image.sync="image"
          class="group-image"
          ref="image"
      />
      <b-card v-if="canApprove" no-body class="group-admin">
        <b-card-header>
          <b-img src="/images/cog.svg" />
          {{ __('groups.group_admin_only') }}
        </b-card-header>
        <b-card-body>
          <div v-if="canNetwork">
            <label for="networks">
              {{ __('networks.networks') }}
            </label>
            <multiselect
                id="networks"
                v-model="networkList"
                :options="networkOptions"
                track-by="id"
                label="name"
                multiple
                deselect-label=""
                :taggable="false"
                selectLabel=""
                class="m-0 mb-1 mb-md-0"
                allow-empty
                :selectedLabel="__('partials.remove')"
            />
          </div>
          <div class="mt-2" v-if="canNetwork">
            <label for="tags">
              {{ __('groups.group_tags') }}
            </label>
            <multiselect
                id="tags"
                v-model="tagList"
                :options="tagOptions"
                track-by="id"
                label="name"
                multiple
                deselect-label=""
                :taggable="false"
                selectLabel=""
                class="m-0 mb-1 mb-md-0"
                allow-empty
                :selectedLabel="__('partials.remove')"
            />
          </div>
          <div class="mt-2" v-if="!approved && canApprove">
            <b-form-group>
              <label class="groups-tags-label" for="moderate">
                {{ __('groups.approve_group') }}
              </label>
              <b-select v-model="moderate" name="moderate">
                <option></option>
                <option value="approve">Approve</option>
              </b-select>
            </b-form-group>
          </div>
        </b-card-body>
      </b-card>

      <div class="group-buttons text-right">
        <p v-if="edited" class="text-success font-weight-bold" v-html="__('groups.edit_succeeded')" />
        <div v-else-if="failed">
          <p v-if="creating" class="text-danger font-weight-bold" v-html="__('groups.create_failed')"/>
          <p v-else class="text-danger font-weight-bold" v-html="__('groups.edit_failed')"/>
        </div>

        <div class="d-flex justify-content-between flex-wrap" v-if="creating">
          <div class="text-right flex-grow-1 mr-4">
            {{ __('groups.groups_approval_text') }}
          </div>
          <b-btn variant="primary" class="break" type="submit" @click="submit">
            {{ __('groups.create_group') }}
          </b-btn>
        </div>
        <div class="d-flex justify-content-end" v-else>
          <b-btn variant="primary" class="break submit" type="submit" @click="submit">
            {{ __('groups.edit_group_save_changes') }}
          </b-btn>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import group from '../mixins/group'
import auth from '../mixins/auth'
import RichTextEditor from './RichTextEditor'
import { required, url, helpers } from 'vuelidate/lib/validators'
import validationHelpers from '../mixins/validationHelpers'
import GroupName from './GroupName'
import GroupWebsite from './GroupWebsite'
import GroupLocation from './GroupLocation'
import GroupLocationMap from './GroupLocationMap'
import GroupTimeZone from './GroupTimeZone'
import GroupPhone from './GroupPhone'
import GroupImage from './GroupImage'

function geocodeableValidation () {
  return this.lat !== null && this.lng !== null
}

export default {
  components: {
    GroupTimeZone,
    RichTextEditor,
    GroupName,
    GroupWebsite,
    GroupLocation,
    GroupLocationMap,
    GroupPhone,
    GroupImage
  },
  mixins: [group, auth, validationHelpers],
  props: {
    idgroups: {
      type: Number,
      required: false,
      default: null
    },
    canApprove: {
      type: Boolean,
      required: false,
      default: false
    },
    canNetwork: {
      type: Boolean,
      required: false,
      default: false
    },
  },
  data () {
    return {
      name: null,
      location: null,
      phone: null,
      website: null,
      timezone: null,
      timezoneValid: true,
      description: null,
      lat: null,
      lng: null,
      postcode: null,
      area: null,
      moderate: null,
      failed: false,
      image: null,
      ready: false,
      approved: false,
      edited: false,
      networkList: null,
      tagList: null
    }
  },
  validations: {
    // We use vuelidate to validate the inputs.  If necessary we pass the relevant validation down to a child component,
    // which is responsible for setting the hasError class.
    //
    // These need to match API\GroupController::createGroupv2.
    name: {
      required,
    },
    description: {
      required
    },
    location: {
      geocodeableValidation
    },
    website: {
      url
    }
  },
  computed: {
    creating () {
      return !this.idgroups
    },
    groups () {
      let groups = this.$store.getters['groups/list']

      groups = JSON.parse(JSON.stringify(groups))

      const ret = groups ? groups.sort((a, b) => {
        return a.name.localeCompare(b.name)
      }) : []

      return ret
    },
    duplicateName () {
      let ret = false

      if (this.name && this.groups && this.groups.length) {
        this.groups.forEach(group => {
            if ((this.creating || this.idgroups != parseInt(group.id)) && group.name.toLowerCase() === this.name.toLowerCase()) {
              ret = true
            }
        })
      }

      return ret
    },
    duplicateError () {
      return this.$lang.get('groups.duplicate', {
        name: this.name
      })
    },
    networkOptions() {
      const networks = this.$store.getters['networks/list']

      return networks ? networks.map(n => {
        return {
          id: n.id,
          name: n.name
        }
      }) : []
    },
    tagOptions() {
      const tags = this.$store.getters['groups/listTags']

      return tags ? tags.map(n => {
        return {
          id: n.id,
          name: n.name
        }
      }) : []
    },
  },
  async mounted () {
    // Fetch the list of groups, so that we can ensure group names are unique.  No need to await because the check
    // can happen later.
    this.$store.dispatch('groups/list')

    if (this.idgroups) {
      // Fetch the group we're editing.
      let group = await this.$store.dispatch('groups/fetch', {
        id: this.idgroups
      })

      this.name = group.name
      this.location = group.location.location
      this.postcode = group.location.postcode
      this.area = group.location.area
      this.phone = group.phone
      this.website = group.website
      this.timezone = group.timezone
      this.description = group.description
      this.lat = parseFloat(group.location.lat)
      this.lng = parseFloat(group.location.lng)
      this.image = group.image
      this.approved = group.approved
      this.networkList = group.networks
      this.tagList = group.tags
    }

    if (this.canNetwork) {
      // Fetch the list of networks.
      this.$store.dispatch('networks/list')

      // Fetch the list of tags.
      this.$store.dispatch('groups/listTags')
    }

    this.ready = true
  },
  methods: {
    async submit () {
      // Events are created via form submission - we don't yet have an API call to do this over AJAX.  Therefore
      // this page and the subcomponents have form inputs with suitable names.
      this.failed = false
      this.edited = false

      this.$v.$touch()

      if (!this.duplicateName) {
        // Check the form is valid.
        if (this.$v.$invalid) {
          // It's not.
          this.validationFocusFirstError()
        } else {
          if (this.creating) {
            const id = await this.$store.dispatch('groups/create', {
              name: this.name,
              website: this.website,
              description: this.description,
              location: this.location,
              postcode: this.postcode,
              area: this.area,
              timezone: this.timezone,
              phone: this.phone,
              image: this.image
            })

            if (id) {
              // Success.  Go to the edit page.
              window.location = '/group/edit/' + id
            } else {
              this.failed = true
            }
          } else {
            if (this.$v.$invalid) {
              // It's not.
              this.validationFocusFirstError()
            } else {
              let id = await this.$store.dispatch('groups/edit', {
                id: this.idgroups,
                name: this.name,
                website: this.website,
                description: this.description,
                location: this.location,
                postcode: this.postcode,
                area: this.area,
                timezone: this.timezone,
                phone: this.phone,
                image: this.image,
                moderate: this.moderate,
                networks: JSON.stringify(this.networkList.map(n => n.id)),
                tags: JSON.stringify(this.tagList.map(n => n.id))
              })

              if (id) {
                // Don't reload the page, because group approval is handled asynchronously, and hence the
                // group approval status might not have been updated yet.  Handle this locally.
                this.approved = this.moderate === 'approve'
                this.edited = true
              } else {
                this.failed = true
              }
            }
          }
        }
      }
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.box {
  background-color: $white;
  box-shadow: 5px 5px $black;
  border: 1px solid $black;
  border-radius: 0;
}

.layout {
  display: grid;
  grid-column-gap: 40px;

  grid-template-columns: 1fr;

  @include media-breakpoint-up(lg) {
    grid-template-columns: 2fr 1.5fr 1fr;
  }

  .group-name {
    grid-row: 1 / 2;
    grid-column: 1 / 2;
  }

  .group-website {
    grid-row: 2 / 3;
    grid-column: 1 / 2;
    margin-right: 2px;
  }

  .group-description {
    grid-row: 3 / 4;
    grid-column: 1 / 2;

    @include media-breakpoint-up(lg) {
      grid-row: 3 / 7;
    }
  }

  .group-location {
    grid-row: 4 / 5;
    grid-column: 1 / 2;

    /deep/ .btn {
      font-size: 16px;
    }

    @include media-breakpoint-up(lg) {
      grid-row: 1 / 2;
      grid-column: 2 / 3;
    }
  }

  .group-locationmap {
    grid-row: 5 / 6;
    grid-column: 1 / 2;

    @include media-breakpoint-up(lg) {
      grid-row: 1 / 4;
      grid-column: 3 / 4;
    }
  }

  .group-timezone {
    grid-row: 6 / 7;
    grid-column: 1 / 2;
    margin-right: 2px;

    @include media-breakpoint-up(lg) {
      grid-row: 2 / 3;
      grid-column: 2 / 3;
    }
  }

  .group-phone {
    grid-row: 7 / 8;
    grid-column: 1 / 2;
    margin-right: 2px;

    @include media-breakpoint-up(lg) {
      grid-row: 3 / 4;
      grid-column: 2 / 3;
    }
  }

  .group-image {
    grid-row: 8 / 9;
    grid-column: 1 / 2;
    margin-right: 2px;

    @include media-breakpoint-up(lg) {
      grid-row: 4 / 5;
      grid-column: 2 / 3;
    }
  }

  .group-admin {
    grid-row: 9 / 10;
    grid-column: 1 / 2;

    /deep/ .btn {
      font-size: 16px;
    }

    @include media-breakpoint-up(lg) {
      grid-row: 5 / 6;
      grid-column: 2 / 4;
    }
  }

  .group-buttons {
    grid-row: 9 / 10;
    grid-column: 1 / 2;

    /deep/ .btn {
      font-size: 16px;
    }

    @include media-breakpoint-up(lg) {
      grid-row: 7 / 8;
      grid-column: 1 / 4;
    }
  }
}

.online {
  min-width: 50px;
  margin-top: 1rem;

  label {
    font-weight: normal;
  }
}

/deep/ .form-control, /deep/ .custom-checkbox input {
  border: 2px solid $black !important;
}

.moveright {
  margin-left: 2px;
}

.movedown {
  margin-top: 2px;
}

/deep/ .hasError, /deep/ .card .form-control.hasError:focus {
  border: 2px solid $brand-danger !important;
  margin: 0px !important;
}

.notice {
  font-size: 15px;
}

/deep/ .ql-toolbar button {
  width: 30px !important;
}
</style>