// This mixin includes lots of function relating to groups.
// TODO In due course the group will move into the store and we'll just pass the id.  All the other props will then
// become computed data in here.

export default {
  props: {
    idgroups: {
      type: Number,
      required: true
    }
  },
  computed: {
    group() {
      return this.$store.getters['groups/get'](this.idgroups)
    },
    volunteers() {
      let ret = []
      if (this.group && this.group.volunteers) {
        ret = this.group.volunteers
      }

      console.log("Volunteers", ret)
      return ret
    },
    canedit() {
      return this.group ? this.group.canedit : false
    },
    ingroup() {
      return this.group ? this.group.ingroup : false
    }
  }
}