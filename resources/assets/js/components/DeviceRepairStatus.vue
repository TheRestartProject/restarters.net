<template>
  <div>
    <multiselect
        class="mb-2"
        v-model="statusValue"
        :placeholder="translatedRepairOutcome"
        :options="statusOptions"
        track-by="id"
        label="text"
        :multiple="false"
        :allow-empty="false"
        deselect-label=""
        :taggable="false"
        selectLabel=""
    />
    <multiselect
        class="mb-2"
        v-if="showSteps"
        v-model="stepsValue"
        :placeholder="translatedNextSteps"
        :options="stepsOptions"
        :multiple="false"
        :allow-empty="false"
        deselect-label=""
        track-by="id"
        label="text"
        :taggable="false"
        selectLabel=""
    />
    <multiselect
        v-if="showParts"
        v-model="partsValue"
        :placeholder="translatedSpareParts"
        :options="partsOptions"
        :multiple="false"
        :allow-empty="false"
        deselect-label=""
        track-by="id"
        label="text"
        :taggable="false"
        selectLabel=""
    />
    <multiselect
        v-if="showBarriers"
        v-model="barriersValue"
        :placeholder="translatedBarriers"
        :options="barrierList"
        :multiple="true"
        :allow-empty="false"
        deselect-label=""
        track-by="id"
        label="barrier"
        :taggable="false"
        selectLabel=""
        selectedLabel=""
        :allowEmpty="true"
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
    },
    barriers: {
      type: Array,
      required: false,
      default: function() {
        return []
      }
    },
    barrierList: {
      type: Array,
      required: true
    }
  },
  computed: {
    showSteps () {
      return this.status === REPAIRABLE
    },
    showParts () {
      return this.status === REPAIRABLE || this.status === FIXED
    },
    showBarriers () {
      return this.status === END_OF_LIFE
    },
    // We have to have a separate copy of values to avoid multiselect mutating the prop directly, which Vue doesn't
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
        this.$emit('update:status', newval.id)
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
        this.$emit('update:steps', newval.id)
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
        this.$emit('update:parts', newval.id)
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
    barriersValue: {
      get() {
        // We have an array of ids which we need to map to an array of options.
        return this.barrierList.filter(b => {
          return this.barriers && this.barriers.indexOf(b.id) !== -1
        })
      },
      set(newval) {
        // We have an array of options we want to emit as an array of ids.
        this.$emit('update:barriers', newval.map(o => o.id))
      }
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
    translatedBarriers() {
      return this.$lang.get('partials.choose_barriers')
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