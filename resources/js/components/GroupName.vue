<template>
  <b-form-group>
    <label for="group_name">{{ __('groups.groups_name_of') }}:</label>
    <b-input id="group_name" name="name" v-model="currentname" :class="{ hasError: hasError }"/>
    <small>{{ __('groups.groups_group_small') }}</small>
  </b-form-group>
</template>
<script>
export default {
  props: {
    name: {
      type: String,
      required: false,
      default: null
    },
    hasError: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  data () {
    return {
      currentname: null,
    }
  },
  mounted () {
    this.currentname = this.name
  },
  watch: {
    currentname(newVal) {
      this.$emit('update:name', newVal)
    },
  }
}
</script>