<template>
  <div>
    <label for="postcode" class="mt-3">{{ __('groups.timezone') }}:</label>
    <vue-typeahead-bootstrap
        v-model="currentValue"
        :maxMatches="3"
        :data="timezones"
        :minMatchingChars="1"
        inputClass="form-control field timezone"
    />
    <small class="form-text text-muted" v-if="inherit">
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
    inherit: {
      type: Boolean,
      required: false,
      default: false
    },
    required: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  components: { VueTypeaheadBootstrap },
  data () {
    return {
      currentValue: null,
      timezones: []
    }
  },
  async mounted() {
    this.currentValue = this.value

    const ret = await axios.get('/api/timezones')

    if (ret.status && ret.status === 200 && ret.data) {
      this.timezones = ret.data.map(t => t.name)
    }

    if (this.required) {
      // The plugin doesn't let us set the required attribute - so do that manually here.
      const input = this.$el.querySelector('input')
      input.required = true
    }
  }
}
</script>