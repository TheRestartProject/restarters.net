<template>
  <div>
    <multiselect
        class="mb-2"
        :value="statusValue"
        :placeholder="translatedRepairOutcome"
        :options="statusOptions"
        track-by="id"
        label="text"
        :multiple="false"
        :allow-empty="false"
        deselect-label=""
        :taggable="false"
        selectLabel=""
        ref="multiselect"
        @select="$emit('update:status', $event.id)">
    </multiselect>
    <multiselect
        class="mb-2"
        v-if="showSteps"
        :value="stepsValue"
        :placeholder="translatedNextSteps"
        :options="stepsOptions"
        :multiple="false"
        :allow-empty="false"
        deselect-label=""
        track-by="id"
        label="text"
        :taggable="false"
        selectLabel=""
        ref="multiselect"
        @select="$emit('update:steps', $event.id)"
    />
    <multiselect
        v-if="showParts"
        :value="partsValue"
        :placeholder="translatedSpareParts"
        :options="partsOptions"
        :multiple="false"
        :allow-empty="false"
        deselect-label=""
        track-by="id"
        label="text"
        :taggable="false"
        selectLabel=""
        ref="multiselect"
        @select="$emit('update:parts', $event.id)"
    />
  </div>
</template>
<script>
import {
  END_OF_LIFE,
  FIXED,
  NEXT_STEPS_DIY,
  NEXT_STEPS_MORE_TIME,
  NEXT_STEPS_PROFESSIONAL,
  REPAIRABLE, SPARE_PARTS_MANUFACTURER, SPARE_PARTS_NOT_NEEDED, SPARE_PARTS_THIRD_PARTY
} from '../constants'

export default {
  props: {
    status: {
      type: Number,
      required: false,
      default: null
    },
    parts: {
      type: Number,
      required: false,
      default: null
    },
    steps: {
      type: Number,
      required: false,
      default: null
    }
  },
  computed: {
    // We have to have a separate copy of this to avoid multiselect mutating the prop directly, which Vue doesn't
    // like.  multiselect is also a bit annoying about not being able to set by the value, only the option object.
    //
    // This kind of two-way binding will improve in Vue 3, supposedly.
    statusValue: {
      get() {
        return this.statusOptions.find(o => {
          return o.id === this.status
        })
      },
      set(newval) {
        this.$emit('update:status', newval)
      }
    },
    statusOptions () {
      return [
        {
          id: FIXED,
          text: this.$lang.get('partials.fixed')
        },
        {
          id: REPAIRABLE,
          text: this.$lang.get('partials.repairable')
        },
        {
          id: END_OF_LIFE,
          text: this.$lang.get('partials.end_of_life')
        }
      ]
    },
    stepsValue: {
      get() {
        return this.stepsOptions.find(o => {
          return o.id === this.steps
        })
      },
      set(newval) {
        this.$emit('update:steps', newval)
      }
    },
    stepsOptions () {
      return [
        {
          id: NEXT_STEPS_MORE_TIME,
          text: this.$lang.get('partials.more_time')
        },
        {
          id: NEXT_STEPS_PROFESSIONAL,
          text: this.$lang.get('partials.professional_help')
        },
        {
          id: NEXT_STEPS_DIY,
          text: this.$lang.get('partials.diy')
        }
      ]
    },
    partsValue: {
      get() {
        return this.partsOptions.find(o => {
          return o.id === this.parts
        })
      },
      set(newval) {
        this.$emit('update:parts', newval)
      }
    },
    partsOptions () {
      return [
        {
          id: SPARE_PARTS_THIRD_PARTY,
          text: this.$lang.get('partials.yes_third_party')
        },
        {
          id: SPARE_PARTS_MANUFACTURER,
          text: this.$lang.get('partials.yes_manufacturer')
        },
        {
          id: SPARE_PARTS_NOT_NEEDED,
          text: this.$lang.get('partials.no')
        }
      ]
    },
    showSteps () {
      return this.status === REPAIRABLE
    },
    showParts () {
      return this.status === REPAIRABLE || this.status === FIXED
    },
    translatedRepairOutcome () {
      return this.$lang.get('devices.repair_outcome')
    },
    translatedNextSteps () {
      return this.$lang.get('devices.repair_details')
    },
    translatedSpareParts() {
      return this.$lang.get('devices.spare_parts')
    },
    translatedMoreTime () {
      return this.$lang.get('partials.more_time')
    },
    translatedProfessionalHelp () {
      return this.$lang.get('partials.professional_help')
    },
    translatedDIY () {
      return this.$lang.get('partials.diy')
    },
  }
}
</script>
<style scoped lang="scss">
// Reduce the size of the options so they fit
::v-deep .multiselect__single, .multiselect__content-wrapper {
  font-size: 14px;
}
</style>