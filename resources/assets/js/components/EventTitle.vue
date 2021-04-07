<template>
  <component :is="component">
    <!-- eslint-disable-next-line -->
    {{ event.venue ? event.venue : event.location }}<b-badge v-if="event.online" variant="primary" pill class="nounderline">{{ translatedOnline }}</b-badge>
  </component>
</template>
<script>
export default {
  props: {
    idevents: {
      type: Number,
      required: true
    },
    component: {
      type: String,
      required: true
    }
  },
  computed: {
    event() {
      return this.$store.getters['events/get'](this.idevents)
    },
    translatedOnline() {
      return this.$lang.get('events.online')
    }
  }
}
</script>
<style scoped lang="scss">
.nounderline {
  text-decoration: none !important;
}
</style>