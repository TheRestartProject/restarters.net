<template>
  <div class="layout md-primary-black">
    <div class="title mb-2 ml-3">
      {{ __('devices.latest_data') }}
      <span class="icon mt-2 mb-2">
        <b-img src="/images/clap_doodle.svg" class="img" />
      </span>
    </div>
    <div class="description pt-4 m-3 font-weight-bold">
      <a :href="'/group/view/' + this.latestData.the_group.idgroups">{{ latestData.the_group.name }}</a>
      <span v-html="translatedWastePrevented" />
    </div>
  </div>
</template>
<script>
export default {
  props: {
    latestData: {
      type: Object,
      required: true
    }
  },
  computed: {
    translatedWastePrevented() {
      return this.$lang.get('devices.group_prevented', {
        idevents: this.latestData.id_events,
        amount: this.latestData.waste_prevented
      })
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.layout {
  display: grid;
  align-items: center;
  padding: 5px;
  grid-template-columns: 1fr;
  grid-template-rows: 55px 54px auto;
  background-color: $brand-light;
  color: black;
  border: 1px solid black;
  box-shadow: $black $shadow $shadow 0px 0px;
  margin-top: 1rem !important;
}

.title {
  font-family: $font-family-third;
  font-size: 30px;
  font-weight: bold;
  background-color: $brand-light;
  color: black;
}

.img {
  height: 46px;
}

.icon {
  height: 41px;
}

/deep/ a {
  color: black;
  text-decoration: underline;
}

.description {
  border-top: 3px dashed #222;
  font-family: $font-family-third;
  font-size: 18px;
}
</style>
