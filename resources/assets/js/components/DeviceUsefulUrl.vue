<template>
  <div class="device-field-row mb-2">
    <b-input value="url.url" :placeholder="translatedUsefulRepairURL" />
    <div />
  </div>
  <div class="device-field-row mb-2">
<!--    TODO Select for url source, and all the action function-->
    <b-input value="url.url" :placeholder="translatedUsefulRepairURL" />
    <div />
  </div>
</template>
<script>
export default {
  props: {
    url: {
      type: Object,
      required: false,
      default: null
    }
  },
  computed: {
    translatedUsefulRepairURL() {
      return this.$lang.get('devices.useful_repair_urls_explanation')
    }
  }
}
</script>