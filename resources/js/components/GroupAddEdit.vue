<template>
  <div>
    TODO Postcode/area for admins only
    TODO Tags
    TODO Networks
    <p v-if="creating">
      {{ __('groups.add_groups_content') }}
    </p>
    <p v-else>
      {{ __('groups.edit_group_text') }}
    </p>

    <input type="hidden" id="lat" v-model="lat"/>
    <input type="hidden" id="lng" v-model="lng"/>

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

      <div class="group-approve" v-if="canApprove">
        <b-form-group>
          <label class="groups-tags-label" for="moderate">
            <svg width="18" height="18" viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd"
                 clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414">
              <g fill="#0394a6">
                <path
                    d="M7.5 1.58a5.941 5.941 0 0 1 5.939 5.938A5.942 5.942 0 0 1 7.5 13.457a5.942 5.942 0 0 1-5.939-5.939A5.941 5.941 0 0 1 7.5 1.58zm0 3.04a2.899 2.899 0 1 1-2.898 2.899A2.9 2.9 0 0 1 7.5 4.62z"></path>
                <ellipse cx="6.472" cy=".217" rx=".274" ry=".217"></ellipse>
                <ellipse cx="8.528" cy=".217" rx=".274" ry=".217"></ellipse>
                <path d="M6.472 0h2.056v1.394H6.472z"></path>
                <path d="M8.802.217H6.198l-.274 1.562h3.152L8.802.217z"></path>
                <ellipse cx="8.528" cy="14.783" rx=".274" ry=".217"></ellipse>
                <ellipse cx="6.472" cy="14.783" rx=".274" ry=".217"></ellipse>
                <path d="M6.472 13.606h2.056V15H6.472z"></path>
                <path
                    d="M6.198 14.783h2.604l.274-1.562H5.924l.274 1.562zM1.47 2.923c.107-.106.262-.125.347-.04.084.085.066.24-.041.347-.107.107-.262.125-.346.04-.085-.084-.067-.24.04-.347zM2.923 1.47c.107-.107.263-.125.347-.04.085.084.067.239-.04.346-.107.107-.262.125-.347.041-.085-.085-.066-.24.04-.347z"></path>
                <path d="M2.923 1.47L1.47 2.923l.986.986 1.453-1.453-.986-.986z"></path>
                <path
                    d="M3.27 1.43L1.43 3.27l.91 1.299L4.569 2.34 3.27 1.43zm10.26 10.647c-.107.106-.262.125-.347.04-.084-.085-.066-.24.041-.347.107-.107.262-.125.346-.04.085.084.067.24-.04.347zm-1.453 1.453c-.107.107-.263.125-.347.04-.085-.084-.067-.239.04-.346.107-.107.262-.125.347-.041.085.085.066.24-.04.347z"></path>
                <path d="M12.077 13.53l1.453-1.453-.986-.986-1.453 1.453.986.986z"></path>
                <path
                    d="M11.73 13.57l1.84-1.84-.91-1.299-2.229 2.229 1.299.91zM0 8.528c0-.151.097-.274.217-.274.119 0 .216.123.216.274 0 .151-.097.274-.216.274-.12 0-.217-.123-.217-.274zm0-2.056c0-.151.097-.274.217-.274.119 0 .216.123.216.274 0 .151-.097.274-.216.274-.12 0-.217-.123-.217-.274z"></path>
                <path d="M0 6.472v2.056h1.394V6.472H0z"></path>
                <path
                    d="M.217 6.198v2.604l1.562.274V5.924l-1.562.274zM15 6.472c0 .151-.097.274-.217.274-.119 0-.216-.123-.216-.274 0-.151.097-.274.216-.274.12 0 .217.123.217.274zm0 2.056c0 .151-.097.274-.217.274-.119 0-.216-.123-.216-.274 0-.151.097-.274.216-.274.12 0 .217.123.217.274z"></path>
                <path d="M15 8.528V6.472h-1.394v2.056H15z"></path>
                <path
                    d="M14.783 8.802V6.198l-1.562-.274v3.152l1.562-.274zM2.923 13.53c-.106-.107-.125-.262-.04-.347.085-.084.24-.066.347.041.107.107.125.262.04.346-.084.085-.24.067-.347-.04zM1.47 12.077c-.107-.107-.125-.263-.04-.347.084-.085.239-.067.346.04.107.107.125.262.041.347-.085.085-.24.066-.347-.04z"></path>
                <path d="M1.47 12.077l1.453 1.453.986-.986-1.453-1.453-.986.986z"></path>
                <path
                    d="M1.43 11.73l1.84 1.84 1.299-.91-2.229-2.229-.91 1.299zM12.077 1.47c.106.107.125.262.04.347-.085.084-.24.066-.347-.041-.107-.107-.125-.262-.04-.346.084-.085.24-.067.347.04zm1.453 1.453c.107.107.125.263.04.347-.084.085-.239.067-.346-.04-.107-.107-.125-.262-.041-.347.085-.085.24-.066.347.04z"></path>
                <path d="M13.53 2.923L12.077 1.47l-.986.986 1.453 1.453.986-.986z"></path>
                <path d="M13.57 3.27l-1.84-1.84-1.299.91 2.229 2.229.91-1.299z"></path>
              </g>
            </svg>
            {{ __('groups.approve_group') }}</label>
          <b-select v-model="moderate" name="moderate">
            <option></option>
            <option value="approve">Approve</option>
          </b-select>
        </b-form-group>
      </div>
      <div class="group-buttons" v-else>
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
      <p class="text-danger font-weight-bold" v-if="failed" v-html="__('groups.create_failed')"/>
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
      // TODO Approval.
      type: Boolean,
      required: false,
      default: false
    }
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
            if ((this.creating || this.idgroups != group.id) && group.name.toLowerCase() === this.name.toLowerCase()) {
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
    }
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
    }

    this.ready = true
  },
  methods: {
    async submit () {
      // Events are created via form submission - we don't yet have an API call to do this over AJAX.  Therefore
      // this page and the subcomponents have form inputs with suitable names.
      this.failed = false
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
              // Success.
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
                image: this.image
              })

              if (id) {
                // Reload the page.  We don't need to do this for the data, but people will expect it.
                window.location = '/group/edit/' + id
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

  .group-approve {
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
    grid-colum: 1 / 2;

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