<template>
  <b-form-group>
    <label for="group_website">{{ __('groups.groups_website') }}:</label>
    <b-input type="url" id="group_website" name="website" v-model="currentwebsite" :class="{ hasError: hasError }"/>
    <small v-if="hasError" class="form-text text-danger group-website-error">
      {{ __('groups.groups_website_invalid') }}
    </small>
    <small v-else>{{ __('groups.groups_website_small') }}</small>
  </b-form-group>
</template>
<script>
export default {
  props: {
    website: {
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
      currentwebsite: null,
    }
  },
  mounted () {
    this.currentwebsite = this.website
  },
  watch: {
    currentwebsite(newVal) {
      this.$emit('update:website', newVal)
    },
  }
}
</script>