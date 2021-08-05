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
    />
    <input type="hidden" name="group" :value="currentIdGroups" />
  </b-form-group>
</template>
<script>
export default {
  props: {
    idgroups: {
      type: Number,
      required: false,
      default: null
    },
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
  mounted() {
    if (this.idgroups) {
      this.currentGroupValue = this.groups.find(g => g.idgroups === this.idgroups)
    }
  },
  watch: {
    currentIdGroups(newVal) {
      this.$emit('update:value', newVal)
    },
  }
}
</script>