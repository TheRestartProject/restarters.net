<template>
  <div>
    <label for="postcode">{{ __('groups.timezone') }}:</label>
    <vue-typeahead-bootstrap
        v-model="currentValue"
        :maxMatches="3"
        :data="timezones"
        :minMatchingChars="1"
        inputClass="form-control field timezone"
        :class="{
'invalid': !valid
        }"
    />
    <small class="form-text text-muted">
      {{ __('groups.timezone_placeholder') }}
    </small>
    <input type="hidden" name="timezone" :value="currentValue" />
  </div>
</template>
<script>
import VueTypeaheadBootstrap from 'vue-typeahead-bootstrap';
import axios from 'axios'

export default {
  props: {
    value: {
      type: String,
      required: false,
      default: null
    },
  },
  components: { VueTypeaheadBootstrap },
  data () {
    return {
      currentValue: null,
      timezones: []
    }
  },
  computed: {
    valid() {
      return !this.currentValue || !this.timezones.length || this.timezones.includes(this.currentValue)
    }
  },
  watch: {
    valid(newValue) {
      this.$emit('update:valid', newValue)
    },
    currentValue(newValue) {
      console.log("Current timezone value", newValue)
      this.$emit('update:timezone', newValue)
    }
  },
  async mounted() {
    this.currentValue = this.value

    const ret = await axios.get('/api/timezones')

    if (ret.status && ret.status === 200 && ret.data) {
      this.timezones = ret.data.map(t => t.name)
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';

/deep/ .invalid input {
  border: 2px solid $brand-danger;
}
</style>