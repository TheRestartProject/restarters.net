<template>
  <b-form-group>
    <label for="event_group" class="moveright">{{ __('events.field_event_group') }}:</label>
    <multiselect
        v-model="currentGroupValue"
        :options="groupOptions"
        track-by="idgroups"
        label="name"
        :allow-empty="false"
        deselectLabel=""
        :placeholder="__('partials.please_choose')"
        :class="{ hasError: hasError }"
        :disabled="disabled"
    />
    <input type="hidden" name="group" :value="currentIdGroups" />
  </b-form-group>
</template>
<script>
export default {
  props: {
    value: {
      type: Number,
      required: false,
      default: null
    },
    hasError: {
      type: Boolean,
      required: false,
      default: false
    },
    disabled: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  data () {
    return {
      currentGroupValue: null,
    }
  },
  computed: {
    currentIdGroups() {
      return this.currentGroupValue ? this.currentGroupValue.idgroups : null
    },
    groups() {
      return this.$store.getters['groups/list']
    },
    groupOptions() {
      return this.groups ? this.groups.sort((a, b) => {
        return a.name.localeCompare(b.name)
      }) : []
    },
  },
  watch: {
    value: {
      handler: function(newVal) {
        if (newVal) {
          this.currentGroupValue = this.groups.find(g => g.idgroups === newVal)
        }
      },
      immediate: true
    },
    currentIdGroups(newVal) {
      this.$emit('update:value', newVal)
    },
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

::v-deep .hasError {
  &.multiselect {
    border: 1px solid $brand-danger !important;
  }
  .multiselect__tags {
    border: 1px solid $brand-danger !important;
  }
}

::v-deep .multiselect {
  border: 1px solid $black !important;
  font-family: "Open Sans", "sans-serif" !important;

  outline: 3px;
  margin: 2px;
  margin-right: 3px;
  width: calc(100% - 4px) !important;

  &.multiselect--active {
    border: 3px solid $black !important;
    outline: 0px !important;
    margin: 0px !important;
  }
}
</style>