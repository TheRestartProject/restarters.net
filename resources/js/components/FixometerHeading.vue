<template>
  <div>
    <div class="d-flex justify-content-between mb-3">
      <div class="d-flex">
        <h1>{{ __('devices.fixometer') }}</h1>
        <b-img src="/images/fixometer_doodle.svg" class="ml-4 d-none d-md-block" />
      </div>
      <div>
        <b-btn variant="primary" @click="addData">
          {{ __('devices.add_data_button') }}
        </b-btn>
      </div>
    </div>
    <hr class="border-top-very-thick" />
    <h2>
      {{ __('devices.global_impact') }}
    </h2>
    <p>
      <b v-html="__('devices.huge_impact')" />
      <span v-html="__('devices.huge_impact_2')" />
    </p>
    <AddDataModal ref="addData" />
  </div>
</template>
<script>
import AddDataModal from "./AddDataModal.vue";

export default {
  components: { AddDataModal },
  methods: {
    addData() {
      this.$refs.addData.show()
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.border-top-very-thick {
  border-top: 5px solid $black;
}
</style>
