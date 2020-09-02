<template>
  <div>
    This is just an example Vue component.  It's not used anywhere yet, and we'll delete it when we have better
    examples.

    Repair status for {{ device.iddevices }} {{ device }}
    <div class="d-flex justify-content-between flex-wrap">
      <!--      Can change to use select2 - need to install that component.-->
      <b-select :value="device.repair_status" :options="statusOptions" class="not100"/>
      <b-select :value="detailsValue" :options="detailsOptions" class="not100"/>
      <b-select :value="partsValue" :options="partsOptions" class="not100"/>
    </div>
  </div>
</template>
<script>
const STATUS_PLEASE_SELECT = 0
const STATUS_FIXED = 1
const STATUS_REPAIRABLE = 2
const STATUS_END_OF_LIFE = 3

const DETAILS_PLEASE_SELECT = 0
const DETAILS_MORE_TIME = 1
const DETAILS_PROFESSIONAL_HELP = 2
const DETAILS_DIY = 3

const PARTS_MANUFACTURER = 1
const PARTS_NO = 2
const PARTS_THIRD_PARTY = 3
const PARTS_PLEASE_SELECT = 4

export default {
  props: {
    device: {
      type: Object,
      required: true
    }
  },
  computed: {
    statusOptions () {
      return [
        {
          value: STATUS_PLEASE_SELECT,
          text: this.$lang.get('general.please_select')
        },
        {
          value: STATUS_FIXED,
          text: this.$lang.get('partials.fixed')
        },
        {
          value: STATUS_REPAIRABLE,
          text: this.$lang.get('partials.repairable')
        },
        {
          value: STATUS_END_OF_LIFE,
          text: this.$lang.get('partials.end_of_life')
        },
      ]
    },
    detailsOptions () {
      return [
        {
          value: DETAILS_PLEASE_SELECT,
          text: this.$lang.get('general.please_select')
        },
        {
          value: DETAILS_MORE_TIME,
          text: this.$lang.get('partials.more_time')
        },
        {
          value: DETAILS_PROFESSIONAL_HELP,
          text: this.$lang.get('partials.professional_help')
        },
        {
          value: DETAILS_DIY,
          text: this.$lang.get('partials.diy')
        }
      ]
    },
    partsOptions () {
      return [
        {
          value: PARTS_PLEASE_SELECT,
          text: this.$lang.get('general.please_select')
        },
        {
          value: PARTS_MANUFACTURER,
          text: this.$lang.get('partials.yes_manufacturer')
        },
        {
          value: PARTS_THIRD_PARTY,
          text: this.$lang.get('partials.yes_third_party')
        },
        {
          value: PARTS_NO,
          text: this.$lang.get('partials.no')
        }
      ]
    },
    detailsValue () {
      if (this.device.more_time_needed) {
        return DETAILS_MORE_TIME
      } else if (this.device.professional_help) {
        return DETAILS_PROFESSIONAL_HELP
      } else if (this.device.do_it_yourself) {
        return DETAILS_DIY
      } else {
        return DETAILS_PLEASE_SELECT
      }
    },
    partsValue () {
      // This logic seems to produce different output from the current, but the current logic looks wrong.
      if (this.spare_parts === 2) {
        return PARTS_NO
      } else if (this.parts_provider === 2) {
        return PARTS_THIRD_PARTY
      } else if (this.device.spare_parts === 1 && this.device.parts_provider) {
        return PARTS_MANUFACTURER
      } else {
        return PARTS_PLEASE_SELECT
      }
    }
  }
}
</script>
<style scoped lang="scss">
.not100 {
  width: initial;
}
</style>