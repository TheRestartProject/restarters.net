<template>
  <div>
    <b-card no-body v-if="box" class="box mt-4">
      <b-card-body class="p-4">
        <GroupAddEdit :idgroups="idgroups" :can-approve="canApprove" :can-network="canNetwork" />
      </b-card-body>
    </b-card>
    <GroupAddEdit :idgroups="idgroups" :can-approve="canApprove" :can-network="canNetwork" v-else />
  </div>
</template>
<script>
import GroupAddEdit from './GroupAddEdit.vue'
import auth from '../mixins/auth'

export default {
  components: {GroupAddEdit},
  mixins: [ auth ],
  props: {
    idgroups: {
      type: Number,
      required: false,
      default: null
    },
    canApprove: {
      type: Boolean,
      required: false,
      default: false
    },
    canNetwork: {
      type: Boolean,
      required: false,
      default: false
    },
    box: {
      type: Boolean,
      required: false,
      default: false
    }
  },
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import 'bootstrap/scss/functions';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/mixins/_breakpoints';

.box {
  background-color: $white;
  box-shadow: 5px 5px $black;
  border: 1px solid $black;
  border-radius: 0;
}
</style>
