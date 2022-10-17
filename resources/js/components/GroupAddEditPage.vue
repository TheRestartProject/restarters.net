<template>
  <div>
    <h1>{{ __('general.new_group') }}</h1>
    <b-card no-body class="box mt-4">
      <b-card-body class="p-4">
        <GroupAddEdit :initial-group="initialGroup" />
      </b-card-body>
    </b-card>
  </div>
</template>
<script>
import GroupAddEdit from './GroupAddEdit'
import auth from '../mixins/auth'

export default {
  components: {GroupAddEdit},
  mixins: [ auth ],
  props: {
    initialGroup: {
      type: Object,
      required: false,
      default: null
    },
  },
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.box {
  background-color: $white;
  box-shadow: 5px 5px $black;
  border: 1px solid $black;
  border-radius: 0;
}
</style>