<template>
  <div>
    <div class="d-flex justify-content-between mb-3">
      <div class="d-flex">
        <h1>{{ translatedFixometer }}</h1>
        <b-img src="/images/fixometer_doodle.svg" class="ml-4" />
      </div>
      <div>
        <b-btn variant="primary" data-target="#add-device-modal" data-toggle="modal">
          {{ translatedAddData }}
        </b-btn>
      </div>
    </div>
    <hr class="border-top-very-thick" />
    <h2>
      {{ translatedOurGlobalImpact}}
    </h2>
    <p>
      <b>{{ translatedHugeImpact}}</b>
      <span v-html="translatedImpactReadMore" />
    </p>
  </div>
</template>
<script>

export default {
  computed: {
    translatedFixometer() {
      return this.$lang.get('devices.fixometer')
    },
    translatedAddData() {
      return this.$lang.get('devices.add_data_button')
    },
    translatedOurGlobalImpact() {
      return this.$lang.get('devices.global_impact')
    },
    translatedHugeImpact() {
      return this.$lang.get('devices.huge_impact')
    },
    translatedImpactReadMore() {
      return this.$lang.get('devices.impact_read_more')
    },
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