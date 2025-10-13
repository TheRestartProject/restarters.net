<template>
  <div class="mb-2">
    <multiselect
        :disabled="disabled"
        v-model="statusValue"
        class="repair-outcome"
        :placeholder="__('devices.repair_outcome')"
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
        :disabled="disabled"
        v-if="showSteps"
        v-model="stepsValue"
        :placeholder="__('devices.repair_details')"
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
        :disabled="disabled"
        v-if="showParts"
        v-model="partsValue"
        class="spare-parts"
        :placeholder="__('devices.spare_parts')"
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
        :disabled="disabled"
        v-if="showBarriers"
        v-model="barrierValue"
        :placeholder="__('partials.choose_barriers')"
        :options="barriersOptions"
        :multiple="false"
        :allow-empty="false"
        deselect-label=""
        track-by="id"
        label="text"
        :taggable="false"
        selectLabel=""
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
      type: String,
      required: false,
      default: null
    },
    parts: {
      type: String,
      required: false,
      default: null
    },
    steps: {
      type: String,
      required: false,
      default: null
    },
    barrier: {
      type: String,
      required: false,
      default: null
    },
    barrierList: {
      type: Array,
      required: true
    },
    disabled: {
      type: Boolean,
      required: false,
      default: false
    },
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
          text: this.__('partials.fixed')
        },
        {
          id: REPAIRABLE,
          text: this.__('partials.repairable')
        },
        {
          id: END_OF_LIFE,
          text: this.__('partials.end_of_life')
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
          text: this.__('partials.more_time')
        },
        {
          id: NEXT_STEPS_PROFESSIONAL,
          text: this.__('partials.professional_help')
        },
        {
          id: NEXT_STEPS_DIY,
          text: this.__('partials.diy')
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
          text: this.__('partials.yes_third_party')
        },
        {
          id: SPARE_PARTS_MANUFACTURER,
          text: this.__('partials.yes_manufacturer')
        },
        {
          id: SPARE_PARTS_NOT_NEEDED,
          text: this.__('partials.no')
        }
      ]
    },
    barriersOptions() {
      return this.barrierList.map(b => {
        var newb = {
          id: b.barrier,
          text: this.__('strings.' + b.barrier)
        }

        return newb
      })
    },
    barrierValue: {
      get() {
        return this.barriersOptions.find(o => {
          return o.id === this.barrier
        })
      },
      set(newval) {
        this.$emit('update:barrier', newval.id)
      }
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