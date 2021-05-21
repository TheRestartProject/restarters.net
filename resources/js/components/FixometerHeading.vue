<template>
  <div>
    <div class="d-flex justify-content-between mb-3">
      <div class="d-flex">
        <h1>{{ __('devices.fixometer') }}</h1>
        <b-img src="/images/fixometer_doodle.svg" class="ml-4 d-none d-md-block" />
      </div>
      <div>
        <b-btn variant="primary" data-target="#add-device-modal" data-toggle="modal">
          {{ __('devices.add_data_button') }}
        </b-btn>
      </div>
    </div>
    <hr class="border-top-very-thick" />
    <h2>
      {{ __('devices.global_impact') }}
    </h2>
    <p>
      <b>{{ __('devices.huge_impact') }}</b>
      <span v-html="__('devices.impact_read_more')" />
    </p>
  </div>
</template>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.border-top-very-thick {
  border-top: 5px solid $black;
}

</style>